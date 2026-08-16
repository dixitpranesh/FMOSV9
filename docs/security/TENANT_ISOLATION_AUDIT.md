# FMOS Tenant Isolation Audit

**Date:** 2026-08-16  

---

## 1. Tenant Model

| Question | Answer |
|----------|--------|
| Multi-tenant? | **Yes** — shared MySQL, `tenant_id` on primary business tables |
| Tenant key | `tenants.id` → denormalized as `users.tenant_id`, `projects.tenant_id`, etc. |
| Org vs tenant | `organizations.tenant_id`; projects also store `organization_id` |
| Resolution | `Auth::tenantId()` from authenticated user row — **not** from request |
| Middleware | **None** — handlers call `Auth::requireTenant()` ad hoc |

Platform users may have `tenant_id = NULL` and are blocked from tenant APIs by `requireTenant()`.

---

## 2. Entity Ownership Matrix

| Entity | Tenant Key | Ownership Check | API Enforcement | Risk |
|--------|------------|-----------------|-----------------|------|
| User | `tenant_id` | List by tenant | Yes | Low |
| Organization | `tenant_id` | List/create | Yes | Low |
| Client | `tenant_id` | List/create/get | Yes on read; **FK on project create unchecked** | High |
| Project | `tenant_id` + `organization_id` | get/list by tenant | Create org/client **unchecked** | High |
| Building/Floor/Room | `tenant_id` + parent | Via project get | Nested SELECT by parent only | Medium |
| Design object | `tenant_id` | CRUD by id+tenant | Create project/room unchecked | High |
| Furniture instance | `tenant_id` | get/update by tenant | Create project/room unchecked | High |
| Kitchen composition | `tenant_id` | get/delete | Create path project unchecked | Medium |
| Material / catalog | `tenant_id` | Yes | Global templates separate | Low–Med |
| Manufacturing job/package | `tenant_id` | get/export yes | Create project_id unchecked | Medium |
| Quotation | `tenant_id` | status yes | Create client/project unchecked | High |
| Files/exports | Path / package id | Via package tenant | Flat export dir | Potential |
| Audit log | `tenant_id` nullable | Write | No list API audited | Info |

---

## 3. Confirmed Cross-Tenant Risks

1. **SEC-003** — Insert with foreign IDs belonging to another tenant (org/client/project/room).  
2. **Cascade / integrity** — `furniture_instances.project_id` FK `ON DELETE CASCADE` can amplify mistaken links.  
3. **Open provisioning (SEC-001)** — not classic IDOR, but destroys tenancy trust model.

**Not broadly confirmed:** Classic read IDOR on `GET /projects/{id}` style endpoints — most use `id + tenant_id`.

---

## 4. Enforcement Patterns

### Good pattern (common)

```sql
SELECT * FROM projects WHERE id = ? AND tenant_id = ?
```

### Bad pattern (creates)

```sql
INSERT INTO projects (tenant_id, organization_id, client_id, ...)
VALUES (?, ?, ?, ...)  -- org/client taken from body without ownership assert
```

### Incomplete defense-in-depth

```sql
SELECT * FROM buildings WHERE project_id = ?  -- after parent fetch; no tenant_id
```

---

## 5. File / Media Isolation

| Path | Isolation | Notes |
|------|-----------|-------|
| `public/media/tenants/{tenantId}/...` | Path-based | Guessable if IDs sequential; no auth on static |
| `storage/tenants/{id}/...` | Path-based | Outside public (assumed) |
| `storage/exports/` | **None** | Flat filenames |
| `storage/mail/` | **None** | Contains tokens when log driver |

---

## 6. Recommendations

1. Implement `TenantGuard::owned(string $table, int $id, int $tenantId): void`.  
2. Call on every create/update referencing FKs.  
3. Add automated cross-tenant tests (see `SECURITY_TEST_PLAN.md`).  
4. Partition exports under tenant directories; signed download endpoints.  
5. Consider soft-delete + restrict cascade for cross-entity deletes.  
6. Document that Option A (shared DB) remains the strategy with app+test enforcement.
