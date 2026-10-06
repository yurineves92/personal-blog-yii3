<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostInput',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 200, minLength: 3, example: 'Meu post via API'),
        new OA\Property(property: 'slug', type: 'string', example: 'meu-post-via-api', nullable: true),
        new OA\Property(property: 'excerpt', type: 'string', maxLength: 500, nullable: true),
        new OA\Property(property: 'content', type: 'string', minLength: 20, example: "## Olá\n\nConteúdo em **Markdown** criado pela API."),
        new OA\Property(property: 'cover_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
    ],
    type: 'object',
)]
final class PostInputSchema
{
}
