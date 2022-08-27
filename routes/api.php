<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::resource('customer', UserController::class)->only('index', 'show', 'store');

Route::get('/customer/{user}/transaction', [TransactionController::class, 'index']);
Route::post('customer/{user}/charge/{method}', [TransactionController::class, 'store'])->where(['method' => 'card']);
Route::match(['get', 'post'], '/payment/webhook',  [TransactionController::class, 'webhook'])->name('payment.webhook');

