import { api } from './api.js';
import { state } from './state.js';
import { pages } from './pages.js';
import { esc } from './security.js';

function parseHash() {
  const raw = location.hash.replace(/^#/, '') || '';
  const [route, qs] = raw.split('?');
  const params = {};
  if (qs) {
    qs.split('&').forEach((pair) => {
      const [k, v] = pair.split('=');
      if (k) params[decodeURIComponent(k)] = decodeURIComponent(v || '');
    });
  }
  return { route: route || 'login', params };
}

function authShell(title, bodyHtml) {
  return `
    <div class="panel login-wrap" style="max-width:42rem">
      <div class="login-brand">
        <img class="login-logo" src="/assets/brand/fmos-logo.png" alt="FMOS — Furniture Manufacturing Operating System" />
      </div>
      <h1>${title}</h1>
      ${bodyHtml}
    </div>`;
}

export function renderLogin() {
  const main = document.getElementById('main');
  document.getElementById('nav').innerHTML = '';
  document.getElementById('user-box').innerHTML = '';
  const { route, params } = parseHash();
  state.route = route;

  if (route === 'register') return renderRegister(main);
  if (route === 'verify-email') return renderVerifyEmail(main, params);
  if (route === 'forgot-password') return renderForgotPassword(main);
  if (route === 'reset-password') return renderResetPassword(main, params);
  if (route === 'verify-pending') return renderVerifyPending(main, params);

  main.innerHTML = authShell('Sign in', `
      <p class="login-sub muted">Design-to-Manufacturing Operating System</p>
      <form id="login-form">
        <label>Email</label>
        <input name="email" type="email" autocomplete="username" required />
        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required />
        <button type="submit">Sign in</button>
        <p id="login-error" class="error"></p>
        <p class="muted" style="margin-top:1rem">
          <a href="#forgot-password">Forgot password?</a>
          ·
          <a href="#register">Create account</a>
        </p>
        <p class="muted" style="margin-top:0.75rem;font-size:0.9rem">Workspace login (after seed): <code>owner@demo.fmos</code> — platform accounts cannot open Organizations / Catalog.</p>
        <div id="login-resend" style="display:none;margin-top:0.75rem">
          <button type="button" class="secondary" id="resend-from-login">Resend verification email</button>
        </div>
      </form>
  `);
  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const errEl = document.getElementById('login-error');
    const resendBox = document.getElementById('login-resend');
    errEl.textContent = '';
    resendBox.style.display = 'none';
    try {
      const res = await api.post('/api/v1/auth/login', {
        email: fd.get('email'),
        password: fd.get('password'),
      });
      api.setToken(res.data.token);
      api.setCsrf(res.data.csrf);
      state.user = res.data.user;
      location.hash = '#dashboard';
      renderShell(state);
    } catch (err) {
      errEl.textContent = err.message;
      if (err.code === 'EMAIL_NOT_VERIFIED') {
        resendBox.style.display = 'block';
        document.getElementById('resend-from-login').onclick = async () => {
          try {
            const r = await api.post('/api/v1/auth/resend-verification', { email: fd.get('email') });
            errEl.textContent = r.data.message;
          } catch (e2) {
            errEl.textContent = e2.message;
          }
        };
      }
    }
  });
}

function renderVerifyPending(main, params) {
  main.innerHTML = authShell('Check your email', `
    <p class="muted">We sent a verification link${params.email ? ` to <strong>${params.email}</strong>` : ''}. Verify your email before signing in.</p>
    <form id="resend-form">
      <label>Email</label>
      <input name="email" type="email" required value="${params.email || ''}" />
      <button type="submit">Resend verification email</button>
      <p id="pending-msg" class="muted"></p>
    </form>
    <p><a href="#login">Back to sign in</a></p>
  `);
  document.getElementById('resend-form').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const r = await api.post('/api/v1/auth/resend-verification', { email: fd.get('email') });
      document.getElementById('pending-msg').textContent = r.data.message;
    } catch (err) {
      document.getElementById('pending-msg').textContent = err.message;
    }
  };
}

async function renderVerifyEmail(main, params) {
  main.innerHTML = authShell('Verifying email…', `<p class="muted" id="verify-status">Please wait.</p><p><a href="#login">Sign in</a></p>`);
  const status = document.getElementById('verify-status');
  if (!params.token) {
    status.textContent = 'Missing verification token.';
    return;
  }
  try {
    const r = await api.post('/api/v1/auth/verify-email', { token: params.token });
    status.textContent = r.data.message || 'Email verified. You can sign in.';
  } catch (err) {
    status.textContent = '';
    status.appendChild(document.createTextNode(err.message || 'Verification failed.'));
    status.appendChild(document.createElement('br'));
    const a = document.createElement('a');
    a.href = '#verify-pending';
    a.textContent = 'Resend verification email';
    status.appendChild(a);
  }
}

