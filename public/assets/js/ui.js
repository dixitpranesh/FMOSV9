import { api } from './api.js';
import { state } from './state.js';
import { pages } from './pages.js';

export function renderLogin() {
  const main = document.getElementById('main');
  document.getElementById('nav').innerHTML = '';
  document.getElementById('user-box').innerHTML = '';
  main.innerHTML = `
    <div class="panel login-wrap">
      <h1>FMOS Login</h1>
      <p class="muted">Design-to-Manufacturing Operating System</p>
      <form id="login-form">
        <label>Email</label>
        <input name="email" type="email" autocomplete="username" required value="owner@demo.fmos" />
        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required value="Password123!" />
        <button type="submit">Sign in</button>
        <p id="login-error" class="error"></p>
      </form>
    </div>`;
  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const res = await api.post('/api/v1/auth/login', {
        email: fd.get('email'),
        password: fd.get('password'),
      });
      api.setToken(res.data.token);
      state.user = res.data.user;
      location.hash = '#dashboard';
      renderShell(state);
    } catch (err) {
      document.getElementById('login-error').textContent = err.message;
    }
  });
}

export function renderShell(appState) {
  const nav = document.getElementById('nav');
  const links = [
    ['dashboard', 'Dashboard'],
    ['organizations', 'Organizations'],
    ['clients', 'Clients'],
    ['projects', 'Projects'],
    ['designer', '2D/3D Designer'],
    ['catalog', 'Catalog'],
    ['furniture', 'Furniture'],
    ['commercial', 'BOM/BOQ/Price'],
    ['manufacturing', 'Manufacturing'],
    ['nesting', 'Nesting/Labels'],
  ];
  nav.innerHTML = links.map(([id, label]) =>
    `<a href="#${id}" class="${appState.route === id ? 'active' : ''}">${label}</a>`
  ).join('');
  document.getElementById('user-box').innerHTML = `
    <span class="muted">${appState.user?.name || ''} · ${appState.user?.email || ''}</span>
    <button class="secondary" id="logout-btn" style="margin-left:0.5rem">Logout</button>`;
  document.getElementById('logout-btn').onclick = async () => {
    try { await api.post('/api/v1/auth/logout', {}); } catch {}
    api.setToken(null);
    state.user = null;
    renderLogin();
  };
  const page = pages[appState.route] || pages.dashboard;
  page(document.getElementById('main'), appState);
}
