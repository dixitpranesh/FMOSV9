# FMOS — Product Capability Source of Truth

**Document type:** User Guide · Feature Catalogue · Product Capability Map · Landing-Page Content Source  
**Date:** 2026-08-16  
**Scope:** Entire repository `NewFMOSPHPV2` (read-only analysis)  
**Rule:** Only capabilities verified in code/UI/API/DB are described. Planned, placeholder, and unfinished items are explicitly marked.  
**Not in scope:** Landing-page HTML/CSS/JS, marketing site implementation, or code changes.

**Evidence priority used:** Working UI + API → domain services → migrations → tests → README/REQ docs.

---

# 1. Executive Product Summary

**FMOS (Furniture Manufacturing Operating System)** is a multi-tenant SaaS web application that takes modular furniture from **project setup → parametric configuration → engineering panels → cutlist / BOM / nesting → commercial pricing**, with 2D and 3D visualization for furniture modules.

It is an **MVP (Phases 0–10)** focused on **furniture-first** workflows (especially wardrobes and kitchen bases), not a full architectural CAD suite.

| Question | Answer (evidence-based) |
|----------|-------------------------|
| What is it? | Browser-based design-to-manufacturing OS for modular furniture |
| Who is it for? | Independent designers, design firms, factory owners (registration personas); operators use RBAC roles |
| Core problem | Bridging interior-furniture design choices to manufacturable panel data and exports |
| Core workflow | Clients/Projects → Furniture modules → Customize → Validate/Generate manufacturing → Nest/Sheet plan → Export CSV/PDF → Optional commercial quote |
| Maturity | Strong furniture + manufacturing path; thin floor designer & commercial UI; no CNC/DXF/billing/AI/team invites |

---

# 2. Application Overview

| Dimension | Implementation |
|-----------|----------------|
| Product name | FMOS |
| Architecture | Modular PHP monolith + MySQL + vanilla JS SPA (`public/app.html`) |
| API | REST `/api/v1` |
| Auth | Email/password, email verification, password reset, session + Bearer token |
| Tenancy | Shared DB with `tenant_id`; registration creates tenant + MAIN organization + owner |
| UI | Hash-routed SPA (`#dashboard`, `#furniture`, …) |
| 3D | Three.js in furniture presentation and kitchen composition views |
| Stack | PHP 8.x, MySQL 8.x, no npm app package; Three.js via assets |

**Capability map (high level):**

```text
Application (FMOS)
├── Identity & Onboarding
├── Workspace (Orgs / Clients / Projects)
├── Floor Designer (thin)
├── Furniture Engineering (deep)
│   ├── Templates & instances
│   ├── Internals / layout / finishes
│   ├── Kitchen L-composition
│   └── 2D / 3D views
├── Catalog & Materials
├── Manufacturing (cutlist, nest, sheet plan, release)
├── Commercial (BOM/BOQ/price/quote APIs + thin UI)
└── Exports (CSV, design HTML, sheet-plan PDF)
```

---

# 3. Target Users & Personas

Derived from **registration types** and product workflows (not invented personas).

### Primary personas (registration)

| Persona | Code | Typical use |
|---------|------|-------------|
| Independent Interior Designer | `INDEPENDENT_DESIGNER` | Personal tenant; configure modules; generate cutlists |
| Modular Furniture Factory Owner | `FACTORY_OWNER` | Org + GST profile; manufacturing-heavy path |
| Interior Design Firm | `DESIGN_FIRM` | Org profile; multi-project design work |

### Operational personas (RBAC — many seeded; UI does not switch roles)

Seeded roles include `TENANT_OWNER`, `DESIGNER`, `ENGINEER`, `ESTIMATOR`, `MANUFACTURING_MANAGER`, `VIEWER`, platform roles, and many shop-floor roles **without** matching HTTP workflows (see §4).

### Secondary / future (permissions exist, product workflows absent)

Machine operator, QC, packing, dispatch, installation, client portal users — **not marketed as live product features**.

---

# 4. User Roles & Permissions

### Registration personas ≠ RBAC roles

Persona is stored as `users.registration_type`. Initial RBAC role for new registrations is **`TENANT_OWNER`**.

### Roles confirmed in `RbacSeeder.php`

| Role | Purpose | Capabilities (mapped) | Restrictions |
|------|---------|----------------------|--------------|
| PLATFORM_SUPER_ADMIN | Platform control | `*` | Not normal tenant user |
| SUPPORT | Support | Mapped support perms | Impersonation permission seeded; **no UI/API workflow found** |
| TENANT_OWNER | Workspace owner | Broad tenant perms (non-platform) | Bound to one tenant |
| TENANT_ADMIN | Tenant admin | Broad manage set | |
| DESIGNER / SENIOR_DESIGNER | Design work | design/furniture/project view-update | |
| ENGINEER | Engineering | manufacturing/furniture related | |
| ESTIMATOR | Pricing | bom/boq/pricing/quote | |
| SALES_* | Sales | quote/client related | |
| PROJECT_MANAGER | Projects | project/client | |
| MANUFACTURING_MANAGER | Production prep | manufacturing/nesting | |
| VIEWER | Read-only | view permissions | |
| CLIENT_ADMIN / CLIENT_USER | Client portal | Limited view (seeded) | **No client portal UI** |
| MACHINE_OPERATOR, QC_*, PACKING_*, etc. | Shop floor | **Role codes only — no route grants / no screens** | Do not market |

