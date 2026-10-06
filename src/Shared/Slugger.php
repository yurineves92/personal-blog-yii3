<?php

declare(strict_types=1);

namespace App\Shared;

use Transliterator;

final class Slugger
{
    public static function slugify(string $text, int $maxLength = 200): string
    {
        $text = trim($text);

        if (class_exists(Transliterator::class)) {
            $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
            if ($transliterator !== null) {
                $text = (string) $transliterator->transliterate($text);
            }
        } else {
            $text = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));
        }

        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        $text = rtrim(substr($text, 0, $maxLength), '-');

        return $text === '' ? 'item' : $text;
    }

    /**
     * Gera um slug único, acrescentando sufixos numéricos enquanto `$exists` retornar true.
     *
     * @param callable(string): bool $exists
     */
    public static function unique(string $text, callable $exists, int $maxLength = 200): string
    {
        $base = self::slugify($text, $maxLength - 4);
        $slug = $base;
        $i = 2;
        while ($exists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
