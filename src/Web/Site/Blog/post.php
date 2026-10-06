<?php

declare(strict_types=1);

use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var App\Blog\Post $post
 * @var string $html
 * @var int $readingMinutes
 * @var App\Blog\Post[] $related
 */

$this->setTitle($post->title);
$this->setParameter('metaDescription', $post->summary(160));
?>

<article class="article">
    <header class="article__head container container--narrow">
        <?php if ($post->categoryName !== null): ?>
            <a class="tag" href="<?= $urlGenerator->generate('blog/category', ['slug' => $post->categorySlug]) ?>">
                <?= Html::encode($post->categoryName) ?>
            </a>
        <?php endif ?>
        <h1 class="article__title"><?= Html::encode($post->title) ?></h1>
        <?php if ($post->excerpt !== null): ?>
            <p class="article__lead"><?= Html::encode($post->excerpt) ?></p>
        <?php endif ?>
        <div class="meta meta--lg">
            <span class="avatar" style="--hue: <?= Format::hue($post->authorId) ?>"><?= Html::encode(mb_substr($post->authorName, 0, 1)) ?></span>
            <div>
                <strong><?= Html::encode($post->authorName) ?></strong>
                <div>
                    <time datetime="<?= $post->publishedAt?->format('c') ?>"><?= Format::date($post->publishedAt) ?></time>
                    · <?= $readingMinutes ?> min de leitura
                    · <?= Format::number($post->views + 1) ?> leituras
                </div>
            </div>
            <?php if (!$currentUser->isGuest() && $currentUser->can('post.update', ['post' => $post])): ?>
                <a class="btn btn--ghost btn--sm meta__edit" href="<?= $urlGenerator->generate('admin/post/update', ['id' => $post->id]) ?>">Editar</a>
            <?php endif ?>
        </div>
    </header>

    <?php if ($post->coverUrl !== null): ?>
        <figure class="article__cover container">
            <img src="<?= Html::encode($post->coverUrl) ?>" alt="">
        </figure>
    <?php else: ?>
        <div class="article__cover article__cover--placeholder container" style="--hue: <?= Format::hue($post->id) ?>" aria-hidden="true"></div>
    <?php endif ?>

    <div class="prose container container--narrow">
        <?= $html ?>
    </div>

    <footer class="article__foot container container--narrow">
        <?php if ($post->reviewerName !== null): ?>
            <p class="article__reviewed">✓ Revisado por <strong><?= Html::encode($post->reviewerName) ?></strong></p>
        <?php endif ?>
        <a class="link-arrow" href="<?= $urlGenerator->generate('blog/index') ?>">← Voltar para o blog</a>
    </footer>
</article>

<?php if ($related !== []): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Continue lendo</h2>
            </div>
            <div class="grid">
                <?php foreach ($related as $item): ?>
                    <?= $this->render('../Shared/post-card.php', ['post' => $item]) ?>
                <?php endforeach ?>
            </div>
        </div>
    </section>
<?php endif ?>
