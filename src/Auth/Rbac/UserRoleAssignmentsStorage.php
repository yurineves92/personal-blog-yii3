<?php

declare(strict_types=1);

namespace App\Auth\Rbac;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Rbac\Assignment;
use Yiisoft\Rbac\SimpleAssignmentsStorage;

/**
 * Atribuições RBAC lidas da coluna `user.role`: cada usuário tem exatamente um papel.
 * A troca de papel é feita no cadastro de usuários, por isso a escrita aqui é só em memória.
 */
final class UserRoleAssignmentsStorage extends SimpleAssignmentsStorage
{
    /** @var array<string, true> */
    private array $loadedUsers = [];
    private bool $allLoaded = false;

    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function getAll(): array
    {
        if (!$this->allLoaded) {
            $rows = $this->db->select(['id', 'role', 'created_at'])->from('user')->where(['is_active' => true])->all();
            foreach ($rows as $row) {
                $this->addFromRow($row);
            }
            $this->allLoaded = true;
        }

        return $this->assignments;
    }

    public function getByUserId(string $userId): array
    {
        if (!$this->allLoaded && !isset($this->loadedUsers[$userId])) {
            $row = $this->db->select(['id', 'role', 'created_at'])
                ->from('user')
                ->where(['id' => (int) $userId, 'is_active' => true])
                ->one();
            if ($row !== null) {
                $this->addFromRow($row);
            }
            $this->loadedUsers[$userId] = true;
        }

        return $this->assignments[$userId] ?? [];
    }

    public function getByItemNames(array $itemNames): array
    {
        $this->getAll();
        return parent::getByItemNames($itemNames);
    }

    public function hasItem(string $name): bool
    {
        $this->getAll();
        return parent::hasItem($name);
    }

    private function addFromRow(array $row): void
    {
        $userId = (string) $row['id'];
        $this->assignments[$userId] = [
            (string) $row['role'] => new Assignment($userId, (string) $row['role'], strtotime((string) $row['created_at']) ?: time()),
        ];
        $this->loadedUsers[$userId] = true;
    }
}
