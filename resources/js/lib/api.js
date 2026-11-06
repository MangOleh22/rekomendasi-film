// Klien HTTP untuk /api (token Sanctum disimpan di localStorage).
const KEY = 'auth_token';
export const getToken = () => localStorage.getItem(KEY);
export const setToken = (t) => (t ? localStorage.setItem(KEY, t) : localStorage.removeItem(KEY));

async function request(method, url, body) {
  const headers = { Accept: 'application/json' };
  if (getToken()) headers.Authorization = `Bearer ${getToken()}`;
  const isForm = body instanceof FormData;
  if (body && !isForm) headers['Content-Type'] = 'application/json';

  const res = await fetch(`/api${url}`, { method, headers, body: isForm ? body : body && JSON.stringify(body) });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401 && getToken()) { setToken(null); location.href = '/login'; }
    const first = data.errors && Object.values(data.errors)[0]?.[0];
    throw new Error(first || data.message || 'Terjadi kesalahan.');
  }
  return data;
}

// Unduh file (mis. CSV) dari endpoint ber-token lalu simpan di perangkat.
export async function download(url, fallbackName = 'unduhan') {
  const res = await fetch(`/api${url}`, { headers: { Authorization: `Bearer ${getToken()}` } });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.message || 'Gagal mengunduh file.');
  }
  const name = res.headers.get('Content-Disposition')?.match(/filename="?([^";]+)"?/)?.[1] || fallbackName;
  const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(await res.blob()), download: name });
  a.click();
  setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}

export const api = {
  get: (url, params = {}) => {
    const q = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== '' && v != null));
    return request('GET', q.size ? `${url}?${q}` : url);
  },
  post: (url, body) => request('POST', url, body),
  put: (url, body) => request('PUT', url, body),
  del: (url) => request('DELETE', url),
};
