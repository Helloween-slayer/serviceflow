<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkerProfileController;
use App\Http\Controllers\WorkerReviewController;
use App\Http\Controllers\ClientReviewController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Worker\WithdrawalController as WorkerWithdrawalController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\MessageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ========== ПУБЛІЧНІ ==========
Route::get('/', function () {
    return redirect()->route('orders.index');
});

// ========== ПУБЛІЧНИЙ ПРОФІЛЬ ВОРКЕРА (доступен всем) ==========
Route::get('/worker/{userId}/profile', [WorkerProfileController::class, 'show'])->name('worker.profile.show');

// ========== ДАШБОРД ==========
Route::get('/dashboard', function () {
    return redirect()->route('orders.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// ========== ПРОФІЛЬ ==========
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ========== ТЕЛЕГРАМ ==========
Route::middleware(['auth'])->group(function () {
    Route::post('/telegram/connect', [TelegramController::class, 'connect'])->name('telegram.connect');
    Route::delete('/telegram/disconnect', [TelegramController::class, 'disconnect'])->name('telegram.disconnect');
    Route::patch('/telegram/notifications', [TelegramController::class, 'toggleNotifications'])->name('telegram.notifications');
    // Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

    Route::get('/orders/{order}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/orders/{order}/messages', [MessageController::class, 'store'])->name('messages.store');
});

// ========== ПУБЛІЧНІ ЗАЯВКИ (ДЛЯ ВСІХ) ==========
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

// ========== КЛІЄНТСЬКІ ЗАЯВКИ ==========
Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Client/Dashboard');
    })->name('dashboard');

    Route::get('/orders', [OrderController::class, 'clientOrders'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Відгуки
    Route::get('/reviews/create/{order}', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/reviews', [ClientReviewController::class, 'index'])->name('reviews.index');
});

// ========== ВИКОНАВЕЦЬ ==========
Route::middleware(['auth', 'role:worker'])->prefix('worker')->name('worker.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Worker/Dashboard');
    })->name('dashboard');

    Route::get('/orders', [OrderController::class, 'workerOrders'])->name('orders.index');
    Route::put('/orders/{order}/take', [OrderController::class, 'takeOrder'])->name('orders.take');
    Route::put('/orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::put('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/profile/edit', [WorkerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [WorkerProfileController::class, 'update'])->name('profile.update');

    Route::get('/reviews', [WorkerReviewController::class, 'index'])->name('reviews.index');

    Route::get('/balance', [WorkerWithdrawalController::class, 'index'])->name('balance.index');
    Route::post('/balance/withdraw', [WorkerWithdrawalController::class, 'store'])->name('balance.withdraw');
});

// ========== АДМІН ==========
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [OrderController::class, 'adminDashboard'])->name('dashboard');
    Route::get('/orders', [OrderController::class, 'adminOrders'])->name('orders.index');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::put('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'update'])->name('withdrawals.update');
});

    // Поповнення балансу
    Route::middleware(['auth'])->prefix('payment')->name('payment.')->group(function () {
        Route::post('/deposit', [PaymentController::class, 'deposit'])->name('deposit');
    });

    // Callback від LiqPay (публічний)
    Route::post('/liqpay/callback', [PaymentController::class, 'callback'])->name('liqpay.callback');


require __DIR__ . '/auth.php';
