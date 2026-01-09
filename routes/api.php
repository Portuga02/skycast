<?php

use App\Services\WeatherService;
use Illuminate\Support\Facades\Route;

// Agora a rota está protegida no grupo de API
Route::get('/clima/{city}', function (string $city, WeatherService $service) {
    $dados = $service->getWeather($city);
    
    if (!$dados) {
        return response()->json(['erro' => 'Cidade não encontrada'], 404);
    }

    return response()->json($dados);
});