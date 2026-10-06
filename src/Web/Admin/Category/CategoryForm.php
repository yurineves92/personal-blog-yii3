<?php

declare(strict_types=1);

namespace App\Web\Admin\Category;

use App\Blog\Category;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Regex;
use Yiisoft\Validator\Rule\Required;

final class CategoryForm extends FormModel
{
    #[Required(message: 'Informe o nome.')]
    #[Length(min: 2, max: 100, lessThanMinMessage: 'Mínimo de {min} caracteres.', greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $name = '';

    #[Length(max: 120, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    #[Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Use apenas letras minúsculas, números e hífens.', skipOnEmpty: true)]
    public ?string $slug = null;

    #[Length(max: 255, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $description = null;

    public static function fromCategory(Category $category): self
    {
        $form = new self();
        $form->name = $category->name;
        $form->slug = $category->slug;
        $form->description = $category->description;
        return $form;
    }
}
