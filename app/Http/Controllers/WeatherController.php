<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    // Busca por Nome
    public function getClima($city)
    {
        try {
            $decodedCity = urldecode($city);
            // ATENÇÃO: Se o seu Service não tiver withoutVerifying, pode dar erro aqui também.
            // Mas vamos focar no GPS agora.
            $data = $this->weatherService->getForecastData($decodedCity);

            if ($data) return response()->json($data, 200);
            return response()->json(['error' => 'Not Found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Autocomplete
    public function search($query)
    {
        $cities = $this->weatherService->searchCities(urldecode($query));
        return response()->json($cities);
    }

    // --- DETETIVE DE RUA (GPS) ---
    public function climaPorCoordenadas(Request $request)
    {
        $request->validate(['lat' => 'required', 'lon' => 'required']);

        $lat = $request->input('lat');
        $lon = $request->input('lon');
        $apiKey = env('OPENWEATHER_API_KEY');

        try {
            // 1. BUSCA O CLIMA (COM CORREÇÃO DE SSL)
            // Adicionei withoutVerifying() aqui!
            $weatherResponse = Http::withoutVerifying()->get("https://api.openweathermap.org/data/2.5/forecast", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'pt_br'
            ]);

            // Se falhar a conexão com a API, lança erro
            if ($weatherResponse->failed()) {
                throw new \Exception("Falha OpenWeather: " . $weatherResponse->status());
            }

            $weatherData = $weatherResponse->json();

            // 2. BUSCA A RUA (COM CORREÇÃO DE SSL)
            // Adicionei withoutVerifying() aqui também!
            try {
                $geoResponse = Http::withoutVerifying()
                    ->withHeaders(['User-Agent' => 'SkyCastPro/1.0'])
                    ->get("https://nominatim.openstreetmap.org/reverse", [
                        'lat' => $lat,
                        'lon' => $lon,
                        'format' => 'json',
                        'zoom' => 18,
                        'addressdetails' => 1
                    ]);

                if ($geoResponse->successful()) {
                    $geoData = $geoResponse->json();
                    $address = $geoData['address'] ?? [];

                    $nomeRua = $address['road'] ?? $address['pedestrian'] ?? $address['suburb'] ?? null;

                    if ($nomeRua) {
                        $weatherData['city']['city_original'] = $weatherData['city']['name'];
                        $weatherData['city']['name'] = $nomeRua;
                    }

                    if (isset($address['state'])) {
                        $weatherData['city']['state_uf'] = str_replace('BR-', '', $address['ISO3166-2-lvl4'] ?? $address['state']);
                    }
                }
            } catch (\Exception $e) {
                // Se o Nominatim falhar, seguimos só com o clima
                \Log::error("Erro Nominatim: " . $e->getMessage());
            }

            return response()->json($weatherData);
        } catch (\Exception $e) {
            \Log::error("Erro Geral GPS: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    // Mapas Coloridos
    public function getMapTile($layer, $z, $x, $y)
    {
        $apiKey = env('OPENWEATHER_API_KEY');
        $url = "https://tile.openweathermap.org/map/{$layer}/{$z}/{$x}/{$y}.png?appid={$apiKey}";

        try {
            $response = Http::withoutVerifying()->timeout(3)->get($url);

            if ($response->successful() && str_contains($response->header('Content-Type'), 'image/png')) {
                return response($response->body())
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'public, max-age=3600');
            }
        } catch (\Exception $e) {
        }

        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        return response($pixel)->header('Content-Type', 'image/png');
    }
}
