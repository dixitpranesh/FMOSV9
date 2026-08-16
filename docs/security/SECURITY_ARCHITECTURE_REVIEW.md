# FMOS Security Architecture Review

**Date:** 2026-08-16  

---

## 1. Application Architecture (as implemented)

```text
Browser (public/app.html + public/assets/js/*)
   |  fetch JSON, Bearer from localStorage, credentials: same-origin
   v
public/index.php  →  Router  →  Route handlers (src/Http/Routes/*.php)
   |
   +---- Auth (session cookie + Bearer hashed in sessions table)
   |
   +---- Auth::requirePermission / requireTenant (per handler)
   |
   +---- Domain services (Project, Furniture, Manufacturing, Commercial, …)
   |
   +---- MySQL (shared DB, tenant_id columns)
   |
   +---- File storage
   |       storage/exports/          (flat)
   |       storage/mail/             (log driver)
   |       storage/rate_limits/
   |       storage/tenants/{id}/     (materials import)
   |       public/media/tenants/{id}/ (textures; web-reachable)
   |
   +---- Mail (log | smtp) via Mailer
   |
   +---- External services: none in runtime (no billing/AI/SSO yet)
```

**Stack verified:** PHP ≥8.1 (Composer has no packages), MySQL migrations under `database/migrations/`, vanilla JS SPA, Three.js-related UI modules, no Docker/CI configs in repo.

---

## 2. Trust Boundaries

| Boundary | Controls present | Gaps |
|----------|------------------|------|
| Browser → API | Auth on most routes; CSRF for cookie-only mutating; parameterized SQL | Open `/tenants`, `/rbac/seed`; Bearer CSRF bypass; XSS |
| User → API | RBAC `requirePermission` on many handlers | Permissions not declarative on router; release checks in service |
| Tenant A → Tenant B | `requireTenant()` + many `tenant_id` filters on reads | Unvalidated FKs on create; nested SELECTs omit tenant_id |
| Uploaded file → App | **No user upload API** | Future risk; CLI import paths |
| App → Mail | Driver abstraction | Log driver stores secrets |
| Admin → User | Platform users (`tenant_id` null) blocked from tenant APIs | Open seed/bootstrap; no real admin console / impersonation runtime |
| Web server → storage | Assumed `public/` docroot | Not enforced in repo; exports/mail outside public but fragile |

---

## 3. Data Flow Summary

| Data | Source | Processing | Storage | API Exposure | Access Control |
|------|--------|------------|---------|--------------|----------------|
| User | Register/login/seed | hash password; verify email | `users` | `/auth/*`, `/users` | Tenant-scoped list; global unique email |
| Organization | Register / org create | GST/PAN validators | `organizations`, addresses | `/organizations` | Tenant-scoped; GST masking helpers exist |
| Project tree | API create | workflow stages | projects/buildings/floors/rooms | `/projects*` | Reads tenant-scoped; create FK gap |
| Furniture | API | layout/rules engines | furniture_* | `/furniture*` | Reads tenant-scoped; create FK gap |
| Manufacturing | Generate/nest/export | cutlist, nesting, PDF | mfg tables + exports | `/manufacturing*`, `/nesting*` | Package/job tenant checks |
| Files/media | CLI laminate import | copy to media | `public/media/tenants/{id}` | Static URLs | Path includes tenant id; auth not on static |
| Billing | — | — | — | — | **Not implemented** |
| Audit | Auth/domain events | Audit::record | `audit_logs` | No dedicated query API found | Write-only from app |

**Insecure flows:** client-chosen FKs; XSS reflection of stored names; tokens in mail logs; static media without authz (anyone with URL).

---

## 4. Target Enterprise Security Architecture

Prefer **incremental hardening**, not rewrite:

```text
Identity (local + future SSO)
   ↓
Authentication (session primary; MFA; status revalidation)
   ↓
Authorization / RBAC (declarative route permissions)
   ↓
Tenant Resolution (from auth context only)
   ↓
Edge controls (rate limit, Origin, headers, payload limits)
   ↓
Application Services
   ↓
Business Rules + Entitlements
   ↓
TenantAwareRepository (mandatory tenant predicate)
   ↓
MySQL + tenant-partitioned object storage
   ↓
Audit + Metrics + Alerts
```

### Principles

1. **Never trust body `tenant_id` / foreign keys** without ownership assert.  
2. **Authn ≠ Authz ≠ Entitlement.**  
3. **HttpOnly session** preferred for browser; Bearer for machine clients with tight TTL.  
4. **Deny by default** on new routes (auth + permission + tenant).  
5. **Platform ops** only via CLI or break-glass admin, never public HTTP seed.

---

## 5. Recommended Tenant Isolation Strategy

**Option A — Shared database + `tenant_id` (current)** — **Recommended to retain.**

| Option | Fit |
|--------|-----|
| A Shared DB + tenant_id | Matches current schema/scale; OK with strict app enforcement + tests |
| B Schema per tenant | High ops cost; not needed yet |
| C DB per tenant | Overkill until enterprise isolation contracts demand it |

**Required upgrades for Option A:**

- Central `TenantScope::assertOwned($table, $id, $tenantId)`  
- Optional DB composite FKs `(tenant_id, id)` where MySQL version allows  
- File paths `storage/tenants/{id}/exports/`  
- Cache/search/AI keys always prefixed by tenant (when added)  
- Static media: signed URLs or auth gateway  

---

## 6. Target RBAC (incremental)

Keep existing system roles (`TENANT_OWNER`, `DESIGNER`, etc.) and extend:

| Role | Purpose |
|------|---------|
| PLATFORM_SUPER_ADMIN | Break-glass; seed; impersonate (audited) |
| TENANT_OWNER | Org admin + billing (future) |
| DESIGNER / ENGINEER / PRODUCTION | Existing domain perms |
| VIEWER | Read-only |
| BILLING_ADMIN | Future subscription |
| SECURITY_AUDITOR | Read audit logs |

**Permission model:** `role → permission → resource action`, always evaluated **inside tenant** except platform permissions.

Do **not** map registration personas to new RBAC roles (already correctly stored as `registration_type`).
