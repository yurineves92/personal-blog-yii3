<?php

declare(strict_types=1);

namespace App\Web\Admin\Auth;

use App\User\UserRepository;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class LoginAction
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
        if (!$this->currentUser->isGuest()) {
            return $this->redirector->toRoute('admin/dashboard');
        }

        $form = new LoginForm();
        $form->return = (string) ($request->getQueryParams()['return'] ?? '');

        if ($this->formHydrator->populateFromPostAndValidate($form, $request)) {
            $user = $this->users->findByEmail($form->email);

            if ($user === null || !$this->passwordHasher->validate($form->password, $user->passwordHash)) {
                $form->addError('E-mail ou senha incorretos.', ['password']);
            } elseif (!$user->isActive) {
                $form->addError('Esta conta está desativada. Fale com um administrador.', ['email']);
            } elseif ($this->currentUser->login($user)) {
                $this->users->touchLastLogin($user->id);
                return $this->redirector->toUrl($this->safeReturnUrl($form->return));
            }
        }

        $form->password = '';

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/login', ['form' => $form]);
    }

    /**
     * Evita open redirect: só aceita caminhos internos do painel.
     */
    private function safeReturnUrl(string $return): string
    {
        if (str_starts_with($return, '/admin') && !str_starts_with($return, '//') && !str_contains($return, '\\')) {
            return $return;
        }
        return '/admin';
    }
}