### Permission families in use by APIs

`organization.*`, `client.*`, `project.*`, `design.*`, `furniture.*`, `catalog.*`, `bom.*`, `boq.*`, `pricing.*`, `quote.*`, `manufacturing.*`, `nesting.*`, `user.*`, `role.*`

---

# 5. Application Navigation Map

```text
Login / Register / Verify / Forgot / Reset
        ↓
Dashboard (API health)
        ├── Organizations (list)
        ├── Clients (create + list)
        ├── Projects (create + list)
        │     ├── [Furniture] → Furniture workspace
        │     └── [Floor] → Floor Designer
        ├── Furniture
        │     ├── Template gallery → Add instance
        │     ├── Customize (Size / Internals / Finishes / Components / 2D / 3D)
        │     └── Kitchen L-shape compositions
        ├── Floor Designer (walls/doors stub)
        ├── Catalog (products / laminates)
        ├── BOM/BOQ/Price (generate + quote JSON)
        ├── Manufacturing (validate / generate / release / CSV)
        └── Nesting/Labels (sheet plan / PDF / nest / labels JSON)
```

**Evidence:** `public/assets/js/ui.js` (`renderShell`), `app.js` hash routing.

---

# 6. Complete User Journey

## Account lifecycle — **IMPLEMENTED**

1. Open app → login or `#register`
2. Select persona → personal details → (factory/firm) org/GST/address → password → terms
3. Account created as `PENDING_EMAIL_VERIFICATION`
4. Verify email via link (`#verify-email?token=`) → status `ACTIVE`
5. Login → Bearer token + CSRF stored in browser
6. Forgot/reset password available; reset revokes sessions

**Gaps:** No profile edit UI; no team invite UI; Terms/Privacy are checkboxes only (no content pages).

## Application lifecycle — **IMPLEMENTED (furniture-first)**

1. Create Client  
2. Create Project (auto Building → Floor → Living Room)  
3. Open Furniture (stores `fmos_project_id`)  
4. Add modules from templates; customize layout/finishes  
5. Optional Kitchen L composition  
6. Manufacturing: validate → generate → cutlist → release  
7. Nesting: sheet plan / PDF / nest  
8. Optional commercial generate + quote approve chain  
9. Export CSV / design HTML / sheet-plan PDF  

**Floor Designer** is optional and thin; primary value path does **not** require it.

---

# 7. Module & Feature Inventory

| Module | Purpose | Status |
|--------|---------|--------|
| Identity & Auth | Register, verify, login, reset | IMPLEMENTED |
| Organizations | List orgs; create via API/reg | PARTIAL (UI read-only) |
| Clients | Create/list | IMPLEMENTED (no edit/delete UI) |
| Projects | Create/list/open; workflow API | IMPLEMENTED (limited UI) |
| Floor Designer | Wall/door objects + simple 2D/3D | PARTIAL / EXPERIMENTAL |
| Furniture | Templates → customize → 2D/3D | IMPLEMENTED |
| Kitchen L | Multi-module composition | IMPLEMENTED (no countertops) |
| Catalog | Browse products/laminates; seed | PARTIAL (browse/seed only) |
| Materials import | CLI laminate import | BACKEND ONLY / CONFIGURATION |
| Manufacturing | Validate, cutlist, release | IMPLEMENTED |
| Nesting & sheet plan | Pack + PDF | IMPLEMENTED (simple optimizer) |
| Labels | JSON label payloads | PARTIAL |
| Commercial | BOM/BOQ/price/quote | PARTIAL (API deep, UI thin) |
| Exports | CSV, HTML, PDF | IMPLEMENTED |
| Subscriptions/Billing | — | NOT IMPLEMENTED |
| CNC/DXF | — | NOT IMPLEMENTED |
| AI assistant | — | NOT IMPLEMENTED |
| Team invites | — | NOT IMPLEMENTED |

---

# 8. Detailed Feature Documentation

## 8.1 Identity & Onboarding

**What it does:** Lets new businesses create a tenant workspace and verify email before use.  
**Why:** Secure multi-tenant onboarding without manual admin provisioning (except break-glass bootstrap).  
**Users:** All registration personas.  
**Capabilities:** Three personas; India GST/PAN validators for factory/firm; rate limits; hashed verify/reset tokens.  
**Status:** IMPLEMENTED · Confidence **HIGH**  
**Evidence:** `RegistrationService.php`, `01_identity.php`, `ui.js`, migration `0012_*`

## 8.2 Clients & Projects

**What it does:** CRM-lite: clients and projects as containers for furniture work.  
**Why:** Organize work by customer and job.  
**Outputs:** Project with default building/floor/room.  
**Status:** IMPLEMENTED · Confidence **HIGH**  
**Evidence:** `ProjectService.php`, `02_projects.php`, `pages.js`

