import { api } from './api.js';

export async function mountManufacturing(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  const furnitureId = localStorage.getItem('fmos_furniture_id');
  main.innerHTML = `<div class="panel"><h2>Engineering / Cutlist / Release</h2>
    <p class="muted">Project ${projectId || '—'} · Furniture ${furnitureId || '—'}</p>
    <div class="toolbar">
      <button id="gen-mfg">Generate Manufacturing</button>
      <button id="release-mfg" class="secondary">Release (Manufacturing Manager)</button>
    </div>
    <p id="mfg-error" class="error"></p>
    <pre id="mfg-out"></pre></div>`;

  const out = document.getElementById('mfg-out');
  const err = document.getElementById('mfg-error');

  document.getElementById('gen-mfg').onclick = async () => {
    err.textContent = '';
    if (!projectId || !furnitureId) {
      err.textContent = 'Select a project and create/open furniture first.';
      return;
    }
    try {
      const res = await api.post('/api/v1/manufacturing/generate', {
        project_id: Number(projectId),
        furniture_id: Number(furnitureId),
      });
      localStorage.setItem('fmos_mfg_id', res.data.id);
      out.textContent = JSON.stringify(res.data, null, 2);
    } catch (e) {
      err.textContent = e.message || 'Generate failed';
      out.textContent = JSON.stringify(e.payload || {}, null, 2);
    }
  };

  document.getElementById('release-mfg').onclick = async () => {
    err.textContent = '';
    const id = localStorage.getItem('fmos_mfg_id');
    if (!id) {
      err.textContent = 'Generate a manufacturing package first.';
      return;
    }
    try {
      const res = await api.post(`/api/v1/manufacturing/${id}/release`, {});
      out.textContent = JSON.stringify(res.data, null, 2);
    } catch (e) {
      err.textContent = e.message || 'Release failed';
      out.textContent = JSON.stringify(e.payload || {}, null, 2);
    }
  };
}
