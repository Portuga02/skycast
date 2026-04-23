<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckWeatherAlerts extends Command
{
    /**
     * O nome e a assinatura do comando (o que você digita no terminal).
     */
    protected $signature = 'app:check-weather-alerts';

    /**
     * A descrição do que esse robô faz.
     */
    protected $description = 'Monitora o clima via OpenWeather e dispara notificações em tempo real via Broadcast';

    /**
     * Executa a lógica do comando.
     */
   public function handle()
{
    $users = User::all();
    $apiKey = env('OPENWEATHER_API_KEY');

    foreach ($users as $user) {
        $response = Http::withoutVerifying()->get("https://api.openweathermap.org/data/2.5/weather", [
            'lat' => $user->lat ?? -8.0476,
            'lon' => $user->lon ?? -34.8770,
            'appid' => $apiKey,
            'units' => 'metric',
            'lang' => 'pt_br'
        ]);

        if ($response->successful()) {
            $current = $response->json();
            $cacheKey = "weather_old_user_" . $user->id;
            
            // 1. Pega o clima anterior da "memória" (Cache)
            $old = cache($cacheKey);

            if ($old) {
                $mudancas = [];
                
                // Comparação de Temperatura
                $diffTemp = $current['main']['temp'] - $old['main']['temp'];
                if (abs($diffTemp) >= 1) { // Só avisa se mudar mais de 1 grau
                    $mudancas[] = $diffTemp > 0 ? "esquentou " . round($diffTemp) . "°C" : "esfriou " . round(abs($diffTemp)) . "°C";
                }

                // Comparação de Condição (Sol -> Chuva)
                if ($current['weather'][0]['main'] !== $old['weather'][0]['main']) {
                    $mudancas[] = "mudou de " . $old['weather'][0]['description'] . " para " . $current['weather'][0]['description'];
                }

                // 2. Se algo mudou, gera a mensagem personalizada
                if (!empty($mudancas)) {
                    $textoAlerta = "O clima mudou! Agora está " . implode(" e ", $mudancas) . ".";
                    
                    // Dispara a notificação com o texto da mudança
                    $user->notify(new \App\Notifications\RainAlertNotification($current, $textoAlerta));
                    $this->info("Mudança detectada para {$user->name}: {$textoAlerta}");
                }
            }

            // 3. Guarda o clima atual no Cache para a próxima comparação (expira em 2 horas)
            cache([$cacheKey => $current], now()->addHours(2));
        }
    }
}
}