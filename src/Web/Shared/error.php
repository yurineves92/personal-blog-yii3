<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $status
 * @var string $title
 * @var string $message
 */

$this->setTitle($title);
?>
<div class="empty-state">
    <div class="empty-state__code"><?= $status ?></div>
    <h1><?= Html::encode($title) ?></h1>
    <p><?= Html::encode($message) ?></p>
    <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/dashboard') ?>">Voltar ao painel</a>
</div>
