<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->name('register')
        ->middleware('throttle:api-auth');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login')
        ->middleware('throttle:api-auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

Route::prefix('oauth')->middleware('throttle:api-oauth')->group(function () {
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
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])
            ->name('projects.index')
            ->middleware('throttle:api-reads');

        Route::get('/{project}', [ProjectController::class, 'show'])
            ->name('projects.detail')
            ->middleware('throttle:api-reads');

        Route::post('/', [ProjectController::class, 'store'])
            ->name('projects.create')
            ->middleware(['permission:create project', 'idempotent', 'throttle:api-mutations']);

        Route::put('/{project}', [ProjectController::class, 'update'])
            ->name('projects.update')
            ->middleware(['permission:update project', 'throttle:api-mutations']);

        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->name('projects.delete')
            ->middleware(['permission:delete project', 'throttle:api-mutations']);
    });

    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index'])
            ->name('tasks.index')
            ->middleware('throttle:api-reads');

        Route::get('/{task}', [TaskController::class, 'show'])
            ->name('tasks.detail')
            ->middleware('throttle:api-reads');

        Route::post('/', [TaskController::class, 'store'])
            ->name('tasks.create')
            ->middleware(['permission:create task', 'idempotent', 'throttle:api-mutations']);

        Route::put('/{task}', [TaskController::class, 'update'])
            ->name('tasks.update')
            ->middleware(['permission:update task detail', 'throttle:api-mutations']);

        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])
            ->name('tasks.status')
            ->middleware(['permission:update task status', 'throttle:api-mutations']);

        Route::delete('/{task}', [TaskController::class, 'destroy'])
            ->name('tasks.delete')
            ->middleware(['permission:delete task', 'throttle:api-mutations']);
    });

    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index'])
            ->name('comments.index')
            ->middleware('throttle:api-reads');

        Route::post('/', [CommentController::class, 'store'])
            ->name('comments.store')
            ->middleware(['permission:create comment', 'throttle:api-mutations']);
    });

    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy')
        ->middleware(['permission:delete comment', 'throttle:api-mutations']);
});
