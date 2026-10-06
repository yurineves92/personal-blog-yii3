<?php

declare(strict_types=1);

namespace App\Blog;

use App\Shared\Clock;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;

final readonly class CategoryRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @param bool $onlyPublished Se true, conta apenas posts publicados.
     * @return Category[]
     */
    public function findAll(bool $onlyPublished = false): array
    {
        $statusFilter = $onlyPublished ? " AND p.status = 'published'" : '';
        $rows = $this->db
            ->select([
                'c.*',
                'posts_count' => new Expression("(SELECT COUNT(*) FROM {{post}} p WHERE p.category_id = c.id$statusFilter)"),
            ])
            ->from(['c' => 'category'])
            ->orderBy(['c.name' => SORT_ASC])
            ->all();

        return array_map(Category::fromRow(...), $rows);
    }

    /**
     * Categorias que possuem ao menos um post publicado.
     *
     * @return Category[]
     */
    public function findWithPublishedPosts(): array
    {
        return array_values(array_filter(
            $this->findAll(onlyPublished: true),
            static fn(Category $c): bool => $c->postsCount > 0,
        ));
    }

    /**
     * @return array<int, string>
     */
    public function options(): array
    {
        $options = [];
        foreach ($this->findAll() as $category) {
            $options[$category->id] = $category->name;
        }
        return $options;
    }

    public function findById(int $id): ?Category
    {
        $row = $this->db->select()->from('category')->where(['id' => $id])->one();
        return $row === null ? null : Category::fromRow($row);
    }

    public function findBySlug(string $slug): ?Category
    {
        $row = $this->db->select()->from('category')->where(['slug' => $slug])->one();
        return $row === null ? null : Category::fromRow($row);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = $this->db->select()->from('category')->where(['slug' => $slug]);
        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }
        return $query->exists();
    }

    public function count(): int
    {
        return (int) $this->db->select()->from('category')->count();
    }

    public function insert(array $data): int
    {
        $data['created_at'] = Clock::now();
        $this->db->createCommand()->insert('category', $data)->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->createCommand()->update('category', $data, ['id' => $id])->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand()->delete('category', ['id' => $id])->execute();
    }
}
