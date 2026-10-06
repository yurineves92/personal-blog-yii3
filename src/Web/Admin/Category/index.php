<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Blog\Category[] $categories
 */

$this->setTitle('Categorias');
?>

<div class="page-header">
    <div>
        <h1>Categorias</h1>
        <p class="muted">Organize os posts por tema. Excluir uma categoria deixa seus posts sem categoria.</p>
    </div>
    <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/category/create') ?>">+ Nova categoria</a>
</div>

<div class="panel panel--flush">
    <?php if ($categories === []): ?>
        <div class="empty-state empty-state--sm"><p>Nenhuma categoria cadastrada.</p></div>
    <?php else: ?>
        <table class="table table--hover">
            <thead>
            <tr><th>Nome</th><th class="hide-sm">Slug</th><th class="hide-sm">Descrição</th><th class="num">Posts</th><th class="actions"></th></tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><a class="table__title" href="<?= $urlGenerator->generate('admin/category/update', ['id' => $category->id]) ?>"><?= Html::encode($category->name) ?></a></td>
                    <td class="muted hide-sm"><?= Html::encode($category->slug) ?></td>
                    <td class="muted hide-sm"><?= Html::encode($category->description ?? '—') ?></td>
                    <td class="num"><?= $category->postsCount ?></td>
                    <td class="actions">
                        <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('admin/category/update', ['id' => $category->id]) ?>">Editar</a>
                        <form method="post" action="<?= $urlGenerator->generate('admin/category/delete', ['id' => $category->id]) ?>"
                              data-confirm="Excluir a categoria “<?= Html::encode($category->name) ?>”? <?= $category->postsCount ?> post(s) ficarão sem categoria.">
                            <?= $csrf->hiddenInput() ?>
                            <button class="btn btn--danger-ghost btn--sm" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>
