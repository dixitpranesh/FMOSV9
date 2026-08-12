import { api } from './api.js';

function packageIds() {
  try {
    const raw = JSON.parse(localStorage.getItem('fmos_mfg_package_ids') || '[]');
    if (Array.isArray(raw) && raw.length) return raw.map(Number);
  } catch {
    /* ignore */
  }
  const one = localStorage.getItem('fmos_mfg_id');
  return one ? [Number(one)] : [];
}

function downloadBase64Pdf(filename, base64) {
  const bin = atob(base64);
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  const blob = new Blob([bytes], { type: 'application/pdf' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}

export async function mountNesting(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  const pkgs = packageIds();
  main.innerHTML = `<div class="panel"><h2>Nesting & Sheet Plan</h2>
    <p class="muted">Project ${projectId || '—'} · Packages: ${pkgs.join(', ') || 'none — generate manufacturing first'}</p>
    <div class="toolbar">
      <button id="run-project-plan">Build project sheet plan</button>
      <button id="download-pdf" class="secondary">Download sheet plan PDF</button>
      <button id="run-nest" class="secondary">Nest current package only</button>
      <button id="labels" class="secondary">Generate Labels</button>
      <button id="reopt" class="secondary">Re-optimize (keep locks)</button>
    </div>
    <div class="toolbar" id="group-bar"></div>
    <canvas id="nest-canvas" width="980" height="520" style="width:100%;border:1px solid #d7dee5;background:#fff"></canvas>
    <pre id="nest-out"></pre></div>`;

  let nestId = localStorage.getItem('fmos_nest_id');
  let nestData = null;
  let projectPlan = null;
  let activeGroup = 0;
  let activeSheet = 0;
  const mfgId = localStorage.getItem('fmos_mfg_id');

  const drawSheet = (sheetMeta, sheetLayout, title) => {
    const canvas = document.getElementById('nest-canvas');
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!sheetLayout) return;
    const sheetL = sheetMeta.length_mm;
    const sheetW = sheetMeta.width_mm;
    const margin = sheetMeta.margin_mm || 10;
    const scale = Math.min((canvas.width - 40) / sheetL, (canvas.height - 60) / sheetW);
    const ox = 20; const oy = 28;
    ctx.fillStyle = '#445';
    ctx.font = '13px sans-serif';
    ctx.fillText(title, 20, 18);
    ctx.strokeStyle = '#1c4b8f';
    ctx.lineWidth = 2;
    ctx.strokeRect(ox, oy, sheetL * scale, sheetW * scale);
    ctx.setLineDash([4, 4]);
    ctx.strokeStyle = '#99a';
    ctx.strokeRect(ox + margin * scale, oy + margin * scale, (sheetL - 2 * margin) * scale, (sheetW - 2 * margin) * scale);
    ctx.setLineDash([]);
    (sheetLayout.placements || []).forEach((p) => {
      ctx.fillStyle = p.locked ? '#ffe8a3' : '#f3e6d0';
      ctx.strokeStyle = '#5b4636';
      const x = ox + p.x * scale;
      const y = oy + p.y * scale;
      const w = p.length_mm * scale;
      const h = p.width_mm * scale;
      ctx.fillRect(x, y, w, h);
      ctx.strokeRect(x, y, w, h);
      ctx.fillStyle = '#222';
      ctx.font = '11px sans-serif';
      ctx.fillText((p.name || p.public_id || '').slice(0, 36), x + 4, y + 14);
      ctx.fillText(`${p.length_mm}×${p.width_mm}×${p.thickness_mm || ''}`, x + 4, y + 28);
      if (p.furniture_code) ctx.fillText(p.furniture_code, x + 4, y + 42);
    });
  };

  const renderGroupBar = () => {
    const bar = document.getElementById('group-bar');
    if (!projectPlan?.groups?.length) {
      bar.innerHTML = '<span class="muted">Build a project sheet plan to preview all laminates.</span>';
      return;
    }
    bar.innerHTML = projectPlan.groups.map((g, i) =>
      `<button class="secondary group-btn ${i === activeGroup ? 'primary' : ''}" data-g="${i}">
        ${g.laminate} · ${g.thickness_mm}mm (${g.sheet_count} sheets)
      </button>`
    ).join('') + `<span class="muted">${projectPlan.totals.sheets} sheets · ${projectPlan.totals.panel_pieces} pieces · ${projectPlan.totals.laminate_groups} laminates</span>`;
    bar.querySelectorAll('.group-btn').forEach((btn) => {
      btn.onclick = () => {
        activeGroup = Number(btn.dataset.g);
        activeSheet = 0;
        renderGroupBar();
        drawActive();
      };
    });
  };

  const drawActive = () => {
    if (!projectPlan?.groups?.[activeGroup]) return;
    const g = projectPlan.groups[activeGroup];
    const sheet = g.sheets[activeSheet] || g.sheets[0];
    drawSheet(
      projectPlan.sheet,
      sheet,
      `${g.laminate} · ${g.thickness_mm}mm · sheet ${activeSheet + 1}/${g.sheet_count}`
    );
    document.getElementById('nest-out').textContent = JSON.stringify({
      laminate: g.laminate,
      thickness_mm: g.thickness_mm,
      sheet: activeSheet + 1,
      sheet_count: g.sheet_count,
      waste_percent: g.waste_percent,
      utilization_percent: g.utilization_percent,
      placements: (sheet?.placements || []).length,
      totals: projectPlan.totals,
    }, null, 2);
  };

  const drawNest = (nest) => {
    nestData = nest;
    const sheet = nest.layout?.sheet || { length_mm: nest.sheet_length_mm, width_mm: nest.sheet_width_mm, margin_mm: nest.layout?.margin_mm || 10 };
    const sheet0 = nest.layout?.sheets?.[0];
    drawSheet(sheet, sheet0, `Package nest · Sheets ${nest.sheet_count} · Waste ${nest.waste_percent}%`);
  };

  document.getElementById('run-project-plan').onclick = async () => {
    if (!projectId) {
      document.getElementById('nest-out').textContent = 'Select a project first.';
      return;
    }
    try {
      const res = await api.post(`/api/v1/projects/${projectId}/nesting/sheet-plan`, {
        package_ids: packageIds(),
      });
      projectPlan = res.data;
      activeGroup = 0;
      activeSheet = 0;
      if (projectPlan.nesting_job_id) {
        nestId = String(projectPlan.nesting_job_id);
        localStorage.setItem('fmos_nest_id', nestId);
      }
      renderGroupBar();
      drawActive();
    } catch (e) {
      document.getElementById('nest-out').textContent = e.message || 'Sheet plan failed';
    }
  };

  document.getElementById('download-pdf').onclick = async () => {
    if (!projectId) {
      document.getElementById('nest-out').textContent = 'Select a project first.';
      return;
    }
    try {
      const res = await api.post(`/api/v1/projects/${projectId}/nesting/sheet-plan/pdf`, {
        package_ids: packageIds(),
      });
      projectPlan = projectPlan || { groups: [], totals: { sheets: res.data.sheet_count } };
      downloadBase64Pdf(res.data.filename, res.data.content_base64);
      document.getElementById('nest-out').textContent = JSON.stringify({
        downloaded: res.data.filename,
        sheet_count: res.data.sheet_count,
        laminate_groups: res.data.laminate_groups,
        path: res.data.path,
      }, null, 2);
    } catch (e) {
      document.getElementById('nest-out').textContent = e.message || 'PDF export failed';
    }
  };

  document.getElementById('run-nest').onclick = async () => {
    if (!mfgId) {
      document.getElementById('nest-out').textContent = 'No current package. Generate manufacturing first.';
      return;
    }
    const res = await api.post(`/api/v1/manufacturing/${mfgId}/nest`, {});
    nestId = res.data.id;
    localStorage.setItem('fmos_nest_id', String(nestId));
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
    drawNest(res.data);
  };

  document.getElementById('labels').onclick = async () => {
    if (!mfgId) return;
    const res = await api.get(`/api/v1/manufacturing/${mfgId}/labels`);
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
  };

  document.getElementById('reopt').onclick = async () => {
    if (!nestId) return;
    const res = await api.post(`/api/v1/nesting/${nestId}/reoptimize`, {});
    nestId = res.data.id;
    localStorage.setItem('fmos_nest_id', String(nestId));
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
    drawNest(res.data);
  };

  // Cycle sheets of active laminate group with canvas click
  document.getElementById('nest-canvas').onclick = () => {
    if (!projectPlan?.groups?.[activeGroup]) return;
    const g = projectPlan.groups[activeGroup];
    activeSheet = (activeSheet + 1) % Math.max(1, g.sheets.length);
    drawActive();
  };

  renderGroupBar();
}
