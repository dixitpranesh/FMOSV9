import { api } from './api.js';

export async function mountDesigner(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><h2>Designer</h2><p class="muted">Open a project from Projects first.</p></div>`;
    return;
  }
  const project = await api.get(`/api/v1/projects/${projectId}`);
  const room = project.data.buildings?.[0]?.floors?.[0]?.rooms?.[0];
  if (!room) {
    main.innerHTML = `<div class="panel error">No room found</div>`;
    return;
  }

  main.innerHTML = `
    <div class="panel">
      <h2>2D / 3D Designer · ${project.data.name} · ${room.name}</h2>
      <div class="toolbar">
        <button id="add-wall">Add Wall</button>
        <button id="add-door" class="secondary">Add Door</button>
        <button id="reload" class="secondary">Reload</button>
      </div>
      <div class="grid grid-2">
        <div class="canvas-wrap"><canvas id="c2d" width="640" height="400"></canvas></div>
        <div class="canvas-wrap" id="view3d"></div>
      </div>
      <pre id="design-log" class="muted"></pre>
    </div>`;

  const canvas = document.getElementById('c2d');
  const ctx = canvas.getContext('2d');
  let objects = [];

  async function load() {
    const res = await api.get(`/api/v1/rooms/${room.id}/design`);
    objects = res.data;
    draw();
    render3d();
    document.getElementById('design-log').textContent = JSON.stringify(objects.map(o => ({ id: o.id, type: o.object_type, g: o.geometry })), null, 2);
  }

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.strokeStyle = '#c5ced8';
    for (let x = 0; x < canvas.width; x += 20) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, canvas.height); ctx.stroke(); }
    for (let y = 0; y < canvas.height; y += 20) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvas.width, y); ctx.stroke(); }
    // CAD Y = plan depth (screen Y)
    objects.forEach(o => {
      const g = o.geometry || {};
      ctx.strokeStyle = o.object_type === 'DOOR' ? '#0f6a5a' : '#1c2430';
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.moveTo((g.x1 || 0) / 10, (g.y1 || 0) / 10);
      ctx.lineTo((g.x2 || 0) / 10, (g.y2 || 0) / 10);
      ctx.stroke();
    });
  }

  async function render3d() {
    const host = document.getElementById('view3d');
    host.innerHTML = '';
    if (!window.THREE) {
      await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/three@0.160.0/build/three.min.js';
        s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
      });
    }
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf4f6f8);
    const camera = new THREE.PerspectiveCamera(50, host.clientWidth / 360, 0.1, 10000);
    // 3D Y = elevation; CAD Y -> 3D Z
    camera.position.set(2000, 1800, 2500);
    camera.lookAt(0, 0, 0);
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(host.clientWidth, 360);
    host.appendChild(renderer.domElement);
    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
    const light = new THREE.DirectionalLight(0xffffff, 0.6);
    light.position.set(1000, 2000, 1000);
    scene.add(light);
    objects.forEach(o => {
      const g = o.geometry || {};
      const len = Math.hypot((g.x2 || 0) - (g.x1 || 0), (g.y2 || 0) - (g.y1 || 0)) || 1000;
      const mesh = new THREE.Mesh(
        new THREE.BoxGeometry(len, g.height || 3000, g.thickness || 100),
        new THREE.MeshStandardMaterial({ color: o.object_type === 'DOOR' ? 0x0f6a5a : 0x8899aa })
      );
      mesh.position.set(((g.x1 || 0) + (g.x2 || 0)) / 2, (g.height || 3000) / 2, ((g.y1 || 0) + (g.y2 || 0)) / 2);
      scene.add(mesh);
    });
    renderer.render(scene, camera);
  }

  document.getElementById('add-wall').onclick = async () => {
    await api.post('/api/v1/design/objects', {
      project_id: Number(projectId),
      room_id: room.id,
      object_type: 'WALL',
      name: 'Wall',
      geometry: { x1: 0, y1: 0, x2: 4000, y2: 0, thickness: 100, height: 3000 },
      parameters: {},
    });
    load();
  };
  document.getElementById('add-door').onclick = async () => {
    await api.post('/api/v1/design/objects', {
      project_id: Number(projectId),
      room_id: room.id,
      object_type: 'DOOR',
      name: 'Door',
      geometry: { x1: 1000, y1: 0, x2: 1900, y2: 0, thickness: 40, height: 2100 },
      parameters: { swing: 'LEFT' },
    });
    load();
  };
  document.getElementById('reload').onclick = load;
  load();
}
