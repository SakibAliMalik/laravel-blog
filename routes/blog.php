<?php

use Illuminate\Support\Facades\Route;
use SakibAliMalik\Blog\Controllers\BlogController;
use SakibAliMalik\Blog\Controllers\Admin\PostController as AdminPostController;
use SakibAliMalik\Blog\Controllers\Admin\CategoryController as AdminCategoryController;
use SakibAliMalik\Blog\Controllers\Admin\TagController as AdminTagController;
use SakibAliMalik\Blog\Controllers\Admin\MediaController as AdminMediaController;

// Public routes
Route::prefix('blog')->group(function () {
    Route::get('/categories', [BlogController::class, 'categories']);
    Route::get('/listing', [BlogController::class, 'blogs']);
    Route::get('/recent', [BlogController::class, 'recentBlogs']);
    Route::get('/tags', [BlogController::class, 'tags']);
    Route::get('/show/{slug}', [BlogController::class, 'blogShow']);
    Route::post('/views', [BlogController::class, 'storeView']);
});

// Admin routes
Route::middleware(config('blog.admin_middleware', ['auth:sanctum']))
    ->prefix('admin/blog')
    ->as('admin.blog.')
    ->group(function () {
        // Posts
        Route::get('/posts', [AdminPostController::class, 'index']);
        Route::post('/posts', [AdminPostController::class, 'store']);
        Route::get('/posts/statistics', [AdminPostController::class, 'statistics']);
        Route::post('/posts/bulk-action', [AdminPostController::class, 'bulkAction']);
        Route::get('/posts/{id}', [AdminPostController::class, 'show']);
        Route::put('/posts/{id}', [AdminPostController::class, 'update']);
        Route::delete('/posts/{id}', [AdminPostController::class, 'destroy']);
        Route::post('/posts/{id}/duplicate', [AdminPostController::class, 'duplicate']);

        // Categories
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::get('/categories/{id}', [AdminCategoryController::class, 'show']);
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        // Tags
        Route::get('/tags', [AdminTagController::class, 'index']);
        Route::post('/tags', [AdminTagController::class, 'store']);
        Route::get('/tags/popular', [AdminTagController::class, 'popular']);
        Route::get('/tags/{id}', [AdminTagController::class, 'show']);
        Route::put('/tags/{id}', [AdminTagController::class, 'update']);
        Route::delete('/tags/{id}', [AdminTagController::class, 'destroy']);

        // Media
        Route::get('/media', [AdminMediaController::class, 'index']);
        Route::post('/media/upload', [AdminMediaController::class, 'upload']);
        Route::put('/media/{id}', [AdminMediaController::class, 'update']);
        Route::delete('/media/{id}', [AdminMediaController::class, 'destroy']);
    });
