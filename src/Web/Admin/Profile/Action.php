<?php

declare(strict_types=1);

namespace App\Web\Admin\Profile;

use App\User\User;
use App\User\UserRepository;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private FormHydrator $formHydrator,
        private CurrentUser $currentUser,
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private Redirector $redirector,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $user */
        $user = $this->currentUser->getIdentity();
        $form = ProfileForm::fromUser($user);

        if ($this->formHydrator->populateFromPost($form, $request)) {
            $this->formHydrator->validate($form);

            if ($this->users->emailExists($form->email, $user->id)) {
                $form->addError('Este e-mail já está em uso.', ['email']);
            }
            if ($form->newPassword !== null
                && ($form->currentPassword === null || !$this->passwordHasher->validate($form->currentPassword, $user->passwordHash))) {
                $form->addError('Senha atual incorreta.', ['currentPassword']);
            }

            if ($form->isValid()) {
                $data = ['name' => trim($form->name), 'email' => $form->email, 'bio' => $form->bio];
                if ($form->newPassword !== null) {
                    $data['password_hash'] = $this->passwordHasher->hash($form->newPassword);
                }
                $this->users->update($user->id, $data);

                return $this->redirector->withFlash('success', 'Perfil atualizado.', 'admin/profile');
            }
        }

        $form->currentPassword = null;
        $form->newPassword = null;

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/template', ['form' => $form, 'user' => $user]);
    }
}
