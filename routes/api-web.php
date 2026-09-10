<?php

use App\Http\Controllers\Api\PostOverlapController;
use App\Http\Controllers\Api\VegetableAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('api')
    ->name('api.')
    ->group(function () {
        Route::get('/vegetables/{vegetable}/slot-summary', [VegetableAvailabilityController::class, 'slotSummary'])
            ->name('vegetables.slot-summary');

        Route::middleware(['auth'])->get('/posts/overlap', PostOverlapController::class)
            ->name('api.posts.overlap');
    });
