<?php

declare(strict_types=1);

use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var App\Site\SettingRepository $settings
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Blog\Post|null $featured
 * @var App\Blog\Post[] $latest
 * @var App\Blog\Post[] $mostRead
 * @var App\Blog\Category[] $categories
 * @var int $publishedCount
 */

$this->setTitle('');
$card = '../Shared/post-card.php';
$avatar = $settings->get('author_avatar');
$github = $settings->get('github_url');
$x = $settings->get('x_url');
$projects = $settings->projects();
$aboutParagraphs = array_filter(array_map('trim', preg_split('/\R{2,}/', $settings->get('about_text')) ?: []));
?>

<section class="hero">
    <div class="container hero__inner">
        <div class="hero__text">
            <div class="author-chip">
                <?php if ($avatar !== ''): ?>
                    <img class="author-chip__photo" src="<?= Html::encode($avatar) ?>" alt="" width="44" height="44">
                <?php endif ?>
                <div>
                    <strong><?= Html::encode($settings->get('author_name')) ?></strong>
                    <span><?= Html::encode($settings->get('author_role')) ?></span>
                </div>
            </div>
            <h1 class="hero__title"><?= Html::encode($settings->get('hero_title')) ?></h1>
            <p class="hero__subtitle"><?= Html::encode($settings->get('hero_subtitle')) ?></p>
            <div class="hero__actions">
                <a class="btn btn--primary btn--lg" href="<?= $urlGenerator->generate('blog/index') ?>">
                    <?= Html::encode($settings->get('hero_cta')) ?> →
                </a>
                <?php if ($github !== ''): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= Html::encode($github) ?>" target="_blank" rel="noopener">
                        <?= $this->render('../Shared/icon-github.php') ?> GitHub
                    </a>
                <?php endif ?>
            </div>
            <dl class="hero__stats">
                <div><dt><?= Format::number($publishedCount) ?></dt><dd>artigos publicados</dd></div>
                <div><dt><?= count($categories) ?></dt><dd>temas</dd></div>
                <div><dt><?= count($projects) ?></dt><dd>projetos em destaque</dd></div>
            </dl>
        </div>

        <?php if ($featured !== null): ?>
            <div class="hero__featured">
                <span class="hero__featured-label">Artigo mais recente</span>
                <?= $this->render($card, ['post' => $featured, 'large' => true]) ?>
            </div>
        <?php endif ?>
    </div>
</section>

<?php $stack = $settings->list('stack'); ?>
<?php if ($stack !== []): ?>
    <section class="stack-strip">
        <div class="container stack-strip__inner">
            <span class="stack-strip__label">Stack</span>
            <?php foreach ($stack as $item): ?>
                <span class="stack-item"><?= Html::encode($item) ?></span>
            <?php endforeach ?>
        </div>
    </section>
<?php endif ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <h2 class="section__title">Últimos artigos</h2>
            <a class="link-arrow" href="<?= $urlGenerator->generate('blog/index') ?>">Ver todos →</a>
        </div>

        <?php if ($categories !== []): ?>
            <div class="chips chips--spaced">
                <?php foreach ($categories as $category): ?>
                    <a class="chip" href="<?= $urlGenerator->generate('blog/category', ['slug' => $category->slug]) ?>">
                        <?= Html::encode($category->name) ?> <small><?= $category->postsCount ?></small>
                    </a>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if ($latest === [] && $featured === null): ?>
            <div class="empty">Nenhum artigo publicado ainda. Volte em breve!</div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($latest as $post): ?>
                    <?= $this->render($card, ['post' => $post]) ?>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</section>

<?php if ($projects !== []): ?>
    <section class="section section--alt" id="projetos">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Projetos</h2>
                <?php if ($github !== ''): ?>
                    <a class="link-arrow" href="<?= Html::encode($github) ?>?tab=repositories" target="_blank" rel="noopener">Todos no GitHub ↗</a>
                <?php endif ?>
            </div>
            <div class="projects">
                <?php foreach ($projects as $project): ?>
                    <?php $tag = $project['url'] !== '' ? 'a' : 'div'; ?>
                    <<?= $tag ?> class="project"<?= $project['url'] !== '' ? ' href="' . Html::encode($project['url']) . '" target="_blank" rel="noopener"' : '' ?>>
                        <div class="project__head">
                            <?= $this->render('../Shared/icon-repo.php') ?>
                            <strong><?= Html::encode($project['name']) ?></strong>
                            <?php if ($project['tag'] !== ''): ?><span class="project__tag"><?= Html::encode($project['tag']) ?></span><?php endif ?>
                        </div>
                        <p><?= Html::encode($project['description']) ?></p>
                    </<?= $tag ?>>
                <?php endforeach ?>
            </div>
        </div>
    </section>
<?php endif ?>

<section class="section" id="sobre">
    <div class="container about">
        <div class="about__text">
            <span class="eyebrow">Sobre</span>
            <h2 class="section__title"><?= Html::encode($settings->get('about_title')) ?></h2>
            <?php foreach ($aboutParagraphs as $paragraph): ?>
                <p><?= nl2br(Html::encode($paragraph)) ?></p>
            <?php endforeach ?>
        </div>
        <aside class="profile-card">
            <?php if ($avatar !== ''): ?>
                <img class="profile-card__photo" src="<?= Html::encode($avatar) ?>" alt="Foto de <?= Html::encode($settings->get('author_name')) ?>" width="96" height="96" loading="lazy">
            <?php endif ?>
            <strong class="profile-card__name"><?= Html::encode($settings->get('author_name')) ?></strong>
            <span class="profile-card__role"><?= Html::encode($settings->get('author_role')) ?></span>
            <?php if ($settings->get('author_location') !== ''): ?>
                <span class="profile-card__location">📍 <?= Html::encode($settings->get('author_location')) ?></span>
            <?php endif ?>
            <div class="profile-card__links">
                <?php if ($github !== ''): ?>
                    <a class="btn btn--dark btn--sm" href="<?= Html::encode($github) ?>" target="_blank" rel="noopener"><?= $this->render('../Shared/icon-github.php') ?> GitHub</a>
                <?php endif ?>
                <?php if ($x !== ''): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= Html::encode($x) ?>" target="_blank" rel="noopener">𝕏 / Twitter</a>
                <?php endif ?>
            </div>
        </aside>
    </div>
</section>

<?php if ($mostRead !== []): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Mais lidos</h2>
            </div>
            <ol class="ranking">
                <?php foreach ($mostRead as $i => $post): ?>
                    <li>
                        <span class="ranking__pos"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div>
                            <a href="<?= $urlGenerator->generate('blog/post', ['slug' => $post->slug]) ?>"><?= Html::encode($post->title) ?></a>
                            <div class="meta"><?= Html::encode($post->categoryName ?? '') ?> · <?= Format::number($post->views) ?> leituras</div>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        </div>
    </section>
<?php endif ?>

<section class="section">
    <div class="container">
        <div class="cta">
            <div>
                <h2>Bora trocar uma ideia?</h2>
                <p>Achou um erro, tem uma sugestão de tema ou quer conversar sobre PHP? Me chama.</p>
            </div>
            <div class="cta__actions">
                <?php if ($github !== ''): ?>
                    <a class="btn btn--light btn--lg" href="<?= Html::encode($github) ?>" target="_blank" rel="noopener"><?= $this->render('../Shared/icon-github.php') ?> GitHub</a>
                <?php endif ?>
                <?php if ($x !== ''): ?>
                    <a class="btn btn--outline-light btn--lg" href="<?= Html::encode($x) ?>" target="_blank" rel="noopener">𝕏 / Twitter</a>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
