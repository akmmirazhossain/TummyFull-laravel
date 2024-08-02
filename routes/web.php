<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


use App\Http\Controllers\AuthController;

// Route::get('/main', 'AuthController@index');
// Route::post('/auth/checklogin', 'AuthController@checklogin');
// Route::get('main/successlogin', 'AuthController@successlogin');
// Route::get('main/logout', 'AuthController@logout');



// Route::get('/auth/checklogin', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/auth/checklogin', [AuthController::class, 'checklogin']);
Route::get('/auth/successlogin', [AuthController::class, 'successlogin']);


require __DIR__ . '/auth.php';
