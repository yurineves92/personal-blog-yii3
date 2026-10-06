<?php

declare(strict_types=1);

namespace App\Web\Site\Landing;

use App\Blog\CategoryRepository;
use App\Blog\PostRepository;
use App\Blog\PostStatus;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PostRepository $posts,
        private CategoryRepository $categories,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $latest = $this->posts->latestPublished(7);

        return $this->viewRenderer->render(__DIR__ . '/template', [
            'featured' => $latest[0] ?? null,
            'latest' => array_slice($latest, 1),
            'mostRead' => $this->posts->mostRead(4),
            'categories' => $this->categories->findWithPublishedPosts(),
            'publishedCount' => $this->posts->countByStatus()[PostStatus::Published->value],
        ]);
    }
}
