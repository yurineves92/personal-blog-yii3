<?php

declare(strict_types=1);

namespace App\User;

enum Role: string
{
    case Admin = 'admin';
    case Reviewer = 'reviewer';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Reviewer => 'Revisor',
            self::Editor => 'Editor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acesso total: usuários, categorias, configurações e publicação direta.',
            self::Reviewer => 'Revisa, edita, aprova ou rejeita posts enviados para revisão.',
            self::Editor => 'Escreve posts e os envia para revisão.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
