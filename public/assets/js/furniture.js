import { api } from './api.js';

export async function mountFurniture(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><p class="muted">Select a project first.</p></div>`;
    return;
  }
  const project = await api.get(`/api/v1/projects/${projectId}`);
  const room = project.data.buildings[0].floors[0].rooms[0];
  main.innerHTML = `<div class="panel"><h2>Parametric Furniture</h2>
    <div class="toolbar">
      <button id="add-wardrobe">Add Wardrobe 2400</button>
      <button id="resize" class="secondary">Resize to 2700</button>
    </div>
    <div id="furn-list"></div></div>`;
  const refresh = async () => {
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    localStorage.setItem('fmos_furniture_id', res.data[0]?.id || '');
    document.getElementById('furn-list').innerHTML = `<pre>${JSON.stringify(res.data, null, 2)}</pre>`;
  };
  document.getElementById('add-wardrobe').onclick = async () => {
    await api.post('/api/v1/furniture/instances', {
      template_code: 'WARDROBE',
      project_id: Number(projectId),
      room_id: room.id,
      name: 'Master Wardrobe',
      parameters: { width: 2400, height: 2400, depth: 600, carcass_thickness: 18, back_thickness: 6, shelf_count: 3, shutter_count: 2 },
      position: { x: 100, y: 100, z: 0, rotation: 0 },
    });
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
