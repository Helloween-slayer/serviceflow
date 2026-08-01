<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ========== ПУБЛІЧНІ ==========
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ========== ДАШБОРД ==========
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ========== ПРОФІЛЬ ==========
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========== ПУБЛІЧНІ ЗАЯВКИ (ДЛЯ ВСІХ) ==========
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

// ========== КЛІЄНТСЬКІ ЗАЯВКИ ==========
Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    // Дашборд клиента
    Route::get('/dashboard', function () {
        return Inertia::render('Client/Dashboard');})->name('dashboard');
    // Заявки
    Route::get('/orders', [OrderController::class, 'clientOrders'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
});

// ========== ВИКОНАВЕЦЬ — ЗАГОТОВКА ==========
Route::middleware(['auth', 'role:worker'])->prefix('worker')->name('worker.')->group(function () {
    // Дашборд виконавця
    Route::get('/dashboard', function () {
        return Inertia::render('Worker/Dashboard');})->name('dashboard');
    // Заявки
    Route::get('/orders', [OrderController::class, 'myWorkerOrders'])->name('orders.index');
    Route::put('/orders/{order}/take', [OrderController::class, 'takeOrder'])->name('orders.take');
    Route::put('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
});

// ========== АДМІН — ЗАГОТОВКА ==========
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [OrderController::class, 'adminDashboard'])->name('dashboard');
    Route::get('/orders', [OrderController::class, 'adminIndex'])->name('orders.index');
});

require __DIR__.'/auth.php';
