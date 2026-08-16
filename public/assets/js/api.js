const TOKEN_KEY = 'fmos_token';
const CSRF_KEY = 'fmos_csrf';

export const api = {
  token() {
    return localStorage.getItem(TOKEN_KEY);
  },
  setToken(token) {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
  },
  csrf() {
    return localStorage.getItem(CSRF_KEY);
  },
  setCsrf(token) {
    if (token) localStorage.setItem(CSRF_KEY, token);
    else localStorage.removeItem(CSRF_KEY);
  },
  async request(method, path, body) {
    const headers = { Accept: 'application/json' };
    const token = this.token();
    if (token) headers.Authorization = `Bearer ${token}`;
    const csrf = this.csrf();
    if (csrf) headers['X-CSRF-Token'] = csrf;
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    const res = await fetch(path, {
      method,
      headers,
      credentials: 'same-origin',
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    const json = await res.json();
    if (!res.ok || json.success === false) {
      const err = new Error(json?.error?.message || 'Request failed');
      err.code = json?.error?.code;
      err.status = res.status;
      err.details = json?.error?.details;
      err.payload = json;
      throw err;
    }
    return json;
  },
  get(path) { return this.request('GET', path); },
  post(path, body) { return this.request('POST', path, body); },
  put(path, body) { return this.request('PUT', path, body); },
  del(path) { return this.request('DELETE', path); },
};
