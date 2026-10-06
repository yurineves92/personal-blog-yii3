<?php

declare(strict_types=1);

use App\Console;

return [
    'app:seed' => Console\SeedCommand::class,
    'user:create' => Console\CreateUserCommand::class,
];
