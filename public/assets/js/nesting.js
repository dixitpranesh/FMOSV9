import { api } from './api.js';

export async function mountNesting(main) {
  const mfgId = localStorage.getItem('fmos_mfg_id');
  main.innerHTML = `<div class="panel"><h2>Nesting & Panel Labels</h2>
    <p class="muted">Package ${mfgId || '—'}</p>
    <div class="toolbar">
      <button id="run-nest">Run Nesting</button>
      <button id="labels" class="secondary">Generate Labels</button>
      <button id="reopt" class="secondary">Re-optimize (keep locks)</button>
    </div>
    <canvas id="nest-canvas" width="900" height="480" style="width:100%;border:1px solid #d7dee5;background:#fff"></canvas>
    <pre id="nest-out"></pre></div>`;

  let nestId = localStorage.getItem('fmos_nest_id');
  let nestData = null;

  const drawNest = (nest) => {
    nestData = nest;
    const canvas = document.getElementById('nest-canvas');
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const sheet = nest.layout?.sheet || { length_mm: nest.sheet_length_mm, width_mm: nest.sheet_width_mm };
    const sheet0 = nest.layout?.sheets?.[0];
    if (!sheet0) return;
    const scale = Math.min((canvas.width - 40) / sheet.length_mm, (canvas.height - 40) / sheet.width_mm);
    const ox = 20; const oy = 20;
    ctx.strokeStyle = '#1c4b8f';
    ctx.lineWidth = 2;
    ctx.strokeRect(ox, oy, sheet.length_mm * scale, sheet.width_mm * scale);
    ctx.setLineDash([4, 4]);
    ctx.strokeStyle = '#99a';
    const margin = nest.layout?.margin_mm || 10;
    ctx.strokeRect(ox + margin * scale, oy + margin * scale, (sheet.length_mm - 2 * margin) * scale, (sheet.width_mm - 2 * margin) * scale);
    ctx.setLineDash([]);
    sheet0.placements.forEach((p) => {
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
      ctx.fillText(p.name || p.public_id, x + 4, y + 14);
      ctx.fillText(`${p.length_mm}×${p.width_mm}×${p.thickness_mm || ''}`, x + 4, y + 28);
    });
    ctx.fillStyle = '#445';
    ctx.fillText(`Sheets: ${nest.sheet_count} · Waste: ${nest.waste_percent}% · Util: ${nest.layout?.utilization_percent ?? ''}%`, 20, canvas.height - 10);
  };

  document.getElementById('run-nest').onclick = async () => {
    const res = await api.post(`/api/v1/manufacturing/${mfgId}/nest`, {});
    nestId = res.data.id;
    localStorage.setItem('fmos_nest_id', String(nestId));
    document.getElementById('nest-out').textContent = JSON.stringify(res.data, null, 2);
    drawNest(res.data);
  };
  document.getElementById('labels').onclick = async () => {
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

  // Click to lock/toggle first placement (manual override MVP)
  document.getElementById('nest-canvas').onclick = async (ev) => {
    if (!nestData?.layout?.sheets?.[0]?.placements?.length || !nestId) return;
    const p = nestData.layout.sheets[0].placements[0];
    try {
      const res = await api.put(`/api/v1/nesting/${nestId}/placement`, {
        panel_id: p.panel_id,
        instance: p.instance || 0,
        sheet_index: 0,
        x: p.x,
        y: p.y,
        length_mm: p.length_mm,
        width_mm: p.width_mm,
        locked: !p.locked,
      });
      document.getElementById('nest-out').textContent = 'Toggled lock on first panel\n' + JSON.stringify(res.data, null, 2);
      drawNest(res.data);
    } catch (e) {
      document.getElementById('nest-out').textContent = e.message;
    }
  };
}
