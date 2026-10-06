<?php

declare(strict_types=1);

namespace App\Blog;

use App\Auth\Rbac\Permission;
use App\Shared\Clock;
use DomainException;
use Yiisoft\User\CurrentUser;

/**
 * Regras do fluxo editorial (quem pode fazer o quê com cada post) e as transições de status.
 *
 * As permissões vêm do RBAC; aqui ficam apenas as condições que dependem do estado do post.
 */
final readonly class PostWorkflow
{
    public const SUBMIT = 'submit';
    public const APPROVE = 'approve';
    public const REJECT = 'reject';
    public const UNPUBLISH = 'unpublish';

    public function __construct(
        private CurrentUser $currentUser,
        private PostRepository $posts,
    ) {}

    public function canView(Post $post): bool
    {
        return $this->isAuthor($post) || $this->currentUser->can(Permission::POST_VIEW_ALL);
    }

    public function canEdit(Post $post): bool
    {
        return $this->currentUser->can(Permission::POST_UPDATE, ['post' => $post]);
    }

    public function canDelete(Post $post): bool
    {
        return $this->currentUser->can(Permission::POST_DELETE, ['post' => $post]);
    }

    /** Somente o autor envia o próprio texto para revisão. */
    public function canSubmit(Post $post): bool
    {
        return $this->isAuthor($post) && $post->status->isEditableByAuthor();
    }

    /**
     * Revisores aprovam posts em revisão de outros autores.
     * Quem tem `post.publishAny` (admin) pode publicar qualquer post não publicado, inclusive os próprios.
     */
    public function canApprove(Post $post): bool
    {
        if ($post->status === PostStatus::Published) {
            return false;
        }
        if ($this->currentUser->can(Permission::POST_PUBLISH_ANY)) {
            return true;
        }
        return $post->status === PostStatus::Pending
            && $this->currentUser->can(Permission::POST_REVIEW)
            && !$this->isAuthor($post);
    }

    public function canReject(Post $post): bool
    {
        return $post->status === PostStatus::Pending
            && $this->currentUser->can(Permission::POST_REVIEW)
            && (!$this->isAuthor($post) || $this->currentUser->can(Permission::POST_PUBLISH_ANY));
    }

    public function canUnpublish(Post $post): bool
    {
        return $post->status === PostStatus::Published && $this->currentUser->can(Permission::POST_REVIEW);
    }

    public function can(string $transition, Post $post): bool
    {
        return match ($transition) {
            self::SUBMIT => $this->canSubmit($post),
            self::APPROVE => $this->canApprove($post),
            self::REJECT => $this->canReject($post),
            self::UNPUBLISH => $this->canUnpublish($post),
            default => false,
        };
    }

    /**
     * Aplica a transição e retorna a mensagem de sucesso.
     *
     * @throws DomainException Quando a transição não é permitida ou falta a justificativa da rejeição.
     */
    public function apply(string $transition, Post $post, ?string $note = null): string
    {
        if (!$this->can($transition, $post)) {
            throw new DomainException('Ação não permitida para este post.');
        }

        $now = Clock::now();
        $reviewerId = (int) $this->currentUser->getId();

        switch ($transition) {
            case self::SUBMIT:
                $this->posts->update($post->id, [
                    'status' => PostStatus::Pending->value,
                    'submitted_at' => $now,
                ]);
                return 'Post enviado para revisão.';

            case self::APPROVE:
                $this->posts->update($post->id, [
                    'status' => PostStatus::Published->value,
                    'reviewer_id' => $reviewerId,
                    'review_note' => null,
                    'published_at' => $post->publishedAt?->format('Y-m-d H:i:s') ?? $now,
                ]);
                return 'Post aprovado e publicado.';

            case self::REJECT:
                $note = trim((string) $note);
                if ($note === '') {
                    throw new DomainException('Explique ao autor o motivo da rejeição.');
                }
                $this->posts->update($post->id, [
                    'status' => PostStatus::Rejected->value,
                    'reviewer_id' => $reviewerId,
                    'review_note' => mb_substr($note, 0, 2000),
                ]);
                return 'Post devolvido ao autor com seus comentários.';

            case self::UNPUBLISH:
                $this->posts->update($post->id, [
                    'status' => PostStatus::Draft->value,
                    'published_at' => null,
                ]);
                return 'Post despublicado e movido para rascunhos.';
        }

        throw new DomainException('Transição desconhecida.');
    }

    private function isAuthor(Post $post): bool
    {
        return (string) $post->authorId === $this->currentUser->getId();
    }
}
