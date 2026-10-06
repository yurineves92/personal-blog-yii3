<?php

declare(strict_types=1);

use App\Auth\Rbac\Permission;
use App\Blog\PostStatus;
use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Shared\Paginator $paginator
 * @var array<string, int> $counts
 * @var string|null $status
 * @var string $search
 * @var bool $viewAll
 * @var App\Blog\PostWorkflow $workflow
 */

$this->setTitle('Posts');
$url = static fn(array $params) => $urlGenerator->generate(
    'admin/post/index',
    [],
    array_filter($params + ['status' => $status, 'q' => $search], static fn($v) => $v !== null && $v !== ''),
);
?>

<div class="page-header">
    <div>
        <h1><?= $viewAll ? 'Posts' : 'Meus posts' ?></h1>
        <p class="muted"><?= $viewAll ? 'Todos os posts do blog.' : 'Como editor, você vê e edita apenas os seus posts.' ?></p>
    </div>
    <?php if ($currentUser->can(Permission::POST_CREATE)): ?>
        <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/post/create') ?>">+ Novo post</a>
    <?php endif ?>
</div>

<div class="toolbar">
    <nav class="tabs">
        <a href="<?= $url(['status' => null, 'page' => null]) ?>" class="<?= $status === null ? 'is-active' : '' ?>">
            Todos <small><?= array_sum($counts) ?></small>
        </a>
        <?php foreach (PostStatus::cases() as $case): ?>
            <a href="<?= $url(['status' => $case->value, 'page' => null]) ?>" class="<?= $status === $case->value ? 'is-active' : '' ?>">
                <?= $case->label() ?> <small><?= $counts[$case->value] ?></small>
            </a>
        <?php endforeach ?>
    </nav>

    <form class="search-inline" method="get" action="<?= $urlGenerator->generate('admin/post/index') ?>">
        <?php if ($status !== null): ?><input type="hidden" name="status" value="<?= Html::encode($status) ?>"><?php endif ?>
        <input type="search" name="q" value="<?= Html::encode($search) ?>" placeholder="Buscar por título ou conteúdo…">
    </form>
</div>

<div class="panel panel--flush">
    <?php if ($paginator->items === []): ?>
        <div class="empty-state empty-state--sm">
            <p>Nenhum post encontrado.</p>
        </div>
    <?php else: ?>
        <table class="table table--hover">
            <thead>
            <tr>
                <th>Título</th>
                <?php if ($viewAll): ?><th class="hide-sm">Autor</th><?php endif ?>
                <th class="hide-sm">Categoria</th>
                <th>Status</th>
                <th class="num hide-sm">Leituras</th>
                <th class="hide-sm">Atualizado</th>
                <th class="actions"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($paginator->items as $post): ?>
                <tr>
                    <td>
                        <a class="table__title" href="<?= $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>"><?= Html::encode($post->title) ?></a>
                        <div class="table__sub hide-sm">/blog/<?= Html::encode($post->slug) ?></div>
                    </td>
                    <?php if ($viewAll): ?><td class="hide-sm"><?= Html::encode($post->authorName) ?></td><?php endif ?>
                    <td class="hide-sm"><?= Html::encode($post->categoryName ?? '—') ?></td>
                    <td><?= $this->render('../Shared/status-badge.php', ['status' => $post->status]) ?></td>
                    <td class="num hide-sm"><?= Format::number($post->views) ?></td>
                    <td class="muted nowrap hide-sm"><?= Format::relative($post->updatedAt) ?></td>
                    <td class="actions">
                        <?php if ($workflow->canEdit($post)): ?>
                            <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('admin/post/update', ['id' => $post->id]) ?>">Editar</a>
                        <?php endif ?>
                        <?php if ($post->isPublished()): ?>
                            <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('blog/post', ['slug' => $post->slug]) ?>" target="_blank" rel="noopener">Ver ↗</a>
                        <?php endif ?>
                        <?php if ($workflow->canDelete($post)): ?>
                            <form method="post" action="<?= $urlGenerator->generate('admin/post/delete', ['id' => $post->id]) ?>" data-confirm="Excluir “<?= Html::encode($post->title) ?>”? Esta ação não pode ser desfeita.">
                                <?= $csrf->hiddenInput() ?>
                                <button class="btn btn--danger-ghost btn--sm" type="submit">Excluir</button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</div>

<?php if ($paginator->pageCount > 1): ?>
    <nav class="pagination">
        <?php foreach ($paginator->pages() as $page): ?>
            <?php if ($page === null): ?>
                <span>…</span>
            <?php elseif ($page === $paginator->page): ?>
                <span class="is-active"><?= $page ?></span>
            <?php else: ?>
                <a href="<?= $url(['page' => $page]) ?>"><?= $page ?></a>
            <?php endif ?>
        <?php endforeach ?>
    </nav>
<?php endif ?>
