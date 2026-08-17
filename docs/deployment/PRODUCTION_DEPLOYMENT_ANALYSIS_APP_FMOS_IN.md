# FMOS Production Deployment Analysis & Runbook

**Date:** 2026-08-17 (updated after cPanel File Manager screenshot)  
**Mode:** Read-only repository analysis — no automatic URL replacements performed  
**Hosting target:** MilesWeb cPanel  

---

## Known / verified production parameters

| Item | Value | Source |
|------|-------|--------|
| Production URL | `https://app.fmos.in` | Product owner |
| Production domain | `app.fmos.in` | Product owner |
| **cPanel username / home** | `/home/fuiyrsce/` | **Verified from File Manager screenshot** |
| **Domain folder (current)** | `/home/fuiyrsce/app.fmos.in/` | **Verified from File Manager screenshot** |
| Current contents of domain folder | Only `.well-known`, `cgi-bin`, `.htaccess`, `.user.ini`, `php.ini` (empty of app code) | Screenshot |
| Sibling sites on same account | `fmos.in`, `interiorsmartcut.in`, `api.interiorsmartcut.in`, `react.interiorsmartcut.in`, `public_html`, etc. | Screenshot |

### Correction from earlier assumption

| Earlier assumption | Actual (screenshot) |
|--------------------|---------------------|
| cPanel home = `/home/app.fmos.in/` | **Wrong** — home is `/home/fuiyrsce/` |
| `app.fmos.in` is the account home | **Wrong** — `app.fmos.in` is a **subdirectory** under `/home/fuiyrsce/` (typical addon/subdomain docroot folder) |

---

## Screenshot conclusion — do we change the folder structure?

**Yes — deploy into a structure that matches the FMOS app (docroot = `public/`). Do not dump the whole Git repo into `/home/fuiyrsce/app.fmos.in/` as it stands.**

### Why

1. `/home/fuiyrsce/app.fmos.in/` is almost certainly the **current Document Root** for `https://app.fmos.in` (empty boilerplate only).
2. FMOS `public/index.php` expects **parent** directories: `.env`, `vendor/`, `src/`, `storage/`, `templates/`.
3. Putting `.env` / `src` / `storage` **inside** the current web root without moving Document Root to `…/public` is unsafe on a shared home with other sites.

### Recommended structure (keep cPanel layout, fix app layout)

**Preferred — change Document Root in cPanel → Domains → `app.fmos.in`:**

```text
/home/fuiyrsce/                             ← cPanel home (VERIFIED)
│
├── apps/
│   └── fmos/                               ← full FMOS clone (OUTSIDE any domain web root)
│       ├── .env
│       ├── vendor/
│       ├── src/
│       ├── storage/
│       ├── templates/
│       ├── database/
│       ├── bin/
│       └── public/                         ← set Document Root HERE
│           ├── index.php
│           ├── app.html
│           ├── assets/
│           ├── media/
│           └── .htaccess                   ← Apache front controller (add at deploy)
│
├── app.fmos.in/                            ← OLD empty domain folder (can leave or clear)
│   ├── .well-known/                        ← keep if SSL validation needs it, or rely on new docroot
│   └── …
│
├── fmos.in/                                ← other sites (unchanged)
├── interiorsmartcut.in/                    ← other sites (unchanged)
├── public_html/                            ← other sites (unchanged)
└── …
```

**In cPanel:** Domains → `app.fmos.in` → Document Root =  
`/home/fuiyrsce/apps/fmos/public`

**Alternative if Document Root cannot be changed** (still acceptable):

```text
/home/fuiyrsce/app.fmos.in/                 ← keep this as parent of the app
├── .env
├── vendor/
├── src/
├── storage/
├── templates/
└── public/                                 ← change Document Root to THIS subdirectory
    ├── index.php
    ├── app.html
    ├── assets/
    └── .htaccess
```

cPanel Document Root = `/home/fuiyrsce/app.fmos.in/public`

### What not to do

```text
❌ /home/fuiyrsce/app.fmos.in/index.php     (flatten public into current root without parent dirs)
❌ /home/fuiyrsce/app.fmos.in/  ← entire repo including .env visible as web root
❌ Copy only public/* into app.fmos.in without the parent app tree
```

---

## Known production parameters (authoritative)

