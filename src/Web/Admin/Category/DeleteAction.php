<?php

declare(strict_types=1);

namespace App\Web\Admin\Category;

use App\Blog\CategoryRepository;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;

final readonly class DeleteAction
{
    public function __construct(
        private CurrentRoute $currentRoute,
        private CategoryRepository $categories,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $category = $this->categories->findById((int) $this->currentRoute->getArgument('id'));
        if ($category === null) {
            return $this->errorPage->notFound('Categoria não encontrada.');
        }

        // A FK usa ON DELETE SET NULL: os posts da categoria ficam "sem categoria".
        $this->categories->delete($category->id);

        return $this->redirector->withFlash('success', 'Categoria “' . $category->name . '” excluída.', 'admin/category/index');
    }
}
