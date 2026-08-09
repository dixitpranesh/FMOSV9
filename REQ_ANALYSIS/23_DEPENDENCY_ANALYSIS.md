# 23 — Implementation Dependency Analysis

**Date:** 2026-08-10  
**Aligned to:** `24_IMPLEMENTATION_ROADMAP.md` · DEC-009 Option B

---

## 1. Hard Dependencies (must precede)

| Capability | Depends on |
| ---------- | ---------- |
| Organization | Tenant |
| Users / RBAC | Tenant (+ Support/Platform roles) |
| Client / Lead | Tenant, Org, Users |
| Project | Tenant, Org, Client, Users |
| Building/Floor/Room | Project |
| 2D design objects | Room, Project revision |
| 3D view | Domain design objects (not independent data) |
| Catalog assign | Catalog products published |
| Furniture instance | Room, Template version, Catalog (materials) |
| BOM revision | Furniture/design revision, Catalog versions |
| BOQ revision | BOM revision (MVP sequential) |
| Pricing calculation | BOQ + pricing version/rules |
| Quotation | Pricing snapshot + Client + Project |
| Engineering validation | Furniture instance + rules + materials |
| Soft manufacturing release | Validation pass, BOM, panels/cutlist, `MANUFACTURING_MANAGER` |
| Nesting | Released/generated panels + sheet catalog |
| Panel labels | Panel IDs from manufacturing package |
| MES production order (P1) | Soft/hard release snapshot |
| QR scan transitions (P1) | Panel instances + canonical FSM |
| CNC adapters (P2) | Nesting + machine-neutral CNC model |
| AI floorplan (P3) | 2D domain model + review UX |

---

## 2. Soft Dependencies (can stub)

| Capability | Can start with stub | Later replace |
| ---------- | ------------------- | ------------- |
| PDF quotation | HTML print | Full PDF service |
| Nesting algorithm | Simple shelf packing heuristic | Advanced engine |
| File storage | Local disk | S3-compatible |
| Auth session | PHP session | Token/JWT if needed |
| Notifications | In-app only | Email provider |
| Background jobs | Sync for small projects | Queue worker |

---

## 3. Parallelization Opportunities

```text
After Phase 2 (Project hierarchy):
  Track A: Phase 3–4 Design (2D/3D)
  Track B: Phase 5 Catalog
  Merge at Phase 6 Furniture (needs both)

After Phase 6:
  Track C: Phase 7 Commercial
  Track D: Phase 8 Engineering panels (can start validation earlier)
  Merge before Phase 8 release + Phase 9 nesting
```

---

## 4. Critical Path (MVP)

```text
Foundation → Identity/Tenant/Org/RBAC → Project hierarchy
 → 2D → 3D → Catalog → Parametric Wardrobe
 → BOM/BOQ/Price/Quote → Engineering/Cutlist/Release
 → Basic Nesting + Labels → Hardening/UAT
```

Anything that delays **Wardrobe parametric correctness** or **tenant isolation** sits on the critical path.

---

## 5. Anti-Patterns (do not)

- Build MES UI before manufacturing snapshots exist  
- Build all furniture categories before commercial/manufacturing rails  
- Put pricing formulas in frontend  
- Use Three.js / Canvas as source of truth  
- Implement CNC adapters before neutral manufacturing model  
- Wire AI into release path  
