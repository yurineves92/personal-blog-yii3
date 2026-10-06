<?php

declare(strict_types=1);

namespace App\Web\Api\V1\Schema;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Post',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Por que escolhi Yii3 para este blog'),
        new OA\Property(property: 'slug', type: 'string', example: 'por-que-escolhi-yii3-para-este-blog'),
        new OA\Property(property: 'excerpt', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'pending', 'published', 'rejected']),
        new OA\Property(property: 'status_label', type: 'string', example: 'Publicado'),
        new OA\Property(property: 'cover_url', type: 'string', format: 'uri', nullable: true),
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
        new OA\Property(property: 'author', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'name', type: 'string')], type: 'object'),
        new OA\Property(property: 'reviewer', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'name', type: 'string')], type: 'object', nullable: true),
        new OA\Property(property: 'review_note', type: 'string', nullable: true),
        new OA\Property(property: 'views', type: 'integer'),
        new OA\Property(property: 'url', description: 'Caminho público (somente posts publicados)', type: 'string', nullable: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'permissions',
            description: 'Ações que o usuário autenticado pode executar neste post',
            properties: [
                new OA\Property(property: 'update', type: 'boolean'),
                new OA\Property(property: 'delete', type: 'boolean'),
                new OA\Property(property: 'transitions', type: 'array', items: new OA\Items(type: 'string', enum: ['submit', 'approve', 'reject', 'unpublish'])),
            ],
            type: 'object',
        ),
        new OA\Property(property: 'content', description: 'Markdown (omitido nas listagens)', type: 'string'),
    ],
    type: 'object',
)]
final class PostSchema
{
}
