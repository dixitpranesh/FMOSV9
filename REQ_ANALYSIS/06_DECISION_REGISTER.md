# 06 — Decision Register (Phase 8)

**Status:** PARTIAL — some decisions recorded; CRITICAL items still open  
**Created:** 2026-08-10  
**Updated:** 2026-08-10  
**Rule:** CRITICAL and HIGH conflicts must be decided by the product owner. No silent resolution.

---

## How to Use This Register

1. Review each conflict below (or the corresponding DECISION REQUIRED blocks in chat).
2. Reply with: `CONF-xxx = Option A | Option B | Custom: …`
3. Decisions will be recorded as authoritative and used for `07_MASTER_REQUIREMENTS.md` onward.

---

## Conflict Summary Table

| Conflict ID | Severity | Topic | Source A | Source B | Conflict | Impact | Decision Required |
| ----------- | -------- | ----- | -------- | -------- | -------- | ------ | ----------------- |
| CONF-SCOPE-001 | CRITICAL | MVP boundary — nesting / QR / production | Vision §47 P1 | Vision §49 MVP journey + BRD §69 MVP | Same features listed as P1 and as MVP success criteria | Defines what Phase 1 engineers build | Choose MVP Option A–D |
| CONF-SCOPE-002 | CRITICAL | Specialized specs vs priority | Vision/BRD/SRS P1–P3 | AI/WL/MES/QR/CNC/Nesting/UI all “Implementation Baseline” | Unclear what is in first build | Scope creep / wrong sequencing | Confirm baseline authority |
| CONF-SCOPE-003 | CRITICAL | SRS “MVP Implementation Sequence” includes Phases 8–10 | SRS §127 title | SRS §130 priorities (AI=P3, MES=P1) | MVP label applied to full roadmap | Mis-sized delivery plan | Define which phases = MVP |
| CONF-LIFE-001 | CRITICAL | Dual project status models | SRS-PROJECT-002 coarse statuses | SRS §69 workflow FSM | One entity, two incompatible enums | Schema + UI + APIs blocked | Dual fields vs single FSM |
| CONF-LIFE-002 | CRITICAL | Panel lifecycle order/states | MES §53 (NESTED before RELEASED) | QR §39 (RELEASED before NESTED); Mfg Engine SHIPPED | Shop-floor FSM cannot be implemented once | MES/QR/DB blocked | Pick canonical panel FSM |
| CONF-LIFE-003 | CRITICAL | Intra-MES panel FSM contradiction | MES §53 full lifecycle | MES §195 abbreviated machine | Same doc contradicts itself | Implementers will guess | Declare normative section |
| CONF-RBAC-001 | CRITICAL | Canonical role catalog | Vision/BRD ~18 personas | User Stories 22; RBAC 27 coded roles | No single role model | AuthZ, UI, stories misaligned | Approve role catalog |
| CONF-RBAC-002 | CRITICAL | Who can `manufacturing.release` | Security §25 Engr has release | RBAC: Engineer —; Mfg Manager ✓; Stories: Production Planner | Conflicting authorization | Security risk / blocked release UX | Name releasing role(s) |
| CONF-RBAC-003 | CRITICAL | Platform admin identity | Platform Super Admin / Super Admin / Platform Admin / PLATFORM_SUPER_ADMIN | Same | Ambiguous platform identity | SaaS admin APIs blocked | One canonical name + scope |
| CONF-DATA-001 | CRITICAL | Organization entity | SRS §81 / BRD §12 include `organizations` | DB + API have no Organization | Hierarchy undefined | Tenant model / isolation | Org real, synonym, or remove |
| CONF-DATA-002 | CRITICAL | BOM/BOQ revision persistence | DB flat `version` on headers | BOM/BOQ spec header + `*_revisions` tables | Incompatible schemas | Commercial + mfg integrity | Pick persistence model |
| CONF-PRICE-001 | CRITICAL | Target margin formula | Pricing §23 text `1 - Target Margin%` | Same section example uses 0.20 for 20% | Selling price math ambiguous | Wrong money / finance risk | Confirm formula + units |
| CONF-SCOPE-004 | HIGH | CNC in MVP? | Vision adapters P2 | BRD adapters P1; SRS Phase 7 P1; CNC baseline | CNC timing unclear | Factory integrations | MVP CNC depth |
| CONF-SCOPE-005 | HIGH | White Label timing | Vision/BRD/SRS P1 | Arch Phase 9 “Advanced”; WL full baseline | Branding vs tenant core | SaaS go-to-market | What WL ships when |
| CONF-SCOPE-006 | HIGH | AI timing | Vision/BRD/SRS P3 | AI spec + UI treat as baseline | AI in/out of early UI | Architecture & cost | AI deferred or scaffold only |
| CONF-SCOPE-007 | HIGH | UI 64 screens vs MVP | UIUX “implement at least” 01–64 | Vision/BRD MVP subset | Overbuilt UI backlog | Frontend waste | Cap MVP screens |
| CONF-LIFE-004 | HIGH | Work order status enums | MES §15 QUEUED/IN_PROGRESS… | Mfg Engine §75 PLANNED/STARTED… | Dual WO models | MES APIs | Unify or scope-split |
| CONF-LIFE-005 | HIGH | Mfg package vs production order statuses | Mfg Engine §10 | MES §8 / §193 | Overlapping RELEASED vocab | State machine bugs | Distinct prefixed enums |
| CONF-LIFE-006 | HIGH | Production stage taxonomy | SRS-MES-002 | Mfg Engine §88 FINISHING/QUALITY/SHIPPING | Stage name mismatch | Routing config | Alias table + owner |
| CONF-LIFE-007 | HIGH | Catalog Product Status vs Lifecycle | Material §10 | Material §101 / BRD §29 | Two status systems | Catalog UX/API | Dual fields or merge |
| CONF-LIFE-008 | HIGH | Manufacturing release preconditions | SRS-MFG-RELEASE-001 | MES §10 (nesting/CNC/routing required) | Soft vs hard release | Factory gate | Two gates or one |
| CONF-LIFE-009 | HIGH | Quotation ACCEPTED vs APPROVED | SRS-QUOTE-002 | Pricing “accepted quotation” | Terminal commercial state | Quotes → jobs | Add ACCEPTED or synonym |
| CONF-RBAC-004 | HIGH | Support Administrator | User Stories / Security distinct | RBAC folds into platform admin | Impersonation identity | Audit/security | Distinct role? |
| CONF-RBAC-005 | HIGH | Business Owner vs TENANT_OWNER | Vision/BRD Business Owner | RBAC TENANT_OWNER only | Highest tenant role unclear | Tenant bootstrap | Mapping decision |
| CONF-RBAC-006 | HIGH | Client portal roles | Single Client persona | CLIENT_ADMIN / CLIENT_USER | Who can approve | Portal ACLs | Split or single |
| CONF-RBAC-007 | HIGH | Tenant hierarchy | Vision Tenant→Organization→Project | WL Tenant→BU→Branch/Factory | Org chart model | Data model | Pick hierarchy |
| CONF-DATA-003 | HIGH | Catalog identity | DB/API `materials` | Material `catalog_products` | FK targets differ | Schema | Canonical catalog entity |
| CONF-PRICE-002 | HIGH | Pricing stack order | Pricing Purpose §1 | Waterfall §24 / §25 | Tax/discount position | Invoice totals | Default waterfall |
| CONF-PRICE-003 | HIGH | Markup vs margin vs additive ₹ | Pricing §21–23 | BOM §89–90 | Different selling constructions | Quotes wrong | Default commercial mode |
| CONF-PRICE-004 | HIGH | Area unit sq.ft vs m² | BOM/BRD/DB SQFT | Pricing Engine m²; Material UOM no SQ_FT | Rate unit mismatch | Estimations | Default area UOM |
| CONF-DATA-004 | HIGH | 2D/3D Y-axis | 2D §4.1 Y=vertical | 3D §185 CAD Y=depth | Coordinate sync | Geometry bugs | Lock axis convention |
| CONF-DATA-005 | HIGH | BOM vs BOQ pipeline | BOM §2 sequential | BOM §4 siblings | Engine orchestration | Event flow | Sequential vs parallel |
| CONF-TERM-001 | HIGH | Customer vs Client vs Tenant | Multiple docs | Multiple docs | Overloaded terms | CRM + SaaS confusion | Glossary freeze |
| CONF-TECH-001 | HIGH | Frontend framework / Tailwind | Vision: no framework | UI: CSS/Tailwind | Stack ambiguity | FE architecture | Approve FE stack |
| CONF-TECH-002 | HIGH | Mobile app OOS vs shop-floor mobile | Vision §48 OOS full mobile | UI/QR mobile-tablet workflows | Delivery channel | MES UX | Responsive web vs app |

