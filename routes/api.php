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
Route::get('/setting', [SettingController::class, 'index']);


use App\Http\Controllers\PhoneVerificationController;

Route::post('/send-otp', [PhoneVerificationController::class, 'verifyPhoneNumber']);
Route::post('/verify-otp', [PhoneVerificationController::class, 'verifyOtp']);

use App\Http\Controllers\SmsTestController;

Route::get('/test-otp', [SmsTestController::class, 'sendSms']);