function renderForgotPassword(main) {
  main.innerHTML = authShell('Forgot password', `
    <form id="forgot-form">
      <label>Email</label>
      <input name="email" type="email" required />
      <button type="submit">Send reset link</button>
      <p id="forgot-msg" class="muted"></p>
    </form>
    <p><a href="#login">Back to sign in</a></p>
  `);
  document.getElementById('forgot-form').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const r = await api.post('/api/v1/auth/forgot-password', { email: fd.get('email') });
      document.getElementById('forgot-msg').textContent = r.data.message;
    } catch (err) {
      document.getElementById('forgot-msg').textContent = err.message;
    }
  };
}

function renderResetPassword(main, params) {
  main.innerHTML = authShell('Reset password', `
    <form id="reset-form">
      <input type="hidden" name="token" value="${params.token || ''}" />
      <label>New password *</label>
      <input name="password" type="password" required minlength="8" />
      <label>Confirm password *</label>
      <input name="password_confirm" type="password" required minlength="8" />
      <button type="submit">Update password</button>
      <p id="reset-msg" class="error"></p>
    </form>
    <p><a href="#login">Back to sign in</a></p>
  `);
  document.getElementById('reset-form').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const r = await api.post('/api/v1/auth/reset-password', {
        token: fd.get('token'),
        password: fd.get('password'),
        password_confirm: fd.get('password_confirm'),
      });
      document.getElementById('reset-msg').className = 'muted';
      document.getElementById('reset-msg').textContent = r.data.message;
    } catch (err) {
      document.getElementById('reset-msg').className = 'error';
      document.getElementById('reset-msg').textContent = err.message;
    }
  };
}

