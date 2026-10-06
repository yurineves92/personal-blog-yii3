<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 */

$this->setTitle('Página não encontrada');
?>

<section class="section">
    <div class="container container--narrow not-found">
        <div class="not-found__code">404</div>
        <h1>Página não encontrada</h1>
        <p>
            Não encontramos <code><?= Html::encode($currentRoute->getUri()?->getPath() ?? '') ?></code>.
            Talvez o post tenha sido despublicado ou o endereço esteja incorreto.
        </p>
        <div class="hero__actions">
            <a class="btn btn--primary" href="<?= $urlGenerator->generate('blog/index') ?>">Ir para o blog</a>
            <a class="btn btn--ghost" href="<?= $urlGenerator->generate('home') ?>">Página inicial</a>
        </div>
    </div>
</section>
