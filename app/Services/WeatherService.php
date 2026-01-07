<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct()
    {
        // Puxa a chave que você colocou no .env  
        $this->apiKey = config('services.openweather.key');
    }

    public function getWeather(string $city)
    {
        // Cache de 15 minutosss (900 segundos) para ser performático
        return Cache::remember("weather_city_{$city}", 900, function () use ($city) {
            $response = Http::get($this->baseUrl, [
                'q' => $city,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'pt_br' // Resultados em português!
            ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        });
    }
}
