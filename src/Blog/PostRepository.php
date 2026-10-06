<?php

declare(strict_types=1);

namespace App\Blog;

use App\Shared\Clock;
use App\Shared\Paginator;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;

final readonly class PostRepository
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * @return Paginator<Post>
     */
    public function publishedPage(int $page, int $perPage, ?int $categoryId = null, ?string $search = null): Paginator
    {
        $query = $this->baseQuery()
            ->where(['p.status' => PostStatus::Published->value])
            ->andFilterWhere(['p.category_id' => $categoryId]);

        $this->applySearch($query, $search);

        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderBy(['p.published_at' => SORT_DESC, 'p.id' => SORT_DESC])
            ->limit($perPage)
            ->offset(Paginator::offset($page, $perPage))
            ->all();

        return new Paginator(array_map(Post::fromRow(...), $rows), $total, $page, $perPage);
    }

    /**
     * @return Post[]
     */
    public function latestPublished(int $limit, ?int $exceptId = null, ?int $categoryId = null): array
    {
        $query = $this->baseQuery()
            ->where(['p.status' => PostStatus::Published->value])
            ->andFilterWhere(['p.category_id' => $categoryId])
            ->orderBy(['p.published_at' => SORT_DESC, 'p.id' => SORT_DESC])
            ->limit($limit);

        if ($exceptId !== null) {
            $query->andWhere(['<>', 'p.id', $exceptId]);
        }

        return array_map(Post::fromRow(...), $query->all());
    }

    /**
     * @return Post[]
     */
    public function mostRead(int $limit): array
    {
        $rows = $this->baseQuery()
            ->where(['p.status' => PostStatus::Published->value])
            ->orderBy(['p.views' => SORT_DESC])
            ->limit($limit)
            ->all();

        return array_map(Post::fromRow(...), $rows);
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        $row = $this->baseQuery()
            ->where(['p.slug' => $slug, 'p.status' => PostStatus::Published->value])
            ->one();

        return $row === null ? null : Post::fromRow($row);
    }

    public function findById(int $id): ?Post
    {
        $row = $this->baseQuery()->where(['p.id' => $id])->one();
        return $row === null ? null : Post::fromRow($row);
    }

    /**
     * Listagem do painel.
     *
     * @param int|null $authorId Restringe aos posts de um autor (editores só veem os próprios).
     * @return Paginator<Post>
     */
    public function adminPage(
        int $page,
        int $perPage,
        ?string $status = null,
        ?int $authorId = null,
        ?string $search = null,
    ): Paginator {
        $query = $this->baseQuery()
            ->filterWhere(['p.author_id' => $authorId])
            ->andFilterWhere(['p.status' => $status]);

        $this->applySearch($query, $search);

        $total = (int) (clone $query)->count();
        $rows = $query
            ->orderBy(['p.updated_at' => SORT_DESC])
            ->limit($perPage)
            ->offset(Paginator::offset($page, $perPage))
            ->all();

        return new Paginator(array_map(Post::fromRow(...), $rows), $total, $page, $perPage);
    }

    /**
     * @return Post[]
     */
    public function pendingReview(int $limit = 50): array
    {
        $rows = $this->baseQuery()
            ->where(['p.status' => PostStatus::Pending->value])
            ->orderBy(['p.submitted_at' => SORT_ASC])
            ->limit($limit)
            ->all();

        return array_map(Post::fromRow(...), $rows);
    }

    /**
     * @return array<string, int> Contagem por status (todas as chaves de PostStatus presentes).
     */
    public function countByStatus(?int $authorId = null): array
    {
        $query = $this->db->select(['status', 'total' => new Expression('COUNT(*)')])
            ->from('post')
            ->groupBy('status');

        if ($authorId !== null) {
            $query->where(['author_id' => $authorId]);
        }

        $counts = array_fill_keys(array_map(static fn(PostStatus $s) => $s->value, PostStatus::cases()), 0);
        foreach ($query->all() as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function totalViews(): int
    {
        return (int) $this->db->select(new Expression('COALESCE(SUM(views), 0)'))->from('post')->scalar();
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = $this->db->select()->from('post')->where(['slug' => $slug]);
        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }
        return $query->exists();
    }

    public function insert(array $data): int
    {
        $now = Clock::now();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $this->db->createCommand()->insert('post', $data)->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Clock::now();
        $this->db->createCommand()->update('post', $data, ['id' => $id])->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand()->delete('post', ['id' => $id])->execute();
    }

    public function incrementViews(int $id): void
    {
        $this->db->createCommand()
            ->update('post', ['views' => new Expression('views + 1')], ['id' => $id])
            ->execute();
    }

    private function baseQuery(): QueryInterface
    {
        return $this->db
            ->select([
                'p.*',
                'category_name' => 'c.name',
                'category_slug' => 'c.slug',
                'author_name' => 'a.name',
                'reviewer_name' => 'r.name',
            ])
            ->from(['p' => 'post'])
            ->leftJoin(['c' => 'category'], 'c.id = p.category_id')
            ->innerJoin(['a' => 'user'], 'a.id = p.author_id')
            ->leftJoin(['r' => 'user'], 'r.id = p.reviewer_id');
    }

    private function applySearch(QueryInterface $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'p.title', $search],
                ['like', 'p.excerpt', $search],
                ['like', 'p.content', $search],
            ]);
        }
    }
}
