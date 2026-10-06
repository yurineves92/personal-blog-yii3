<?php

declare(strict_types=1);

use App\User\UserRepository;
use Yiisoft\Auth\IdentityRepositoryInterface;

return [
    IdentityRepositoryInterface::class => UserRepository::class,
];
