<?php

declare(strict_types=1);

namespace App\Web\Admin\User;

use App\User\Role;
use App\User\UserRepository;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class EditAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private FormHydrator $formHydrator,
        private CurrentRoute $currentRoute,
        private CurrentUser $currentUser,
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->currentRoute->getArgument('id');
        $user = $id !== null ? $this->users->findById((int) $id) : null;
        if ($id !== null && $user === null) {
            return $this->errorPage->notFound('Usuário não encontrado.');
        }

        $isSelf = $user !== null && $user->getId() === $this->currentUser->getId();
        $form = $user !== null ? UserForm::fromUser($user) : new UserForm();

        if ($this->formHydrator->populateFromPost($form, $request)) {
            $this->formHydrator->validate($form);

            if ($this->users->emailExists($form->email, $user?->id)) {
                $form->addError('Este e-mail já está em uso.', ['email']);
            }
            if ($user === null && $form->password === null) {
                $form->addError('Defina uma senha inicial.', ['password']);
            }
            // Não deixa o sistema sem administrador ativo.
            if ($user?->role === Role::Admin && ($form->role !== Role::Admin->value || !$form->isActive)
                && $user->isActive && $this->users->countActiveAdmins() <= 1) {
                $form->addError('Este é o único administrador ativo. Promova outro usuário antes.', ['role']);
            }
            if ($isSelf && !$form->isActive) {
                $form->addError('Você não pode desativar a própria conta.', ['isActive']);
            }

            if ($form->isValid()) {
                $data = [
                    'name' => trim($form->name),
                    'email' => $form->email,
                    'role' => $form->role,
                    'bio' => $form->bio,
                    'is_active' => $form->isActive,
                ];
                if ($form->password !== null) {
                    $data['password_hash'] = $this->passwordHasher->hash($form->password);
                }

                if ($user === null) {
                    $this->users->insert($data);
                    $message = 'Usuário criado.';
                } else {
                    $this->users->update($user->id, $data);
                    $message = 'Usuário atualizado.';
                }

                return $this->redirector->withFlash('success', $message, 'admin/user/index');
            }
        }

        $form->password = null;

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/form', ['form' => $form, 'user' => $user, 'isSelf' => $isSelf]);
    }
}
