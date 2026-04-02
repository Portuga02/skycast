<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use League\Uri\Http;

class CheckWeatherAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-weather-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    // app/Console/Commands/CheckWeatherAlerts.php

public function handle()
{
    // 1. Busca no banco todos os usuários que ativaram notificações e suas cidades
    $users = User::where('wants_alerts', true)->get();

    foreach ($users as $user) {
        // 2. Bate na API da OpenWeather para a cidade do usuário
        $weather = Http::get("https://api.openweathermap.org/data/2.5/weather?lat={$user->lat}&lon={$user->lon}...");
        
        $weatherId = $weather['weather'][0]['id'];

        // 3. Verifica se a previsão mudou drasticamente (Ex: ID na casa dos 200, 300 ou 500)
        if ($weatherId >= 200 && $weatherId <= 599) {
            // 4. Dispara a notificação pro usuário!
            $user->notify(new \App\Notifications\RainAlertNotification($weather));
        }
    }
}
}