| Item | Known value |
|------|-------------|
| Production URL | `https://app.fmos.in` |
| Production domain | `app.fmos.in` |
| cPanel home path | `/home/fuiyrsce/` *(verified)* |
| Domain directory | `/home/fuiyrsce/app.fmos.in/` *(verified; likely current docroot)* |

These values are **fixed** for this guide. Placeholders are used only where information is genuinely unknown.

---

## Values classification

| Category | Examples |
|----------|----------|
| **Known / verified** | `https://app.fmos.in`, `app.fmos.in`, `/home/fuiyrsce/`, `/home/fuiyrsce/app.fmos.in/` |
| **Discovered from repository** | Docroot must be app `public/`; APIs at `/api/v1`; `APP_URL` drives email links; relative frontend fetch; PHP ≥8.1; Composer autoload; no `interiorsmartcut.in` in **this** FMOSV9 codebase |
| **Must configure in cPanel** | Document root → `…/public`, SSL, HTTPS redirect, MySQL DB/user, PHP version ≥8.1 |
| **Must provide (developer/ops)** | `<MYSQL_DATABASE>`, `<MYSQL_USER>`, `<MYSQL_PASSWORD>`, `<APP_KEY>`, SMTP credentials, deploy method |
| **Shared-host note** | Same account hosts `interiorsmartcut.in` and others — isolate FMOS under `apps/fmos` + narrow docroot; do not share `.env` across sites |

---

# 1. Document root — verification status

## What the repository requires

Application entry is **`public/index.php`**, which loads:

```text
dirname(__DIR__) . '/vendor/autoload.php'   → repo root /vendor
dirname(__DIR__) . '/.env'                  → repo root /.env
dirname(__DIR__) . '/src/...'               → repo root /src
dirname(__DIR__) . '/public/app.html'       → SPA shell
```

Local README starts PHP as:

```text
php -S … -t public public/router.php
```

**Conclusion from code:** The web-accessible document root **must be the application’s `public/` directory**, not the repository root. Putting the entire repo under the web root would expose `.env`, `src/`, `storage/`, `vendor/`, migrations, and tests unless additional deny rules are perfect — **unsafe**.

## Live server (screenshot)

| Path | Status |
|------|--------|
| `/home/fuiyrsce/` | **Verified** cPanel home |
| `/home/fuiyrsce/app.fmos.in/` | **Verified** domain folder; currently boilerplate only → almost certainly **current** Document Root |
| Target Document Root | **Must become** `/home/fuiyrsce/apps/fmos/public` **or** `/home/fuiyrsce/app.fmos.in/public` |

Confirm once in: cPanel → **Domains** → `app.fmos.in` → Document Root.

---

# 2. Recommended final directory structure

Do **not** place the full Git repository inside an unchanged web root.

## Recommended (safe) layout — matches screenshot account

```text
/home/fuiyrsce/                             ← cPanel home (VERIFIED)
│
├── apps/
│   └── fmos/                               ← full application (OUTSIDE web roots)
│       ├── .env
│       ├── composer.json
│       ├── vendor/
│       ├── src/
│       ├── templates/
│       ├── storage/
│       ├── database/
│       ├── bin/
│       ├── config/
│       └── public/                         ← Document Root for app.fmos.in
│           ├── index.php
│           ├── router.php
│           ├── app.html
│           ├── assets/
│           ├── favicon.*
│           ├── media/
│           └── .htaccess
│
├── app.fmos.in/                            ← legacy/empty domain dir (optional keep .well-known)
├── fmos.in/
├── interiorsmartcut.in/
├── public_html/
└── …
```

### Preferred cPanel document-root options

**Option A — Symlink / custom docroot (recommended):**

```text
Document Root for app.fmos.in =
  /home/fuiyrsce/apps/fmos/public
```

**Option B — App lives under current domain folder:**

```text
/home/fuiyrsce/app.fmos.in/          ← application root (not web root)
/home/fuiyrsce/app.fmos.in/public    ← Document Root
```

### If the entire repository is forced into the current web root

```text
/home/fuiyrsce/app.fmos.in/   ← entire repo with Document Root unchanged
```

**Security implications:** `.env`, `storage/mail`, source may be web-reachable; worse on a multi-site home (`interiorsmartcut.in` siblings).

**Mitigation if unavoidable:** Still set Document Root to `app.fmos.in/public` only.

---

