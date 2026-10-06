<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Blog\PostRepository;
use App\Blog\PostWorkflow;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * POST /admin/posts/{id}/{submit|approve|reject|unpublish}
 */
final readonly class TransitionAction
{
    public function __construct(
        private CurrentRoute $currentRoute,
        private PostRepository $posts,
        private PostWorkflow $workflow,
        private Redirector $redirector,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $post = $this->posts->findById((int) $this->currentRoute->getArgument('id'));
        if ($post === null) {
            return $this->errorPage->notFound('Post não encontrado.');
        }

        $transition = (string) $this->currentRoute->getArgument('transition');
        $body = (array) $request->getParsedBody();
        $returnTo = ($body['return'] ?? '') === 'review' ? 'admin/review' : null;

        try {
            $message = $this->workflow->apply($transition, $post, isset($body['note']) ? (string) $body['note'] : null);
        } catch (DomainException $e) {
            return $this->redirector->withFlash('error', $e->getMessage(), 'admin/post/view', ['id' => $post->id]);
        }

        return $returnTo !== null
            ? $this->redirector->withFlash('success', $message, $returnTo)
            : $this->redirector->withFlash('success', $message, 'admin/post/view', ['id' => $post->id]);
    }
}
