<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

Route::get('/clima/coordenadas', [WeatherController::class, 'climaPorCoordenadas']);

Route::get('/map-tile/{layer}/{z}/{x}/{y}', [WeatherController::class, 'getMapTile']);

Route::get('/cidades/busca/{query}', [WeatherController::class, 'search'])->where('query', '.*');

Route::get('/clima/{city}', [WeatherController::class, 'getClima']);