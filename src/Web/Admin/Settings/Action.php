<?php

declare(strict_types=1);

namespace App\Web\Admin\Settings;

use App\Site\SettingRepository;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private FormHydrator $formHydrator,
        private SettingRepository $settings,
        private Redirector $redirector,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $form = SettingsForm::fromSettings($this->settings->all());

        if ($this->formHydrator->populateFromPostAndValidate($form, $request)) {
            $this->settings->save($form->toSettings());
            return $this->redirector->withFlash('success', 'Configurações salvas.', 'admin/settings');
        }

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/template', ['form' => $form]);
    }
}
