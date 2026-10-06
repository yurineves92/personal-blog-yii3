<?php

declare(strict_types=1);

namespace App\Web\Admin\Settings;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Url;

final class SettingsForm extends FormModel
{
    #[Required(message: 'Obrigatório.')]
    #[Length(max: 60, greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $siteName = '';

    #[Length(max: 120, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $siteTagline = null;

    #[Required(message: 'Obrigatório.')]
    #[Length(max: 160, greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $heroTitle = '';

    #[Length(max: 400, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $heroSubtitle = null;

    #[Required(message: 'Obrigatório.')]
    #[Length(max: 40, greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $heroCta = '';

    #[Length(max: 120, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $aboutTitle = null;

    #[Length(max: 2000, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $aboutText = null;

    #[Required(message: 'Obrigatório.')]
    #[Length(max: 80, greaterThanMaxMessage: 'Máximo de {max} caracteres.')]
    public string $authorName = '';

    #[Length(max: 120, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $authorRole = null;

    #[Length(max: 80, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $authorLocation = null;

    #[Url(message: 'URL inválida.', skipOnEmpty: true)]
    public ?string $authorAvatar = null;

    #[Url(message: 'URL inválida.', skipOnEmpty: true)]
    public ?string $githubUrl = null;

    #[Url(message: 'URL inválida.', skipOnEmpty: true)]
    public ?string $xUrl = null;

    #[Length(max: 300, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $stack = null;

    #[Length(max: 3000, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $projects = null;

    #[Length(max: 200, greaterThanMaxMessage: 'Máximo de {max} caracteres.', skipOnEmpty: true)]
    public ?string $footerText = null;

    /** Propriedade do form => chave na tabela `setting`. */
    public const MAP = [
        'siteName' => 'site_name',
        'siteTagline' => 'site_tagline',
        'heroTitle' => 'hero_title',
        'heroSubtitle' => 'hero_subtitle',
        'heroCta' => 'hero_cta',
        'aboutTitle' => 'about_title',
        'aboutText' => 'about_text',
        'authorName' => 'author_name',
        'authorRole' => 'author_role',
        'authorLocation' => 'author_location',
        'authorAvatar' => 'author_avatar',
        'githubUrl' => 'github_url',
        'xUrl' => 'x_url',
        'stack' => 'stack',
        'projects' => 'projects',
        'footerText' => 'footer_text',
    ];

    /**
     * @param array<string, string> $values
     */
    public static function fromSettings(array $values): self
    {
        $form = new self();
        foreach (self::MAP as $property => $key) {
            $form->$property = $values[$key] ?? '';
        }
        return $form;
    }

    /**
     * @return array<string, string>
     */
    public function toSettings(): array
    {
        $values = [];
        foreach (self::MAP as $property => $key) {
            $values[$key] = trim((string) $this->$property);
        }
        return $values;
    }
}
