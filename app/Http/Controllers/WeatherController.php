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

    public function getClima($city)
    {
        try {
            $decodedCity = urldecode($city);
            $data = $this->weatherService->getForecastData($decodedCity);

            if ($data) {
                return response()->json($data, 200);
            }

            return response()->json(['error' => 'Not Found'], 404);
        } catch (\Exception $e) {
            // Isso vai imprimir o erro real no seu log se falhar
            \Log::error("Erro no Controller: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search($query)
    {
        $cities = $this->weatherService->searchCities(urldecode($query));
        return response()->json($cities);
    }
}