<?php

declare(strict_types=1);

namespace App\Web\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenMiddleware;

/**
 * Valida o token CSRF em todo o site, exceto na API REST, que autentica só por header
 * Bearer (ver {@see \App\Web\Api\ApiAuthMiddleware}) e por isso não é vulnerável a CSRF.
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public const API_PREFIX = '/admin/api/v1/';

    public function __construct(
        private CsrfTokenMiddleware $csrf,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (str_starts_with($request->getUri()->getPath(), self::API_PREFIX)) {
            return $handler->handle($request);
        }

        return $this->csrf->process($request, $handler);
    }
}
