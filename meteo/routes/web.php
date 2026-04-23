<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WeatherController::class, 'index'])->name('weather.index');
Route::get('/weather', [WeatherController::class, 'search'])->name('weather.search');
