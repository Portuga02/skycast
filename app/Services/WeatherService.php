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
          $dados['air_quality'] = $this->getAirQuality($geoData['lat'], $geoData['lon']);

            // NOVO: Injetamos os Vizinhos!
            $dados['nearby'] = $this->getNearbyWeather($geoData['lat'], $geoData['lon']);

            return $dados;
        });
    }

    /**
     * Busca por coordenadas (Geolocalização)
     */
    public function getForecastByCoordinates($lat, $lon)
    {
        // Arredonda para melhorar o cache (GPS muda muito os decimais)
        $cacheKey = "forecast_coords_v3_" . round($lat, 4) . "_" . round($lon, 4);

        return Cache::remember($cacheKey, 900, function () use ($lat, $lon) {
            
            // 1. Busca a Previsão do Tempo (Forecast) usando o GPS direto
            $response = Http::withoutVerifying()->get($this->forecastUrl, [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'pt_br'
            ]);

            if ($response->failed()) return null;

            $dados = $response->json();

            // 2. Tenta descobrir o nome do Bairro/Cidade (Reverse Geocoding)
            $geoResponse = Http::withoutVerifying()->get($this->geoUrl . "/reverse", [
                'lat' => $lat,
                'lon' => $lon,
                'limit' => 1,
                'appid' => $this->apiKey
            ]);

            // Variáveis de controle
            $bairroOuCidadeEspecifica = null;

            if ($geoResponse->successful() && isset($geoResponse->json()[0])) {
                $geoData = $geoResponse->json()[0];
                $bairroOuCidadeEspecifica = $geoData['name'] ?? null;
            }

            // Se achou o nome do bairro, substitui no resultado
            if ($bairroOuCidadeEspecifica) {
                $dados['city']['name'] = $bairroOuCidadeEspecifica;
            }

            // 3. Injeções Extras (Usamos $lat e $lon originais para garantir que não quebre)
            
            // Qualidade do Ar
            $dados['air_quality'] = $this->getAirQuality($lat, $lon);

            // Vizinhos (Nearby)
            $dados['nearby'] = $this->getNearbyWeather($lat, $lon);

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
    /**
     * Busca estações meteorológicas próximas (Vizinhos)
     */
    public function getNearbyWeather($lat, $lon)
    {
        return Cache::remember("nearby_{$lat}_{$lon}", 900, function () use ($lat, $lon) {
            $response = Http::withoutVerifying()->get('https://api.openweathermap.org/data/2.5/find', [
                'lat' => $lat,
                'lon' => $lon,
                'cnt' => 20, // Quantidade de vizinhos (pode aumentar se quiser)
                'units' => 'metric',
                'lang' => 'pt_br',
                'appid' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json()['list'];
            }

            return [];
        });
    }
}