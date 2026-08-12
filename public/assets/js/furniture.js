import { api } from './api.js';

function finishUrl(m) {
  if (!m) return '';
  return m.assets?.find((a) => a.is_primary == 1)?.public_url || m.assets?.[0]?.public_url || '';
}

function finishThumb(m, cls = 'finish-thumb') {
  const url = finishUrl(m);
  return url
    ? `<img src="${url}" alt="${m.sku || ''}" class="${cls}" loading="lazy" />`
    : `<span class="${cls} finish-swatch-empty" title="No texture"></span>`;
}

function finishLabel(m) {
  if (!m) return '';
  const series = m.series_name || m.series_code || '';
  return series ? `${m.sku} · ${series}` : String(m.sku || '');
}

/**
 * Custom finish dropdown with laminate color swatch + SKU (native <select> cannot show images).
 * @returns {{ getValue: () => string, setValue: (v: string|number|null) => void, el: HTMLElement }}
 */
function mountFinishSelect(host, {
  laminates,
  value = '',
  emptyLabel = '— none —',
  compact = false,
  onChange = null,
} = {}) {
  const byId = Object.fromEntries((laminates || []).map((m) => [String(m.id), m]));
  let current = value == null || value === '' ? '' : String(value);

  host.className = `finish-select${compact ? ' compact' : ''}`;
  host.innerHTML = `
    <button type="button" class="finish-btn" aria-haspopup="listbox" aria-expanded="false">
      <span class="finish-btn-inner"></span>
      <span class="finish-caret">▾</span>
    </button>
    <div class="finish-menu" role="listbox" hidden></div>`;

  const btn = host.querySelector('.finish-btn');
  const inner = host.querySelector('.finish-btn-inner');
  const menu = host.querySelector('.finish-menu');

  const renderButton = () => {
    const m = current ? byId[current] : null;
    if (!m) {
      inner.innerHTML = `<span class="finish-swatch finish-swatch-empty"></span><span class="muted">${emptyLabel}</span>`;
      return;
    }
    inner.innerHTML = `${finishThumb(m, 'finish-swatch')}<span class="finish-code">${finishLabel(m)}</span>`;
  };

  const renderMenu = () => {
    const opts = [
      `<button type="button" class="finish-opt${!current ? ' selected' : ''}" data-id="" role="option">
        <span class="finish-swatch finish-swatch-empty"></span><span>${emptyLabel}</span>
      </button>`,
      ...(laminates || []).map((m) => {
        const id = String(m.id);
        return `<button type="button" class="finish-opt${id === current ? ' selected' : ''}" data-id="${id}" role="option">
          ${finishThumb(m, 'finish-swatch')}<span class="finish-code">${finishLabel(m)}</span>
        </button>`;
      }),
    ];
    menu.innerHTML = opts.join('');
    menu.querySelectorAll('.finish-opt').forEach((opt) => {
      opt.onclick = (e) => {
        e.stopPropagation();
        current = opt.dataset.id || '';
        host.dataset.value = current;
        close();
        renderButton();
        if (onChange) onChange(current ? Number(current) : null);
      };
    });
  };

  const close = () => {
    host.classList.remove('open');
    menu.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  };

  const open = () => {
    document.querySelectorAll('.finish-select.open').forEach((el) => {
      if (el !== host) {
        el.classList.remove('open');
        const m = el.querySelector('.finish-menu');
        if (m) m.hidden = true;
        const b = el.querySelector('.finish-btn');
        if (b) b.setAttribute('aria-expanded', 'false');
      }
    });
    renderMenu();
    host.classList.add('open');
    menu.hidden = false;
    btn.setAttribute('aria-expanded', 'true');
    const selected = menu.querySelector('.finish-opt.selected');
    if (selected) selected.scrollIntoView({ block: 'nearest' });
  };

  btn.onclick = (e) => {
    e.stopPropagation();
    if (host.classList.contains('open')) close();
    else open();
  };

  if (!mountFinishSelect._docBound) {
    document.addEventListener('click', () => {
      document.querySelectorAll('.finish-select.open').forEach((el) => {
        el.classList.remove('open');
        const m = el.querySelector('.finish-menu');
        if (m) m.hidden = true;
        const b = el.querySelector('.finish-btn');
        if (b) b.setAttribute('aria-expanded', 'false');
      });
    });
    mountFinishSelect._docBound = true;
  }

  host.dataset.value = current;
  renderButton();

  return {
    el: host,
    getValue: () => host.dataset.value || '',
    setValue: (v) => {
      current = v == null || v === '' ? '' : String(v);
      host.dataset.value = current;
      renderButton();
    },
  };
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

function emptyLayout(doorType = 'HINGED') {
  return {
    plinth_height_mm: 0,
    partition_thickness_mm: 18,
    door_type: doorType,
    loft: { enabled: false, height_mm: 600, shelf_count: 1 },
    bays: [{ id: `bay-${Date.now()}`, label: 'Bay 1', width_mm: null, sections: [defaultSection('SHELVES')] }],
  };
}

/** Category-aware quick presets for any furniture type */
function layoutPresets(category, doorType = 'HINGED') {
  const common = { partition_thickness_mm: 18, door_type: doorType, loft: { enabled: false, height_mm: 600, shelf_count: 1 } };
  const presets = {
    WARDROBE: [
      {
        id: 'hang-draw-shelf',
        label: 'Hang + drawers + shelves',
        layout: {
          ...common,
          plinth_height_mm: 110,
          bays: [
            { id: 'bay-1', label: 'Hang/Draw', width_mm: null, sections: [
              defaultSection('HANGING'),
              defaultSection('DRAWERS'),
              { type: 'SHELVES', height_mm: null, shelf_count: 1, label: 'Bottom' },
            ]},
            { id: 'bay-2', label: 'Shelves', width_mm: null, sections: [defaultSection('SHELVES')] },
          ],
        },
      },
      {
        id: 'full-hang',
        label: 'Full hanging',
        layout: {
          ...common,
          plinth_height_mm: 110,
          bays: [{ id: 'bay-1', label: 'Hanging', width_mm: null, sections: [defaultSection('HANGING')] }],
        },
      },
      {
        id: 'with-loft',
        label: '2 bay + loft',
        layout: {
          ...common,
          plinth_height_mm: 110,
          loft: { enabled: true, height_mm: 600, shelf_count: 1 },
          bays: [
            { id: 'bay-1', label: 'Left', width_mm: null, sections: [defaultSection('HANGING'), defaultSection('DRAWERS')] },
            { id: 'bay-2', label: 'Right', width_mm: null, sections: [defaultSection('SHELVES')] },
          ],
        },
      },
    ],
    TV_UNIT: [
      {
        id: 'tv-3bay',
        label: 'Open centre + side storage',
        layout: {
          ...common,
          plinth_height_mm: 80,
          bays: [
            { id: 'bay-1', label: 'Left', width_mm: null, sections: [{ type: 'OPEN', height_mm: 220, label: 'Niche' }, defaultSection('DRAWERS')] },
            { id: 'bay-2', label: 'TV niche', width_mm: null, sections: [{ type: 'OPEN', height_mm: null, label: 'Open' }] },
            { id: 'bay-3', label: 'Right', width_mm: null, sections: [{ type: 'OPEN', height_mm: 220, label: 'Niche' }, { type: 'SHELVES', shelf_count: 1, height_mm: null, label: 'Shelf' }] },
          ],
        },
      },
      {
        id: 'tv-drawers',
        label: 'All drawers',
        layout: {
          ...common,
          plinth_height_mm: 80,
          bays: [{ id: 'bay-1', label: 'Drawers', width_mm: null, sections: [{ type: 'DRAWERS', height_mm: null, drawer_count: 3, drawer_height_mm: 160, label: 'Drawers' }] }],
        },
      },
    ],
    KITCHEN: [
      {
        id: 'kit-shelf',
        label: 'Single shelf',
        layout: { ...common, plinth_height_mm: 100, bays: [{ id: 'bay-1', label: 'Cabinet', width_mm: null, sections: [{ type: 'SHELVES', shelf_count: 1, height_mm: null, label: 'Shelf' }] }] },
      },
      {
        id: 'kit-drawers',
        label: 'Drawer pack',
        layout: { ...common, plinth_height_mm: 100, bays: [{ id: 'bay-1', label: 'Drawers', width_mm: null, sections: [{ type: 'DRAWERS', drawer_count: 3, drawer_height_mm: 150, height_mm: null, label: 'Drawers' }] }] },
      },
      {
        id: 'kit-pantry',
        label: 'Pantry shelves',
        layout: { ...common, plinth_height_mm: 100, bays: [{ id: 'bay-1', label: 'Pantry', width_mm: null, sections: [{ type: 'SHELVES', shelf_count: 5, height_mm: null, label: 'Shelves' }] }] },
      },
    ],
    STORAGE: [
      {
        id: 'chest',
        label: '4 drawers',
        layout: { ...common, plinth_height_mm: 80, door_type: 'NONE', bays: [{ id: 'bay-1', label: 'Chest', width_mm: null, sections: [{ type: 'DRAWERS', drawer_count: 4, drawer_height_mm: 180, height_mm: null, label: 'Drawers' }] }] },
      },
      {
        id: 'books',
        label: 'Open shelves',
        layout: { ...common, plinth_height_mm: 0, door_type: 'NONE', bays: [{ id: 'bay-1', label: 'Shelves', width_mm: null, sections: [{ type: 'SHELVES', shelf_count: 5, height_mm: null, label: 'Shelves' }] }] },
      },
    ],
    BATHROOM: [
      {
        id: 'vanity-draw',
        label: '2 drawers',
        layout: { ...common, plinth_height_mm: 100, bays: [{ id: 'bay-1', label: 'Vanity', width_mm: null, sections: [{ type: 'DRAWERS', drawer_count: 2, drawer_height_mm: 180, height_mm: null, label: 'Drawers' }] }] },
      },
    ],
    STUDY: [
      {
        id: 'desk-ped',
        label: 'Desk + pedestal',
        layout: {
          ...common,
          plinth_height_mm: 0,
          bays: [
            { id: 'bay-1', label: 'Knee space', width_mm: null, sections: [{ type: 'OPEN', height_mm: null, label: 'Open' }] },
            { id: 'bay-2', label: 'Pedestal', width_mm: 400, sections: [{ type: 'DRAWERS', drawer_count: 3, drawer_height_mm: 150, height_mm: null, label: 'Drawers' }] },
          ],
        },
      },
    ],
  };
  return presets[category] || [
    {
      id: 'generic-shelf',
      label: 'Open shelves',
      layout: { ...common, plinth_height_mm: 0, bays: [{ id: 'bay-1', label: 'Main', width_mm: null, sections: [defaultSection('SHELVES')] }] },
    },
    {
      id: 'generic-draw',
      label: 'Drawer bank',
      layout: { ...common, plinth_height_mm: 80, bays: [{ id: 'bay-1', label: 'Drawers', width_mm: null, sections: [defaultSection('DRAWERS')] }] },
    },
  ];
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
  const byId = Object.fromEntries(laminates.map((m) => [String(m.id), m]));
  const templatesByCode = Object.fromEntries(templates.map((t) => [t.code, t]));
  let exteriorSelect = null;
  let interiorSelect = null;

  main.innerHTML = `<div class="panel">
    <h2>Furniture <span class="muted" style="font-size:.85rem">(${mode})</span></h2>
    <h3>Add from template</h3>
    <p class="muted">Every unit can be customized after adding — size, internals, finishes.</p>
    <div id="tpl-grid" class="tpl-grid"></div>
    <div class="toolbar" style="margin-top:.75rem">
      <label>Code <input id="furn-code" placeholder="auto" style="width:9rem"></label>
      <label>Qty <input id="furn-qty" type="number" min="1" value="1" style="width:4rem"></label>
      <label>Width <input id="furn-width" type="number" placeholder="from template" style="width:5rem"></label>
      <label>Height <input id="furn-height" type="number" placeholder="from template" style="width:5rem"></label>
      <label>Depth <input id="furn-depth" type="number" placeholder="from template" style="width:5rem"></label>
      <span class="muted" style="font-size:.75rem">Leave blank to use each template’s defaults</span>
    </div>
    <div id="furn-list"></div>

    <div id="customize-wrap" class="panel customize-wrap" style="margin-top:1rem;display:none">
      <div class="customize-head">
        <div>
          <h3 id="cust-title">Customize</h3>
          <p class="muted" id="cust-sub"></p>
        </div>
        <div class="toolbar">
          <button id="save-all-custom" class="primary">Save all customizations</button>
          <button id="close-custom" class="secondary">Close</button>
        </div>
      </div>
      <div class="cust-tabs" id="cust-tabs">
        <button data-tab="size" class="cust-tab active">Size & options</button>
        <button data-tab="internals" class="cust-tab">Internals</button>
        <button data-tab="finishes" class="cust-tab">Finishes</button>
        <button data-tab="components" class="cust-tab">Components</button>
        <button data-tab="views" class="cust-tab">2D / 3D</button>
      </div>

      <div class="cust-pane" id="pane-size">
        <div class="grid grid-2" id="param-fields"></div>
        <p id="params-msg" class="muted"></p>
      </div>

      <div class="cust-pane" id="pane-internals" style="display:none">
        <div class="toolbar" id="preset-bar"></div>
        <div class="toolbar">
          <label>Plinth mm <input id="lay-plinth" type="number" style="width:5rem"></label>
          <label>Partition mm <input id="lay-part" type="number" style="width:5rem"></label>
          <label>Door
            <select id="lay-door"><option>HINGED</option><option>SLIDING</option><option>NONE</option></select>
          </label>
          <label><input id="lay-loft" type="checkbox"> Loft</label>
          <label>Loft H <input id="lay-loft-h" type="number" style="width:5rem"></label>
          <label>Loft shelves <input id="lay-loft-s" type="number" min="0" style="width:4rem"></label>
          <button id="add-bay" class="secondary">Add bay</button>
        </div>
        <div id="bay-editor"></div>
        <p id="layout-msg" class="muted"></p>
      </div>

      <div class="cust-pane" id="pane-finishes" style="display:none">
        <div class="grid grid-2">
          <div>
            <label>Exterior laminate</label>
            <div id="spec-exterior" class="finish-select-host"></div>
          </div>
          <div>
            <label>Interior laminate</label>
            <div id="spec-interior" class="finish-select-host"></div>
          </div>
          <div>
            <label>Notes</label>
            <input id="spec-notes" placeholder="optional manufacturing/design notes" />
          </div>
        </div>
        <p id="spec-msg" class="muted"></p>
      </div>

      <div class="cust-pane" id="pane-components" style="display:none">
        <div id="comp-list"></div>
      </div>

      <div class="cust-pane" id="pane-views" style="display:none">
        <div class="toolbar">
          <select id="view2d">
            <option>FRONT</option><option>INTERNAL</option><option>PLAN</option><option>LEFT</option><option>RIGHT</option><option>BACK</option><option>SECTION</option>
          </select>
          <button id="reload-views" class="secondary">Reload views</button>
          <button id="export-design" class="secondary">Export design HTML</button>
          <button id="capture-3d" class="secondary">Capture 3D PNG</button>
        </div>
        <div class="toolbar" id="view3d-tools">
          <label class="chk"><input type="checkbox" id="scale-person" checked> Person (170 cm)</label>
          <label class="chk"><input type="checkbox" id="scale-grid" checked> Floor grid</label>
          <label class="chk"><input type="checkbox" id="scale-dims" checked> Size labels</label>
          <button id="fit-3d" class="secondary" type="button">Fit view</button>
          <span class="muted" style="font-size:.75rem">Drag to orbit · scroll to zoom</span>
        </div>
        <div class="grid grid-2">
          <div class="canvas-wrap"><canvas id="furn-2d" width="640" height="480"></canvas></div>
          <div class="canvas-wrap view3d-wrap" id="furn-3d-wrap">
            <div id="furn-3d" class="view3d-host"></div>
            <div id="furn-3d-size" class="view3d-size" hidden></div>
          </div>
        </div>
      </div>
    </div>
  </div>`;

  let selectedId = localStorage.getItem('fmos_furniture_id') || '';
  let currentFurniture = null;
  let draftLayout = null;
  let renderer3d = null;
  let view3dState = null;
  let activeTab = 'size';

  document.getElementById('tpl-grid').innerHTML = templates.map((t) => `
    <button class="tpl-card" data-code="${t.code}">
      <strong>${t.name}</strong>
      <span class="badge">${t.category}</span>
      <span class="muted">${t.description || ''}</span>
      <span class="muted" style="font-size:.75rem">Click to add · customize anytime</span>
    </button>`).join('');

  exteriorSelect = mountFinishSelect(document.getElementById('spec-exterior'), {
    laminates,
    emptyLabel: '— none —',
  });
  interiorSelect = mountFinishSelect(document.getElementById('spec-interior'), {
    laminates,
    emptyLabel: '— none —',
  });

  const showTab = (tab) => {
    activeTab = tab;
    document.querySelectorAll('.cust-tab').forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
    ['size', 'internals', 'finishes', 'components', 'views'].forEach((id) => {
      document.getElementById(`pane-${id}`).style.display = id === tab ? 'block' : 'none';
    });
    if (tab === 'views' && selectedId) {
      draw2d(selectedId);
      draw3d(selectedId);
    }
  };
  document.querySelectorAll('.cust-tab').forEach((btn) => {
    btn.onclick = () => showTab(btn.dataset.tab);
  });

  const renderSchemaFields = (furniture) => {
    const tpl = templatesByCode[furniture.type] || templates.find((t) => t.id == furniture.template_id) || {};
    const schema = tpl.parameters || {};
    const values = furniture.parameters || {};
    const skip = new Set(['layout']);
    const fields = Object.entries(schema).filter(([k]) => !skip.has(k));
    document.getElementById('param-fields').innerHTML = fields.map(([key, def]) => {
      const label = key.replace(/_/g, ' ');
      const val = values[key] ?? def.default ?? '';
      if ((def.type || '') === 'enum') {
        const opts = (def.options || []).map((o) => `<option value="${o}" ${String(val) === String(o) ? 'selected' : ''}>${o}</option>`).join('');
        return `<div><label>${label}</label><select data-param="${key}" class="schema-param">${opts}</select></div>`;
      }
      const min = def.min ?? '';
      const max = def.max ?? '';
      const unit = def.unit ? ` (${def.unit})` : '';
      const hint = (min !== '' || max !== '') ? ` <span class="muted" style="font-weight:400">${min}–${max}</span>` : '';
      return `<div><label>${label}${unit}${hint}</label><input data-param="${key}" class="schema-param" type="number" min="${min}" max="${max}" value="${val}"></div>`;
    }).join('') + `
      <div><label>Name</label><input id="cust-name" value="${furniture.name || ''}"></div>
      <div><label>Code</label><input id="cust-code" value="${furniture.code || ''}"></div>
      <div><label>Quantity</label><input id="cust-qty" type="number" min="1" value="${furniture.quantity || 1}"></div>`;
  };

  const renderPresets = (furniture) => {
    const category = furniture.category || templatesByCode[furniture.type]?.category || 'STORAGE';
    const door = draftLayout?.door_type || furniture.parameters?.door_type || 'HINGED';
    const presets = layoutPresets(category, door);
    document.getElementById('preset-bar').innerHTML = `<span class="muted">Quick presets:</span>` + presets.map((p) =>
      `<button class="secondary preset-btn" data-preset="${p.id}">${p.label}</button>`
    ).join('');
    document.querySelectorAll('.preset-btn').forEach((btn) => {
      btn.onclick = () => {
        const preset = presets.find((p) => p.id === btn.dataset.preset);
        if (!preset) return;
        draftLayout = clone(preset.layout);
        syncLayoutForm();
        renderBayEditor();
        document.getElementById('layout-msg').textContent = `Preset “${preset.label}” applied — click Save all customizations.`;
      };
    });
  };

  const syncLayoutForm = () => {
    if (!draftLayout) return;
    document.getElementById('lay-plinth').value = draftLayout.plinth_height_mm ?? 0;
    document.getElementById('lay-part').value = draftLayout.partition_thickness_mm ?? 18;
    document.getElementById('lay-door').value = draftLayout.door_type || 'HINGED';
    document.getElementById('lay-loft').checked = !!draftLayout.loft?.enabled;
    document.getElementById('lay-loft-h').value = draftLayout.loft?.height_mm ?? 600;
    document.getElementById('lay-loft-s').value = draftLayout.loft?.shelf_count ?? 1;
  };

  const readLayoutForm = () => {
    if (!draftLayout) draftLayout = emptyLayout();
    draftLayout.plinth_height_mm = Number(document.getElementById('lay-plinth').value || 0);
    draftLayout.partition_thickness_mm = Number(document.getElementById('lay-part').value || 18);
    draftLayout.door_type = document.getElementById('lay-door').value;
    draftLayout.loft = {
      enabled: document.getElementById('lay-loft').checked,
      height_mm: Number(document.getElementById('lay-loft-h').value || 600),
      shelf_count: Number(document.getElementById('lay-loft-s').value || 0),
    };
    return draftLayout;
  };

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
      </div>`).join('') || '<p class="muted">No bays — add one or pick a preset.</p>';

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
      const m = c.finish_id ? byId[String(c.finish_id)] : null;
      const finishCell = m
        ? `<span class="finish-inline">${finishThumb(m, 'finish-swatch')}<span>${m.sku}</span></span>`
        : '<span class="muted">—</span>';
      return `<tr>
        <td>${c.component_key}</td><td>${c.name}</td><td>${c.component_type}</td>
        <td>${c.length_mm}×${c.width_mm}×${c.thickness_mm}</td><td>${c.quantity}</td><td>${finishCell}</td>
        <td><div class="comp-finish" data-cid="${c.id}"></div></td>
      </tr>`;
    }).join('');
    document.getElementById('comp-list').innerHTML = `<table><thead><tr><th>Key</th><th>Name</th><th>Type</th><th>Size</th><th>Qty</th><th>Finish</th><th>Override</th></tr></thead><tbody>${rows}</tbody></table>`;
    document.querySelectorAll('.comp-finish').forEach((host) => {
      const row = (res.data || []).find((c) => String(c.id) === host.dataset.cid);
      mountFinishSelect(host, {
        laminates,
        value: row?.finish_id || '',
        emptyLabel: 'default',
        compact: true,
        onChange: async (finishId) => {
          await api.put(`/api/v1/furniture/instances/${furnitureId}/components/${host.dataset.cid}`, {
            finish_id: finishId,
          });
          renderComponents(furnitureId);
        },
      });
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
        ctx.strokeStyle = '#1c2430';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
        if (el.label && el.w * scale > 36) {
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

  const loadTexture = (loader, url) => new Promise((resolve, reject) => {
    loader.load(
      url,
      (tex) => {
        tex.colorSpace = THREE.SRGBColorSpace;
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.needsUpdate = true;
        resolve(tex);
      },
      undefined,
      reject
    );
  });

  /** Fit camera so the full unit is visible with margin. */
  const fitCameraToBounds = (camera, bounds, aspect, margin = 1.55) => {
    const cx = bounds.width / 2;
    const cy = bounds.height / 2;
    const cz = bounds.depth / 2;
    const radius = Math.sqrt(bounds.width ** 2 + bounds.height ** 2 + bounds.depth ** 2) / 2;
    const fov = (camera.fov * Math.PI) / 180;
    const fitH = radius / Math.sin(fov / 2);
    const fitW = radius / Math.sin(Math.atan(Math.tan(fov / 2) * aspect));
    const distance = Math.max(fitH, fitW) * margin;
    // 3/4 front-right elevated view
    const dir = new THREE.Vector3(0.85, 0.45, 1.15).normalize();
    camera.position.set(cx + dir.x * distance, cy + dir.y * distance, cz + dir.z * distance);
    camera.near = Math.max(1, distance / 100);
    camera.far = distance * 20;
    camera.lookAt(cx, cy * 0.55, cz);
    camera.updateProjectionMatrix();
    return { target: new THREE.Vector3(cx, cy * 0.55, cz), distance };
  };

  const makePersonFigure = (heightMm = 1700) => {
    const g = new THREE.Group();
    g.name = 'scale-person';
    const skin = new THREE.MeshStandardMaterial({ color: 0x8a96a3, roughness: 0.85, metalness: 0 });
    const bodyH = heightMm * 0.52;
    const legH = heightMm * 0.38;
    const headR = heightMm * 0.07;
    const torso = new THREE.Mesh(new THREE.CylinderGeometry(90, 110, bodyH, 12), skin);
    torso.position.y = legH + bodyH / 2;
    const legL = new THREE.Mesh(new THREE.CylinderGeometry(35, 40, legH, 8), skin);
    legL.position.set(-45, legH / 2, 0);
    const legR = legL.clone();
    legR.position.x = 45;
    const head = new THREE.Mesh(new THREE.SphereGeometry(headR, 12, 12), skin);
    head.position.y = legH + bodyH + headR * 0.9;
    g.add(torso, legL, legR, head);
    return g;
  };

  const makeFloorGrid = (bounds) => {
    const g = new THREE.Group();
    g.name = 'scale-grid';
    const pad = 600;
    const gw = bounds.width + pad * 2;
    const gd = bounds.depth + pad * 2;
    const floor = new THREE.Mesh(
      new THREE.PlaneGeometry(gw, gd),
      new THREE.MeshStandardMaterial({ color: 0xe8ecf0, roughness: 1, metalness: 0 })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.set(bounds.width / 2, -1, bounds.depth / 2);
    g.add(floor);
    const step = 500;
    const lineMat = new THREE.LineBasicMaterial({ color: 0xb8c2cc });
    const pts = [];
    for (let x = -pad; x <= bounds.width + pad + 0.1; x += step) {
      pts.push(new THREE.Vector3(x, 0.5, -pad), new THREE.Vector3(x, 0.5, bounds.depth + pad));
    }
    for (let z = -pad; z <= bounds.depth + pad + 0.1; z += step) {
      pts.push(new THREE.Vector3(-pad, 0.5, z), new THREE.Vector3(bounds.width + pad, 0.5, z));
    }
    const geo = new THREE.BufferGeometry().setFromPoints(pts);
    g.add(new THREE.LineSegments(geo, lineMat));
    return g;
  };

  const updateSizeOverlay = (bounds) => {
    const el = document.getElementById('furn-3d-size');
    if (!el) return;
    const show = document.getElementById('scale-dims')?.checked;
    if (!show) {
      el.hidden = true;
      return;
    }
    el.hidden = false;
    const mm = (n) => Math.round(Number(n) || 0);
    el.innerHTML = `
      <strong>${mm(bounds.width)} × ${mm(bounds.height)} × ${mm(bounds.depth)} mm</strong>
      <span>W × H × D · grid 500 mm · person 1700 mm</span>`;
  };

  const draw3d = async (furnitureId) => {
    const host = document.getElementById('furn-3d');
    host.innerHTML = '';
    if (view3dState?.dispose) view3dState.dispose();
    view3dState = null;

    if (!window.THREE) {
      await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/three@0.160.0/build/three.min.js';
        s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
      });
    }

    const model = await api.get(`/api/v1/furniture/instances/${furnitureId}/3d-model`);
    const b = model.data.bounds;
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xf4f6f8);

    const heightPx = 480;
    const widthPx = Math.max(1, host.clientWidth || host.parentElement?.clientWidth || 640);
    const camera = new THREE.PerspectiveCamera(40, widthPx / heightPx, 1, 100000);
    const fitted = fitCameraToBounds(camera, b, widthPx / heightPx);

    renderer3d = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer3d.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer3d.setSize(widthPx, heightPx);
    renderer3d.outputColorSpace = THREE.SRGBColorSpace;
    host.appendChild(renderer3d.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 1.05));
    const key = new THREE.DirectionalLight(0xffffff, 0.9);
    key.position.set(b.width * 1.6, b.height * 2.4, b.depth * 2.8);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.35);
    fill.position.set(-b.width, b.height, -b.depth);
    scene.add(fill);

    const helpers = new THREE.Group();
    helpers.name = 'scale-helpers';
    scene.add(helpers);

    const person = makePersonFigure(1700);
    person.position.set(-350, 0, b.depth / 2);
    helpers.add(person);

    const grid = makeFloorGrid(b);
    helpers.add(grid);

    const syncHelpers = () => {
      person.visible = !!document.getElementById('scale-person')?.checked;
      grid.visible = !!document.getElementById('scale-grid')?.checked;
      updateSizeOverlay(b);
      renderer3d.render(scene, camera);
    };

    const loader = new THREE.TextureLoader();
    const textureCache = new Map();
    const getTex = async (url) => {
      if (!url) return null;
      if (!textureCache.has(url)) {
        textureCache.set(url, loadTexture(loader, url).catch(() => null));
      }
      return textureCache.get(url);
    };

    for (const mesh of model.data.meshes) {
      const fallback = mesh.color || (mesh.role === 'shutter' ? '#c9d6df' : '#e6ebf0');
      const material = new THREE.MeshStandardMaterial({
        color: fallback,
        roughness: mesh.finish?.roughness ?? 0.7,
        metalness: mesh.finish?.metalness ?? 0,
      });
      const box = new THREE.Mesh(new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]), material);
      box.position.set(mesh.position[0], mesh.position[1], mesh.position[2]);
      scene.add(box);

      const url = mesh.finish?.texture_url;
      if (url) {
        const tex = await getTex(url);
        if (tex) {
          const map = tex.clone();
          map.needsUpdate = true;
          map.wrapS = map.wrapT = THREE.RepeatWrapping;
          map.repeat.set(
            Math.max(0.5, mesh.size[0] / 500),
            Math.max(0.5, mesh.size[1] / 500)
          );
          material.map = map;
          material.color.set('#ffffff');
          material.roughness = mesh.finish.roughness ?? 0.55;
          material.metalness = mesh.finish.metalness ?? 0;
          material.needsUpdate = true;
        }
      }
    }

    // Orbit / zoom
    const target = fitted.target.clone();
    let dragging = false;
    let lastX = 0;
    let lastY = 0;
    const canvas = renderer3d.domElement;
    canvas.style.cursor = 'grab';

    const onPointerDown = (e) => {
      dragging = true;
      lastX = e.clientX;
      lastY = e.clientY;
      canvas.style.cursor = 'grabbing';
      canvas.setPointerCapture?.(e.pointerId);
    };
    const onPointerUp = (e) => {
      dragging = false;
      canvas.style.cursor = 'grab';
      canvas.releasePointerCapture?.(e.pointerId);
    };
    const onPointerMove = (e) => {
      if (!dragging) return;
      const dx = e.clientX - lastX;
      const dy = e.clientY - lastY;
      lastX = e.clientX;
      lastY = e.clientY;
      const offset = camera.position.clone().sub(target);
      const spherical = new THREE.Spherical().setFromVector3(offset);
      spherical.theta -= dx * 0.01;
      spherical.phi = Math.min(Math.PI * 0.45, Math.max(0.12, spherical.phi - dy * 0.01));
      offset.setFromSpherical(spherical);
      camera.position.copy(target).add(offset);
      camera.lookAt(target);
      renderer3d.render(scene, camera);
    };
    const onWheel = (e) => {
      e.preventDefault();
      const offset = camera.position.clone().sub(target);
      const factor = e.deltaY > 0 ? 1.08 : 0.92;
      offset.multiplyScalar(factor);
      const minDist = Math.sqrt(b.width ** 2 + b.height ** 2 + b.depth ** 2) * 0.35;
      const maxDist = Math.sqrt(b.width ** 2 + b.height ** 2 + b.depth ** 2) * 6;
      if (offset.length() < minDist) offset.setLength(minDist);
      if (offset.length() > maxDist) offset.setLength(maxDist);
      camera.position.copy(target).add(offset);
      camera.lookAt(target);
      renderer3d.render(scene, camera);
    };
    canvas.addEventListener('pointerdown', onPointerDown);
    canvas.addEventListener('pointerup', onPointerUp);
    canvas.addEventListener('pointercancel', onPointerUp);
    canvas.addEventListener('pointermove', onPointerMove);
    canvas.addEventListener('wheel', onWheel, { passive: false });

    const fitNow = () => {
      const next = fitCameraToBounds(camera, b, canvas.clientWidth / Math.max(1, canvas.clientHeight));
      target.copy(next.target);
      renderer3d.render(scene, camera);
    };

    view3dState = {
      scene,
      camera,
      bounds: b,
      fit: fitNow,
      syncHelpers,
      dispose: () => {
        canvas.removeEventListener('pointerdown', onPointerDown);
        canvas.removeEventListener('pointerup', onPointerUp);
        canvas.removeEventListener('pointercancel', onPointerUp);
        canvas.removeEventListener('pointermove', onPointerMove);
        canvas.removeEventListener('wheel', onWheel);
      },
    };

    syncHelpers();
  };

  const openCustomize = async (id) => {
    selectedId = String(id);
    localStorage.setItem('fmos_furniture_id', selectedId);
    const res = await api.get(`/api/v1/furniture/instances/${selectedId}`);
    currentFurniture = res.data;
    const f = currentFurniture;
    const p = f.parameters || {};
    draftLayout = clone(p.layout || emptyLayout(p.door_type || 'HINGED'));
    if (!draftLayout.bays?.length) draftLayout = emptyLayout(p.door_type || 'HINGED');

    document.getElementById('customize-wrap').style.display = 'block';
    document.getElementById('cust-title').textContent = `Customize · ${f.name}`;
    document.getElementById('cust-sub').textContent = `${f.code || ''} · ${f.type || f.category || ''} · ${f.width_mm}×${f.height_mm}×${f.depth_mm} mm · ${(f.component_rows || []).length} parts`;

    renderSchemaFields(f);
    renderPresets(f);
    syncLayoutForm();
    renderBayEditor();

    exteriorSelect.setValue(f.exterior_finish_id || '');
    interiorSelect.setValue(f.interior_finish_id || '');
    document.getElementById('spec-notes').value = f.specification?.notes || '';
    await renderComponents(selectedId);
    showTab(activeTab || 'size');
    if (activeTab === 'views') {
      await draw2d(selectedId);
      await draw3d(selectedId);
    }
    document.getElementById('customize-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const collectParameters = () => {
    const parameters = {};
    document.querySelectorAll('.schema-param').forEach((el) => {
      const key = el.dataset.param;
      if (el.tagName === 'SELECT') parameters[key] = el.value;
      else parameters[key] = el.value === '' ? null : Number(el.value);
    });
    return parameters;
  };

  const saveAll = async () => {
    if (!selectedId) return;
    const layout = readLayoutForm();
    const parameters = collectParameters();
    parameters.door_type = layout.door_type || parameters.door_type;
    try {
      await api.put(`/api/v1/furniture/instances/${selectedId}/customize`, {
        name: document.getElementById('cust-name').value,
        code: document.getElementById('cust-code').value,
        quantity: Number(document.getElementById('cust-qty').value || 1),
        parameters,
        layout,
        exterior_finish_id: exteriorSelect.getValue() ? Number(exteriorSelect.getValue()) : null,
        interior_finish_id: interiorSelect.getValue() ? Number(interiorSelect.getValue()) : null,
        specification: { notes: document.getElementById('spec-notes').value || '' },
      });
      document.getElementById('params-msg').textContent = 'All customizations saved.';
      document.getElementById('layout-msg').textContent = 'Internals regenerated.';
      document.getElementById('spec-msg').textContent = 'Finishes saved.';
      await refresh();
      await openCustomize(selectedId);
    } catch (err) {
      const msg = err?.message || String(err);
      document.getElementById('params-msg').textContent = msg;
      document.getElementById('layout-msg').textContent = msg;
    }
  };

  document.querySelectorAll('.tpl-card').forEach((btn) => {
    btn.onmouseenter = () => {
      const tpl = templatesByCode[btn.dataset.code];
      if (!tpl) return;
      [['furn-width', 'width'], ['furn-height', 'height'], ['furn-depth', 'depth']].forEach(([id, key]) => {
        const el = document.getElementById(id);
        if (el && el.value === '' && tpl.parameters[key]?.default != null) {
          el.placeholder = String(tpl.parameters[key].default);
        }
      });
    };
    btn.onclick = async () => {
      const code = btn.dataset.code;
      const tpl = templatesByCode[code];
      const paramDim = (key, inputId) => {
        const def = tpl.parameters?.[key] || {};
        const raw = document.getElementById(inputId).value;
        let v = raw === '' || raw == null
          ? Number(def.default ?? 0)
          : Number(raw);
        if (!Number.isFinite(v)) v = Number(def.default ?? 0);
        if (def.min != null) v = Math.max(Number(def.min), v);
        if (def.max != null) v = Math.min(Number(def.max), v);
        return v;
      };
      const width = paramDim('width', 'furn-width');
      const height = paramDim('height', 'furn-height');
      const depth = paramDim('depth', 'furn-depth');
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
          layout: clone(tpl.parameters.layout?.default || emptyLayout()),
        },
      };
      if (mode === 'LEGACY' && room) payload.room_id = room.id;
      try {
        const created = await api.post('/api/v1/furniture/instances', payload);
        selectedId = String(created.data.id);
        await refresh();
        await openCustomize(selectedId);
      } catch (err) {
        alert(err?.message || 'Failed to add furniture');
      }
    };
  });

  const refresh = async () => {
    const res = await api.get(`/api/v1/projects/${projectId}/furniture`);
    if (!selectedId && res.data[0]?.id) selectedId = String(res.data[0].id);
    localStorage.setItem('fmos_furniture_id', selectedId || '');
    const rows = (res.data || []).map((f) => `<tr class="${String(f.id) === selectedId ? 'row-active' : ''}">
      <td>${f.code || ''}</td>
      <td>${f.name || ''}</td>
      <td><span class="badge">${f.category || f.type || ''}</span></td>
      <td>${f.width_mm || ''}×${f.height_mm || ''}×${f.depth_mm || ''}</td>
      <td>${(f.parameters?.layout?.bays || []).length || 0} bays</td>
      <td>
        <button data-id="${f.id}" class="open-cust">Customize</button>
      </td>
    </tr>`).join('');
    document.getElementById('furn-list').innerHTML = `<table><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>W×H×D</th><th>Layout</th><th></th></tr></thead>
      <tbody>${rows || '<tr><td colspan="6" class="muted">No furniture — pick a template above</td></tr>'}</tbody></table>`;
    document.querySelectorAll('.open-cust').forEach((btn) => { btn.onclick = () => openCustomize(btn.dataset.id); });
  };

  document.getElementById('add-bay').onclick = () => {
    if (!draftLayout) draftLayout = emptyLayout();
    draftLayout.bays.push({ id: `bay-${Date.now()}`, label: `Bay ${draftLayout.bays.length + 1}`, width_mm: null, sections: [defaultSection('SHELVES')] });
    renderBayEditor();
  };
  document.getElementById('save-all-custom').onclick = saveAll;
  document.getElementById('close-custom').onclick = () => {
    document.getElementById('customize-wrap').style.display = 'none';
  };
  document.getElementById('view2d').onchange = () => selectedId && draw2d(selectedId);
  document.getElementById('reload-views').onclick = async () => { if (!selectedId) return; await draw2d(selectedId); await draw3d(selectedId); };
  document.getElementById('fit-3d').onclick = () => view3dState?.fit?.();
  ['scale-person', 'scale-grid', 'scale-dims'].forEach((id) => {
    document.getElementById(id).onchange = () => view3dState?.syncHelpers?.();
  });
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
