<?php

use App\Http\Controllers\Api\PostOverlapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('api')
    ->name('api.')
    ->group(function () {
        Route::get('/posts/overlap', PostOverlapController::class)
            ->name('posts.overlap');
    });
