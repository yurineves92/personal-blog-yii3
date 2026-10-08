<?php

declare(strict_types=1);

namespace App\Web\Admin\Post;

use App\Auth\Rbac\Permission;
use App\Blog\CategoryRepository;
use App\Blog\CoverUploader;
use App\Blog\Post;
use App\Blog\PostRepository;
use App\Blog\PostService;
use App\Blog\PostStatus;
use App\Blog\PostWorkflow;
use App\Web\Shared\AdminLayout;
use App\Web\Shared\ErrorPage;
use App\Web\Shared\Redirector;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
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
        private CoverUploader $covers,
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
                $this->applyCover($form, $request);
            }
            if ($form->isValid()) {
                if ($post?->coverUrl !== $form->coverUrl) {
                    $this->covers->delete($post?->coverUrl); // capa antiga enviada pelo painel
                }
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

    /**
     * Upload de capa tem prioridade sobre o campo de URL; o checkbox "remover" limpa a capa.
     */
    private function applyCover(PostForm $form, ServerRequestInterface $request): void
    {
        $file = $request->getUploadedFiles()[$form->getFormName()]['coverFile'] ?? null;

        if ($file instanceof UploadedFileInterface && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $form->coverUrl = $this->covers->store($file);
            } catch (DomainException $e) {
                $form->addError($e->getMessage(), ['coverUrl']);
            }
        } elseif ($form->removeCover) {
            $form->coverUrl = null;
        }
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
