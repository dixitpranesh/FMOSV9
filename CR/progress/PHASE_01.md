# CR-001 Phase 1 Progress

**Date:** 2026-08-10  
**Status:** Implemented

## Delivered

- Migration `0009_cr001_phase1_furniture_canonical.sql`
  - `projects.model_mode` (`LEGACY` | `FURNITURE_FIRST`)
  - `furniture_instances.room_id` nullable + code/category/type/qty/dims/finish FKs
  - `furniture_components`, `materials`, `material_assets`
  - `tenant_manufacturing_rules`, `sheet_definitions` (+ seed defaults)
- `FurnitureEngine`: create without room; dual-write components; sheet from DB
- `MaterialService` + `GET /api/v1/materials`
- Furniture UI: code/qty + mode badge; no required room for furniture-first
- Test: `php tests/cr001_phase1.php`

## Notes

- Existing projects with room-linked furniture marked `LEGACY` by migration backfill
- New projects default `FURNITURE_FIRST` while still auto-creating Building/Floor/Room (CRD-001)
