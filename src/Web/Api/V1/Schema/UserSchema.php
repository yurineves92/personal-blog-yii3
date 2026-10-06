<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Yuri Neves'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@miniblog.test'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'reviewer', 'editor']),
        new OA\Property(property: 'role_label', type: 'string', example: 'Administrador'),
    ],
    type: 'object',
)]
final class UserSchema
{
}