---

## Decision Log

| Decision ID | Conflict ID | Decision | Rationale | Date | Affected Areas |
| ----------- | ----------- | -------- | --------- | ---- | -------------- |
| DEC-001 | CONF-LIFE-001 | **Option A** — Project has two orthogonal fields: `status` (`DRAFT`, `ACTIVE`, `ON_HOLD`, `COMPLETED`, `ARCHIVED`) and `workflow_stage` (SRS §69 FSM; tenant-configurable). | Owner choice 2026-08-10 | Project schema, APIs, UI, reports |
| DEC-002 | CONF-LIFE-002 | **Option A** — QR panel FSM is canonical; publish synonym/alias map (`SHIPPED` ≡ `DISPATCHED`, etc.). Order: **RELEASED before NESTED** (per QR §39). | Owner chose Option A; QR order is part of Option A | Panel status, MES, QR, DB enums |
| DEC-003 | CONF-LIFE-003 | MES §53 must align to QR canonical FSM; MES §195 is an **abbreviated non-normative example** only. | Follows CONF-LIFE-002 Option A | MES spec interpretation |
| DEC-004 | CONF-DATA-001 | **Option A** — Organization is a **real** tier: `Tenant → Organization → Project`. Must be added to Database + API. | Owner choice 2026-08-10 | Tenant model, isolation, admin UI, APIs |
| DEC-005 | CONF-RBAC-007 | Adopt Vision hierarchy with Organization (not White-Label BU/Branch as primary). Factory remains configurable under Org/Tenant as needed, not a replacement for Organization. | Paired with DEC-004 | Hierarchy, RBAC scope |
| DEC-006 | CONF-PRICE-002 | Default pricing waterfall: **Cost → Markup/Target Margin → Gross Selling Price → Discount → Tax → Final Price**. Alternate sequences may exist as tenant config later; default is this waterfall. | Owner: “Waterfall” 2026-08-10 | Pricing engine, quotes, BOQ |
| DEC-007 | CONF-TECH-001 | Frontend = **Vanilla JavaScript ES6+ + HTML5 + CSS**. No React/Vue/Angular. **No Tailwind** unless later approved (owner specified CSS, not Tailwind). | Owner choice 2026-08-10 | UI architecture, tooling |
| DEC-008 | CONF-TECH-002 | Shop-floor / mobile-tablet = **responsive web** (same vanilla stack), **not** a native mobile application. Aligns with Vision OOS “full mobile application.” | Inferred from vanilla web stack + Vision OOS; owner grouped with TECH-001 | MES/QR UX |
| DEC-009 | CONF-SCOPE-001 | **Option B — Design-to-cut MVP:** Auth/tenant/RBAC, project hierarchy, 2D/3D, parametric furniture foundation, materials, BOM/BOQ/pricing, cutlist, revision, **basic nesting**, panel IDs/labels, manufacturing snapshot/export. **Out of MVP:** shop-floor MES execution, QC/pack/dispatch, CNC machine adapters, white-label polish beyond tenant core, AI features. | Owner choice 2026-08-10 | Roadmap, backlog, UI screen cap |
| DEC-010 | CONF-SCOPE-002 | Priority authority: **Vision/BRD/SRS** (as constrained by DEC-009) govern what ships when. Specialized specs (AI, WL, MES, QR, CNC, Nesting, UI) are **detailed design for approved scope only** — “Implementation Baseline” status does not override MVP cut. Nesting/QR specs apply only to the MVP subset (basic nesting + panel labels/IDs). | Follows DEC-009 | Spec interpretation |
| DEC-011 | CONF-SCOPE-003 | SRS §127 “MVP Implementation Sequence” is reinterpreted as **overall delivery roadmap**, not MVP definition. **MVP = DEC-009 Option B** (approx. SRS Phases 1–6 + basic nesting + panel labels; not Phases 7–10 shop-floor/WL/AI). | Follows DEC-009 | Implementation phases |
| DEC-012 | CONF-RBAC-001 | Canonical MVP role catalog = **RBAC matrix 27 coded roles** (`PLATFORM_SUPER_ADMIN` … `VIEWER`). Vision/BRD/User Stories personas are **aliases** mapped to these codes. | Owner answered “1” (question 1) 2026-08-10 | RBAC, stories, UI |
| DEC-013 | CONF-DATA-002 | BOM/BOQ persistence = **header + immutable revision tables** (`bom_headers`/`bom_revisions`, `boq_headers`/`boq_revisions`). Flat `version`-only model in DB spec is superseded. | Owner choice 2026-08-10 | Database, API, commercial integrity |
| DEC-014 | CONF-RBAC-002 | `manufacturing.release` held by **`MANUFACTURING_MANAGER` only** by default. `ENGINEER` may validate/generate manufacturing data but **must not** release. Security §25 example granting release to Manufacturing Engineer is **superseded**. | Owner Option A 2026-08-10 | RBAC matrix, release APIs, UI |
| DEC-015 | CONF-RBAC-003 | Canonical platform admin = **`PLATFORM_SUPER_ADMIN`**. **Support is a distinct platform role** with `platform.support.impersonate` (time-limited, audited). Aliases (Platform Super Admin, Super Admin, Platform Admin) map to `PLATFORM_SUPER_ADMIN`. | Owner Option A 2026-08-10 | Platform admin, support, audit |
| DEC-016 | CONF-RBAC-004 | Resolved by DEC-015 — Support is distinct (not merely a permission on platform admin). | Follows DEC-015 | Support identity |
| DEC-017 | CONF-PRICE-001 | Target margin formula = **`Selling Price = Cost / (1 - m/100)`** where `m` is percent points (20% → 0.20). Markup remains `Cost × (1 + m/100)`. Pricing §23 text ambiguity resolved in favor of the worked example. | Owner Option A 2026-08-10 | Pricing engine, tests |

