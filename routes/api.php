<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CheckController;
use App\Http\Controllers\Common\AppFileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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

    Route::prefix('checks')->name('checks.')->group(function () {
        Route::match(['get', 'post'], '/', [CheckController::class, 'index'])->name('index');
        Route::post('/deposit', [CheckController::class, 'deposit'])->name('deposit');
        Route::match(['get', 'post'], '/{check}/show', [CheckController::class, 'show'])->name('show');
        Route::delete('/{check}/destroy', [CheckController::class, 'destroy'])->name('destroy');
        Route::post('/{check}/updateStatus', [CheckController::class, 'updateStatus'])
            ->name('update-status');
    });
});

Route::prefix('app_file')->name('app_file.')
    ->group(function () {
        Route::match(['get', 'post'], '/{appFile}/show', [AppFileController::class, 'show'])->name('show');
    });

Route::name('api.auth.')->prefix('auth')->group(function () {
    require __DIR__ . '/auth.php';

    Route::middleware('auth:sanctum')
        ->group(function () {
            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

            Route::match(
                ['get', 'post'],
                '/me',
                fn (Request $request) => response()->json($request->user()?->currentAccessToken())
            )->name('me');
        });
});
