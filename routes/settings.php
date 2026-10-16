<?php

use App\Http\Controllers\Settings\MaintenanceController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\EstablishmentController;
use App\Http\Controllers\Settings\SacCodeController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/establishment', [EstablishmentController::class, 'edit'])->name('establishment.edit');
    Route::patch('settings/establishment', [EstablishmentController::class, 'update'])->name('establishment.update');
    Route::get('settings/sac-codes', [SacCodeController::class, 'edit'])->name('sac-codes.edit');
    Route::patch('settings/sac-codes', [SacCodeController::class, 'update'])->name('sac-codes.update');
    Route::get('settings/maintenance', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
    Route::patch('settings/maintenance', [MaintenanceController::class, 'update'])->name('maintenance.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
