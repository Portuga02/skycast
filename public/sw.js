// public/sw.js

// Aqui nós escutamos o evento 'push' que vem do servidor
self.addEventListener('push', function(event) {
    // Se o usuário tirou a permissão depois, nós não fazemos nada
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    // Se o servidor mandar dados extras (título, texto), nós pegamos aqui
    const data = event.data?.json() ?? {};
    const title = data.title || 'Alerta SkyCast PRO';
    const message = data.body || 'Temos uma nova atualização sobre o clima!';
    
    // Você pode colocar o caminho do ícone do solzinho/nuvem aqui depois
    const icon = '/favicon.ico'; 

    // Pede para o sistema operacional mostrar a notificação na tela
    event.waitUntil(
        self.registration.showNotification(title, {
            body: message,
            icon: icon,
        })
    );
});