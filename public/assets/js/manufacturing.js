import { api } from './api.js';

export async function mountManufacturing(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  const furnitureId = localStorage.getItem('fmos_furniture_id');
  main.innerHTML = `<div class="panel"><h2>Engineering / Cutlist / Release</h2>
    <div class="toolbar">
      <button id="gen-mfg">Generate Manufacturing</button>
      <button id="release-mfg" class="secondary">Release (Manufacturing Manager)</button>
    </div>
    <pre id="mfg-out"></pre></div>`;
  document.getElementById('gen-mfg').onclick = async () => {
    const res = await api.post('/api/v1/manufacturing/generate', {
      project_id: Number(projectId),
      furniture_id: Number(furnitureId),
    });
    localStorage.setItem('fmos_mfg_id', res.data.id);
    document.getElementById('mfg-out').textContent = JSON.stringify(res.data, null, 2);
  };
  document.getElementById('release-mfg').onclick = async () => {
    const id = localStorage.getItem('fmos_mfg_id');
    const res = await api.post(`/api/v1/manufacturing/${id}/release`, {});
    document.getElementById('mfg-out').textContent = JSON.stringify(res.data, null, 2);
  };
}
