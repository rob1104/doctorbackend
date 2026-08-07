import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Endpoint personalizado que creamos en api.php
    authEndpoint: import.meta.env.VITE_API_URL 
        ? import.meta.env.VITE_API_URL + '/broadcasting/auth' 
        : '/api/broadcasting/auth',
    auth: {
        headers: {
            // Asumiendo que guardas el token en localStorage
            Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
            Accept: 'application/json',
        }
    }
});
