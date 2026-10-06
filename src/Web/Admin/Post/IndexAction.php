<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Auth\Rbac\Permission;
use App\Blog\PostRepository;
use App\Blog\PostStatus;
use App\Blog\PostWorkflow;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private const PER_PAGE = 15;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CurrentUser $currentUser,
        private PostRepository $posts,
        private PostWorkflow $workflow,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $status = PostStatus::tryFrom((string) ($query['status'] ?? ''))?->value;
        $search = trim((string) ($query['q'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));

        // Editores enxergam apenas os próprios posts.
        $viewAll = $this->currentUser->can(Permission::POST_VIEW_ALL);
        $authorId = $viewAll ? null : (int) $this->currentUser->getId();

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/index', [
                'paginator' => $this->posts->adminPage($page, self::PER_PAGE, $status, $authorId, $search),
                'counts' => $this->posts->countByStatus($authorId),
                'status' => $status,
                'search' => $search,
                'viewAll' => $viewAll,
                'workflow' => $this->workflow,
            ]);
    }
}
