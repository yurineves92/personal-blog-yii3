<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Problem',
    description: 'Erro no formato RFC 9457',
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'about:blank'),
        new OA\Property(property: 'title', type: 'string', example: 'Unprocessable Entity'),
        new OA\Property(property: 'status', type: 'integer', example: 422),
        new OA\Property(property: 'detail', type: 'string', example: 'Os dados enviados são inválidos.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['title' => ['O título deve ter ao menos 3 caracteres.']],
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
    type: 'object',
)]
final class ProblemSchema
{
}
