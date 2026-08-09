# 00 — Executive Summary (Interim — Decision Gate)

**Product:** FMOS — Design-to-Manufacturing Operating System for Interior & Modular Furniture  
**Analysis Date:** 2026-08-10  
**Gate Status:** **CRITICAL CLEARED** — awaiting HIGH batch confirm, then Phases 9–37

---

## 1. Product Understanding

FMOS is a multi-tenant SaaS platform that connects:

**Architectural / Interior Design → Parametric Furniture → BOM/BOQ/Pricing → Manufacturing Engineering → Nesting → CNC/CAM → MES → QR Traceability → QC → Packing → Dispatch**

through one authoritative parametric project model (“Design Once → Generate Everything”).

Technology baseline stated across core docs: **PHP 8.x + MySQL 8.x + JavaScript ES6+ + HTML5 + Three.js + REST `/api/v1`**, modular monolith with future engine extraction.

---

## 2. Major Modules (Derived)

1. Platform Administration / Multi-Tenancy / White-Label  
2. Identity, Auth, RBAC  
3. CRM & Sales  
4. Project / Building / Floor / Room  
5. 2D CAD  
6. 3D / BIM-like Spatial Model  
7. Interior Design  
8. Parametric Furniture Engine + Component Designer  
9. Materials & Catalog  
10. BOM  
11. BOQ  
12. Pricing Engine  
13. Quotations & Proposals  
14. Engineering Validation  
15. Manufacturing Engine (panels, cutlist, edge, hardware)  
16. Nesting  
17. CNC/CAM Adapters  
18. MES / Production  
19. QR Panel Tracking  
20. QC / Packing / Dispatch / Installation  
21. Documents & Revisions  
22. Notifications & Audit  
23. AI Services (deferred priority disputed)  
24. Reporting & Analytics  

---

## 3. User Types (Unresolved — see CONF-RBAC-*)

Documents disagree. Approximate families:

- Platform: Super Admin / Support  
- Tenant: Owner / Admin / Business Owner  
- Commercial: Sales Manager / Sales / Estimator  
- Design: Architect / Designer / Design Lead / Reviewer  
- Engineering: Design Engineer / Manufacturing Engineer / CNC Programmer  
- Factory: Manufacturing Manager / Production Manager / Supervisor / Operators / QC / Packing / Dispatch  
- External: Client Admin / Client User  

Canonical catalog not yet approved.

---

## 4. Requirement Statistics (Interim)

| Metric | Value |
| ------ | ----- |
| Documents analyzed | **24** |
| Requirements extracted (full REQ-xxx catalog) | **In progress — gated** |
| Approximate distinct requirement statements (corpus) | **1,500+** (estimated from SRS IDs + domain MUST/SHALL density) |
| Duplicate / overlap clusters identified | **Many** (Vision↔BRD↔SRS↔domain specs) |
| Conflicts registered | **35+** material conflicts |
| Critical conflicts | **12** |
| High conflicts | **23+** |
| Ambiguous requirements | Material (status models, MVP, roles, pricing math) |
| Missing requirements | Org entity definition; NFR numeric SLAs largely absent; ACCEPTED quote state |
| Implementation-ready requirements | **Not yet baselined** (awaiting decisions) |

---

## 5. Major Product Decisions Required

See `06_DECISION_REGISTER.md` and chat DECISION REQUIRED blocks. Top blockers:

1. MVP boundary (nesting / QR / CNC / MES / WL / AI)  
2. Project + panel status models  
3. Canonical RBAC role set + manufacturing.release holder  
4. Organization / Tenant hierarchy  
5. BOM revision schema + pricing margin formula  
6. Frontend stack (vanilla vs Tailwind; mobile = responsive web?)  

---

## 6. Recommended Implementation Strategy

See **`24_IMPLEMENTATION_ROADMAP.md`**.

**MVP (Option B):** Phases **0–10** (~design-to-cut).  
**Post-MVP:** Phases **11–14** (factory, white-label, advanced CNC, AI).

Working assumption: HIGH defaults in Decision Register §OPEN used for planning until owner confirms.

---

## Next Step

Product owner answers CRITICAL/HIGH decisions → Decision Recording (Phase 9) → Master Requirements Baseline (Phase 10) → full blueprint (Phases 11–37).
