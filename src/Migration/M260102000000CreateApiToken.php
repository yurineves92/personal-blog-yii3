<?php

declare(strict_types=1);

namespace App\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * Tokens de acesso à API. Guardamos apenas o hash SHA-256: o token em texto puro é mostrado uma única vez.
 */
final class M260102000000CreateApiToken implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('api_token', [
            'id' => ColumnBuilder::primaryKey(),
            'user_id' => ColumnBuilder::integer()->notNull(),
            'name' => ColumnBuilder::string(100)->notNull(),
            'token_hash' => ColumnBuilder::char(64)->notNull()->unique(),
            'last_used_at' => ColumnBuilder::datetime()->null(),
            'expires_at' => ColumnBuilder::datetime()->notNull(),
            'created_at' => ColumnBuilder::datetime()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $b->addForeignKey('api_token', 'fk-api_token-user', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('api_token');
    }
}
