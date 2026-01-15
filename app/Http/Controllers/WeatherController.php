<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

   public function show($city)
{
    $decodedCity = urldecode($city);
    $data = $this->weatherService->getWeather($decodedCity);
    return $data ? response()->json($data) : response()->json(['message' => 'Erro'], 404);
}

public function search($query)
{
    // Este método deve chamar a função searchCities que criamos no seu Service
    $cities = $this->weatherService->searchCities(urldecode($query));
    return response()->json($cities);
}
}
