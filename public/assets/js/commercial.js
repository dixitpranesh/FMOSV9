import { api } from './api.js';

export async function mountCommercial(main) {
  const projectId = localStorage.getItem('fmos_project_id');
  const furnitureId = localStorage.getItem('fmos_furniture_id');
  main.innerHTML = `<div class="panel"><h2>BOM / BOQ / Pricing / Quote</h2>
    <div class="toolbar">
      <button id="gen-commercial">Generate Commercial</button>
      <button id="make-quote" class="secondary">Create & Approve Quote</button>
    </div>
    <pre id="commercial-out"></pre></div>`;
  let last = null;
  document.getElementById('gen-commercial').onclick = async () => {
    last = await api.post('/api/v1/commercial/generate', {
      project_id: Number(projectId),
      furniture_id: Number(furnitureId),
    });
    localStorage.setItem('fmos_pricing_id', last.data.pricing_calculation_id);
    document.getElementById('commercial-out').textContent = JSON.stringify(last.data, null, 2);
  };
  document.getElementById('make-quote').onclick = async () => {
    const project = await api.get(`/api/v1/projects/${projectId}`);
    const pricingId = localStorage.getItem('fmos_pricing_id');
    const q = await api.post('/api/v1/quotations', {
      project_id: Number(projectId),
      client_id: project.data.client_id,
      pricing_calculation_id: Number(pricingId),
    });
    const approved = await api.post(`/api/v1/quotations/${q.data.id}/status`, { status: 'APPROVED' });
    const accepted = await api.post(`/api/v1/quotations/${q.data.id}/status`, { status: 'ACCEPTED' });
    document.getElementById('commercial-out').textContent = JSON.stringify({ quote: accepted.data }, null, 2);
  };
}
