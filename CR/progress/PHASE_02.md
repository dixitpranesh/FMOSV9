# CR-001 Phase 2 Progress

**Date:** 2026-08-10  
**Status:** Implemented

## Delivered

- New projects created as `FURNITURE_FIRST` (still auto Building/Floor/Room per CRD-001)
- Existing room-linked projects already marked `LEGACY` in Phase 1 migration
- `POST /api/v1/projects/{id}/furniture` — create furniture without requiring `room_id`
- UI: Furniture is primary nav/project open action; Floor Designer secondary
- Projects list shows `model_mode` badge

## Verification

- Phase 1 test already covers create-without-room
- UI smoke: Projects → Furniture (no room required on furniture-first)
