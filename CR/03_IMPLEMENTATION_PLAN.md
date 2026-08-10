# CR-001 Phased Implementation Plan

**Status:** **APPROVED** by owner (`APPROVE CR-001 PLAN`) — implementation in progress  
**Scope:** Full CR (CRD-006=A)  
**Decisions:** CRD-001–012  
**Approved:** 2026-08-10  
**Visual references:** `CR/04_REFERENCE_SAMPLES_INTERPRETATION.md` + `CR/samples/` (illustrative only; not one project)  
**Laminates:** `CR/05_LAMINATE_CATALOG_AND_3D.md` — local library `C:\laminates` → catalog → furniture finish → 3D textures

---

## Guiding rules

1. Decouple → Normalize → Reuse → Connect → Preserve  
2. No silent production data rewrite (legacy mode)  
3. One canonical Furniture + Component model  
4. Unified BOM (quote + manufacturing)  
5. Push/commit after each completed phase once implementation starts  
6. Samples define **format/quality expectations**, never hard-coded product data  

---

## Phase overview

| Phase | Name | Outcome |
| ----: | ---- | ------- |
| **0** | Analysis & plan gate | This document set reviewed/approved |
| **1** | Canonical furniture domain | Spec columns + `furniture_components`; room nullable; material/asset tables |
| **2** | Decouple + legacy mode | New projects furniture-first; old projects LEGACY |
| **2b** | Laminate import | Import `C:\laminates` → tenant material catalog + texture assets |
| **3** | Furniture specification UI/API | Spec/finish pickers (exterior/interior) + hardware/mfg fields |
| **4** | Component tree CRUD | Relational components; per-component `finish_id`; generator writes rows |
| **5** | Furniture 2D workspace | Views, dimensions, title block metadata |
| **6** | Furniture 3D workspace | Component meshes + **selected laminate textures** + image export |
| **7** | Validation + mfg jobs | Multi-furniture initiate manufacturing + snapshot |
| **8** | Panels + rules | Finishing/cutting sizes; edges; sheet defs |
| **9** | Unified BOM | One BOM for engineering + quotations |
| **10** | Cutlist | Full column set + filters/views |
| **11** | Nesting + visual sheets | Configurable sheets; multi-module; utilization |
| **12** | Manual nest override | Move/rotate/lock/re-optimize |
| **13** | Exports | Design PDF + manufacturing package + Excel |
| **14** | Migration tooling | Legacy convert-on-open / batch tools |
| **15** | Regression & AC | E2E CR journey + floor regression + RBAC |

---

## Phase 0 — Analysis gate (DONE)

**Objective:** Owner approves decisions + plan.  
**Exit:** Explicit `APPROVE CR-001 PLAN` — **received 2026-08-10**.  
**Next:** Phase 1 implementation.

---

## Phase 1 — Canonical furniture domain model

**Objective:** Schema foundation for furniture-first without breaking LEGACY rows.

**Dependencies:** Phase 0 approval  

**Affected tables**
- Alter `furniture_instances`: `room_id` NULL; add `code`, `category`, `type`, `quantity`, `width_mm`, `height_mm`, `depth_mm` (synced from params)
- Create `furniture_components`
- Create `tenant_manufacturing_rules`
- Create `sheet_definitions`
- Create `materials` + `material_assets`
- Alter `projects`: `model_mode` (`LEGACY`|`FURNITURE_FIRST`) default `FURNITURE_FIRST` for new

**API**
- Extend furniture GET/PUT with structured fields  
- Keep old create with room_id for legacy  

**Backend:** `FurnitureEngine` dual-write components to table when generating  
**Frontend:** minimal — show code/qty fields  
**Migration:** additive + backfill dims from JSON; existing rows `room_id` preserved; projects marked `LEGACY` if they already have furniture with rooms  
**Tests:** unit create furniture without room; legacy with room still works  
**Rollback:** keep nullable columns; feature flag off dual-write  
**Risks:** FK consumers assuming room — audit queries  

---

## Phase 2 — Decouple furniture from floor/room + dual mode

**Objective:** Runtime paths honor `model_mode`.

**Dependencies:** Phase 1  

**Changes**
- `createProject`: still auto-creates Building/Floor/Room (CRD-001) but sets `FURNITURE_FIRST`
- Existing projects: set `LEGACY` via one-time script (non-destructive)
- API: `POST /projects/{id}/furniture` without `room_id`
- UI: Projects → Furniture list primary; Designer (floor) secondary/optional
- E2E: new furniture-first journey; keep legacy test optional

