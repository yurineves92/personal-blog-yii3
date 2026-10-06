<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Blog\PostRepository;
use App\Blog\PostWorkflow;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ReviewQueueAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PostRepository $posts,
        private PostWorkflow $workflow,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/review', [
                'pending' => $this->posts->pendingReview(),
                'workflow' => $this->workflow,
            ]);
    }
}