# 3. Domain configuration analysis

| # | Question | Finding |
|---|----------|---------|
| 1 | Where should `app.fmos.in` point? | DNS A/AAAA → MilesWeb server IP (**confirm with MilesWeb**). HTTP(S) vhost Document Root → `<APPLICATION_ROOT>/public` |
| 2 | Primary / addon / subdomain / separate account? | Screenshot shows **shared cPanel user `fuiyrsce`** with many domain folders (`app.fmos.in`, `fmos.in`, `interiorsmartcut.in`, …). `app.fmos.in` is a domain folder under that home — typically addon/subdomain. Confirm in Domains UI |
| 3 | Exact document root? | **Must confirm in cPanel**; code requires `…/public` |
| 4 | DNS → MilesWeb? | **Requires MilesWeb confirmation** (not in repo) |
| 5 | SSL installed? | **Requires confirmation**; app expects HTTPS in production (`cookie_secure` when `APP_ENV=production` or HTTPS on) |
| 6 | HTTPS redirects? | **Requires cPanel/Force HTTPS**; not implemented in PHP |
| 7 | `www.app.fmos.in`? | Not referenced in code. Prefer **non-www only** or redirect www → apex; set `APP_URL` to the canonical host users actually use |
| 8 | Absolute URLs in app? | Email verify/reset links use `APP_URL`. Frontend uses **relative** `/api/v1/...`. Three.js loaded from `https://unpkg.com/...` |
| 9 | Hard-coded API URLs? | **No** production API host hard-coded in SPA; paths like `/api/v1/auth/login` |
| 10 | CORS vs domain? | Origin allowlist = `APP_URL` + optional `CORS_ALLOWED_ORIGINS`. Same-origin SPA → set `APP_URL=https://app.fmos.in` |
| 11 | Session vs domain? | Cookie name `SESSION_NAME` (default `fmos_session`); Secure flag when HTTPS or `APP_ENV=production`; SameSite=Lax; **no domain attribute set** (host-only cookie for `app.fmos.in`) |
| 12 | Email verification links? | `APP_URL + '/#verify-email?token='` |
| 13 | Password reset links? | `APP_URL + '/#reset-password?token='` |

---

# 4. Production URL scan

## Old domain `interiorsmartcut.in`

**Result:** **Zero matches** in the repository. No production action required for that domain.

## URL / reference table (actionable)

| File | Current URL/Reference | Purpose | Production Action |
|------|-----------------------|---------|-------------------|
| `.env` (server) | *(create from example)* | Runtime config | Set `APP_URL=https://app.fmos.in`, `APP_ENV=production`, `APP_DEBUG=false`, DB, mail, `APP_KEY` |
| `.env.example` | `APP_URL=http://localhost:8080` | Template only | Keep as local template; **do not copy localhost to production `.env`** |
| `src/Domains/Identity/EmailVerificationService.php` | `Env::get('APP_URL', 'http://localhost:8080')` | Verify email links | Ensure production `.env` `APP_URL=https://app.fmos.in` |
| `src/Domains/Identity/PasswordResetService.php` | same default | Reset password links | Same |
| `src/Core/Mailer.php` | same default | Email template `$appUrl` | Same |
| `src/Core/Security.php` | `APP_URL` + `CORS_ALLOWED_ORIGINS` | Origin allowlist for mutating requests | `APP_URL=https://app.fmos.in`; leave CORS empty unless extra frontends |
| `public/assets/js/api.js` | `fetch(path)` relative | API calls | **No change** — resolves to `https://app.fmos.in/api/...` |
| `public/assets/js/*.js` | `/api/v1/...` paths | SPA API | **No change** |
| `public/assets/js/furniture.js` | `https://unpkg.com/three@0.160.0/...` | CDN Three.js | Allow outbound HTTPS to unpkg **or** vendor Three.js locally later |
| `public/assets/js/designer.js` | same unpkg | CDN Three.js | Same |
| `public/app.html` | `/assets/...`, `/favicon...` | Absolute-from-root asset paths | Works when docroot = `public/` |
| `src/Core/Mail/MailAddresses.php` | `*@fmos.in` defaults | From/reply mailboxes | Align SMTP From with allowed senders (`accounts@fmos.in`, etc.) |
| `.env.example` mail map | `accounts@fmos.in` … | Mail identity | Keep; configure SMTP for those addresses |
| `README.md` / tests | `127.0.0.1:8088` | Local/dev | **No production change** |
| `src/Core/Mail/SmtpMailTransport.php` | `EHLO localhost` | SMTP protocol greeting | Optional later harden; not a public URL |
| `src/Core/Database.php` | default host `127.0.0.1` | DB | Set `DB_HOST` to MilesWeb MySQL host (often `localhost`) |
| REQ / docs specs | various example URLs | Documentation only | Ignore for runtime |

