# FMOS — User Stories & Acceptance Criteria Specification

**Document ID:** USAC-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Product:** FMOS — End-to-End Interior Design, Furniture Engineering, Manufacturing & MES SaaS Platform  
**Technology:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + Canvas/SVG + Three.js/WebGL  
**Audience:** Cursor, Product, UX, Engineering, QA, DevOps, Implementation Teams  
**Date:** 2026-08-10

---

# 1. Purpose

This document translates the FMOS product requirements into implementation-ready:

- Epics
- Features
- User stories
- Acceptance criteria
- Negative scenarios
- Permission expectations
- Dependencies
- Definition of Done

Cursor must use this document together with the FMOS:

```text
Product Vision & Scope
System Architecture
BRD
SRS
Functional Specification
Database Specification
API Specification
RBAC & Permission Matrix
2D CAD Specification
3D/BIM Specification
Parametric Furniture Engine Specification
Material & Catalog Specification
BOM/BOQ Specification
Pricing Engine Specification
Manufacturing Engine Specification
Nesting Specification
CNC/CAM Specification
MES Specification
QR/Panel Tracking Specification
AI Specification
White-Label/SaaS Specification
UI/UX Screen Specification
```

This document is the behavioral contract for implementation and testing.

---

# 2. Story Format

Every story follows:

```text
As a [persona]
I want [capability]
So that [business value]
```

Each story includes acceptance criteria in Given/When/Then form.

---

# 3. Core Personas

## 3.1 Platform Super Admin

Manages the FMOS SaaS platform.

## 3.2 Tenant Admin

Manages a customer's FMOS environment.

## 3.3 Business Owner

Views commercial and operational performance.

## 3.4 Sales Manager

Manages leads, customers, opportunities and quotations.

## 3.5 Sales Executive

Creates customers, projects and commercial proposals.

## 3.6 Interior Designer

Creates architectural and interior designs.

## 3.7 Design Lead

Reviews and approves design work.

## 3.8 CAD Engineer

Creates and validates 2D/3D engineering output.

## 3.9 Furniture Engineer

Creates parametric furniture and manufacturing-ready components.

## 3.10 Estimator

Creates BOM/BOQ and pricing.

## 3.11 Production Planner

Converts approved designs into production plans.

## 3.12 Manufacturing Engineer

Validates panels, operations, nesting and machine outputs.

## 3.13 CNC Programmer

Creates and validates CNC/CAM programs.

## 3.14 Factory Manager

Manages factory operations and production.

## 3.15 Machine Operator

Executes assigned machine operations.

## 3.16 Shop-Floor Operator

Executes manufacturing stages and scans panels.

## 3.17 Quality Inspector

Performs inspections and manages defects/rework.

## 3.18 Packing Operator

Verifies and packs finished components.

## 3.19 Dispatch Coordinator

Manages dispatch.

## 3.20 Client

Reviews designs, quotes and approvals.

## 3.21 Consultant / Multi-Tenant User

May access multiple authorized tenants.

## 3.22 Support Administrator

Provides controlled support/impersonation.

---

# 4. Global Acceptance Principles

Every implementation must satisfy:

1. Server-side authorization is authoritative.
2. Tenant isolation is enforced server-side.
3. User-visible actions must respect RBAC.
4. All critical changes must be auditable.
5. Destructive operations require confirmation or undo where appropriate.
6. Financial values use decimal-safe calculations.
7. Manufacturing outputs require deterministic validation.
8. AI suggestions do not silently commit high-impact changes.
9. Revisions are preserved where applicable.
10. Background jobs are asynchronous for long-running operations.
11. Errors must be actionable.
12. APIs must validate all input.
13. Frontend validation never replaces backend validation.
14. Every tenant-owned resource is tenant-scoped.
15. Every critical workflow must be recoverable after failure.

---

# 5. Epic E01 — Authentication & Tenant Access

## US-E01-001 — Tenant Login

**As a tenant user, I want to log into my organization's FMOS environment so that I can access authorized functionality.**

### Acceptance Criteria

- Given a valid tenant domain and valid credentials, when the user logs in, then the system authenticates the user and resolves the correct tenant.
- Given invalid credentials, when login is attempted, then access is denied without revealing sensitive information.
- Given a suspended tenant, when login is attempted, then the system applies the tenant suspension policy.
- Given an unauthorized tenant membership, when login is attempted, then tenant access is denied.
- Given a successful login, then the user receives only permissions belonging to the current tenant context.

---

## US-E01-002 — Tenant-Branded Login

**As a tenant admin, I want the login page to display my branding so that the platform feels like our own application.**

### Acceptance Criteria

- Given tenant branding is configured, when the login page loads, then the tenant logo is displayed.
- The configured application name is displayed.
- Configured colors are applied.
- No other tenant's branding is displayed.
- If branding is unavailable, a safe platform fallback is used.

---

## US-E01-003 — Password Reset

**As a user, I want to reset my password so that I can regain access.**

### Acceptance Criteria

- User can request reset using registered email.
- Reset token is time-limited.
- Reset token is single-use.
- Tenant branding is applied to reset communication.
- Password is not exposed in logs.

---

## US-E01-004 — SSO Login

**As an enterprise tenant user, I want to authenticate using SSO so that I can use corporate identity management.**

### Acceptance Criteria

- Tenant can configure supported SSO provider.
- User is redirected to the configured identity provider.
- Successful authentication creates the correct tenant session.
- Unauthorized domains cannot use the tenant's SSO configuration.
- SSO configuration secrets are protected.

---

# 6. Epic E02 — Tenant & SaaS Administration

## US-E02-001 — Create Tenant

**As a platform admin, I want to create a tenant so that a new customer can use FMOS.**

### Acceptance Criteria

- Tenant receives immutable internal ID.
- Tenant slug is unique.
- Default configuration is created.
- Default roles are provisioned.
- Tenant admin membership is created.
- Default branding is created.
- Provisioning is idempotent.
- Provisioning failure does not leave an unusable partial tenant.

---

## US-E02-002 — Tenant Onboarding

**As a tenant admin, I want a setup wizard so that I can configure my FMOS environment.**

### Acceptance Criteria

Wizard supports:

```text
Organization
Branding
Workspace
Factory
Catalog
Users
Preferences
Finish
```

Each completed step is persisted.

The user can resume later.

---

## US-E02-003 — Tenant Branding

**As a tenant admin, I want to customize the application branding.**

### Acceptance Criteria

Tenant can configure:

```text
logo
favicon
application name
primary color
secondary color
accent color
login image
email footer
document footer
```

Changes apply to permitted tenant-facing surfaces.

---

## US-E02-004 — Custom Domain

**As a tenant admin, I want to use a custom domain so that users access FMOS through our brand.**

### Acceptance Criteria

