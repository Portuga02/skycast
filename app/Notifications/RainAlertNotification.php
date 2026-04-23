<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage; // <--- IMPORTANTE

class RainAlertNotification extends Notification
{
    use Queueable;

    public $weather;
    public $mensagemCustomizada;

    public function __construct($weather, $mensagemCustomizada = null)
    {
        $this->weather = $weather;
        $this->mensagemCustomizada = $mensagemCustomizada;
    }

    public function toBroadcast($notifiable)
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            // Se tiver mensagem de mudança, usa ela. Senão, usa a padrão.
            'mensagem' => $this->mensagemCustomizada ?? "Clima atual: " . $this->weather['weather'][0]['description'],
        ]);
    }
    // 3. Isso salva no banco de dados (tabela notifications)
    public function toArray(object $notifiable): array
    {
        return [
            'mensagem' => "Alerta de clima em " . ($this->weather['name'] ?? 'sua região'),
            'temp' => $this->weather['main']['temp'] ?? 0,
        ];
    }
}
