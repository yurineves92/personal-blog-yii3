<?php

declare(strict_types=1);

use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var App\Site\SettingRepository $settings
 * @var App\Blog\Post $post
 * @var string $html
 * @var int $readingMinutes
 * @var App\Blog\Post[] $related
 */

$this->setTitle($post->title);
$this->setParameter('metaDescription', $post->summary(160));

if ($post->coverUrl !== null) {
    // og:image precisa de URL absoluta para as prévias do LinkedIn/X/WhatsApp.
    $uri = $currentRoute->getUri();
    $origin = $uri !== null ? $uri->getScheme() . '://' . $uri->getAuthority() : '';
    $this->setParameter('ogImage', str_starts_with($post->coverUrl, '/') ? $origin . $post->coverUrl : $post->coverUrl);
}

// Foto do autor: usa a do perfil configurado quando o post é do dono do blog.
$avatar = $post->authorName === $settings->get('author_name') ? $settings->get('author_avatar') : '';
$canEdit = !$currentUser->isGuest() && $currentUser->can('post.update', ['post' => $post]);
?>

<article class="article">
    <header class="article__head container container--wide">
        <?php if ($post->coverUrl !== null): ?>
            <figure class="article__cover">
                <img src="<?= Html::encode($post->coverUrl) ?>" alt="<?= Html::encode($post->title) ?>" width="1600" height="900">
            </figure>
        <?php endif ?>

        <?php if ($post->categoryName !== null): ?>
            <div class="article__tags">
                <a class="tag-outline" href="<?= $urlGenerator->generate('blog/category', ['slug' => $post->categorySlug]) ?>">
                    <?= Html::encode($post->categoryName) ?>
                </a>
            </div>
        <?php endif ?>

        <p class="article__reading"><?= $readingMinutes ?> min de leitura</p>
        <h1 class="article__title"><?= Html::encode($post->title) ?></h1>
        <?php if ($post->excerpt !== null): ?>
            <p class="article__lead"><?= Html::encode($post->excerpt) ?></p>
        <?php endif ?>

        <div class="article__facts">
            <div class="fact">
                <svg class="fact__icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg>
                <span>Publicado em</span>
                <time datetime="<?= $post->publishedAt?->format('c') ?>"><?= Format::date($post->publishedAt) ?></time>
            </div>
            <div class="fact">
                <?php if ($avatar !== ''): ?>
                    <img class="fact__avatar" src="<?= Html::encode($avatar) ?>" alt="" width="28" height="28">
                <?php else: ?>
                    <span class="avatar avatar--xs fact__avatar" style="--hue: <?= Format::hue($post->authorId) ?>"><?= Html::encode(mb_substr($post->authorName, 0, 1)) ?></span>
                <?php endif ?>
                <span>Escrito por</span>
                <strong><?= Html::encode($post->authorName) ?></strong>
            </div>
            <?php if ($canEdit): ?>
                <a class="fact fact--link" href="<?= $urlGenerator->generate('admin/post/update', ['id' => $post->id]) ?>">
                    <svg class="fact__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/></svg>
                    <span>Painel</span>
                    <strong>Editar artigo</strong>
                </a>
            <?php else: ?>
                <div class="fact">
                    <svg class="fact__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Leituras</span>
                    <strong><?= Format::number($post->views + 1) ?></strong>
                </div>
            <?php endif ?>
        </div>
    </header>

    <div class="prose container container--narrow">
        <?= $html ?>
    </div>

    <footer class="article__foot container container--narrow">
        <a class="link-arrow" href="<?= $urlGenerator->generate('blog/index') ?>">← Voltar para os artigos</a>
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