- Admin can add a domain.
- System generates verification instructions.
- Domain ownership can be verified.
- Unverified domains cannot become primary.
- Verified domain can be activated.
- Domain cannot belong to another tenant.
- DNS/SSL status is visible.

---

## US-E02-005 — Tenant Feature Entitlement

**As a tenant admin, I want only entitled features available so that our subscription is enforced.**

### Acceptance Criteria

- Feature access is evaluated server-side.
- Disabled feature cannot be used through direct API calls.
- Frontend hides or disables unavailable actions.
- Existing data is preserved after downgrade.
- Upgrade activates newly entitled features.

---

## US-E02-006 — Tenant Suspension

**As a platform admin, I want to suspend a tenant so that platform access can be controlled.**

### Acceptance Criteria

- Tenant status becomes suspended.
- Configured write operations are blocked.
- Data remains intact.
- Users receive an appropriate message.
- Suspension is audited.

---

# 7. Epic E03 — User, Roles & Permissions

## US-E03-001 — Invite User

**As a tenant admin, I want to invite a user so that they can join our organization.**

### Acceptance Criteria

- Admin enters email and role.
- Invitation is tenant-scoped.
- Invitation expires.
- User cannot accept invitation into a different tenant.
- Invitation is branded.
- Acceptance creates tenant membership.

---

## US-E03-002 — Assign Role

**As a tenant admin, I want to assign roles so that users receive appropriate access.**

### Acceptance Criteria

- Role must belong to authorized scope.
- Permission changes take effect after authorization refresh.
- User cannot grant themselves permissions.
- Role assignment is audited.

---

## US-E03-003 — Factory Access

**As a tenant admin, I want to restrict users to specific factories so that factory data remains controlled.**

### Acceptance Criteria

- User can be assigned one or more factories.
- Factory-scoped APIs enforce membership.
- Unauthorized factory data is not returned.
- Reports respect factory scope.

---

## US-E03-004 — Support Impersonation

**As a support administrator, I want controlled impersonation so that I can troubleshoot customer issues.**

### Acceptance Criteria

- Impersonation requires explicit permission.
- Reason is mandatory.
- Session has expiry.
- UI clearly displays impersonation state.
- All actions are audited.
- Support can exit impersonation.

---

# 8. Epic E04 — CRM & Customers

## US-E04-001 — Create Customer

**As a sales executive, I want to create a customer so that I can associate projects and quotes with them.**

### Acceptance Criteria

Customer record supports:

```text
name
contact
email
phone
address
tax information
notes
```

Required fields are validated.

---

## US-E04-002 — Customer Search

**As a sales user, I want to search customers so that I can quickly find existing records.**

### Acceptance Criteria

Search supports:

```text
name
email
phone
code
```

Only authorized tenant records are returned.

---

## US-E04-003 — Customer Project History

**As a sales user, I want to view customer project history so that I understand previous engagements.**

### Acceptance Criteria

User can view authorized:

```text
projects
quotes
approvals
documents
activity
```

---

# 9. Epic E05 — Project Management

## US-E05-001 — Create Project

**As a sales or design user, I want to create a project so that I can manage a customer's interior engagement.**

### Acceptance Criteria

Project supports:

```text
project name
customer
type
address
designer
factory
currency
units
due date
```

Project receives unique identifier.

---

## US-E05-002 — Project Context

**As a user, I want project context available throughout FMOS so that I don't repeatedly select the project.**

### Acceptance Criteria

Project context persists across authorized module navigation.

Current:

```text
project
floor
room
revision
```

is visible where relevant.

---

## US-E05-003 — Project Revision

**As a design lead, I want revisions so that approved design history is preserved.**

### Acceptance Criteria

- New revision can be created.
- Revision number is unique within project.
- Previous revision remains accessible.
- Approval references exact revision.
- Manufacturing package references revision.

---

# 10. Epic E06 — Building & Architectural Design

## US-E06-001 — Create Building

**As a designer, I want to create a building so that I can structure the project spatially.**

### Acceptance Criteria

Building supports:

```text
name
address
levels
default units
```

---

## US-E06-002 — Create Floor

**As a designer, I want to create floors so that spaces are organized vertically.**

### Acceptance Criteria

Floor supports:

```text
name
level
height
```

and belongs to project/building.

---

## US-E06-003 — Create Room

**As a designer, I want to create rooms so that I can design individual spaces.**

### Acceptance Criteria

Room supports:

```text
name
type
dimensions
floor
```

Room appears in project navigation.

---

## US-E06-004 — Place Wall

**As a designer, I want to draw walls so that I can create a floor plan.**

### Acceptance Criteria

Wall supports:

```text
length
thickness
height
material
```

Wall snaps to configured grid where enabled.

---

## US-E06-005 — Place Door

**As a designer, I want to place doors so that openings are represented correctly.**

### Acceptance Criteria

Door supports:

```text
width
height
frame
swing
direction
```

Door updates associated views.

---

## US-E06-006 — Place Window

**As a designer, I want to place windows so that architectural openings are represented.**

### Acceptance Criteria

Window supports:

```text
width
height
sill height
frame
```

---

## US-E06-007 — Place Structural Element

**As a designer, I want to place beams and columns so that furniture design respects physical constraints.**

### Acceptance Criteria

Structural elements appear in:

```text
2D
3D
elevation
```

and affect collision/placement rules where configured.

---

# 11. Epic E07 — 2D CAD

## US-E07-001 — Open 2D CAD

**As a designer, I want a dedicated 2D CAD workspace so that I can create precise plans.**

### Acceptance Criteria

Workspace provides:

```text
canvas
toolbar
layers
rulers
grid
inspector
status bar
```

---

## US-E07-002 — Zoom and Pan

**As a designer, I want to zoom and pan so that I can work at different scales.**

### Acceptance Criteria

Supports:

```text
mouse wheel
trackpad
keyboard shortcuts
zoom controls
fit view
```

---

## US-E07-003 — Snap

**As a designer, I want snapping so that geometry aligns precisely.**

### Acceptance Criteria

Supports configurable:

```text
grid snap
endpoint snap
midpoint snap
intersection snap
object snap
```

---

## US-E07-004 — Dimension

**As a designer, I want to add dimensions so that drawings communicate exact measurements.**

### Acceptance Criteria

Dimension remains associated with geometry when supported.

---

## US-E07-005 — Undo/Redo

**As a designer, I want undo/redo so that I can safely experiment.**

### Acceptance Criteria

Undo reverses supported design actions.

Redo restores undone actions.

History is scoped to the active document/session.

---

## US-E07-006 — CAD Save

**As a designer, I want changes saved so that design work is not lost.**

### Acceptance Criteria

- Manual save supported.
- Autosave supported where enabled.
- Save state visible.
- Save errors actionable.
- Concurrent edit conflicts are detected where supported.

---

# 12. Epic E08 — 3D/BIM

