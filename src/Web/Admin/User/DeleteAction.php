<?php

declare(strict_types=1);

namespace App\Web\Admin\User;

use App\User\Role;
use App\User\UserRepository;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\User\CurrentUser;

final readonly class DeleteAction
{
    public function __construct(
        private CurrentRoute $currentRoute,
        private CurrentUser $currentUser,
        private UserRepository $users,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $user = $this->users->findById((int) $this->currentRoute->getArgument('id'));
        if ($user === null) {
            return $this->errorPage->notFound('Usuário não encontrado.');
        }

        $error = match (true) {
            $user->getId() === $this->currentUser->getId() => 'Você não pode excluir a própria conta.',
            $user->role === Role::Admin && $user->isActive && $this->users->countActiveAdmins() <= 1 => 'Não é possível excluir o único administrador ativo.',
            $this->users->hasPosts($user->id) => 'Este usuário possui posts. Desative a conta em vez de excluí-la.',
            default => null,
        };

        if ($error !== null) {
            return $this->redirector->withFlash('error', $error, 'admin/user/index');
        }

        $this->users->delete($user->id);

        return $this->redirector->withFlash('success', 'Usuário “' . $user->name . '” excluído.', 'admin/user/index');
    }
}
