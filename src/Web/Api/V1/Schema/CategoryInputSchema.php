<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoryInput',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Segurança'),
        new OA\Property(property: 'slug', type: 'string', example: 'seguranca', nullable: true),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true),
    ],
    type: 'object',
)]
final class CategoryInputSchema
{
}
