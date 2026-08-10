import { api } from './api.js';

export async function mountManufacturing(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  main.innerHTML = `<div class="panel"><h2>Manufacturing</h2>
    <p class="muted">Project ${projectId || '—'}</p>
    <div id="mfg-furn-list"></div>
    <div class="toolbar">
      <button id="validate-sel">Validate selected</button>
      <button id="gen-mfg">Generate for selected</button>
      <button id="release-mfg" class="secondary">Release package</button>
      <button id="export-cut" class="secondary">Export cutlist CSV</button>
    </div>
    <div id="cutlist-wrap"></div>
    <p id="mfg-error" class="error"></p>
    <pre id="mfg-out"></pre></div>`;

  const out = document.getElementById('mfg-out');
  const err = document.getElementById('mfg-error');

  const loadFurniture = async () => {
    if (!projectId) return;
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    document.getElementById('mfg-furn-list').innerHTML = `
      <table><thead><tr><th></th><th>Code</th><th>Name</th><th>Size</th></tr></thead>
      <tbody>${(res.data || []).map((f) => `<tr>
        <td><input type="checkbox" class="mfg-fid" value="${f.id}" ${String(f.id) === localStorage.getItem('fmos_furniture_id') ? 'checked' : ''}></td>
        <td>${f.code || ''}</td><td>${f.name}</td>
        <td>${f.width_mm}×${f.height_mm}×${f.depth_mm}</td>
      </tr>`).join('')}</tbody></table>`;
  };

  const selectedIds = () => [...document.querySelectorAll('.mfg-fid:checked')].map((el) => Number(el.value));

  document.getElementById('validate-sel').onclick = async () => {
    err.textContent = '';
    const ids = selectedIds();
    if (!ids.length) { err.textContent = 'Select furniture'; return; }
    const results = [];
    for (const id of ids) {
      results.push((await api.post(`/api/v1/furniture/instances/${id}/validate`, {})).data);
    }
    out.textContent = JSON.stringify(results, null, 2);
  };

  document.getElementById('gen-mfg').onclick = async () => {
    err.textContent = '';
    const ids = selectedIds();
    if (!projectId || !ids.length) {
      err.textContent = 'Select a project and furniture.';
      return;
    }
    try {
      const res = await api.post(`/api/v1/projects/${projectId}/manufacturing`, { furniture_ids: ids });
      const firstPkg = res.data.furniture?.[0]?.manufacturing_package_id;
      if (firstPkg) localStorage.setItem('fmos_mfg_id', String(firstPkg));
      out.textContent = JSON.stringify(res.data, null, 2);
      if (firstPkg) {
        const cut = await api.get(`/api/v1/manufacturing/${firstPkg}/cutlist`);
        renderCutlist(cut.data);
      }
    } catch (e) {
      err.textContent = e.message || 'Generate failed';
      out.textContent = JSON.stringify(e.payload || {}, null, 2);
    }
  };

  const renderCutlist = (data) => {
    const cols = data.columns || [];
    document.getElementById('cutlist-wrap').innerHTML = `
      <h3>Cutlist</h3>
      <table><thead><tr>${cols.map((c) => `<th>${c}</th>`).join('')}</tr></thead>
      <tbody>${(data.items || []).map((r) => `<tr>${cols.map((c) => `<td>${r[c] ?? ''}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
  };

  document.getElementById('release-mfg').onclick = async () => {
    err.textContent = '';
    const id = localStorage.getItem('fmos_mfg_id');
    if (!id) { err.textContent = 'Generate a manufacturing package first.'; return; }
    try {
      const res = await api.post(`/api/v1/manufacturing/${id}/release`, {});
      out.textContent = JSON.stringify(res.data, null, 2);
    } catch (e) {
      err.textContent = e.message || 'Release failed';
    }
  };

  document.getElementById('export-cut').onclick = async () => {
    const id = localStorage.getItem('fmos_mfg_id');
    if (!id) { err.textContent = 'No package'; return; }
    const res = await api.post(`/api/v1/manufacturing/${id}/cutlist/export`, {});
    const blob = new Blob([res.data.content], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = res.data.filename;
    a.click();
  };

  loadFurniture();
}
