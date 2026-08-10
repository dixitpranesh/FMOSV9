import { api } from './api.js';

async function listTable(main, title, rows, columns) {
  main.innerHTML = `<div class="panel"><h2>${title}</h2>
    <table><thead><tr>${columns.map(c => `<th>${c}</th>`).join('')}</tr></thead>
    <tbody>${rows.map(r => `<tr>${columns.map(c => `<td>${r[c] ?? ''}</td>`).join('')}</tr>`).join('') || '<tr><td colspan="99" class="muted">No data</td></tr>'}
    </tbody></table></div>`;
}

export const pages = {
  async dashboard(main) {
    try {
      const health = await api.get('/api/v1/health');
      main.innerHTML = `<div class="panel"><h2>Dashboard</h2>
        <p>Status: <span class="ok">${health.data.status}</span></p>
        <p class="muted">Phase marker: ${health.data.phase || 'n/a'}</p>
        <p>Use the navigation to complete the design-to-cut MVP journey.</p></div>`;
    } catch (e) {
      main.innerHTML = `<div class="panel error">${e.message}</div>`;
    }
  },
  async organizations(main) {
    const res = await api.get('/api/v1/organizations');
    await listTable(main, 'Organizations', res.data.map(o => ({
      id: o.id, name: o.name, code: o.code, status: o.status,
    })), ['id', 'name', 'code', 'status']);
  },
  async clients(main) {
    main.innerHTML = `<div class="panel"><h2>Clients</h2>
      <form id="client-form" class="grid grid-2">
        <div><label>Name</label><input name="name" required /></div>
        <div><label>Email</label><input name="email" type="email" /></div>
        <div><label>Phone</label><input name="phone" /></div>
        <div><label>Company</label><input name="company" /></div>
        <div><button>Create Client</button></div>
      </form><div id="client-list"></div></div>`;
    const refresh = async () => {
      const res = await api.get('/api/v1/clients');
      document.getElementById('client-list').innerHTML = `<table><thead><tr><th>ID</th><th>Name</th><th>Company</th><th>Email</th></tr></thead>
        <tbody>${res.data.map(c => `<tr><td>${c.id}</td><td>${c.name}</td><td>${c.company || ''}</td><td>${c.email || ''}</td></tr>`).join('')}</tbody></table>`;
    };
    document.getElementById('client-form').onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      await api.post('/api/v1/clients', Object.fromEntries(fd.entries()));
      e.target.reset();
      refresh();
    };
    refresh();
  },
  async projects(main) {
    const clients = await api.get('/api/v1/clients');
    const orgs = await api.get('/api/v1/organizations');
    main.innerHTML = `<div class="panel"><h2>Projects</h2>
      <form id="project-form" class="grid grid-2">
        <div><label>Name</label><input name="name" required /></div>
        <div><label>Organization</label><select name="organization_id">${orgs.data.map(o => `<option value="${o.id}">${o.name}</option>`).join('')}</select></div>
        <div><label>Client</label><select name="client_id">${clients.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('')}</select></div>
        <div><button>Create Project</button></div>
      </form><div id="project-list"></div></div>`;
    const refresh = async () => {
      const res = await api.get('/api/v1/projects');
      document.getElementById('project-list').innerHTML = `<table><thead><tr><th>ID</th><th>Name</th><th>Mode</th><th>Status</th><th>Workflow</th><th>Open</th></tr></thead>
        <tbody>${res.data.map(p => `<tr>
          <td>${p.id}</td>
          <td>${p.name}</td>
          <td><span class="badge">${p.model_mode || 'FURNITURE_FIRST'}</span></td>
          <td>${p.status}</td>
          <td>${p.workflow_stage}</td>
          <td>
            <button data-id="${p.id}" class="open-furniture">Furniture</button>
            <button data-id="${p.id}" class="open-project secondary">Floor</button>
          </td>
        </tr>`).join('')}</tbody></table>`;
      document.querySelectorAll('.open-furniture').forEach(btn => btn.onclick = () => {
        localStorage.setItem('fmos_project_id', btn.dataset.id);
        location.hash = '#furniture';
      });
      document.querySelectorAll('.open-project').forEach(btn => btn.onclick = () => {
        localStorage.setItem('fmos_project_id', btn.dataset.id);
        location.hash = '#designer';
      });
    };
    document.getElementById('project-form').onsubmit = async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const body = Object.fromEntries(fd.entries());
      body.organization_id = Number(body.organization_id);
      body.client_id = Number(body.client_id);
      await api.post('/api/v1/projects', body);
      refresh();
    };
    refresh();
  },
  async designer(main) {
    const { mountDesigner } = await import('./designer.js');
    mountDesigner(main);
  },
  async catalog(main) {
    const { mountCatalog } = await import('./catalog.js');
    mountCatalog(main);
  },
  async furniture(main) {
    const { mountFurniture } = await import('./furniture.js');
    mountFurniture(main);
  },
  async commercial(main) {
    const { mountCommercial } = await import('./commercial.js');
    mountCommercial(main);
  },
  async manufacturing(main) {
    const { mountManufacturing } = await import('./manufacturing.js');
    mountManufacturing(main);
  },
  async nesting(main) {
    const { mountNesting } = await import('./nesting.js');
    mountNesting(main);
  },
};
