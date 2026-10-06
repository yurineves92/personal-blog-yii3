<?php

declare(strict_types=1);

use App\Web\Shared\FormView;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\Auth\LoginForm $form
 * @var App\Site\SettingRepository $settings
 */

$this->setTitle('Entrar');
$f = new FormView($form);
?>

<div class="auth">
    <div class="auth__card">
        <a class="auth__brand" href="<?= $urlGenerator->generate('home') ?>">
            <span class="brand-mark"><?= Html::encode(mb_strtolower(mb_substr($settings->get('site_name'), 0, 1))) ?></span>
            <?= Html::encode($settings->get('site_name')) ?>
        </a>
        <h1>Entrar no painel</h1>
        <p class="muted">Acesso para administradores, revisores e editores.</p>

        <form method="post" action="<?= $urlGenerator->generate('admin/login') ?>" novalidate>
            <?= $csrf->hiddenInput() ?>
            <input type="hidden" name="<?= $f->name('return') ?>" value="<?= $f->value('return') ?>">

            <div class="<?= $f->fieldClass('email') ?>">
                <label for="email">E-mail</label>
                <input id="email" type="email" name="<?= $f->name('email') ?>" value="<?= $f->value('email') ?>" autocomplete="username" autofocus required>
                <?= $f->errorTag('email') ?>
            </div>

            <div class="<?= $f->fieldClass('password') ?>">
                <label for="password">Senha</label>
                <input id="password" type="password" name="<?= $f->name('password') ?>" autocomplete="current-password" required>
                <?= $f->errorTag('password') ?>
            </div>

            <button class="btn btn--primary btn--block" type="submit">Entrar</button>
        </form>

        <a class="auth__back" href="<?= $urlGenerator->generate('home') ?>">← Voltar ao site</a>
    </div>
</div>
