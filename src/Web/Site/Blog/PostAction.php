<?php

declare(strict_types=1);

namespace App\Web\Site\Blog;

use App\Blog\PostRepository;
use App\Shared\Markdown;
use App\Web\NotFound\NotFoundHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class PostAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PostRepository $posts,
        private Markdown $markdown,
        private CurrentRoute $currentRoute,
        private NotFoundHandler $notFound,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->posts->findPublishedBySlug((string) $this->currentRoute->getArgument('slug'));
        if ($post === null) {
            return $this->notFound->handle($request);
        }

        $this->posts->incrementViews($post->id);

        $related = $post->categoryId !== null
            ? $this->posts->latestPublished(3, $post->id, $post->categoryId)
            : [];
        if (count($related) < 3) {
            $ids = array_map(static fn($p) => $p->id, $related);
            foreach ($this->posts->latestPublished(6, $post->id) as $candidate) {
                if (count($related) >= 3) {
                    break;
                }
                if (!in_array($candidate->id, $ids, true)) {
                    $related[] = $candidate;
                }
            }
        }

        return $this->viewRenderer->render(__DIR__ . '/post', [
            'post' => $post,
            'html' => $this->markdown->toHtml($post->content),
            'readingMinutes' => Markdown::readingMinutes($post->content),
            'related' => $related,
        ]);
    }
}
