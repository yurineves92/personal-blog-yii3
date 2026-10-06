<?php

declare(strict_types=1);

namespace App\Web\Admin\Dashboard;

use App\Auth\Rbac\Permission;
use App\Blog\CategoryRepository;
use App\Blog\PostRepository;
use App\Blog\PostStatus;
use App\User\User;
use App\User\UserRepository;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CurrentUser $currentUser,
        private PostRepository $posts,
        private CategoryRepository $categories,
        private UserRepository $users,
    ) {}

    public function __invoke(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->currentUser->getIdentity();
        $viewAll = $this->currentUser->can(Permission::POST_VIEW_ALL);
        $authorFilter = $viewAll ? null : $user->id;

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/template', [
                'user' => $user,
                'viewAll' => $viewAll,
                'counts' => $this->posts->countByStatus($authorFilter),
                'myCounts' => $this->posts->countByStatus($user->id),
                'recent' => $this->posts->adminPage(1, 6, authorId: $authorFilter)->items,
                'myRejected' => $this->posts->adminPage(1, 5, PostStatus::Rejected->value, $user->id)->items,
                'pending' => $this->currentUser->can(Permission::POST_REVIEW) ? $this->posts->pendingReview(5) : [],
                'totalViews' => $viewAll ? $this->posts->totalViews() : null,
                'userCount' => $this->currentUser->can(Permission::USER_MANAGE) ? $this->users->count() : null,
                'categoryCount' => $this->currentUser->can(Permission::CATEGORY_MANAGE) ? $this->categories->count() : null,
            ]);
    }
}
