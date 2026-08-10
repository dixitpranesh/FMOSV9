# CR-001 Analysis — Architecture, Inventories & Gap Analysis

**Status:** Analysis complete (pre-implementation)  
**Basis:** Current FMOS codebase on `master` + `CR/CHANGE_REQUEST_FURNITURE_CENTRIC_WORKFLOW.md` + CRD-001–007  
**Date:** 2026-08-10

---

## 48.1 Architecture Map (Current)

```text
Browser (Vanilla ES6 + HTML/CSS + Canvas + Three.js CDN)
        │  REST /api/v1 + Bearer token
        ▼
public/index.php → Router → Http/Routes/*
        │
        ▼
Domain services (src/Domains/*)
  Identity | Tenant | Project | Architecture | Furniture | Catalog
  Pricing/Commercial | Manufacturing
        │
        ▼
MySQL 8 (fmos_v9) + JSON columns for params/geometry/components/snapshots
        │
        ▼
storage/ (logs, uploads, exports)
```

| Layer | Location | Notes |
| ----- | -------- | ----- |
| Frontend shell | `public/app.html`, `public/assets/js/*` | Hash routes; project-centric nav |
| API | `src/Http/Routes/*.php` | Phase-split route files |
| Auth/RBAC | `src/Core/Auth.php`, Identity seeder | 27 roles; permission checks |
| Project | `ProjectService` | Auto-creates Building/Floor/Room |
| Floor 2D/3D | `DesignService` + `designer.js` | Room-scoped `design_objects` |
| Furniture | `FurnitureEngine` | Template → instance; `components_json` |
| Catalog | `CatalogService` | `catalog_products` |
| Commercial | `CommercialService` | BOM/BOQ/pricing/quote from furniture components |
| Manufacturing | `ManufacturingService` | Validate, panels, cutlist, nest, labels |
| Tests | `tests/e2e_mvp.php`, `tests/run.php` | E2E assumes room exists |

---

## 48.2 Current Workflow

```text
Create Tenant/Org/User
  → Create Client
  → Create Project  ★ auto Building → Floor → Room
  → (optional) 2D floor walls/doors on Room
  → Create Furniture Instance ★ REQUIRES room_id
  → Generate Commercial (BOM/BOQ/Price/Quote)
  → Generate Manufacturing (single furniture)
  → Release
  → Nest + Labels
```

---

## 48.3 Dependency Map — Furniture → Floor/Room

| Location | Dependency | Severity |
| -------- | ---------- | -------- |
| `furniture_instances.room_id` NOT NULL + FK | Hard DB requirement | **CRITICAL** |
| `FurnitureEngine::createInstance` requires `room_id` | API/service | **CRITICAL** |
| `POST /api/v1/furniture/instances` body | API contract | **CRITICAL** |
| `furniture.js` reads first room from project | UI | HIGH |
| `e2e_mvp.php` uses room from hierarchy | Tests | HIGH |
| `design_objects.room_id` NOT NULL | Floor design only | OK (keep) |
| `panels` / mfg package | `furniture_id` only (no room) | OK |
| BOM/BOQ | project + furniture | OK |

**No wall_id / building_id on furniture today** — only `room_id` (+ project_id).

---

## 48.4 JSON Inventory

