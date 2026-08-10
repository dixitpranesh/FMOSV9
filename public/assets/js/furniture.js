import { api } from './api.js';

export async function mountFurniture(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><p class="muted">Select a project first.</p></div>`;
    return;
  }
  const project = await api.get(`/api/v1/projects/${projectId}`);
  const mode = project.data.model_mode || 'FURNITURE_FIRST';
  const room = project.data.buildings?.[0]?.floors?.[0]?.rooms?.[0] || null;
  main.innerHTML = `<div class="panel">
    <h2>Furniture <span class="muted" style="font-size:.85rem">(${mode})</span></h2>
    <div class="toolbar">
      <label>Code <input id="furn-code" placeholder="auto" style="width:9rem"></label>
      <label>Qty <input id="furn-qty" type="number" min="1" value="1" style="width:4rem"></label>
      <button id="add-wardrobe">Add Wardrobe 2400</button>
      <button id="resize" class="secondary">Resize to 2700</button>
    </div>
    <div id="furn-list"></div></div>`;
  const refresh = async () => {
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    localStorage.setItem('fmos_furniture_id', res.data[0]?.id || '');
    const rows = (res.data || []).map((f) => {
      const comps = f.component_rows?.length ?? f.components?.length ?? 0;
      return `<tr>
        <td>${f.code || ''}</td>
        <td>${f.name || ''}</td>
        <td>${f.quantity ?? 1}</td>
        <td>${f.width_mm || ''}×${f.height_mm || ''}×${f.depth_mm || ''}</td>
        <td>${f.room_id ?? '—'}</td>
        <td>${comps}</td>
      </tr>`;
    }).join('');
    document.getElementById('furn-list').innerHTML = `
      <table class="data">
        <thead><tr><th>Code</th><th>Name</th><th>Qty</th><th>W×H×D</th><th>Room</th><th>Components</th></tr></thead>
        <tbody>${rows || '<tr><td colspan="6" class="muted">No furniture yet</td></tr>'}</tbody>
      </table>`;
  };
  document.getElementById('add-wardrobe').onclick = async () => {
    const payload = {
      template_code: 'WARDROBE',
      project_id: Number(projectId),
      name: 'Master Wardrobe',
      code: document.getElementById('furn-code').value || undefined,
      quantity: Number(document.getElementById('furn-qty').value || 1),
      parameters: { width: 2400, height: 2400, depth: 600, carcass_thickness: 18, back_thickness: 6, shelf_count: 3, shutter_count: 2 },
      position: { x: 100, y: 100, z: 0, rotation: 0 },
    };
    // LEGACY projects may still attach to room; furniture-first does not require it
    if (mode === 'LEGACY' && room) {
      payload.room_id = room.id;
    }
    await api.post('/api/v1/furniture/instances', payload);
    refresh();
  };
  document.getElementById('resize').onclick = async () => {
    const id = localStorage.getItem('fmos_furniture_id');
    if (!id) return;
    await api.put(`/api/v1/furniture/instances/${id}/parameters`, { parameters: { width: 2700 } });
    refresh();
  };
  refresh();
}
