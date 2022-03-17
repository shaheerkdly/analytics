<?php

use App\Http\Controllers\AnalyticController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/today/{user}', [AnalyticController::class, 'todayStats'])->name('todayStats');
Route::get('/previous-week/{user}', [AnalyticController::class, 'previousWeekStats'])->name('previousWeekStats');
Route::get('/previous-month/{user}', [AnalyticController::class, 'previousMonthStats'])->name('previousMonthStats');
Route::get('/previous-three-month/{user}', [AnalyticController::class, 'previousThreeMonthStats'])->name('previousThreeMonthStats');
Route::get('/all-time/{user}', [AnalyticController::class, 'allTimeStats'])->name('allTimeStats');

