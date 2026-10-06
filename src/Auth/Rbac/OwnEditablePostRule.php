<?php

declare(strict_types=1);

namespace App\Auth\Rbac;

use App\Blog\Post;
use Yiisoft\Rbac\Item;
use Yiisoft\Rbac\RuleContext;
use Yiisoft\Rbac\RuleInterface;

/**
 * Permite a ação apenas se o usuário for o autor do post e o post ainda estiver
 * em um estado editável pelo autor (rascunho ou rejeitado).
 *
 * Uso: `$currentUser->can(Permission::POST_UPDATE, ['post' => $post])`.
 */
final class OwnEditablePostRule implements RuleInterface
{
    public function execute(?string $userId, Item $item, RuleContext $context): bool
    {
        $post = $context->getParameterValue('post');

        return $userId !== null
            && $post instanceof Post
            && (string) $post->authorId === $userId
            && $post->status->isEditableByAuthor();
    }
}
