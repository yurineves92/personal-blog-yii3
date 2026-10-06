<?php

declare(strict_types=1);

use App\Auth\Rbac\Permission;
use App\Web\Admin;
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

            // Configurações do site (textos da landing page)
            Route::methods($getPost, '/configuracoes')
                ->middleware(AccessMiddleware::permission(Permission::SETTINGS_MANAGE))
                ->action(Admin\Settings\Action::class)
                ->name('admin/settings'),
        ),
];