## US-E08-001 — Generate 3D from Plan

**As a designer, I want the 3D model to reflect the 2D plan so that I can visualize the design.**

### Acceptance Criteria

Changes to supported 2D architectural objects propagate to 3D.

---

## US-E08-002 — 3D Navigation

**As a designer, I want orbit, pan, zoom and standard views so that I can inspect the model.**

### Acceptance Criteria

Support:

```text
orbit
pan
zoom
top
front
left
right
isometric
fit
```

---

## US-E08-003 — Linked Selection

**As a designer, I want selecting an object in 3D to select it in 2D so that I can work across views.**

### Acceptance Criteria

Selection synchronizes across configured views.

---

## US-E08-004 — Material Visualization

**As a designer, I want assigned materials visible in 3D so that I can review finishes.**

### Acceptance Criteria

Material assignment changes 3D appearance.

---

# 13. Epic E09 — Parametric Furniture

## US-E09-001 — Browse Furniture Catalog

**As a designer, I want to browse furniture templates so that I can quickly design interiors.**

### Acceptance Criteria

Catalog supports:

```text
search
filter
preview
dimensions
category
```

---

## US-E09-002 — Place Furniture

**As a designer, I want to drag furniture into a room so that I can build the interior.**

### Acceptance Criteria

Placement supports:

```text
position
rotation
dimensions
```

and validates configured constraints.

---

## US-E09-003 — Edit Furniture Parameters

**As a designer, I want to change dimensions so that furniture fits the space.**

### Acceptance Criteria

Changing parameters updates:

```text
geometry
components
BOM
pricing
```

where applicable.

---

## US-E09-004 — Create Parametric Cabinet

**As a furniture engineer, I want to create a parametric cabinet so that the organization can reuse engineered designs.**

### Acceptance Criteria

Designer can define:

```text
carcass
panels
shelves
drawers
shutters
hardware
edge banding
```

---

## US-E09-005 — Parameter Validation

**As a furniture engineer, I want invalid combinations rejected so that furniture remains manufacturable.**

### Acceptance Criteria

System identifies:

```text
dimension violation
hardware incompatibility
minimum thickness violation
clearance violation
```

and prevents release where configured.

---

## US-E09-006 — Furniture Template Versioning

**As a catalog administrator, I want versions so that changes do not unexpectedly affect existing projects.**

### Acceptance Criteria

Existing project instances retain their effective revision.

---

# 14. Epic E10 — Materials & Catalog

## US-E10-001 — Create Material

**As a catalog admin, I want to create materials so that designers can use approved materials.**

### Acceptance Criteria

Material supports:

```text
code
name
type
thickness
supplier
price
image
status
```

---

## US-E10-002 — Import Catalog

**As a catalog admin, I want to bulk import materials so that large catalogs can be onboarded efficiently.**

### Acceptance Criteria

Import supports:

```text
upload
column mapping
validation
preview
commit
error report
```

---

## US-E10-003 — Assign Material

**As a designer, I want to assign a material to a component so that the design reflects actual production materials.**

### Acceptance Criteria

Assignment updates:

```text
3D
BOM
pricing
manufacturing
```

where applicable.

---

## US-E10-004 — Hardware Compatibility

**As a furniture engineer, I want hardware compatibility rules so that invalid hardware is prevented.**

### Acceptance Criteria

System validates hardware against supported component types and dimensions.

---

# 15. Epic E11 — BOM / BOQ

## US-E11-001 — Generate BOM

**As an engineer, I want FMOS to generate a BOM so that all required components are known.**

### Acceptance Criteria

BOM includes:

```text
panels
materials
hardware
edge banding
accessories
```

according to configured rules.

---

## US-E11-002 — Regenerate BOM

**As an engineer, I want to regenerate BOM after design changes so that quantities remain current.**

### Acceptance Criteria

Regeneration replaces/revises derived values without corrupting manually maintained metadata.

---

## US-E11-003 — Generate BOQ

**As an estimator, I want a BOQ so that I can price the project.**

### Acceptance Criteria

BOQ includes:

```text
item
quantity
unit
rate
amount
tax
total
```

---

## US-E11-004 — BOM Revision

**As an engineer, I want BOM revisions linked to design revisions.**

### Acceptance Criteria

BOM identifies:

```text
source design revision
generation timestamp
generation status
```

---

# 16. Epic E12 — Pricing

## US-E12-001 — Raw Material Pricing

**As an estimator, I want pricing based on material consumption so that quotes reflect actual usage.**

### Acceptance Criteria

Pricing can use:

```text
square area
linear length
piece quantity
hardware quantity
```

---

## US-E12-002 — Panel Pricing

**As a manufacturer, I want fixed panel rates so that factory pricing can be applied.**

### Acceptance Criteria

Panel pricing supports configurable:

```text
panel type
dimensions/range
unit rate
```

---

## US-E12-003 — Hybrid Pricing

**As an estimator, I want hybrid pricing so that different items can use different pricing methods.**

### Acceptance Criteria

System clearly identifies calculation source.

---

## US-E12-004 — Margin

**As a business owner, I want margin configuration so that quotes reflect commercial targets.**

### Acceptance Criteria

Margin is applied according to configured scope and permissions.

---

## US-E12-005 — Pricing Explanation

**As a user, I want to understand price changes so that I can explain them to stakeholders.**

### Acceptance Criteria

System can show calculation breakdown.

---

# 17. Epic E13 — Quotation & Client Approval

## US-E13-001 — Create Quote

**As a sales user, I want to create a quote from the project so that I can send a commercial proposal.**

### Acceptance Criteria

Quote pulls authorized:

```text
BOQ
pricing
customer
project
tax
```

---

## US-E13-002 — Quote Revision

**As a sales user, I want quote revisions so that commercial history is preserved.**

### Acceptance Criteria

Each revision is uniquely identifiable.

---

## US-E13-003 — Send Quote

**As a sales user, I want to send a branded quote so that the customer can review it.**

### Acceptance Criteria

Quote uses tenant branding.

---

## US-E13-004 — Client Approval

**As a client, I want to approve a design/quote online so that work can proceed.**

### Acceptance Criteria

Client can:

```text
approve
request changes
reject
comment
```

Approval is linked to exact revision.

---

## US-E13-005 — Approval Lock

**As a project manager, I want approved revisions protected so that production does not accidentally use unapproved changes.**

### Acceptance Criteria

Approved revision is visibly locked.

Changes after approval require new revision or authorized workflow.

---

# 18. Epic E14 — Manufacturing

## US-E14-001 — Release Production

**As a production planner, I want to release an approved project to manufacturing so that factory work can begin.**

### Acceptance Criteria

Release validation checks:

```text
design approved
BOM valid
materials valid
manufacturing rules valid
```

Release is audited.

---

## US-E14-002 — Create Production Order

