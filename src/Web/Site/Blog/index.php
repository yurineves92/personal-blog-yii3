<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Shared\Paginator $paginator
 * @var App\Blog\Category[] $categories
 * @var App\Blog\Category|null $category
 * @var string $search
 */

$this->setTitle($category !== null ? $category->name : 'Blog');
if ($category?->description !== null) {
    $this->setParameter('metaDescription', $category->description);
}

$route = $category !== null ? 'blog/category' : 'blog/index';
$routeArgs = $category !== null ? ['slug' => $category->slug] : [];
$pageUrl = static function (int $page) use ($urlGenerator, $route, $routeArgs, $search): string {
    $query = array_filter(['q' => $search, 'page' => $page > 1 ? $page : null]);
    return $urlGenerator->generate($route, $routeArgs, $query);
};
?>

<section class="page-head">
    <div class="container">
        <span class="eyebrow"><?= $category !== null ? 'Categoria' : 'Blog' ?></span>
        <h1 class="page-head__title"><?= Html::encode($category?->name ?? 'Todos os artigos') ?></h1>
        <?php if ($category?->description !== null): ?>
            <p class="page-head__subtitle"><?= Html::encode($category->description) ?></p>
        <?php endif ?>

        <form class="search" method="get" action="<?= $urlGenerator->generate($route, $routeArgs) ?>" role="search">
            <input type="search" name="q" value="<?= Html::encode($search) ?>" placeholder="Buscar artigos…" aria-label="Buscar posts">
            <button class="btn btn--primary" type="submit">Buscar</button>
        </form>

        <div class="chips">
            <a class="chip<?= $category === null ? ' is-active' : '' ?>" href="<?= $urlGenerator->generate('blog/index') ?>">Todos</a>
            <?php foreach ($categories as $item): ?>
                <a class="chip<?= $category?->id === $item->id ? ' is-active' : '' ?>"
                   href="<?= $urlGenerator->generate('blog/category', ['slug' => $item->slug]) ?>">
                    <?= Html::encode($item->name) ?> <small><?= $item->postsCount ?></small>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($search !== ''): ?>
            <p class="result-info">
                <?= $paginator->total ?> resultado(s) para <strong>“<?= Html::encode($search) ?>”</strong>
                · <a href="<?= $urlGenerator->generate($route, $routeArgs) ?>">limpar busca</a>
            </p>
        <?php endif ?>

        <?php if ($paginator->items === []): ?>
            <div class="empty">Nenhum artigo encontrado.</div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($paginator->items as $post): ?>
                    <?= $this->render('../Shared/post-card.php', ['post' => $post]) ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($paginator->pageCount > 1): ?>
            <nav class="pagination" aria-label="Paginação">
                <?php if ($paginator->hasPrevious()): ?>
                    <a href="<?= $pageUrl($paginator->page - 1) ?>">← Anterior</a>
                <?php endif ?>
                <?php foreach ($paginator->pages() as $page): ?>
                    <?php if ($page === null): ?>
                        <span class="pagination__gap">…</span>
                    <?php elseif ($page === $paginator->page): ?>
                        <span class="is-active" aria-current="page"><?= $page ?></span>
                    <?php else: ?>
                        <a href="<?= $pageUrl($page) ?>"><?= $page ?></a>
                    <?php endif ?>
                <?php endforeach ?>
                <?php if ($paginator->hasNext()): ?>
                    <a href="<?= $pageUrl($paginator->page + 1) ?>">Próxima →</a>
                <?php endif ?>
            </nav>
        <?php endif ?>
    </div>
</section>
