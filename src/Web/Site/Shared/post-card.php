<?php

declare(strict_types=1);

use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var App\Blog\Post $post
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $large
 */

$large ??= false;
$url = $urlGenerator->generate('blog/post', ['slug' => $post->slug]);
$hue = Format::hue($post->id);
?>
<article class="card<?= $large ? ' card--large' : '' ?>">
    <a class="card__cover" href="<?= $url ?>" tabindex="-1" aria-hidden="true"
       style="--hue: <?= $hue ?>">
        <?php if ($post->coverUrl !== null): ?>
            <img src="<?= Html::encode($post->coverUrl) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="card__cover-text"><?= Html::encode(mb_substr($post->title, 0, 1)) ?></span>
        <?php endif ?>
    </a>
    <div class="card__body">
        <?php if ($post->categoryName !== null): ?>
            <a class="tag" href="<?= $urlGenerator->generate('blog/category', ['slug' => $post->categorySlug]) ?>">
                <?= Html::encode($post->categoryName) ?>
            </a>
        <?php endif ?>
        <h3 class="card__title"><a href="<?= $url ?>"><?= Html::encode($post->title) ?></a></h3>
        <p class="card__excerpt"><?= Html::encode($post->summary($large ? 260 : 150)) ?></p>
        <div class="meta">
            <span class="avatar avatar--xs" style="--hue: <?= Format::hue($post->authorId) ?>"><?= Html::encode(mb_substr($post->authorName, 0, 1)) ?></span>
            <span><?= Html::encode($post->authorName) ?></span>
            <span class="meta__dot">·</span>
            <time datetime="<?= $post->publishedAt?->format('c') ?>"><?= Format::date($post->publishedAt) ?></time>
        </div>
    </div>
</article>
