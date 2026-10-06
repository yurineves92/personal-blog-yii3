<?php

declare(strict_types=1);

namespace App\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * Cria as tabelas do miniblog: usuários, categorias, posts e configurações do site.
 */
final class M260101000000CreateSchema implements RevertibleMigrationInterface
{
    private const OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(MigrationBuilder $b): void
    {
        $b->createTable('user', [
            'id' => ColumnBuilder::primaryKey(),
            'name' => ColumnBuilder::string(120)->notNull(),
            'email' => ColumnBuilder::string(190)->notNull()->unique(),
            'password_hash' => ColumnBuilder::string(255)->notNull(),
            'role' => ColumnBuilder::string(20)->notNull(),
            'bio' => ColumnBuilder::text()->null(),
            'is_active' => ColumnBuilder::boolean()->notNull()->defaultValue(true),
            'last_login_at' => ColumnBuilder::datetime()->null(),
            'created_at' => ColumnBuilder::datetime()->notNull(),
            'updated_at' => ColumnBuilder::datetime()->notNull(),
        ], self::OPTIONS);

        $b->createTable('category', [
            'id' => ColumnBuilder::primaryKey(),
            'name' => ColumnBuilder::string(100)->notNull(),
            'slug' => ColumnBuilder::string(120)->notNull()->unique(),
            'description' => ColumnBuilder::string(255)->null(),
            'created_at' => ColumnBuilder::datetime()->notNull(),
        ], self::OPTIONS);

        $b->createTable('post', [
            'id' => ColumnBuilder::primaryKey(),
            'title' => ColumnBuilder::string(200)->notNull(),
            'slug' => ColumnBuilder::string(220)->notNull()->unique(),
            'excerpt' => ColumnBuilder::string(500)->null(),
            'content' => ColumnBuilder::text()->notNull(),
            'cover_url' => ColumnBuilder::string(500)->null(),
            'category_id' => ColumnBuilder::integer()->null(),
            'author_id' => ColumnBuilder::integer()->notNull(),
            'reviewer_id' => ColumnBuilder::integer()->null(),
            'status' => ColumnBuilder::string(20)->notNull()->defaultValue('draft'),
            'review_note' => ColumnBuilder::text()->null(),
            'views' => ColumnBuilder::integer()->notNull()->defaultValue(0),
            'published_at' => ColumnBuilder::datetime()->null(),
            'submitted_at' => ColumnBuilder::datetime()->null(),
            'created_at' => ColumnBuilder::datetime()->notNull(),
            'updated_at' => ColumnBuilder::datetime()->notNull(),
        ], self::OPTIONS);

        $b->createIndex('post', 'idx-post-status-published_at', ['status', 'published_at']);
        $b->addForeignKey('post', 'fk-post-category', 'category_id', 'category', 'id', 'SET NULL', 'CASCADE');
        $b->addForeignKey('post', 'fk-post-author', 'author_id', 'user', 'id', 'RESTRICT', 'CASCADE');
        $b->addForeignKey('post', 'fk-post-reviewer', 'reviewer_id', 'user', 'id', 'SET NULL', 'CASCADE');

        $b->createTable('setting', [
            'name' => ColumnBuilder::string(64)->notNull()->primaryKey(),
            'value' => ColumnBuilder::text()->null(),
        ], self::OPTIONS);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('setting');
        $b->dropTable('post');
        $b->dropTable('category');
        $b->dropTable('user');
    }
}
