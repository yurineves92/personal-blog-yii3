<?php

declare(strict_types=1);

namespace App\Web\Api;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

/**
 * Respostas JSON da API. Erros seguem a RFC 9457 (application/problem+json).
 */
final readonly class ApiResponder
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function json(mixed $data, int $status = Status::OK): ResponseInterface
    {
        return $this->write($data, $status, 'application/json');
    }

    public function created(mixed $data): ResponseInterface
    {
        return $this->json($data, Status::CREATED);
    }

    public function noContent(): ResponseInterface
    {
        return $this->responseFactory->createResponse(Status::NO_CONTENT);
    }

    /**
     * @param array<string, string[]> $errors Erros de validação por campo.
     */
    public function problem(int $status, string $detail, array $errors = []): ResponseInterface
    {
        $body = [
            'type' => 'about:blank',
            'title' => Status::TEXTS[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
        ];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        $response = $this->write($body, $status, 'application/problem+json');

        return $status === Status::UNAUTHORIZED
            ? $response->withHeader(Header::WWW_AUTHENTICATE, 'Bearer realm="api"')
            : $response;
    }

    public function unauthorized(string $detail = 'Token de acesso ausente, inválido ou expirado.'): ResponseInterface
    {
        return $this->problem(Status::UNAUTHORIZED, $detail);
    }

    public function forbidden(string $detail = 'Você não tem permissão para esta ação.'): ResponseInterface
    {
        return $this->problem(Status::FORBIDDEN, $detail);
    }

    public function notFound(string $detail = 'Recurso não encontrado.'): ResponseInterface
    {
        return $this->problem(Status::NOT_FOUND, $detail);
    }

    /**
     * @param array<string, string[]> $errors
     */
    public function validationFailed(array $errors): ResponseInterface
    {
        return $this->problem(Status::UNPROCESSABLE_ENTITY, 'Os dados enviados são inválidos.', $errors);
    }

    /**
     * Corpo JSON da requisição como array. Retorna null se o JSON for inválido.
     */
    public static function body(ServerRequestInterface $request): ?array
    {
        $raw = trim((string) $request->getBody());
        if ($raw === '') {
            return [];
        }
        try {
            $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        return is_array($data) ? $data : null;
    }

    private function write(mixed $data, int $status, string $contentType): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader(Header::CONTENT_TYPE, $contentType . '; charset=utf-8');
        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
        );
        return $response;
    }
}