**As a production planner, I want a production order generated from approved design data.**

### Acceptance Criteria

Production order references:

```text
project
design revision
BOM revision
factory
```

---

## US-E14-003 — Generate Panels

**As a manufacturing engineer, I want furniture converted into panel records so that manufacturing can execute it.**

### Acceptance Criteria

Each panel has:

```text
unique ID
dimensions
material
edge banding
operations
source component
```

---

## US-E14-004 — Manufacturing Validation

**As a manufacturing engineer, I want manufacturing rules validated before release.**

### Acceptance Criteria

Validation identifies:

```text
invalid dimensions
unsupported material
missing hardware
machine incompatibility
missing operation
```

---

# 19. Epic E15 — Nesting

## US-E15-001 — Create Nesting Job

**As a production planner, I want to create a nesting job so that panels are arranged efficiently on sheets.**

### Acceptance Criteria

Job contains:

```text
panel set
material
sheet type
optimization parameters
```

---

## US-E15-002 — Optimize Sheets

**As a manufacturing engineer, I want sheet utilization optimized so that waste is minimized.**

### Acceptance Criteria

Result includes:

```text
sheet count
utilization
waste
panel placements
```

---

## US-E15-003 — Review Nesting

**As a manufacturing engineer, I want to visually review nesting before release.**

### Acceptance Criteria

UI shows:

```text
sheet boundary
panels
grain direction
cut paths
labels
waste
```

---

## US-E15-004 — Regenerate Nesting

**As a planner, I want to regenerate nesting when panels change.**

### Acceptance Criteria

Old nesting result remains traceable if it was previously released.

---

# 20. Epic E16 — CNC/CAM

## US-E16-001 — Generate CNC Program

**As a CNC programmer, I want CNC output generated from validated manufacturing geometry.**

### Acceptance Criteria

Output uses supported machine profile.

---

## US-E16-002 — CNC Validation

**As a CNC programmer, I want programs validated before release.**

### Acceptance Criteria

Validation checks:

```text
geometry
machine
tool
material
operation
dimensions
```

---

## US-E16-003 — CNC Revision

**As a CNC programmer, I want program versions so that production uses the correct file.**

### Acceptance Criteria

Each released program references:

```text
production revision
nesting revision
machine profile
```

---

## US-E16-004 — CNC Export

**As a CNC programmer, I want to export supported machine formats.**

### Acceptance Criteria

Supported configured formats can be exported.

Export is audited.

---

# 21. Epic E17 — MES

## US-E17-001 — MES Dashboard

**As a factory manager, I want a production dashboard so that I can monitor factory performance.**

### Acceptance Criteria

Dashboard shows:

```text
orders
WIP
machines
downtime
quality
on-time delivery
```

---

## US-E17-002 — Work Center Queue

**As a machine operator, I want to see my queue so that I know what to process next.**

### Acceptance Criteria

Queue is filtered to authorized work center.

---

## US-E17-003 — Start Operation

**As an operator, I want to start a work operation so that production status is accurate.**

### Acceptance Criteria

System validates:

```text
operator permission
work order state
station
panel/order
```

---

## US-E17-004 — Complete Operation

**As an operator, I want to complete an operation so that the panel moves to the next stage.**

### Acceptance Criteria

Completion creates an auditable event.

---

## US-E17-005 — Pause Operation

**As an operator, I want to pause work so that downtime/status is accurately recorded.**

### Acceptance Criteria

Pause requires configured reason.

---

## US-E17-006 — Machine Downtime

**As a factory manager, I want downtime recorded so that bottlenecks can be analyzed.**

### Acceptance Criteria

System captures:

```text
machine
reason
start
end
duration
operator
```

---

# 22. Epic E18 — QR / Panel Tracking

## US-E18-001 — Generate Panel QR

**As a manufacturing engineer, I want a QR code for each panel so that panels can be traced.**

### Acceptance Criteria

QR resolves to the correct panel.

---

## US-E18-002 — Print Panel Label

**As a factory user, I want to print a panel label so that physical panels can be identified.**

### Acceptance Criteria

Label contains configured:

```text
panel ID
project
furniture
dimensions
material
QR
barcode
```

---

## US-E18-003 — Scan Panel

**As a shop-floor operator, I want to scan a panel so that FMOS knows where it is.**

### Acceptance Criteria

Successful scan:

```text
identifies panel
validates station
updates status
records event
```

---

## US-E18-004 — Wrong Station

**As a shop-floor operator, I want FMOS to warn me if a panel arrives at the wrong station.**

### Acceptance Criteria

System clearly shows:

```text
WRONG STATION
```

and expected operation.

No unauthorized state transition occurs.

---

## US-E18-005 — Panel Timeline

**As a factory manager, I want to see panel genealogy so that I can trace production history.**

### Acceptance Criteria

Timeline includes:

```text
created
nested
cut
edge banded
drilled
QC
packed
dispatched
```

where applicable.

---

# 23. Epic E19 — Quality

## US-E19-001 — Quality Inspection

**As a quality inspector, I want to inspect a panel so that defects are captured before packing.**

### Acceptance Criteria

Inspection supports:

```text
checklist
measurement
photo
comment
pass/fail
```

---

## US-E19-002 — Defect

**As a quality inspector, I want to record defects so that corrective action can be taken.**

### Acceptance Criteria

Defect includes:

```text
category
severity
description
photo
```

---

## US-E19-003 — Rework

**As a quality inspector, I want to create rework so that failed items return to production.**

### Acceptance Criteria

Rework references original panel/order and preserves history.

---

## US-E19-004 — Scrap

**As an authorized factory user, I want to scrap a panel so that unusable material is recorded.**

### Acceptance Criteria

Scrap requires permission and reason.

---

# 24. Epic E20 — Packing & Dispatch

## US-E20-001 — Packing Verification

**As a packing operator, I want to scan components so that packages contain the correct parts.**

### Acceptance Criteria

System verifies expected contents.

Missing components are identified.

---

## US-E20-002 — Create Package

**As a packing operator, I want to create packages so that dispatch can be organized.**

### Acceptance Criteria

Package references:

```text
production order
components
dimensions
weight if configured
```

---

## US-E20-003 — Dispatch

**As a dispatch coordinator, I want to dispatch verified packages so that delivery is traceable.**

### Acceptance Criteria

Dispatch requires configured verification.

---

# 25. Epic E21 — AI Copilot

## US-E21-001 — AI Copilot

**As a designer, I want to ask AI questions about my current project so that I can work faster.**

### Acceptance Criteria

AI receives only authorized context.

AI shows useful source/context references where available.

---

## US-E21-002 — Natural Language Design

**As a designer, I want to describe a furniture requirement in natural language so that FMOS can generate a parametric proposal.**

### Acceptance Criteria

Example:

```text
Create a 2400mm wide wardrobe with 3 drawers.
```

