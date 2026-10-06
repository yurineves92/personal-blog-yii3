<?php

declare(strict_types=1);

namespace App\Web\Api;

use App\Api\ApiTokenRepository;
use App\Auth\Rbac\Permission;
use App\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\User\CurrentUser;

/**
 * Autentica a API exclusivamente por `Authorization: Bearer <token>`.
 *
 * O cookie de sessão do painel é ignorado de propósito: sem cookie não há risco de CSRF,
 * por isso as rotas da API ficam fora da validação de token CSRF.
 * O usuário do token vira a identidade atual, então `$currentUser->can()` e o RBAC funcionam igual ao painel.
 */
final readonly class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ApiTokenRepository $tokens,
        private UserRepository $users,
        private CurrentUser $currentUser,
        private ApiResponder $responder,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return $this->responder->unauthorized();
        }

        $userId = $this->tokens->findUserIdByToken($matches[1]);
        $user = $userId !== null ? $this->users->findIdentity((string) $userId) : null;
        if ($user === null) {
            return $this->responder->unauthorized();
        }

        $this->currentUser->overrideIdentity($user);
        try {
            if (!$this->currentUser->can(Permission::CMS_ACCESS)) {
                return $this->responder->forbidden();
            }
            return $handler->handle($request);
        } finally {
            $this->currentUser->clearIdentityOverride();
        }
    }
}
