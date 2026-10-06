<?php

declare(strict_types=1);

use App\Blog\PostRepository;
use App\Site\SettingRepository;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

return [
    'yiisoft/user' => [
        'authUrl' => '/admin/login',
    ],

    'yiisoft/session' => [
        'session' => [
            'options' => [
                'name' => 'miniblog_session',
                'cookie_httponly' => 1,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => 0,
            ],
        ],
    ],

    // Parâmetros disponíveis em todos os templates e layouts.
    'yiisoft/view' => [
        'parameters' => [
            'currentUser' => Reference::to(CurrentUser::class),
            'flash' => Reference::to(FlashInterface::class),
            'settings' => Reference::to(SettingRepository::class),
            'postRepository' => Reference::to(PostRepository::class),
        ],
    ],
];
