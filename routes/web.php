<?php

use Illuminate\Support\Facades\Route;


//AUTH CHECKERS
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/auth/checklogin', [AuthController::class, 'checklogin']);


//ADMIN ROUTES
use App\Http\Controllers\AdminController;

Route::get('/admin', [AdminController::class, 'index']);

Route::post('/recharge-wallet', [AdminController::class, 'rechargeWallet']);
