<?php

declare(strict_types=1);

namespace App\Shared;

use DateTimeInterface;
use IntlDateFormatter;

final class Format
{
    public static function date(?DateTimeInterface $date, bool $withTime = false): string
    {
        if ($date === null) {
            return '—';
        }

        if (class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter(
                'pt_BR',
                IntlDateFormatter::MEDIUM,
                $withTime ? IntlDateFormatter::SHORT : IntlDateFormatter::NONE,
                $date->getTimezone(),
            );
            $formatted = $formatter->format($date);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    }

    public static function relative(?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        $diff = time() - $date->getTimestamp();
        return match (true) {
            $diff < 60 => 'agora mesmo',
            $diff < 3600 => 'há ' . intdiv($diff, 60) . ' min',
            $diff < 86400 => 'há ' . intdiv($diff, 3600) . ' h',
            $diff < 86400 * 7 => 'há ' . intdiv($diff, 86400) . ' dia(s)',
            default => self::date($date),
        };
    }

    public static function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Matiz (0–359) estável para gerar capas/avatares em gradiente quando não há imagem.
     */
    public static function hue(int|string $seed): int
    {
        return abs(crc32((string) $seed)) % 360;
    }
}
