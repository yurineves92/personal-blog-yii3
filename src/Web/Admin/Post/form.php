<?php

declare(strict_types=1);

use App\Web\Shared\FormView;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Web\Admin\Post\PostForm $form
 * @var App\Blog\Post|null $post
 * @var array<int, string> $categories
 * @var bool $canSubmit
 * @var bool $canPublish
 */

$this->setTitle($post === null ? 'Novo post' : 'Editar post');
$f = new FormView($form);
$action = $post === null
    ? $urlGenerator->generate('admin/post/create')
    : $urlGenerator->generate('admin/post/update', ['id' => $post->id]);
?>

<div class="page-header">
    <div>
        <a class="back" href="<?= $post === null ? $urlGenerator->generate('admin/post/index') : $urlGenerator->generate('admin/post/view', ['id' => $post->id]) ?>">← Voltar</a>
        <h1><?= $post === null ? 'Novo post' : Html::encode($post->title) ?></h1>
        <?php if ($post !== null): ?>
            <p class="muted">
                <?= $this->render('../Shared/status-badge.php', ['status' => $post->status]) ?>
                por <?= Html::encode($post->authorName) ?>
            </p>
        <?php endif ?>
    </div>
</div>

<?php if ($post?->reviewNote !== null && $post->status->value === 'rejected'): ?>
    <div class="callout callout--danger">
        <strong>Comentário do revisor<?= $post->reviewerName ? ' (' . Html::encode($post->reviewerName) . ')' : '' ?>:</strong>
        <p><?= nl2br(Html::encode($post->reviewNote)) ?></p>
    </div>
<?php endif ?>

<form class="editor" method="post" action="<?= $action ?>" novalidate>
    <?= $csrf->hiddenInput() ?>

    <div class="editor__main">
        <div class="<?= $f->fieldClass('title') ?>">
            <label for="title">Título</label>
            <input id="title" class="input--title" type="text" name="<?= $f->name('title') ?>" value="<?= $f->value('title') ?>" maxlength="200" required data-slug-source>
            <?= $f->errorTag('title') ?>
        </div>

        <div class="<?= $f->fieldClass('excerpt') ?>">
            <label for="excerpt">Resumo <span class="muted">(opcional — aparece nos cards e no topo do post)</span></label>
            <textarea id="excerpt" name="<?= $f->name('excerpt') ?>" rows="2" maxlength="500" data-counter><?= $f->value('excerpt') ?></textarea>
            <?= $f->errorTag('excerpt') ?>
        </div>

        <div class="<?= $f->fieldClass('content') ?>">
            <label for="content">Conteúdo <span class="muted">(Markdown)</span></label>
            <div class="md-toolbar" data-md-toolbar="content">
                <button type="button" data-md="heading" title="Título">H2</button>
                <button type="button" data-md="bold" title="Negrito"><b>B</b></button>
                <button type="button" data-md="italic" title="Itálico"><i>I</i></button>
                <button type="button" data-md="link" title="Link">link</button>
                <button type="button" data-md="quote" title="Citação">❝</button>
                <button type="button" data-md="code" title="Código">&lt;/&gt;</button>
                <button type="button" data-md="list" title="Lista">•</button>
            </div>
            <textarea id="content" class="input--content" name="<?= $f->name('content') ?>" rows="22" required><?= $f->value('content') ?></textarea>
            <?= $f->errorTag('content') ?>
        </div>
    </div>

    <aside class="editor__side">
        <div class="panel">
            <h3>Publicação</h3>
            <div class="editor__actions">
                <button class="btn btn--ghost btn--block" type="submit" name="<?= $f->name('intent') ?>" value="save">
                    <?= $post?->status->value === 'published' ? 'Salvar alterações' : 'Salvar rascunho' ?>
                </button>
                <?php if ($canSubmit): ?>
                    <button class="btn btn--primary btn--block" type="submit" name="<?= $f->name('intent') ?>" value="submit">
                        Salvar e enviar para revisão
                    </button>
                <?php endif ?>
                <?php if ($canPublish): ?>
                    <button class="btn btn--success btn--block" type="submit" name="<?= $f->name('intent') ?>" value="publish">
                        Salvar e publicar agora
                    </button>
                <?php endif ?>
            </div>
            <?php if ($canSubmit && !$canPublish): ?>
                <p class="hint">Depois de enviado, um revisor aprova ou devolve o post com comentários.</p>
            <?php endif ?>
        </div>

        <div class="panel">
            <h3>Detalhes</h3>
            <div class="<?= $f->fieldClass('categoryId') ?>">
                <label for="categoryId">Categoria</label>
                <select id="categoryId" name="<?= $f->name('categoryId') ?>">
                    <option value="">— Sem categoria —</option>
                    <?php foreach ($categories as $id => $name): ?>
                        <option value="<?= $id ?>"<?= $form->categoryId === $id ? ' selected' : '' ?>><?= Html::encode($name) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $f->errorTag('categoryId') ?>
            </div>

            <div class="<?= $f->fieldClass('slug') ?>">
                <label for="slug">Slug (URL)</label>
                <input id="slug" type="text" name="<?= $f->name('slug') ?>" value="<?= $f->value('slug') ?>" placeholder="gerado a partir do título" data-slug-target>
                <?= $f->errorTag('slug') ?>
                <div class="hint">/blog/<span data-slug-preview><?= $f->value('slug') ?: '…' ?></span></div>
            </div>

            <div class="<?= $f->fieldClass('coverUrl') ?>">
                <label for="coverUrl">Imagem de capa (URL)</label>
                <input id="coverUrl" type="url" name="<?= $f->name('coverUrl') ?>" value="<?= $f->value('coverUrl') ?>" placeholder="https://…">
                <?= $f->errorTag('coverUrl') ?>
                <div class="hint">Sem imagem, o site gera uma capa colorida automaticamente.</div>
            </div>
        </div>

        <div class="panel panel--muted">
            <h3>Dicas de Markdown</h3>
            <ul class="cheatsheet">
                <li><code>## Título</code></li>
                <li><code>**negrito**</code> · <code>_itálico_</code></li>
                <li><code>[texto](https://…)</code></li>
                <li><code>![alt](https://…/img.png)</code></li>
                <li><code>```código```</code></li>
            </ul>
        </div>
    </aside>
</form>