## 8.3 Furniture Module Configuration

**What it does:** Parametric modular furniture from a template catalog with bay internals, finishes, components, and views.  
**Why:** Convert design intent into panel structures manufacturers can cut.  
**Status:** IMPLEMENTED · Confidence **HIGH**  
**Evidence:** `FurnitureEngine.php`, `FurnitureLayoutEngine.php`, `ModuleRulesEngine.php`, `furniture.js`

## 8.4 Kitchen L-Composition

**What it does:** Creates an L-shaped kitchen base layout by spawning multiple kitchen modules + corner and placing them.  
**Why:** Faster kitchen run planning than placing every carcass manually.  
**Limitation:** Countertops explicitly not included.  
**Status:** IMPLEMENTED · Confidence **HIGH**  
**Evidence:** `KitchenCompositionService.php`, `furniture.js`

## 8.5 Manufacturing Generation

**What it does:** Validates modules against sheet/material rules, generates manufacturing packages, cutlists, hardware lines, unified BOM with edge banding meters.  
**Why:** Reduce manual engineering transcription.  
**Status:** IMPLEMENTED · Confidence **HIGH**  
**Evidence:** `ManufacturingService.php`, `EdgeBandBom.php`, `manufacturing.js`

## 8.6 Nesting & Sheet Plan

**What it does:** Packs panels onto sheets (simple algorithm), builds project sheet plans by laminate group, exports PDF.  
**Why:** Preview board usage before cutting.  
**Status:** IMPLEMENTED (optimizer PARTIAL vs industrial CAM) · Confidence **MEDIUM–HIGH**  
**Evidence:** `ManufacturingService` nest, `SheetPlanService.php`, `nesting.js`

## 8.7 Commercial Pricing

**What it does:** Generates BOM→BOQ→pricing calculation and quotations with status transitions.  
**Why:** Bridge engineering to sales estimates.  
**UI limitation:** Mostly JSON dump / one-click approve.  
**Status:** PARTIAL · Confidence **MEDIUM**  
**Evidence:** `CommercialService.php`, `commercial.js`

## 8.8 Catalog & Materials

**What it does:** Product catalog (boards/hardware) and laminate materials with textures.  
**Why:** Assign real finish/board data to modules.  
**Import:** Laminates via CLI, not in-app upload.  
**Status:** PARTIAL · Confidence **HIGH** for browse; import BACKEND-ONLY  
**Evidence:** `CatalogService.php`, `MaterialService.php`, `bin/import_laminates.php`, `catalog.js`

## 8.9 Floor Designer

**What it does:** Loads first room; add wall/door objects with fixed geometries; simple 2D canvas and static 3D boxes.  
**Why:** Early architecture sketching.  
**Status:** PARTIAL / EXPERIMENTAL · Confidence **MEDIUM**  
**Evidence:** `designer.js`, `DesignService.php`

---

# 9. Detailed User Guides

## Workflow A — Register and Sign In

### Purpose
Create a verified tenant account.

### Who
New users (all personas).

### Steps
1. Open `#register` → choose persona.  
2. Enter personal details (email unique).  
3. Factory/Firm: enter legal name, constitution, GST status, address (GSTIN/PAN validated when provided).  
4. Set password (server policy: length + complexity).  
5. Accept terms → Create account.  
6. Check email / verify via `#verify-email?token=`.  
7. Sign in at `#login`.

### Common errors
Unverified login → `EMAIL_NOT_VERIFIED`; weak password; invalid GSTIN/PAN/PIN/mobile; rate limited.

---

## Workflow B — Create Client and Project

### Purpose
Establish a job container.

### Preconditions
Logged-in tenant user with `client.create` / `project.create` (owner has these).

### Steps
1. `#clients` → Name (required) + optional email/phone/company → Create.  
2. `#projects` → Name, Organization, Client → Create.  
3. System creates Building 1 → Ground Floor → Living Room.  
4. Click **Furniture** to set project context and open furniture workspace.

### Expected output
Project row with status/workflow; default room hierarchy.

---

## Workflow C — Configure Modular Furniture

### Purpose
Add and customize furniture for manufacturing.

### Preconditions
Project selected (`fmos_project_id`).

### Steps
1. `#furniture` → click template card (wardrobe, kitchen base, TV unit, etc.).  
2. Adjust size/qty/material type → instance created.  
3. **Customize** → Size & options / Internals (bays, drawers, shelves, doors, loft, fillers) / Finishes (laminates, EXPO) / Components.  
4. Review **2D Design** views; **3D Presentation** with camera/quality controls.  
5. **Save all customizations**.  
6. Optional: create **Kitchen L-shape**; open Plan/3D; send modules to manufacturing.

### Expected output
Configured `furniture_instances` with layout JSON and components; 2D/3D models.

### Common errors
Validation issues from module rules (e.g. bay width vs drawer max); missing project context; kitchen API if migrations missing.

---

## Workflow D — Manufacturing Cutlist & Export

### Purpose
Produce cuttable panel lists and CSV.

### Preconditions
Furniture instances in project; optionally kitchen modules preselected.