**Rollback:** force room_id in API behind flag  
**Risks:** UI confusion — clear badges Legacy vs Furniture-First  

---

## Phase 2b — Laminate catalog import

**Objective:** Make `C:\laminates` usable inside FMOS (not runtime-coupled to that path).

**Dependencies:** Phase 1 material/asset schema  
**Deliverables:**
- `bin/import_laminates.php` (idempotent SKU upsert + file copy to tenant storage)
- Demo tenant seeded with imported laminates (45 SKUs if LQ-001=A)
- Catalog UI list with thumbnail + code  

**DoD:** Browse laminates; open texture URL; no dependency on `C:\laminates` after import  
**Risks:** naming ambiguity — resolve LQ-001/002/005 first  

---

## Phase 3 — Furniture specification model

**Objective:** Structured specification (CR §5).

**Tables:** construction/finish/hardware/mfg fields (columns + optional JSON extensibility); `exterior_finish_id` / `interior_finish_id`  
**API:** `PUT /furniture/{id}/specification`  
**UI:** Furniture workspace tabs — Specification with **laminate picker** (swatch + code)  
**Tests:** validation of required thickness/material before mfg; finish FK optional until mfg if LQ allows  
**Risks:** over-modeling — ship MVP field set, extensible JSON for rare attrs  

---

## Phase 4 — Component tree

**Objective:** Components are first-class, hierarchical, identifiable.

**Tables:** `furniture_components` with `parent_component_id`, type, dims, material_id, finish_id, qty, geometry_json, manufacturing_data_json, status  
**Engine:** generator upserts rows; `components_json` becomes cache/snapshot only  
**API:** CRUD `/furniture/{id}/components`  
**UI:** Components tree editor + **per-door/panel finish override**  
**Tests:** wardrobe tree integrity; delete cascades rules; component finish overrides furniture default  
**Rollback:** read path can fall back to JSON if table empty  
**Risks:** sync bugs dual-write — make table authoritative ASAP  

---

## Phase 5 — Furniture 2D integration

**Objective:** Furniture 2D independent of floor CAD.

**Views:** Plan, Front, Back, Left, Right, Section, Internal  
**Dims:** derived from component geometry (no hard-coded text)  
**Title block:** metadata fields from project/client/furniture/revision  
**API:** `GET /furniture/{id}/2d?view=`  
**UI:** Furniture workspace [2D] with view switcher  
**Export stub:** print-ready HTML (PDF in Phase 13)  
**Tests:** dimension updates when width changes  
**Risks:** drawing quality — iterate elevations after core wireframe  

---

## Phase 6 — Furniture 3D integration

**Objective:** Same canonical components → Three.js meshes **textured from selected laminates**.

**Features:** orbit/pan/zoom, standard cameras, isolation, **TextureLoader albedo maps**, roughness defaults, live update on finish change  
**Export:** PNG/JPG canvas capture (GLB later if time)  
**API:** `GET /furniture/{id}/3d-model` (mesh descriptors + `texture_url` / finish meta)  
**Tests:** component count matches tree; changing exterior finish changes door materials; missing texture falls back to colour  
**Risks:** performance on large kitchens — LOD/simple boxes first for carcass internals; UV scale for fluted grain 

---

## Phase 7 — Design validation + manufacturing jobs

**Objective:** Initiate manufacturing for one or many furniture modules.

**Tables:** `manufacturing_jobs`, `manufacturing_job_furniture`, enrich snapshots  
**API:**  
- `POST /furniture/{id}/validate`  
- `POST /projects/{id}/manufacturing` `{ furniture_ids: [] }`  
**UI:** Manufacturing workspace with checkboxes + validation checklist  
**RBAC:** preserve `manufacturing.generate` / `manufacturing.release`  
**Tests:** blocker prevents proceed; multi-select creates job  
**Risks:** partial failure across modules — transactional job create  

---

## Phase 8 — Panel generation + manufacturing rules

**Objective:** Panels with finishing vs cutting sizes; configurable sheets; edge banding.

**Rules:** `tenant_manufacturing_rules` (edge allowance, kerf default, rotation policy)  
**Sheets:** `sheet_definitions` (length/width/thickness/material/margin/kerf) — **no hard-coded 2440×1220 in engine**  
**Panels:** add `component_id`, `finishing_length/width`, `cutting_length/width`, material/finish FKs, edge_1..4 structured  
**Tests:** cutting = finishing ± rule; sheet from DB  
**Risks:** wrong default rules — seed conservative zeros + documented defaults for owner edit  

