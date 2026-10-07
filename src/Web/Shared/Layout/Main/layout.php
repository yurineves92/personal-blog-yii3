<?php

declare(strict_types=1);

use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;

/**
 * @var App\Shared\ApplicationParams $applicationParams
 * @var App\Site\SettingRepository $settings
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var string $content
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$assetManager->register(MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$siteName = $settings->get('site_name');
$brandLetter = mb_strtolower(mb_substr($siteName, 0, 1));
$github = $settings->get('github_url');
$x = $settings->get('x_url');
$githubIcon = $this->render(dirname(__DIR__, 3) . '/Site/Shared/icon-github.php');
$routeName = $currentRoute->getName() ?? '';
$isBlog = str_starts_with($routeName, 'blog/');
$title = $this->getTitle();

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= Html::encode($this->getParameter('metaDescription', $settings->get('site_tagline'))) ?>">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= Html::encode($title !== '' ? $title . ' · ' . $siteName : $siteName) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= $urlGenerator->generate('home') ?>">
            <span class="brand__mark" aria-hidden="true"><?= Html::encode($brandLetter) ?></span>
            <span class="brand__name"><?= Html::encode($siteName) ?></span>
        </a>

        <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Abrir menu">
        <label for="nav-toggle" class="nav-toggle__label" aria-hidden="true"><span></span></label>

        <nav class="site-nav">
            <a href="<?= $urlGenerator->generate('home') ?>" class="<?= $routeName === 'home' ? 'is-active' : '' ?>">Início</a>
            <a href="<?= $urlGenerator->generate('blog/index') ?>" class="<?= $isBlog ? 'is-active' : '' ?>">Artigos</a>
            <a href="<?= $urlGenerator->generate('home') ?>#projetos">Projetos</a>
            <a href="<?= $urlGenerator->generate('home') ?>#sobre">Sobre</a>
            <?php if ($github !== ''): ?>
                <a class="site-nav__icon" href="<?= Html::encode($github) ?>" target="_blank" rel="noopener" title="GitHub" aria-label="GitHub"><?= $githubIcon ?></a>
            <?php endif ?>
            <?php if ($currentUser->isGuest()): ?>
                <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('admin/login') ?>">Entrar</a>
            <?php else: ?>
                <a class="btn btn--dark btn--sm" href="<?= $urlGenerator->generate('admin/dashboard') ?>">Painel</a>
            <?php endif ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div>
            <a class="brand brand--light" href="<?= $urlGenerator->generate('home') ?>">
                <span class="brand__mark" aria-hidden="true"><?= Html::encode($brandLetter) ?></span>
                <span class="brand__name"><?= Html::encode($siteName) ?></span>
            </a>
            <p class="site-footer__tagline"><?= Html::encode($settings->get('site_tagline')) ?></p>
        </div>
        <nav class="site-footer__nav">
            <a href="<?= $urlGenerator->generate('home') ?>">Início</a>
            <a href="<?= $urlGenerator->generate('blog/index') ?>">Artigos</a>
            <?php if ($github !== ''): ?><a href="<?= Html::encode($github) ?>" target="_blank" rel="noopener">GitHub</a><?php endif ?>
            <?php if ($x !== ''): ?><a href="<?= Html::encode($x) ?>" target="_blank" rel="noopener">X / Twitter</a><?php endif ?>
            <a href="<?= $urlGenerator->generate('admin/login') ?>">Painel</a>
        </nav>
    </div>
    <div class="container site-footer__bottom">
        <span>© <?= date('Y') ?> <?= Html::encode($siteName) ?></span>
        <span><?= Html::encode($settings->get('footer_text')) ?></span>
    </div>
</footer>

<?= $this->render(dirname(__DIR__, 2) . '/mermaid.php') ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
