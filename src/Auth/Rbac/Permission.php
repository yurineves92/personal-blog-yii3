<?php

declare(strict_types=1);

namespace App\Auth\Rbac;

/**
 * Nomes das permissões RBAC usadas pela aplicação.
 */
final class Permission
{
    /** Acessar o painel CMS. */
    public const CMS_ACCESS = 'cms.access';

    /** Criar posts. */
    public const POST_CREATE = 'post.create';
    /** Editar qualquer post. */
    public const POST_UPDATE = 'post.update';
    /** Editar os próprios posts enquanto rascunho/rejeitado (regra {@see OwnEditablePostRule}). */
    public const POST_UPDATE_OWN = 'post.updateOwn';
    /** Excluir qualquer post. */
    public const POST_DELETE = 'post.delete';
    /** Excluir os próprios posts enquanto rascunho/rejeitado. */
    public const POST_DELETE_OWN = 'post.deleteOwn';
    /** Ver posts de todos os autores no painel. */
    public const POST_VIEW_ALL = 'post.viewAll';
    /** Aprovar, rejeitar e despublicar posts. */
    public const POST_REVIEW = 'post.review';
    /** Publicar diretamente qualquer post (pulando a revisão, inclusive os próprios). */
    public const POST_PUBLISH_ANY = 'post.publishAny';

    /** Gerenciar categorias. */
    public const CATEGORY_MANAGE = 'category.manage';
    /** Gerenciar usuários e papéis. */
    public const USER_MANAGE = 'user.manage';
    /** Editar textos do site/landing page. */
    public const SETTINGS_MANAGE = 'settings.manage';
}
