# FMOS Security Remediation Plan

**Date:** 2026-08-16  
**Rule:** Do not implement until stakeholders approve this plan.

---

## Recommended Implementation Order

```text
P0 — Emergency Security Fixes
P1 — Authentication & Authorization
P1 — Tenant Isolation
P1 — API Security
P2 — Data Security
P2 — File Security
P2 — Logging & Monitoring
P3 — Enterprise Identity
P3 — DevSecOps
P4 — Compliance Readiness
```

---

## Phase 0 — Emergency (P0)

| Item | Effort | Dependencies | Risk reduction | Modules | DB | Tests |
|------|--------|--------------|----------------|---------|-----|-------|
| Disable/gate `POST /tenants` | 0.5d | None | Eliminates open provisioning | `01_identity.php`, docs, smoke | None | Unauth 401/403 |
| Disable/gate `POST /rbac/seed` | 0.5d | CLI seed path | Protects global RBAC | routes, `bin/` | None | Unauth denied |
| FK ownership asserts on create | 2–3d | Shared helper | Stops cross-tenant linking | Project, Furniture, Design, Commercial, Mfg | Optional composite FKs later | Cross-tenant matrix |
| Gate `debug_verify_token` + protect mail storage | 0.5d | Env flags | Stops token harvest | RegistrationService, Mail, `.env.example` | None | Prod-like env |
| `APP_DEBUG` / error sanitization | 0.5d | None | Stops info leak | `index.php`, domain routes | None | Debug off |

**Rollback:** Feature flags / restore routes behind `BOOTSTRAP_ENABLED` secret.

---

## Phase 1 — Security Foundation

### Authentication & sessions

| Change | Effort | Notes |
|--------|--------|-------|
| Revalidate ACTIVE/lock on every `Auth::user()` | 1d | SEC-006; revoke on suspend |
| Strengthen password policy | 0.5d | SEC-010 |
| Remove demo password from UI | 0.25d | SEC-011 |
| Prefer HttpOnly session for SPA **or** short Bearer TTL | 3–5d | SEC-004 architecture |
| POST-only verify; fragment tokens in UI | 1d | SEC-013 |

### Authorization / tenant

| Change | Effort | Notes |
|--------|--------|-------|
| `TenantGuard::assertOwned` everywhere | 2d | Completes SEC-003 |
| Declarative route permission map | 2d | Reviewability |
| Nested queries include `tenant_id` | 1d | SEC-017 |

### API / browser

| Change | Effort | Notes |
|--------|--------|-------|
| HTML escape helper; purge unsafe `innerHTML` | 3–5d | SEC-005 |
| Security headers middleware | 1d | SEC-008 |
| Origin check for mutating cookie auth | 1d | CSRF defense-in-depth |
| Rate limits on mfg/nest/export | 1d | SEC-019 |
| RateLimiter file locking / Redis | 1–2d | SEC-012 |

**Risk reduction:** Blocks XSS→ATO chain and remaining isolation holes.  
**Regression:** UI encoding; clients with bad FKs fail closed.

---

## Phase 2 — SaaS Security

| Item | Effort | Notes |
|------|--------|-------|
| Tenant-partitioned exports + auth download | 2d | SEC-014 |
| Signed media URLs or auth gate | 2–3d | Static media |
| Subscription/entitlement service stubs + server enforcement | 5–10d | SEC-021 when productized |
| Expand audit events + export/admin | 2d | |
| PAN/GST envelope encryption | 3–5d | SEC-022 |
| Session revoke-all on status change | 1d | IR |

---

## Phase 3 — Enterprise Security

| Item | Effort | Notes |
|------|--------|-------|
| MFA (TOTP) optional → mandatory for owners | 2–3w | SEC-015 |
| SSO OIDC for enterprise tenants | 3–6w | |
| SCIM provisioning | Later | |
| SIEM-friendly structured security logs | 1w | SEC-025 |
| Secrets manager + rotation runbooks | 1w | |
| SBOM + Dependabot when Composer deps appear | 0.5w | SEC-020 |

---

## Phase 4 — Compliance Readiness

Assess (do not claim certification):

- SOC 2: access control, change mgmt, logging, IR  
- ISO 27001: ISMS processes beyond code  
- GDPR / India DPDP: DSAR, deletion, lawful basis, privacy@fmos.in already designated  

---

## Critical / High Implementation Specs

### SEC-001 / SEC-002

**Change:** Remove public routes or require `PLATFORM_SUPER_ADMIN` + CSRF + audit.  
**Architecture:** Bootstrap = CLI only.  
**Backend:** `01_identity.php`, new `bin/rbac_seed.php`.  
**Frontend:** None.  
**API:** Breaking for anonymous callers (intended).  
**Rollback:** Env `ALLOW_PUBLIC_BOOTSTRAP=false` default.

### SEC-003

**Change:** Central ownership assert before INSERT/UPDATE.  
**Backend:** New `src/Core/TenantGuard.php`; wire all services.  
**DB:** Optional `(tenant_id, id)` composite unique + app checks.  
**Tests:** Full cross-tenant matrix.  
**Rollback:** Flag `TENANT_FK_STRICT=true`.

### SEC-004 / SEC-005

**Change:** Escape all untrusted strings; CSP; migrate token storage.  
**Frontend:** `escapeHtml`, replace `innerHTML` sinks.  
**Backend:** Headers; optional cookie-session API.  
**Rollback:** CSP report-only first.

### SEC-006

**Change:** Status checks in `Auth::user()`; `revokeAllSessions` on suspend.  
**Tests:** Suspend mid-session.

### SEC-007

**Change:** Triple-gate debug tokens; `.htaccess`/nginx deny `storage`.  
**Tests:** Register without debug field in staging profile.

---

## Fix Prioritization Score (qualitative)

```text
Risk ∝ Severity × Exploitability × Business Impact × Tenant Impact

SEC-001, SEC-002  → maximum (P0)
SEC-003, SEC-005, SEC-004, SEC-006, SEC-007 → P0/P1
SEC-008…SEC-014 → P1/P2
SEC-015+ enterprise gaps → P3/P4
```