### CRITICAL status

| Conflict ID | Status |
| ----------- | ------ |
| CONF-SCOPE-001/002/003 | **DECIDED** (DEC-009–011) |
| CONF-LIFE-001/002/003 | **DECIDED** (DEC-001–003) |
| CONF-RBAC-001/002/003 | **DECIDED** (DEC-012, 014–015) |
| CONF-DATA-001/002 | **DECIDED** (DEC-004, 013) |
| CONF-PRICE-001 | **DECIDED** (DEC-017) |

**All CRITICAL conflicts are decided.**

### Still OPEN (HIGH — batch confirmation requested)

| Conflict ID | Severity | Proposed default (for owner confirm) |
| ----------- | -------- | ------------------------------------ |
| CONF-PRICE-003 | HIGH | Default commercial mode = **markup %** (margin available as tenant option) |
| CONF-PRICE-004 | HIGH | Default area UOM = **sq.ft** (m² supported via conversion) |
| CONF-SCOPE-004 | HIGH | CNC adapters = **post-MVP (P1)**; optional generic DXF export only if needed for cut proof — no machine adapters in MVP |
| CONF-SCOPE-005 | HIGH | White-label polish/custom domains = **P1**; tenant isolation + basic branding fields allowed in foundation |
| CONF-SCOPE-006 | HIGH | AI = **P3 / out of MVP**; no AI screens in MVP UI |
| CONF-SCOPE-007 | HIGH | Cap MVP UI to screens required by DEC-009 only |
| CONF-LIFE-004 | HIGH | MES owns execution WO statuses; Mfg Engine planning statuses scoped separately / renamed |
| CONF-LIFE-005 | HIGH | Distinct prefixed enums: `MFG_*` vs `PO_*` |
| CONF-LIFE-006 | HIGH | SRS-MES-002 as job rollup; factory routing may use aliases (QUALITY→QC, SHIPPING→DISPATCH) |
| CONF-LIFE-007 | HIGH | Dual catalog fields: publish lifecycle + commercial availability; designers use **PUBLISHED** by default |
| CONF-LIFE-008 | HIGH | **Two gates:** soft `MANUFACTURING_READY` (SRS) vs hard `PRODUCTION_ORDER_RELEASE` (MES; post-MVP) |
| CONF-LIFE-009 | HIGH | Add **ACCEPTED** after APPROVED for client-accepted quotations |
| CONF-RBAC-005 | HIGH | **Business Owner = TENANT_OWNER** alias |
| CONF-RBAC-006 | HIGH | Keep **CLIENT_ADMIN / CLIENT_USER** split; only CLIENT_ADMIN approves by default |
| CONF-DATA-003 | HIGH | Canonical catalog = **`catalog_products`** (+ typed children); `materials` treated as compatibility alias/view |
| CONF-DATA-004 | HIGH | Lock: CAD Y = **plan depth**; 3D Y = **elevation**; CAD Y → 3D Z |
| CONF-DATA-005 | HIGH | Pipeline **sequential** for MVP: Design → BOM → BOQ → Pricing (engines may share quantity services) |
| CONF-TERM-001 | HIGH | Glossary: Tenant=SaaS buyer org; Client=CRM end customer; Customer=avoid or =Client in CRM |

---

## Gate Status

```text
[x] Conflicts detected
[x] CRITICAL/HIGH documented
[x] All CRITICAL decisions recorded (DEC-001–017)
[~] HIGH decisions — awaiting batch confirm
[ ] Master requirements baseline authorized
[ ] Implementation architecture authorized
```

**STOP softened:** CRITICAL gate cleared. Blueprint Phase 9+ proceeds after HIGH batch confirm (or explicit “accept all proposed HIGH defaults”).
