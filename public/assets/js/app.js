import { api } from './api.js';
import { state } from './state.js';
import { renderLogin, renderShell } from './ui.js';

const AUTH_ROUTES = new Set(['login', 'register', 'verify-email', 'verify-pending', 'forgot-password', 'reset-password']);

function currentRoute() {
  const raw = location.hash.replace(/^#/, '') || '';
  return raw.split('?')[0] || 'dashboard';
}

export async function boot() {
  state.route = currentRoute();
  try {
    const me = await api.get('/api/v1/auth/me');
    state.user = me.data;
    if (AUTH_ROUTES.has(state.route)) {
      location.hash = '#dashboard';
      state.route = 'dashboard';
    }
    renderShell(state);
  } catch {
    if (!AUTH_ROUTES.has(state.route) && state.route !== '') {
      location.hash = '#login';
      state.route = 'login';
    }
    renderLogin();
  }
}

window.addEventListener('hashchange', () => {
  state.route = currentRoute() || 'dashboard';
  if (state.user) {
    if (AUTH_ROUTES.has(state.route)) {
      location.hash = '#dashboard';
      return;
    }
    renderShell(state);
  } else {
    renderLogin();
  }
});

boot();
