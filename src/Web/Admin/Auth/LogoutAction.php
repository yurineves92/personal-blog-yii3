<?php

declare(strict_types=1);

namespace App\Web\Admin\Auth;

use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\User\CurrentUser;

final readonly class LogoutAction
{
    public function __construct(
        private CurrentUser $currentUser,
        private Redirector $redirector,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->currentUser->logout();
        return $this->redirector->toRoute('admin/login');
    }
}
