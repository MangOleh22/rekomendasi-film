// Realtime lewat WebSocket. Opsional: aktif hanya jika kunci publik terisi saat build.
//  - Pusher (hosting biasa): VITE_PUSHER_APP_KEY + VITE_PUSHER_APP_CLUSTER
//  - Laravel Reverb (VPS/lokal): VITE_REVERB_APP_KEY
// Hanya KUNCI PUBLIK yang boleh masuk VITE_*; secret tidak pernah dikirim ke browser.
// Echo & pusher-js dimuat dinamis supaya tidak membebani bundel utama.
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

function echoConfig() {
  if (pusherKey && pusherCluster) {
    return { broadcaster: 'pusher', key: pusherKey, cluster: pusherCluster, forceTLS: true, disableStats: true, enabledTransports: ['ws', 'wss'] };
  }
  if (reverbKey) {
    const https = (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.slice(0, -1)) === 'https';
    return {
      broadcaster: 'reverb',
      key: reverbKey,
      wsHost: import.meta.env.VITE_REVERB_HOST || location.hostname,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: https,
      enabledTransports: ['ws', 'wss'],
    };
  }
  return null;
}

export async function listen(channel, event, handler) {
  const config = echoConfig();
  if (!config) return;
  try {
    if (!window.Echo) {
      const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')]);
      window.Pusher = Pusher;
      window.Echo = new Echo(config);
    }
    window.Echo.channel(channel).listen(`.${event}`, handler);

    // Server realtime tidak terjangkau: berhenti mencoba ulang agar konsol tidak dipenuhi error WebSocket.
    if (!window.__realtimeGuard) {
      window.__realtimeGuard = true;
      const stop = () => { window.Echo?.disconnect(); console.info('Realtime nonaktif (server WebSocket tidak terjangkau).'); };
      window.Echo.connector.pusher.connection.bind('unavailable', stop);
      window.Echo.connector.pusher.connection.bind('failed', stop);
    }
  } catch (e) {
    console.warn('Realtime tidak aktif:', e);
  }
}
