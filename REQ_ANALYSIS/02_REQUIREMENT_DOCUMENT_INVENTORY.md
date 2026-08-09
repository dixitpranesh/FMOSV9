# 02 — Requirement Document Inventory (Phase 1)

**Analysis Date:** 2026-08-10  
**Corpus:** `/REQ` (24 documents)

---

## Inventory Table

| Document ID | File | Title | Document Type | Scope | Modules | Version | Date | Dependencies |
| ----------- | ---- | ----- | ------------- | ----- | ------- | ------- | ---- | ------------ |
| REQ-DOC-001 | `Product_Vision_and_Scope_Interior_Design_Manufacturing_Platform.md` | Product Vision & Scope | Product Requirements | Platform-wide vision, MVP, OOS | All capability areas | 1.0 | 2026-08-09 | None (root) |
| REQ-DOC-002 | `System_Architecture_Interior_Design_Manufacturing_Platform.md` | System Architecture & Technical Architecture | Other (Architecture) | Modular monolith, domains, tech stack | Platform, engines, storage | 1.0 | 2026-08-09 | REQ-DOC-001 |
| REQ-DOC-003 | `BRD_Interior_Design_Parametric_Furniture_Manufacturing_Platform.md` | Business Requirements Document (BRD) | Business Requirements | End-to-end business capabilities | All business modules | 1.0 | 2026-08-09 | REQ-DOC-001, REQ-DOC-002 |
| REQ-DOC-004 | `SRS_Interior_Design_Parametric_Furniture_Manufacturing_MES_Platform.md` | Software Requirements Specification (SRS) | Software Requirements Specification | Software-level FR/NFR | All domains | 1.0 | 2026-08-09 | REQ-DOC-001–003 |
| REQ-DOC-005 | `Database_Specification_Interior_Design_Parametric_Furniture_Manufacturing_MES.md` | Database Specification Document | Database Specification | MySQL logical/physical schema | All persisted domains | 1.0 | 2026-08-09 | REQ-DOC-004 |
| REQ-DOC-006 | `API_Specification_Interior_Design_Parametric_Furniture_Manufacturing_MES.md` | API Specification Document | API Specification | REST `/api/v1` | All API modules | 1.0 | 2026-08-09 | REQ-DOC-004, REQ-DOC-005 |
| REQ-DOC-007 | `RBAC_Permission_Matrix_Interior_Design_Manufacturing_MES.md` | RBAC & Permission Matrix Specification | RBAC Specification | Roles, permissions, isolation | Identity, all modules | 1.0 | 2026-08-10 | REQ-DOC-004 |
| REQ-DOC-008 | `2D_CAD_Specification_Interior_Design_Manufacturing_MES.md` | 2D CAD Specification | Functional Specification | 2D CAD engine | Architecture / 2D | 1.0 | 2026-08-10 | REQ-DOC-004 |
| REQ-DOC-009 | `3D_BIM_Specification_Interior_Design_Manufacturing_MES.md` | 3D / BIM Specification | Functional Specification | 3D/BIM subsystem | Architecture / 3D | 1.0 | 2026-08-10 | REQ-DOC-004, REQ-DOC-008 |
| REQ-DOC-010 | `Parametric_Furniture_Engine_Specification.md` | Parametric Furniture Engine Specification | Functional Specification | Parametric furniture + component rules | Furniture | 1.0 | 2026-08-10 | REQ-DOC-004 |
| REQ-DOC-011 | `Material_and_Catalog_Specification.md` | Material and Catalog Specification | Functional Specification | Catalogs, materials, pricing of materials | Catalog | 1.0 | 2026-08-10 | REQ-DOC-004 |
| REQ-DOC-012 | `BOM_BOQ_Specification_Interior_Design_Manufacturing_MES.md` | BOM / BOQ Specification | Functional Specification | BOM/BOQ generation & commercial lines | BOM, BOQ | 1.0 | 2026-08-10 | REQ-DOC-010, REQ-DOC-011 |
| REQ-DOC-013 | `Pricing_Engine_Specification_Interior_Design_Manufacturing_MES.md` | Pricing Engine Specification | Functional Specification | Dual pricing, rates, margins | Pricing | 1.0 | 2026-08-10 | REQ-DOC-012 |
| REQ-DOC-014 | `Manufacturing_Engine_Specification_Interior_Design_Manufacturing_MES.md` | Manufacturing Engine Specification | Functional Specification | Panel decomp, cutlist, release | Manufacturing | 1.0 | 2026-08-10 | REQ-DOC-010, REQ-DOC-012 |
| REQ-DOC-015 | `Nesting_Engine_Specification_Interior_Design_Manufacturing_MES.md` | Nesting Engine Specification | Functional Specification | Sheet optimization | Nesting | 1.0 | 2026-08-10 | REQ-DOC-014 |
| REQ-DOC-016 | `CNC_CAM_Engine_Specification_Interior_Design_Manufacturing_MES.md` | CNC / CAM Engine Specification | Functional Specification | Machine-neutral CNC + adapters | CNC/CAM | 1.0 | 2026-08-10 | REQ-DOC-014, REQ-DOC-015 |
| REQ-DOC-017 | `MES_Specification_Interior_Design_Manufacturing.md` | MES Specification | Functional Specification | Factory execution | MES | 1.0 | 2026-08-10 | REQ-DOC-014–016 |
| REQ-DOC-018 | `QR_Panel_Tracking_Specification_FMOS.md` | QR / Panel Tracking Specification | Functional Specification | Panel identity & scanning | MES / Traceability | 1.0 | 2026-08-10 | REQ-DOC-014, REQ-DOC-017 |
| REQ-DOC-019 | `AI_Specification_FMOS.md` | AI Specification | Functional Specification | AI gateway & assistants | AI | 1.0 | 2026-08-10 | REQ-DOC-001, REQ-DOC-004 |
| REQ-DOC-020 | `White_Label_SaaS_Specification_FMOS.md` | White-Label / SaaS Specification | Functional Specification | Multi-tenant SaaS + branding | Platform / SaaS | 1.0 | 2026-08-10 | REQ-DOC-001, REQ-DOC-007 |
| REQ-DOC-021 | `FMOS_UI_UX_Screen_Specification.md` | FMOS UI/UX Screen Specification | UI Specification / UX Specification | Screen inventory & UX | All UI modules | 1.0 | 2026-08-10 | REQ-DOC-004 |
| REQ-DOC-022 | `FMOS_User_Stories_and_Acceptance_Criteria.md` | FMOS User Stories and Acceptance Criteria | User Stories / Acceptance Criteria | Story-level AC | All modules | 1.0 | 2026-08-10 | REQ-DOC-003, REQ-DOC-004 |
| REQ-DOC-023 | `FMOS_Security_Specification.md` | FMOS Security Specification | Security Specification | AuthN/Z, isolation, controls | Cross-cutting | 1.0 | 2026-08-10 | REQ-DOC-007 |
| REQ-DOC-024 | `FMOS_Test_Strategy_and_Quality_Engineering_Specification.md` | FMOS Test Strategy and Quality Engineering Specification | Test Strategy | Test types, coverage expectations | Cross-cutting | 1.0 | 2026-08-10 | REQ-DOC-004, REQ-DOC-022 |

---

## Document Type Distribution

| Type | Count |
| ---- | ----: |
| Product Requirements | 1 |
| Business Requirements | 1 |
| Software Requirements Specification | 1 |
| Architecture | 1 |
| Database Specification | 1 |
| API Specification | 1 |
| RBAC Specification | 1 |
| Functional Specification (engines/domains) | 12 |
| UI/UX Specification | 1 |
| User Stories / AC | 1 |
| Security Specification | 1 |
| Test Strategy | 1 |

---

## Naming / Product Identity Note

Early documents (REQ-DOC-001–016) use the long product title without consistently branding **FMOS**. Later documents (REQ-DOC-018–024) brand the product as **FMOS**. Treated as the same product unless otherwise decided (see terminology TERM register).

---

## Authority Hierarchy (Observed, Not Yet Approved)

Observed intended reading order from Product Vision §58 / BRD §110:

```text
Vision → Architecture → BRD → SRS → Database → API → RBAC → Domain Specs → UI → Stories → Test → Security
```

**Conflict:** Specialized specs (AI, White Label, Nesting, MES, QR, CNC) declare `Status: Implementation Baseline` equal to core docs, while Vision/BRD/SRS assign them P1–P3. This is escalated in the Decision Register.
