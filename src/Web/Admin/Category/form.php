<?php

declare(strict_types=1);

use App\Web\Shared\FormView;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\Category\CategoryForm $form
 * @var App\Blog\Category|null $category
 */

$this->setTitle($category === null ? 'Nova categoria' : 'Editar categoria');
$f = new FormView($form);
$action = $category === null
    ? $urlGenerator->generate('admin/category/create')
    : $urlGenerator->generate('admin/category/update', ['id' => $category->id]);
?>

<div class="page-header">
    <div>
        <a class="back" href="<?= $urlGenerator->generate('admin/category/index') ?>">← Categorias</a>
        <h1><?= $category === null ? 'Nova categoria' : Html::encode($category->name) ?></h1>
    </div>
</div>

<form class="panel form-narrow" method="post" action="<?= $action ?>" novalidate>
    <?= $csrf->hiddenInput() ?>

    <div class="<?= $f->fieldClass('name') ?>">
        <label for="name">Nome</label>
        <input id="name" type="text" name="<?= $f->name('name') ?>" value="<?= $f->value('name') ?>" maxlength="100" required autofocus data-slug-source>
        <?= $f->errorTag('name') ?>
    </div>

    <div class="<?= $f->fieldClass('slug') ?>">
        <label for="slug">Slug</label>
        <input id="slug" type="text" name="<?= $f->name('slug') ?>" value="<?= $f->value('slug') ?>" placeholder="gerado a partir do nome" data-slug-target>
        <?= $f->errorTag('slug') ?>
        <div class="hint">/blog/categoria/<span data-slug-preview><?= $f->value('slug') ?: '…' ?></span></div>
    </div>

    <div class="<?= $f->fieldClass('description') ?>">
        <label for="description">Descrição <span class="muted">(opcional)</span></label>
        <textarea id="description" name="<?= $f->name('description') ?>" rows="3" maxlength="255" data-counter><?= $f->value('description') ?></textarea>
        <?= $f->errorTag('description') ?>
    </div>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= $urlGenerator->generate('admin/category/index') ?>">Cancelar</a>
        <button class="btn btn--primary" type="submit">Salvar</button>
    </div>
</form>
