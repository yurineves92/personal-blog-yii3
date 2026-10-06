<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Páginas de erro do painel (403/404) renderizadas no layout administrativo.
 */
final readonly class ErrorPage
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function forbidden(string $message = 'Você não tem permissão para acessar esta página.'): ResponseInterface
    {
        return $this->render(Status::FORBIDDEN, 'Acesso negado', $message);
    }

    public function notFound(string $message = 'O registro solicitado não foi encontrado.'): ResponseInterface
    {
        return $this->render(Status::NOT_FOUND, 'Não encontrado', $message);
    }

    private function render(int $status, string $title, string $message): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/error', ['status' => $status, 'title' => $title, 'message' => $message])
            ->withStatus($status);
    }
}