### Steps
1. `#manufacturing` → select furniture.  
2. **Validate selected** → review issue cards.  
3. **Generate for selected** → manufacturing job + packages + cutlist tables.  
4. **Export cutlist CSV** → download.  
5. **Release package** when ready (blocked if blockers remain).

### Expected output
Cutlist (panels + hardware), package IDs for nesting; CSV under tenant export storage.

---

## Workflow E — Nesting & Sheet Plan PDF

### Purpose
Visualize panel packing and laminate sheet usage.

### Preconditions
Manufacturing package IDs available (from generate).

### Steps
1. `#nesting` → **Build project sheet plan**.  
2. Inspect laminate groups on canvas.  
3. **Download sheet plan PDF**.  
4. Optional: nest current package; re-optimize with locks; generate labels JSON.

### Expected output
Sheet plan visualization + PDF; nesting layout; labels JSON (not sticker file).

---

## Workflow F — Commercial Generate (thin UI)

### Purpose
Create pricing calculation and advance a quote.

### Preconditions
`fmos_project_id` and `fmos_furniture_id` set.

### Steps
1. `#commercial` → **Generate Commercial**.  
2. Review JSON output (pricing id stored).  
3. **Create & Approve Quote** → creates quote then APPROVED then ACCEPTED.

### Expected output
BOM/BOQ/pricing records + quotation statuses — **no polished quote PDF UI**.

---

# 10. Design & Floor Planning

| Capability | Status | Notes |
|------------|--------|-------|
| Auto building/floor/room on project create | IMPLEMENTED | Default Living Room |
| Multi-building/floor/room UI | NOT IMPLEMENTED | Hierarchy exists in DB/API get only |
| Interactive wall drawing | NOT IMPLEMENTED | Buttons spawn fixed walls/doors |
| Furniture placement on floor plan | NOT IMPLEMENTED | Furniture is project-scoped, not floor CAD |
| Grid / zoom / pan (floor) | PARTIAL | Simple canvas scale |
| Snapping / dimensions tools | NOT IMPLEMENTED | |

**Honest summary:** Floor planning is a **stub**. Product center of gravity is **furniture module engineering**, not architectural CAD.

---

# 11. Furniture Module Management

### Template library (IMPLEMENTED)

Includes (among others): Wardrobe (hinged/sliding/loft), TV unit, Kitchen base/corner/wall/tall, Chest of drawers, Bookcase, Crockery, Vanity, Study table — via `FurnitureTemplateCatalog.php`.

### User capabilities

| Capability | Status |
|------------|--------|
| Select template & create instance | IMPLEMENTED |
| Parametric W/H/D and options | IMPLEMENTED |
| Bay layout & section types (shelves/drawers/hanging/open/mirror) | IMPLEMENTED |
| Recommend/apply internal configs & presets | IMPLEMENTED |
| Doors hinged/sliding/none; loft; plinth; fillers | IMPLEMENTED |
| Exterior/interior laminates; board material | IMPLEMENTED |
| EXPO / finish overrides on components | IMPLEMENTED |
| Component list view | IMPLEMENTED |
| Delete instance | IMPLEMENTED |
| Duplicate module UI | NOT found |
| Versioning UI | Template versioning internal; no user version browser |

---

# 12. Materials & Catalog Management

| Capability | Status | User action |
|------------|--------|-------------|
| Browse catalog products | IMPLEMENTED | Catalog → Products |
| Seed default boards/hardware | IMPLEMENTED | Seed Product Defaults |
| Browse laminates with textures | IMPLEMENTED | Catalog → Laminates |
| Import laminates | BACKEND ONLY | `php bin/import_laminates.php` |
| Assign finishes in furniture | IMPLEMENTED | Finishes tab |
| Create laminate in UI | NOT IMPLEMENTED | |
| Edge banding as BOM meters | IMPLEMENTED | Manufacturing BOM path |
| Hardware SKUs | IMPLEMENTED | Seed + BOM/cutlist hardware lines |

---

# 13. 2D Design Capabilities

| Area | Capability | Status |
|------|------------|--------|
| Furniture | FRONT/INTERNAL/PLAN/LEFT/RIGHT/BACK/SECTION drawings | IMPLEMENTED |
| Furniture | Zoom/fit/pan, export design HTML | IMPLEMENTED |
| Kitchen | Plan canvas for L composition | IMPLEMENTED |
| Floor | Line drawing of walls/doors | PARTIAL |
| Floor | Interactive edit | NOT IMPLEMENTED |

---

# 14. 3D Visualization Capabilities

| Area | Capability | Status |
|------|------------|--------|
| Furniture | Three.js model from API; orbit/camera presets; quality; person/grid/dims/room/shadows | IMPLEMENTED |
| Furniture | Presentation / fullscreen / export 4K sheet (client-side PNG) | IMPLEMENTED |
| Kitchen | Aggregated 3D | IMPLEMENTED |
| Floor designer | Static boxes, non-interactive camera | PARTIAL |

**Not claimed:** Photoreal rendering, AR, VR.

---

# 15. Engineering Capabilities

**Design input → Engineering logic → Manufacturing output**

