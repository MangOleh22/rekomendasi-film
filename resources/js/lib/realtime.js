// Realtime via Laravel Reverb (WebSocket). Opsional: aktif hanya jika VITE_REVERB_APP_KEY terisi.
// Echo & pusher-js dimuat dinamis supaya tidak membebani bundel utama.
export async function listen(channel, event, handler) {
  const key = import.meta.env.VITE_REVERB_APP_KEY;
  if (!key) return;
  try {
    if (!window.Echo) {
      const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')]);
      window.Pusher = Pusher;
      const https = (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.slice(0, -1)) === 'https';
      window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST || location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: https,
        enabledTransports: ['ws', 'wss'],
      });
    }
    window.Echo.channel(channel).listen(`.${event}`, handler);

    // Reverb belum jalan / tidak terjangkau: berhenti mencoba ulang agar konsol tidak dipenuhi error WebSocket.
    if (!window.__realtimeGuard) {
      window.__realtimeGuard = true;
      const stop = () => { window.Echo?.disconnect(); console.info('Realtime nonaktif (server Reverb tidak terjangkau). Jalankan: php artisan reverb:start'); };
      window.Echo.connector.pusher.connection.bind('unavailable', stop);
      window.Echo.connector.pusher.connection.bind('failed', stop);
    }
  } catch (e) {
    console.warn('Realtime tidak aktif:', e);
  }
}
