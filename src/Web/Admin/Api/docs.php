<?php

declare(strict_types=1);

use App\Shared\Format;
use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\Renderer\Csrf $csrf
 * @var App\Api\ApiToken[] $tokens
 * @var string|null $newToken
 */

$this->setTitle('API');
$specUrl = $urlGenerator->generate('admin/api/spec');
?>
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">

<div class="page-header">
    <div>
        <h1>API REST</h1>
        <p class="muted">
            Base: <code>/admin/api/v1</code> · autenticação <code>Authorization: Bearer &lt;token&gt;</code> ·
            mesmas permissões do painel. Especificação: <a href="<?= $specUrl ?>" target="_blank">openapi.json</a>
        </p>
    </div>
</div>

<?php if ($newToken !== null): ?>
    <div class="callout callout--success">
        <strong>Seu novo token</strong> (copie agora — por segurança ele não será exibido novamente):
        <div class="token-box">
            <code id="new-token"><?= Html::encode((string) $newToken) ?></code>
            <button type="button" class="btn btn--ghost btn--sm" data-copy="#new-token">Copiar</button>
        </div>
        <p class="hint">O Swagger UI abaixo já foi autorizado com este token.</p>
    </div>
<?php endif ?>

<div class="api-grid">
    <section class="panel">
        <div class="panel__head"><h2>Seus tokens</h2></div>
        <form class="token-form" method="post" action="<?= $urlGenerator->generate('admin/api/token/create') ?>">
            <?= $csrf->hiddenInput() ?>
            <input type="text" name="name" placeholder="Nome (ex.: postman, cli)" maxlength="100">
            <button class="btn btn--primary" type="submit">Gerar token</button>
        </form>

        <?php if ($tokens === []): ?>
            <p class="muted">Nenhum token ainda.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Nome</th><th class="hide-sm">Último uso</th><th>Expira</th><th class="actions"></th></tr></thead>
                <tbody>
                <?php foreach ($tokens as $token): ?>
                    <tr class="<?= $token->isExpired() ? 'is-muted' : '' ?>">
                        <td><strong><?= Html::encode($token->name) ?></strong></td>
                        <td class="muted hide-sm"><?= $token->lastUsedAt ? Format::relative($token->lastUsedAt) : 'nunca' ?></td>
                        <td class="muted"><?= $token->isExpired() ? 'expirado' : Format::date($token->expiresAt) ?></td>
                        <td class="actions">
                            <form method="post" action="<?= $urlGenerator->generate('admin/api/token/revoke', ['id' => $token->id]) ?>" data-confirm="Revogar o token “<?= Html::encode($token->name) ?>”?">
                                <?= $csrf->hiddenInput() ?>
                                <button class="btn btn--danger-ghost btn--sm" type="submit">Revogar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>

    <section class="panel panel--muted">
        <div class="panel__head"><h2>Exemplo com curl</h2></div>
        <pre class="code-sample"><code>curl -X POST http://localhost:8080/admin/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@miniblog.test","password":"admin123"}'

curl http://localhost:8080/admin/api/v1/posts?status=pending \
  -H "Authorization: Bearer mb_…"</code></pre>
    </section>
</div>

<section class="panel swagger-panel">
    <div id="swagger-ui"></div>
</section>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.addEventListener('load', function () {
        var ui = SwaggerUIBundle({
            url: <?= json_encode($specUrl) ?>,
            dom_id: '#swagger-ui',
            deepLinking: true,
            persistAuthorization: true,
            tryItOutEnabled: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 0
        });
        <?php if ($newToken !== null): ?>
        ui.preauthorizeApiKey('bearerAuth', <?= json_encode((string) $newToken) ?>);
        <?php endif ?>
    });
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy]');
        if (!button) return;
        navigator.clipboard.writeText(document.querySelector(button.getAttribute('data-copy')).textContent.trim());
        button.textContent = 'Copiado!';
    });
</script>
