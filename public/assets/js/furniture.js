import { api } from './api.js';

function finishThumb(m) {
  if (!m) return '';
  const url = m.assets?.find((a) => a.is_primary == 1)?.public_url || m.assets?.[0]?.public_url || '';
  return url ? `<img src="${url}" alt="${m.sku}" class="finish-thumb" />` : '';
}

export async function mountFurniture(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><p class="muted">Select a project first.</p></div>`;
    return;
  }
  const project = await api.get(`/api/v1/projects/${projectId}`);
  const mode = project.data.model_mode || 'FURNITURE_FIRST';
  const room = project.data.buildings?.[0]?.floors?.[0]?.rooms?.[0] || null;
  const laminatesRes = await api.get('/api/v1/materials?category=LAMINATE');
  const laminates = laminatesRes.data || [];
  const lamOptions = laminates.map((m) => `<option value="${m.id}">${m.sku} (${m.series_name || m.series_code || ''})</option>`).join('');

  main.innerHTML = `<div class="panel">
    <h2>Furniture <span class="muted" style="font-size:.85rem">(${mode})</span></h2>
    <div class="toolbar">
      <label>Code <input id="furn-code" placeholder="auto" style="width:9rem"></label>
      <label>Qty <input id="furn-qty" type="number" min="1" value="1" style="width:4rem"></label>
      <button id="add-wardrobe">Add Wardrobe 2400</button>
      <button id="resize" class="secondary">Resize to 2700</button>
    </div>
    <div id="furn-list"></div>
    <div id="furn-spec" class="panel" style="margin-top:1rem;display:none">
      <h3>Specification</h3>
      <p class="muted" id="spec-title"></p>
      <div class="grid grid-2">
        <div>
          <label>Exterior laminate</label>
          <select id="spec-exterior"><option value="">— none —</option>${lamOptions}</select>
          <div id="spec-exterior-preview" class="finish-preview"></div>
        </div>
        <div>
          <label>Interior laminate</label>
          <select id="spec-interior"><option value="">— none —</option>${lamOptions}</select>
          <div id="spec-interior-preview" class="finish-preview"></div>
        </div>
        <div>
          <label>Carcass notes</label>
          <input id="spec-notes" placeholder="optional" />
        </div>
        <div style="align-self:end"><button id="save-spec">Save specification</button></div>
      </div>
      <p id="spec-msg" class="muted"></p>
    </div>
    <div id="furn-components" class="panel" style="margin-top:1rem;display:none">
      <h3>Components</h3>
      <div id="comp-list"></div>
    </div>
    <div id="furn-views" class="panel" style="margin-top:1rem;display:none">
      <h3>2D / 3D</h3>
      <div class="toolbar">
        <select id="view2d">
          <option>FRONT</option><option>INTERNAL</option><option>PLAN</option><option>LEFT</option><option>RIGHT</option><option>BACK</option><option>SECTION</option>
        </select>
        <button id="reload-views" class="secondary">Reload views</button>
        <button id="export-design" class="secondary">Export design HTML</button>
        <button id="capture-3d" class="secondary">Capture 3D PNG</button>
      </div>
      <div class="grid grid-2">
        <div class="canvas-wrap"><canvas id="furn-2d" width="640" height="420"></canvas></div>
        <div class="canvas-wrap" id="furn-3d" style="height:420px"></div>
      </div>
    </div>
  </div>`;

  let selectedId = localStorage.getItem('fmos_furniture_id') || '';

  const byId = Object.fromEntries(laminates.map((m) => [String(m.id), m]));

  const paintPreview = (selectId, previewId) => {
    const id = document.getElementById(selectId).value;
    const m = byId[id];
    document.getElementById(previewId).innerHTML = m
      ? `${finishThumb(m)}<span>${m.sku} · ${m.series_name || ''}</span>`
      : '<span class="muted">No finish</span>';
  };

  document.getElementById('spec-exterior').onchange = () => paintPreview('spec-exterior', 'spec-exterior-preview');
  document.getElementById('spec-interior').onchange = () => paintPreview('spec-interior', 'spec-interior-preview');

  const renderComponents = async (furnitureId) => {
    const res = await api.get(`/api/v1/furniture/instances/${furnitureId}/components`);
    const rows = (res.data || []).map((c) => {
      const finishSku = c.finish_id ? (byId[String(c.finish_id)]?.sku || c.finish_id) : '—';
      return `<tr>
        <td>${c.component_key}</td>
        <td>${c.name}</td>
        <td>${c.component_type}</td>
        <td>${c.length_mm}×${c.width_mm}×${c.thickness_mm}</td>
        <td>${c.quantity}</td>
        <td>${finishSku}</td>
        <td>
          <select data-cid="${c.id}" class="comp-finish">
            <option value="">default</option>
            ${lamOptions}
          </select>
        </td>
      </tr>`;
    }).join('');
    document.getElementById('furn-components').style.display = 'block';
    document.getElementById('comp-list').innerHTML = `
      <table><thead><tr><th>Key</th><th>Name</th><th>Type</th><th>Size</th><th>Qty</th><th>Finish</th><th>Override</th></tr></thead>
      <tbody>${rows}</tbody></table>`;
    document.querySelectorAll('.comp-finish').forEach((sel) => {
      const row = (res.data || []).find((c) => String(c.id) === sel.dataset.cid);
      if (row?.finish_id) sel.value = String(row.finish_id);
      sel.onchange = async () => {
        await api.put(`/api/v1/furniture/instances/${furnitureId}/components/${sel.dataset.cid}`, {
          finish_id: sel.value ? Number(sel.value) : null,
        });
        renderComponents(furnitureId);
      };
    });
  };

  const draw2d = async (furnitureId) => {
    const view = document.getElementById('view2d').value;
    const res = await api.get(`/api/v1/furniture/instances/${furnitureId}/2d?view=${view}`);
    const d = res.data;
    const canvas = document.getElementById('furn-2d');
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    const bw = Math.max(1, d.bounds.width || 1);
    const bh = Math.max(1, view === 'PLAN' ? d.bounds.depth : d.bounds.height);
    const scale = Math.min((canvas.width - 80) / bw, (canvas.height - 80) / bh);
    const ox = 50;
    const oy = 40;
    const mapX = (x) => ox + x * scale;
    const mapY = (y) => oy + y * scale;
    d.elements.forEach((el) => {
      ctx.strokeStyle = el.role === 'shutter' ? '#0f6a5a' : '#1c2430';
      ctx.lineWidth = el.role === 'inner' ? 1 : 2;
      if (el.type === 'rect') {
        ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
      } else if (el.type === 'line') {
        ctx.beginPath();
        ctx.moveTo(mapX(el.x1), mapY(el.y1));
        ctx.lineTo(mapX(el.x2), mapY(el.y2));
        ctx.stroke();
      }
    });
    ctx.fillStyle = '#c00';
    ctx.font = '12px sans-serif';
    d.dimensions.forEach((dim) => {
      const x = mapX((dim.from[0] + dim.to[0]) / 2);
      const y = mapY((dim.from[1] + dim.to[1]) / 2);
      ctx.fillText(String(dim.label), x, Math.max(12, y));
    });
    ctx.fillStyle = '#445';
    ctx.fillText(`${d.title_block.furniture} · ${d.title_block.code || ''} · Rev ${d.title_block.revision}`, 12, canvas.height - 10);
  };

  let renderer3d = null;
  const draw3d = async (furnitureId) => {
    const host = document.getElementById('furn-3d');
    host.innerHTML = '';
    if (!window.THREE) {
      await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/three@0.160.0/build/three.min.js';
        s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
      });
    }
    const model = await api.get(`/api/v1/furniture/instances/${furnitureId}/3d-model`);
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf4f6f8);
    const camera = new THREE.PerspectiveCamera(45, host.clientWidth / 420, 0.1, 20000);
    const b = model.data.bounds;
    camera.position.set(b.width * 1.2, b.height * 0.9, b.depth * 2.2);
    camera.lookAt(b.width / 2, b.height / 2, b.depth / 2);
    renderer3d = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer3d.setSize(host.clientWidth, 420);
    host.appendChild(renderer3d.domElement);
    scene.add(new THREE.AmbientLight(0xffffff, 0.85));
    const light = new THREE.DirectionalLight(0xffffff, 0.65);
    light.position.set(b.width, b.height * 2, b.depth * 2);
    scene.add(light);
    const loader = new THREE.TextureLoader();
    for (const mesh of model.data.meshes) {
      let material;
      if (mesh.finish?.texture_url) {
        const tex = loader.load(mesh.finish.texture_url);
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.repeat.set(Math.max(1, mesh.size[0] / 400), Math.max(1, mesh.size[1] / 400));
        material = new THREE.MeshStandardMaterial({
          map: tex,
          roughness: mesh.finish.roughness ?? 0.55,
          metalness: mesh.finish.metalness ?? 0,
        });
      } else {
        material = new THREE.MeshStandardMaterial({ color: mesh.color || '#d7dee5', roughness: 0.7 });
      }
      const box = new THREE.Mesh(new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]), material);
      box.position.set(mesh.position[0], mesh.position[1], mesh.position[2]);
      scene.add(box);
    }
    renderer3d.render(scene, camera);
  };

  const openSpec = async (id) => {
    selectedId = String(id);
    localStorage.setItem('fmos_furniture_id', selectedId);
    const res = await api.get(`/api/v1/furniture/instances/${selectedId}`);
    const f = res.data;
    document.getElementById('furn-spec').style.display = 'block';
    document.getElementById('furn-views').style.display = 'block';
    document.getElementById('spec-title').textContent = `${f.code || ''} · ${f.name} · ${f.width_mm}×${f.height_mm}×${f.depth_mm} mm`;
    document.getElementById('spec-exterior').value = f.exterior_finish_id || '';
    document.getElementById('spec-interior').value = f.interior_finish_id || '';
    document.getElementById('spec-notes').value = f.specification?.notes || '';
    paintPreview('spec-exterior', 'spec-exterior-preview');
    paintPreview('spec-interior', 'spec-interior-preview');
    document.getElementById('spec-msg').textContent = '';
    await renderComponents(selectedId);
    await draw2d(selectedId);
    await draw3d(selectedId);
  };

  const refresh = async () => {
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    if (!selectedId && res.data[0]?.id) selectedId = String(res.data[0].id);
    localStorage.setItem('fmos_furniture_id', selectedId || '');
    const rows = (res.data || []).map((f) => {
      const comps = f.component_rows?.length ?? f.components?.length ?? 0;
      const ext = f.exterior_finish_id ? (byId[String(f.exterior_finish_id)]?.sku || f.exterior_finish_id) : '—';
      return `<tr class="${String(f.id) === selectedId ? 'row-active' : ''}">
        <td>${f.code || ''}</td>
        <td>${f.name || ''}</td>
        <td>${f.quantity ?? 1}</td>
        <td>${f.width_mm || ''}×${f.height_mm || ''}×${f.depth_mm || ''}</td>
        <td>${ext}</td>
        <td>${f.room_id ?? '—'}</td>
        <td>${comps}</td>
        <td><button data-id="${f.id}" class="open-spec secondary">Spec</button></td>
      </tr>`;
    }).join('');
    document.getElementById('furn-list').innerHTML = `
      <table class="data">
        <thead><tr><th>Code</th><th>Name</th><th>Qty</th><th>W×H×D</th><th>Exterior</th><th>Room</th><th>Components</th><th></th></tr></thead>
        <tbody>${rows || '<tr><td colspan="8" class="muted">No furniture yet</td></tr>'}</tbody>
      </table>`;
    document.querySelectorAll('.open-spec').forEach((btn) => {
      btn.onclick = () => openSpec(btn.dataset.id);
    });
    if (selectedId) openSpec(selectedId);
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
    if (mode === 'LEGACY' && room) payload.room_id = room.id;
    const created = await api.post('/api/v1/furniture/instances', payload);
    selectedId = String(created.data.id);
    refresh();
  };
  document.getElementById('resize').onclick = async () => {
    if (!selectedId) return;
    await api.put(`/api/v1/furniture/instances/${selectedId}/parameters`, { parameters: { width: 2700 } });
    refresh();
  };
  document.getElementById('save-spec').onclick = async () => {
    if (!selectedId) return;
    const exterior = document.getElementById('spec-exterior').value;
    const interior = document.getElementById('spec-interior').value;
    await api.put(`/api/v1/furniture/instances/${selectedId}/specification`, {
      exterior_finish_id: exterior ? Number(exterior) : null,
      interior_finish_id: interior ? Number(interior) : null,
      specification: { notes: document.getElementById('spec-notes').value || '' },
    });
    document.getElementById('spec-msg').textContent = 'Saved.';
    refresh();
  };
  document.getElementById('view2d').onchange = () => selectedId && draw2d(selectedId);
  document.getElementById('reload-views').onclick = async () => {
    if (!selectedId) return;
    await draw2d(selectedId);
    await draw3d(selectedId);
  };
  document.getElementById('export-design').onclick = async () => {
    if (!selectedId) return;
    const view = document.getElementById('view2d').value;
    const res = await api.post(`/api/v1/furniture/instances/${selectedId}/export/design`, { view });
    const blob = new Blob([res.data.content], { type: 'text/html' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = res.data.filename;
    a.click();
  };
  document.getElementById('capture-3d').onclick = () => {
    if (!renderer3d) return;
    const a = document.createElement('a');
    a.href = renderer3d.domElement.toDataURL('image/png');
    a.download = `furniture-${selectedId}-3d.png`;
    a.click();
  };

  refresh();
}