AI produces structured parameters.

The deterministic furniture engine validates them.

AI does not directly bypass engineering rules.

---

## US-E21-003 — AI Design Proposal

**As a designer, I want AI to suggest design alternatives so that I can compare options.**

### Acceptance Criteria

Each proposal shows:

```text
dimensions
materials
estimated cost
manufacturability status
```

---

## US-E21-004 — AI Apply Action

**As a designer, I want to review AI changes before applying them.**

### Acceptance Criteria

High-impact AI changes require explicit:

```text
Apply
```

---

## US-E21-005 — AI Manufacturing Explanation

**As a production manager, I want AI to explain delays so that I can identify bottlenecks.**

### Acceptance Criteria

AI analysis is based on actual authorized MES data.

It identifies evidence used.

---

## US-E21-006 — AI Isolation

**As a tenant admin, I want AI data isolated so that another tenant cannot access our information.**

### Acceptance Criteria

AI retrieval always respects:

```text
tenant
factory
project
permission
```

---

# 26. Epic E22 — Image-to-3D / Floorplan AI

## US-E22-001 — Upload Floorplan

**As a designer, I want to upload a floorplan image so that FMOS can analyze it.**

### Acceptance Criteria

System validates file type and size.

Processing occurs asynchronously.

---

## US-E22-002 — Detect Walls

**As a designer, I want walls detected from an uploaded floorplan.**

### Acceptance Criteria

Detected walls appear as editable candidates.

---

## US-E22-003 — Review AI Detection

**As a designer, I want to correct AI detections before committing them.**

### Acceptance Criteria

User can:

```text
accept
reject
edit
merge
split
```

---

## US-E22-004 — Create Editable Plan

**As a designer, I want accepted detections converted into editable CAD objects.**

### Acceptance Criteria

Accepted objects become standard FMOS geometry.

---

# 27. Epic E23 — Documents

## US-E23-001 — Generate Drawing

**As an engineer, I want drawings generated automatically so that documentation is faster.**

### Acceptance Criteria

Drawing uses approved project data.

---

## US-E23-002 — Generate Proposal

**As a sales user, I want a branded proposal generated from project data.**

### Acceptance Criteria

Proposal contains configured tenant branding and approved commercial data.

---

## US-E23-003 — Document Version

**As a project manager, I want document versions so that I know which file is current.**

### Acceptance Criteria

Document revision is traceable.

---

# 28. Epic E24 — Notifications & Collaboration

## US-E24-001 — Notification

**As a user, I want notifications when work relevant to me changes.**

### Acceptance Criteria

Notifications include:

```text
event
time
source
deep link
```

---

## US-E24-002 — Comments

**As a project member, I want to comment on project objects so that collaboration is contextual.**

### Acceptance Criteria

Comments can be associated with authorized resources.

---

## US-E24-003 — Mentions

**As a user, I want to mention another user so that they are notified.**

### Acceptance Criteria

Mention creates notification according to preferences.

---

# 29. Epic E25 — Reports & Analytics

## US-E25-001 — Sales Dashboard

**As a business owner, I want sales analytics so that I can understand pipeline and conversion.**

### Acceptance Criteria

Authorized users can view:

```text
leads
opportunities
quotes
conversion
revenue
```

---

## US-E25-002 — Factory Dashboard

**As a factory manager, I want operational analytics so that I can identify bottlenecks.**

### Acceptance Criteria

Dashboard includes:

```text
throughput
utilization
waste
quality
delivery
```

---

## US-E25-003 — Project Profitability

**As a business owner, I want project profitability so that I can understand margins.**

### Acceptance Criteria

Only authorized users can view cost/margin data.

---

# 30. Epic E26 — Documents, Imports & Exports

## US-E26-001 — Import Catalog

**As a catalog administrator, I want an import wizard so that I can onboard large catalogs.**

### Acceptance Criteria

Flow:

```text
Upload
Map
Validate
Preview
Commit
```

---

## US-E26-002 — Export Data

**As an authorized user, I want to export data so that I can use it outside FMOS.**

### Acceptance Criteria

Export respects:

```text
tenant
permissions
filters
columns
```

---

# 31. Epic E27 — SaaS Usage & Billing

## US-E27-001 — View Usage

**As a tenant admin, I want to see resource usage so that I understand our subscription consumption.**

### Acceptance Criteria

Usage includes configured:

```text
users
projects
storage
AI
API
```

---

## US-E27-002 — Subscription Upgrade

**As a tenant admin, I want to upgrade my plan so that additional capabilities become available.**

### Acceptance Criteria

Plan change is recorded.

New entitlements become effective according to billing rules.

Existing data is retained.

---

## US-E27-003 — Subscription Suspension

**As a platform admin, I want subscription state to control tenant access.**

### Acceptance Criteria

Suspended subscription triggers configured access policy.

---

# 32. Epic E28 — White Label

## US-E28-001 — Branded Email

**As a tenant admin, I want emails to use our identity so that customer communication is consistent.**

### Acceptance Criteria

Supported templates use:

```text
tenant logo
tenant name
tenant footer
configured sender
```

---

## US-E28-002 — Branded Client Portal

**As a tenant admin, I want the client portal branded so that customers see our identity.**

### Acceptance Criteria

Client portal uses effective tenant branding.

---

## US-E28-003 — Branded Documents

**As a tenant admin, I want proposals and reports branded so that customers see our company identity.**

### Acceptance Criteria

Configured documents use tenant templates.

---

# 33. Epic E29 — Search & Command Center

## US-E29-001 — Global Search

**As a user, I want to search across FMOS so that I can quickly find resources.**

### Acceptance Criteria

Results are grouped by entity.

Tenant and permission filters are enforced.

---

## US-E29-002 — Command Palette

**As a power user, I want command search so that I can execute actions quickly.**

### Acceptance Criteria

Commands are permission-aware.

Keyboard shortcut works.

---

# 34. Epic E30 — Offline Shop-Floor

## US-E30-001 — Offline Scan

**As a shop-floor operator, I want scanning to continue during temporary network loss so that production is not stopped unnecessarily.**

### Acceptance Criteria

Supported scans are queued locally.

UI clearly shows offline state.

Queued events synchronize when connection returns.

---

## US-E30-002 — Sync Conflict

**As a factory user, I want conflicts identified so that conflicting production events are not silently overwritten.**

### Acceptance Criteria

Conflicts are surfaced for resolution.

---

# 35. Epic E31 — Security & Audit

## US-E31-001 — Audit Critical Action

**As a platform owner, I want critical actions audited so that changes are traceable.**

### Acceptance Criteria

Audit captures:

```text
user
tenant
action
resource
timestamp
correlation ID
```

---

## US-E31-002 — Tenant Isolation

**As a platform owner, I want tenant data isolated so that customers cannot access each other's information.**

### Acceptance Criteria

