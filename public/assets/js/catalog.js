import { api } from './api.js';

export async function mountCatalog(main) {
  main.innerHTML = `<div class="panel"><h2>Catalog</h2>
    <div class="toolbar">
      <button id="tab-products" class="secondary">Products</button>
      <button id="tab-laminates">Laminates</button>
      <button id="seed-cat" class="secondary">Seed Product Defaults</button>
      <button id="refresh-cat" class="secondary">Refresh</button>
    </div>
    <div id="cat-list"></div></div>`;

  let mode = 'laminates';

  const renderProducts = async () => {
    const res = await api.get('/api/v1/catalog/products');
    document.getElementById('cat-list').innerHTML = `<table><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Publish</th><th>Cost</th><th>Sell</th></tr></thead>
      <tbody>${res.data.map(p => `<tr><td>${p.sku}</td><td>${p.name}</td><td>${p.category}</td><td>${p.publish_status}</td><td>${p.cost}</td><td>${p.selling_price}</td></tr>`).join('')}</tbody></table>`;
  };

  const renderLaminates = async () => {
    const res = await api.get('/api/v1/materials?category=LAMINATE');
    const cards = (res.data || []).map((m) => {
      const thumb = m.assets?.find((a) => a.is_primary == 1)?.public_url
        || m.assets?.[0]?.public_url
        || '';
      return `<div class="lam-card">
        ${thumb ? `<img src="${thumb}" alt="${m.sku}" loading="lazy" />` : '<div class="lam-missing">No texture</div>'}
        <div class="lam-meta">
          <strong>${m.sku}</strong>
          <span class="badge">${m.series_code || ''} · ${m.series_name || ''}</span>
        </div>
      </div>`;
    }).join('');
    document.getElementById('cat-list').innerHTML = `
      <p class="muted">${res.data?.length || 0} laminates (import via <code>php bin/import_laminates.php</code>)</p>
      <div class="lam-grid">${cards || '<p class="muted">No laminates imported yet.</p>'}</div>`;
  };

  const refresh = async () => {
    if (mode === 'laminates') await renderLaminates();
    else await renderProducts();
  };

  document.getElementById('tab-products').onclick = () => { mode = 'products'; refresh(); };
  document.getElementById('tab-laminates').onclick = () => { mode = 'laminates'; refresh(); };
  document.getElementById('seed-cat').onclick = async () => { await api.post('/api/v1/catalog/seed', {}); mode = 'products'; refresh(); };
  document.getElementById('refresh-cat').onclick = refresh;
  refresh();
}
