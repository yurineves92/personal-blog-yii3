<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Clock;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;

final readonly class UserRepository implements IdentityRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
    ) {}

    public function findIdentity(string $id): ?User
    {
        $user = $this->findById((int) $id);
        return $user !== null && $user->isActive ? $user : null;
    }

    public function findById(int $id): ?User
    {
        $row = $this->db->select()->from('user')->where(['id' => $id])->one();
        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->select()->from('user')->where(['email' => mb_strtolower(trim($email))])->one();
        return $row === null ? null : User::fromRow($row);
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        $rows = $this->db
            ->select([
                'u.*',
                'posts_count' => new Expression('(SELECT COUNT(*) FROM {{post}} p WHERE p.author_id = u.id)'),
            ])
            ->from(['u' => 'user'])
            ->orderBy(['u.name' => SORT_ASC])
            ->all();

        return array_map(User::fromRow(...), $rows);
    }

    public function count(): int
    {
        return (int) $this->db->select()->from('user')->count();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $query = $this->db->select()->from('user')->where(['email' => mb_strtolower(trim($email))]);
        if ($exceptId !== null) {
            $query->andWhere(['<>', 'id', $exceptId]);
        }
        return $query->exists();
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->db->select()->from('user')
            ->where(['role' => Role::Admin->value, 'is_active' => true])
            ->count();
    }

    public function hasPosts(int $id): bool
    {
        return $this->db->select()->from('post')->where(['author_id' => $id])->exists();
    }

    public function insert(array $data): int
    {
        $now = Clock::now();
        $data['email'] = mb_strtolower(trim((string) $data['email']));
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $this->db->createCommand()->insert('user', $data)->execute();
        return (int) $this->db->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim((string) $data['email']));
        }
        $data['updated_at'] = Clock::now();
        $this->db->createCommand()->update('user', $data, ['id' => $id])->execute();
    }

    public function touchLastLogin(int $id): void
    {
        $this->db->createCommand()->update('user', ['last_login_at' => Clock::now()], ['id' => $id])->execute();
    }

    public function delete(int $id): void
    {
        $this->db->createCommand()->delete('user', ['id' => $id])->execute();
    }
}
