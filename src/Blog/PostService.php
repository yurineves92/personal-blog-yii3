<?php

declare(strict_types=1);

namespace App\Blog;

use App\Shared\Slugger;
use App\Web\Admin\Post\PostForm;

/**
 * Regras de gravação de posts compartilhadas entre o painel (HTML) e a API (JSON).
 */
final readonly class PostService
{
    public function __construct(
        private PostRepository $posts,
        private CategoryRepository $categories,
    ) {}

    /**
     * Validações que dependem do banco; adicionam erros ao form.
     */
    public function validate(PostForm $form, ?Post $post = null): void
    {
        if ($form->categoryId !== null && $this->categories->findById($form->categoryId) === null) {
            $form->addError('Categoria inválida.', ['categoryId']);
        }
        if ($form->slug !== null && $this->posts->slugExists($form->slug, $post?->id)) {
            $form->addError('Já existe um post com este slug.', ['slug']);
        }
    }

    /**
     * Cria (quando `$post` é null) ou atualiza um post e retorna o ID.
     */
    public function save(PostForm $form, ?Post $post, int $authorId): int
    {
        $data = [
            'title' => trim($form->title),
            'slug' => $form->slug ?? Slugger::unique(
                $form->title,
                fn(string $slug): bool => $this->posts->slugExists($slug, $post?->id),
            ),
            'excerpt' => $form->excerpt !== null ? trim($form->excerpt) : null,
            'content' => $form->content,
            'cover_url' => $form->coverUrl,
            'category_id' => $form->categoryId,
        ];

        if ($post === null) {
            return $this->posts->insert($data + [
                'author_id' => $authorId,
                'status' => PostStatus::Draft->value,
            ]);
        }

        $this->posts->update($post->id, $data);
        return $post->id;
    }
}
