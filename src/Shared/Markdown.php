<?php

declare(strict_types=1);

namespace App\Shared;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Converte o conteúdo Markdown dos posts para HTML.
 * HTML bruto digitado no editor é escapado e links inseguros (javascript:) são removidos.
 */
final class Markdown
{
    private ?GithubFlavoredMarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        $this->converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);

        return $this->converter->convert($markdown)->getContent();
    }

    public static function readingMinutes(string $markdown): int
    {
        $words = str_word_count(strip_tags($markdown));
        return max(1, (int) ceil($words / 200));
    }
}
