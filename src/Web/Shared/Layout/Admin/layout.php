<?php

declare(strict_types=1);

use App\Auth\Rbac\Permission;
use App\Blog\PostStatus;
use App\Shared\Format;
use App\User\User;
use App\Web\Shared\Layout\Admin\AdminAsset;
use Yiisoft\Html\Html;

/**
 * @var App\Shared\ApplicationParams $applicationParams
 * @var App\Site\SettingRepository $settings
 * @var App\Blog\PostRepository $postRepository
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var Yiisoft\Session\Flash\FlashInterface $flash
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var string $content
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$assetManager->register(AdminAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$siteName = $settings->get('site_name');
$routeName = $currentRoute->getName() ?? '';
$identity = $currentUser->getIdentity();
$user = $identity instanceof User ? $identity : null;

$menu = [];
if ($user !== null) {
    $menu[] = ['Painel', 'admin/dashboard', '▦', $routeName === 'admin/dashboard', null];
    $menu[] = ['Posts', 'admin/post/index', '✎', in_array($routeName, ['admin/post/index', 'admin/post/view', 'admin/post/update'], true), null];
    if ($currentUser->can(Permission::POST_CREATE)) {
        $menu[] = ['Novo post', 'admin/post/create', '+', $routeName === 'admin/post/create', null];
    }
    if ($currentUser->can(Permission::POST_REVIEW)) {
        $pending = $postRepository->countByStatus()[PostStatus::Pending->value];
        $menu[] = ['Fila de revisão', 'admin/review', '✓', $routeName === 'admin/review', $pending ?: null];
    }
    if ($currentUser->can(Permission::CATEGORY_MANAGE)) {
        $menu[] = ['Categorias', 'admin/category/index', '#', str_starts_with($routeName, 'admin/category/'), null];
    }
    if ($currentUser->can(Permission::USER_MANAGE)) {
        $menu[] = ['Usuários', 'admin/user/index', '☺', str_starts_with($routeName, 'admin/user/'), null];
    }
    $menu[] = ['API', 'admin/api', '{}', $routeName === 'admin/api', null];
    if ($currentUser->can(Permission::SETTINGS_MANAGE)) {
        $menu[] = ['Configurações', 'admin/settings', '⚙', $routeName === 'admin/settings', null];
    }
}

$flashes = [];
foreach (['success', 'error', 'info'] as $type) {
    $message = $flash->get($type);
    if ($message !== null) {
        $flashes[$type] = (string) $message;
    }
}

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title><?= Html::encode($this->getTitle()) ?> · Painel <?= Html::encode($siteName) ?></title>
    <?php $this->head() ?>
</head>
<body class="<?= $user === null ? 'is-guest' : 'is-admin' ?>">
<?php $this->beginBody() ?>

<?php if ($user === null): ?>
    <?php foreach ($flashes as $type => $message): ?>
        <div class="flash flash--<?= $type ?> flash--floating" role="status"><?= Html::encode($message) ?></div>
    <?php endforeach ?>
    <?= $content ?>
<?php else: ?>
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-label="Abrir menu">
    <aside class="sidebar">
        <a class="sidebar__brand" href="<?= $urlGenerator->generate('admin/dashboard') ?>">
            <span class="brand-mark"><?= Html::encode(mb_strtolower(mb_substr($siteName, 0, 1))) ?></span>
            <span><?= Html::encode($siteName) ?> <small>CMS</small></span>
        </a>

        <nav class="sidebar__nav">
            <?php foreach ($menu as [$label, $route, $icon, $active, $badge]): ?>
                <a href="<?= $urlGenerator->generate($route) ?>" class="<?= $active ? 'is-active' : '' ?>">
                    <span class="sidebar__icon" aria-hidden="true"><?= $icon ?></span>
                    <span><?= Html::encode($label) ?></span>
                    <?php if ($badge !== null): ?><span class="badge badge--count"><?= (int) $badge ?></span><?php endif ?>
                </a>
            <?php endforeach ?>
        </nav>

        <div class="sidebar__footer">
            <a href="<?= $urlGenerator->generate('home') ?>" target="_blank" rel="noopener">↗ Ver site</a>
        </div>
    </aside>
    <label for="sidebar-toggle" class="sidebar-backdrop" aria-hidden="true"></label>

    <div class="shell">
        <header class="topbar">
            <label for="sidebar-toggle" class="topbar__menu" title="Menu">☰</label>
            <div class="topbar__title"><?= Html::encode($this->getTitle()) ?></div>

            <details class="usermenu">
                <summary>
                    <span class="avatar" style="--hue: <?= Format::hue($user->id) ?>"><?= Html::encode($user->initials()) ?></span>
                    <span class="usermenu__info">
                        <strong><?= Html::encode($user->name) ?></strong>
                        <small><?= Html::encode($user->role->label()) ?></small>
                    </span>
                </summary>
                <div class="usermenu__panel">
                    <a href="<?= $urlGenerator->generate('admin/profile') ?>">Meu perfil</a>
                    <form method="post" action="<?= $urlGenerator->generate('admin/logout') ?>">
                        <?= $csrf->hiddenInput() ?>
                        <button type="submit">Sair</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="content">
            <?php foreach ($flashes as $type => $message): ?>
                <div class="flash flash--<?= $type ?>" role="status"><?= Html::encode($message) ?></div>
            <?php endforeach ?>
            <?= $content ?>
        </main>
    </div>
<?php endif ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
