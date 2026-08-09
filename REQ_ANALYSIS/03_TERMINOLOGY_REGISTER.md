# Terminology Conflict Register (Phase 3 — Interim)

**Rule:** Do not auto-merge terms. Decisions required where impact is material.

| ID | Term A | Term B | Possible Meaning | Evidence | Potential Impact | Decision Required |
| -- | ------ | ------ | ---------------- | -------- | ---------------- | ----------------- |
| TERM-001 | FMOS | Interior Design… Platform (long title) | Same product, later branding | Early vs late REQ docs | Naming in UI/docs | Confirm product name = FMOS |
| TERM-002 | Tenant | Organization | Same org vs parent/child | Vision Tenant→Org→Project; DB has Tenant only; BRD “across organizations” | Data model | See CONF-DATA-001 |
| TERM-003 | Company | Tenant | Synonym for tenant business | Vision “Company configuration” | Admin UX copy | Confirm synonym |
| TERM-004 | Client | Customer | CRM party vs SaaS buyer vs portal user | Vision target customers = SaaS buyers; CRM Client; portal Client | CRM schema + portal | Freeze glossary |
| TERM-005 | Business Owner | TENANT_OWNER | Highest tenant commercial role | Vision/BRD vs RBAC | Bootstrap permissions | See CONF-RBAC-005 |
| TERM-006 | Platform Super Admin | Platform Admin / Super Admin | Same platform role | Multiple docs | Admin APIs | See CONF-RBAC-003 |
| TERM-007 | Factory Supervisor | PRODUCTION_SUPERVISOR / Factory Manager | Factory leadership | Vision vs RBAC vs Stories | MES RBAC | Map aliases |
| TERM-008 | Design Engineer | ENGINEER / Manufacturing Engineer | Engineering roles | Vision vs RBAC vs Security | Release rights | See CONF-RBAC-002 |
| TERM-009 | materials | catalog_products | Catalog master entity | DB/API vs Material spec | FKs | See CONF-DATA-003 |
| TERM-010 | furniture_instances | furniture | Placed furniture entity | DB vs API path | API naming | Pick public name |
| TERM-011 | DISPATCHED | SHIPPED | Terminal logistics panel state | MES/QR vs Mfg Engine | Status enum | Alias or distinct |
| TERM-012 | APPROVED (quote) | ACCEPTED (quote) | Client commercial acceptance | SRS vs Pricing | Quote FSM | See CONF-LIFE-009 |
| TERM-013 | ACTIVE (catalog) | PUBLISHED (catalog) | Commercial availability vs publish workflow | Material §10 vs §101 | Designer selectable materials | See CONF-LIFE-007 |
| TERM-014 | project.edit | project.update | Same permission | SRS/Security vs RBAC | Permission keys | Standardize keys |
| TERM-015 | User | Employee / Member / Operator | Generic principal vs role | Scattered | Avoid treating User as role | Confirm User ≠ role |

---

## Working Glossary Proposal (NOT APPROVED)

Pending owner confirmation:

| Proposed Canonical Term | Meaning |
| ----------------------- | ------- |
| **FMOS** | Product name |
| **Tenant** | SaaS customer organization (isolation root) |
| **Organization / Business Unit** | *TBD* — optional sub-tenant structure |
| **Client** | Tenant’s end customer (CRM) |
| **User** | Authenticated human principal |
| **Role** | Named permission bundle |
| **Project** | Design/manufacturing job container |
| **Panel Instance** | Trackable physical panel |
| **Manufacturing Revision** | Immutable engineering release snapshot |
| **Production Order** | MES execution of a manufacturing revision |