```text
Template + parameters + layout + finishes
        ↓
FurnitureLayoutEngine (carcass, back, shelves, drawers, doors, fillers…)
        ↓
Component / panel list (sizes, materials, finishes, EXPO)
        ↓
ModuleRulesEngine validation (bay/module constraints)
        ↓
ManufacturingService validate + generate panels + hardware + BOM
        ↓
Cutlist / Nest / Sheet plan / CSV / PDF
```

| Capability | Status |
|------------|--------|
| Panel generation from layout | IMPLEMENTED |
| Rule-based internals | IMPLEMENTED |
| FMOSV2 rules bridge (imported JSON) | IMPLEMENTED (import CLI) |
| Manufacturing validation issues | IMPLEMENTED |
| CNC toolpath generation | NOT IMPLEMENTED |

---

# 16. Cutlist / BOM / BOQ

| Artifact | What user gets | Status |
|----------|----------------|--------|
| Cutlist | Panel rows (finish/cut sizes, thickness, qty, material, edges, EXPO, faces) + hardware | IMPLEMENTED |
| Cutlist CSV | Downloadable UTF-8 CSV | IMPLEMENTED |
| Unified BOM | Includes edge-band meters | IMPLEMENTED |
| BOQ | Generated with pricing | IMPLEMENTED (API) |
| Pricing calculation | Markup/tax defaults | IMPLEMENTED |
| Quotation | Status workflow | IMPLEMENTED (API); UI thin |
| Quote PDF | — | NOT IMPLEMENTED |

---

# 17. Optimization & Nesting

| Topic | Detail |
|-------|--------|
| What is optimized | Panel placement on rectangular sheets (utilization / packing) |
| Algorithm level | Simple shelf/row packer with rotation and lock/reoptimize — **not** industrial CAM optimizer |
| Inputs | Panels from manufacturing packages; sheet definitions |
| Outputs | Nest layout; project sheet plan by laminate; PDF |
| User controls | Build plan, nest package, reoptimize keeping locks |
| Limitations | Not guaranteed minimal waste; no machine-specific kerf/tooling UI marketed |

**Do not market as “AI optimization.”**

---

# 18. Manufacturing Workflow

```text
Select furniture → Validate → Generate job/packages
        → Review cutlist → (optional) Nest / Sheet plan PDF
        → Release package (when no blockers)
        → Export CSV
```

| Step | Status |
|------|--------|
| Validate | IMPLEMENTED |
| Generate | IMPLEMENTED |
| Cutlist view | IMPLEMENTED |
| Release | IMPLEMENTED |
| Labels JSON | PARTIAL |
| Production floor tracking / QC | NOT IMPLEMENTED (perms only) |

---

# 19. Export & Output Capabilities

| Output | Format | Trigger | Status | Recipient |
|--------|--------|---------|--------|-----------|
| Cutlist | CSV | Manufacturing export | IMPLEMENTED | Factory / CNC prep (manual) |
| Design drawing | HTML/SVG | Furniture 2D export | IMPLEMENTED | Designer review |
| Sheet plan | PDF | Nesting PDF | IMPLEMENTED | Production planning |
| Labels | JSON | Nesting Generate Labels | PARTIAL | Dev/ops; not print UI |
| 3D sheet | PNG (browser) | Furniture 3D export 4K | IMPLEMENTED | Presentation |
| Quote document | — | — | NOT IMPLEMENTED | |

**Not available:** DXF, CNC G-code, XLSX, label stickers PDF.

---

# 20. Validation & Business Rules (business language)

| Rule | Trigger | User impact |
|------|---------|-------------|
| Email must be verified before login | Login | Must verify or resend |
| Suspended/locked accounts cannot use API | Auth | Access revoked mid-session |
| Factory/firm require org + address | Register | Wizard blocks continue |
| GSTIN format/checksum when GST=YES | Register | Invalid GSTIN rejected; format-valid ≠ government verified |
| Password complexity | Register/reset | Weak passwords rejected |
| Module rules (bay widths, drawers, etc.) | Validate/customize | Warnings/errors before manufacturing |
| Manufacturing blockers prevent healthy release | Release | Package stays blocked |
| Tenant ownership of projects/orgs/clients | Create APIs | Cross-tenant IDs rejected |
| Panel must fit sheet constraints | Manufacturing validate | Issues listed |

---

# 21. Error Handling (user-facing)

| Situation | User experience |
|-----------|-----------------|
| Bad login | Invalid credentials |
| Unverified | Message + resend control |
| Missing permission | Access denied |
| Validation (register/furniture) | Inline/API error messages |
| Manufacturing failure | Job failed (generic outside local debug) |
| Rate limit | Too many requests |
| Missing project context | Prompt to select project |
| Kitchen without migration | Soft error message in furniture UI |

---

# 22. End-to-End Use Cases

## Use Case 1 — Modular wardrobe for a Client (P0)

1. Register/login → Create client → Create project  
2. Open Furniture → Add Wardrobe template  
3. Configure bays (hanging/shelves/drawers), doors, finishes  
4. Review 2D/3D → Save  
5. Manufacturing validate + generate → Export CSV  
6. Nesting sheet plan PDF  

