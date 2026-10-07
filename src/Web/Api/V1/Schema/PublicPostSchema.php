<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PublicPost',
    description: 'Post publicado. `content_html` e `related` vêm apenas no detalhe.',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'excerpt', type: 'string'),
        new OA\Property(property: 'cover_url', type: 'string', nullable: true),
        new OA\Property(
            property: 'category',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'slug', type: 'string'),
            ],
            type: 'object',
            nullable: true,
        ),
        new OA\Property(property: 'author', properties: [new OA\Property(property: 'name', type: 'string')], type: 'object'),
        new OA\Property(property: 'reading_minutes', type: 'integer'),
        new OA\Property(property: 'views', type: 'integer'),
        new OA\Property(property: 'url', type: 'string', example: '/blog/meu-post'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'content_html', description: 'Markdown já convertido (HTML do usuário escapado)', type: 'string'),
        new OA\Property(property: 'related', type: 'array', items: new OA\Items(type: 'object')),
    ],
    type: 'object',
)]
final class PublicPostSchema
{
}
