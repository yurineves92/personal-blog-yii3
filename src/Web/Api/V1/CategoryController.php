<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use App\Auth\Rbac\Permission;
use App\Blog\Category;
use App\Blog\CategoryRepository;
use App\Shared\Slugger;
use App\Web\Admin\Category\CategoryForm;
use App\Web\Api\ApiResponder;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\User\CurrentUser;

final readonly class CategoryController
{
    public function __construct(
        private ApiResponder $responder,
        private CategoryRepository $categories,
        private FormHydrator $formHydrator,
        private CurrentUser $currentUser,
        private CurrentRoute $currentRoute,
    ) {}

    #[OA\Get(
        path: '/categories',
        operationId: 'listCategories',
        summary: 'Listar categorias',
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                ]),
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized', response: 401),
        ],
    )]
    public function index(): ResponseInterface
    {
        return $this->responder->json([
            'data' => array_map(Resource::category(...), $this->categories->findAll()),
        ]);
    }

    #[OA\Post(
        path: '/categories',
        operationId: 'createCategory',
        summary: 'Criar categoria (admin)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        tags: ['Categories'],
        responses: [
            new OA\Response(response: 201, description: 'Criada', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->currentUser->can(Permission::CATEGORY_MANAGE)) {
            return $this->responder->forbidden();
        }
        return $this->save($request, null);
    }

    #[OA\Put(
        path: '/categories/{id}',
        operationId: 'updateCategory',
        summary: 'Atualizar categoria (admin)',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CategoryInput')),
        tags: ['Categories'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Atualizada', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationFailed', response: 422),
        ],
    )]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->currentUser->can(Permission::CATEGORY_MANAGE)) {
            return $this->responder->forbidden();
        }
        $category = $this->categories->findById((int) $this->currentRoute->getArgument('id'));
        if ($category === null) {
            return $this->responder->notFound('Categoria não encontrada.');
        }
        return $this->save($request, $category);
    }

    #[OA\Delete(
        path: '/categories/{id}',
        operationId: 'deleteCategory',
        description: 'Os posts da categoria ficam sem categoria.',
        summary: 'Excluir categoria (admin)',
        tags: ['Categories'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 204, description: 'Excluída'),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function delete(): ResponseInterface
    {
        if (!$this->currentUser->can(Permission::CATEGORY_MANAGE)) {
            return $this->responder->forbidden();
        }
        $category = $this->categories->findById((int) $this->currentRoute->getArgument('id'));
        if ($category === null) {
            return $this->responder->notFound('Categoria não encontrada.');
        }
        $this->categories->delete($category->id);
        return $this->responder->noContent();
    }

    private function save(ServerRequestInterface $request, ?Category $category): ResponseInterface
    {
        $body = ApiResponder::body($request);
        if ($body === null) {
            return $this->responder->problem(Status::BAD_REQUEST, 'JSON inválido.');
        }

        $form = $category !== null ? CategoryForm::fromCategory($category) : new CategoryForm();
        $this->formHydrator->populate($form, $body, ['name' => 'name', 'slug' => 'slug', 'description' => 'description'], strict: true, scope: '');
        $this->formHydrator->validate($form);
        if ($form->slug !== null && $this->categories->slugExists($form->slug, $category?->id)) {
            $form->addError('Já existe uma categoria com este slug.', ['slug']);
        }
        if (!$form->isValid()) {
            return $this->responder->validationFailed($form->getValidationResult()->getErrorMessagesIndexedByProperty());
        }

        $data = [
            'name' => trim($form->name),
            'slug' => $form->slug ?? Slugger::unique(
                $form->name,
                fn(string $slug): bool => $this->categories->slugExists($slug, $category?->id),
                120,
            ),
            'description' => $form->description !== null ? trim($form->description) : null,
        ];

        if ($category === null) {
            $id = $this->categories->insert($data);
        } else {
            $id = $category->id;
            $this->categories->update($id, $data);
        }

        $saved = $this->categories->findById($id);
        assert($saved !== null);

        return $category === null
            ? $this->responder->created(Resource::category($saved))
            : $this->responder->json(Resource::category($saved));
    }
}
