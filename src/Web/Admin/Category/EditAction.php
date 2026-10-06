<?php

declare(strict_types=1);

namespace App\Web\Admin\Category;

use App\Blog\CategoryRepository;
use App\Shared\Slugger;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class EditAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private FormHydrator $formHydrator,
        private CurrentRoute $currentRoute,
        private CategoryRepository $categories,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->currentRoute->getArgument('id');
        $category = $id !== null ? $this->categories->findById((int) $id) : null;
        if ($id !== null && $category === null) {
            return $this->errorPage->notFound('Categoria não encontrada.');
        }

        $form = $category !== null ? CategoryForm::fromCategory($category) : new CategoryForm();

        if ($this->formHydrator->populateFromPost($form, $request)) {
            $this->formHydrator->validate($form);

            if ($form->slug !== null && $this->categories->slugExists($form->slug, $category?->id)) {
                $form->addError('Já existe uma categoria com este slug.', ['slug']);
            }

            if ($form->isValid()) {
                $data = [
                    'name' => trim($form->name),
                    'slug' => $form->slug ?? Slugger::unique(
                        $form->name,
                        fn(string $slug): bool => $this->categories->slugExists($slug, $category?->id),
                        120,
                    ),
                    'description' => $form->description !== null ? trim($form->description) : null,
                ];

                if ($category === null) {
                    $this->categories->insert($data);
                    $message = 'Categoria criada.';
                } else {
                    $this->categories->update($category->id, $data);
                    $message = 'Categoria atualizada.';
                }

                return $this->redirector->withFlash('success', $message, 'admin/category/index');
            }
        }

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/form', ['form' => $form, 'category' => $category]);
    }
}
