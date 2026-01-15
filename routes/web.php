<?php

use App\Services\WeatherService;
use Illuminate\Support\Facades\Route;

// Esta rota faz o Laravel carregar o welcome.blade.php na página inicial
Route::get('/', function () {
    return view('welcome');
});
Route::get('/clima/{city}', function (string $city, WeatherService $service) {
    $dados = $service->getWeather($city);
    
    if (!$dados) {
        return response()->json(['erro' => 'Cidade não encontrada'], 404);
    }

    return $dados;
});