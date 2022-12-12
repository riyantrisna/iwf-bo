<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegionsController;
use App\Http\Controllers\EventController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::middleware(['guest'])->group(function () {
    Route::get('/', function(){
        return redirect('/login');
    });
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout']);

Route::middleware(['admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/get-region-by-provinces/{id}', [RegionsController::class, 'getRegionsByProvincesId']);
    Route::get('/get-occupation-type/{key}', [UserController::class, 'getOccupationType']);


    Route::prefix('user')->group(function(){
        Route::get('/', [UserController::class, 'index']);
        Route::post('/data', [UserController::class, 'data']);
        Route::get('/add-view', [UserController::class, 'addView']);
        Route::post('/add', [UserController::class, 'add']);
        Route::get('/edit-view/{id}', [UserController::class, 'editView']);
        Route::post('/edit', [UserController::class, 'edit']);
        Route::get('/detail/{id}', [UserController::class, 'detail']);
        Route::get('/delete/{id}', [UserController::class, 'delete']);
        Route::get('/export', [UserController::class, 'userExport']);
    });

    Route::prefix('event')->group(function(){
        Route::get('/', [EventController::class, 'index']);
        Route::post('/data', [EventController::class, 'data']);
        Route::get('/add-view', [EventController::class, 'addView']);
        Route::post('/add', [EventController::class, 'add']);
        Route::get('/edit-view/{id}', [EventController::class, 'editView']);
        Route::post('/edit', [EventController::class, 'edit']);
        Route::get('/detail/{id}', [EventController::class, 'detail']);
        Route::get('/delete/{id}', [EventController::class, 'delete']);
        Route::get('/export', [EventController::class, 'userExport']);
    });
});

