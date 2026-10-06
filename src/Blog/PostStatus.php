<?php

declare(strict_types=1);

namespace App\Blog;

/**
 * Fluxo editorial:
 *
 *   rascunho ──enviar──▶ em revisão ──aprovar──▶ publicado
 *      ▲                     │                       │
 *      └──── rejeitado ◀─────┘ rejeitar              │
 *      ▲                                             │
 *      └──────────────── despublicar ◀───────────────┘
 */
enum PostStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Pending => 'Em revisão',
            self::Published => 'Publicado',
            self::Rejected => 'Rejeitado',
        };
    }

    /**
     * Estados em que o autor ainda pode editar/excluir o próprio post.
     */
    public function isEditableByAuthor(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
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
