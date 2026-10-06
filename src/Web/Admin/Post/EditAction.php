<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Auth\Rbac\Permission;
use App\Blog\CategoryRepository;
use App\Blog\Post;
use App\Blog\PostRepository;
use App\Blog\PostService;
use App\Blog\PostStatus;
use App\Blog\PostWorkflow;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Criação (`/admin/posts/novo`) e edição (`/admin/posts/{id}/editar`) de posts.
 */
final readonly class EditAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private FormHydrator $formHydrator,
        private CurrentRoute $currentRoute,
        private CurrentUser $currentUser,
        private PostRepository $posts,
        private CategoryRepository $categories,
        private PostService $postService,
        private PostWorkflow $workflow,
        private Redirector $redirector,
        private FlashInterface $flash,
        private ErrorPage $errorPage,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->currentRoute->getArgument('id');
        $post = null;

        if ($id !== null) {
            $post = $this->posts->findById((int) $id);
            if ($post === null) {
                return $this->errorPage->notFound('Post não encontrado.');
            }
            if (!$this->workflow->canEdit($post)) {
                return $this->errorPage->forbidden(
                    $post->status === PostStatus::Pending
                        ? 'Este post está em revisão e não pode ser editado pelo autor agora.'
                        : 'Você não tem permissão para editar este post.',
                );
            }
        }

        $form = $post !== null ? PostForm::fromPost($post) : new PostForm();

        if ($this->formHydrator->populateFromPost($form, $request)) {
            $this->formHydrator->validate($form);
            $this->postService->validate($form, $post);

            if ($form->isValid()) {
                return $this->save($form, $post);
            }
        }

        return $this->viewRenderer
            ->withLayout(AdminLayout::PATH)
            ->render(__DIR__ . '/form', [
                'form' => $form,
                'post' => $post,
                'categories' => $this->categories->options(),
                'canSubmit' => $post === null || $this->workflow->canSubmit($post),
                'canPublish' => $this->currentUser->can(Permission::POST_PUBLISH_ANY)
                    && ($post === null || $post->status !== PostStatus::Published),
            ]);
    }

    private function save(PostForm $form, ?Post $post): ResponseInterface
    {
        $id = $this->postService->save($form, $post, (int) $this->currentUser->getId());
        $message = $post === null ? 'Rascunho criado.' : 'Alterações salvas.';

        // Botões "enviar para revisão" / "publicar agora" aplicam a transição logo após salvar.
        $intent = match ($form->intent) {
            'submit' => PostWorkflow::SUBMIT,
            'publish' => PostWorkflow::APPROVE,
            default => null,
        };

        if ($intent !== null) {
            $fresh = $this->posts->findById($id);
            if ($fresh !== null && $this->workflow->can($intent, $fresh)) {
                $message = $this->workflow->apply($intent, $fresh);
            }
        }

        $this->flash->set('success', $message);
        return $this->redirector->toRoute('admin/post/view', ['id' => $id]);
    }
}
