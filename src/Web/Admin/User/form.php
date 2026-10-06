<?php

declare(strict_types=1);

use App\User\Role;
use App\Web\Shared\FormView;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\User\UserForm $form
 * @var App\User\User|null $user
 * @var bool $isSelf
 */

$this->setTitle($user === null ? 'Novo usuário' : 'Editar usuário');
$f = new FormView($form);
$action = $user === null
    ? $urlGenerator->generate('admin/user/create')
    : $urlGenerator->generate('admin/user/update', ['id' => $user->id]);
?>

<div class="page-header">
    <div>
        <a class="back" href="<?= $urlGenerator->generate('admin/user/index') ?>">← Usuários</a>
        <h1><?= $user === null ? 'Novo usuário' : Html::encode($user->name) ?></h1>
    </div>
</div>

<form class="panel form-narrow" method="post" action="<?= $action ?>" novalidate>
    <?= $csrf->hiddenInput() ?>

    <div class="form-row">
        <div class="<?= $f->fieldClass('name') ?>">
            <label for="name">Nome</label>
            <input id="name" type="text" name="<?= $f->name('name') ?>" value="<?= $f->value('name') ?>" required autofocus>
            <?= $f->errorTag('name') ?>
        </div>
        <div class="<?= $f->fieldClass('email') ?>">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="<?= $f->name('email') ?>" value="<?= $f->value('email') ?>" required autocomplete="off">
            <?= $f->errorTag('email') ?>
        </div>
    </div>

    <fieldset class="<?= $f->fieldClass('role') ?>">
        <legend>Papel</legend>
        <div class="role-options">
            <?php foreach ([Role::Editor, Role::Reviewer, Role::Admin] as $role): ?>
                <label class="role-option">
                    <input type="radio" name="<?= $f->name('role') ?>" value="<?= $role->value ?>"<?= $form->role === $role->value ? ' checked' : '' ?>>
                    <span>
                        <strong><?= $role->label() ?></strong>
                        <small><?= Html::encode($role->description()) ?></small>
                    </span>
                </label>
            <?php endforeach ?>
        </div>
        <?= $f->errorTag('role') ?>
    </fieldset>

    <div class="<?= $f->fieldClass('password') ?>">
        <label for="password"><?= $user === null ? 'Senha inicial' : 'Nova senha' ?></label>
        <input id="password" type="password" name="<?= $f->name('password') ?>" autocomplete="new-password" minlength="8"
               placeholder="<?= $user === null ? 'mínimo 8 caracteres' : 'deixe em branco para manter a atual' ?>">
        <?= $f->errorTag('password') ?>
    </div>

    <div class="<?= $f->fieldClass('bio') ?>">
        <label for="bio">Bio <span class="muted">(opcional)</span></label>
        <textarea id="bio" name="<?= $f->name('bio') ?>" rows="3"><?= $f->value('bio') ?></textarea>
        <?= $f->errorTag('bio') ?>
    </div>

    <div class="<?= $f->fieldClass('isActive') ?>">
        <input type="hidden" name="<?= $f->name('isActive') ?>" value="0">
        <label class="switch">
            <input type="checkbox" name="<?= $f->name('isActive') ?>" value="1"<?= $form->isActive ? ' checked' : '' ?><?= $isSelf ? ' disabled' : '' ?>>
            <span>Conta ativa (pode entrar no painel)</span>
        </label>
        <?php if ($isSelf): ?><input type="hidden" name="<?= $f->name('isActive') ?>" value="1"><?php endif ?>
        <?= $f->errorTag('isActive') ?>
    </div>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= $urlGenerator->generate('admin/user/index') ?>">Cancelar</a>
        <button class="btn btn--primary" type="submit">Salvar</button>
    </div>
</form>
