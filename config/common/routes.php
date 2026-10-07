<?php

declare(strict_types=1);

use App\Auth\Rbac\Permission;
use App\Web\Admin;
use App\Web\Api;
use App\Web\Shared\Middleware\AccessMiddleware;
use App\Web\Site;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

$getPost = [Method::GET, Method::POST];

return [
    // ---------------------------------------------------------------- Site público
    Route::get('/')->action(Site\Landing\Action::class)->name('home'),
    Route::get('/blog')->action(Site\Blog\IndexAction::class)->name('blog/index'),
    Route::get('/blog/categoria/{slug}')->action(Site\Blog\IndexAction::class)->name('blog/category'),
    Route::get('/blog/{slug}')->action(Site\Blog\PostAction::class)->name('blog/post'),

    // ---------------------------------------------------------------- Autenticação
    Route::methods($getPost, '/admin/login')->action(Admin\Auth\LoginAction::class)->name('admin/login'),
    Route::post('/admin/logout')->action(Admin\Auth\LogoutAction::class)->name('admin/logout'),

    // ---------------------------------------------------------------- API REST (token Bearer, sem CSRF)
    Group::create('/admin/api/v1')
        ->routes(
            Route::post('/auth/token')->action([Api\V1\AuthController::class, 'token'])->name('api/auth/token'),

            // Leitura pública (sem token): só conteúdo publicado, com CORS liberado.
            Group::create('/public')
                ->middleware(Api\CorsMiddleware::class)
                ->routes(
                    Route::methods([Method::GET, Method::OPTIONS], '/site')->action([Api\V1\PublicController::class, 'site'])->name('api/public/site'),
                    Route::methods([Method::GET, Method::OPTIONS], '/posts')->action([Api\V1\PublicController::class, 'posts'])->name('api/public/posts'),
                    Route::methods([Method::GET, Method::OPTIONS], '/posts/{slug:[a-z0-9-]+}')->action([Api\V1\PublicController::class, 'show'])->name('api/public/post'),
                    Route::methods([Method::GET, Method::OPTIONS], '/categories')->action([Api\V1\PublicController::class, 'categories'])->name('api/public/categories'),
                ),

            Group::create()
                ->middleware(Api\ApiAuthMiddleware::class)
                ->routes(
                    Route::get('/me')->action([Api\V1\AuthController::class, 'me'])->name('api/me'),

                    Route::get('/posts')->action([Api\V1\PostController::class, 'index'])->name('api/post/index'),
                    Route::post('/posts')->action([Api\V1\PostController::class, 'create'])->name('api/post/create'),
                    Route::get('/posts/{id:\d+}')->action([Api\V1\PostController::class, 'view'])->name('api/post/view'),
                    Route::methods([Method::PUT, Method::PATCH], '/posts/{id:\d+}')->action([Api\V1\PostController::class, 'update'])->name('api/post/update'),
                    Route::delete('/posts/{id:\d+}')->action([Api\V1\PostController::class, 'delete'])->name('api/post/delete'),
                    Route::post('/posts/{id:\d+}/{transition:submit|approve|reject|unpublish}')
                        ->action([Api\V1\PostController::class, 'transition'])
                        ->name('api/post/transition'),

                    Route::get('/categories')->action([Api\V1\CategoryController::class, 'index'])->name('api/category/index'),
                    Route::post('/categories')->action([Api\V1\CategoryController::class, 'create'])->name('api/category/create'),
                    Route::methods([Method::PUT, Method::PATCH], '/categories/{id:\d+}')->action([Api\V1\CategoryController::class, 'update'])->name('api/category/update'),
                    Route::delete('/categories/{id:\d+}')->action([Api\V1\CategoryController::class, 'delete'])->name('api/category/delete'),
                ),
            // Qualquer outro caminho da API: 404 em JSON.
            Route::methods([Method::GET, Method::POST, Method::PUT, Method::PATCH, Method::DELETE], '/{path:.*}')
                ->action([Api\V1\AuthController::class, 'notFound']),
        ),

    // ---------------------------------------------------------------- Painel CMS
    Group::create('/admin')
        ->middleware(AccessMiddleware::permission(Permission::CMS_ACCESS))
        ->routes(
            Route::get('')->action(Admin\Dashboard\Action::class)->name('admin/dashboard'),
            Route::methods($getPost, '/perfil')->action(Admin\Profile\Action::class)->name('admin/profile'),

            // Posts (as checagens finas por post ficam nas actions, via RBAC + regra de autoria)
            Route::get('/posts')->action(Admin\Post\IndexAction::class)->name('admin/post/index'),
            Route::methods($getPost, '/posts/novo')
                ->middleware(AccessMiddleware::permission(Permission::POST_CREATE))
                ->action(Admin\Post\EditAction::class)
                ->name('admin/post/create'),
            Route::get('/posts/{id:\d+}')->action(Admin\Post\ViewAction::class)->name('admin/post/view'),
            Route::methods($getPost, '/posts/{id:\d+}/editar')->action(Admin\Post\EditAction::class)->name('admin/post/update'),
            Route::post('/posts/{id:\d+}/excluir')->action(Admin\Post\DeleteAction::class)->name('admin/post/delete'),
            Route::post('/posts/{id:\d+}/{transition:submit|approve|reject|unpublish}')
                ->action(Admin\Post\TransitionAction::class)
                ->name('admin/post/transition'),
            Route::get('/revisao')
                ->middleware(AccessMiddleware::permission(Permission::POST_REVIEW))
                ->action(Admin\Post\ReviewQueueAction::class)
                ->name('admin/review'),

            // Categorias
            Group::create('/categorias')
                ->middleware(AccessMiddleware::permission(Permission::CATEGORY_MANAGE))
                ->routes(
                    Route::get('')->action(Admin\Category\IndexAction::class)->name('admin/category/index'),
                    Route::methods($getPost, '/nova')->action(Admin\Category\EditAction::class)->name('admin/category/create'),
                    Route::methods($getPost, '/{id:\d+}/editar')->action(Admin\Category\EditAction::class)->name('admin/category/update'),
                    Route::post('/{id:\d+}/excluir')->action(Admin\Category\DeleteAction::class)->name('admin/category/delete'),
                ),

            // Usuários
            Group::create('/usuarios')
                ->middleware(AccessMiddleware::permission(Permission::USER_MANAGE))
                ->routes(
                    Route::get('')->action(Admin\User\IndexAction::class)->name('admin/user/index'),
                    Route::methods($getPost, '/novo')->action(Admin\User\EditAction::class)->name('admin/user/create'),
                    Route::methods($getPost, '/{id:\d+}/editar')->action(Admin\User\EditAction::class)->name('admin/user/update'),
                    Route::post('/{id:\d+}/excluir')->action(Admin\User\DeleteAction::class)->name('admin/user/delete'),
                ),

            // Documentação da API (Swagger UI) e tokens do usuário logado
            Route::get('/api')->action(Admin\Api\DocsAction::class)->name('admin/api'),
            Route::get('/api/openapi.json')->action(Admin\Api\OpenApiAction::class)->name('admin/api/spec'),
            Route::post('/api/tokens')->action([Admin\Api\TokenAction::class, 'create'])->name('admin/api/token/create'),
            Route::post('/api/tokens/{id:\d+}/revogar')->action([Admin\Api\TokenAction::class, 'revoke'])->name('admin/api/token/revoke'),

            // Configurações do site (textos da landing page)
            Route::methods($getPost, '/configuracoes')
                ->middleware(AccessMiddleware::permission(Permission::SETTINGS_MANAGE))
                ->action(Admin\Settings\Action::class)
                ->name('admin/settings'),
        ),
];
