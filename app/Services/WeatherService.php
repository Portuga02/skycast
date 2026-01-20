<?php

namespace App\Services;

use Illuminate\Support\Facades\Http; // ESSENCIAL
use Illuminate\Support\Facades\Cache; // ESSENCIAL
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected $apiKey;
    protected $geoUrl = 'http://api.openweathermap.org/geo/1.0/direct';
    // Mudamos a base principal para forecast para aproveitar tudo da API
    protected $forecastUrl = 'https://api.openweathermap.org/data/2.5/forecast';

    public function __construct()
    {
       $this->apiKey = config('services.openweather.key');
    }

    /**
     * Busca Clima Atual + 5 dias usando sua lógica de desambiguação por UF
     */
   public function getForecastData(string $city)
{
    $cityQuery = str_replace('-', ',', $city);
    $cityQuery = trim(preg_replace('/\s+/', ' ', $cityQuery));

    // Usamos o seu Cache v3
    return \Illuminate\Support\Facades\Cache::remember("forecast_city_v1_" . md5($cityQuery), 900, function () use ($cityQuery) {

        // 1. Sua Geocoding API (que já funciona)
        $geoResponse = \Illuminate\Support\Facades\Http::get($this->geoUrl, [
            'q' => $cityQuery,
            'limit' => 1,
            'appid' => $this->apiKey
        ]);

        if ($geoResponse->failed() || !isset($geoResponse->json()[0])) return null;

        $geoData = $geoResponse->json()[0];
        
        // 2. Chamada para FORECAST (para os 5 dias)
        $response = Http::withoutVerifying()->get('https://api.openweathermap.org/data/2.5/forecast', [
            'lat' => $geoData['lat'],
            'lon' => $geoData['lon'],
            'appid' => $this->apiKey,
            'units' => 'metric',
            'lang' => 'pt_br'
        ]);

        if ($response->failed()) return null;

        $dados = $response->json();
        
        // Injetamos a UF usando sua função formatState
        $dados['city']['state_uf'] = $this->formatState($geoData['state'] ?? null);

        return $dados;
    });
}

    /**
     * Sua lógica de formatação de estados (Mantida exatamente como você fez)
     */
    public function formatState(?string $state)
    {
        if (!$state) return null;

        $states = [
            'Paraiba' => 'PB', 'Pernambuco' => 'PE', 'Ceara' => 'CE',
            'Rio Grande do Norte' => 'RN', 'Bahia' => 'BA', 'Alagoas' => 'AL',
            'Sergipe' => 'SE', 'Maranhao' => 'MA', 'Piaui' => 'PI',
            'Sao Paulo' => 'SP', 'Rio de Janeiro' => 'RJ', 'Minas Gerais' => 'MG',
            'Espirito Santo' => 'ES', 'Parana' => 'PR', 'Rio Grande do Sul' => 'RS',
            'Santa Catarina' => 'SC', 'Goias' => 'GO', 'Mato Grosso' => 'MT',
            'Mato Grosso do Sul' => 'MS', 'Distrito Federal' => 'DF', 'Acre' => 'AC',
            'Amapa' => 'AP', 'Amazonas' => 'AM', 'Para' => 'PA',
            'Rondonia' => 'RO', 'Roraima' => 'RR', 'Tocantins' => 'TO'
        ];

        $stateClean = $this->normalizeString($state);

        foreach ($states as $name => $initial) {
            if ($this->normalizeString($name) === $stateClean) {
                return $initial;
            }
        }

        return $state;
    }

    private function normalizeString(string $string)
    {
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ã', 'õ', 'â', 'ê', 'î', 'ô', 'û'],
            ['a', 'e', 'i', 'o', 'u', 'a', 'o', 'a', 'e', 'i', 'o', 'u'],
            mb_strtolower($string)
        );
    }

    public function searchCities(string $query)
    {
        $response = Http::withoutVerifying()->get($this->geoUrl, [
            'q' => $query,
            'limit' => 5,
            'appid' => $this->apiKey
        ]);

        if ($response->failed()) return [];

        return collect($response->json())->map(function ($item) {
            return [
                'name' => $item['name'],
                'state' => $this->formatState($item['state'] ?? null),
                'country' => $item['country'],
                'full_name' => $item['name'] . (isset($item['state']) ? " - " . $item['state'] : "")
            ];
        })->all();
    }
}