function renderRegister(main) {
  const factoryCaps = ['Modular kitchen', 'Wardrobe', 'Bedroom furniture', 'Living room furniture', 'Office furniture', 'Custom furniture', 'CNC capability', 'Panel processing', 'Edge banding', 'Lamination'];
  const firmCaps = ['Residential', 'Commercial', 'Hospitality', 'Retail', 'Office', 'Kitchen', 'Wardrobe', 'Turnkey projects'];
  const constitutions = [
    ['PROPRIETORSHIP', 'Proprietorship'],
    ['PARTNERSHIP', 'Partnership'],
    ['LLP', 'LLP'],
    ['PRIVATE_LIMITED', 'Private Limited'],
    ['PUBLIC_LIMITED', 'Public Limited'],
    ['OPC', 'One Person Company'],
    ['HUF', 'HUF'],
    ['SOCIETY', 'Society'],
    ['TRUST', 'Trust'],
    ['OTHER', 'Other'],
  ];

  main.innerHTML = authShell('Create your FMOS account', `
    <p class="muted" id="reg-step-label">Step 1 of 5 — Account type</p>
    <div id="reg-progress" class="muted" style="margin-bottom:0.75rem"></div>
    <form id="reg-form">
      <section data-step="1">
        <p>I am registering as:</p>
        <label><input type="radio" name="registration_type" value="INDEPENDENT_DESIGNER" required /> Independent Interior Designer</label><br/>
        <label><input type="radio" name="registration_type" value="FACTORY_OWNER" /> Modular Furniture Factory Owner</label><br/>
        <label><input type="radio" name="registration_type" value="DESIGN_FIRM" /> Interior Design Firm</label>
      </section>
      <section data-step="2" hidden>
        <label>First name *</label><input name="first_name" required />
        <label>Last name *</label><input name="last_name" required />
        <label>Display name</label><input name="display_name" />
        <label>Email *</label><input name="email" type="email" required />
        <label>Mobile country code</label><input name="mobile_country_code" value="+91" />
        <label>Mobile</label><input name="mobile" />
        <label>Designation</label><input name="designation" />
        <label>Professional / business name</label><input name="professional_name" />
        <label>Website / portfolio</label><input name="website_personal" />
      </section>
      <section data-step="3" hidden id="org-step">
        <label>Legal business name *</label><input name="legal_name" />
        <label>Trade / business name</label><input name="trade_name" />
        <label>Constitution *</label>
        <select name="constitution">${constitutions.map(([v, l]) => `<option value="${v}">${l}</option>`).join('')}</select>
        <label>GST registered? *</label>
        <select name="gst_registered">
          <option value="YES">Yes</option>
          <option value="NO" selected>No</option>
          <option value="NOT_APPLICABLE">Not Applicable</option>
        </select>
        <label>GSTIN</label><input name="gstin" maxlength="15" />
        <label>PAN</label><input name="pan" maxlength="10" />
        <label>Business email</label><input name="business_email" type="email" />
        <label>Business phone</label><input name="business_phone" />
        <label>Website</label><input name="website" />
        <label>Year established</label><input name="year_established" type="number" min="1900" max="2100" />
        <label>Manufacturing locations</label><input name="manufacturing_locations_count" type="number" min="0" />
        <hr/>
        <p><strong>Principal place of business</strong></p>
        <label>Address line 1 *</label><input name="line1" />
        <label>Address line 2</label><input name="line2" />
        <label>City / Town</label><input name="city" />
        <label>District</label><input name="district" />
        <label>State *</label><input name="state" />
        <label>PIN code *</label><input name="pin_code" maxlength="6" />
        <div id="caps-box"></div>
      </section>
      <section data-step="4" hidden>
        <label>Password *</label><input name="password" type="password" required minlength="8" />
        <label>Confirm password *</label><input name="password_confirm" type="password" required minlength="8" />
      </section>
      <section data-step="5" hidden>
        <label><input type="checkbox" name="terms_accepted" required /> I agree to the Terms of Service and acknowledge the Privacy Notice. *</label><br/>
        <label><input type="checkbox" name="marketing_email_consent" /> I would like to receive product updates and marketing communications.</label>
      </section>
      <div class="toolbar" style="margin-top:1rem;gap:0.5rem;display:flex">
        <button type="button" class="secondary" id="reg-back" hidden>Back</button>
        <button type="button" id="reg-next">Continue</button>
        <button type="submit" id="reg-submit" hidden>Create account</button>
      </div>
      <p id="reg-error" class="error"></p>
      <p class="muted"><a href="#login">Already have an account? Sign in</a></p>
    </form>
  `);

  let step = 1;
  const form = document.getElementById('reg-form');
  const typeOf = () => form.registration_type.value;
  const isOrg = () => typeOf() === 'FACTORY_OWNER' || typeOf() === 'DESIGN_FIRM';
  const maxStep = () => (isOrg() ? 5 : 5);

  const paintCaps = () => {
    const box = document.getElementById('caps-box');
    const list = typeOf() === 'FACTORY_OWNER' ? factoryCaps : firmCaps;
    box.innerHTML = `<p><strong>Business profile (optional)</strong></p>` + list.map((c) =>
      `<label style="display:inline-block;margin-right:0.75rem"><input type="checkbox" name="cap" value="${c}" /> ${c}</label>`
    ).join('');
  };

  const showStep = () => {
    const org = isOrg();
    // Independent skips org step 3 visually by mapping steps: 1 type, 2 personal, 3 password, 4 terms
    // Org: 1 type, 2 personal, 3 org, 4 password, 5 terms
    form.querySelectorAll('[data-step]').forEach((sec) => {
      const n = Number(sec.dataset.step);
      let visible = false;
      if (!org) {
        const map = { 1: 1, 2: 2, 3: 4, 4: 5 };
        visible = map[step] === n || (step === 3 && n === 4) || (step === 4 && n === 5) || (step === 1 && n === 1) || (step === 2 && n === 2);
        if (step === 3) visible = n === 4;
        if (step === 4) visible = n === 5;
        if (step === 1) visible = n === 1;
        if (step === 2) visible = n === 2;
      } else {
        visible = n === step;
      }
      sec.hidden = !visible;
    });
    document.getElementById('org-step').hidden = !org || step !== 3;
    if (org && step === 3) paintCaps();
    document.getElementById('reg-back').hidden = step <= 1;
    const last = org ? 5 : 4;
    document.getElementById('reg-next').hidden = step >= last;
    document.getElementById('reg-submit').hidden = step < last;
    document.getElementById('reg-step-label').textContent = `Step ${step} of ${last}`;
  };

  document.getElementById('reg-next').onclick = () => {
    const last = isOrg() ? 5 : 4;
    if (step < last) step += 1;
    showStep();
  };
  document.getElementById('reg-back').onclick = () => {
    if (step > 1) step -= 1;
    showStep();
  };
  form.querySelectorAll('input[name=registration_type]').forEach((el) => el.addEventListener('change', () => showStep()));
  showStep();

  form.onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const type = fd.get('registration_type');
    const caps = [...form.querySelectorAll('input[name=cap]:checked')].map((c) => c.value);
    const payload = {
      registration_type: type,
      first_name: fd.get('first_name'),
      last_name: fd.get('last_name'),
      display_name: fd.get('display_name') || undefined,
      email: fd.get('email'),
      mobile_country_code: fd.get('mobile_country_code') || '+91',
      mobile: fd.get('mobile') || undefined,
      designation: fd.get('designation') || undefined,
      password: fd.get('password'),
      password_confirm: fd.get('password_confirm'),
      terms_accepted: !!fd.get('terms_accepted'),
      privacy_acknowledged: !!fd.get('terms_accepted'),
      marketing_email_consent: !!fd.get('marketing_email_consent'),
      terms_version: '1.0',
      privacy_version: '1.0',
    };
    if (type === 'INDEPENDENT_DESIGNER') {
      payload.organization = {
        legal_name: fd.get('professional_name') || payload.display_name || `${payload.first_name} ${payload.last_name}`,
        trade_name: fd.get('professional_name') || undefined,
        website: fd.get('website_personal') || undefined,
        gst_registered: 'NOT_APPLICABLE',
      };
    } else {
      payload.organization = {
        legal_name: fd.get('legal_name'),
        trade_name: fd.get('trade_name') || undefined,
        constitution: fd.get('constitution'),
        gst_registered: fd.get('gst_registered'),
        gstin: fd.get('gstin') || undefined,
        pan: fd.get('pan') || undefined,
        business_email: fd.get('business_email') || undefined,
        business_phone: fd.get('business_phone') || undefined,
        website: fd.get('website') || undefined,
        year_established: fd.get('year_established') || undefined,
        manufacturing_locations_count: fd.get('manufacturing_locations_count') || undefined,
        profile: { capabilities: caps },
      };
      payload.address = {
        line1: fd.get('line1'),
        line2: fd.get('line2') || undefined,
        city: fd.get('city') || undefined,
        district: fd.get('district') || undefined,
        state: fd.get('state'),
        pin_code: fd.get('pin_code'),
        country: 'IN',
      };
      if (fd.get('mobile')) payload.mobile = fd.get('mobile');
    }
    const errEl = document.getElementById('reg-error');
    errEl.textContent = '';
    try {
      const r = await api.post('/api/v1/auth/register', payload);
      if (r.data.csrf) api.setCsrf(r.data.csrf);
      location.hash = `#verify-pending?email=${encodeURIComponent(payload.email)}`;
      renderLogin();
    } catch (err) {
      errEl.textContent = err.message;
    }
  };
}

