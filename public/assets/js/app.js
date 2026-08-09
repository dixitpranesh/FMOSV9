import { api } from './api.js';
import { state } from './state.js';
import { renderLogin, renderShell } from './ui.js';

export async function boot() {
  try {
    const me = await api.get('/api/v1/auth/me');
    state.user = me.data;
    renderShell(state);
  } catch {
    renderLogin(state);
  }
}

window.addEventListener('hashchange', () => {
  state.route = location.hash.replace(/^#/, '') || 'dashboard';
  if (state.user) renderShell(state);
  else renderLogin(state);
});

boot();