Tenant A cannot access Tenant B data through:

```text
UI
URL
API
search
file storage
cache
AI
background jobs
```

---

## US-E31-003 — Authorization Enforcement

**As a security administrator, I want API permissions enforced server-side.**

### Acceptance Criteria

Direct API calls without permission fail even if the UI action is hidden.

---

# 36. Epic E32 — Performance & Reliability

## US-E32-001 — Large Project

**As a designer, I want large projects to remain usable so that complex projects do not become impractical.**

### Acceptance Criteria

Application uses appropriate:

```text
lazy loading
LOD
virtualized lists
caching
```

---

## US-E32-002 — Long-Running Job

**As a user, I want long operations to run asynchronously so that the UI remains usable.**

### Acceptance Criteria

Operations such as:

```text
AI processing
nesting
CNC generation
document generation
large import
```

run asynchronously where appropriate.

---

# 37. Epic E33 — Change Impact & Revision Management

## US-E33-001 — Detect Manufacturing Impact

**As a designer, I want to know when a design change affects manufacturing so that released production data does not become stale.**

### Acceptance Criteria

System identifies affected:

```text
BOM
BOQ
panels
nesting
CNC
production order
```

---

## US-E33-002 — Create New Revision

**As a design lead, I want changes after approval to create a new revision so that approved history remains intact.**

### Acceptance Criteria

Old revision remains immutable.

New revision gets new identifier.

---

# 38. Epic E34 — Admin Configuration

## US-E34-001 — Configure Tenant Settings

**As a tenant admin, I want configurable settings so that FMOS matches our operating model.**

### Acceptance Criteria

Settings are categorized and validated.

---

## US-E34-002 — Configure Numbering

**As a tenant admin, I want custom numbering sequences so that business documents follow our conventions.**

### Acceptance Criteria

Sequences are tenant-scoped.

Concurrent creation cannot produce duplicates.

---

# 39. Epic E35 — Client Portal

## US-E35-001 — Client Login

**As a client, I want secure portal access so that I can review my project.**

### Acceptance Criteria

Client sees only explicitly shared data.

---

## US-E35-002 — View 3D Design

**As a client, I want to view the design in 3D so that I can understand the proposed interior.**

### Acceptance Criteria

Client can:

```text
rotate
zoom
view
```

without access to internal engineering tools.

---

## US-E35-003 — Approve Design

**As a client, I want to approve the current revision so that the project can proceed.**

### Acceptance Criteria

Approval references exact revision and records timestamp/user.

---

# 40. Epic E36 — Manufacturing Quality Gates

## US-E36-001 — Pre-Production Gate

**As a production planner, I want a validation gate before release.**

### Acceptance Criteria

Release is blocked when mandatory checks fail.

---

## US-E36-002 — Pre-CNC Gate

**As a CNC programmer, I want machine readiness validated before program release.**

### Acceptance Criteria

System validates configured machine/program requirements.

---

## US-E36-003 — Pre-Packing Gate

**As a packing operator, I want all required panels verified before packing completion.**

### Acceptance Criteria

Missing required panels prevent completion unless authorized override is used and audited.

---

# 41. Epic E37 — AI-Assisted Commercial & Manufacturing Intelligence

## US-E37-001 — AI Cost Optimization

**As an estimator, I want AI to suggest cost-saving alternatives so that project profitability can improve.**

### Acceptance Criteria

Suggestions identify:

```text
current item
alternative
estimated impact
manufacturing impact
```

User must approve changes.

---

## US-E37-002 — AI Material Recommendation

**As a designer, I want material recommendations based on project requirements so that I can select suitable materials faster.**

### Acceptance Criteria

Recommendations use authorized tenant catalog data.

---

## US-E37-003 — AI Delay Analysis

**As a factory manager, I want AI to identify production delays so that I can act quickly.**

### Acceptance Criteria

AI uses actual production events and does not invent operational facts.

---

# 42. Epic E38 — Notifications & Escalations

## US-E38-001 — Approval Reminder

**As a project manager, I want pending approvals surfaced so that projects do not stall.**

### Acceptance Criteria

Pending approval appears in dashboard/notification according to policy.

---

## US-E38-002 — Production Delay Alert

**As a factory manager, I want delayed production orders highlighted so that I can intervene.**

### Acceptance Criteria

Orders exceeding configured thresholds are flagged.

---

# 43. Epic E39 — Document & Drawing Automation

## US-E39-001 — Generate Working Drawing

**As an engineer, I want working drawings generated from the model so that documentation stays synchronized.**

### Acceptance Criteria

Drawing reflects the selected approved revision.

---

## US-E39-002 — Generate Client Proposal

**As a sales user, I want a proposal generated from project data so that proposal creation is faster.**

### Acceptance Criteria

Proposal uses authorized current data and tenant branding.

---

# 44. Epic E40 — Catalog Governance

## US-E40-001 — Approve Catalog Item

**As a catalog manager, I want catalog items approved before designers use them so that standards are maintained.**

### Acceptance Criteria

Catalog item states include configured lifecycle states.

---

## US-E40-002 — Archive Catalog Item

**As a catalog admin, I want to archive obsolete items without breaking historical projects.**

### Acceptance Criteria

Archived item cannot be newly selected where configured.

Existing project references remain intact.

---

# 45. Epic E41 — Manufacturing Catalog Governance

## US-E41-001 — Machine Profile

**As a manufacturing engineer, I want machine profiles so that CNC generation respects machine capabilities.**

### Acceptance Criteria

Machine profile contains configured:

```text
working area
tools
operations
materials
post processor
```

---

## US-E41-002 — Manufacturing Rule Set

**As a manufacturing engineer, I want rule sets so that production constraints are standardized.**

### Acceptance Criteria

Rule set can be assigned to:

```text
tenant
factory
machine
product
```

according to scope.

---

# 46. Epic E42 — Reporting & Audit

## US-E42-001 — Project Activity

**As a project manager, I want a project activity timeline so that I can understand what changed.**

### Acceptance Criteria

Timeline shows authorized events in chronological order.

---

## US-E42-002 — Manufacturing Traceability Report

**As a factory manager, I want traceability reports so that I can trace physical panels through production.**

### Acceptance Criteria

Report connects:

```text
project
furniture
panel
production order
operations
quality
package
dispatch
```

---

# 47. Epic E43 — Data Import / Migration

## US-E43-001 — Import Existing Catalog

**As a tenant admin, I want to import existing catalog data so that onboarding is practical.**

### Acceptance Criteria

Import supports validation and error reporting.

---

## US-E43-002 — Tenant Data Export

**As a tenant admin, I want to export tenant data so that I retain control over our information.**

### Acceptance Criteria

Export is permission-protected and audited.

---

# 48. Epic E44 — SaaS Support

## US-E44-001 — Platform Tenant Search