**Seed defaults (editable, not hard-coded in logic):** e.g. edge allowance 0 mm until configured; example sheet 2440×1220 as a **seed row**, not engine constant.

---

## Phase 9 — Unified BOM

**Objective:** One BOM revision model for manufacturing and quotations (CRD-002).

**Changes**
- Generate BOM from panels + hardware components after panel generation  
- Quotations reference unified `bom_revision_id` / pricing from that BOM  
- Deprecate parallel “commercial-only” component BOM path (adapter during transition)

**Tests:** quote total tracks BOM revision; mfg snapshot pins BOM revision  
**Risks:** quote regression — feature flag + dual-read during transition  

---

## Phase 10 — Cutlist

**Objective:** Professional cutlist columns (CR §28) + furniture/selected/project views.

**API:** `GET /manufacturing/{id}/cutlist?scope=`  
**UI:** filters furniture/material/thickness  
**Export stub:** CSV (Excel in Phase 13)  
**Tests:** finishing ≠ cutting columns populated  

---

## Phase 11 — Nesting / sheet optimization + visual layout

**Objective:** Optimize sheets for compatible panels across selected furniture.

**Compatibility:** material + thickness + finish + grain rules  
**Output:** sheet count, utilization, waste, placements, graphical layout  
**UI:** canvas/SVG sheet viewer with panel labels  
**Tests:** multi-furniture nest; incompatible materials not mixed  
**Risks:** algorithm quality — keep heuristic; interface for future solvers  

---

## Phase 12 — Manual nesting override

**Objective:** Move/rotate/assign/lock/reset/re-optimize with validation (no overlap / in-bounds).

**Dependencies:** Phase 11  
**Tests:** overlap rejected; locked panels preserved on re-optimize  

---

## Phase 13 — Exports

**Objective:** Design package PDF + manufacturing package (PDF/Excel) + cutlist Excel.

**Tech choice (to confirm at phase start):** Dompdf or TCPDF for PDF; CSV/XLSX via simple writer  
**Contents:** per CR §41  
**Tests:** export contains revision + furniture ids  

---

## Phase 14 — Migration / backward compatibility

**Objective:** Tools for LEGACY → FURNITURE_FIRST without data loss.

**Deliverables**
- Report: projects by mode  
- Convert-on-open optional flow  
- Batch migrate script (logged, reversible soft markers)  
- JSON → `furniture_components` backfill  

**Tests:** migrate sample legacy project; floor design still works  

---

## Phase 15 — Regression & acceptance

**Objective:** Satisfy CR §47 AC-001–029 + floor regression + RBAC/audit.

**Suites**
- Unit: dims, finishing/cutting, nest metrics, stale propagation  
- Integration: Project→Furniture→Components→2D/3D→Mfg→Panels→BOM→Cutlist→Nest  
- UI smoke: furniture-first journey  
- Regression: existing floor wall/door path  
- E2E replace/extend `tests/e2e_mvp.php` → `tests/e2e_furniture_centric.php`

**Exit:** All AC checked; owner UAT sign-off  

---

## Suggested vertical slice order (within full scope)

Even with full scope, implement **Wardrobe** end-to-end through Phase 11 before expanding categories (TV/Kitchen), mirroring prior FMOS vertical-slice discipline.

```text
Wardrobe furniture-first
  → components table
  → validate
  → panels (finishing/cutting)
  → unified BOM
  → nest visual
  → cutlist
  → then TV Unit / Kitchen templates
```

---

## Cross-cutting work each phase must include

- Migrations versioned under `database/migrations/`  
- RBAC permission checks  
- Audit events for create/update/mfg/export  
- Artifact stale status updates  
- Tests + brief phase notes in `CR/progress/`  
- Git commit + push after each phase  

---

## Rollback strategy (global)

- Additive migrations preferred  
- Feature flags: `furniture_first_ui`, `unified_bom`, `component_table_authoritative`  
- Legacy mode always available for old projects  
- Manufacturing snapshots protect historical releases  

---

## Approval checklist

Before coding Phase 1:

- [ ] CRD-001–007 confirmed (done)  
- [ ] Analysis `02_ANALYSIS_AND_GAP.md` accepted  
- [ ] This plan accepted (or revised)  
- [ ] Default manufacturing seed rules acceptable (Phase 8)  
- [ ] PDF library preference noted (or defer to Phase 13 decision)  

**Reply with:** `APPROVE CR-001 PLAN` or list plan edits.