**Status:** Fully supported for furniture-first path.

## Use Case 2 — L-Shaped Kitchen Bases (P0)

1. Project → Furniture → Kitchen L composer  
2. Set run lengths / module widths / preset  
3. Review plan & 3D → Use modules in manufacturing  
4. Generate cutlists per module  

**Limitation:** No countertops.

## Use Case 3 — Factory Onboarding with GST Profile (P1)

1. Register as Factory Owner with GSTIN/PAN/address  
2. Verify email → Login  
3. Proceed with projects/manufacturing  

## Use Case 4 — Quick Sales Estimate (P2)

1. Configure furniture → Commercial generate → Create/approve quote  

**Limitation:** JSON-centric; no customer-ready quote document.

## Use Case 5 — Architectural Floor Design (P3)

Floor designer walls/doors only — **not** a complete use case for marketing.

---

# 23. Feature → Persona Mapping

| Feature | Independent Designer | Design Firm | Factory Owner |
|---------|----------------------|-------------|---------------|
| Register + verify | ✓ | ✓ | ✓ |
| Clients/projects | ✓ | ✓ | ✓ |
| Furniture configure + 2D/3D | ✓ Core | ✓ Core | ✓ Core |
| Kitchen L | ✓ | ✓ | ✓ |
| Manufacturing/cutlist | ✓ | ✓ | ✓ Primary |
| Nesting/PDF | ✓ | ✓ | ✓ Primary |
| GST org profile | Optional/minimal | Required path | Required path |
| Catalog laminates | ✓ | ✓ | ✓ |
| Commercial quote | Optional | Optional | Optional |
| Floor designer | Minor | Minor | Minor |

**Shared across personas:** Furniture-to-cutlist pipeline is the shared product core.

---

# 24. Feature Dependency Map

```text
Tenant + User (verified)
    ↓
Organization (MAIN)
    ↓
Client → Project → (Building → Floor → Room)
    ↓
Furniture Template → Instance → Layout/Components/Finishes
    ↓                    ↓
    Kitchen Composition ──┘ (optional aggregator)
    ↓
Manufacturing Package (panels, hardware, BOM)
    ↓
Cutlist CSV ←→ Nesting / Sheet Plan PDF
    ↓
Commercial BOM/BOQ/Pricing/Quotation (optional)
```

Catalog/materials feed finishes and board selection into furniture and manufacturing.

---

# 25. Product Differentiators

| Differentiator | Supporting functionality | Evidence | User value | Confidence |
|----------------|--------------------------|----------|------------|------------|
| Design-to-manufacturing in one product | Configure → validate → cutlist → nest | Furniture + Manufacturing domains | Fewer handoffs | **HIGH** |
| Parametric modular furniture with bay internals | Layout engine + rules | `FurnitureLayoutEngine`, `ModuleRulesEngine` | Faster accurate configs | **HIGH** |
| Furniture 2D + interactive 3D presentation | View service + Three.js UI | `FurnitureViewService`, `furniture.js` | Client presentation | **HIGH** |
| Kitchen L composition to manufacturable modules | KitchenCompositionService | kitchen APIs + UI | Faster kitchen runs | **MEDIUM–HIGH** |
| Sheet plan PDF by laminate | SheetPlanService | nesting PDF route | Production planning aid | **MEDIUM–HIGH** |
| Full architectural CAD | — | Floor designer stub | — | **LOW** (not a differentiator) |
| CNC-ready machine files | — | Absent | — | **N/A — do not claim** |
| AI design | — | Absent | — | **N/A — do not claim** |

---

# 26. Landing Page Feature Inventory

### Category 1 — Core Product
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Multi-tenant workspaces | Each registration gets isolated tenant | Secure business separation | Implemented | P0 |
| Client & project management | Organize jobs by client/project | Clear job structure | Implemented | P0 |

### Category 2 — Design
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Floor designer | Basic walls/doors sketch | Early room context | Partial | P3 |

### Category 3 — Furniture Engineering
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Template library | Ready modular types | Fast start | Implemented | P0 |
| Parametric configuration | Sizes, bays, doors, loft, fillers | Accurate modules | Implemented | P0 |
| Internals rules & presets | Recommend/validate configs | Fewer engineering mistakes | Implemented | P0 |
| Kitchen L composer | Multi-module L layout | Faster kitchens | Implemented | P0 |

### Category 4 — Materials
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Laminate catalog browse | Textures + codes | Realistic finishes | Implemented | P1 |
| Board/hardware catalog | Seedable products | Manufacturing materials | Implemented | P1 |

### Category 5 — 3D Visualization
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Furniture 3D presentation | Interactive Three.js view | Stakeholder buy-in | Implemented | P0 |
| 2D technical views | Multi-view drawings | Shop/design review | Implemented | P0 |

### Category 6 — Manufacturing
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Validation | Constraint checks | Catch issues early | Implemented | P0 |
| Cutlist generation | Panels + hardware | Production-ready list | Implemented | P0 |
| Package release | Controlled release state | Process discipline | Implemented | P1 |

