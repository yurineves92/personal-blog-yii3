<?php

declare(strict_types=1);

/**
 * @var App\Blog\PostStatus $status
 */
?>
<span class="badge badge--<?= $status->value ?>"><?= $status->label() ?></span>
