<?php

declare(strict_types=1);

use App\Shared\Format;
use App\User\Role;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\User\CurrentUser $currentUser
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\User\User[] $users
 * @var array<string, list<string>> $matrix
 * @var array<string, string> $permissions
 */

$this->setTitle('Usuários');
$roles = [Role::Admin, Role::Reviewer, Role::Editor];
?>

<div class="page-header">
    <div>
        <h1>Usuários</h1>
        <p class="muted">Gerencie quem acessa o painel e com qual papel.</p>
    </div>
    <a class="btn btn--primary" href="<?= $urlGenerator->generate('admin/user/create') ?>">+ Novo usuário</a>
</div>

<div class="panel panel--flush">
    <table class="table table--hover">
        <thead>
        <tr><th>Usuário</th><th>Papel</th><th class="num hide-sm">Posts</th><th class="hide-sm">Último acesso</th><th class="hide-sm">Status</th><th class="actions"></th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr class="<?= $user->isActive ? '' : 'is-muted' ?>">
                <td>
                    <div class="user-cell">
                        <span class="avatar" style="--hue: <?= Format::hue($user->id) ?>"><?= Html::encode($user->initials()) ?></span>
                        <div>
                            <a class="table__title" href="<?= $urlGenerator->generate('admin/user/update', ['id' => $user->id]) ?>"><?= Html::encode($user->name) ?></a>
                            <?php if ($user->getId() === $currentUser->getId()): ?><span class="badge">você</span><?php endif ?>
                            <div class="table__sub"><?= Html::encode($user->email) ?></div>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge--role-<?= $user->role->value ?>"><?= $user->role->label() ?></span></td>
                <td class="num hide-sm"><?= $user->postsCount ?></td>
                <td class="muted nowrap hide-sm"><?= $user->lastLoginAt !== null ? Format::relative($user->lastLoginAt) : 'nunca' ?></td>
                <td class="hide-sm"><?= $user->isActive ? '<span class="dot dot--ok"></span> Ativo' : '<span class="dot"></span> Inativo' ?></td>
                <td class="actions">
                    <a class="btn btn--ghost btn--sm" href="<?= $urlGenerator->generate('admin/user/update', ['id' => $user->id]) ?>">Editar</a>
                    <?php if ($user->getId() !== $currentUser->getId()): ?>
                        <form method="post" action="<?= $urlGenerator->generate('admin/user/delete', ['id' => $user->id]) ?>" data-confirm="Excluir o usuário <?= Html::encode($user->name) ?>?">
                            <?= $csrf->hiddenInput() ?>
                            <button class="btn btn--danger-ghost btn--sm" type="submit">Excluir</button>
                        </form>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

<section class="panel">
    <div class="panel__head">
        <h2>Papéis e permissões (RBAC)</h2>
    </div>
    <table class="table table--matrix">
        <thead>
        <tr>
            <th>Permissão</th>
            <?php foreach ($roles as $role): ?><th class="center"><?= $role->label() ?></th><?php endforeach ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($permissions as $name => $description): ?>
            <tr>
                <td><?= Html::encode($description) ?> <code class="muted"><?= Html::encode($name) ?></code></td>
                <?php foreach ($roles as $role): ?>
                    <td class="center"><?= in_array($name, $matrix[$role->value], true) ? '<span class="check">✓</span>' : '<span class="muted">—</span>' ?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <p class="hint">
        Permissões “próprios posts” usam uma regra RBAC: o editor só edita/exclui posts de sua autoria
        enquanto estão em rascunho ou rejeitados. Revisores não aprovam os próprios posts.
    </p>
</section>
