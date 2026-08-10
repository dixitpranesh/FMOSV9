# CR-001 Decision Register

**Change Request:** Furniture-Centric Design-to-Manufacturing Workflow Refactor  
**Status:** Decisions recorded — analysis/plan authorized; **application code not started**  
**Date:** 2026-08-10

---

## Product Owner Decisions

| ID | Topic | Decision | Implication |
| -- | ----- | -------- | ----------- |
| **CRD-001** | Project create behavior | **B** — Keep auto-create Building/Floor/Room for compatibility; furniture must not require them | Floor hierarchy remains; furniture FK to room becomes optional |
| **CRD-002** | Commercial vs manufacturing BOM | **C** — **Unify** one BOM for quote and manufacturing | Evolve commercial + mfg BOM into one canonical BOM derived from furniture/components/panels |
| **CRD-003** | Component storage | **A** — Relational `furniture_components` is canonical; JSON for geometry/snapshots only | Migrate off opaque `components_json` as source of truth |
| **CRD-004** | Existing data | **B** — **Legacy mode** for old projects; new projects use furniture-first | Dual-path runtime; migration tooling; no silent rewrite of production data |
| **CRD-005** | Cutting-size rules | **A** — Tenant manufacturing rules table with seeded editable defaults | No hard-coded sample allowances in engine code |
| **CRD-006** | First-release scope | **A** — **Full CR** (spec, components, furniture 2D/3D, multi-furniture nesting, exports, migration) | Large phased delivery; acceptance = CR §56 DoD |
| **CRD-007** | Organization | **A** — Keep required `organization_id` (DEC-004) | Project create still requires Org |
| **CRD-008** | Laminate SKU grain | **LQ-001=A** — Each file = one SKU | 45 selectable laminates from `C:\laminates` |
| **CRD-009** | Texture series codes | **LQ-002** — `ECO`=Echoe (rhythmic flutes); `SHR`=Shore (layered grooves + flat ridges); `STR`=Strand (straight-line grooves) | Store `series_code` + human `series_name`; drive UI filters/labels |
| **CRD-010** | Finish assignment levels | **LQ-003** — **Multi-finish**: exterior + interior + per-component overrides | Required for multi-colour doors (e.g. kids wardrobe) |
| **CRD-011** | Laminate display name | **LQ-005** — Use filename/SKU code as display name | No separate marketing name until later catalog enrichment |
| **CRD-012** | Laminate → 3D | Selected finish(es) apply albedo textures in furniture Three.js | Import to app storage; see `CR/05_LAMINATE_CATALOG_AND_3D.md` |

---

## Prior FMOS decisions still in force

Unless CR overrides:

- DEC-001 dual project `status` + `workflow_stage`
- DEC-004 Tenant → Organization → Project
- DEC-007 Vanilla ES6 + CSS
- DEC-009 MVP Option B baseline (extended by CR-001 full furniture-centric scope)
- DEC-013 BOM header + immutable revisions
- DEC-014 `manufacturing.release` = MANUFACTURING_MANAGER
- Panel FSM QR-canonical; soft mfg release in MVP era

**CR override note:** CRD-002 changes BOM ownership/order relative to earlier commercial-first sequencing — unified BOM derived from manufacturing panel/component model, also used for quotations.

---

## Conflict log (resolved by decisions)

### CONFLICT #1 — Project auto hierarchy
- **Existing:** `ProjectService::createProject` always creates Building/Floor/Room  
- **CR:** Project without Floor required  
- **Decision:** CRD-001 = B (keep auto-create; decouple furniture)

### CONFLICT #2 — BOM pipeline order
- **Existing:** Commercial generate before manufacturing  
- **CR:** Panels → BOM → Nesting → Cutlist  
- **Decision:** CRD-002 = C (unified BOM)

### CONFLICT #3 — room_id mandatory
- **Existing:** `furniture_instances.room_id NOT NULL`  
- **CR:** Furniture without Room  
- **Decision:** CRD-003/004 — nullable room + legacy mode for old projects

---

## Gate

```text
[x] Product decisions recorded (CRD-001–012)
[x] Analysis docs complete
[x] Phased plan reviewed by owner
[x] Implementation authorized — APPROVE CR-001 PLAN (2026-08-10)
```
