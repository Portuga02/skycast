<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherService
{
    protected $apiKey;
    protected $geoUrl = 'http://api.openweathermap.org/geo/1.0/direct';
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

      return \Illuminate\Support\Facades\Cache::remember("forecast_city_v20_" . md5($cityQuery), 900, function () use ($cityQuery) {

            // 1. Geocoding
            $geoResponse = Http::withoutVerifying()->get($this->geoUrl, [
                'q' => $cityQuery,
                'limit' => 1,
                'appid' => $this->apiKey
            ]);

            if ($geoResponse->failed() || !isset($geoResponse->json()[0])) return null;

            $geoData = $geoResponse->json()[0];

            // 2. Forecast
            $response = Http::withoutVerifying()->get($this->forecastUrl, [
                'lat' => $geoData['lat'],
                'lon' => $geoData['lon'],
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'pt_br'
            ]);

            if ($response->failed()) return null;

            // CORREÇÃO 1: Definimos a variável $dados aqui
            $dados = $response->json();

            // Injetamos a UF
            $dados['city']['state_uf'] = $this->formatState($geoData['state'] ?? null);
            
            // Injetamos a Qualidade do Ar
            $dados['air_quality'] = $this->getAirQuality($geoData['lat'], $geoData['lon']);

            return $dados;
        });
    }

    /**
     * Busca por coordenadas (Geolocalização)
     */
    public function getForecastByCoordinates($lat, $lon)
    {
        $cacheKey = "forecast_coords_v2_" . round($lat, 4) . "_" . round($lon, 4);

        return Cache::remember($cacheKey, 900, function () use ($lat, $lon) {
            
            // 1. Forecast
            $response = Http::withoutVerifying()->get($this->forecastUrl, [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'pt_br'
            ]);

            if ($response->failed()) return null;

            $dados = $response->json();

            // 2. Reverse Geocoding (Para pegar o Bairro)
            $geoResponse = Http::withoutVerifying()->get($this->geoUrl . "/reverse", [
                'lat' => $lat,
                'lon' => $lon,
                'limit' => 1,
                'appid' => $this->apiKey
            ]);

            $state = null;
            $bairroOuCidadeEspecifica = null;

            if ($geoResponse->successful() && isset($geoResponse->json()[0])) {
                $geoData = $geoResponse->json()[0];
                $state = $geoData['state'] ?? null;
                $bairroOuCidadeEspecifica = $geoData['name'] ?? null;
            }

            // Injeta o nome do bairro se existir
            if ($bairroOuCidadeEspecifica) {
                $dados['city']['name'] = $bairroOuCidadeEspecifica;
            }

            // Injeta UF
            $dados['city']['state_uf'] = $this->formatState($state);
            
            // CORREÇÃO 2: Removemos a linha que resetava o $dados aqui
            
            // Injeta Qualidade do Ar
            $dados['air_quality'] = $this->getAirQuality($lat, $lon);

            return $dados;
        });
    }

    /**
     * Busca a qualidade do ar (AQI)
     */
    private function getAirQuality($lat, $lon)
    {
        return Cache::remember("pollution_{$lat}_{$lon}", 3600, function () use ($lat, $lon) {
            $response = Http::withoutVerifying()->get('http://api.openweathermap.org/data/2.5/air_pollution', [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json()['list'][0]['main']['aqi'] ?? null;
            }
            
            return null;
        });
    }

    // --- Métodos Auxiliares ---

    public function formatState(?string $state)
    {
        if (!$state) return null;
        $states = [
            'Paraiba' => 'PB', 'Pernambuco' => 'PE', 'Ceara' => 'CE', 'Rio Grande do Norte' => 'RN',
            'Bahia' => 'BA', 'Alagoas' => 'AL', 'Sergipe' => 'SE', 'Maranhao' => 'MA',
            'Piaui' => 'PI', 'Sao Paulo' => 'SP', 'Rio de Janeiro' => 'RJ', 'Minas Gerais' => 'MG',
            'Espirito Santo' => 'ES', 'Parana' => 'PR', 'Rio Grande do Sul' => 'RS', 'Santa Catarina' => 'SC',
            'Goias' => 'GO', 'Mato Grosso' => 'MT', 'Mato Grosso do Sul' => 'MS', 'Distrito Federal' => 'DF',
            'Acre' => 'AC', 'Amapa' => 'AP', 'Amazonas' => 'AM', 'Para' => 'PA', 'Rondonia' => 'RO',
            'Roraima' => 'RR', 'Tocantins' => 'TO'
        ];
        $stateClean = $this->normalizeString($state);
        foreach ($states as $name => $initial) {
            if ($this->normalizeString($name) === $stateClean) return $initial;
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
        return Cache::remember("search_city_" . md5($query), 3600, function () use ($query) {
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
        });
    }
}