**As a platform support admin, I want to search tenants so that I can troubleshoot accounts.**

### Acceptance Criteria

Search supports:

```text
tenant name
tenant code
domain
status
```

---

## US-E44-002 — Tenant Health

**As a support administrator, I want tenant health information so that I can identify issues quickly.**

### Acceptance Criteria

Health includes authorized:

```text
subscription
storage
integrations
recent errors
usage
```

---

# 49. Cross-Module User Stories

## US-X-001 — Design-to-BOM Continuity

**As a designer, I want furniture changes to automatically update derived BOM information so that engineering remains synchronized.**

### Acceptance Criteria

Given furniture dimensions change:

```text
When the change is saved
Then derived components are recalculated
And BOM is marked/generated as current
And dependent pricing/manufacturing data is flagged as affected where necessary.
```

---

## US-X-002 — BOM-to-Pricing Continuity

**As an estimator, I want current BOM data available to pricing so that quotes reflect the design.**

### Acceptance Criteria

Pricing uses the correct BOM revision.

---

## US-X-003 — Pricing-to-Quote Continuity

**As a sales user, I want the quote generated from approved commercial data so that pricing is consistent.**

### Acceptance Criteria

Quote references pricing revision.

---

## US-X-004 — Quote-to-Production Continuity

**As a production planner, I want only approved scope released to manufacturing.**

### Acceptance Criteria

Production release checks approval status.

---

## US-X-005 — Design-to-Manufacturing Change Detection

**As a project manager, I want downstream impact detected after design changes.**

### Acceptance Criteria

System identifies affected downstream artifacts.

---

## US-X-006 — Manufacturing-to-MES Continuity

**As a factory manager, I want released production orders to generate executable work so that shop-floor teams can work directly from FMOS.**

### Acceptance Criteria

Production release generates appropriate:

```text
work orders
panels
operations
labels
```

---

## US-X-007 — MES-to-Panel Traceability

**As a factory manager, I want every panel event traceable to its source design.**

### Acceptance Criteria

Panel genealogy connects to original project/furniture/component.

---

## US-X-008 — Panel-to-Dispatch Traceability

**As a factory manager, I want finished panels/components traceable through packing and dispatch.**

### Acceptance Criteria

Traceability is preserved from production through package and dispatch.

---

# 50. Critical Negative Acceptance Scenarios

The following must be tested explicitly.

## SEC-001 — Cross-Tenant Access

Given Tenant A user knows Tenant B resource ID:

```text
When user requests resource
Then access is denied
And Tenant B data is not returned.
```

## SEC-002 — Cross-Tenant File

Given Tenant A knows Tenant B file path:

```text
When file is requested
Then access is denied.
```

## SEC-003 — Cross-Tenant AI

Given Tenant A asks AI about Tenant B data:

```text
Then AI must not retrieve Tenant B data.
```

## SEC-004 — Unauthorized API

Given a user lacks:

```text
manufacturing.release
```

when they call the API directly:

```text
Then request is rejected.
```

## SEC-005 — Unauthorized MES Transition

Given operator is assigned to Work Center A:

```text
When operator attempts Work Center B operation
Then state transition is rejected.
```

## SEC-006 — Invalid Production Release

Given mandatory validation fails:

```text
When user releases production
Then release is blocked.
```

## SEC-007 — Invalid CNC

Given machine profile is incompatible:

```text
When CNC release is attempted
Then release is blocked.
```

## SEC-008 — Wrong Panel Station

Given panel is expected at drilling:

```text
When scanned at packing
Then system warns/blocks according to workflow policy.
```

## SEC-009 — Unauthorized Margin

Given user lacks pricing margin permission:

```text
When quote is opened
Then internal margin is not exposed.
```

## SEC-010 — Deleted Catalog Item

Given catalog item is archived:

```text
When historical project is opened
Then historical reference remains available.
```

## SEC-011 — Stale Revision

Given production is released against Revision 8:

```text
When Revision 9 changes the furniture
Then existing production package remains identifiable as Revision 8.
```

## SEC-012 — AI Unavailable

Given AI provider is unavailable:

```text
When user requests AI
Then core application remains usable
and an actionable error is shown.
```

---

# 51. Critical Workflow Acceptance Tests

## WF-001 — Complete Design-to-Manufacturing

The system must support:

```text
Create Customer
 ↓
Create Project
 ↓
Create Building
 ↓
Create Floor
 ↓
Create Room
 ↓
Create 2D Plan
 ↓
Create 3D Model
 ↓
Place Furniture
 ↓
Configure Materials
 ↓
Generate BOM
 ↓
Generate BOQ
 ↓
Calculate Pricing
 ↓
Generate Quote
 ↓
Client Approval
 ↓
Manufacturing Validation
 ↓
Production Release
 ↓
Generate Panels
 ↓
Nesting
 ↓
CNC
 ↓
MES
 ↓
QR Scan
 ↓
Quality
 ↓
Packing
 ↓
Dispatch
```

Every step must preserve required object relationships.

---

# 52. WF-002 — Design Change After Approval

```text
Approved R08
 ↓
Designer changes cabinet
 ↓
System creates/requests R09
 ↓
Impact Analysis
 ↓
BOM affected
 ↓
Pricing affected
 ↓
Nesting affected
 ↓
CNC affected
 ↓
Production package marked stale
```

No silent downstream corruption is allowed.

---

# 53. WF-003 — Manufacturing Failure

```text
Panel fails QC
 ↓
Defect recorded
 ↓
Rework created
 ↓
Rework operation
 ↓
Re-inspection
 ↓
Pass
 ↓
Continue production
```

---

# 54. WF-004 — Missing Panel

```text
Packing
 ↓
Scan expected panels
 ↓
One panel missing
 ↓
Package blocked
 ↓
System identifies missing panel
 ↓
Panel traceability opened
```

---

# 55. WF-005 — Wrong Station

```text
Panel expected:
Drilling

Operator scans:
Packing

System:
WRONG STATION
```

No invalid completion.

---

# 56. WF-006 — Tenant Onboarding

```text
Create Tenant
 ↓
Create Admin
 ↓
Branding
 ↓
Domain
 ↓
Factory
 ↓
Users
 ↓
Catalog
 ↓
Project
```

All records remain tenant-isolated.

---

# 57. WF-007 — White Label

```text
Tenant configures branding
 ↓
Logo
 ↓
Colors
 ↓
Application Name
 ↓
Custom Domain
 ↓
Verify Domain
 ↓
Login
 ↓
Dashboard
 ↓
Client Portal
 ↓
Proposal
 ↓
Email
```

All configured surfaces display tenant identity.

---

# 58. WF-008 — AI Furniture Request

Input:

```text
Create a 2400mm wardrobe with three drawers.
```

Expected:

