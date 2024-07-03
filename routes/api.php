<?php

use App\Http\Controllers\ApiController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// use App\Http\Controllers\Basic;

// Route::get('basic', 'Basic@index');

// routes/api.php

Route::get('items', [ApiController::class, 'getItems']);

// routes/api.php

use App\Http\Controllers\MenuController;


Route::get('/menu', [MenuController::class, 'index']);

Route::get('/menu/{menuId}', [MenuController::class, 'getMenuById']);



// use Illuminate\Http\Request;
// Route::get('/mrdtest', [MrdTest::class, 'index']);

// Route for MrdSettingController
use App\Http\Controllers\SettingController;

Route::get('/setting', [SettingController::class, 'serverSettings']);
Route::post('/mealbox-switch', [SettingController::class, 'mealboxSwitch']);


use App\Http\Controllers\PhoneVerificationController;

Route::post('/send-otp', [PhoneVerificationController::class, 'verifyPhoneNumber']);
Route::post('/verify-otp', [PhoneVerificationController::class, 'verifyOtp']);




// use App\Http\Controllers\OrderAuto;

// Route::get('/orderauto', [OrderAuto::class, 'index']);
// Route::get('/item/{id}', [OrderAuto::class, 'show']);


use App\Http\Controllers\OrderController;

Route::post('/order-place', [OrderController::class, 'orderPlace']);
Route::post('/quantity-changer', [OrderController::class, 'quantityChanger']);




use App\Http\Controllers\UserController;

Route::get('/user-fetch', [UserController::class, 'userFetch']);
Route::post('/user-update', [UserController::class, 'userUpdate']);


use App\Http\Controllers\LogController;

Route::get('/mealbook', [LogController::class, 'mealBook']);


use App\Http\Controllers\ChefController;

Route::get('/orderlist-chef-now', [ChefController::class, 'orderListChefNow']);
Route::get('/orderlist-chef-later', [ChefController::class, 'orderListChefLater']);
Route::get('/orderlist-chef-test', [ChefController::class, 'orderListChefTest']);


use App\Http\Controllers\DeliveryController;

Route::get('/delivery', [DeliveryController::class, 'deliveryList']);
