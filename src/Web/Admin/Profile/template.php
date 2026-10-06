<?php

declare(strict_types=1);

use App\Shared\Format;
use App\Web\Shared\FormView;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\Profile\ProfileForm $form
 * @var App\User\User $user
 */

$this->setTitle('Meu perfil');
$f = new FormView($form);
?>

<div class="page-header">
    <div class="user-cell">
        <span class="avatar avatar--lg" style="--hue: <?= Format::hue($user->id) ?>"><?= Html::encode($user->initials()) ?></span>
        <div>
            <h1><?= Html::encode($user->name) ?></h1>
            <p class="muted"><?= $user->role->label() ?> · membro desde <?= Format::date($user->createdAt) ?></p>
        </div>
    </div>
</div>

<form class="panel form-narrow" method="post" action="<?= $urlGenerator->generate('admin/profile') ?>" novalidate>
    <?= $csrf->hiddenInput() ?>

    <h3>Dados pessoais</h3>
    <div class="form-row">
        <div class="<?= $f->fieldClass('name') ?>">
            <label for="name">Nome</label>
            <input id="name" type="text" name="<?= $f->name('name') ?>" value="<?= $f->value('name') ?>" required>
            <?= $f->errorTag('name') ?>
        </div>
        <div class="<?= $f->fieldClass('email') ?>">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="<?= $f->name('email') ?>" value="<?= $f->value('email') ?>" required>
            <?= $f->errorTag('email') ?>
        </div>
    </div>
    <div class="<?= $f->fieldClass('bio') ?>">
        <label for="bio">Bio</label>
        <textarea id="bio" name="<?= $f->name('bio') ?>" rows="3"><?= $f->value('bio') ?></textarea>
        <?= $f->errorTag('bio') ?>
    </div>

    <h3>Alterar senha</h3>
    <div class="form-row">
        <div class="<?= $f->fieldClass('currentPassword') ?>">
            <label for="currentPassword">Senha atual</label>
            <input id="currentPassword" type="password" name="<?= $f->name('currentPassword') ?>" autocomplete="current-password">
            <?= $f->errorTag('currentPassword') ?>
        </div>
        <div class="<?= $f->fieldClass('newPassword') ?>">
            <label for="newPassword">Nova senha</label>
            <input id="newPassword" type="password" name="<?= $f->name('newPassword') ?>" autocomplete="new-password" minlength="8">
            <?= $f->errorTag('newPassword') ?>
        </div>
    </div>
    <p class="hint">Deixe em branco para manter a senha atual.</p>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Salvar perfil</button>
    </div>
</form>
