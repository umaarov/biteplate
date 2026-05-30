<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Billing\BillingDesk;
use App\Livewire\Floor\FloorBoard;
use App\Livewire\History\HistoryDashboard;
use App\Livewire\Kitchen\KitchenBoard;
use App\Livewire\Orders\OrderBuilder;
use App\Livewire\Reservations\ReservationBook;
use App\Livewire\Staff\StaffDirectory;
use Illuminate\Support\Facades\Route;

// --- Authentication -------------------------------------------------------
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login/dev', [AuthController::class, 'devLogin'])->name('login.dev');
Route::get('/auth/keycloak', [AuthController::class, 'keycloakRedirect'])->name('auth.keycloak');
Route::get('/auth/keycloak/callback', [AuthController::class, 'keycloakCallback'])->name('auth.keycloak.callback');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- POS (staff only) -----------------------------------------------------
Route::middleware('staff')->group(function (): void {
    Route::redirect('/', '/floor');

    Route::get('/floor', FloorBoard::class)->middleware('permission:view_floor')->name('floor');
    Route::get('/order/{table}', OrderBuilder::class)->middleware('permission:take_order')->name('order');
    Route::get('/kitchen', KitchenBoard::class)->middleware('permission:view_kitchen_queue')->name('kitchen');
    Route::get('/billing', BillingDesk::class)->middleware('permission:view_billing')->name('billing');
    Route::get('/reservations', ReservationBook::class)->middleware('permission:view_floor')->name('reservations');
    Route::get('/history', HistoryDashboard::class)->middleware('permission:view_reports')->name('history');
    Route::get('/staff', StaffDirectory::class)->middleware('permission:manage_staff')->name('staff');
});