## API shape in production

```text
https://app.fmos.in/api/v1/...
```

Same origin as the SPA. **No separate API subdomain** in current code.

## Email link shape

```text
https://app.fmos.in/#verify-email?token=<token>
https://app.fmos.in/#reset-password?token=<token>
```

Requires `APP_URL=https://app.fmos.in` (no trailing slash issues — code rtrims `/`).

---

# 5. Path / URL consistency check

```text
Browser
  ↓
https://app.fmos.in                    ← KNOWN
  ↓
MilesWeb / cPanel + SSL                ← confirm
  ↓
Document Root = <APPLICATION_ROOT>/public   ← REQUIRED by code; confirm path
  ↓
public/index.php  (+ Apache rewrite)   ← entry; .htaccess REQUIRED (missing in repo today)
  ↓
PHP application (src/, .env, vendor/)
  ↓
MySQL (<MYSQL_*>)                      ← cPanel MySQL
```

```text
Frontend (app.html + /assets/js)
  ↓ relative fetch /api/v1/*
Backend (Router + Domains)
  ↓ PDO
Database
```

| Check | Result |
|-------|--------|
| Frontend → API same host | Yes |
| Absolute API host hard-coded | No |
| Static assets under `/assets` | Yes |
| Media under `/media/tenants/...` | Yes (must exist under docroot `public/media`) |
| Storage outside docroot | Yes when structure followed (`../storage`) |

---

# 6. Critical Apache gap (discovered)

The repository has **`storage/.htaccess`** (deny) but **no `public/.htaccess`**.

Built-in PHP server uses `public/router.php`. **cPanel Apache will not** route `/api/v1/...` or SPA deep links through `index.php` without rewrite rules.

**Production action:** Create `public/.htaccess` at deploy time (recommended content):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^ index.php [L]
```

Also ensure cPanel “Force HTTPS Redirect” is on for `app.fmos.in`.

---

# 7. Production `.env` checklist (known + placeholders)

```env
APP_NAME=FMOS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.fmos.in
APP_KEY=<GENERATE_32+_CHAR_SECRET>
BOOTSTRAP_SECRET=

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<MYSQL_DATABASE>
DB_USERNAME=<MYSQL_USER>
DB_PASSWORD=<MYSQL_PASSWORD>

SESSION_NAME=fmos_session
SESSION_LIFETIME=7200

STORAGE_PATH=storage
LOG_LEVEL=warning

MAIL_DRIVER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<SMTP_USER>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=FMOS
MAIL_FROM_ACCOUNTS=accounts@fmos.in
MAIL_FROM_NOREPLY=no-reply@fmos.in
MAIL_REPLY_TO=support@fmos.in
MAIL_EXPOSE_TOKENS=false

CORS_ALLOWED_ORIGINS=

EMAIL_VERIFY_TTL_HOURS=24
PASSWORD_RESET_TTL_MINUTES=60
PASSWORD_MIN_LENGTH=12
```

| Variable | Source |
|----------|--------|
| `APP_URL=https://app.fmos.in` | **Known** |
| `APP_ENV=production` | **Discovered requirement** |
| DB_* | **cPanel MySQL + developer** |
| `APP_KEY` | **Developer generate** |
| MAIL_* SMTP | **Developer + mail provider / MilesWeb** |
| Mailbox addresses `@fmos.in` | **Known product mail map** |

---

# 8. Deployment runbook (ordered)

### A. Confirm with MilesWeb / cPanel

1. Document Root for `app.fmos.in` → record exact path.  
2. PHP version ≥ **8.1**.  
3. `mod_rewrite` / AllowOverride enabled.  
4. SSL certificate active for `https://app.fmos.in`.  
5. Force HTTPS enabled.  
6. DNS A record for `app.fmos.in` → server IP.  
7. Decide policy for `www.app.fmos.in` (redirect recommended).

