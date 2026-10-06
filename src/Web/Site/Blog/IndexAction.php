<?php

declare(strict_types=1);

namespace App\Web\Site\Blog;

use App\Blog\CategoryRepository;
use App\Blog\PostRepository;
use App\Web\NotFound\NotFoundHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private const PER_PAGE = 9;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PostRepository $posts,
        private CategoryRepository $categories,
        private CurrentRoute $currentRoute,
        private NotFoundHandler $notFound,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $search = trim((string) ($query['q'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));

        $category = null;
        $slug = $this->currentRoute->getArgument('slug');
        if ($slug !== null) {
            $category = $this->categories->findBySlug($slug);
            if ($category === null) {
                return $this->notFound->handle($request);
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/index', [
            'paginator' => $this->posts->publishedPage($page, self::PER_PAGE, $category?->id, $search),
            'categories' => $this->categories->findWithPublishedPosts(),
            'category' => $category,
            'search' => $search,
        ]);
    }
}
