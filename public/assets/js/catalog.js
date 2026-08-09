import { api } from './api.js';

export async function mountCatalog(main) {
  main.innerHTML = `<div class="panel"><h2>Catalog</h2>
    <div class="toolbar"><button id="seed-cat">Seed Defaults</button><button id="refresh-cat" class="secondary">Refresh</button></div>
    <div id="cat-list"></div></div>`;
  const refresh = async () => {
    const res = await api.get('/api/v1/catalog/products');
    document.getElementById('cat-list').innerHTML = `<table><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Publish</th><th>Cost</th><th>Sell</th></tr></thead>
      <tbody>${res.data.map(p => `<tr><td>${p.sku}</td><td>${p.name}</td><td>${p.category}</td><td>${p.publish_status}</td><td>${p.cost}</td><td>${p.selling_price}</td></tr>`).join('')}</tbody></table>`;
  };
  document.getElementById('seed-cat').onclick = async () => { await api.post('/api/v1/catalog/seed', {}); refresh(); };
  document.getElementById('refresh-cat').onclick = refresh;
  refresh();
}
