import { api } from './api.js';
import { esc, safeUrl } from './security.js';

function finishUrl(m) {
  if (!m) return '';
  return m.assets?.find((a) => a.is_primary == 1)?.public_url || m.assets?.[0]?.public_url || '';
}

function finishThumb(m, cls = 'finish-thumb') {
  const url = safeUrl(finishUrl(m));
  return url
    ? `<img src="${esc(url)}" alt="${esc(m.sku || '')}" class="${cls}" loading="lazy" />`
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
    inner.innerHTML = `${finishThumb(m, 'finish-swatch')}<span class="finish-code">${esc(finishLabel(m))}</span>`;
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
  if (type === 'DRAWERS') return { type: 'DRAWERS', height_mm: 600, drawer_count: 3, drawer_height_mm: 180, label: 'Drawers', cutlery_organizer: false };
  if (type === 'HANGING') return { type: 'HANGING', height_mm: 1100, label: 'Hanging', hanging_style: 'standard' };
  if (type === 'OPEN') return { type: 'OPEN', height_mm: null, label: 'Open' };
  if (type === 'MIRROR') return { type: 'MIRROR', height_mm: null, label: 'Mirror', mirror_margin_mm: 80, mirror_width_mm: null, mirror_height_mm: null };
  return { type: 'SHELVES', height_mm: null, shelf_count: 3, label: 'Shelves', shelf_style: 'standard' };
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

/** Fallback presets if catalog API is unavailable (kept minimal). */
function layoutPresetsFallback(category, doorType = 'HINGED') {
  const common = { partition_thickness_mm: 18, door_type: doorType, loft: { enabled: false, height_mm: 600, shelf_count: 1 } };
  if (category === 'WARDROBE') {
    return [{
      id: 'full-hang',
      label: 'Full hanging',
      layout: {
        ...common,
        plinth_height_mm: 110,
        bays: [{ id: 'bay-1', label: 'Hanging', width_mm: null, sections: [defaultSection('HANGING')] }],
      },
    }];
  }
  if (category === 'KITCHEN') {
    return [{
      id: 'kit-shelf',
      label: 'Single shelf',
      layout: {
        ...common,
        plinth_height_mm: 100,
        bays: [{ id: 'bay-1', label: 'Cabinet', width_mm: null, sections: [{ type: 'SHELVES', shelf_count: 1, height_mm: null, label: 'Shelf' }] }],
      },
    }];
  }
  return [{
    id: 'generic-shelf',
    label: 'Open shelves',
    layout: { ...common, plinth_height_mm: 0, bays: [{ id: 'bay-1', label: 'Main', width_mm: null, sections: [defaultSection('SHELVES')] }] },
  }];
}

export async function mountFurniture(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  if (!projectId) {
    main.innerHTML = `<div class="panel"><p class="muted">Select a project first.</p></div>`;
    return;
  }
  const [project, laminatesRes, templatesRes, catalogRes] = await Promise.all([
    api.get(`/api/v1/projects/${projectId}`),
    api.get('/api/v1/materials?category=LAMINATE'),
    api.get('/api/v1/furniture/templates'),
    api.get('/api/v1/catalog/products').catch(() => ({ data: [] })),
  ]);
  const mode = project.data.model_mode || 'FURNITURE_FIRST';
  const room = project.data.buildings?.[0]?.floors?.[0]?.rooms?.[0] || null;
  const laminates = laminatesRes.data || [];
  const templates = templatesRes.data || [];
  const boards = (catalogRes.data || []).filter((p) => String(p.category || '').toUpperCase() === 'BOARD'
    && String(p.publish_status || '').toUpperCase() === 'PUBLISHED');
  window.__fmosBoards = boards;
  const byId = Object.fromEntries(laminates.map((m) => [String(m.id), m]));
  const templatesByCode = Object.fromEntries(templates.map((t) => [t.code, t]));
  let exteriorSelect = null;
  let interiorSelect = null;

  const boardOptionsHtml = (selectedId = '', includeEmpty = true, emptyLabel = '— optional —') => {
    const opts = includeEmpty ? [`<option value="">${emptyLabel}</option>`] : [];
    boards.forEach((b) => {
      const sel = String(selectedId) === String(b.id) ? 'selected' : '';
      opts.push(`<option value="${b.id}" ${sel}>${b.name}${b.thickness_mm != null ? ` (${b.thickness_mm} mm)` : ''}</option>`);
    });
    return opts.join('');
  };
  const defaultBoardId = () => {
    const b18 = boards.find((b) => Number(b.thickness_mm) === 18);
    return b18?.id || boards[0]?.id || '';
  };

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
      <label>Material Type
        <select id="furn-material" style="min-width:12rem">
          <option value="">— optional —</option>
        </select>
      </label>
      <span class="muted" style="font-size:.75rem">Leave blank to use each template’s defaults</span>
    </div>
    <div id="furn-list"></div>

    <div class="panel" style="margin-top:1rem" id="kitchen-comp-panel">
      <h3>Kitchen L-shape composition</h3>
      <p class="muted" style="font-size:.85rem;margin:.25rem 0 .75rem">
        Creates multiple Kitchen Base modules + a blind corner as separate furniture units, placed into an L.
        Each module keeps its own cutlist/BOM. Countertops are not included yet.
      </p>
      <div class="toolbar" style="flex-wrap:wrap;gap:.5rem">
        <label>Name <input id="kc-name" value="L Kitchen Base" style="width:10rem"></label>
        <label>Run A mm <input id="kc-run-a" type="number" value="1800" min="600" step="50" style="width:5.5rem"></label>
        <label>Run B mm <input id="kc-run-b" type="number" value="1200" min="600" step="50" style="width:5.5rem"></label>
        <label>Depth <input id="kc-depth" type="number" value="560" min="400" max="700" style="width:4.5rem"></label>
        <label>Height <input id="kc-height" type="number" value="720" min="500" max="1200" style="width:4.5rem"></label>
        <label>Corner <input id="kc-corner" type="number" value="900" min="700" max="1200" style="width:4.5rem"></label>
        <label>Module W <input id="kc-mod-w" type="number" value="600" min="300" max="1200" style="width:4.5rem"></label>
        <label>Module config
          <select id="kc-preset">
            <option value="shelf">Shelf (CFG_KB_SHELF)</option>
            <option value="drawers">Drawers (CFG_KB_DRAWERS)</option>
            <option value="sink">Sink / plumbing (CFG_KB_SINK)</option>
            <option value="open">Open niche</option>
          </select>
        </label>
        <button id="kc-create" class="primary" type="button">Create L-shape</button>
      </div>
      <div id="kc-list" style="margin-top:.75rem"></div>
      <div id="kc-plan-wrap" style="display:none;margin-top:.75rem">
        <div class="toolbar" style="flex-wrap:wrap;gap:.5rem">
          <strong id="kc-plan-title">Composition plan</strong>
          <button id="kc-show-plan" class="secondary" type="button">Plan</button>
          <button id="kc-show-3d" class="secondary" type="button">3D</button>
          <button id="kc-reload-plan" class="secondary" type="button">Reload</button>
          <button id="kc-fit-3d" class="secondary" type="button" style="display:none">Fit</button>
          <button id="kc-open-mfg" class="secondary" type="button">Use modules in manufacturing</button>
        </div>
        <canvas id="kc-plan" width="900" height="520" style="width:100%;max-width:960px;border:1px solid var(--line);background:#fbfcfd"></canvas>
        <div id="kc-3d-wrap" class="canvas-wrap view3d-wrap" style="display:none;margin-top:.5rem;min-height:520px">
          <div id="kc-3d" class="view3d-host" style="min-height:520px"></div>
        </div>
      </div>
    </div>

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
        <button data-tab="view2d" class="cust-tab">2D Design</button>
        <button data-tab="view3d" class="cust-tab">3D Presentation</button>
      </div>

        <div class="cust-pane" id="pane-size">
        <div class="grid grid-2" id="param-fields"></div>
        <div class="filler-block" style="margin-top:1rem">
          <div class="expo-head"><strong>Installation fillers</strong></div>
          <p class="muted" style="font-size:.8rem;margin:.35rem 0 .6rem">
            Opt-in only — fillers become real cutlist/BOM panels. Do not enable just to hide a viewport gap.
          </p>
          <div class="toolbar" style="flex-wrap:wrap;gap:.75rem">
            <label class="chk"><input type="checkbox" id="filler-left-en"> Left filler</label>
            <label>Width mm <input id="filler-left-w" type="number" min="10" max="300" value="50" style="width:5rem"></label>
            <label class="chk"><input type="checkbox" id="filler-right-en"> Right filler</label>
            <label>Width mm <input id="filler-right-w" type="number" min="10" max="300" value="50" style="width:5rem"></label>
          </div>
          <p id="filler-msg" class="muted"></p>
        </div>
        <p id="params-msg" class="muted"></p>
      </div>

      <div class="cust-pane" id="pane-internals" style="display:none">
        <div id="internals-recommend" class="module-recommend" style="margin-bottom:.85rem;padding:.65rem .75rem;border:1px solid var(--line);border-radius:8px;background:#f7f9fb">
          <div class="toolbar" style="justify-content:space-between;margin-bottom:.35rem">
            <strong>Recommended internal configurations</strong>
            <button type="button" class="secondary" id="refresh-recommend">Refresh</button>
          </div>
          <p class="muted" style="font-size:.8rem;margin:0 0 .5rem">Advisory only — apply or remove without forcing structure. Save customizations to regenerate cutlist/BOM/2D/3D.</p>
          <div id="recommend-lists"></div>
          <p id="recommend-validation" class="muted" style="font-size:.8rem;margin:.5rem 0 0"></p>
        </div>
        <div class="toolbar" id="preset-bar"></div>
        <div class="toolbar">
          <label>Target bay
            <select id="cfg-bay-target" style="min-width:8rem"></select>
          </label>
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
            <label>Material Type (board)</label>
            <select id="spec-material"></select>
            <p class="muted" style="font-size:.75rem;margin:.25rem 0 0">Default board for this unit (carcass panels). Separate from laminate finishes.</p>
          </div>
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
        <div class="expo-block" style="margin-top:1rem">
          <div class="expo-head">
            <strong>Exposed components (EXPO)</strong>
            <span class="muted" title="Mark components that will remain visible after installation. EXPO components may require decorative finishing and special edge treatment.">Mark sides/parts visible to the client</span>
          </div>
          <p class="muted" style="font-size:.8rem;margin:.35rem 0 .6rem">EXPO ≠ doors only — doors default to exposed; you can also mark left/right sides, top, back, shelves, etc.</p>
          <div id="expo-options" class="expo-options"></div>
          <p id="expo-msg" class="muted"></p>
        </div>
        <p id="spec-msg" class="muted"></p>
      </div>

      <div class="cust-pane" id="pane-components" style="display:none">
        <div class="expo-block" style="margin-bottom:1rem">
          <div class="expo-head">
            <strong>Exposed components (EXPO)</strong>
            <span class="muted">Visible after installation</span>
          </div>
          <div id="expo-options-comp" class="expo-options"></div>
        </div>
        <div id="comp-list"></div>
      </div>

      <div class="cust-pane" id="pane-view2d" style="display:none">
        <div class="view-stage view-stage-2d">
          <div class="view-stage-head">
            <div>
              <strong>2D Design</strong>
              <span class="muted">Engineering / measurement workspace</span>
            </div>
            <div class="toolbar" style="margin:0">
              <select id="view2d">
                <option>FRONT</option><option>INTERNAL</option><option>PLAN</option><option>LEFT</option><option>RIGHT</option><option>BACK</option><option>SECTION</option>
              </select>
              <button id="fit-2d" class="secondary" type="button">Fit</button>
              <button id="zoom-2d-in" class="secondary" type="button">Zoom +</button>
              <button id="zoom-2d-out" class="secondary" type="button">Zoom −</button>
              <button id="reload-2d" class="secondary" type="button">Reload</button>
              <button id="export-design" class="secondary" type="button">Export design HTML</button>
            </div>
          </div>
          <div class="canvas-wrap design2d-wrap">
            <canvas id="furn-2d" width="1100" height="720"></canvas>
          </div>
          <p class="muted" style="font-size:.75rem;margin:.4rem 0 0">Scroll to zoom · drag to pan · EXPO highlighted in blue</p>
        </div>
      </div>

      <div class="cust-pane" id="pane-view3d" style="display:none">
        <div class="view-stage view-stage-3d">
          <div class="view-stage-head">
            <div>
              <strong id="view3d-title">3D Presentation</strong>
              <span class="muted" id="view3d-sub">Client-facing visualization</span>
            </div>
            <div class="toolbar" style="margin:0">
              <button id="pres-mode" class="secondary" type="button" data-on="0">Presentation</button>
              <button id="fullscreen-3d" class="secondary" type="button">Fullscreen</button>
              <button id="reload-3d" class="secondary" type="button">Reload</button>
              <button id="capture-3d" class="primary" type="button" title="4K side-by-side: Front + Isometric">Export 4K sheet</button>
            </div>
          </div>
          <div class="toolbar view3d-toolbar" id="view3d-tools">
            <label>Camera
              <select id="cam-preset">
                <option value="iso">Isometric</option>
                <option value="front">Front</option>
                <option value="left">Left</option>
                <option value="right">Right</option>
                <option value="top">Top</option>
                <option value="close">Close-up</option>
              </select>
            </label>
            <label>Quality
              <select id="viz-quality">
                <option value="standard">Standard</option>
                <option value="high" selected>High</option>
                <option value="presentation">Presentation</option>
              </select>
            </label>
            <label class="chk"><input type="checkbox" id="scale-person"> Person (170 cm)</label>
            <label class="chk"><input type="checkbox" id="scale-grid"> Floor grid</label>
            <label class="chk"><input type="checkbox" id="scale-dims"> Size labels</label>
            <label class="chk"><input type="checkbox" id="scale-room" checked> Room backdrop</label>
            <span class="muted" style="font-size:.75rem" title="Walls only on non-EXPO sides">Walls follow EXPO</span>
            <label class="chk"><input type="checkbox" id="scale-shadows" checked> Shadows</label>
            <button id="fit-3d" class="secondary" type="button">Fit view</button>
            <span class="muted" style="font-size:.75rem">Orbit · scroll zoom</span>
          </div>
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
    <button class="tpl-card" data-code="${esc(t.code)}">
      <strong>${esc(t.name)}</strong>
      <span class="badge">${esc(t.category)}</span>
      <span class="muted">${esc(t.description || '')}</span>
      <span class="muted" style="font-size:.75rem">Click to add · customize anytime</span>
    </button>`).join('');

  const furnMaterialEl = document.getElementById('furn-material');
  if (furnMaterialEl) {
    furnMaterialEl.innerHTML = boardOptionsHtml(defaultBoardId(), true, '— optional —');
  }
  const specMaterialEl = document.getElementById('spec-material');
  if (specMaterialEl) {
    specMaterialEl.innerHTML = boardOptionsHtml('', true, '— none (generic Board) —');
  }

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
    ['size', 'internals', 'finishes', 'components', 'view2d', 'view3d'].forEach((id) => {
      const el = document.getElementById(`pane-${id}`);
      if (el) el.style.display = id === tab ? 'block' : 'none';
    });
    if (tab === 'internals') refreshRecommendations();
    if (tab === 'view2d' && selectedId) draw2d(selectedId);
    if (tab === 'view3d' && selectedId) draw3d(selectedId);
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
      const label = def.label || key.replace(/_/g, ' ');
      const val = values[key] ?? def.default ?? '';
      if ((def.type || '') === 'enum') {
        const opts = (def.options || []).map((o) => `<option value="${o}" ${String(val) === String(o) ? 'selected' : ''}>${o}</option>`).join('');
        return `<div><label>${label}</label><select data-param="${key}" class="schema-param">${opts}</select></div>`;
      }
      if ((def.type || '') === 'catalog_board') {
        const boards = (window.__fmosBoards || []);
        const opts = [`<option value="">— default board —</option>`]
          .concat(boards.map((b) => `<option value="${b.id}" ${String(val) === String(b.id) ? 'selected' : ''}>${b.name} (${b.thickness_mm || '?'} mm)</option>`))
          .join('');
        return `<div><label>${label}</label><select data-param="${key}" class="schema-param">${opts}</select>
          <p class="muted" style="font-size:.75rem;margin:.2rem 0 0">Board for the back panel (optional)</p></div>`;
      }
      const min = def.min ?? '';
      const max = def.max ?? '';
      const unit = def.unit ? ` (${def.unit})` : '';
      const rec = def.recommended != null ? ` · recommended ${def.recommended}` : '';
      const hint = (min !== '' || max !== '') ? ` <span class="muted" style="font-weight:400">${min}–${max}${rec}</span>` : '';
      return `<div><label>${label}${unit}${hint}</label><input data-param="${key}" class="schema-param" type="number" min="${min}" max="${max}" value="${val}"></div>`;
    }).join('') + `
      <div><label>Name</label><input id="cust-name" value="${furniture.name || ''}"></div>
      <div><label>Code</label><input id="cust-code" value="${furniture.code || ''}"></div>
      <div><label>Quantity</label><input id="cust-qty" type="number" min="1" value="${furniture.quantity || 1}"></div>`;
  };

  const syncBayTargetSelect = () => {
    const sel = document.getElementById('cfg-bay-target');
    if (!sel || !draftLayout?.bays?.length) return;
    const prev = sel.value;
    sel.innerHTML = draftLayout.bays.map((b, i) =>
      `<option value="${b.id || `bay-${i + 1}`}">${b.label || `Bay ${i + 1}`}</option>`
    ).join('');
    if ([...sel.options].some((o) => o.value === prev)) sel.value = prev;
  };

  const renderRecommendLists = (rec) => {
    const host = document.getElementById('recommend-lists');
    const valEl = document.getElementById('recommend-validation');
    if (!host) return;
    const rowHtml = (items, kind) => {
      if (!items?.length) return '';
      const title = kind === 'recommended' ? 'Recommended' : kind === 'optional' ? 'Optional' : kind === 'required' ? 'Required' : 'Unavailable';
      return `<div style="margin:.4rem 0"><div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.04em">${title}</div>
        <div class="toolbar" style="flex-wrap:wrap;gap:.35rem;margin-top:.25rem">
          ${items.map((c) => {
            const present = !!c.present;
            const unavailable = c.status === 'unavailable';
            const reason = (c.reasons || []).join(' ');
            const btnLabel = unavailable ? c.name : (present ? `Remove ${c.name}` : `Add ${c.name}`);
            const disabled = unavailable && !present ? 'disabled' : '';
            const cls = unavailable ? 'secondary' : (present ? 'danger' : (kind === 'recommended' ? 'primary' : 'secondary'));
            return `<button type="button" class="${cls} cfg-action" data-id="${c.id}" data-action="${present ? 'remove' : 'apply'}" ${disabled} title="${reason || c.description || ''}">${btnLabel}</button>${reason && unavailable ? `<span class="muted" style="font-size:.72rem;max-width:14rem">${reason}</span>` : ''}`;
          }).join('')}
        </div></div>`;
    };
    host.innerHTML = [
      rowHtml(rec.required, 'required'),
      rowHtml(rec.recommended, 'recommended'),
      rowHtml(rec.optional, 'optional'),
      rowHtml(rec.unavailable, 'unavailable'),
    ].join('') || '<p class="muted">No configurations for this module type.</p>';

    if (valEl) {
      const issues = rec.validation?.issues || [];
      valEl.textContent = issues.length
        ? `Validation: ${issues.map((i) => i.message).join(' · ')}`
        : (rec.validation?.ok === false ? 'Layout has validation issues.' : 'Layout validation OK.');
      valEl.className = issues.length ? 'error' : 'muted';
      valEl.style.fontSize = '.8rem';
    }

    host.querySelectorAll('.cfg-action').forEach((btn) => {
      btn.onclick = async () => {
        if (!selectedId) return;
        readLayoutForm();
        const bayId = document.getElementById('cfg-bay-target')?.value || draftLayout?.bays?.[0]?.id || null;
        try {
          btn.disabled = true;
          const res = await api.post(`/api/v1/furniture/instances/${selectedId}/apply-internal-config`, {
            config_id: btn.dataset.id,
            action: btn.dataset.action,
            bay_id: bayId,
            layout: draftLayout,
          });
          currentFurniture = res.data.instance;
          draftLayout = clone(currentFurniture.parameters?.layout || draftLayout);
          syncLayoutForm();
          renderBayEditor();
          syncBayTargetSelect();
          renderRecommendLists(res.data.recommendation || {});
          document.getElementById('layout-msg').textContent = `${btn.dataset.action === 'remove' ? 'Removed' : 'Applied'} ${btn.dataset.id} — components regenerated.`;
          await renderComponents(selectedId);
          if (activeTab === 'view2d') await draw2d(selectedId);
          if (activeTab === 'view3d') await draw3d(selectedId);
        } catch (err) {
          document.getElementById('layout-msg').textContent = err?.message || 'Failed to apply configuration';
        } finally {
          btn.disabled = false;
        }
      };
    });
  };

  const refreshRecommendations = async () => {
    if (!selectedId || !draftLayout) return;
    readLayoutForm();
    const p = currentFurniture?.parameters || {};
    try {
      const res = await api.post(`/api/v1/furniture/instances/${selectedId}/recommend-internals`, {
        layout: draftLayout,
        width: Number(document.querySelector('[data-param="width"]')?.value || p.width || currentFurniture?.width_mm || 0),
        height: Number(document.querySelector('[data-param="height"]')?.value || p.height || currentFurniture?.height_mm || 0),
        depth: Number(document.querySelector('[data-param="depth"]')?.value || p.depth || currentFurniture?.depth_mm || 0),
      });
      renderRecommendLists(res.data || {});
    } catch (err) {
      const host = document.getElementById('recommend-lists');
      if (host) host.innerHTML = `<p class="muted">${err?.message || 'Recommendations unavailable'}</p>`;
    }
  };

  const renderPresets = async (furniture) => {
    const category = furniture.category || templatesByCode[furniture.type]?.category || 'STORAGE';
    const door = draftLayout?.door_type || furniture.parameters?.door_type || 'HINGED';
    let presets = [];
    try {
      const res = await api.get(`/api/v1/furniture/layout-presets?category=${encodeURIComponent(category)}`);
      presets = (res.data || []).map((p) => {
        const layout = clone(p.layout || {});
        if (door && layout.door_type !== 'NONE') layout.door_type = door;
        return { id: p.id, label: p.label, layout };
      });
    } catch {
      presets = layoutPresetsFallback(category, door);
    }
    if (!presets.length) presets = layoutPresetsFallback(category, door);
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
        syncBayTargetSelect();
        refreshRecommendations();
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
            <div class="sec-move" title="Section order is top → bottom">
              <button type="button" data-bi="${bi}" data-si="${si}" class="sec-up secondary" ${si === 0 ? 'disabled' : ''} aria-label="Move section up">↑</button>
              <button type="button" data-bi="${bi}" data-si="${si}" class="sec-down secondary" ${si >= (bay.sections.length - 1) ? 'disabled' : ''} aria-label="Move section down">↓</button>
            </div>
            <select data-bi="${bi}" data-si="${si}" class="sec-type">
              ${['HANGING','SHELVES','DRAWERS','OPEN','MIRROR'].map((t) => `<option ${sec.type === t ? 'selected' : ''}>${t}</option>`).join('')}
            </select>
            <input data-bi="${bi}" data-si="${si}" class="sec-label" value="${sec.label || ''}" placeholder="Label" />
            <label>H <input data-bi="${bi}" data-si="${si}" class="sec-h" type="number" placeholder="auto" value="${sec.height_mm ?? ''}" style="width:4.5rem"></label>
            <label>Shelves <input data-bi="${bi}" data-si="${si}" class="sec-shelves" type="number" value="${sec.shelf_count ?? 0}" style="width:3.5rem"></label>
            <label>Drawers <input data-bi="${bi}" data-si="${si}" class="sec-drawers" type="number" value="${sec.drawer_count ?? 0}" style="width:3.5rem"></label>
            <label>Dr H <input data-bi="${bi}" data-si="${si}" class="sec-drh" type="number" value="${sec.drawer_height_mm ?? 180}" style="width:3.5rem"></label>
            ${sec.type === 'HANGING' ? `
            <label title="standard / long (top shelf) / double (two rods)">Hanging
              <select data-bi="${bi}" data-si="${si}" class="sec-hang-style">
                ${['standard', 'long', 'double'].map((s) => `<option value="${s}" ${String(sec.hanging_style || 'standard') === s ? 'selected' : ''}>${s}</option>`).join('')}
              </select>
            </label>
            ` : ''}
            ${sec.type === 'SHELVES' ? `
            <label title="standard / shoe / plate_tray / bottle">Shelf style
              <select data-bi="${bi}" data-si="${si}" class="sec-shelf-style">
                ${['standard', 'shoe', 'plate_tray', 'bottle'].map((s) => `<option value="${s}" ${String(sec.shelf_style || 'standard') === s ? 'selected' : ''}>${s}</option>`).join('')}
              </select>
            </label>
            ` : ''}
            ${sec.type === 'DRAWERS' ? `
            <label title="Adds cutlery organizer hardware to manufacturing BOM" class="sec-cutlery-wrap">
              <input data-bi="${bi}" data-si="${si}" class="sec-cutlery" type="checkbox" ${sec.cutlery_organizer ? 'checked' : ''}> Cutlery org
            </label>
            <label title="Wicker/laundry baskets instead of wood drawer boxes" class="sec-wicker-wrap">
              <input data-bi="${bi}" data-si="${si}" class="sec-wicker" type="checkbox" ${sec.wicker_basket ? 'checked' : ''}> Wicker
            </label>
            ` : ''}
            ${sec.type === 'OPEN' ? `
            <label title="Specialty open bay">Open type
              <select data-bi="${bi}" data-si="${si}" class="sec-open-style">
                ${[
                  ['standard', 'standard'],
                  ['waste', 'waste bin'],
                  ['trouser', 'trouser rack'],
                  ['hob', 'hob bay'],
                ].map(([v, lab]) => {
                  let cur = 'standard';
                  if (sec.waste_bin) cur = 'waste';
                  else if (sec.trouser_rack) cur = 'trouser';
                  else if (sec.hob_bay) cur = 'hob';
                  return `<option value="${v}" ${cur === v ? 'selected' : ''}>${lab}</option>`;
                }).join('')}
              </select>
            </label>
            ` : ''}
            ${sec.type === 'MIRROR' ? `
            <label title="Inset from section edges; 0 = full-section glass">Mirror margin <input data-bi="${bi}" data-si="${si}" class="sec-mmargin" type="number" value="${sec.mirror_margin_mm ?? 80}" style="width:3.5rem"></label>
            <label title="Optional override; blank uses section − 2×margin">Mirror W <input data-bi="${bi}" data-si="${si}" class="sec-mw" type="number" placeholder="auto" value="${sec.mirror_width_mm ?? ''}" style="width:4rem"></label>
            <label title="Optional override; blank uses section − 2×margin">Mirror H <input data-bi="${bi}" data-si="${si}" class="sec-mh" type="number" placeholder="auto" value="${sec.mirror_height_mm ?? ''}" style="width:4rem"></label>
            ` : ''}
            <button data-bi="${bi}" data-si="${si}" class="del-sec secondary">✕</button>
          </div>`).join('')}
      </div>`).join('') || '<p class="muted">No bays — add one or pick a preset.</p>';

    const moveSection = (bi, si, delta) => {
      const sections = draftLayout.bays[bi]?.sections;
      if (!sections) return;
      const from = Number(si);
      const to = from + delta;
      if (to < 0 || to >= sections.length) return;
      const [item] = sections.splice(from, 1);
      sections.splice(to, 0, item);
      renderBayEditor();
    };

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
    host.querySelectorAll('.sec-up').forEach((el) => el.onclick = () => moveSection(Number(el.dataset.bi), Number(el.dataset.si), -1));
    host.querySelectorAll('.sec-down').forEach((el) => el.onclick = () => moveSection(Number(el.dataset.bi), Number(el.dataset.si), 1));
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
    host.querySelectorAll('.sec-hang-style').forEach((el) => el.onchange = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].hanging_style = el.value || 'standard';
    });
    host.querySelectorAll('.sec-shelf-style').forEach((el) => el.onchange = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].shelf_style = el.value || 'standard';
    });
    host.querySelectorAll('.sec-cutlery').forEach((el) => el.onchange = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].cutlery_organizer = !!el.checked;
    });
    host.querySelectorAll('.sec-wicker').forEach((el) => el.onchange = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].wicker_basket = !!el.checked;
    });
    host.querySelectorAll('.sec-open-style').forEach((el) => el.onchange = () => {
      const sec = draftLayout.bays[el.dataset.bi].sections[el.dataset.si];
      sec.waste_bin = el.value === 'waste';
      sec.trouser_rack = el.value === 'trouser';
      sec.hob_bay = el.value === 'hob';
      if (el.value === 'waste') sec.label = sec.label || 'Waste bin';
      if (el.value === 'trouser') sec.label = sec.label || 'Trouser pull-out';
      if (el.value === 'hob') sec.label = sec.label || 'Hob bay';
    });
    host.querySelectorAll('.sec-mmargin').forEach((el) => el.oninput = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].mirror_margin_mm = el.value === '' ? 80 : Number(el.value);
    });
    host.querySelectorAll('.sec-mw').forEach((el) => el.oninput = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].mirror_width_mm = el.value === '' ? null : Number(el.value);
    });
    host.querySelectorAll('.sec-mh').forEach((el) => el.oninput = () => {
      draftLayout.bays[el.dataset.bi].sections[el.dataset.si].mirror_height_mm = el.value === '' ? null : Number(el.value);
    });
    host.querySelectorAll('.del-sec').forEach((el) => el.onclick = () => {
      draftLayout.bays[el.dataset.bi].sections.splice(Number(el.dataset.si), 1);
      renderBayEditor();
    });
    syncBayTargetSelect();
  };

  const renderExpoOptions = (furniture) => {
    const options = furniture?.expo_options || [];
    const html = options.length
      ? options.map((o) => `
          <label class="expo-check" title="${o.role}">
            <input type="checkbox" class="expo-role" data-role="${o.role}" ${o.expo ? 'checked' : ''}>
            <span>${o.label}${o.count > 1 ? ` (${o.count})` : ''}</span>
            ${o.expo ? '<em class="expo-badge">EXPO</em>' : ''}
          </label>`).join('')
      : '<p class="muted">No eligible components yet.</p>';
    const a = document.getElementById('expo-options');
    const b = document.getElementById('expo-options-comp');
    if (a) a.innerHTML = html;
    if (b) b.innerHTML = html;
  };

  const collectExpo = () => {
    const expo = {};
    document.querySelectorAll('#expo-options .expo-role, #expo-options-comp .expo-role').forEach((el) => {
      // Prefer finishes pane if both exist; last write wins — keep consistent by reading one host
    });
    const host = document.getElementById('expo-options');
    (host ? host.querySelectorAll('.expo-role') : []).forEach((el) => {
      expo[el.dataset.role] = !!el.checked;
    });
    return expo;
  };

  const bindExpoSync = () => {
    const sync = (sourceId, targetId) => {
      const src = document.getElementById(sourceId);
      const dst = document.getElementById(targetId);
      if (!src || !dst) return;
      src.querySelectorAll('.expo-role').forEach((el) => {
        el.onchange = () => {
          const other = dst.querySelector(`.expo-role[data-role="${el.dataset.role}"]`);
          if (other) other.checked = el.checked;
          const badgeHost = el.closest('.expo-check');
          if (badgeHost) {
            const existing = badgeHost.querySelector('.expo-badge');
            if (el.checked && !existing) {
              badgeHost.insertAdjacentHTML('beforeend', '<em class="expo-badge">EXPO</em>');
            } else if (!el.checked && existing) {
              existing.remove();
            }
          }
          const otherHost = other?.closest('.expo-check');
          if (otherHost) {
            const existing = otherHost.querySelector('.expo-badge');
            if (el.checked && !existing) {
              otherHost.insertAdjacentHTML('beforeend', '<em class="expo-badge">EXPO</em>');
            } else if (!el.checked && existing) {
              existing.remove();
            }
          }
        };
      });
    };
    sync('expo-options', 'expo-options-comp');
    sync('expo-options-comp', 'expo-options');
  };

  const renderComponents = async (furnitureId) => {
    const res = await api.get(`/api/v1/furniture/instances/${furnitureId}/components`);
    const rows = (res.data || []).map((c) => {
      const m = c.finish_id ? byId[String(c.finish_id)] : null;
      const finishCell = m
        ? `<span class="finish-inline">${finishThumb(m, 'finish-swatch')}<span>${m.sku}</span></span>`
        : '<span class="muted">—</span>';
      const role = c.geometry?.role || c.manufacturing_data?.role || '';
      const expo = !!(c.geometry?.expo ?? c.manufacturing_data?.expo);
      return `<tr>
        <td>${c.component_key}</td><td>${c.name}${expo ? ' <em class="expo-badge">EXPO</em>' : ''}</td><td>${c.component_type}${role ? ` <span class="muted">(${role})</span>` : ''}</td>
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

  let view2dState = { zoom: 1, panX: 0, panY: 0, data: null, view: 'FRONT' };

  const draw2d = async (furnitureId) => {
    const view = document.getElementById('view2d')?.value || 'FRONT';
    const res = await api.get(`/api/v1/furniture/instances/${furnitureId}/2d?view=${view}`);
    const d = res.data;
    view2dState.data = d;
    view2dState.view = view;
    paint2d();
  };

  const paint2d = () => {
    const d = view2dState.data;
    if (!d) return;
    const view = view2dState.view;
    const canvas = document.getElementById('furn-2d');
    if (!canvas) return;
    const wrap = canvas.parentElement;
    const targetW = Math.max(900, wrap?.clientWidth || 1100);
    const targetH = Math.max(560, Math.min(820, Math.round(targetW * 0.62)));
    if (canvas.width !== targetW || canvas.height !== targetH) {
      canvas.width = targetW;
      canvas.height = targetH;
    }
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#fbfcfd';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const bw = Math.max(1, d.bounds.width || 1);
    const bh = Math.max(1, view === 'PLAN' ? d.bounds.depth : d.bounds.height);
    // Expand content box by annotation extents (mm) so Fit never clamps labels onto geometry.
    let minX = 0;
    let minY = 0;
    let maxX = bw;
    let maxY = bh;
    (d.dimensions || []).forEach((dim) => {
      const xs = [dim.from?.[0], dim.to?.[0]].filter((n) => Number.isFinite(n));
      const ys = [dim.from?.[1], dim.to?.[1]].filter((n) => Number.isFinite(n));
      xs.forEach((x) => { minX = Math.min(minX, x); maxX = Math.max(maxX, x); });
      ys.forEach((y) => { minY = Math.min(minY, y); maxY = Math.max(maxY, y); });
    });
    // Room for leader-line callouts outside the carcass.
    const calloutPad = Math.max(120, bw * 0.06);
    minX -= calloutPad * 0.35;
    maxX += calloutPad;
    minY -= Math.max(40, calloutPad * 0.25);
    maxY += 20;
    const contentW = Math.max(1, maxX - minX);
    const contentH = Math.max(1, maxY - minY);
    const marginL = 48;
    const marginR = 48;
    const marginT = 36;
    const marginB = 28;
    const baseScale = Math.min(
      (canvas.width - marginL - marginR) / contentW,
      (canvas.height - marginT - marginB) / contentH
    );
    const scale = baseScale * view2dState.zoom;
    const ox = marginL - minX * scale + view2dState.panX;
    const oy = marginT - minY * scale + view2dState.panY;
    const mapX = (x) => ox + x * scale;
    const mapY = (y) => oy + y * scale;

    const skipInnerLabel = new Set(['outer', 'inner', 'bay', 'expo', 'carcass']);
    const colors = {
      bay: '#dfe9f2', hanging: '#e8f5e9', shelves: '#fff8e1', drawers: '#fce4ec',
      open: '#f3f5f7', niche: '#efe8e0', mirror: '#cfd8dc', loft: '#e3f2fd', plinth: '#eceff1', filler: '#e8eaf6',
      expo: 'rgba(30, 136, 229, 0.22)',
    };

    const drawDimLine = (dim) => {
      const x1 = mapX(dim.from[0]);
      const y1 = mapY(dim.from[1]);
      const x2 = mapX(dim.to[0]);
      const y2 = mapY(dim.to[1]);
      const horizontal = dim.axis === 'H' || Math.abs(y2 - y1) < Math.abs(x2 - x1);
      ctx.save();
      ctx.strokeStyle = '#c62828';
      ctx.fillStyle = '#c62828';
      ctx.lineWidth = 1;
      ctx.setLineDash([]);
      ctx.beginPath();
      ctx.moveTo(x1, y1);
      ctx.lineTo(x2, y2);
      ctx.stroke();
      // End ticks
      const tick = 6;
      if (horizontal) {
        ctx.beginPath();
        ctx.moveTo(x1, y1 - tick);
        ctx.lineTo(x1, y1 + tick);
        ctx.moveTo(x2, y2 - tick);
        ctx.lineTo(x2, y2 + tick);
        ctx.stroke();
      } else {
        ctx.beginPath();
        ctx.moveTo(x1 - tick, y1);
        ctx.lineTo(x1 + tick, y1);
        ctx.moveTo(x2 - tick, y2);
        ctx.lineTo(x2 + tick, y2);
        ctx.stroke();
      }
      const label = String(dim.label ?? dim.value ?? '');
      ctx.font = '600 12px "Segoe UI", sans-serif';
      const tw = ctx.measureText(label).width;
      if (horizontal) {
        const mx = (x1 + x2) / 2;
        const my = y1 - 6;
        ctx.fillStyle = 'rgba(251,252,253,0.92)';
        ctx.fillRect(mx - tw / 2 - 3, my - 11, tw + 6, 14);
        ctx.fillStyle = '#c62828';
        ctx.textAlign = 'center';
        ctx.fillText(label, mx, my);
      } else {
        // Keep vertical labels outside the carcass (left of left dims, right of right dims).
        const midXmm = (dim.from[0] + dim.to[0]) / 2;
        const outsideRight = midXmm > bw * 0.5;
        const mx = outsideRight ? x1 + 12 : x1 - 8;
        const my = (y1 + y2) / 2;
        ctx.save();
        ctx.translate(mx, my);
        ctx.rotate(-Math.PI / 2);
        ctx.fillStyle = 'rgba(251,252,253,0.92)';
        ctx.fillRect(-tw / 2 - 3, -11, tw + 6, 14);
        ctx.fillStyle = '#c62828';
        ctx.textAlign = 'center';
        ctx.fillText(label, 0, 0);
        ctx.restore();
      }
      ctx.textAlign = 'left';
      ctx.restore();
    };

    const drawCallout = (el, stackIndex = 0) => {
      const ax = mapX(el.anchor_x ?? el.x ?? 0);
      const ay = mapY(el.anchor_y ?? el.y ?? 0);
      const side = el.side || 'right';
      const gap = 28 + stackIndex * 18;
      const stackY = stackIndex * 16;
      let lx = ax;
      let ly = ay;
      if (side === 'left') { lx = ax - gap - 36; ly = ay + stackY; }
      else if (side === 'right') { lx = ax + gap + 8; ly = ay + stackY; }
      else if (side === 'top') { lx = ax + 8 + stackIndex * 12; ly = ay - gap - 4; }
      else if (side === 'bottom') { lx = ax + 8 + stackIndex * 12; ly = ay + gap + 10; }

      ctx.save();
      ctx.strokeStyle = '#1565c0';
      ctx.fillStyle = '#0d47a1';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(ax, ay);
      if (side === 'left' || side === 'right') {
        ctx.lineTo(side === 'left' ? lx + 34 : lx - 2, ly);
      } else {
        ctx.lineTo(ax, ly);
        ctx.lineTo(lx, ly);
      }
      ctx.stroke();
      // Anchor dot
      ctx.beginPath();
      ctx.arc(ax, ay, 2.2, 0, Math.PI * 2);
      ctx.fill();
      const text = el.text || 'EXPO';
      ctx.font = 'bold 11px "Segoe UI", sans-serif';
      const tw = ctx.measureText(text).width;
      ctx.fillStyle = 'rgba(251,252,253,0.95)';
      ctx.fillRect(lx - 2, ly - 11, tw + 6, 14);
      ctx.fillStyle = '#0d47a1';
      ctx.textAlign = 'left';
      ctx.fillText(text, lx, ly);
      ctx.restore();
    };

    const callouts = [];
    d.elements.forEach((el) => {
      if (el.type === 'rect') {
        if (el.role === 'expo') {
          ctx.fillStyle = colors.expo;
          ctx.fillRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
          ctx.strokeStyle = '#1565c0';
          ctx.lineWidth = 2;
          ctx.setLineDash([5, 3]);
          ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
          ctx.setLineDash([]);
          return;
        }
        ctx.fillStyle = colors[el.role] || 'transparent';
        if (colors[el.role]) ctx.fillRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
        ctx.strokeStyle = '#1c2430';
        ctx.lineWidth = 1.5;
        ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
        // Only name functional zones (not carcass/bay frames) when there is room.
        // Keep labels clear of the top/bottom stroke of the section box.
        if (
          el.label
          && !skipInnerLabel.has(el.role)
          && el.w * scale > 48
          && el.h * scale > 28
        ) {
          const padX = 6;
          const padY = Math.min(18, Math.max(12, el.h * scale * 0.22));
          ctx.fillStyle = el.role === 'mirror' ? '#0d47a1' : '#334';
          ctx.font = (el.role === 'mirror' ? '600 ' : '') + '12px "Segoe UI", sans-serif';
          ctx.fillText(el.label, mapX(el.x) + padX, mapY(el.y) + padY);
        }
      } else if (el.type === 'line') {
        ctx.strokeStyle = el.expo ? '#1565c0' : '#666';
        ctx.lineWidth = el.expo ? 2.5 : 1;
        ctx.beginPath();
        ctx.moveTo(mapX(el.x1), mapY(el.y1));
        ctx.lineTo(mapX(el.x2), mapY(el.y2));
        ctx.stroke();
        ctx.lineWidth = 1;
      } else if (el.type === 'callout' || (el.type === 'label' && el.role === 'expo-label')) {
        callouts.push(el.type === 'callout' ? el : {
          ...el,
          text: el.text || 'EXPO',
          anchor_x: el.x,
          anchor_y: el.y,
          side: el.x < (d.bounds.width || 0) * 0.35 ? 'left' : 'right',
        });
      } else if (el.type === 'label') {
        // Non-EXPO labels: keep only if clearly outside content (rare).
        ctx.fillStyle = '#334';
        ctx.font = '12px sans-serif';
        ctx.fillText(el.text || '', mapX(el.x), mapY(el.y));
      }
    });

    // Stack callouts per side so multiple EXPO tags do not collide.
    const sideCount = { left: 0, right: 0, top: 0, bottom: 0 };
    callouts.forEach((c) => {
      const side = c.side || 'right';
      drawCallout(c, sideCount[side] || 0);
      sideCount[side] = (sideCount[side] || 0) + 1;
    });

    (d.dimensions || []).forEach((dim) => drawDimLine(dim));
  };

  const loadTexture = (loader, url) => new Promise((resolve, reject) => {
    loader.load(
      url,
      (tex) => {
        tex.colorSpace = THREE.SRGBColorSpace;
        tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
        tex.generateMipmaps = true;
        tex.minFilter = THREE.LinearMipmapLinearFilter;
        tex.magFilter = THREE.LinearFilter;
        tex.needsUpdate = true;
        resolve(tex);
      },
      undefined,
      reject
    );
  });

  /** Average sRGB from a loaded albedo — matches catalog swatch better than lit/tonemapped preview alone. */
  const sampleTextureAverage = (tex) => {
    const img = tex?.image;
    if (!img) return null;
    const iw = img.naturalWidth || img.width || 0;
    const ih = img.naturalHeight || img.height || 0;
    if (!iw || !ih) return null;
    const w = Math.min(48, iw);
    const h = Math.min(48, ih);
    const c = document.createElement('canvas');
    c.width = w;
    c.height = h;
    const ctx = c.getContext('2d', { willReadFrequently: true });
    if (!ctx) return null;
    try {
      ctx.drawImage(img, 0, 0, w, h);
      const data = ctx.getImageData(0, 0, w, h).data;
      let r = 0;
      let g = 0;
      let b = 0;
      let n = 0;
      for (let i = 0; i < data.length; i += 4) {
        r += data[i];
        g += data[i + 1];
        b += data[i + 2];
        n += 1;
      }
      if (!n) return null;
      r = Math.round(r / n);
      g = Math.round(g / n);
      b = Math.round(b / n);
      const lum = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
      return { r, g, b, lum, hex: `#${[r, g, b].map((x) => x.toString(16).padStart(2, '0')).join('')}` };
    } catch {
      return null;
    }
  };

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
    const ox = bounds.min_x ?? 0;
    const oz = bounds.min_z ?? 0;
    const gw = bounds.width + pad * 2;
    const gd = bounds.depth + pad * 2;
    const floor = new THREE.Mesh(
      new THREE.PlaneGeometry(gw, gd),
      new THREE.MeshStandardMaterial({ color: 0xd8dde3, roughness: 0.92, metalness: 0 })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.position.set(ox + bounds.width / 2, 0, oz + bounds.depth / 2);
    floor.receiveShadow = true;
    g.add(floor);
    const step = 500;
    const lineMat = new THREE.LineBasicMaterial({ color: 0xb0b8c2 });
    const pts = [];
    for (let x = ox - pad; x <= ox + bounds.width + pad + 0.1; x += step) {
      pts.push(new THREE.Vector3(x, 0.5, oz - pad), new THREE.Vector3(x, 0.5, oz + bounds.depth + pad));
    }
    for (let z = oz - pad; z <= oz + bounds.depth + pad + 0.1; z += step) {
      pts.push(new THREE.Vector3(ox - pad, 0.5, z), new THREE.Vector3(ox + bounds.width + pad, 0.5, z));
    }
    const geo = new THREE.BufferGeometry().setFromPoints(pts);
    g.add(new THREE.LineSegments(geo, lineMat));
    return g;
  };

  const makeRoomBackdrop = (bounds, expoMap = {}) => {
    // Presentation-only room shell. Walls are solid boxes (visible when orbiting).
    // EXPO sides face the room → no wall on that side.
    // Non-EXPO sides are treated as wall-adjacent → wall flush to that face.
    const g = new THREE.Group();
    g.name = 'room-backdrop';
    const wallMat = new THREE.MeshStandardMaterial({
      color: 0xf4f6f8, roughness: 0.92, metalness: 0, envMapIntensity: 0.35,
    });
    const floorMat = new THREE.MeshStandardMaterial({
      color: 0xb9c2cc, roughness: 0.78, metalness: 0.02, envMapIntensity: 0.25,
    });
    const wallT = 80;
    const roomH = bounds.height + 600;
    const floorPad = 400;
    const minX = bounds.min_x ?? 0;
    const maxX = bounds.max_x ?? (minX + bounds.width);
    const minZ = bounds.min_z ?? 0;
    const maxZ = bounds.max_z ?? (minZ + bounds.depth);
    const spanX = maxX - minX;
    const spanZ = maxZ - minZ;
    const cx = (minX + maxX) / 2;
    const cz = (minZ + maxZ) / 2;

    const leftFiller = (bounds.filler_left || 0) > 0;
    const rightFiller = (bounds.filler_right || 0) > 0;
    // EXPO side panel faces the room → omit that wall.
    // Filler present ⇒ wall stays (installation against wall); filler strip may still be EXPO on its visible face.
    const leftExpo = !!expoMap.LEFT_PANEL;
    const rightExpo = !!expoMap.RIGHT_PANEL;
    const backExpo = !!expoMap.BACK_PANEL;
    const showLeftWall = leftFiller || !leftExpo;
    const showRightWall = rightFiller || !rightExpo;
    const showBackWall = !backExpo;

    const floorExtraL = showLeftWall ? wallT : floorPad;
    const floorExtraR = showRightWall ? wallT : floorPad;
    const floor = new THREE.Mesh(
      new THREE.BoxGeometry(spanX + floorExtraL + floorExtraR + floorPad, 40, spanZ + floorPad * 2 + (showBackWall ? wallT : 0)),
      floorMat
    );
    floor.position.set(
      cx + (floorExtraR - floorExtraL) / 2,
      -20,
      cz + floorPad * 0.15
    );
    floor.receiveShadow = true;
    floor.name = 'room-floor';
    g.add(floor);

    if (showBackWall) {
      const back = new THREE.Mesh(
        new THREE.BoxGeometry(spanX + (showLeftWall ? wallT : 0) + (showRightWall ? wallT : 0), roomH, wallT),
        wallMat
      );
      back.position.set(
        cx + ((showRightWall ? wallT : 0) - (showLeftWall ? wallT : 0)) / 2,
        roomH / 2 - 100,
        minZ - wallT / 2
      );
      back.receiveShadow = true;
      back.castShadow = true;
      back.name = 'room-wall-back';
      g.add(back);
    }

    if (showLeftWall) {
      const left = new THREE.Mesh(
        new THREE.BoxGeometry(wallT, roomH, spanZ + (showBackWall ? wallT : 0)),
        wallMat
      );
      left.position.set(minX - wallT / 2, roomH / 2 - 100, cz - (showBackWall ? wallT / 2 : 0));
      left.receiveShadow = true;
      left.castShadow = true;
      left.name = 'room-wall-left';
      g.add(left);
    }

    if (showRightWall) {
      const right = new THREE.Mesh(
        new THREE.BoxGeometry(wallT, roomH, spanZ + (showBackWall ? wallT : 0)),
        wallMat
      );
      right.position.set(maxX + wallT / 2, roomH / 2 - 100, cz - (showBackWall ? wallT / 2 : 0));
      right.receiveShadow = true;
      right.castShadow = true;
      right.name = 'room-wall-right';
      g.add(right);
    }

    g.userData.walls = { left: showLeftWall, right: showRightWall, back: showBackWall };
    return g;
  };

  const makePresentationEnv = (renderer) => {
    // Procedural environment (no HDRI assets in repo). Uses Three.js PMREMGenerator only.
    if (!THREE.PMREMGenerator) return null;
    const pmrem = new THREE.PMREMGenerator(renderer);
    pmrem.compileEquirectangularShader?.();
    const envScene = new THREE.Scene();
    envScene.add(new THREE.AmbientLight(0xffffff, 0.6));
    const hemi = new THREE.HemisphereLight(0xf0f4ff, 0x8a9098, 1.0);
    envScene.add(hemi);
    const top = new THREE.Mesh(
      new THREE.SphereGeometry(50, 32, 16),
      new THREE.MeshBasicMaterial({ color: 0xe8eef5, side: THREE.BackSide })
    );
    envScene.add(top);
    const ground = new THREE.Mesh(
      new THREE.CircleGeometry(40, 32),
      new THREE.MeshBasicMaterial({ color: 0x9aa3ad })
    );
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = -8;
    envScene.add(ground);
    const rt = pmrem.fromScene(envScene, 0.04);
    pmrem.dispose();
    return rt.texture;
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
      <span>W × H × D</span>`;
  };

  const applyCameraPreset = (preset, camera, bounds, target, margin = 1.18) => {
    const ox = bounds.min_x ?? 0;
    const oz = bounds.min_z ?? 0;
    const cx = ox + bounds.width / 2;
    const cy = bounds.height * 0.5;
    const cz = oz + bounds.depth / 2;
    target.set(cx, cy, cz);
    const aspect = camera.aspect || 1;
    const fov = (camera.fov * Math.PI) / 180;

    const setLook = (x, y, z) => {
      camera.position.set(x, y, z);
      camera.lookAt(target);
      camera.updateProjectionMatrix();
    };

    // Distance so the full AABB fits in the frustum with margin.
    const fitDistForSize = (fitW, fitH) => {
      const distH = (fitH * 0.5 * margin) / Math.tan(fov / 2);
      const distW = (fitW * 0.5 * margin) / (Math.tan(fov / 2) * aspect);
      return Math.max(distH, distW);
    };

    switch (preset) {
      case 'front': {
        const dist = fitDistForSize(bounds.width, bounds.height) + bounds.depth * 0.5;
        setLook(cx, cy, cz + dist);
        break;
      }
      case 'left': {
        const dist = fitDistForSize(bounds.depth, bounds.height) + bounds.width * 0.5;
        setLook(cx - dist, cy, cz);
        break;
      }
      case 'right': {
        const dist = fitDistForSize(bounds.depth, bounds.height) + bounds.width * 0.5;
        setLook(cx + dist, cy, cz);
        break;
      }
      case 'top': {
        const dist = fitDistForSize(bounds.width, bounds.depth) + bounds.height * 0.5;
        setLook(cx, cy + dist, cz + 1);
        break;
      }
      case 'close': {
        const R = Math.sqrt(bounds.width ** 2 + bounds.height ** 2 + bounds.depth ** 2);
        setLook(cx + R * 0.35, cy * 0.9, cz + R * 0.55);
        break;
      }
      case 'iso':
      default: {
        // True isometric-style: yaw 45°, elevation ≈ 35.264° (arcsin(tan 30°)).
        const yaw = Math.PI / 4;
        const pitch = Math.atan(Math.SQRT1_2);
        const dir = new THREE.Vector3(
          Math.cos(pitch) * Math.sin(yaw),
          Math.sin(pitch),
          Math.cos(pitch) * Math.cos(yaw)
        ).normalize();
        // Project AABB corners onto view axes to fit the full unit.
        const corners = [];
        for (const x of [ox, ox + bounds.width]) {
          for (const y of [0, bounds.height]) {
            for (const z of [oz, oz + bounds.depth]) {
              corners.push(new THREE.Vector3(x, y, z).sub(target));
            }
          }
        }
        const up = new THREE.Vector3(0, 1, 0);
        const right = new THREE.Vector3().crossVectors(dir, up).normalize();
        // If looking nearly along up, fall back.
        if (right.lengthSq() < 1e-6) right.set(1, 0, 0);
        const trueUp = new THREE.Vector3().crossVectors(right, dir).normalize();
        let maxR = 0;
        let maxU = 0;
        corners.forEach((c) => {
          maxR = Math.max(maxR, Math.abs(c.dot(right)));
          maxU = Math.max(maxU, Math.abs(c.dot(trueUp)));
        });
        const dist = fitDistForSize(maxR * 2, maxU * 2);
        const pos = target.clone().addScaledVector(dir, dist);
        setLook(pos.x, pos.y, pos.z);
        break;
      }
    }
  };

  const qualitySettings = (q) => {
    // Lower exposure + Reinhard preserves dark laminate hues (ACES shifts burgundy→purple).
    if (q === 'presentation') return { pixelRatio: Math.min(window.devicePixelRatio || 1, 2.5), shadowMap: 2048, exposure: 0.92 };
    if (q === 'high') return { pixelRatio: Math.min(window.devicePixelRatio || 1, 2), shadowMap: 1536, exposure: 0.95 };
    return { pixelRatio: Math.min(window.devicePixelRatio || 1, 1.5), shadowMap: 1024, exposure: 1.0 };
  };

  const draw3d = async (furnitureId, options = {}) => {
    const host = document.getElementById(options.hostId || 'furn-3d');
    if (!host) return;
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

    const model = options.model
      ? { data: options.model }
      : await api.get(`/api/v1/furniture/instances/${furnitureId}/3d-model`);
    const b = model.data.bounds;
    const title = document.getElementById(options.titleId || 'view3d-title');
    const sub = document.getElementById(options.subId || 'view3d-sub');
    if (title) title.textContent = options.titleText || currentFurniture?.name || '3D Presentation';
    if (sub) sub.textContent = options.subText || `${Math.round(b.width)} × ${Math.round(b.height)} × ${Math.round(b.depth)} mm`;

    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0xdde5ee);
    scene.fog = new THREE.Fog(0xdde5ee, Math.max(b.width, b.depth) * 3.5, Math.max(b.width, b.depth) * 10);

    const wrap = document.getElementById(options.wrapId || 'furn-3d-wrap');
    const heightPx = Math.max(520, Math.min(820, wrap?.clientHeight || 640));
    const widthPx = Math.max(1, host.clientWidth || wrap?.clientWidth || 960);
    const camera = new THREE.PerspectiveCamera(32, widthPx / heightPx, 1, 200000);
    const ox = b.min_x ?? 0;
    const oz = b.min_z ?? 0;
    const target = new THREE.Vector3(ox + b.width / 2, b.height * 0.48, oz + b.depth / 2);
    applyCameraPreset(options.camPreset || document.getElementById('cam-preset')?.value || 'iso', camera, b, target);

    const q = qualitySettings(document.getElementById('viz-quality')?.value || 'high');
    renderer3d = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true, alpha: false, powerPreference: 'high-performance' });
    renderer3d.setPixelRatio(q.pixelRatio);
    renderer3d.setSize(widthPx, heightPx);
    renderer3d.outputColorSpace = THREE.SRGBColorSpace;
    // Reinhard keeps dark red/burgundy laminates closer to catalog swatches than ACES.
    renderer3d.toneMapping = THREE.ReinhardToneMapping;
    renderer3d.toneMappingExposure = q.exposure;
    renderer3d.shadowMap.enabled = !!document.getElementById('scale-shadows')?.checked;
    renderer3d.shadowMap.type = THREE.PCFSoftShadowMap;
    host.appendChild(renderer3d.domElement);

    const envMap = makePresentationEnv(renderer3d);
    if (envMap) {
      scene.environment = envMap;
    }

    // Neutral daylight — warm keys push dark laminates toward purple under tonemapping.
    scene.add(new THREE.AmbientLight(0xffffff, 0.32));
    scene.add(new THREE.HemisphereLight(0xf4f6f8, 0x9aa3ad, 0.55));
    const key = new THREE.DirectionalLight(0xffffff, 1.05);
    key.position.set(b.width * 1.6, b.height * 2.8, b.depth * 2.4);
    key.castShadow = true;
    key.shadow.mapSize.set(q.shadowMap, q.shadowMap);
    key.shadow.camera.near = 10;
    key.shadow.camera.far = Math.max(b.width, b.height, b.depth) * 8;
    const sExt = Math.max(b.width, b.depth) * 1.5;
    key.shadow.camera.left = -sExt;
    key.shadow.camera.right = sExt;
    key.shadow.camera.top = sExt;
    key.shadow.camera.bottom = -sExt;
    key.shadow.bias = -0.00012;
    key.shadow.normalBias = 1.5;
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xe8eef5, 0.42);
    fill.position.set(-b.width * 1.2, b.height * 1.5, -b.depth * 0.6);
    scene.add(fill);
    const rim = new THREE.DirectionalLight(0xffffff, 0.18);
    rim.position.set(b.width * 0.15, b.height * 1.6, -b.depth * 2.2);
    scene.add(rim);

    const room = makeRoomBackdrop(b, model.data.expo || {});
    scene.add(room);
    const helpers = new THREE.Group();
    helpers.name = 'scale-helpers';
    scene.add(helpers);
    const person = makePersonFigure(1700);
    person.position.set(ox + b.width / 2 + Math.max(500, b.width * 0.15), 0, oz + b.depth + 450);
    person.traverse((o) => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = true; } });
    helpers.add(person);
    const grid = makeFloorGrid(b);
    helpers.add(grid);

    const syncHelpers = () => {
      const present = document.getElementById('pres-mode')?.dataset.on === '1';
      person.visible = !present && !!document.getElementById('scale-person')?.checked;
      grid.visible = !present && !!document.getElementById('scale-grid')?.checked;
      room.visible = !!document.getElementById('scale-room')?.checked;
      renderer3d.shadowMap.enabled = !!document.getElementById('scale-shadows')?.checked;
      updateSizeOverlay(b);
      const sizeEl = document.getElementById('furn-3d-size');
      if (present && sizeEl) sizeEl.hidden = true;
      helpers.children.forEach((c) => {
        if (c.type === 'LineSegments') {
          c.visible = c.userData.keepInPresentation ? true : !present;
        }
      });
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

    // Placement rotation_y is CCW degrees about Y (KitchenPlacement); Three.js yaw is opposite.
    const applyMeshPose = (obj, meshData) => {
      obj.position.set(meshData.position[0], meshData.position[1], meshData.position[2]);
      const ry = Number(meshData.rotation_y || 0);
      obj.rotation.y = ry ? -THREE.MathUtils.degToRad(ry) : 0;
    };

    for (const mesh of model.data.meshes) {
      if (mesh.role === 'reveal') {
        const isDrawerGap = !!(mesh.presentation?.drawer_gap);
        const revealMat = new THREE.MeshStandardMaterial({
          color: isDrawerGap ? '#050507' : '#12141a',
          roughness: 0.95,
          metalness: 0,
          envMapIntensity: 0.02,
        });
        const reveal = new THREE.Mesh(
          new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]),
          revealMat
        );
        applyMeshPose(reveal, mesh);
        reveal.receiveShadow = true;
        reveal.castShadow = false;
        reveal.userData.role = 'reveal';
        scene.add(reveal);
        continue;
      }

      if (mesh.role === 'mirror') {
        const mirrorMat = new THREE.MeshStandardMaterial({
          color: '#c5d0da',
          metalness: 0.95,
          roughness: 0.06,
          envMapIntensity: 1.35,
        });
        const mirror = new THREE.Mesh(
          new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]),
          mirrorMat
        );
        applyMeshPose(mirror, mesh);
        mirror.castShadow = true;
        mirror.receiveShadow = true;
        mirror.userData.role = 'mirror';
        mirror.userData.component_role = mesh.component_role || 'MIRROR_PANEL';
        mirror.userData.expo = false;
        scene.add(mirror);
        continue;
      }

      const fallback = mesh.color || (mesh.role === 'shutter' || mesh.role === 'drawer' ? '#2a1820' : '#e6ebf0');
      // Standard material for laminates — Physical clearcoat shifts dark burgundy toward purple.
      const MatClass = THREE.MeshStandardMaterial;
      const makeMat = (fin, colorFallback) => {
        const roughness = fin?.roughness ?? 0.62;
        const metalness = fin?.metalness ?? 0;
        const base = fin?.base_color || colorFallback || fallback;
        return new MatClass({
          color: base,
          roughness,
          metalness,
          envMapIntensity: 0.28,
        });
      };

      let materials;
      const ff = mesh.face_finishes;
      // Open-niche liners are fully client-visible — exterior laminate on all faces.
      if (mesh.role === 'niche' && mesh.expo) {
        const extMat = makeMat(ff?.exterior || mesh.finish, (ff?.exterior || mesh.finish)?.base_color || '#2a1820');
        materials = [extMat, extMat, extMat, extMat, extMat, extMat];
      } else if (ff && (ff.exterior || ff.interior) && ff.expo_face_index != null && mesh.expo) {
        const intMat = makeMat(ff.interior, '#e6ebf0');
        const extMat = makeMat(ff.exterior, ff.exterior?.base_color || '#2a1820');
        materials = [intMat, intMat, intMat, intMat, intMat, intMat];
        materials[ff.expo_face_index] = extMat;
      } else if (ff && !mesh.expo) {
        const intMat = makeMat(ff.interior || ff.exterior, fallback);
        materials = [intMat, intMat, intMat, intMat, intMat, intMat];
      } else {
        materials = makeMat(mesh.finish, fallback);
      }

      const box = new THREE.Mesh(new THREE.BoxGeometry(mesh.size[0], mesh.size[1], mesh.size[2]), materials);
      applyMeshPose(box, mesh);
      box.castShadow = true;
      box.receiveShadow = true;
      box.userData.expo = !!mesh.expo;
      box.userData.component_role = mesh.component_role || null;
      box.userData.role = mesh.role || null;
      scene.add(box);

      // Soft silhouette edges on doors/drawers (presentation) — not manufacturing geometry.
      if (mesh.role === 'shutter' || mesh.role === 'drawer') {
        const edges = new THREE.EdgesGeometry(box.geometry, 20);
        const line = new THREE.LineSegments(
          edges,
          new THREE.LineBasicMaterial({
            color: mesh.role === 'drawer' ? 0x000000 : 0x0a0a0c,
            transparent: true,
            opacity: mesh.role === 'drawer' ? 0.75 : 0.55,
          })
        );
        applyMeshPose(line, mesh);
        line.scale.set(1.002, 1.002, 1.002);
        line.userData.keepInPresentation = true;
        helpers.add(line);
      }

      // Handleless pull groove on drawer fronts — clear visual cue they are drawers, not a door.
      if (mesh.role === 'drawer') {
        const gw = Math.max(80, mesh.size[0] * 0.42);
        const gh = Math.max(3.5, Math.min(6, mesh.size[1] * 0.035));
        const gd = 4;
        const grooveMat = new THREE.MeshStandardMaterial({
          color: 0x030305,
          roughness: 0.98,
          metalness: 0,
          envMapIntensity: 0,
        });
        const groove = new THREE.Mesh(new THREE.BoxGeometry(gw, gh, gd), grooveMat);
        groove.position.set(0, -mesh.size[1] * 0.38, mesh.size[2] * 0.5 - gd * 0.35);
        groove.castShadow = true;
        groove.receiveShadow = true;
        groove.userData.role = 'drawer-groove';
        box.add(groove);
      }

      // EXPO designation outline — structural side/carcass panels only.
      // Niche liners / glass / fillers must not get wireframes (looked like ghost geometry).
      const expoOutlineComponents = new Set([
        'LEFT_PANEL', 'RIGHT_PANEL', 'TOP_PANEL', 'BOTTOM_PANEL', 'BACK_PANEL',
      ]);
      if (mesh.expo && expoOutlineComponents.has(mesh.component_role)) {
        const edges = new THREE.EdgesGeometry(box.geometry);
        const line = new THREE.LineSegments(edges, new THREE.LineBasicMaterial({ color: 0x1565c0, transparent: true, opacity: 0.45 }));
        applyMeshPose(line, mesh);
        line.userData.expoOutline = true;
        helpers.add(line);
      }

      const applyTex = async (mat, fin, size) => {
        const url = fin?.texture_url;
        if (!url || !mat) return;
        const tex = await getTex(url);
        if (!tex) return;
        const avg = sampleTextureAverage(tex);
        const map = tex.clone();
        map.needsUpdate = true;
        map.wrapS = map.wrapT = THREE.RepeatWrapping;
        map.colorSpace = THREE.SRGBColorSpace;
        map.anisotropy = Math.min(16, renderer3d.capabilities.getMaxAnisotropy?.() || 1);
        // ~700mm tile → flute scale closer to catalog swatch, less moiré than 500mm.
        const tileMm = 700;
        map.repeat.set(
          Math.max(0.35, (size[0] || tileMm) / tileMm),
          Math.max(0.35, (size[1] || tileMm) / tileMm)
        );
        mat.map = map;
        // Pure white multiply so albedo RGB matches the texture/swatch (avg ~#301623 for Echoe 20297_ECO_6_2).
        mat.color.set('#ffffff');
        if (avg?.hex) {
          mat.userData.albedoAvg = avg.hex;
          // Dark laminates: keep matte, low environment wash so hue stays true.
          if (avg.lum < 0.28) {
            mat.roughness = Math.max(fin?.roughness ?? 0.68, 0.62);
            mat.envMapIntensity = 0.16;
            mat.bumpMap = map;
            mat.bumpScale = 0.55;
          } else {
            mat.roughness = fin?.roughness ?? 0.55;
            mat.envMapIntensity = 0.32;
            mat.bumpMap = map;
            mat.bumpScale = 0.35;
          }
        } else {
          mat.roughness = fin?.roughness ?? 0.55;
          mat.envMapIntensity = 0.28;
        }
        mat.metalness = fin?.metalness ?? 0;
        mat.needsUpdate = true;
      };

      if (Array.isArray(materials)) {
        const extIdx = ff?.expo_face_index;
        const nicheAllExterior = mesh.role === 'niche' && mesh.expo;
        for (let i = 0; i < materials.length; i++) {
          const fin = nicheAllExterior || (mesh.expo && i === extIdx)
            ? (ff?.exterior || mesh.finish)
            : (ff?.interior || ff?.exterior || mesh.finish);
          await applyTex(materials[i], fin, mesh.size);
        }
      } else {
        await applyTex(materials, mesh.finish, mesh.size);
      }
    }

    const canvas = renderer3d.domElement;
    canvas.style.cursor = 'grab';
    let dragging = false;
    let lastX = 0;
    let lastY = 0;
    const onPointerDown = (e) => {
      dragging = true; lastX = e.clientX; lastY = e.clientY;
      canvas.style.cursor = 'grabbing';
      canvas.setPointerCapture?.(e.pointerId);
    };
    const onPointerUp = (e) => {
      dragging = false; canvas.style.cursor = 'grab';
      canvas.releasePointerCapture?.(e.pointerId);
    };
    const onPointerMove = (e) => {
      if (!dragging) return;
      const dx = e.clientX - lastX; const dy = e.clientY - lastY;
      lastX = e.clientX; lastY = e.clientY;
      const offset = camera.position.clone().sub(target);
      const spherical = new THREE.Spherical().setFromVector3(offset);
      spherical.theta -= dx * 0.01;
      spherical.phi = Math.min(Math.PI * 0.48, Math.max(0.1, spherical.phi - dy * 0.01));
      offset.setFromSpherical(spherical);
      camera.position.copy(target).add(offset);
      camera.lookAt(target);
      renderer3d.render(scene, camera);
    };
    const onWheel = (e) => {
      e.preventDefault();
      const offset = camera.position.clone().sub(target);
      offset.multiplyScalar(e.deltaY > 0 ? 1.08 : 0.92);
      const minDist = Math.sqrt(b.width ** 2 + b.height ** 2 + b.depth ** 2) * 0.32;
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
      applyCameraPreset(document.getElementById('cam-preset')?.value || 'iso', camera, b, target);
      renderer3d.render(scene, camera);
    };

    /**
     * Client presentation sheet: single 4K PNG with Front | Isometric side-by-side.
     * Full unit framed in each panel; room/shadows kept; debug overlays hidden.
     */
    const exportHiRes = () => {
      const OUT_W = 3840;
      const OUT_H = 2160;
      const PANEL_W = Math.floor(OUT_W / 2);
      const PANEL_H = OUT_H;

      const prevPerson = person.visible;
      const prevGrid = grid.visible;
      const prevRoom = room.visible;
      const prevSizeHidden = document.getElementById('furn-3d-size')?.hidden;
      const prevCamPos = camera.position.clone();
      const prevTarget = target.clone();
      const prevAspect = camera.aspect;
      const prevFov = camera.fov;
      const prevPr = renderer3d.getPixelRatio();
      const lineVis = helpers.children.map((c) => ({ c, v: c.visible }));

      // Export chrome: hide person/grid/dims/EXPO outlines; keep room + front silhouettes.
      person.visible = false;
      grid.visible = false;
      if (document.getElementById('furn-3d-size')) document.getElementById('furn-3d-size').hidden = true;
      room.visible = !!document.getElementById('scale-room')?.checked;
      helpers.children.forEach((c) => {
        if (c.type === 'LineSegments') {
          c.visible = !!c.userData.keepInPresentation;
        }
      });

      // Slightly wider FOV for export framing of tall units.
      camera.fov = 30;
      const prevFog = scene.fog;
      scene.fog = null; // avoid distance fog clipping the sheet

      const renderPanel = (preset) => {
        renderer3d.setPixelRatio(1);
        renderer3d.setSize(PANEL_W, PANEL_H, false);
        camera.aspect = PANEL_W / PANEL_H;
        applyCameraPreset(preset, camera, b, target, 1.14);
        renderer3d.render(scene, camera);
        return renderer3d.domElement.toDataURL('image/png');
      };

      const frontUrl = renderPanel('front');
      const isoUrl = renderPanel('iso');

      // Restore viewport renderer immediately so UI stays responsive while compositing.
      scene.fog = prevFog;
      camera.fov = prevFov;
      camera.position.copy(prevCamPos);
      target.copy(prevTarget);
      camera.lookAt(target);
      renderer3d.setPixelRatio(prevPr);
      renderer3d.setSize(widthPx, heightPx, false);
      camera.aspect = prevAspect;
      camera.updateProjectionMatrix();
      person.visible = prevPerson;
      grid.visible = prevGrid;
      room.visible = prevRoom;
      lineVis.forEach(({ c, v }) => { c.visible = v; });
      if (document.getElementById('furn-3d-size')) {
        document.getElementById('furn-3d-size').hidden = !!prevSizeHidden;
      }
      syncHelpers();

      const sheet = document.createElement('canvas');
      sheet.width = OUT_W;
      sheet.height = OUT_H;
      const ctx = sheet.getContext('2d');
      const bg = '#dde5ee';
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, OUT_W, OUT_H);

      const drawHalf = (dataUrl, dx) => new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
          ctx.drawImage(img, dx, 0, PANEL_W, PANEL_H);
          resolve();
        };
        img.onerror = reject;
        img.src = dataUrl;
      });

      // Sync path: decode via Image then return data URL (caller may await if we return Promise).
      // Use temporary ImageBitmap-free approach with sync draw after load — return Promise.
      return (async () => {
        await drawHalf(frontUrl, 0);
        await drawHalf(isoUrl, PANEL_W);
        // Subtle center divider
        ctx.fillStyle = 'rgba(255,255,255,0.55)';
        ctx.fillRect(PANEL_W - 1, 0, 2, OUT_H);
        // Small captions
        ctx.font = '500 28px "Segoe UI", system-ui, sans-serif';
        ctx.fillStyle = 'rgba(40,48,56,0.55)';
        ctx.fillText('Front', 48, OUT_H - 40);
        ctx.fillText('Isometric', PANEL_W + 48, OUT_H - 40);
        const name = currentFurniture?.name || 'Furniture';
        const dims = `${Math.round(b.width)} × ${Math.round(b.height)} × ${Math.round(b.depth)} mm`;
        ctx.font = '600 22px "Segoe UI", system-ui, sans-serif';
        ctx.fillStyle = 'rgba(40,48,56,0.4)';
        ctx.fillText(`${name}  ·  ${dims}`, 48, 48);
        return sheet.toDataURL('image/png');
      })();
    };

    view3dState = {
      scene, camera, bounds: b, target, fit: fitNow, syncHelpers, exportHiRes,
      applyPreset: (p) => { applyCameraPreset(p, camera, b, target); renderer3d.render(scene, camera); },
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
    await renderPresets(f);
    syncLayoutForm();
    renderBayEditor();
    syncBayTargetSelect();
    await refreshRecommendations();

    exteriorSelect.setValue(f.exterior_finish_id || '');
    interiorSelect.setValue(f.interior_finish_id || '');
    const sm = document.getElementById('spec-material');
    if (sm) {
      sm.innerHTML = boardOptionsHtml(f.material_id || '', true, '— none (generic Board) —');
      sm.value = f.material_id ? String(f.material_id) : '';
    }
    document.getElementById('spec-notes').value = f.specification?.notes || '';
    renderExpoOptions(f);
    bindExpoSync();
    syncFillerForm(f);
    await renderComponents(selectedId);
    showTab(activeTab || 'size');
    if (activeTab === 'view2d') await draw2d(selectedId);
    if (activeTab === 'view3d') await draw3d(selectedId);
    document.getElementById('customize-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const syncFillerForm = (furniture) => {
    const f = furniture?.parameters?.fillers || {};
    const left = f.left || {};
    const right = f.right || {};
    const le = document.getElementById('filler-left-en');
    const lw = document.getElementById('filler-left-w');
    const re = document.getElementById('filler-right-en');
    const rw = document.getElementById('filler-right-w');
    if (le) le.checked = !!left.enabled;
    if (lw) lw.value = left.width_mm != null ? left.width_mm : 50;
    if (re) re.checked = !!right.enabled;
    if (rw) rw.value = right.width_mm != null ? right.width_mm : 50;
  };

  const collectFillers = () => ({
    left: {
      enabled: !!document.getElementById('filler-left-en')?.checked,
      width_mm: Number(document.getElementById('filler-left-w')?.value || 50),
    },
    right: {
      enabled: !!document.getElementById('filler-right-en')?.checked,
      width_mm: Number(document.getElementById('filler-right-w')?.value || 50),
    },
  });

  const collectParameters = () => {
    const parameters = {};
    document.querySelectorAll('.schema-param').forEach((el) => {
      const key = el.dataset.param;
      if (el.tagName === 'SELECT') {
        parameters[key] = el.value === '' ? null : (Number.isFinite(Number(el.value)) ? Number(el.value) : el.value);
      } else {
        parameters[key] = el.value === '' ? null : Number(el.value);
      }
    });
    parameters.fillers = collectFillers();
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
        expo: collectExpo(),
        material_id: document.getElementById('spec-material')?.value
          ? Number(document.getElementById('spec-material').value)
          : null,
        exterior_finish_id: exteriorSelect.getValue() ? Number(exteriorSelect.getValue()) : null,
        interior_finish_id: interiorSelect.getValue() ? Number(interiorSelect.getValue()) : null,
        specification: { notes: document.getElementById('spec-notes').value || '' },
      });
      document.getElementById('params-msg').textContent = 'All customizations saved.';
      document.getElementById('layout-msg').textContent = 'Internals regenerated.';
      document.getElementById('spec-msg').textContent = 'Finishes saved.';
      const expoMsg = document.getElementById('expo-msg');
      if (expoMsg) expoMsg.textContent = 'EXPO selection saved.';
      const fillerMsg = document.getElementById('filler-msg');
      if (fillerMsg) {
        const fl = collectFillers();
        const bits = [];
        if (fl.left.enabled) bits.push(`left ${fl.left.width_mm}mm`);
        if (fl.right.enabled) bits.push(`right ${fl.right.width_mm}mm`);
        fillerMsg.textContent = bits.length ? `Fillers saved (${bits.join(', ')}).` : 'No fillers enabled.';
      }
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
          back_thickness: tpl.parameters.back_thickness?.default ?? 18,
          back_material_id: tpl.parameters.back_material_id?.default ?? null,
          shutter_count: tpl.parameters.shutter_count?.default ?? 2,
          door_type: tpl.parameters.door_type?.default ?? 'HINGED',
          layout: clone(tpl.parameters.layout?.default || emptyLayout()),
        },
      };
      const matRaw = document.getElementById('furn-material')?.value;
      if (matRaw) payload.material_id = Number(matRaw);
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
      <td>${esc(f.code || '')}</td>
      <td>${esc(f.name || '')}</td>
      <td><span class="badge">${esc(f.category || f.type || '')}</span></td>
      <td>${esc(f.width_mm || '')}×${esc(f.height_mm || '')}×${esc(f.depth_mm || '')}</td>
      <td>${(f.parameters?.layout?.bays || []).length || 0} bays</td>
      <td>
        <button data-id="${esc(f.id)}" class="open-cust">Customize</button>
        <button data-id="${esc(f.id)}" data-code="${esc(f.code || f.name || '')}" class="del-furn danger" type="button">Delete</button>
      </td>
    </tr>`).join('');
    document.getElementById('furn-list').innerHTML = `<table><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>W×H×D</th><th>Layout</th><th></th></tr></thead>
      <tbody>${rows || '<tr><td colspan="6" class="muted">No furniture — pick a template above</td></tr>'}</tbody></table>`;
    document.querySelectorAll('.open-cust').forEach((btn) => { btn.onclick = () => openCustomize(btn.dataset.id); });
    document.querySelectorAll('.del-furn').forEach((btn) => {
      btn.onclick = async () => {
        const id = btn.dataset.id;
        const label = btn.dataset.code || `furniture #${id}`;
        if (!confirm(`Delete ${label}?\n\nThis removes it from the project. You can recreate it from a template if needed.`)) {
          return;
        }
        try {
          await api.del(`/api/v1/furniture/instances/${id}`);
          if (String(selectedId) === String(id)) {
            selectedId = '';
            localStorage.removeItem('fmos_furniture_id');
            document.getElementById('customize-wrap').style.display = 'none';
          }
          await refresh();
        } catch (err) {
          alert(err?.message || 'Failed to delete furniture');
        }
      };
    });
  };

  document.getElementById('add-bay').onclick = () => {
    if (!draftLayout) draftLayout = emptyLayout();
    draftLayout.bays.push({ id: `bay-${Date.now()}`, label: `Bay ${draftLayout.bays.length + 1}`, width_mm: null, sections: [defaultSection('SHELVES')] });
    renderBayEditor();
    refreshRecommendations();
  };
  document.getElementById('refresh-recommend')?.addEventListener('click', () => refreshRecommendations());
  document.getElementById('save-all-custom').onclick = saveAll;
  document.getElementById('close-custom').onclick = () => {
    document.getElementById('customize-wrap').style.display = 'none';
  };
  document.getElementById('view2d').onchange = () => {
    view2dState.zoom = 1; view2dState.panX = 0; view2dState.panY = 0;
    selectedId && draw2d(selectedId);
  };
  document.getElementById('reload-2d')?.addEventListener('click', () => selectedId && draw2d(selectedId));
  document.getElementById('reload-3d')?.addEventListener('click', () => selectedId && draw3d(selectedId));
  document.getElementById('fit-2d')?.addEventListener('click', () => {
    view2dState.zoom = 1; view2dState.panX = 0; view2dState.panY = 0; paint2d();
  });
  document.getElementById('zoom-2d-in')?.addEventListener('click', () => {
    view2dState.zoom = Math.min(4, view2dState.zoom * 1.2); paint2d();
  });
  document.getElementById('zoom-2d-out')?.addEventListener('click', () => {
    view2dState.zoom = Math.max(0.4, view2dState.zoom / 1.2); paint2d();
  });
  const c2 = document.getElementById('furn-2d');
  if (c2) {
    let pan = false; let lx = 0; let ly = 0;
    c2.addEventListener('wheel', (e) => {
      e.preventDefault();
      view2dState.zoom = Math.min(4, Math.max(0.4, view2dState.zoom * (e.deltaY > 0 ? 0.9 : 1.1)));
      paint2d();
    }, { passive: false });
    c2.addEventListener('pointerdown', (e) => { pan = true; lx = e.clientX; ly = e.clientY; c2.setPointerCapture?.(e.pointerId); });
    c2.addEventListener('pointerup', () => { pan = false; });
    c2.addEventListener('pointermove', (e) => {
      if (!pan) return;
      view2dState.panX += e.clientX - lx;
      view2dState.panY += e.clientY - ly;
      lx = e.clientX; ly = e.clientY;
      paint2d();
    });
  }
  document.getElementById('fit-3d').onclick = () => view3dState?.fit?.();
  document.getElementById('cam-preset')?.addEventListener('change', (e) => {
    view3dState?.applyPreset?.(e.target.value);
  });
  document.getElementById('viz-quality')?.addEventListener('change', () => selectedId && draw3d(selectedId));
  ['scale-person', 'scale-grid', 'scale-dims', 'scale-room', 'scale-shadows'].forEach((id) => {
    document.getElementById(id)?.addEventListener('change', () => view3dState?.syncHelpers?.());
  });
  document.getElementById('pres-mode')?.addEventListener('click', () => {
    const btn = document.getElementById('pres-mode');
    const on = btn.dataset.on !== '1';
    btn.dataset.on = on ? '1' : '0';
    btn.textContent = on ? 'Exit presentation' : 'Presentation';
    btn.classList.toggle('primary', on);
    const tools = document.getElementById('view3d-tools');
    if (tools) tools.style.opacity = on ? '0.35' : '1';
    if (on) {
      document.getElementById('scale-person').checked = false;
      document.getElementById('scale-grid').checked = false;
      document.getElementById('scale-dims').checked = false;
    }
    view3dState?.syncHelpers?.();
  });
  document.getElementById('fullscreen-3d')?.addEventListener('click', async () => {
    const wrap = document.getElementById('furn-3d-wrap');
    if (!wrap) return;
    if (!document.fullscreenElement) await wrap.requestFullscreen?.();
    else await document.exitFullscreen?.();
    setTimeout(() => selectedId && draw3d(selectedId), 120);
  });
  document.getElementById('export-design').onclick = async () => {
    if (!selectedId) return;
    const res = await api.post(`/api/v1/furniture/instances/${selectedId}/export/design`, { view: document.getElementById('view2d').value });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([res.data.content], { type: 'text/html' }));
    a.download = res.data.filename;
    a.click();
  };
  document.getElementById('capture-3d').onclick = async () => {
    if (!view3dState?.exportHiRes && !renderer3d) return;
    const btn = document.getElementById('capture-3d');
    const prev = btn?.textContent;
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Exporting 4K…';
    }
    try {
      const url = await (view3dState?.exportHiRes?.() || renderer3d.domElement.toDataURL('image/png'));
      const a = document.createElement('a');
      a.href = url;
      a.download = `furniture-${selectedId}-front-iso-4k.png`;
      a.click();
    } catch (err) {
      console.error(err);
      alert(err?.message || 'Export failed');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = prev || 'Export 4K sheet';
      }
    }
  };

  let activeCompositionId = null;
  let kcPlanData = null;

  const paintKcPlan = () => {
    const canvas = document.getElementById('kc-plan');
    if (!canvas || !kcPlanData) return;
    const d = kcPlanData;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#fbfcfd';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    const bw = Math.max(1, d.bounds?.width || 1);
    const bd = Math.max(1, d.bounds?.depth || 1);
    const minX = d.bounds?.min_x ?? 0;
    const minZ = d.bounds?.min_z ?? 0;
    const margin = 48;
    const scale = Math.min((canvas.width - margin * 2) / bw, (canvas.height - margin * 2) / bd);
    const ox = margin - minX * scale;
    const oy = margin - minZ * scale;
    const mapX = (x) => ox + x * scale;
    const mapY = (y) => oy + y * scale;
    (d.elements || []).forEach((el) => {
      if (el.type !== 'rect') return;
      ctx.fillStyle = el.role === 'corner' ? '#e8eaf6' : '#e3f2fd';
      ctx.strokeStyle = '#1c2430';
      ctx.lineWidth = 1.5;
      ctx.fillRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
      ctx.strokeRect(mapX(el.x), mapY(el.y), el.w * scale, el.h * scale);
      if (el.label && el.w * scale > 40) {
        ctx.fillStyle = '#334';
        ctx.font = '11px "Segoe UI", sans-serif';
        ctx.fillText(String(el.label).slice(0, 18), mapX(el.x) + 4, mapY(el.y) + 14);
      }
    });
    ctx.fillStyle = '#c62828';
    ctx.font = '12px "Segoe UI", sans-serif';
    (d.dimensions || []).forEach((dim) => {
      const mx = mapX((dim.from[0] + dim.to[0]) / 2);
      const my = mapY((dim.from[1] + dim.to[1]) / 2);
      ctx.beginPath();
      ctx.strokeStyle = '#c62828';
      ctx.moveTo(mapX(dim.from[0]), mapY(dim.from[1]));
      ctx.lineTo(mapX(dim.to[0]), mapY(dim.to[1]));
      ctx.stroke();
      ctx.fillText(String(dim.label || dim.value || ''), mx, my - 4);
    });
  };

  const setKcViewMode = (mode) => {
    const plan = document.getElementById('kc-plan');
    const wrap3d = document.getElementById('kc-3d-wrap');
    const fitBtn = document.getElementById('kc-fit-3d');
    if (plan) plan.style.display = mode === 'plan' ? '' : 'none';
    if (wrap3d) wrap3d.style.display = mode === '3d' ? '' : 'none';
    if (fitBtn) fitBtn.style.display = mode === '3d' ? '' : 'none';
  };

  const showKcPlan = async (id) => {
    activeCompositionId = id;
    const res = await api.get(`/api/v1/kitchen-compositions/${id}/2d`);
    kcPlanData = res.data;
    document.getElementById('kc-plan-wrap').style.display = '';
    document.getElementById('kc-plan-title').textContent = `${kcPlanData.title_block?.furniture || 'Composition'} · PLAN`;
    setKcViewMode('plan');
    paintKcPlan();
  };

  const showKc3d = async (id) => {
    activeCompositionId = id;
    if (!kcPlanData || String(kcPlanData.composition_id || '') !== String(id)) {
      const planRes = await api.get(`/api/v1/kitchen-compositions/${id}/2d`);
      kcPlanData = planRes.data;
    }
    document.getElementById('kc-plan-wrap').style.display = '';
    setKcViewMode('3d');
    document.getElementById('kc-plan-title').textContent = `${kcPlanData?.title_block?.furniture || 'Composition'} · 3D`;
    const model = await api.get(`/api/v1/kitchen-compositions/${id}/3d-model`);
    const b = model.data.bounds || {};
    await draw3d(null, {
      hostId: 'kc-3d',
      wrapId: 'kc-3d-wrap',
      model: model.data,
      titleText: kcPlanData?.title_block?.furniture || 'Kitchen L',
      subText: `${Math.round(b.width || 0)} × ${Math.round(b.height || 0)} × ${Math.round(b.depth || 0)} mm`,
      camPreset: 'iso',
    });
  };

  const refreshKitchenComps = async () => {
    const host = document.getElementById('kc-list');
    if (!host) return;
    try {
      const res = await api.get(`/api/v1/projects/${projectId}/kitchen-compositions`);
      const rows = (res.data || []).map((c) => `
        <div class="toolbar" style="justify-content:space-between;border:1px solid var(--line);border-radius:8px;padding:.45rem .65rem;margin:.35rem 0;background:#fafbfc">
          <span><strong>${c.name}</strong> <span class="muted">${c.shape} · ${(c.furniture_ids || []).length} modules</span></span>
          <span>
            <button type="button" class="secondary kc-view" data-id="${c.id}">Plan</button>
            <button type="button" class="secondary kc-view-3d" data-id="${c.id}">3D</button>
            <button type="button" class="danger kc-del" data-id="${c.id}">Delete composition</button>
          </span>
        </div>`).join('');
      host.innerHTML = rows || '<p class="muted">No kitchen compositions yet.</p>';
      host.querySelectorAll('.kc-view').forEach((btn) => {
        btn.onclick = () => showKcPlan(btn.dataset.id);
      });
      host.querySelectorAll('.kc-view-3d').forEach((btn) => {
        btn.onclick = () => showKc3d(btn.dataset.id);
      });
      host.querySelectorAll('.kc-del').forEach((btn) => {
        btn.onclick = async () => {
          if (!confirm('Delete this composition record?\n\nLinked furniture modules are kept (delete them separately if needed).')) return;
          await api.del(`/api/v1/kitchen-compositions/${btn.dataset.id}`);
          if (String(activeCompositionId) === String(btn.dataset.id)) {
            activeCompositionId = null;
            document.getElementById('kc-plan-wrap').style.display = 'none';
            if (view3dState?.dispose) { view3dState.dispose(); view3dState = null; }
          }
          await refreshKitchenComps();
        };
      });
    } catch (err) {
      host.innerHTML = `<p class="muted">${err?.message || 'Kitchen compositions unavailable (run migrations?).'}</p>`;
    }
  };

  document.getElementById('kc-create')?.addEventListener('click', async () => {
    const btn = document.getElementById('kc-create');
    try {
      if (btn) btn.disabled = true;
      const created = await api.post(`/api/v1/projects/${projectId}/kitchen-compositions`, {
        name: document.getElementById('kc-name')?.value || 'L Kitchen Base',
        run_a_length_mm: Number(document.getElementById('kc-run-a')?.value || 1800),
        run_b_length_mm: Number(document.getElementById('kc-run-b')?.value || 1200),
        depth_mm: Number(document.getElementById('kc-depth')?.value || 560),
        height_mm: Number(document.getElementById('kc-height')?.value || 720),
        corner_size_mm: Number(document.getElementById('kc-corner')?.value || 900),
        module_width_mm: Number(document.getElementById('kc-mod-w')?.value || 600),
        default_module_preset: document.getElementById('kc-preset')?.value || 'shelf',
        material_id: document.getElementById('furn-material')?.value || null,
      });
      await refresh();
      await refreshKitchenComps();
      await showKcPlan(created.data.id);
    } catch (err) {
      alert(err?.message || 'Failed to create L-shape kitchen');
    } finally {
      if (btn) btn.disabled = false;
    }
  });
  document.getElementById('kc-reload-plan')?.addEventListener('click', () => {
    if (!activeCompositionId) return;
    const wrap3d = document.getElementById('kc-3d-wrap');
    if (wrap3d && wrap3d.style.display !== 'none') showKc3d(activeCompositionId);
    else showKcPlan(activeCompositionId);
  });
  document.getElementById('kc-show-plan')?.addEventListener('click', () => {
    if (activeCompositionId) showKcPlan(activeCompositionId);
  });
  document.getElementById('kc-show-3d')?.addEventListener('click', () => {
    if (activeCompositionId) showKc3d(activeCompositionId);
  });
  document.getElementById('kc-fit-3d')?.addEventListener('click', () => view3dState?.fit?.());
  document.getElementById('kc-open-mfg')?.addEventListener('click', () => {
    const ids = kcPlanData?.furniture_ids || [];
    if (!ids.length) {
      alert('No modules in this composition.');
      return;
    }
    localStorage.setItem('fmos_mfg_furniture_ids', JSON.stringify(ids.map(Number)));
    localStorage.setItem('fmos_project_id', String(projectId));
    location.hash = 'manufacturing';
  });

  refresh();
  refreshKitchenComps();
}
