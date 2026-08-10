import { api } from './api.js';

function finishThumb(m) {
  if (!m) return '';
  const url = m.assets?.find((a) => a.is_primary == 1)?.public_url || m.assets?.[0]?.public_url || '';
  return url ? `<img src="${url}" alt="${m.sku}" class="finish-thumb" />` : '';
}

function clone(v) {
  return JSON.parse(JSON.stringify(v));
}

function defaultSection(type = 'SHELVES') {
  if (type === 'DRAWERS') return { type: 'DRAWERS', height_mm: 600, drawer_count: 3, drawer_height_mm: 180, label: 'Drawers' };
  if (type === 'HANGING') return { type: 'HANGING', height_mm: 1100, label: 'Hanging' };
  if (type === 'OPEN') return { type: 'OPEN', height_mm: null, label: 'Open' };
  return { type: 'SHELVES', height_mm: null, shelf_count: 3, label: 'Shelves' };
}

export async function mountFurniture(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><p class="muted">Select a project first.</p></div>`;
    return;
  }
  const [project, laminatesRes, templatesRes] = await Promise.all([
    api.get(`/api/v1/projects/${projectId}`),
    api.get('/api/v1/materials?category=LAMINATE'),
    api.get('/api/v1/furniture/templates'),
  ]);
  const mode = project.data.model_mode || 'FURNITURE_FIRST';
  const room = project.data.buildings?.[0]?.floors?.[0]?.rooms?.[0] || null;
  const laminates = laminatesRes.data || [];
  const templates = templatesRes.data || [];
  const lamOptions = laminates.map((m) => `<option value="${m.id}">${m.sku} (${m.series_name || m.series_code || ''})</option>`).join('');
  const byId = Object.fromEntries(laminates.map((m) => [String(m.id), m]));
  const templatesByCode = Object.fromEntries(templates.map((t) => [t.code, t]));

  main.innerHTML = `<div class="panel">
    <h2>Furniture <span class="muted" style="font-size:.85rem">(${mode})</span></h2>
    <h3>Add from template</h3>
    <div id="tpl-grid" class="tpl-grid"></div>
    <div class="toolbar" style="margin-top:.75rem">
      <label>Code <input id="furn-code" placeholder="auto" style="width:9rem"></label>
      <label>Qty <input id="furn-qty" type="number" min="1" value="1" style="width:4rem"></label>
      <label>Width <input id="furn-width" type="number" value="2400" style="width:5rem"></label>
      <label>Height <input id="furn-height" type="number" value="2400" style="width:5rem"></label>
      <label>Depth <input id="furn-depth" type="number" value="600" style="width:5rem"></label>
    </div>
    <div id="furn-list"></div>

    <div id="furn-params" class="panel" style="margin-top:1rem;display:none">
      <h3>Dimensions & doors</h3>
      <div class="grid grid-2" id="param-fields"></div>
      <button id="save-params">Apply dimensions</button>
      <span id="params-msg" class="muted"></span>
    </div>

    <div id="furn-layout" class="panel" style="margin-top:1rem;display:none">
      <h3>Internal layout (customizable)</h3>
      <div class="toolbar">
        <label>Plinth mm <input id="lay-plinth" type="number" style="width:5rem"></label>
        <label>Partition mm <input id="lay-part" type="number" style="width:5rem"></label>
        <label>Door
          <select id="lay-door"><option>HINGED</option><option>SLIDING</option><option>NONE</option></select>
        </label>
        <label><input id="lay-loft" type="checkbox"> Loft</label>
        <label>Loft H <input id="lay-loft-h" type="number" style="width:5rem"></label>
        <button id="add-bay" class="secondary">Add bay</button>
        <button id="save-layout">Save layout & regenerate</button>
      </div>
      <div id="bay-editor"></div>
      <p id="layout-msg" class="muted"></p>
    </div>

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
      <h3>Generated components</h3>
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
  let currentFurniture = null;
  let draftLayout = null;
  let renderer3d = null;

  document.getElementById('tpl-grid').innerHTML = templates.map((t) => `
    <button class="tpl-card" data-code="${t.code}">
      <strong>${t.name}</strong>
      <span class="badge">${t.category}</span>
      <span class="muted">${t.description || ''}</span>
    </button>`).join('');

  document.querySelectorAll('.tpl-card').forEach((btn) => {
    btn.onclick = async () => {
      const code = btn.dataset.code;
      const tpl = templatesByCode[code];
      const width = Number(document.getElementById('furn-width').value || tpl.parameters.width?.default || 1200);
      const height = Number(document.getElementById('furn-height').value || tpl.parameters.height?.default || 2100);
      const depth = Number(document.getElementById('furn-depth').value || tpl.parameters.depth?.default || 600);
      const payload = {
        template_code: code,
        project_id: Number(projectId),
        name: tpl.name,
        code: document.getElementById('furn-code').value || undefined,
        quantity: Number(document.getElementById('furn-qty').value || 1),
        parameters: {
          width, height, depth,
          carcass_thickness: tpl.parameters.carcass_thickness?.default ?? 18,
          back_thickness: tpl.parameters.back_thickness?.default ?? 6,
          shutter_count: tpl.parameters.shutter_count?.default ?? 2,
          door_type: tpl.parameters.door_type?.default ?? 'HINGED',
          layout: clone(tpl.parameters.layout?.default || null),
        },
      };
      if (mode === 'LEGACY' && room) payload.room_id = room.id;
      const created = await api.post('/api/v1/furniture/instances', payload);
      selectedId = String(created.data.id);
      await refresh();
    };
  });

  const paintPreview = (selectId, previewId) => {
    const id = document.getElementById(selectId).value;
    const m = byId[id];
    document.getElementById(previewId).innerHTML = m
      ? `${finishThumb(m)}<span>${m.sku} · ${m.series_name || ''}</span>`
      : '<span class="muted">No finish</span>';
  };
  document.getElementById('spec-exterior').onchange = () => paintPreview('spec-exterior', 'spec-exterior-preview');
  document.getElementById('spec-interior').onchange = () => paintPreview('spec-interior', 'spec-interior-preview');

  const renderBayEditor = () => {
    const host = document.getElementById('bay-editor');
    if (!draftLayout) { host.innerHTML = ''; return; }
    host.innerHTML = (draftLayout.bays || []).map((bay, bi) => `
      <div class="bay-card">
        <div class="toolbar">
          <strong>Bay ${bi + 1}</strong>
          <input data-bi="${bi}" class="bay-label" value="${bay.label || ''}" placeholder="Label" />
          <label>Width mm <input data-bi="${bi}" class="bay-width" type="number" placeholder="auto" value="${bay.width_mm ?? ''}" style="width:5rem"></label>
          <button data-bi="${bi}" class="add-sec secondary">Add section</button>
          <button data-bi="${bi}" class="del-bay danger">Remove bay</button>
        </div>
        ${(bay.sections || []).map((sec, si) => `
          <div class="sec-row">
            <select data-bi="${bi}" data-si="${si}" class="sec-type">
              ${['HANGING','SHELVES','DRAWERS','OPEN'].map((t) => `<option ${sec.type === t ? 'selected' : ''}>${t}</option>`).join('')}
            </select>
            <input data-bi="${bi}" data-si="${si}" class="sec-label" value="${sec.label || ''}" placeholder="Label" />
            <label>H <input data-bi="${bi}" data-si="${si}" class="sec-h" type="number" placeholder="auto" value="${sec.height_mm ?? ''}" style="width:4.5rem"></label>
            <label>Shelves <input data-bi="${bi}" data-si="${si}" class="sec-shelves" type="number" value="${sec.shelf_count ?? 0}" style="width:3.5rem"></label>
            <label>Drawers <input data-bi="${bi}" data-si="${si}" class="sec-drawers" type="number" value="${sec.drawer_count ?? 0}" style="width:3.5rem"></label>
            <label>Dr H <input data-bi="${bi}" data-si="${si}" class="sec-drh" type="number" value="${sec.drawer_height_mm ?? 180}" style="width:3.5rem"></label>
            <button data-bi="${bi}" data-si="${si}" class="del-sec secondary">✕</button>
          </div>`).join('')}
      </div>`).join('') || '<p class="muted">No bays — add one.</p>';

    host.querySelectorAll('.bay-label').forEach((el) => el.oninput = () => { draftLayout.bays[el.dataset.bi].label = el.value; });
    host.querySelectorAll('.bay-width').forEach((el) => el.oninput = () => {
      draftLayout.bays[el.dataset.bi].width_mm = el.value === '' ? null : Number(el.value);
    });
    host.querySelectorAll('.add-sec').forEach((el) => el.onclick = () => {
      draftLayout.bays[el.dataset.bi].sections.push(defaultSection('SHELVES'));
      renderBayEditor();
    });
    host.querySelectorAll('.del-bay').forEach((el) => el.onclick = () => {
      draftLayout.bays.splice(Number(el.dataset.bi), 1);
      renderBayEditor();
    });
    host.querySelectorAll('.sec-type').forEach((el) => el.onchange = () => {
      const sec = draftLayout.bays[el.dataset.bi].sections[el.dataset.si];
      Object.assign(sec, defaultSection(el.value), { label: sec.label || el.value });
      renderBayEditor();
    });
    host.querySelectorAll('.sec-label').forEach((el) => el.oninput = () => { draftLayout.bays[el.dataset.bi].sections[el.dataset.si].label = el.value; });
    host.querySelectorAll('.sec-h').forEach((el) => el.oninput = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].height_mm = el.value === '' ? null : Number(el.value);
    });
    host.querySelectorAll('.sec-shelves').forEach((el) => el.oninput = () => { draftLayout.bays[el.dataset.bi].sections[el.dataset.si].shelf_count = Number(el.value || 0); });
    host.querySelectorAll('.sec-drawers').forEach((el) => el.oninput = () => { draftLayout.bays[el.dataset.bi].sections[el.dataset.si].drawer_count = Number(el.value || 0); });
    host.querySelectorAll('.sec-drh').forEach((el) => el.oninput = () => { draftLayout.bays[el.dataset.bi].sections[el.dataset.si].drawer_height_mm = Number(el.value || 180); });
    host.querySelectorAll('.del-sec').forEach((el) => el.onclick = () => {
      draftLayout.bays[el.dataset.bi].sections.splice(Number(el.dataset.si), 1);
      renderBayEditor();
    });
  };

  const renderComponents = async (furnitureId) => {
    const res = await api.get(`/api/v1/furniture/instances/${furnitureId}/components`);
    const rows = (res.data || []).map((c) => {
      const finishSku = c.finish_id ? (byId[String(c.finish_id)]?.sku || c.finish_id) : '—';
      return `<tr>
        <td>${c.component_key}</td><td>${c.name}</td><td>${c.component_type}</td>
        <td>${c.length_mm}×${c.width_mm}×${c.thickness_mm}</td><td>${c.quantity}</td><td>${finishSku}</td>
        <td><select data-cid="${c.id}" class="comp-finish"><option value="">default</option>${lamOptions}</select></td>
      </tr>`;
    }).join('');
    document.getElementById('furn-components').style.display = 'block';
    document.getElementById('comp-list').innerHTML = `<table><thead><tr><th>Key</th><th>Name</th><th>Type</th><th>Size</th><th>Qty</th><th>Finish</th><th>Override</th></tr></thead><tbody>${rows}</tbody></table>`;
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
    const ox = 50; const oy = 40;
    const mapX = (x) => ox + x * scale;
    const mapY = (y) => oy + y * scale;
    const colors = { bay: '#dfe9f2', hanging: '#e8f5e9', shelves: '#fff8e1', drawers: '#fce4ec', open: '#f3f5f7', loft: '#e3f2fd', plinth: '#eceff1' };
    d.elements.forEach((el) => {
      if (el.type === 'rect') {
        ctx.fillStyle = colors[el.role] || 'transparent';
        if (colors[el.role]) ctx.fillRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
        ctx.strokeStyle = el.role === 'shutter' ? '#0f6a5a' : '#1c2430';
        ctx.lineWidth = el.role === 'inner' ? 1 : 2;
        ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
        if (el.label && el.w * scale > 40) {
          ctx.fillStyle = '#334';
          ctx.font = '11px sans-serif';
          ctx.fillText(el.label, mapX(el.x) + 4, mapY(el.y) + 14);
        }
      } else if (el.type === 'line') {
        ctx.strokeStyle = '#666';
        ctx.beginPath();
        ctx.moveTo(mapX(el.x1), mapY(el.y1));
        ctx.lineTo(mapX(el.x2), mapY(el.y2));
        ctx.stroke();
      }
    });
    ctx.fillStyle = '#c00';
    ctx.font = '12px sans-serif';
    d.dimensions.forEach((dim) => {
      ctx.fillText(String(dim.label), mapX((dim.from[0] + dim.to[0]) / 2), Math.max(12, mapY((dim.from[1] + dim.to[1]) / 2)));
    });
  };

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
        material = new THREE.MeshStandardMaterial({ map: tex, roughness: mesh.finish.roughness ?? 0.55, metalness: mesh.finish.metalness ?? 0 });
      } else {
        material = new THREE.MeshStandardMaterial({ color: mesh.color || '#d7dee5', roughness: 0.7 });
      }
      const box = new THREE.Mesh(new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]), material);
      box.position.set(mesh.position[0], mesh.position[1], mesh.position[2]);
      scene.add(box);
    }
    renderer3d.render(scene, camera);
  };

  const openFurniture = async (id) => {
    selectedId = String(id);
    localStorage.setItem('fmos_furniture_id', selectedId);
    const res = await api.get(`/api/v1/furniture/instances/${selectedId}`);
    currentFurniture = res.data;
    const f = currentFurniture;
    const p = f.parameters || {};
    draftLayout = clone(p.layout || { plinth_height_mm: 0, partition_thickness_mm: 18, door_type: 'HINGED', loft: { enabled: false, height_mm: 600, shelf_count: 1 }, bays: [] });

    document.getElementById('furn-params').style.display = 'block';
    document.getElementById('furn-layout').style.display = 'block';
    document.getElementById('furn-spec').style.display = 'block';
    document.getElementById('furn-views').style.display = 'block';
    document.getElementById('param-fields').innerHTML = `
      <div><label>Width</label><input id="p-width" type="number" value="${p.width || f.width_mm || ''}"></div>
      <div><label>Height</label><input id="p-height" type="number" value="${p.height || f.height_mm || ''}"></div>
      <div><label>Depth</label><input id="p-depth" type="number" value="${p.depth || f.depth_mm || ''}"></div>
      <div><label>Shutters/Doors</label><input id="p-shutters" type="number" value="${p.shutter_count ?? 2}"></div>
      <div><label>Carcass thk</label><input id="p-cth" type="number" value="${p.carcass_thickness ?? 18}"></div>
      <div><label>Back thk</label><input id="p-bth" type="number" value="${p.back_thickness ?? 6}"></div>`;

    document.getElementById('lay-plinth').value = draftLayout.plinth_height_mm ?? 0;
    document.getElementById('lay-part').value = draftLayout.partition_thickness_mm ?? 18;
    document.getElementById('lay-door').value = draftLayout.door_type || p.door_type || 'HINGED';
    document.getElementById('lay-loft').checked = !!draftLayout.loft?.enabled;
    document.getElementById('lay-loft-h').value = draftLayout.loft?.height_mm ?? 600;
    renderBayEditor();

    document.getElementById('spec-title').textContent = `${f.code || ''} · ${f.name} · ${f.width_mm}×${f.height_mm}×${f.depth_mm} mm · ${(f.component_rows || []).length} parts`;
    document.getElementById('spec-exterior').value = f.exterior_finish_id || '';
    document.getElementById('spec-interior').value = f.interior_finish_id || '';
    document.getElementById('spec-notes').value = f.specification?.notes || '';
    paintPreview('spec-exterior', 'spec-exterior-preview');
    paintPreview('spec-interior', 'spec-interior-preview');
    await renderComponents(selectedId);
    await draw2d(selectedId);
    await draw3d(selectedId);
  };

  const refresh = async () => {
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    if (!selectedId && res.data[0]?.id) selectedId = String(res.data[0].id);
    localStorage.setItem('fmos_furniture_id', selectedId || '');
    const rows = (res.data || []).map((f) => `<tr class="${String(f.id) === selectedId ? 'row-active' : ''}">
      <td>${f.code || ''}</td><td>${f.name || ''}</td><td>${f.type || ''}</td>
      <td>${f.width_mm || ''}×${f.height_mm || ''}×${f.depth_mm || ''}</td>
      <td>${(f.parameters?.layout?.bays || []).length || '—'}</td>
      <td><button data-id="${f.id}" class="open-furn secondary">Open</button></td>
    </tr>`).join('');
    document.getElementById('furn-list').innerHTML = `<table><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>W×H×D</th><th>Bays</th><th></th></tr></thead>
      <tbody>${rows || '<tr><td colspan="6" class="muted">No furniture — pick a template above</td></tr>'}</tbody></table>`;
    document.querySelectorAll('.open-furn').forEach((btn) => { btn.onclick = () => openFurniture(btn.dataset.id); });
    if (selectedId) await openFurniture(selectedId);
  };

  document.getElementById('add-bay').onclick = () => {
    if (!draftLayout) return;
    draftLayout.bays.push({ id: `bay-${Date.now()}`, label: `Bay ${draftLayout.bays.length + 1}`, width_mm: null, sections: [defaultSection('SHELVES')] });
    renderBayEditor();
  };
  document.getElementById('save-layout').onclick = async () => {
    if (!selectedId || !draftLayout) return;
    draftLayout.plinth_height_mm = Number(document.getElementById('lay-plinth').value || 0);
    draftLayout.partition_thickness_mm = Number(document.getElementById('lay-part').value || 18);
    draftLayout.door_type = document.getElementById('lay-door').value;
    draftLayout.loft = {
      enabled: document.getElementById('lay-loft').checked,
      height_mm: Number(document.getElementById('lay-loft-h').value || 600),
      shelf_count: draftLayout.loft?.shelf_count ?? 1,
    };
    await api.put(`/api/v1/furniture/instances/${selectedId}/layout`, { layout: draftLayout });
    await api.put(`/api/v1/furniture/instances/${selectedId}/parameters`, {
      parameters: { door_type: draftLayout.door_type, shutter_count: Number(document.getElementById('p-shutters')?.value || 2) },
    });
    document.getElementById('layout-msg').textContent = 'Layout saved — components regenerated.';
    await refresh();
  };
  document.getElementById('save-params').onclick = async () => {
    if (!selectedId) return;
    await api.put(`/api/v1/furniture/instances/${selectedId}/parameters`, {
      parameters: {
        width: Number(document.getElementById('p-width').value),
        height: Number(document.getElementById('p-height').value),
        depth: Number(document.getElementById('p-depth').value),
        shutter_count: Number(document.getElementById('p-shutters').value),
        carcass_thickness: Number(document.getElementById('p-cth').value),
        back_thickness: Number(document.getElementById('p-bth').value),
      },
    });
    document.getElementById('params-msg').textContent = 'Dimensions applied.';
    await refresh();
  };
  document.getElementById('save-spec').onclick = async () => {
    if (!selectedId) return;
    await api.put(`/api/v1/furniture/instances/${selectedId}/specification`, {
      exterior_finish_id: document.getElementById('spec-exterior').value ? Number(document.getElementById('spec-exterior').value) : null,
      interior_finish_id: document.getElementById('spec-interior').value ? Number(document.getElementById('spec-interior').value) : null,
      specification: { notes: document.getElementById('spec-notes').value || '' },
    });
    document.getElementById('spec-msg').textContent = 'Saved.';
    await refresh();
  };
  document.getElementById('view2d').onchange = () => selectedId && draw2d(selectedId);
  document.getElementById('reload-views').onclick = async () => { if (!selectedId) return; await draw2d(selectedId); await draw3d(selectedId); };
  document.getElementById('export-design').onclick = async () => {
    if (!selectedId) return;
    const res = await api.post(`/api/v1/furniture/instances/${selectedId}/export/design`, { view: document.getElementById('view2d').value });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([res.data.content], { type: 'text/html' }));
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
