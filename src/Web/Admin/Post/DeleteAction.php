<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Blog\PostRepository;
use App\Blog\PostWorkflow;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\CurrentRoute;

final readonly class DeleteAction
{
    public function __construct(
        private CurrentRoute $currentRoute,
        private PostRepository $posts,
        private PostWorkflow $workflow,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $post = $this->posts->findById((int) $this->currentRoute->getArgument('id'));
        if ($post === null) {
            return $this->errorPage->notFound('Post não encontrado.');
        }
        if (!$this->workflow->canDelete($post)) {
            return $this->errorPage->forbidden('Você não pode excluir este post.');
        }

        $this->posts->delete($post->id);

        return $this->redirector->withFlash('success', 'Post “' . $post->title . '” excluído.', 'admin/post/index');
    }
}