| JSON Location | Purpose | Consumer | Canonical? | Proposed Treatment |
| ------------- | ------- | -------- | ---------- | ------------------ |
| `furniture_instances.parameter_values_json` | Parametric dims/counts | FurnitureEngine, UI | Semi | Keep for params; promote key dims to columns (width/height/depth/qty/code) |
| `furniture_instances.components_json` | Generated component list | Mfg, Commercial, UI | **Yes today (bad)** | **Migrate to relational `furniture_components`** (CRD-003); JSON snapshot only |
| `furniture_instances.position_json` | Placement pose | Future floor placement | No | Keep; optional when room linked |
| `furniture_instances.stale_flags_json` | Downstream stale markers | UI/mfg | Yes (lifecycle) | Keep; extend artifact statuses |
| `furniture_templates.parameters_json` | Template param defs | Engine | Config | Keep as configuration |
| `furniture_templates.rules_json` | Rule metadata | Engine | Config | Expand to tenant mfg rules table |
| `design_objects.geometry_json` | Wall/door geometry | 2D/3D designer | Geometry | Keep |
| `design_objects.parameters_json` | Object params | Designer | Geometry | Keep |
| `design_objects.materials_json` | Material refs | Designer | Flexible | Keep / link catalog IDs |
| `panels.edge_json` | Edge flags | Mfg/cutlist | Partial | Evolve to structured edge fields + rules |
| `manufacturing_packages.validation_json` | Validation issues | Mfg UI | Generated | Keep |
| `manufacturing_packages.snapshot_json` | Release snapshot | Traceability | Snapshot | Keep / enrich (CR §33) |
| `nesting_jobs.layout_json` | Sheet placements | Nesting UI | Generated | Keep; drive visual layout |
| `bom/pricing snapshot JSON` | Quote immutability | Commercial | Snapshot | Keep; align with unified BOM |
| `catalog_products.attributes_json` | Extra attrs | Catalog | Flexible | Keep |
| `tenants.settings_json` | Tenant settings | Admin | Config | Keep; may hold feature flags / legacy mode |

---

## 48.5 Database Inventory (material tables)

| Table | Purpose | Related | JSON Columns | Migration for CR-001 |
| ----- | ------- | ------- | ------------ | -------------------- |
| `projects` | Project | org, client | — | Add `model_mode` LEGACY\|FURNITURE_FIRST (CRD-004) |
| `buildings`/`floors`/`rooms` | Optional spatial | project | — | Keep; still auto-created (CRD-001) |
| `furniture_templates` | Parametric templates | instances | parameters, rules | Extend categories/types |
| `furniture_instances` | Furniture modules | project, **room** | params, components, position, stale | **room_id NULL**; add code/category/type/qty columns; drop components as SoT |
| *(new)* `furniture_components` | Component tree | furniture, parent | geometry, mfg_data | **Create** (CRD-003) |
| *(new)* `furniture_specifications` or columns on instance | Spec/finish/hardware | furniture | optional JSON bags | Structured + JSON for extensibility |
| *(new)* `tenant_manufacturing_rules` | Cutting allowances, kerf defaults | tenant | rule_json | **Create** (CRD-005) |
| *(new)* `sheet_definitions` | Configurable sheets | tenant/catalog | — | **Create**; replace hard-coded 2440×1220 |
| `catalog_products` | Materials/finishes/hardware | BOM/panels | attributes | Link finishes/edge bands |
| `manufacturing_packages` | Soft release package | project, furniture | validation, snapshot | Support multi-furniture job header |
| *(new)* `manufacturing_jobs` | Multi-module mfg run | project, selected furniture | — | **Create** |
| `panels` | Panel instances | package, furniture | edge_json | Add component_id, finishing/cutting sizes, material/finish FKs |
| `cutlist_items` | Cutlist rows | package, panel | — | Expand columns per CR §28 |
| `nesting_jobs` | Nest results | package | layout_json | Link sheet_definition; visual layout |
| `bom_headers`/`bom_revisions`/`bom_items` | BOM | project, furniture | — | **Unify** as engineering+commercial BOM (CRD-002) |
| `boq_*` / `quotations` / `pricing_*` | Commercial | BOM | snapshots | Quote from unified BOM revisions |
| `design_objects` | Floor CAD | room | geometry… | Unchanged |
| `audit_logs` | Audit | all | before/after | Reuse |

---

## 48.6 Existing Engine Inventory

