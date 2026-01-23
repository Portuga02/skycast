<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;

/*
|--------------------------------------------------------------------------
| API Routes - ORDEM CORRIGIDA
|--------------------------------------------------------------------------
*/

// 1. ROTA ESPECÍFICA (TEM QUE VIR PRIMEIRO!)
// Se vier depois, o Laravel confunde "coordenadas" com o nome de uma cidade.
Route::get('/clima/coordenadas', [WeatherController::class, 'climaPorCoordenadas']);

// 2. Rota para os Tiles do Mapa (Mapinhas Coloridos)
Route::get('/map-tile/{layer}/{z}/{x}/{y}', [WeatherController::class, 'getMapTile']);

// 3. Rota para Autocomplete
Route::get('/cidades/busca/{query}', [WeatherController::class, 'search'])->where('query', '.*');

// 4. ROTA GENÉRICA (DEIXE POR ÚLTIMO)
// Busca clima pelo nome da cidade (Ex: /clima/Recife)
Route::get('/clima/{city}', [WeatherController::class, 'getClima']);