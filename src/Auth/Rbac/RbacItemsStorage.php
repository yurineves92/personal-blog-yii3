<?php

declare(strict_types=1);

namespace App\Auth\Rbac;

use App\User\Role as UserRole;
use Yiisoft\Rbac\Permission as RbacPermission;
use Yiisoft\Rbac\Role as RbacRole;
use Yiisoft\Rbac\SimpleItemsStorage;

/**
 * Hierarquia RBAC definida em código (papéis → permissões).
 *
 * As permissões "Own" têm uma regra e apontam para a permissão geral como filha,
 * no padrão clássico do Yii: checar `post.update` com `['post' => $post]` concede
 * acesso ao editor somente quando a regra {@see OwnEditablePostRule} passa.
 */
final class RbacItemsStorage extends SimpleItemsStorage
{
    private const ROLE_PERMISSIONS = [
        UserRole::Editor->value => [
            Permission::CMS_ACCESS,
            Permission::POST_CREATE,
            Permission::POST_UPDATE_OWN,
            Permission::POST_DELETE_OWN,
        ],
        UserRole::Reviewer->value => [
            Permission::CMS_ACCESS,
            Permission::POST_CREATE,
            Permission::POST_UPDATE,
            Permission::POST_DELETE_OWN,
            Permission::POST_VIEW_ALL,
            Permission::POST_REVIEW,
        ],
        UserRole::Admin->value => [
            Permission::CMS_ACCESS,
            Permission::POST_CREATE,
            Permission::POST_UPDATE,
            Permission::POST_DELETE,
            Permission::POST_VIEW_ALL,
            Permission::POST_REVIEW,
            Permission::POST_PUBLISH_ANY,
            Permission::CATEGORY_MANAGE,
            Permission::USER_MANAGE,
            Permission::SETTINGS_MANAGE,
        ],
    ];

    public function __construct()
    {
        $now = time();
        $permission = static fn(string $name, string $description, ?string $rule = null): RbacPermission =>
            (new RbacPermission($name))
                ->withDescription($description)
                ->withRuleName($rule)
                ->withCreatedAt($now)
                ->withUpdatedAt($now);

        foreach ([
            $permission(Permission::CMS_ACCESS, 'Acessar o painel'),
            $permission(Permission::POST_CREATE, 'Criar posts'),
            $permission(Permission::POST_UPDATE, 'Editar qualquer post'),
            $permission(Permission::POST_UPDATE_OWN, 'Editar os próprios posts', OwnEditablePostRule::class),
            $permission(Permission::POST_DELETE, 'Excluir qualquer post'),
            $permission(Permission::POST_DELETE_OWN, 'Excluir os próprios posts', OwnEditablePostRule::class),
            $permission(Permission::POST_VIEW_ALL, 'Ver posts de todos os autores'),
            $permission(Permission::POST_REVIEW, 'Aprovar, rejeitar e despublicar posts'),
            $permission(Permission::POST_PUBLISH_ANY, 'Publicar diretamente qualquer post'),
            $permission(Permission::CATEGORY_MANAGE, 'Gerenciar categorias'),
            $permission(Permission::USER_MANAGE, 'Gerenciar usuários'),
            $permission(Permission::SETTINGS_MANAGE, 'Editar configurações do site'),
        ] as $item) {
            $this->add($item);
        }

        $this->addChild(Permission::POST_UPDATE_OWN, Permission::POST_UPDATE);
        $this->addChild(Permission::POST_DELETE_OWN, Permission::POST_DELETE);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = UserRole::from($roleName);
            $this->add(
                (new RbacRole($roleName))
                    ->withDescription($role->description())
                    ->withCreatedAt($now)
                    ->withUpdatedAt($now),
            );
            foreach ($permissions as $permissionName) {
                $this->addChild($roleName, $permissionName);
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return self::ROLE_PERMISSIONS;
    }
}
