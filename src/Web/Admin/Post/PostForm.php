<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Blog\Post;
use Yiisoft\FormModel\Attribute\Safe;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;

final class PostForm extends FormModel
{
    #[Required(message: 'Informe o título.')]
    #[Length(min: 3, max: 200, lessThanMinMessage: 'O título deve ter ao menos {min} caracteres.', greaterThanMaxMessage: 'O título deve ter no máximo {max} caracteres.')]
    public string $title = '';

    #[Length(max: 200, greaterThanMaxMessage: 'O slug deve ter no máximo {max} caracteres.', skipOnEmpty: true)]
    #[Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Use apenas letras minúsculas, números e hífens.', skipOnEmpty: true)]
    public ?string $slug = null;

    #[Length(max: 500, greaterThanMaxMessage: 'O resumo deve ter no máximo {max} caracteres.', skipOnEmpty: true)]
    public ?string $excerpt = null;

    #[Required(message: 'Escreva o conteúdo do post.')]
    #[Length(min: 20, lessThanMinMessage: 'O conteúdo deve ter ao menos {min} caracteres.')]
    public string $content = '';

    #[Url(message: 'Informe uma URL válida (http/https).', skipOnEmpty: true)]
    #[Length(max: 500, greaterThanMaxMessage: 'A URL deve ter no máximo {max} caracteres.', skipOnEmpty: true)]
    public ?string $coverUrl = null;

    #[Integer(min: 1, notNumberMessage: 'Categoria inválida.', lessThanMinMessage: 'Categoria inválida.', skipOnEmpty: true)]
    public ?int $categoryId = null;

    /** Botão clicado: save | submit | publish */
    #[Safe]
    public ?string $intent = null;

    public static function fromPost(Post $post): self
    {
        $form = new self();
        $form->title = $post->title;
        $form->slug = $post->slug;
        $form->excerpt = $post->excerpt;
        $form->content = $post->content;
        $form->coverUrl = $post->coverUrl;
        $form->categoryId = $post->categoryId;
        return $form;
    }
}