### B. Place application files

1. Clone or upload repo to `/home/fuiyrsce/apps/fmos/` (recommended).  
   - GitHub remote: `https://github.com/dixitpranesh/FMOSV9.git` (confirm: `<GITHUB_REPOSITORY>`).  
2. Point Document Root for `app.fmos.in` to `/home/fuiyrsce/apps/fmos/public` (cPanel → Domains).  
3. Add `public/.htaccess` (front controller).  
4. Ensure `storage/` is writable (`logs`, `mail`, `rate_limits`, `tenants`, `exports`).  
5. Ensure `storage/.htaccess` / deny remains in place.

### C. PHP dependencies

```bash
cd /home/fuiyrsce/apps/fmos
composer install --no-dev --optimize-autoloader
```

*(If Composer unavailable on host, build `vendor/` locally and upload.)*

### D. Database

1. Create MySQL database/user in cPanel → fill `<MYSQL_*>`.  
2. Copy `.env.example` → `.env` and set production values above.  
3. Run:

```bash
php bin/migrate.php
php bin/seed.php
```

4. Optionally seed catalog via authenticated API or ops process after login.

### E. Mail

1. Configure SMTP for `accounts@fmos.in` / transactional provider.  
2. Set `MAIL_DRIVER=smtp` and credentials.  
3. Send test registration and confirm link is `https://app.fmos.in/#verify-email?...`.

### F. Smoke checks

| Check | Expected |
|-------|----------|
| `https://app.fmos.in/` | SPA loads |
| `https://app.fmos.in/api/v1/health` | JSON `status: ok` |
| `https://app.fmos.in/assets/js/app.js` | 200 |
| Login / register | Works |
| Verify email link host | `app.fmos.in` |
| `https://app.fmos.in/.env` | **404/403** |
| `https://app.fmos.in/../.env` | Blocked |
| HTTP → HTTPS | Redirect |

### G. Post-deploy hardening

- `APP_DEBUG=false`  
- Empty `BOOTSTRAP_SECRET` (keep `/api/v1/tenants` disabled)  
- `MAIL_EXPOSE_TOKENS=false`  
- Restrict file permissions on `.env` (`chmod 600` if shell available)  
- Confirm CDN Three.js allowed or self-host later  

---

# 9. Request flow diagram (production)

```text
https://app.fmos.in
        │
        ▼
MilesWeb Apache (SSL)
        │
        ▼
Document Root: /home/fuiyrsce/apps/fmos/public   (*or /home/fuiyrsce/app.fmos.in/public*)
        │
        ├─ /assets/*, /media/*, favicon → static files
        ├─ /api/v1/* → rewrite → index.php → Router → Domains → MySQL
        └─ other paths → index.php → app.html (SPA)
                │
                └─ JS fetch('/api/v1/...') same origin
```

---

# 10. Explicit non-assumptions

| Assumption | Status |
|------------|--------|
| `/home/fuiyrsce/app.fmos.in/` **is** the document root today | **Likely yes** (empty boilerplate folder) — change to `…/public` |
| DNS → MilesWeb | **Must confirm** |
| SSL already installed | **Must confirm** (`.well-known` present suggests SSL activity) |
| `www` is required | **Not used by code** |
| `interiorsmartcut.in` still in **this** FMOSV9 code | **False — not present** (sibling site on same cPanel only) |
| Separate `api.` subdomain | **Not required by current app** |

---

# 11. Summary for implementers

| Topic | Decision |
|-------|----------|
| Public URL | `https://app.fmos.in` |
| cPanel home | `/home/fuiyrsce/` |
| Current domain folder | `/home/fuiyrsce/app.fmos.in/` (empty boilerplate) |
| App code location (recommended) | `/home/fuiyrsce/apps/fmos/` |
| Document root (required) | `/home/fuiyrsce/apps/fmos/public` (or `/home/fuiyrsce/app.fmos.in/public`) |
| API | `https://app.fmos.in/api/v1/...` |
| Must set | `APP_URL=https://app.fmos.in` |
| Must add | Apache `public/.htaccess` front controller |
| Old domain cleanup | None needed for `interiorsmartcut.in` |
| Confirm with MilesWeb | Docroot, DNS, SSL, PHP 8.1+, rewrite |

---

*End of production deployment analysis. No codebase URLs were modified.*
