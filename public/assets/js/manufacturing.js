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
  let lastCutlists = [];

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

  const storePackages = (jobId, packageIds) => {
    localStorage.setItem('fmos_mfg_job_id', String(jobId || ''));
    localStorage.setItem('fmos_mfg_package_ids', JSON.stringify(packageIds || []));
    if (packageIds?.[0]) localStorage.setItem('fmos_mfg_id', String(packageIds[0]));
  };

  const storedPackageIds = () => {
    try {
      const raw = JSON.parse(localStorage.getItem('fmos_mfg_package_ids') || '[]');
      if (Array.isArray(raw) && raw.length) return raw.map(Number);
    } catch {
      /* ignore */
    }
    const one = localStorage.getItem('fmos_mfg_id');
    return one ? [Number(one)] : [];
  };

  const cutlistTableHtml = (data) => {
    const cols = [
      ['description', 'Description'],
      ['finishing_length_mm', 'Finish L'],
      ['finishing_width_mm', 'Finish W'],
      ['cutting_length_mm', 'Cut L'],
      ['cutting_width_mm', 'Cut W'],
      ['thickness_mm', 'Thk'],
      ['quantity', 'Qty'],
      ['material_name', 'Material'],
      ['colour', 'Colour'],
      ['note', 'Note'],
    ];
    const rows = (data.items || []).map((r) => `<tr>${cols.map(([k]) => `<td>${r[k] ?? ''}</td>`).join('')}</tr>`).join('');
    const hw = (data.hardware || []).map((r) => `<tr>
      <td>${r.description || ''}</td><td></td><td></td><td></td><td></td><td></td>
      <td>${r.quantity ?? ''}</td><td>Hardware</td><td></td><td>${r.note || ''}</td>
    </tr>`).join('');
    return `
      <div class="mfg-cut-block" data-pkg="${data.package_id}">
        <h4>Cutlist · ${data.furniture_code || data.package_id}</h4>
        <p class="muted">${data.furniture_name || ''} · ${(data.items || []).length} panels · ${(data.hardware || []).length} hardware</p>
        <table><thead><tr>${cols.map(([, label]) => `<th>${label}</th>`).join('')}</tr></thead>
        <tbody>${rows || '<tr><td colspan="10" class="muted">No panels</td></tr>'}${hw}</tbody></table>
      </div>`;
  };

  const renderAllCutlists = (blocks) => {
    lastCutlists = blocks || [];
    if (!lastCutlists.length) {
      document.getElementById('cutlist-wrap').innerHTML = '<p class="muted">No cutlists</p>';
      return;
    }
    const totalPanels = lastCutlists.reduce((n, d) => n + (d.items || []).length, 0);
    const totalHw = lastCutlists.reduce((n, d) => n + (d.hardware || []).length, 0);
    const codes = lastCutlists.map((d) => d.furniture_code || d.package_id).join(', ');
    document.getElementById('cutlist-wrap').innerHTML = `
      <h3>Cutlist · ${lastCutlists.length} unit${lastCutlists.length > 1 ? 's' : ''}</h3>
      <p class="muted">${codes} · ${totalPanels} panels · ${totalHw} hardware total</p>
      ${lastCutlists.map(cutlistTableHtml).join('')}`;
  };

  document.getElementById('validate-sel').onclick = async () => {
    err.textContent = '';
    const ids = selectedIds();
    if (!ids.length) { err.textContent = 'Select furniture'; return; }
    const results = [];
    for (const id of ids) {
      results.push((await api.post(`/api/v1/furniture/instances/${id}/validate`, {})).data);
    }
    const cards = results.map((r) => {
      const badge = r.ok ? '<span class="badge ok-badge">OK</span>' : '<span class="badge err-badge">Issues</span>';
      const issues = (r.issues || []).map((i) =>
        `<li class="sev-${(i.severity || '').toLowerCase()}"><strong>${i.severity}</strong> ${i.message}</li>`
      ).join('') || '<li class="muted">No issues</li>';
      return `<div class="mfg-result">
        <div class="toolbar" style="margin:0">
          <strong>${r.summary?.code || r.furniture?.code || r.furniture_id}</strong>
          ${badge}
          <span class="muted">${r.summary?.parts ?? 0} parts · sheet ${r.sheet?.code || ''}</span>
        </div>
        <ul class="mfg-issues">${issues}</ul>
      </div>`;
    }).join('');
    document.getElementById('cutlist-wrap').innerHTML = `<h3>Validation</h3>${cards}`;
    out.textContent = JSON.stringify(results.map((r) => ({
      furniture_id: r.furniture_id,
      ok: r.ok,
      summary: r.summary,
      issues: r.issues,
      sheet: r.sheet?.code,
    })), null, 2);
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
      const jobFurniture = res.data.furniture || [];
      const packages = jobFurniture.map((f) => Number(f.manufacturing_package_id)).filter(Boolean);
      storePackages(res.data.id, packages);
      out.textContent = JSON.stringify({
        job_id: res.data.id,
        status: res.data.status,
        packages: jobFurniture.map((f) => ({
          furniture_id: f.furniture_id,
          manufacturing_package_id: f.manufacturing_package_id,
          status: f.status,
        })),
      }, null, 2);
      if (!packages.length) {
        err.textContent = 'No manufacturing packages created.';
        return;
      }
      const blocks = [];
      for (const pkgId of packages) {
        blocks.push((await api.get(`/api/v1/manufacturing/${pkgId}/cutlist`)).data);
      }
      renderAllCutlists(blocks);
    } catch (e) {
      err.textContent = e.message || 'Generate failed';
      out.textContent = JSON.stringify(e.payload || {}, null, 2);
    }
  };

  document.getElementById('release-mfg').onclick = async () => {
    err.textContent = '';
    const pkgIds = storedPackageIds();
    if (!pkgIds.length) { err.textContent = 'Generate a manufacturing package first.'; return; }
    try {
      const released = [];
      for (const id of pkgIds) {
        released.push((await api.post(`/api/v1/manufacturing/${id}/release`, {})).data);
      }
      out.textContent = JSON.stringify(released.map((p) => ({ id: p.id, status: p.status, furniture_id: p.furniture_id })), null, 2);
    } catch (e) {
      err.textContent = e.message || 'Release failed';
    }
  };

  document.getElementById('export-cut').onclick = async () => {
    err.textContent = '';
    const jobId = localStorage.getItem('fmos_mfg_job_id');
    const pkgIds = storedPackageIds();
    if (!jobId && !pkgIds.length) {
      err.textContent = 'Generate manufacturing for selected furniture first.';
      return;
    }
    try {
      let res;
      if (jobId) {
        res = await api.post(`/api/v1/manufacturing/jobs/${jobId}/cutlist/export`, {});
      } else {
        res = await api.post('/api/v1/manufacturing/cutlist/export', { package_ids: pkgIds });
      }
      const blob = new Blob([res.data.content], { type: 'text/csv' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = res.data.filename;
      a.click();
      out.textContent = JSON.stringify({
        filename: res.data.filename,
        furniture_codes: res.data.furniture_codes,
        row_count: res.data.row_count,
        package_ids: res.data.package_ids,
      }, null, 2);
    } catch (e) {
      err.textContent = e.message || 'Export failed';
    }
  };

  loadFurniture();
}
