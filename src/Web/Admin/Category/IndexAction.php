<?php

declare(strict_types=1);

namespace App\Web\Admin\Category;

use App\Blog\CategoryRepository;
use App\Web\Shared\AdminLayout;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private CategoryRepository $categories,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/index', ['categories' => $this->categories->findAll()]);
    }
}
