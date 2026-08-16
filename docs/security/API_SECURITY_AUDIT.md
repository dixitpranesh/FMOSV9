# FMOS API Security Audit

**Date:** 2026-08-16  

**Conventions:** Auth default = required unless noted. Rate limit = only where listed. Tenant = `requireTenant` + service filters unless noted.

---

## 1. Public / Unauthenticated Endpoints

| Method | Endpoint | Auth | Role | Tenant | Validation | Rate Limit | Risk |
|--------|----------|------|------|--------|------------|------------|------|
| GET | `/api/v1/health` | No | — | — | — | No | Med — error details |
| GET | `/api/v1/ping` | No | — | — | — | No | Low |
| POST | `/api/v1/auth/login` | No | — | — | email/pass | 30/5m IP | Med |
| POST | `/api/v1/auth/logout` | No | — | — | — | No | Low |
| POST | `/api/v1/auth/register` | No | — | — | persona fields | 10/h IP | Med — debug token |
| POST/GET | `/api/v1/auth/verify-email` | No | — | — | token | No | Med — GET query |
| POST | `/api/v1/auth/resend-verification` | No | — | — | email | 5/h IP | Low |
| POST | `/api/v1/auth/forgot-password` | No | — | — | email | 5/h IP | Low |
| POST | `/api/v1/auth/reset-password` | No | — | — | token+pass | 10/h IP | Low |
| POST | `/api/v1/tenants` | **No** | — | creates | weak | **No** | **Critical SEC-001** |
| POST | `/api/v1/rbac/seed` | **No** | — | global | — | **No** | **Critical SEC-002** |

---

## 2. Identity (authenticated)

| Method | Endpoint | Auth | Permission | Tenant | Notes | Risk |
|--------|----------|------|------------|--------|-------|------|
| GET | `/api/v1/auth/me` | Yes | — | — | Current user | Low |
| GET | `/api/v1/organizations` | Yes | organization.view | Yes | | Low |
| POST | `/api/v1/organizations` | Yes | organization.create | Yes | | Low |
| GET | `/api/v1/roles` | Yes | role.view | Global system roles | | Low |
| GET | `/api/v1/permissions` | Yes | role.view | Global | | Low |
| GET | `/api/v1/users` | Yes | user.view | Yes | No password hash | Low |

---

## 3. CRM / Projects (`02_projects.php`)

| Method | Endpoint | Perm | Tenant | Input risk | Risk |
|--------|----------|------|--------|------------|------|
| GET/POST | `/api/v1/clients` | client.view/create | Yes | | Low |
| GET/POST | `/api/v1/projects` | project.view/create | Yes | **org/client FKs** | **High SEC-003** |
| GET | `/api/v1/projects/{id}` | project.view | Yes | | Low |
| PATCH | `/api/v1/projects/{id}/workflow` | project.update | Yes | | Low |

---

## 4. Domain (`03_domain.php`) — summary groups

| Group | Auth | Typical perms | Tenant on read | Create FK risk | Rate limit | Risk |
|-------|------|---------------|----------------|----------------|------------|------|
| Design objects | Yes | design.* | Yes | project/room | No | High create |
| Catalog products | Yes | catalog.* | Yes | — | No | Low |
| Furniture templates/types | Yes | furniture.view | Partial global templates | — | No | Low–Med |
| Furniture instances | Yes | furniture.* | Yes | project/room | No | High create |
| Materials | Yes | catalog.view | Yes | — | No | Low |
| Kitchen compositions | Yes | furniture.* | Yes | project | No | Med |
| Manufacturing generate/release/nest | Yes | manufacturing.* / nesting.* | Yes on get | project | **No** | Med DoS SEC-019 |
| Cutlist export | Yes | manufacturing.view | Yes | — | No | Med export |
| Nesting sheet-plan/PDF | Yes | nesting.* | Yes | — | No | Med CPU |
| Commercial / quotations | Yes | bom/quote.* | Partial | project/client | No | High create |
| 2D/3D model JSON | Yes | furniture.view | Yes | Large payloads | No | Med DoS |

---

## 5. Cross-Cutting API Issues

| Topic | Finding |
|-------|---------|
| CSRF | Cookie-only; SPA Bearer skips — SEC-004 |
| CORS | Not configured — same-origin assumed — SEC-023 |
| Mass assignment | Body FKs trusted — SEC-003; `tenant_id` not taken from body for auth |
| Excessive data | Design/3D JSON large; exports return content in JSON |
| Pagination | Limited / inconsistent — DoS via large lists possible |
| Request size | No explicit global body size guard in Router |
| Versioning | `/api/v1` present |
| Webhooks | None |
| API keys | Session Bearer only |
| Replay | Tokens single-use for verify/reset; API Bearer reusable until expiry |

---

## 6. API Remediation Priorities

1. Remove/lock public bootstrap endpoints.  
2. Ownership asserts on all FK inputs.  
3. Rate-limit expensive manufacturing/nesting/export.  
4. Origin allowlist if cross-site clients appear.  
5. Consistent pagination + max page size.  
6. Declarative auth metadata on routes for reviewability.
