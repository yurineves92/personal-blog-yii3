<?php

declare(strict_types=1);

use App\Blog\PostStatus;
use App\Blog\PostWorkflow;
use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Blog\Post $post
 * @var string $html
 * @var int $readingMinutes
 * @var App\Blog\PostWorkflow $workflow
 */

$this->setTitle($post->title);
$transitionUrl = static fn(string $t) => $urlGenerator->generate('admin/post/transition', ['id' => $post->id, 'transition' => $t]);
?>

<div class="page-header">
    <div>
        <a class="back" href="<?= $urlGenerator->generate('admin/post/index') ?>">← Posts</a>
        <h1><?= Html::encode($post->title) ?></h1>
        <p class="muted">
            <?= $this->render('../Shared/status-badge.php', ['status' => $post->status]) ?>
            por <strong><?= Html::encode($post->authorName) ?></strong>
            · <?= $readingMinutes ?> min de leitura
            <?php if ($post->categoryName !== null): ?> · <?= Html::encode($post->categoryName) ?><?php endif ?>
        </p>
    </div>
    <div class="page-header__actions">
        <?php if ($post->isPublished()): ?>
            <a class="btn btn--ghost" href="<?= $urlGenerator->generate('blog/post', ['slug' => $post->slug]) ?>" target="_blank" rel="noopener">Ver no site ↗</a>
        <?php endif ?>
        <?php if ($workflow->canEdit($post)): ?>
            <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/post/update', ['id' => $post->id]) ?>">Editar</a>
        <?php endif ?>
    </div>
</div>

<div class="editor">
    <div class="editor__main">
        <?php if ($post->status === PostStatus::Rejected && $post->reviewNote !== null): ?>
            <div class="callout callout--danger">
                <strong>Devolvido por <?= Html::encode($post->reviewerName ?? 'revisor') ?>:</strong>
                <p><?= nl2br(Html::encode($post->reviewNote)) ?></p>
            </div>
        <?php elseif ($post->status === PostStatus::Pending): ?>
            <div class="callout callout--warn">
                Este post está aguardando revisão desde <?= Format::date($post->submittedAt, true) ?>.
            </div>
        <?php endif ?>

        <article class="panel preview">
            <?php if ($post->coverUrl !== null): ?>
                <img class="preview__cover" src="<?= Html::encode($post->coverUrl) ?>" alt="">
            <?php endif ?>
            <?php if ($post->excerpt !== null): ?>
                <p class="preview__lead"><?= Html::encode($post->excerpt) ?></p>
            <?php endif ?>
            <div class="prose"><?= $html ?></div>
        </article>
    </div>

    <aside class="editor__side">
        <div class="panel">
            <h3>Fluxo editorial</h3>
            <ol class="timeline">
                <li class="is-done">Criado <?= Format::date($post->createdAt, true) ?></li>
                <?php if ($post->submittedAt !== null): ?>
                    <li class="is-done">Enviado para revisão <?= Format::date($post->submittedAt, true) ?></li>
                <?php endif ?>
                <?php if ($post->status === PostStatus::Rejected): ?>
                    <li class="is-danger">Rejeitado por <?= Html::encode($post->reviewerName ?? '—') ?></li>
                <?php endif ?>
                <?php if ($post->publishedAt !== null): ?>
                    <li class="is-done">Publicado <?= Format::date($post->publishedAt, true) ?><?= $post->reviewerName ? ' · aprovado por ' . Html::encode($post->reviewerName) : '' ?></li>
                <?php endif ?>
                <li>Última alteração <?= Format::relative($post->updatedAt) ?></li>
            </ol>

            <div class="editor__actions">
                <?php if ($workflow->canSubmit($post)): ?>
                    <form method="post" action="<?= $transitionUrl(PostWorkflow::SUBMIT) ?>">
                        <?= $csrf->hiddenInput() ?>
                        <button class="btn btn--primary btn--block" type="submit">Enviar para revisão</button>
                    </form>
                <?php endif ?>

                <?php if ($workflow->canApprove($post)): ?>
                    <form method="post" action="<?= $transitionUrl(PostWorkflow::APPROVE) ?>">
                        <?= $csrf->hiddenInput() ?>
                        <button class="btn btn--success btn--block" type="submit">
                            <?= $post->status === PostStatus::Pending ? 'Aprovar e publicar' : 'Publicar agora' ?>
                        </button>
                    </form>
                <?php endif ?>

                <?php if ($workflow->canUnpublish($post)): ?>
                    <form method="post" action="<?= $transitionUrl(PostWorkflow::UNPUBLISH) ?>" data-confirm="Despublicar este post? Ele sairá do site e voltará para rascunho.">
                        <?= $csrf->hiddenInput() ?>
                        <button class="btn btn--ghost btn--block" type="submit">Despublicar</button>
                    </form>
                <?php endif ?>

                <?php if ($workflow->canDelete($post)): ?>
                    <form method="post" action="<?= $urlGenerator->generate('admin/post/delete', ['id' => $post->id]) ?>" data-confirm="Excluir este post? Esta ação não pode ser desfeita.">
                        <?= $csrf->hiddenInput() ?>
                        <button class="btn btn--danger-ghost btn--block" type="submit">Excluir</button>
                    </form>
                <?php endif ?>
            </div>

            <?php if ($post->status === PostStatus::Pending && !$workflow->canApprove($post) && !$workflow->canReject($post)): ?>
                <p class="hint">Aguardando um revisor. Enquanto isso, o post fica bloqueado para edição pelo autor.</p>
            <?php endif ?>
        </div>

        <?php if ($workflow->canReject($post)): ?>
            <div class="panel">
                <h3>Devolver ao autor</h3>
                <form method="post" action="<?= $transitionUrl(PostWorkflow::REJECT) ?>">
                    <?= $csrf->hiddenInput() ?>
                    <div class="field">
                        <label for="note">Comentário para o autor</label>
                        <textarea id="note" name="note" rows="4" required placeholder="O que precisa ser ajustado?"></textarea>
                    </div>
                    <button class="btn btn--danger btn--block" type="submit">Rejeitar</button>
                </form>
            </div>
        <?php endif ?>

        <div class="panel panel--muted">
            <h3>Informações</h3>
            <dl class="details">
                <dt>Slug</dt><dd>/blog/<?= Html::encode($post->slug) ?></dd>
                <dt>Leituras</dt><dd><?= Format::number($post->views) ?></dd>
                <dt>Autor</dt><dd><?= Html::encode($post->authorName) ?></dd>
                <dt>Revisor</dt><dd><?= Html::encode($post->reviewerName ?? '—') ?></dd>
            </dl>
        </div>
    </aside>
</div>
