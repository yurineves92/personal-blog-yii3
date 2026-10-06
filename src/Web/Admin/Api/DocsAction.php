<?php

declare(strict_types=1);

namespace App\Web\Admin\Api;

use App\Api\ApiTokenRepository;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Página "API" do painel: Swagger UI + tokens de acesso do usuário logado.
 */
final readonly class DocsAction
{
    public const FLASH_NEW_TOKEN = 'api_new_token';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CurrentUser $currentUser,
        private ApiTokenRepository $tokens,
        private FlashInterface $flash,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/docs', [
                'tokens' => $this->tokens->findByUser((int) $this->currentUser->getId()),
                // Token recém-criado: exibido uma única vez e já usado para autorizar o Swagger UI.
                'newToken' => $this->flash->get(self::FLASH_NEW_TOKEN),
            ]);
    }
}
