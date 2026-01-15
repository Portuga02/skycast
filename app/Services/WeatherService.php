<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Service responsável por gerenciar a comunicação com a API OpenWeather.
 * Implementa desambiguação geográfica, enriquecimento de dados e cache estratégico.
 */
class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';
    protected $geoUrl = 'http://api.openweathermap.org/geo/1.0/direct';

    public function __construct()
    {
        // Puxa a chave configurada no services.php (que lê do seu .env)
        $this->apiKey = config('services.openweather.key');
    }

    /**
     * Busca os dados climáticos detalhados e realiza a desambiguação geográfica.
     * * @param string $city Nome da cidade enviado pelo frontend (ex: "Recife - PE" ou "Prata - PB")
     * @return array|null Retorna os dados climáticos formatados ou null em caso de falha
     */
    public function getWeather(string $city)
    {
        // 1. Tratamento da string de entrada:
        // Substituímos o hífen por vírgula para que a Geocoding API entenda o separador de estado
        $cityQuery = str_replace('-', ',', $city);
        $cityQuery = trim(preg_replace('/\s+/', ' ', $cityQuery));

        // 2. Uso de Cache Estratégico:
        // Cache de 15 minutos (900s) para performance e economia de cota da API
        return Cache::remember("weather_city_v3_" . md5($cityQuery), 900, function () use ($cityQuery) {
            
            // 3. Chamada à Geocoding API para desambiguação:
            // Buscamos coordenadas (Lat/Lon) para evitar erro em cidades com nomes duplicados
            $geoResponse = Http::withoutVerifying()->get($this->geoUrl, [
                'q' => $cityQuery,
                'limit' => 1,
                'appid' => $this->apiKey
            ]);

            // Validação da resposta da Geocodificação
            if ($geoResponse->failed() || !isset($geoResponse->json()[0])) {
                return null;
            }

            $geoData = $geoResponse->json()[0];
            $latitude = $geoData['lat'];
            $longitude = $geoData['lon'];
            $nomeEstadoOriginal = $geoData['state'] ?? null;

            // 4. Chamada à Weather API por Coordenadas:
            // Método mais preciso para garantir o clima da localização exata selecionada
            $response = Http::withoutVerifying()->get($this->baseUrl, [
                'lat' => $latitude,
                'lon' => $longitude,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang' => 'pt_br'
            ]);

            if ($response->failed()) {
                return null;
            }

            $dadosClimaticos = $response->json();
            
            // 5. Enriquecimento de Dados (Data Enrichment):
            // Injetamos a sigla do estado (UF) para exibição no Vue
            $dadosClimaticos['state'] = $this->formatState($nomeEstadoOriginal);

            return $dadosClimaticos;
        });
    }

    /**
     * Converte o nome do estado retornado pela API para sua respectiva sigla (UF).
     */
    private function formatState(?string $state)
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
            'Amapa' => 'AP', 'Amazonas' => 'AM', 'Para' => 'PA', 'Rondonia' => 'RO', 
            'Roraima' => 'RR', 'Tocantins' => 'TO'
        ];

        // Normalização simples para garantir o match mesmo com variações de acentuação
        $stateClean = $this->normalizeString($state);

        // Busca no mapeamento ignorando case
        foreach ($states as $name => $initial) {
            if ($this->normalizeString($name) === $stateClean) {
                return $initial;
            }
        }

        return $state; // Retorna o nome original se não encontrar na lista
    }

    /**
     * Remove acentos e converte para minúsculo para comparação de strings.
     */
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
    // Buscamos até 5 opções para o usuário escolher
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
            // Guardamos a string completa formatada para a busca final
            'full_name' => $item['name'] . ($item['state'] ? " - " . $item['state'] : "")
        ];
    })->all();
}
}