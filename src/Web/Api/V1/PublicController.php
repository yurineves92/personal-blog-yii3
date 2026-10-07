<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use App\Blog\Category;
use App\Blog\CategoryRepository;
use App\Blog\Post;
use App\Blog\PostRepository;
use App\Shared\Markdown;
use App\Site\SettingRepository;
use App\Web\Api\ApiResponder;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * Endpoints públicos e somente leitura: apenas conteúdo publicado, sem autenticação.
 * Pensados para clientes de leitura (ex.: o app PWA em Vue).
 */
final readonly class PublicController
{
    public function __construct(
        private ApiResponder $responder,
        private PostRepository $posts,
        private CategoryRepository $categories,
        private SettingRepository $settings,
        private Markdown $markdown,
        private CurrentRoute $currentRoute,
    ) {}

    #[OA\Get(
        path: '/public/site',
        operationId: 'publicSite',
        summary: 'Dados do site e do autor',
        security: [],
        tags: ['Public'],
        responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'object'))],
    )]
    public function site(): ResponseInterface
    {
        $s = $this->settings;
        return $this->responder->json([
            'name' => $s->get('site_name'),
            'tagline' => $s->get('site_tagline'),
            'hero_title' => $s->get('hero_title'),
            'hero_subtitle' => $s->get('hero_subtitle'),
            'about_title' => $s->get('about_title'),
            'about_text' => $s->get('about_text'),
            'author' => [
                'name' => $s->get('author_name'),
                'role' => $s->get('author_role'),
                'location' => $s->get('author_location'),
                'avatar' => $s->get('author_avatar'),
                'github' => $s->get('github_url'),
                'x' => $s->get('x_url'),
            ],
            'stack' => $s->list('stack'),
            'projects' => $s->projects(),
        ]);
    }

    #[OA\Get(
        path: '/public/posts',
        operationId: 'publicPosts',
        summary: 'Posts publicados',
        security: [],
        tags: ['Public'],
        parameters: [
            new OA\QueryParameter(name: 'category', description: 'Slug da categoria', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 12, maximum: 50, minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PublicPost')),
                    new OA\Property(property: 'meta', type: 'object'),
                ]),
            ),
        ],
    )]
    public function posts(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $categorySlug = trim((string) ($query['category'] ?? ''));
        $category = $categorySlug !== '' ? $this->categories->findBySlug($categorySlug) : null;
        if ($categorySlug !== '' && $category === null) {
            return $this->responder->notFound('Categoria não encontrada.');
        }

        $page = $this->posts->publishedPage(
            max(1, (int) ($query['page'] ?? 1)),
            min(50, max(1, (int) ($query['per_page'] ?? 12))),
            $category?->id,
            (string) ($query['q'] ?? ''),
        );

        return $this->responder->json([
            'data' => array_map(fn(Post $p) => $this->serialize($p), $page->items),
            'meta' => ['page' => $page->page, 'per_page' => $page->perPage, 'total' => $page->total, 'page_count' => $page->pageCount],
        ]);
    }

    #[OA\Get(
        path: '/public/posts/{slug}',
        operationId: 'publicPost',
        summary: 'Post publicado (com HTML renderizado)',
        security: [],
        tags: ['Public'],
        parameters: [new OA\PathParameter(name: 'slug', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PublicPost')),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(): ResponseInterface
    {
        $post = $this->posts->findPublishedBySlug((string) $this->currentRoute->getArgument('slug'));
        if ($post === null) {
            return $this->responder->notFound('Post não encontrado.');
        }
        $this->posts->incrementViews($post->id);

        return $this->responder->json($this->serialize($post) + [
            'content_html' => $this->markdown->toHtml($post->content),
            'related' => array_map(
                fn(Post $p) => $this->serialize($p),
                $this->posts->latestPublished(3, $post->id, $post->categoryId),
            ),
        ]);
    }

    #[OA\Get(
        path: '/public/categories',
        operationId: 'publicCategories',
        summary: 'Categorias com posts publicados',
        security: [],
        tags: ['Public'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')),
                ]),
            ),
        ],
    )]
    public function categories(): ResponseInterface
    {
        return $this->responder->json([
            'data' => array_map(
                static fn(Category $c) => Resource::category($c),
                $this->categories->findWithPublishedPosts(),
            ),
        ]);
    }

    private function serialize(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->summary(220),
            'cover_url' => $post->coverUrl,
            'category' => $post->categoryId !== null
                ? ['id' => $post->categoryId, 'name' => $post->categoryName, 'slug' => $post->categorySlug]
                : null,
            'author' => ['name' => $post->authorName],
            'reading_minutes' => Markdown::readingMinutes($post->content),
            'views' => $post->views,
            'url' => '/blog/' . $post->slug,
            'published_at' => Resource::date($post->publishedAt),
        ];
    }
}
