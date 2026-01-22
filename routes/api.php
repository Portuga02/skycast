<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

// Rota para o Clima Final
Route::get('/clima/coordenadas', [WeatherController::class, 'getByCoordinates']);
Route::get('/clima/{city}', [WeatherController::class, 'getClima']);


// Rota para as Sugestões (Autocomplete)
Route::get('/cidades/busca/{query}', [WeatherController::class, 'search'])->where('query', '.*');
Route::get('/map-tile/{z}/{x}/{y}', [WeatherController::class, 'getMapTile']);
// Rota para o Proxy de Imagens do Mapa