function renderNoTenant(main, user) {
  const email = user?.email || 'this account';
  main.innerHTML = `<div class="panel">
    <h2>Tenant workspace required</h2>
    <p><code>${esc(email)}</code> is a platform account (no factory/tenant). Calls to Organizations, Clients, and Catalog return <strong>403 Tenant context required</strong>.</p>
    <p>Log out and sign in as a tenant owner, for example the seeded demo user:</p>
    <ul>
      <li><code>owner@demo.fmos</code> / <code>Password123!</code></li>
    </ul>
    <p class="muted">Or create a company account via Sign up. Platform users (<code>platform@fmos.local</code>, <code>support@fmos.local</code>) are for admin tooling only.</p>
  </div>`;
}

export function renderShell(appState) {
  const nav = document.getElementById('nav');
  const hasTenant = !!appState.user?.tenant_id;
  const links = [
    ['dashboard', 'Dashboard'],
    ['organizations', 'Organizations'],
    ['clients', 'Clients'],
    ['projects', 'Projects'],
    ['furniture', 'Furniture'],
    ['designer', 'Floor Designer'],
    ['catalog', 'Catalog'],
    ['commercial', 'BOM/BOQ/Price'],
    ['manufacturing', 'Manufacturing'],
    ['nesting', 'Nesting/Labels'],
  ];
  nav.innerHTML = links.map(([id, label]) =>
    `<a href="#${id}" class="${appState.route === id ? 'active' : ''}">${esc(label)}</a>`
  ).join('');
  document.getElementById('user-box').innerHTML = `
    <span class="muted">${esc(appState.user?.name || '')} · ${esc(appState.user?.email || '')}${hasTenant ? '' : ' · platform'}</span>
    <button class="secondary" id="logout-btn" style="margin-left:0.5rem">Logout</button>`;
  document.getElementById('logout-btn').onclick = async () => {
    try { await api.post('/api/v1/auth/logout', {}); } catch {}
    api.setToken(null);
    api.setCsrf(null);
    state.user = null;
    location.hash = '#login';
    renderLogin();
  };
  const main = document.getElementById('main');
  if (!hasTenant) {
    renderNoTenant(main, appState.user);
    return;
  }
  const page = pages[appState.route] || pages.dashboard;
  page(main, appState);
}
