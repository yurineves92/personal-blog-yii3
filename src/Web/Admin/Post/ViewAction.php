<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Blog\PostRepository;
use App\Blog\PostWorkflow;
use App\Shared\Markdown;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\ErrorPage;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Pré-visualização do post no painel, com as ações do fluxo editorial.
 */
final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CurrentRoute $currentRoute,
        private PostRepository $posts,
        private PostWorkflow $workflow,
        private Markdown $markdown,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $post = $this->posts->findById((int) $this->currentRoute->getArgument('id'));
        if ($post === null) {
            return $this->errorPage->notFound('Post não encontrado.');
        }
        if (!$this->workflow->canView($post)) {
            return $this->errorPage->forbidden();
        }

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/view', [
                'post' => $post,
                'html' => $this->markdown->toHtml($post->content),
                'readingMinutes' => Markdown::readingMinutes($post->content),
                'workflow' => $this->workflow,
            ]);
    }
}
