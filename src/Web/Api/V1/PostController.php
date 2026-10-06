<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use App\Auth\Rbac\Permission;
use App\Blog\Post;
use App\Blog\PostRepository;
use App\Blog\PostService;
use App\Blog\PostStatus;
use App\Blog\PostWorkflow;
use App\Web\Admin\Post\PostForm;
use App\Web\Api\ApiResponder;
use DomainException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\User\CurrentUser;

final readonly class PostController
{
    /** Campos JSON (snake_case) => propriedades do PostForm. */
    private const FIELD_MAP = [
        'title' => 'title',
        'slug' => 'slug',
        'excerpt' => 'excerpt',
        'content' => 'content',
        'coverUrl' => 'cover_url',
        'categoryId' => 'category_id',
    ];

    public function __construct(
        private ApiResponder $responder,
        private Resource $resource,
        private PostRepository $posts,
        private PostService $postService,
        private PostWorkflow $workflow,
        private FormHydrator $formHydrator,
        private CurrentUser $currentUser,
        private CurrentRoute $currentRoute,
    ) {}

    #[OA\Get(
        path: '/posts',
        operationId: 'listPosts',
        description: 'Editores recebem apenas os próprios posts; revisores e admins recebem todos.',
        summary: 'Listar posts',
        tags: ['Posts'],
        parameters: [
            new OA\QueryParameter(name: 'status', schema: new OA\Schema(type: 'string', enum: ['draft', 'pending', 'published', 'rejected'])),
            new OA\QueryParameter(name: 'q', description: 'Busca em título, resumo e conteúdo', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 20, maximum: 50, minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página de posts (sem o campo content)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Post')),
                    new OA\Property(property: 'meta', properties: [
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page_count', type: 'integer'),
                    ], type: 'object'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
        ],
    )]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $perPage = min(50, max(1, (int) ($query['per_page'] ?? 20)));
        $authorId = $this->currentUser->can(Permission::POST_VIEW_ALL) ? null : (int) $this->currentUser->getId();

        $page = $this->posts->adminPage(
            max(1, (int) ($query['page'] ?? 1)),
            $perPage,
            PostStatus::tryFrom((string) ($query['status'] ?? ''))?->value,
            $authorId,
            (string) ($query['q'] ?? ''),
        );

        return $this->responder->json([
            'data' => array_map(fn(Post $p) => $this->resource->post($p, withContent: false), $page->items),
            'meta' => [
                'page' => $page->page,
                'per_page' => $page->perPage,
                'total' => $page->total,
                'page_count' => $page->pageCount,
            ],
        ]);
    }

    #[OA\Get(
        path: '/posts/{id}',
        operationId: 'getPost',
        summary: 'Detalhar post',
        tags: ['Posts'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Post')),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function view(): ResponseInterface
    {
        $post = $this->findPost();
        // Posts de outros autores ficam invisíveis (404) para quem não pode vê-los.
        if ($post === null || !$this->workflow->canView($post)) {
            return $this->responder->notFound('Post não encontrado.');
        }
        return $this->responder->json($this->resource->post($post));
    }

    #[OA\Post(
        path: '/posts',
        operationId: 'createPost',
        description: 'O post é criado como rascunho e o usuário autenticado vira o autor.',
        summary: 'Criar post',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PostInput')),
        tags: ['Posts'],
        responses: [
            new OA\Response(response: 201, description: 'Criado', content: new OA\JsonContent(ref: '#/components/schemas/Post')),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->currentUser->can(Permission::POST_CREATE)) {
            return $this->responder->forbidden();
        }
        return $this->save($request, null);
    }

    #[OA\Put(
        path: '/posts/{id}',
        operationId: 'updatePost',
        description: 'Atualização parcial: envie apenas os campos que deseja alterar. Também aceita PATCH.',
        summary: 'Atualizar post',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PostInput')),
        tags: ['Posts'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Atualizado', content: new OA\JsonContent(ref: '#/components/schemas/Post')),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->findPost();
        if ($post === null || !$this->workflow->canView($post)) {
            return $this->responder->notFound('Post não encontrado.');
        }
        if (!$this->workflow->canEdit($post)) {
            return $this->responder->forbidden(
                $post->status === PostStatus::Pending
                    ? 'O post está em revisão e não pode ser editado pelo autor.'
                    : 'Você não pode editar este post.',
            );
        }
        return $this->save($request, $post);
    }

    #[OA\Delete(
        path: '/posts/{id}',
        operationId: 'deletePost',
        summary: 'Excluir post',
        tags: ['Posts'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 204, description: 'Excluído'),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function delete(): ResponseInterface
    {
        $post = $this->findPost();
        if ($post === null || !$this->workflow->canView($post)) {
            return $this->responder->notFound('Post não encontrado.');
        }
        if (!$this->workflow->canDelete($post)) {
            return $this->responder->forbidden('Você não pode excluir este post.');
        }
        $this->posts->delete($post->id);
        return $this->responder->noContent();
    }

    #[OA\Post(
        path: '/posts/{id}/{transition}',
        operationId: 'transitionPost',
        description: "Aplica uma transição do fluxo editorial:\n\n- `submit`: autor envia para revisão\n- `approve`: revisor/admin publica\n- `reject`: revisor devolve ao autor (exige `note`)\n- `unpublish`: tira do ar e volta para rascunho",
        summary: 'Fluxo editorial',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'note', description: 'Obrigatório em reject', type: 'string', example: 'Faltou um exemplo de código.'),
        ])),
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/id'),
            new OA\PathParameter(name: 'transition', required: true, schema: new OA\Schema(type: 'string', enum: ['submit', 'approve', 'reject', 'unpublish'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transição aplicada',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Post aprovado e publicado.'),
                    new OA\Property(property: 'post', ref: '#/components/schemas/Post'),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(response: 409, description: 'Transição não permitida no estado atual', content: new OA\JsonContent(ref: '#/components/schemas/Problem')),
        ],
    )]
    public function transition(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->findPost();
        if ($post === null || !$this->workflow->canView($post)) {
            return $this->responder->notFound('Post não encontrado.');
        }

        $body = ApiResponder::body($request) ?? [];
        $note = isset($body['note']) ? (string) $body['note'] : null;
        $transition = (string) $this->currentRoute->getArgument('transition');

        try {
            $message = $this->workflow->apply($transition, $post, $note);
        } catch (DomainException $e) {
            return $this->responder->problem(Status::CONFLICT, $e->getMessage());
        }

        $fresh = $this->posts->findById($post->id);
        assert($fresh !== null);

        return $this->responder->json(['message' => $message, 'post' => $this->resource->post($fresh)]);
    }

    private function save(ServerRequestInterface $request, ?Post $post): ResponseInterface
    {
        $body = ApiResponder::body($request);
        if ($body === null) {
            return $this->responder->problem(Status::BAD_REQUEST, 'JSON inválido.');
        }

        $form = $post !== null ? PostForm::fromPost($post) : new PostForm();
        $this->formHydrator->populate($form, $body, self::FIELD_MAP, strict: true, scope: '');
        $this->formHydrator->validate($form);
        $this->postService->validate($form, $post);

        if (!$form->isValid()) {
            return $this->responder->validationFailed(
                Resource::errors($form->getValidationResult()->getErrorMessagesIndexedByProperty()),
            );
        }

        $id = $this->postService->save($form, $post, (int) $this->currentUser->getId());
        $saved = $this->posts->findById($id);
        assert($saved !== null);

        return $post === null
            ? $this->responder->created($this->resource->post($saved))
            : $this->responder->json($this->resource->post($saved));
    }

    private function findPost(): ?Post
    {
        return $this->posts->findById((int) $this->currentRoute->getArgument('id'));
    }
}
