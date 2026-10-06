<?php

declare(strict_types=1);

namespace App\Web\Shared\Middleware;

use App\Web\Shared\Redirector;
use App\Web\Shared\ErrorPage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\User\CurrentUser;

/**
 * Exige usuário autenticado e, opcionalmente, uma permissão RBAC.
 *
 * Nas rotas:
 *   ->middleware(AccessMiddleware::class)                                       // só login
 *   ->middleware(AccessMiddleware::permission(Permission::USER_MANAGE))         // login + permissão
 */
final class AccessMiddleware implements MiddlewareInterface
{
    private ?string $permission = null;

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly Redirector $redirector,
        private readonly ErrorPage $errorPage,
    ) {}

    /**
     * Definição de middleware (array definition) para uso nas rotas.
     */
    public static function permission(string $permission): array
    {
        return [
            'class' => self::class,
            'withPermission()' => [$permission],
        ];
    }

    public function withPermission(string $permission): self
    {
        $new = clone $this;
        $new->permission = $permission;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->currentUser->isGuest()) {
            $uri = $request->getUri();
            $return = $uri->getPath() . ($uri->getQuery() !== '' ? '?' . $uri->getQuery() : '');
            return $this->redirector->toRoute('admin/login', query: ['return' => $return]);
        }

        if ($this->permission !== null && !$this->currentUser->can($this->permission)) {
            return $this->errorPage->forbidden();
        }

        return $handler->handle($request);
    }
}
