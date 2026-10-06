<?php

declare(strict_types=1);

namespace App\Web\Admin\Auth;

use Yiisoft\FormModel\Attribute\Safe;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;

final class LoginForm extends FormModel
{
    #[Required(message: 'Informe o e-mail.')]
    #[Email(message: 'E-mail inválido.')]
    public string $email = '';

    #[Required(message: 'Informe a senha.')]
    public string $password = '';

    #[Safe]
    public string $return = '';
}