### Category 7 — Optimization
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Nesting + sheet plan PDF | Panel packing preview | Material planning | Implemented (simple) | P1 |

### Category 8 — Automation / AI
| — | None implemented | — | Not implemented | Exclude |

### Category 9 — Collaboration / Project Management
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| RBAC foundation | Roles/permissions | Future team control | Partial (no invite UI) | P2 |

### Category 10 — Reporting / Export
| Feature | Short description | Benefit | Status | Priority |
|---------|-------------------|---------|--------|----------|
| Cutlist CSV | Spreadsheet export | Factory handoff | Implemented | P0 |
| Sheet plan PDF | Printable plan | Shop floor | Implemented | P1 |
| Design HTML | Drawing export | Review share | Implemented | P2 |
| Commercial quote docs | — | — | Partial/missing PDF | P2 |

---

# 27. Marketing-Ready Feature Descriptions (P0/P1)

## Parametric Furniture Configurator
- **Technical:** Template-driven instances with layout engine producing panel components and rule validation.  
- **User-friendly:** Choose a wardrobe or kitchen module, set sizes and internals, and the system builds the parts list behind the scenes.  
- **Marketing:** Configure modular furniture once—and keep design aligned with what can be manufactured.

## Design-to-Cutlist Pipeline
- **Technical:** ManufacturingService validates furniture, generates packages/panels/hardware, exports CSV.  
- **User-friendly:** After configuring a module, generate a cutlist you can download for production.  
- **Marketing:** Move from configuration to a production cutlist without retyping sizes into spreadsheets.

## Furniture 2D & 3D Views
- **Technical:** Server-generated drawing/model JSON rendered in canvas/Three.js.  
- **User-friendly:** Inspect front/plan/section drawings and walk around a 3D preview.  
- **Marketing:** Show clients clear 2D drawings and interactive 3D presentations of the same module.

## Kitchen L-Shape Composer
- **Technical:** Spawns kitchen instances + corner, places them, aggregates views; manufacturing remains per module.  
- **User-friendly:** Build an L kitchen run from lengths and module widths, then send modules to manufacturing.  
- **Marketing:** Compose L-shaped kitchen bases faster, then generate manufacturing data per unit.  
- **Caveat:** Countertops not included.

## Nesting & Sheet Plan PDF
- **Technical:** Simple packer + laminate-grouped sheet plan PDF.  
- **User-friendly:** See how panels sit on boards and download a sheet plan PDF.  
- **Marketing:** Preview board layouts and share a sheet plan with production.  
- **Caveat:** Not an industrial CAM optimizer.

## Secure Business Onboarding
- **Technical:** Persona registration, email verification, org GST/PAN validation for firms/factories.  
- **User-friendly:** Sign up as designer, firm, or factory; verify email; start in your own workspace.  
- **Marketing:** Get a dedicated workspace suited to how your business operates.

---

# 28. Product Benefits (evidence-backed)

1. **One workspace from configuration to cutlist** — reduces spreadsheet re-entry.  
2. **Parametric modules** — faster iteration on wardrobes/kitchens.  
3. **Visual confirmation** — 2D + 3D before manufacturing.  
4. **Manufacturing validation** — surface constraint issues early.  
5. **Exportable production artifacts** — CSV cutlist and sheet-plan PDF.  
6. **Business-type onboarding** — designer vs firm vs factory paths.  
7. **Material-aware finishes** — laminate/board selection tied to modules.

**Do not claim** quantified time savings, waste % reduction, or CNC automation without measured evidence.

---

# 29. Current Product Gaps & Limitations

| Gap | Impact |
|-----|--------|
| Floor designer is a stub | Do not market as full CAD/floor planning |
| Commercial UI is JSON-centric | Not sales-document ready |
| No quote PDF / CNC / DXF | Factory still needs other tools for machines |
| No team invite / user-mgmt UI | Multi-user orgs limited despite RBAC seed |
| No subscription/billing | Not a billed SaaS console yet |
| No AI features | Do not claim AI |
| Laminate import is CLI | Non-technical users need ops help |
| Labels are JSON only | No print label workflow |
| Many RBAC shop-floor roles unused | Permissions ≠ product modules |
| Dashboard is health-only | Not an operations dashboard |
| Countertops missing in kitchen L | Explicit UI limitation |
| Conflict: register UI may say min 8 chars while server policy is stronger | Prefer server rules |

---

# 30. Internal / Non-Marketing Features

| Item | Why exclude from marketing |
|------|----------------------------|
| `BOOTSTRAP_SECRET` tenant bootstrap API | Ops break-glass |
| `bin/rbac_seed.php`, migrate, import CLIs | Developer/ops |
| `MAIL_DRIVER=log` / debug tokens | Local testing |
| `<pre>` JSON dumps in commercial/mfg/nesting | Debug-oriented UX |
| Platform impersonation permission (unused) | Internal |
| Security rate-limit files, audit internals | Infrastructure |
| FMOSV2 JSON import scripts | Content ops |
| Test suites | Engineering |

---

# 31. Feature Status & Confidence Matrix

