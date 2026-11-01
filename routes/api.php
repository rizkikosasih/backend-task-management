<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

Route::prefix('oauth')->middleware('throttle:10,1')->group(function () {
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google|github')
        ->name('oauth.redirect');

    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google|github')
        ->name('oauth.callback');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{provider}/link', [SocialAuthController::class, 'link'])
            ->where('provider', 'google|github')
            ->name('oauth.link');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    /*
    |--------------------------
    | PROJECT MODULE
    |--------------------------
    */
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('projects.index');

        Route::get('/{project}', [ProjectController::class, 'show'])->name('projects.detail');

        Route::post('/', [ProjectController::class, 'store'])
            ->name('projects.create')
            ->middleware(['permission:create project', 'idempotent']);

        Route::put('/{project}', [ProjectController::class, 'update'])
            ->name('projects.update')
            ->middleware('permission:update project');

        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->name('projects.delete')
            ->middleware('permission:delete project');
    });

    /*
    |--------------------------
    | TASK MODULE
    |--------------------------
    */
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');

        Route::get('/{task}', [TaskController::class, 'show'])->name('tasks.detail');

        Route::post('/', [TaskController::class, 'store'])
            ->name('tasks.create')
            ->middleware(['permission:create task', 'idempotent']);

        Route::put('/{task}', [TaskController::class, 'update'])
            ->name('tasks.update')
            ->middleware('permission:update task detail');

        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])
            ->name('tasks.status')
            ->middleware('permission:update task status');

        Route::delete('/{task}', [TaskController::class, 'destroy'])
            ->name('tasks.delete')
            ->middleware('permission:delete task');
    });

    /*
    |--------------------------
    | COMMENTS MODULE
    |--------------------------
    */
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('comments.index');

        Route::post('/', [CommentController::class, 'store'])
            ->name('comments.store')
            ->middleware('permission:create comment');
    });

    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy')
        ->middleware('permission:delete comment');
});
