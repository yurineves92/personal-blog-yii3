<?php

declare(strict_types=1);

use App\Blog\PostWorkflow;
use App\Shared\Format;
use App\Shared\Markdown;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Blog\Post[] $pending
 * @var App\Blog\PostWorkflow $workflow
 */

$this->setTitle('Fila de revisão');
?>

<div class="page-header">
    <div>
        <h1>Fila de revisão</h1>
        <p class="muted">Posts enviados pelos autores, do mais antigo para o mais recente.</p>
    </div>
</div>

<?php if ($pending === []): ?>
    <div class="panel empty-state">
        <div class="empty-state__icon">✓</div>
        <h2>Tudo revisado!</h2>
        <p class="muted">Nenhum post aguardando revisão no momento.</p>
    </div>
<?php else: ?>
    <div class="review-list">
        <?php foreach ($pending as $post): ?>
            <article class="panel review-item">
                <div class="review-item__body">
                    <h2><a href="<?= $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>"><?= Html::encode($post->title) ?></a></h2>
                    <p class="muted">
                        por <strong><?= Html::encode($post->authorName) ?></strong>
                        · enviado <?= Format::relative($post->submittedAt) ?>
                        · <?= Markdown::readingMinutes($post->content) ?> min de leitura
                        <?php if ($post->categoryName !== null): ?> · <?= Html::encode($post->categoryName) ?><?php endif ?>
                    </p>
                    <p><?= Html::encode($post->summary(240)) ?></p>
                </div>
                <div class="review-item__actions">
                    <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>">Ler e revisar</a>
                    <?php if ($workflow->canApprove($post)): ?>
                        <form method="post" action="<?= $urlGenerator->generate('admin/post/transition', ['id' => $post->id, 'transition' => PostWorkflow::APPROVE]) ?>">
                            <?= $csrf->hiddenInput() ?>
                            <input type="hidden" name="return" value="review">
                            <button class="btn btn--success btn--sm" type="submit">Aprovar</button>
                        </form>
                    <?php else: ?>
                        <span class="hint">Você é o autor — outro revisor precisa aprovar.</span>
                    <?php endif ?>
                </div>
            </article>
        <?php endforeach ?>
    </div>
<?php endif ?>
