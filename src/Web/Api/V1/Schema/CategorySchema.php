<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Yii3'),
        new OA\Property(property: 'slug', type: 'string', example: 'yii3'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'posts_count', type: 'integer', example: 8),
    ],
    type: 'object',
)]
final class CategorySchema
{
}
