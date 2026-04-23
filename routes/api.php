<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

// 1. GPS Automático
Route::get('/clima/coordenadas', [WeatherController::class, 'climaPorCoordenadas']);

// 2. Busca e Autocomplete
Route::get('/cidades/busca/{query}', [WeatherController::class, 'search'])->where('query', '.*');
Route::get('/clima/{city}', [WeatherController::class, 'getClima']);

// 3. CAMADAS DO MAPA (O segredo dos botões)
// Ajustei para aceitar o .png no final, que é como o Leaflet pede
Route::get('/mapa/camada/{layer}/{z}/{x}/{y}.png', [WeatherController::class, 'getMapTile']);