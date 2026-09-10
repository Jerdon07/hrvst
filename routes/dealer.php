<?php

use App\Http\Controllers\Dealer\DashboardController;
use App\Http\Controllers\Dealer\Schedule\DemandController;
use App\Http\Controllers\Dealer\Schedule\PostItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'dealer'])->prefix('dealer')->name('dealer.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('demands')->name('demands.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::get('/archived', [DemandController::class, 'archived'])->name('archived');
        Route::get('/create', [DemandController::class, 'create'])->name('create');
        Route::post('/', [DemandController::class, 'store'])->name('store');
        Route::get('/{post}', [DemandController::class, 'show'])->name('show');
        Route::get('/{post}/edit', [DemandController::class, 'edit'])->name('edit');
        Route::put('/{post}', [DemandController::class, 'update'])->name('update');
        Route::delete('/{post}', [DemandController::class, 'destroy'])->name('destroy');

        Route::prefix('items')->name('items.')->group(function () {
            Route::post('/{postItem}/fulfill', [PostItemController::class, 'fulfill'])->name('fulfill');
            Route::post('/{postItem}/expire', [PostItemController::class, 'expire'])->name('expire');
        });
    });
});
