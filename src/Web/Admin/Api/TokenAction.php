<?php

declare(strict_types=1);

namespace App\Web\Admin\Api;

use App\Api\ApiTokenRepository;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

/**
 * Cria (POST /admin/api/tokens) e revoga (POST /admin/api/tokens/{id}/revogar) tokens do usuário logado.
 */
final readonly class TokenAction
{
    public function __construct(
        private CurrentUser $currentUser,
        private CurrentRoute $currentRoute,
        private ApiTokenRepository $tokens,
        private FlashInterface $flash,
        private Redirector $redirector,
    ) {}

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $name = trim((string) (((array) $request->getParsedBody())['name'] ?? '')) ?: 'painel';
        $created = $this->tokens->create((int) $this->currentUser->getId(), $name);

        $this->flash->set(DocsAction::FLASH_NEW_TOKEN, $created['token']);

        return $this->redirector->withFlash('success', 'Token criado. Copie agora: ele não será exibido novamente.', 'admin/api');
    }

    public function revoke(): ResponseInterface
    {
        $revoked = $this->tokens->revoke((int) $this->currentRoute->getArgument('id'), (int) $this->currentUser->getId());

        return $revoked
            ? $this->redirector->withFlash('success', 'Token revogado.', 'admin/api')
            : $this->redirector->withFlash('error', 'Token não encontrado.', 'admin/api');
    }
}
