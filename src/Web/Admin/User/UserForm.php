<?php

declare(strict_types=1);

namespace App\Web\Admin\User;

use App\User\Role;
use App\User\User;
use Yiisoft\FormModel\Attribute\Safe;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class UserForm extends FormModel
{
    #[Required(message: 'Informe o nome.')]
    #[Length(min: 2, max: 120, lessThanMinMessage: 'Mínimo de {min} caracteres.', greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $name = '';

    #[Required(message: 'Informe o e-mail.')]
    #[Email(message: 'E-mail inválido.')]
    #[Length(max: 190, greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $email = '';

    #[Required(message: 'Escolha um papel.')]
    #[In(values: ['admin', 'reviewer', 'editor'], message: 'Papel inválido.')]
    public string $role = 'editor';

    /** Obrigatória apenas na criação; em branco na edição mantém a senha atual. */
    #[Length(min: 8, max: 72, lessThanMinMessage: 'A senha deve ter ao menos {min} caracteres.', greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $password = null;

    #[Length(max: 1000, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $bio = null;

    #[Safe]
    public bool $isActive = true;

    public static function fromUser(User $user): self
    {
        $form = new self();
        $form->name = $user->name;
        $form->email = $user->email;
        $form->role = $user->role->value;
        $form->bio = $user->bio;
        $form->isActive = $user->isActive;
        return $form;
    }

    public function roleEnum(): Role
    {
        return Role::from($this->role);
    }
}
