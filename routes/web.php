<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChangePinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\RegistrationRequestController;
use App\Http\Controllers\Shared\UserController;
use App\Http\Controllers\VegetableExportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/register', [RegistrationRequestController::class, 'create'])->name('register.create');
Route::post('/register', [RegistrationRequestController::class, 'store'])->name('register.store');

Route::get('/address/barangays', [AddressController::class, 'barangays'])->name('address.barangays');

/* ---------- change PIN (must be accessible before is_verified check) ---------- */

Route::middleware(['auth'])->group(function () {
    Route::get('change-pin', [ChangePinController::class, 'show'])->name('change-pin.show');
    Route::post('change-pin', [ChangePinController::class, 'update'])->name('change-pin.update');

    Route::post('onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

/* ---------- authenticated & verified ---------- */

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

/* ---------- Vegetable CSV Download ---------- */

// routes/web.php
Route::get('vegetables/{vegetable}/export', [VegetableExportController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('vegetables.export');

/* ---------- development only ---------- */

if (app()->environment('local', 'development')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('login/admin', function () {
            Auth::loginUsingId(1);

            return redirect()->route('dashboard');
        })->name('login.admin');

        Route::get('login/farmer', function () {
            Auth::loginUsingId(4);

            return redirect()->route('dashboard');
        })->name('login.farmer');

        Route::get('login/dealer', function () {
            Auth::loginUsingId(5);

            return redirect()->route('dashboard');
        })->name('login.dealer');
    });
}

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/farmer.php';
require __DIR__.'/dealer.php';
require __DIR__.'/shared.php';
require __DIR__.'/api-web.php';
require __DIR__.'/billing.php';