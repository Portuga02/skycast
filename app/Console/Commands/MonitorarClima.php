<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Notifications\WeatherAlert;

class MonitorarClima extends Command
{
    // O comando que você vai rodar no terminal se quiser testar: php artisan clima:monitorar
    protected $signature = 'clima:monitorar';
    protected $description = 'Vigia o céu e avisa os usuários sobre mudanças no clima';

    public function handle()
    {
        // 1. Pega todos os usuários que aceitaram notificações (ajuste o campo conforme seu banco)
        $users = User::all(); 
        $apiKey = env('OPENWEATHER_API_KEY');

        foreach ($users as $user) {
            // Se o usuário não tiver lat/lon salvos, usamos Recife como padrão
            $lat = $user->lat ?? -8.0476;
            $lon = $user->lon ?? -34.8770;

            try {
                // 2. Consulta o clima atual
                $response = Http::withoutVerifying()->get("https://api.openweathermap.org/data/2.5/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $apiKey,
                    'units' => 'metric',
                    'lang' => 'pt_br'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $weatherId = $data['weather'][0]['id'];
                    $temp = $data['main']['temp'];
                    $umidade = $data['main']['humidity'];
                    $descricao = $data['weather'][0]['description'];

                    // 3. LÓGICA DE ALERTAS (O "Cérebro")
                    $mensagem = null;

                    // Alerta de Chuva ou Tempestade (IDs 200 a 599)
                 if ($weatherId >= 200) { // Qualquer coisa (chuva, nuvens, sol) vai entrar aqui
    $mensagem = "☁️ Teste de Monitoramento: O clima atual é {$descricao} com {$temp}°C!";}
                    
                    // Alerta de Umidade Baixa (Muito comum no Nordeste/Recife em certas épocas)
                    elseif ($umidade < 30) {
                        $mensagem = "🌵 Alerta de Ar Seco: Umidade em {$umidade}%. Beba bastante água! 💧";
                    }

                    // Alerta de Calor Extremo
                    elseif ($temp > 35) {
                        $mensagem = "🔥 Calor Intenso: Temperatura de {$temp}°C. Evite exposição ao sol! ☀️";
                    }

                    // 4. DISPARA SE TIVER MENSAGEM
                    if ($mensagem) {
                        // Isso aqui vai bater no seu window.Echo lá no Vue!
                        $user->notify(new WeatherAlert($mensagem));
                        $this->info("Alerta enviado para {$user->name}: {$mensagem}");
                    }
                }

            } catch (\Exception $e) {
                $this->error("Erro ao monitorar para o usuário {$user->id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}