| Engine | Current state | Reuse plan |
| ------ | ------------- | ---------- |
| 2D floor CAD | Canvas designer, room-scoped objects | Keep for optional Floor Design |
| 2D furniture drawings | **Missing** | New furniture 2D views from component model |
| 3D floor | Three.js from design_objects | Keep |
| 3D furniture | Approximate only via floor designer / box | New component-accurate 3D from canonical model |
| Parametric rules | Deterministic wardrobe/kitchen generator | Evolve → component tree writer |
| Panel generator | From components_json + sheet-fit split | Drive from `furniture_components`; finishing/cutting rules |
| BOM | CommercialService from components | Unify with mfg panels/components |
| Cutlist | Thin table from panels | Expand fields; finishing vs cutting |
| Nesting | Basic shelf packing; hard-coded sheet | Configurable sheets; visual layout; multi-furniture; manual override later in plan |
| Export | Labels JSON / quote snapshot only | PDF design package + mfg package + Excel cutlist |
| Validation | Size/param blockers | Full CR §17 checklist |
| Revision/stale | revision int + stale_flags_json | Formal artifact statuses + mfg snapshots |

---

## 48.7 Gap Analysis

| Domain | Current | Target | Gap | Risk | Proposed Change |
| ------ | ------- | ------ | --- | ---- | --------------- |
| Project UX | Assumes room before furniture | Furniture immediately | Nav/API/UI | Medium | Furniture-first nav; room optional |
| Furniture FK | room required | room optional | Schema+API | High | Nullable room_id; legacy mode |
| Spec model | Template params only | Full spec (construction/finish/hardware/mfg) | Large | High | Spec entity/columns + UI |
| Components | JSON blob | Relational tree | Large | High | `furniture_components` + migration |
| 2D furniture | None | Multi-view drawings + dims + title block | Large | High | New furniture 2D workspace |
| 3D furniture | Weak | Component-accurate + export image/GLB | Large | High | New furniture 3D from components |
| Validation | Partial | Full gate before mfg | Medium | Medium | Validation service |
| Panels | L/W only | Finishing vs cutting + edges + traceability | Medium | Medium | Panel schema + rules engine |
| BOM | Commercial-first separate | Unified BOM | Medium | High | Single BOM pipeline (CRD-002) |
| Nesting | Single furniture, hardcoded sheet | Multi-furniture, configurable, visual, override | Large | High | Sheet defs + job model + UI |
| Cutlist | Minimal columns | Full professional columns | Medium | Medium | Expand cutlist generator |
| Exports | Minimal | Design PDF + mfg package | Large | Medium | Export services |
| Data migration | N/A | Legacy mode + tools | Medium | High | `model_mode` + converters |
| RBAC/Audit | Exists | Preserve + new perms | Low | Low | Add furniture/mfg action perms |

---

## Target Architecture (CR §49)

```text
PROJECT (organization required)
│
├── Furniture  (room_id NULL allowed; optional placement later)
│   ├── Specification
│   ├── Components (relational tree)  ← canonical
│   ├── Materials / Finishes / Hardware
│   ├── 2D views (plan/elevations/section)
│   ├── 3D (same model)
│   └── Artifact status (2D/3D/BOM/Panels/Cutlist/Nesting)
│
├── Optional Floor Design
│   └── Room → optional furniture placement
│
└── Manufacturing Jobs (1..N furniture modules)
    ├── Validation
    ├── Snapshot
    ├── Panels (finishing/cutting, edges, component_id)
    ├── Unified BOM
    ├── Sheets (from sheet_definitions)
    ├── Nesting (visual layout)
    └── Cutlist (+ export)
```

### Legacy vs Furniture-First

| Mode | When | Behavior |
| ---- | ---- | -------- |
| `LEGACY` | Existing projects (CRD-004) | Current APIs/UI paths continue; room-linked furniture OK |
| `FURNITURE_FIRST` | New projects default | Furniture CRUD without room; new workspaces |

---

## Open technical risks (non-blocking to plan)

1. Full CR scope (CRD-006=A) is large — plan phases must ship vertical slices with DoD gates.  
2. Unified BOM may temporarily break quote UX until quote reads new BOM revisions — needs careful migration of `CommercialService`.  
3. Furniture 2D title-block PDF without a PDF library — choose TCPDF/Dompdf or HTML-print MVP in export phase.