| Feature | Status | Confidence | Evidence |
|---------|--------|------------|----------|
| Registration + email verify | Implemented | High | Identity services + UI |
| Login / reset | Implemented | High | Auth + UI |
| Clients / projects | Implemented | High | ProjectService + pages.js |
| Furniture templates & customize | Implemented | High | Furniture domain + furniture.js |
| Module rules / internals | Implemented | High | ModuleRulesEngine APIs |
| Kitchen L | Implemented | High | KitchenCompositionService + UI |
| Furniture 2D/3D | Implemented | High | ViewService + Three.js UI |
| Manufacturing cutlist + CSV | Implemented | High | ManufacturingService + ExportService |
| Nesting + sheet plan PDF | Implemented | Medium–High | Simple packer + PDF |
| Catalog browse + seed | Implemented | High | catalog.js + CatalogService |
| Laminate import | Backend only | High | CLI only |
| Commercial BOM/BOQ/quote | Partial | Medium | Strong API, thin UI |
| Floor designer | Partial | Medium | designer.js stub |
| Labels print | Partial | Low–Medium | JSON only |
| CNC/DXF | Not implemented | High (absence) | No exporters |
| AI | Not implemented | High (absence) | No services |
| Billing | Not implemented | High (absence) | No domain |
| Team invites | Not implemented | High (absence) | No invite APIs |

---

# 32. Evidence / Codebase Traceability (selected)

| Capability | Evidence |
|------------|----------|
| SPA shell / nav | `public/app.html`, `public/assets/js/ui.js`, `app.js` |
| Auth UI | `public/assets/js/ui.js` |
| Auth API | `src/Http/Routes/01_identity.php`, `src/Core/Auth.php`, `src/Domains/Identity/*` |
| Projects | `src/Http/Routes/02_projects.php`, `src/Domains/Project/ProjectService.php`, `pages.js` |
| Furniture UI | `public/assets/js/furniture.js` |
| Furniture engine | `src/Domains/Furniture/FurnitureEngine.php`, `FurnitureLayoutEngine.php`, `ModuleRulesEngine.php` |
| Kitchen | `KitchenCompositionService.php`, migration `0011_kitchen_compositions.sql` |
| Design objects | `DesignService.php`, `designer.js` |
| Catalog/materials | `CatalogService.php`, `MaterialService.php`, `catalog.js`, `bin/import_laminates.php` |
| Manufacturing | `ManufacturingService.php`, `manufacturing.js`, migrations `0008`–`0010` |
| Nesting/PDF | `SheetPlanService.php`, `nesting.js`, `Support/SimplePdf.php` |
| Commercial | `CommercialService.php`, `commercial.js`, migration `0007` |
| Export | `ExportService.php` |
| RBAC | `RbacSeeder.php` |
| Demo seed | `bin/seed.php` |

---

# 33. Recommended Landing Page Content Source

> **Do not build the landing page here.** Use this as copy input only.

## Recommended Hero Value Proposition
**Configure modular furniture and generate manufacturing-ready cutlists—from one workspace.**

## Core Product Benefits
1. Parametric modular configuration  
2. Design-to-cutlist continuity  
3. 2D drawings + 3D presentation  
4. Manufacturing validation before release  
5. Nesting / sheet-plan PDF for planning  
6. Workspaces for designers, firms, and factories  

## Primary Feature Groups
Furniture Engineering · Manufacturing Prep · Visualization · Materials Catalog · Project Workspace · Secure Onboarding  

## Key Differentiators (claim carefully)
Furniture-first design-to-manufacturing; parametric bay internals; kitchen L composer; sheet plan PDF.  
**Avoid:** AI, CNC automation, full architectural CAD, guaranteed waste savings.

## Target Personas
Primary: Independent designers, design firms, modular factory owners.  
Secondary (future): Shop-floor roles (not live product today).

## Major Use Cases
Wardrobe configuration → cutlist; Kitchen L bases → manufacturing; Factory onboarding with business profile.

## Product Workflow
Sign up → Project → Configure furniture → Validate/Generate → Nest/PDF → Export CSV → (Optional) Quote  

## Key Outputs
Cutlist CSV · Sheet plan PDF · Design HTML · 3D presentation · Pricing/quotation records  

## Trust/Proof Points (only if you choose to show)
- Open demo after seed (local)  
- India-oriented GSTIN/PAN format checks for firm/factory signup  
- Email verification before access  
**Do not invent** customer counts, certifications, or compliance seals.

---

# 34. Final Product Capability Summary

**FMOS is a furniture-manufacturing-oriented SaaS MVP** that helps designers and factories configure modular furniture, visualize it in 2D/3D, validate it for production, and produce cutlists, nesting previews, and sheet-plan PDFs—with optional commercial pricing APIs.

It is **strongest** in the **furniture → engineering panels → manufacturing exports** chain.  
It is **weakest** as a general CAD floor planner, sales quoting suite, CNC post-processor, collaboration suite, or AI product.

Use this document as the **single source of truth** for landing-page messaging. Anything not marked **Implemented** with **High** confidence should be omitted or clearly framed as limited/coming later.

---

*End of Product Capability Source of Truth.*
