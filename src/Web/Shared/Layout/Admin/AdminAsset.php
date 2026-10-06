<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Admin;

use Yiisoft\Assets\AssetBundle;

final class AdminAsset extends AssetBundle
{
    public ?string $basePath = '@assets/admin';
    public ?string $baseUrl = '@assetsUrl/admin';
    public ?string $sourcePath = '@assetsSource/admin';

    public array $css = [
        'admin.css',
    ];

    public array $js = [
        ['admin.js', 'defer' => true],
    ];
}
