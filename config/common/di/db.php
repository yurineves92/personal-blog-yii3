<?php

declare(strict_types=1);

use App\Environment;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection;
use Yiisoft\Db\Mysql\Driver;
use Yiisoft\Db\Mysql\Dsn;

return [
    // O schema é pequeno: cache em memória por request evita cache obsoleto após migrations.
    SchemaCache::class => [
        '__construct()' => [new ArrayCache()],
    ],

    ConnectionInterface::class => [
        'class' => Connection::class,
        '__construct()' => [
            'driver' => new Driver(
                new Dsn(
                    host: Environment::dbHost(),
                    databaseName: Environment::dbName(),
                    port: (string) Environment::dbPort(),
                    options: ['charset' => 'utf8mb4'],
                ),
                Environment::dbUser(),
                Environment::dbPassword(),
            ),
        ],
    ],
];
