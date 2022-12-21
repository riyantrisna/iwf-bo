<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RegionsController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LandingpageController;
use App\Http\Controllers\GroupEventController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SpeakerController;
use App\Http\Controllers\SponsorController;

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
        Route::get('/export', [EventController::class, 'eventExport']);
        Route::get('/event-user-export/{id}', [EventController::class, 'eventUserExport']);
        Route::get('/all-event-user-export', [EventController::class, 'allEventUserExport']);
    });

    Route::prefix('post')->group(function(){
        Route::get('/', [PostController::class, 'index']);
        Route::post('/data', [PostController::class, 'data']);
        Route::get('/add-view', [PostController::class, 'addView']);
        Route::post('/add', [PostController::class, 'add']);
        Route::get('/edit-view/{id}', [PostController::class, 'editView']);
        Route::post('/edit', [PostController::class, 'edit']);
        Route::get('/detail/{id}', [PostController::class, 'detail']);
        Route::get('/delete/{id}', [PostController::class, 'delete']);
    });

    Route::prefix('event-group')->group(function(){
        Route::get('/', [GroupEventController::class, 'index']);
        Route::post('/data', [GroupEventController::class, 'data']);
        Route::get('/add-view', [GroupEventController::class, 'addView']);
        Route::post('/add', [GroupEventController::class, 'add']);
        Route::get('/edit-view/{id}', [GroupEventController::class, 'editView']);
        Route::post('/edit', [GroupEventController::class, 'edit']);
        Route::get('/detail/{id}', [GroupEventController::class, 'detail']);
        Route::get('/delete/{id}', [GroupEventController::class, 'delete']);
    });

    Route::prefix('setting')->group(function(){
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/data', [SettingController::class, 'data']);
        Route::get('/add-view', [SettingController::class, 'addView']);
        Route::post('/add', [SettingController::class, 'add']);
        Route::get('/edit-view/{id}', [SettingController::class, 'editView']);
        Route::post('/edit', [SettingController::class, 'edit']);
        Route::get('/detail/{id}', [SettingController::class, 'detail']);
        Route::get('/delete/{id}', [SettingController::class, 'delete']);
    });

    Route::prefix('speaker')->group(function(){
        Route::get('/', [SpeakerController::class, 'index']);
        Route::post('/data', [SpeakerController::class, 'data']);
        Route::get('/add-view', [SpeakerController::class, 'addView']);
        Route::post('/add', [SpeakerController::class, 'add']);
        Route::get('/edit-view/{id}', [SpeakerController::class, 'editView']);
        Route::post('/edit', [SpeakerController::class, 'edit']);
        Route::get('/detail/{id}', [SpeakerController::class, 'detail']);
        Route::get('/delete/{id}', [SpeakerController::class, 'delete']);
    });

    Route::prefix('sponsor')->group(function(){
        Route::get('/', [SponsorController::class, 'index']);
        Route::post('/data', [SponsorController::class, 'data']);
        Route::get('/add-view', [SponsorController::class, 'addView']);
        Route::post('/add', [SponsorController::class, 'add']);
        Route::get('/edit-view/{id}', [SponsorController::class, 'editView']);
        Route::post('/edit', [SponsorController::class, 'edit']);
        Route::get('/detail/{id}', [SponsorController::class, 'detail']);
        Route::get('/delete/{id}', [SponsorController::class, 'delete']);
    });

    Route::prefix('landingpage')->group(function(){
        Route::get('/', [LandingpageController::class, 'index']);
        Route::post('/edit', [LandingpageController::class, 'edit']);
    });
});

