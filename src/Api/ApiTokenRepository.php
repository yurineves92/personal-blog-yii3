<?php

declare(strict_types=1);

namespace App\Api;

use App\Shared\Clock;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class ApiTokenRepository
{
    /** Prefixo que facilita identificar o token em logs e scanners de segredos. */
    public const PREFIX = 'mb_';
    public const DEFAULT_TTL_DAYS = 30;

    public function __construct(
        private ConnectionInterface $db,
    ) {}

    /**
     * Cria um token e retorna o valor em texto puro — ele não pode ser recuperado depois.
     *
     * @return array{token: string, model: ApiToken}
     */
    public function create(int $userId, string $name, int $ttlDays = self::DEFAULT_TTL_DAYS): array
    {
        $token = self::PREFIX . bin2hex(random_bytes(32));
        $this->db->createCommand()->insert('api_token', [
            'user_id' => $userId,
            'name' => mb_substr(trim($name) ?: 'token', 0, 100),
            'token_hash' => self::hash($token),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$ttlDays} days")),
            'created_at' => Clock::now(),
        ])->execute();

        $model = $this->findById((int) $this->db->getLastInsertId());
        assert($model !== null);

        return ['token' => $token, 'model' => $model];
    }

    /**
     * Valida o token recebido no header Authorization e devolve o ID do usuário dono.
     */
    public function findUserIdByToken(string $token): ?int
    {
        if (!str_starts_with($token, self::PREFIX)) {
            return null;
        }

        $row = $this->db->select(['id', 'user_id'])
            ->from('api_token')
            ->where(['token_hash' => self::hash($token)])
            ->andWhere(['>', 'expires_at', Clock::now()])
            ->one();

        if ($row === null) {
            return null;
        }

        $this->db->createCommand()
            ->update('api_token', ['last_used_at' => Clock::now()], ['id' => (int) $row['id']])
            ->execute();

        return (int) $row['user_id'];
    }

    public function findById(int $id): ?ApiToken
    {
        $row = $this->db->select()->from('api_token')->where(['id' => $id])->one();
        return $row === null ? null : ApiToken::fromRow($row);
    }

    /**
     * @return ApiToken[]
     */
    public function findByUser(int $userId): array
    {
        $rows = $this->db->select()->from('api_token')
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return array_map(ApiToken::fromRow(...), $rows);
    }

    public function revoke(int $id, int $userId): bool
    {
        return $this->db->createCommand()
            ->delete('api_token', ['id' => $id, 'user_id' => $userId])
            ->execute() > 0;
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
