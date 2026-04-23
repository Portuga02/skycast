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

  public function climaPorCoordenadas(Request $request)
{
    $request->validate(['lat' => 'required', 'lon' => 'required']);

    $lat = $request->input('lat');
    $lon = $request->input('lon');
    $apiKey = env('OPENWEATHER_API_KEY');

    // Se a chave não vier do ENV, o erro 401 é certo.
    if (!$apiKey) {
        return response()->json(['error' => 'Chave API não configurada no .env'], 500);
    }

    try {
        // Usamos withoutVerifying() para evitar erros de certificado SSL no ambiente local
        $weatherResponse = Http::withoutVerifying()->get("https://api.openweathermap.org/data/2.5/forecast", [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'pt_br'
        ]);

        if ($weatherResponse->status() === 401) {
            return response()->json(['error' => 'Chave da OpenWeather inválida ou ainda não ativada.'], 401);
        }

        if ($weatherResponse->failed()) {
            throw new \Exception("Falha OpenWeather: " . $weatherResponse->status());
        }

        $weatherData = $weatherResponse->json();

        // Lógica de Cidades Vizinhas (Find)
        try {
            $nearbyResponse = Http::withoutVerifying()->get("https://api.openweathermap.org/data/2.5/find", [
                'lat' => $lat,
                'lon' => $lon,
                'cnt' => 5,
                'appid' => $apiKey,
                'units' => 'metric'
            ]);

            if ($nearbyResponse->successful()) {
                $weatherData['nearby'] = $nearbyResponse->json()['list'] ?? [];
            }
        } catch (\Exception $e) {
            $weatherData['nearby'] = [];
        }

        return response()->json($weatherData);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
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
    public function getByCoordinates(Request $request)
    {
        $lat = $request->lat;
        $lon = $request->lon;
        $apiKey = env('OPENWEATHER_API_KEY');

        $responsePrincipal = Http::get("https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric");

        return response()->json([
            'lat' => $responsePrincipal['coord']['lat'],
            'lon' => $responsePrincipal['coord']['lon'],
            'temp' => $responsePrincipal['main']['temp'],
            'iconCode' => $responsePrincipal['weather'][0]['icon'],
            'weatherId' => $responsePrincipal['weather'][0]['id'],
            // 'nearby' => $arrayDeVizinhos
        ]);
    }
}
