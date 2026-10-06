<?php

declare(strict_types=1);

namespace App\Web\Admin\User;

use App\Auth\Rbac\RbacItemsStorage;
use App\User\UserRepository;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $users,
        private ItemsStorageInterface $rbacItems,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $permissions = [];
        foreach (RbacItemsStorage::matrix() as $role => $names) {
            foreach ($names as $name) {
                $permissions[$name] ??= $this->rbacItems->getPermission($name)?->getDescription() ?? $name;
            }
        }

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/index', [
                'users' => $this->users->findAll(),
                'matrix' => RbacItemsStorage::matrix(),
                'permissions' => $permissions,
            ]);
    }
}
