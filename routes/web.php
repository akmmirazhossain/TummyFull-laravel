<?php

use Illuminate\Support\Facades\Route;



//TESTING ROUTES
use App\Http\Controllers\TesterController;

Route::get('/testy-dalbhath', [TesterController::class, 'testyDalbhath']);



//AUTH CHECKERS
use App\Http\Controllers\Web\AuthController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/auth/checklogin', [AuthController::class, 'checklogin']);



//ANALYTICS 

use App\Http\Controllers\Web\AdminAnalyticsController;

Route::get('/dashboard', [AdminAnalyticsController::class, 'dashboard'])->name('dashboard');
Route::get('/order_list', [AdminAnalyticsController::class, 'order_list'])->name('order_list');

//CHART ROUTES


//WALLET ROUTES 
use App\Http\Controllers\Web\AdminWalletController;

Route::get('/wallet-recharge', [AdminWalletController::class, 'walletRecharge'])->name('wallet-recharge');
Route::get('/wallet-search-user', [AdminWalletController::class, 'walletSearchUser'])->name('wallet-search-user');
Route::get('/wallet-recharge-history', [AdminWalletController::class, 'walletRechargeHistory'])->name('wallet-recharge-history');

Route::post('/wallet-recharge-confirm', [AdminWalletController::class, 'walletRechargeConfirm'])->name('wallet-recharge-confirm');


//CUSTOMER ROUTES
use App\Http\Controllers\Web\AdminCustomerController;

Route::post('/customer_list', [AdminCustomerController::class, 'customer_list'])->name('customer_list');

//USER ROUTES
use App\Http\Controllers\Web\AdminUserController;

Route::get('/user_list', [AdminUserController::class, 'user_list'])->name('user_list');
Route::get('/user_list/{id}', [AdminUserController::class, 'show'])->name('user.show');


//CHEF VIEWS
use App\Http\Controllers\Web\AdminChefController;

Route::get('/chef-list', [AdminChefController::class, 'chef_list'])->name('chef-list');
Route::get('/chef-payment-list', [AdminChefController::class, 'chef_payment_list'])->name('chef-payment-list');
Route::post('/chef-pay', [AdminChefController::class, 'chef_pay'])->name('chef-pay');
Route::get('/chef-payment-history', [AdminChefController::class, 'chef_payment_history'])->name('chef-payment-history');


//ADMIN NOTIF ROUTES
use App\Http\Controllers\Web\AdminNotifController;

Route::get('/notif-list', [AdminNotifController::class, 'notif_list'])->name('notif-list');
