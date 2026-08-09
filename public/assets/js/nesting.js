import { api } from './api.js';

export async function mountNesting(main) {
  const mfgId = localStorage.getItem('fmos_mfg_id');
  main.innerHTML = `<div class="panel"><h2>Nesting & Panel Labels</h2>
    <div class="toolbar">
      <button id="run-nest">Run Basic Nesting</button>
      <button id="labels" class="secondary">Generate Labels</button>
    </div>
    <pre id="nest-out"></pre></div>`;
  document.getElementById('run-nest').onclick = async () => {
    const res = await api.post(`/api/v1/manufacturing/${mfgId}/nest`, {});
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
  };
  document.getElementById('labels').onclick = async () => {
    const res = await api.get(`/api/v1/manufacturing/${mfgId}/labels`);
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
  };
}
