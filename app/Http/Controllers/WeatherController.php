<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response; // Importante
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
    // Novo endpoint: /api/clima/coordenadas?lat=-8.05&lon=-34.88
    public function getByCoordinates(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $data = $this->weatherService->getForecastByCoordinates(
            $request->lat,
            $request->lon
        );

        if ($data) {
            return response()->json($data, 200);
        }

        return response()->json(['error' => 'Localização não encontrada'], 404);
    }
    // Adicione isso no final do seu WeatherController.php
    public function getMapTile($z, $x, $y)
    {
        $apiKey = config('services.openweather.key');

        // Vamos usar 'precipitation_new' (a mais moderna)
        $url = "https://tile.openweathermap.org/map/precipitation_new/{$z}/{$x}/{$y}.png?appid={$apiKey}";

        try {
            // 1. O TRUQUE: withoutVerifying() ignora erros de SSL locais
            $response = Http::withoutVerifying()->timeout(3)->get($url);

            // 2. VERIFICAÇÃO RIGOROSA: Só retorna se for realmente uma imagem PNG
            if ($response->successful() && str_contains($response->header('Content-Type'), 'image/png')) {
                return response($response->body())
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'public, max-age=3600');
            }
        } catch (\Exception $e) {
            // Ignora o erro e cai no retorno transparente abaixo
        }

        // --- PLANO DE SEGURANÇA: PIXEL TRANSPARENTE ---
        // Se deu erro, ou não é imagem, retorna transparente para o mapa não ficar branco
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        return response($pixel)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store');
    }
    public function climaPorCoordenadas(Request $request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $apiKey = env('OPENWEATHER_API_KEY');

        // 1. Busca a Previsão do Tempo (O que você já faz)
        $weatherResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast", [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'pt_br'
        ]);

        $weatherData = $weatherResponse->json();

        // 2. SOLUÇÃO: Busca o Estado via Reverse Geocoding
        // Essa API transforma Lat/Lon em "Recife, Pernambuco, BR"
        $geoResponse = Http::get("http://api.openweathermap.org/geo/1.0/reverse", [
            'lat' => $lat,
            'lon' => $lon,
            'limit' => 1,
            'appid' => $apiKey
        ]);

        // 3. Injeta o estado na resposta
        if ($geoResponse->successful() && !empty($geoResponse->json())) {
            $geoData = $geoResponse->json()[0];

            // Adiciona o campo 'state_uf' manualmente no objeto city
            // A API retorna o nome completo (ex: "Pernambuco"), você pode mapear para UF se quiser
            // ou mandar o nome completo mesmo.
            $weatherData['city']['state_uf'] = $geoData['state'] ?? null;
        }

        return response()->json($weatherData);
    }
}
