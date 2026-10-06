<?php

declare(strict_types=1);

namespace App\Web\Api\V1;

use App\Blog\Category;
use App\Blog\Post;
use App\Blog\PostWorkflow;
use App\User\User;
use DateTimeInterface;

/**
 * Converte entidades em arrays para as respostas JSON da API (formato público da v1).
 */
final readonly class Resource
{
    public function __construct(
        private PostWorkflow $workflow,
    ) {}

    public function post(Post $post, bool $withContent = true): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'status' => $post->status->value,
            'status_label' => $post->status->label(),
            'cover_url' => $post->coverUrl,
            'category' => $post->categoryId !== null
                ? ['id' => $post->categoryId, 'name' => $post->categoryName, 'slug' => $post->categorySlug]
                : null,
            'author' => ['id' => $post->authorId, 'name' => $post->authorName],
            'reviewer' => $post->reviewerId !== null ? ['id' => $post->reviewerId, 'name' => $post->reviewerName] : null,
            'review_note' => $post->reviewNote,
            'views' => $post->views,
            'url' => $post->isPublished() ? '/blog/' . $post->slug : null,
            'published_at' => self::date($post->publishedAt),
            'submitted_at' => self::date($post->submittedAt),
            'created_at' => self::date($post->createdAt),
            'updated_at' => self::date($post->updatedAt),
            // O que o usuário autenticado pode fazer com este post — útil para montar a UI do cliente.
            'permissions' => [
                'update' => $this->workflow->canEdit($post),
                'delete' => $this->workflow->canDelete($post),
                'transitions' => array_values(array_filter(
                    [PostWorkflow::SUBMIT, PostWorkflow::APPROVE, PostWorkflow::REJECT, PostWorkflow::UNPUBLISH],
                    fn(string $t): bool => $this->workflow->can($t, $post),
                )),
            ],
        ];

        if ($withContent) {
            $data['content'] = $post->content;
        }

        return $data;
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'posts_count' => $category->postsCount,
        ];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
        ];
    }

    public static function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }

    /**
     * Converte os erros do FormModel (camelCase) para os nomes de campo da API (snake_case).
     *
     * @param array<string, string[]> $errors
     * @return array<string, string[]>
     */
    public static function errors(array $errors): array
    {
        $result = [];
        foreach ($errors as $property => $messages) {
            $key = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $property));
            $result[$key] = $messages;
        }
        return $result;
    }
}
