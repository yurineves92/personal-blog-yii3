<?php

declare(strict_types=1);

namespace App\Web\Admin\Profile;

use App\User\User;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

final class ProfileForm extends FormModel
{
    #[Required(message: 'Informe o nome.')]
    #[Length(min: 2, max: 120, lessThanMinMessage: 'Mínimo de {min} caracteres.', greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $name = '';

    #[Required(message: 'Informe o e-mail.')]
    #[Email(message: 'E-mail inválido.')]
    public string $email = '';

    #[Length(max: 1000, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $bio = null;

    #[Length(max: 72, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $currentPassword = null;

    #[Length(min: 8, max: 72, lessThanMinMessage: 'A senha deve ter ao menos {min} caracteres.', greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $newPassword = null;

    public static function fromUser(User $user): self
    {
        $form = new self();
        $form->name = $user->name;
        $form->email = $user->email;
        $form->bio = $user->bio;
        return $form;
    }
}
