<?php

declare(strict_types=1);

use App\Web\Shared\FormView;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\Settings\SettingsForm $form
 */

$this->setTitle('Configurações do site');
$f = new FormView($form);

$input = static function (string $property, string $label, string $hint = '', string $type = 'text') use ($f): string {
    return '<div class="' . $f->fieldClass($property) . '">'
        . '<label for="' . $property . '">' . $label . '</label>'
        . '<input id="' . $property . '" type="' . $type . '" name="' . $f->name($property) . '" value="' . $f->value($property) . '">'
        . ($hint !== '' ? '<div class="hint">' . $hint . '</div>' : '')
        . $f->errorTag($property)
        . '</div>';
};
$textarea = static function (string $property, string $label, int $rows = 3, string $hint = '', string $class = '') use ($f): string {
    return '<div class="' . $f->fieldClass($property) . '">'
        . '<label for="' . $property . '">' . $label . '</label>'
        . '<textarea id="' . $property . '" name="' . $f->name($property) . '" rows="' . $rows . '"' . ($class ? ' class="' . $class . '"' : '') . '>' . $f->value($property) . '</textarea>'
        . ($hint !== '' ? '<div class="hint">' . $hint . '</div>' : '')
        . $f->errorTag($property)
        . '</div>';
};
?>

<div class="page-header">
    <div>
        <h1>Configurações do site</h1>
        <p class="muted">Textos da landing page, perfil do autor, projetos e rodapé.</p>
    </div>
    <a class="btn btn--ghost" href="<?= $urlGenerator->generate('home') ?>" target="_blank" rel="noopener">Ver landing page ↗</a>
</div>

<form method="post" action="<?= $urlGenerator->generate('admin/settings') ?>" novalidate>
    <?= $csrf->hiddenInput() ?>

    <div class="settings-grid">
        <section class="panel">
            <h3>Identidade</h3>
            <?= $input('siteName', 'Nome do site') ?>
            <?= $input('siteTagline', 'Slogan', 'Aparece acima do título da landing e no rodapé.') ?>
            <?= $input('footerText', 'Texto do rodapé') ?>
        </section>

        <section class="panel">
            <h3>Hero (topo da landing)</h3>
            <?= $textarea('heroTitle', 'Título', 2) ?>
            <?= $textarea('heroSubtitle', 'Subtítulo', 3) ?>
            <?= $input('heroCta', 'Texto do botão principal') ?>
        </section>

        <section class="panel">
            <h3>Autor</h3>
            <div class="form-row">
                <?= $input('authorName', 'Nome') ?>
                <?= $input('authorLocation', 'Localização') ?>
            </div>
            <?= $input('authorRole', 'Cargo / especialidade') ?>
            <?= $input('authorAvatar', 'Foto (URL)', 'Ex.: https://github.com/seu-usuario.png', 'url') ?>
            <div class="form-row">
                <?= $input('githubUrl', 'GitHub', '', 'url') ?>
                <?= $input('xUrl', 'X / Twitter', '', 'url') ?>
            </div>
            <?= $input('stack', 'Stack', 'Separada por vírgulas.') ?>
        </section>

        <section class="panel">
            <h3>Seção “Sobre”</h3>
            <?= $input('aboutTitle', 'Título') ?>
            <?= $textarea('aboutText', 'Texto', 8, 'Linhas em branco separam parágrafos.') ?>
        </section>

        <section class="panel settings-grid__wide">
            <h3>Projetos em destaque</h3>
            <?= $textarea('projects', 'Um projeto por linha', 7, 'Formato: <code>nome | url | descrição | etiqueta</code>', 'input--content input--short') ?>
        </section>
    </div>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit">Salvar configurações</button>
    </div>
</form>
