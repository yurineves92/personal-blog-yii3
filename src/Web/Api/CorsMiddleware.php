<?php

declare(strict_types=1);

namespace App\Web\Api;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

/**
 * CORS aberto para os endpoints públicos (somente leitura, sem cookies nem credenciais),
 * permitindo que clientes em outra origem — como o app PWA — consumam o conteúdo.
 */
final readonly class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $request->getMethod() === Method::OPTIONS
            ? $this->responseFactory->createResponse(Status::NO_CONTENT)
            : $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
