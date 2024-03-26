<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::any('ping/{message?}', fn (?string $message = null) => ['message' => $message ?? 'pong'])
    ->where('message', '[a-zA-Z0-9\-\ ]{0,}')
    ->name('ping');

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

Route::middleware('auth:sanctum')->group(function () {
    Route::match(['get', 'post'], 'notification', NotificationController::class)->name('notifications');

    Route::prefix('account')->name('account.')->group(function () {
        Route::match(['get', 'post'], '/', [AccountController::class, 'index'])->name('index');
    });

    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::match(['get', 'post'], '/', [TransactionController::class, 'expenses'])->name('index');
        Route::post('/create', [TransactionController::class, 'newExpense'])->name('create');
    });

    Route::prefix('incomes')->name('incomes.')->group(function () {
        Route::match(['get', 'post'], '/', [TransactionController::class, 'incomes'])->name('index');
    });
});

Route::name('api.')->group(function () {
    require __DIR__ . '/auth.php';
});
