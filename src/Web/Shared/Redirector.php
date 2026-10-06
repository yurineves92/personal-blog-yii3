<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class Redirector
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private FlashInterface $flash,
    ) {}

    public function toRoute(string $name, array $arguments = [], array $query = []): ResponseInterface
    {
        return $this->toUrl($this->urlGenerator->generate($name, $arguments, $query));
    }

    public function toUrl(string $url): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $url);
    }

    /**
     * Redireciona registrando uma mensagem flash (`success`, `error`, `info`).
     */
    public function withFlash(string $type, string $message, string $route, array $arguments = [], array $query = []): ResponseInterface
    {
        $this->flash->set($type, $message);
        return $this->toRoute($route, $arguments, $query);
    }
}
