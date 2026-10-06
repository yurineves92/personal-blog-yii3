<?php

declare(strict_types=1);

use App\Auth\Rbac\Permission;
use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var App\User\User $user
 * @var bool $viewAll
 * @var array<string, int> $counts
 * @var array<string, int> $myCounts
 * @var App\Blog\Post[] $recent
 * @var App\Blog\Post[] $myRejected
 * @var App\Blog\Post[] $pending
 * @var int|null $totalViews
 * @var int|null $userCount
 * @var int|null $categoryCount
 */

$this->setTitle('Painel');
$badge = '../Shared/status-badge.php';
$firstName = explode(' ', $user->name)[0];
?>

<div class="page-header">
    <div>
        <h1>Olá, <?= Html::encode($firstName) ?>!</h1>
        <p class="muted">
            Você está conectado como <strong><?= Html::encode($user->role->label()) ?></strong>.
            <?= Html::encode($user->role->description()) ?>
        </p>
    </div>
    <?php if ($currentUser->can(Permission::POST_CREATE)): ?>
        <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/post/create') ?>">+ Novo post</a>
    <?php endif ?>
</div>

<div class="stats">
    <a class="stat" href="<?= $urlGenerator->generate('admin/post/index', [], ['status' => 'published']) ?>">
        <span class="stat__label">Publicados<?= $viewAll ? '' : ' (meus)' ?></span>
        <strong class="stat__value"><?= Format::number($counts['published']) ?></strong>
    </a>
    <a class="stat" href="<?= $urlGenerator->generate('admin/post/index', [], ['status' => 'pending']) ?>">
        <span class="stat__label">Em revisão</span>
        <strong class="stat__value stat__value--warn"><?= Format::number($counts['pending']) ?></strong>
    </a>
    <a class="stat" href="<?= $urlGenerator->generate('admin/post/index', [], ['status' => 'draft']) ?>">
        <span class="stat__label">Rascunhos</span>
        <strong class="stat__value"><?= Format::number($counts['draft']) ?></strong>
    </a>
    <a class="stat" href="<?= $urlGenerator->generate('admin/post/index', [], ['status' => 'rejected']) ?>">
        <span class="stat__label">Rejeitados</span>
        <strong class="stat__value stat__value--danger"><?= Format::number($counts['rejected']) ?></strong>
    </a>
    <?php if ($totalViews !== null): ?>
        <div class="stat">
            <span class="stat__label">Leituras totais</span>
            <strong class="stat__value"><?= Format::number($totalViews) ?></strong>
        </div>
    <?php endif ?>
    <?php if ($userCount !== null): ?>
        <a class="stat" href="<?= $urlGenerator->generate('admin/user/index') ?>">
            <span class="stat__label">Usuários</span>
            <strong class="stat__value"><?= Format::number($userCount) ?></strong>
        </a>
    <?php endif ?>
    <?php if ($categoryCount !== null): ?>
        <a class="stat" href="<?= $urlGenerator->generate('admin/category/index') ?>">
            <span class="stat__label">Categorias</span>
            <strong class="stat__value"><?= Format::number($categoryCount) ?></strong>
        </a>
    <?php endif ?>
</div>

<div class="dash-grid">
    <?php if ($pending !== []): ?>
        <section class="panel">
            <div class="panel__head">
                <h2>Aguardando revisão</h2>
                <a href="<?= $urlGenerator->generate('admin/review') ?>">Ver fila →</a>
            </div>
            <ul class="list">
                <?php foreach ($pending as $post): ?>
                    <li>
                        <a href="<?= $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>"><?= Html::encode($post->title) ?></a>
                        <small class="muted">por <?= Html::encode($post->authorName) ?> · enviado <?= Format::relative($post->submittedAt) ?></small>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endif ?>

    <?php if ($myRejected !== []): ?>
        <section class="panel panel--danger">
            <div class="panel__head">
                <h2>Seus posts devolvidos</h2>
            </div>
            <ul class="list">
                <?php foreach ($myRejected as $post): ?>
                    <li>
                        <a href="<?= $urlGenerator->generate('admin/post/update', ['id' => $post->id]) ?>"><?= Html::encode($post->title) ?></a>
                        <?php if ($post->reviewNote !== null): ?>
                            <blockquote class="note">“<?= Html::encode($post->reviewNote) ?>” — <?= Html::encode($post->reviewerName ?? 'revisor') ?></blockquote>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endif ?>

    <section class="panel panel--wide">
        <div class="panel__head">
            <h2><?= $viewAll ? 'Atividade recente' : 'Seus posts recentes' ?></h2>
            <a href="<?= $urlGenerator->generate('admin/post/index') ?>">Todos os posts →</a>
        </div>
        <?php if ($recent === []): ?>
            <p class="muted">Nenhum post ainda.
                <?php if ($currentUser->can(Permission::POST_CREATE)): ?>
                    <a href="<?= $urlGenerator->generate('admin/post/create') ?>">Escreva o primeiro!</a>
                <?php endif ?>
            </p>
        <?php else: ?>
            <table class="table">
                <thead>
                <tr><th>Título</th><th class="hide-sm">Autor</th><th>Status</th><th class="hide-sm">Atualizado</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $post): ?>
                    <tr>
                        <td><a class="table__title" href="<?= $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>"><?= Html::encode($post->title) ?></a></td>
                        <td class="hide-sm"><?= Html::encode($post->authorName) ?></td>
                        <td><?= $this->render($badge, ['status' => $post->status]) ?></td>
                        <td class="muted hide-sm"><?= Format::relative($post->updatedAt) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>

    <?php if ($viewAll): ?>
        <section class="panel">
            <div class="panel__head"><h2>Sua produção</h2></div>
            <dl class="mini-stats">
                <?php foreach (App\Blog\PostStatus::cases() as $status): ?>
                    <div><dt><?= $status->label() ?></dt><dd><?= $myCounts[$status->value] ?></dd></div>
                <?php endforeach ?>
            </dl>
        </section>
    <?php endif ?>
</div>
