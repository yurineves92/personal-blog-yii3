<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use OpenApi\Attributes as OA;

/**
 * Metadados globais da especificação OpenAPI. Os endpoints são descritos por atributos
 * nos próprios controllers; o `swagger-php` lê tudo e gera o JSON servido em /admin/api/openapi.json.
 */
#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        description: "API REST do painel do blog.\n\nAutentique-se com `POST /auth/token` (ou gere um token na página **API** do painel) e envie `Authorization: Bearer <token>`. As permissões são as mesmas do painel (RBAC: admin, revisor, editor).\n\nErros seguem a RFC 9457 (`application/problem+json`).",
        title: 'Miniblog API',
    ),
    servers: [new OA\Server(url: '/admin/api/v1', description: 'Este servidor')],
    security: [['bearerAuth' => []]],
    tags: [
        new OA\Tag(name: 'Auth', description: 'Tokens e usuário autenticado'),
        new OA\Tag(name: 'Posts', description: 'CRUD de posts e fluxo editorial'),
        new OA\Tag(name: 'Categories', description: 'Categorias'),
    ],
)]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', description: 'Token gerado no painel ou em POST /auth/token', bearerFormat: 'mb_…', scheme: 'bearer')]
#[OA\Response(response: 'Unauthorized', description: 'Token ausente, inválido ou expirado', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
#[OA\Response(response: 'Forbidden', description: 'Sem permissão (RBAC)', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
#[OA\Response(response: 'NotFound', description: 'Recurso não encontrado', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
#[OA\Response(response: 'ValidationFailed', description: 'Dados inválidos', content: new OA\JsonContent(ref: '#/components/schemas/Problem'))]
#[OA\Parameter(parameter: 'id', name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1))]
/**
 * Os schemas (Post, User, Problem...) ficam em uma classe cada, no namespace Schema.
 */
final class OpenApiSpec
{
}
