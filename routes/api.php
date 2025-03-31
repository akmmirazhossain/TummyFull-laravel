<?php



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
//AUTH ROUTES
use App\Http\Controllers\Web\AuthController;

Route::get('/hashpass', [AuthController::class, 'hashpass']);



//MENU ROUTES
use App\Http\Controllers\MenuController;


Route::get('/menu', [MenuController::class, 'index']);

Route::get('/menu/{menuId}', [MenuController::class, 'getMenuById']);


// Route for MrdSettingController
use App\Http\Controllers\SettingController;

Route::get('/setting', [SettingController::class, 'serverSettings']);
Route::post('/mealbox-switch', [SettingController::class, 'mealboxSwitch']);


use App\Http\Controllers\PhoneVerificationController;

Route::post('/send-otp', [PhoneVerificationController::class, 'verifyPhoneNumber']);
Route::post('/verify-otp', [PhoneVerificationController::class, 'verifyOtp']);


use App\Http\Controllers\OrderController;

Route::post('/order-place', [OrderController::class, 'orderPlace']);
Route::post('/quantity-changer', [OrderController::class, 'quantityChanger']);
Route::post('/mealbox-status', [OrderController::class, 'mealboxStatApi']);


use App\Http\Controllers\OrderFoodSwapController;

Route::post('/order-food-swap', [OrderFoodSwapController::class, 'foodSwap']);

use App\Http\Controllers\UserController;

Route::get('/user-fetch', [UserController::class, 'userFetch']);
Route::post('/user-update', [UserController::class, 'userUpdate']);


use App\Http\Controllers\LogController;

Route::get('/mealbook', [LogController::class, 'mealBook']);


use App\Http\Controllers\ChefController;

Route::get('/orderlist-chef-now', [ChefController::class, 'orderListChefNow']);
Route::get('/chef-order-history', [ChefController::class, 'chefOrderHistory']);
Route::get('/chef-payment-history', [ChefController::class, 'chefPaymentHistory']);
// Route::get('/orderlist-chef-later', [ChefController::class, 'orderListChefLater']);
// Route::get('/orderlist-chef-test', [ChefController::class, 'orderListChefTest']);


use App\Http\Controllers\DeliveryController;

Route::get('/delivery-list', [DeliveryController::class, 'deliveryList']);
Route::post('/delivery-update', [DeliveryController::class, 'deliveryUpdate']);


use App\Http\Controllers\NotificationController;

Route::post('/notif-order-place', [NotificationController::class, 'notifOrderPlace']);
Route::get('/notif-get', [NotificationController::class, 'notifGet']);
Route::get('/notif-seen', [NotificationController::class, 'notifSeen']);


use App\Http\Controllers\SmsController;

Route::get('/sms-order-final-alert', [SmsController::class, 'smsOrderFinalAlert']);
Route::get('/sms-discount-new-user', [SmsController::class, 'smsDiscountNewUser']);
