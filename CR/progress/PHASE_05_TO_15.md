# CR-001 Phases 5–15 Progress

**Date:** 2026-08-10  
**Status:** Implemented (wardrobe vertical slice)

## Phase 5 — Furniture 2D
- `GET /furniture/instances/{id}/2d?view=`
- Canvas view switcher in Furniture workspace
- Title block metadata; dims from live geometry

## Phase 6 — Furniture 3D + laminates
- `GET /furniture/instances/{id}/3d-model`
- Three.js meshes with TextureLoader albedo from selected finishes
- Capture 3D PNG

## Phase 7 — Validation + jobs
- `POST /furniture/instances/{id}/validate`
- `POST /projects/{id}/manufacturing` with `furniture_ids[]`
- Tables `manufacturing_jobs`, `manufacturing_job_furniture`

## Phase 8 — Panels + rules
- Finishing vs cutting sizes via `tenant_manufacturing_rules`
- Sheet from `sheet_definitions` (no hard-coded nest sheet)
- Edge 1–4 columns on panels/cutlist

## Phase 9 — Unified BOM
- BOM revision generated with manufacturing package
- Commercial generate prefers `bom_revision_id` (`source=unified_bom`)

## Phase 10 — Cutlist
- `GET /manufacturing/{id}/cutlist`
- UI table + CSV export

## Phase 11–12 — Nesting
- Visual canvas layout with margin/labels
- Manual lock placement + re-optimize preserving locks

## Phase 13 — Exports
- Cutlist CSV + design HTML (SVG) stubs (PDF library deferred)

## Phase 14 — Migration tooling
- `bin/migrate_project_mode.php --report|--project=|--backfill-components`

## Phase 15 — Regression
- `tests/e2e_furniture_centric.php` **PASSED**
- `tests/cr001_cutting_rules.php` **PASSED**

## Migration
`0010_cr001_phases7_12_mfg_panels.sql`