```text
AI intent
 ↓
Structured parameters
 ↓
Furniture engine
 ↓
Validation
 ↓
Preview
 ↓
User approval
 ↓
Commit
 ↓
BOM
 ↓
Pricing
```

AI must not directly bypass the deterministic furniture engine.

---

# 59. WF-009 — Floorplan AI

```text
Upload floorplan
 ↓
AI processing
 ↓
Wall/door/window detection
 ↓
Review
 ↓
Accept/Edit
 ↓
Generate CAD
 ↓
Generate 3D
```

---

# 60. WF-010 — Offline QR

```text
Network lost
 ↓
Scan panel
 ↓
Event queued locally
 ↓
Operator sees Offline
 ↓
Network restored
 ↓
Sync
 ↓
Server validation
 ↓
Event committed
```

---

# 61. Story Prioritization

Priority levels:

```text
P0 = Mandatory for core product
P1 = Required for initial commercial release
P2 = Important enhancement
P3 = Future capability
```

Recommended P0 modules:

```text
Authentication
Tenant
RBAC
Project
2D CAD
3D
Furniture
Catalog
BOM
Pricing
Manufacturing
Nesting
CNC
MES
QR
Quality
Client Approval
```

Recommended P1:

```text
AI
White Label
Advanced Analytics
Offline
SSO
Advanced Reporting
```

---

# 62. Definition of Ready

A story is Ready when:

```text
[ ] Business objective understood
[ ] User role identified
[ ] Screen identified
[ ] API/data dependencies known
[ ] Permission identified
[ ] Acceptance criteria defined
[ ] Error states understood
[ ] Dependencies identified
[ ] No unresolved architectural contradiction
```

---

# 63. Definition of Done

A story is Done only when:

```text
[ ] Code implemented
[ ] Backend validation implemented
[ ] Frontend implemented
[ ] RBAC implemented
[ ] Tenant isolation verified
[ ] API documented
[ ] Error handling implemented
[ ] Loading state implemented
[ ] Empty state implemented where applicable
[ ] Audit implemented where required
[ ] Unit tests passed
[ ] Integration tests passed
[ ] E2E tests passed where applicable
[ ] Security tests passed
[ ] UI reviewed
[ ] Accessibility checked
[ ] No console errors
[ ] No PHP errors/warnings
[ ] Database migration complete
[ ] Documentation updated
```

---

# 64. Cursor Implementation Rules

Cursor MUST NOT blindly implement all stories in one pass.

Before coding:

```text
1. Analyze repository.
2. Map existing functionality.
3. Identify reusable modules.
4. Identify missing functionality.
5. Map stories to existing screens/routes.
6. Map stories to APIs.
7. Map stories to database entities.
8. Identify conflicts with current architecture.
9. Propose implementation sequence.
10. Implement one epic at a time.
```

---

# 65. Cursor Story Traceability

Each implemented story should be traceable using:

```text
Story ID
↓
Screen
↓
Component
↓
API
↓
Service
↓
Database
↓
Tests
```

Example:

```text
US-E09-003
 ↓
Furniture Designer
 ↓
ParameterEditor.js
 ↓
PATCH /api/v1/furniture/{id}
 ↓
FurnitureService.php
 ↓
furniture_instances
 ↓
FurnitureParameterTest
```

---

# 66. Cursor Acceptance Test Generation

For every story Cursor should create appropriate tests from the acceptance criteria.

Example:

```text
US-E18-003
```

must generate tests for:

```text
valid scan
invalid scan
wrong station
unknown panel
unauthorized station
duplicate scan
offline scan
sync
```

---

# 67. Story Dependency Graph

Core dependency:

```text
Tenant
 ↓
Auth
 ↓
RBAC
 ↓
Project
 ↓
Building
 ↓
Room
 ↓
2D CAD
 ↓
3D
 ↓
Furniture
 ↓
Materials
 ↓
BOM
 ↓
Pricing
 ↓
Quote
 ↓
Approval
 ↓
Manufacturing
 ↓
Nesting
 ↓
CNC
 ↓
MES
 ↓
QR
 ↓
Quality
 ↓
Packing
 ↓
Dispatch
```

---

# 68. Recommended Implementation Waves

## Wave 1 — Platform Foundation

```text
Tenant
Authentication
RBAC
Navigation
UI system
Project
Audit
```

## Wave 2 — Design

```text
Building
Floor
Room
2D CAD
3D
```

## Wave 3 — Furniture

```text
Catalog
Furniture
Parametric Engine
Materials
Hardware
```

## Wave 4 — Commercial

```text
BOM
BOQ
Pricing
Quote
Client Approval
```

## Wave 5 — Manufacturing

```text
Production
Panels
Nesting
CNC
```

## Wave 6 — MES

```text
Work Centers
Machines
Shop Floor
QR
Tracking
Quality
Packing
Dispatch
```

## Wave 7 — Intelligence

```text
AI
Image-to-3D
Recommendations
Analytics
```

## Wave 8 — SaaS

```text
White Label
Domains
Subscription
Usage
SSO
Dedicated Deployment
```

---

# 69. Product-Level Success Criteria

FMOS is considered functionally complete when a customer can:

```text
1. Create a customer.
2. Create a project.
3. Create a building/floor/room.
4. Draw/import a floor plan.
5. Build the 3D model.
6. Design modular furniture.
7. Select actual materials/hardware.
8. Generate BOM.
9. Generate BOQ.
10. Calculate price.
11. Generate quote.
12. Obtain client approval.
13. Release manufacturing.
14. Generate panels.
15. Optimize nesting.
16. Generate CNC output.
17. Track panels with QR.
18. Execute MES operations.
19. Perform quality inspection.
20. Handle rework.
21. Pack.
22. Dispatch.
23. Trace the final physical product back to its original design.
```

---

# 70. Final Product Principle

The fundamental FMOS user story is:

> **As an interior business, I want to take a project from customer requirement through spatial design, parametric furniture engineering, costing, client approval, manufacturing and factory execution in one connected system, so that design intent is preserved all the way to the physical product.**

Every module and user story should reinforce that outcome.

The system should never become a collection of disconnected CRUD screens.

The critical object chain must remain connected:

```text
Customer
  ↓
Project
  ↓
Building
  ↓
Floor
  ↓
Room
  ↓
Design Object
  ↓
Furniture Instance
  ↓
Component
  ↓
Material / Hardware
  ↓
BOM
  ↓
BOQ
  ↓
Price
  ↓
Quote
  ↓
Approval
  ↓
Manufacturing Revision
  ↓
Panel
  ↓
Nesting
  ↓
CNC Program
  ↓
Work Order
  ↓
MES Event
  ↓
QR Scan
  ↓
Quality
  ↓
Package
  ↓
Dispatch
```

**This document is the implementation and QA behavioral contract. Cursor must use the story IDs and acceptance criteria as traceability identifiers throughout development, testing and code review.**
