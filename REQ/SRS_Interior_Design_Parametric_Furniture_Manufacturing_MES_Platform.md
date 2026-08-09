# Software Requirements Specification (SRS)
## End-to-End Interior Design, Parametric Furniture, Estimation, Manufacturing & MES Platform

**Document ID:** SRS-IDFM-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Product Owner, Business Analyst, Solution Architect, Developers, QA, DevOps, Cursor / AI Coding Agents  
**Technology Baseline:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + HTML5 + CSS + Three.js  
**Architecture Baseline:** Modular Monolith with domain boundaries and future engine extraction  
**Primary Unit:** Millimeter (mm)  
**Date:** 2026-08-09

---

# 1. Document Purpose

This Software Requirements Specification converts the approved Product Vision, System Architecture and Business Requirements into software-level requirements that can be implemented, tested and traced.

This document is intended to be used directly by:

- Cursor
- Software developers
- QA engineers
- Solution architects
- UI/UX designers
- DevOps engineers
- Product owners

The SRS defines:

- functional requirements
- system behavior
- business rules
- workflows
- data requirements
- validations
- permissions
- state transitions
- integration requirements
- non-functional requirements
- error handling
- audit requirements
- testing expectations
- implementation constraints

This document is not a UI-only specification. It defines the behavior of the complete system.

---

# 2. Requirement Priority

Every requirement uses one of these priorities:

| Priority | Meaning |
|---|---|
| P0 | Mandatory for MVP / core platform |
| P1 | Required for first commercial release |
| P2 | Advanced capability |
| P3 | Future capability |

---

# 3. Requirement Language

The following terms are mandatory:

- **MUST** = mandatory
- **SHALL** = mandatory system behavior
- **SHOULD** = recommended
- **MAY** = optional
- **MUST NOT** = prohibited

---

# 4. Product Scope

The software shall provide the following major domains:

1. Authentication & Identity
2. Multi-Tenancy
3. RBAC
4. CRM & Sales
5. Project Management
6. Architectural Design
7. 2D Design Workspace
8. 3D Workspace
9. Interior Design
10. Parametric Furniture
11. Component Designer
12. Materials & Catalog
13. BOM
14. BOQ
15. Pricing
16. Quotations & Proposals
17. Engineering Validation
18. Manufacturing
19. Cutlist
20. Nesting
21. CNC/CAM
22. MES
23. QR Panel Tracking
24. QC
25. Packing
26. Dispatch
27. Documents
28. Revision Management
29. Audit
30. Notifications
31. AI Integration
32. White Label / SaaS
33. Reporting & Analytics

---

# 5. System Context

The system shall support this lifecycle:

```text
CRM
 ↓
Project
 ↓
Building / Floor / Room
 ↓
2D Architectural Design
 ↓
3D Spatial Model
 ↓
Interior Design
 ↓
Parametric Furniture
 ↓
Materials
 ↓
Engineering Validation
 ↓
BOM
 ↓
BOQ
 ↓
Pricing
 ↓
Quotation / Proposal
 ↓
Client Approval
 ↓
Manufacturing Release
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC/CAM
 ↓
Production
 ↓
QC
 ↓
Packing
 ↓
Dispatch
 ↓
Installation / Completion
```

---

# 6. Core System Principle

The system MUST maintain one authoritative project model.

The following must be derived from the project model:

- 2D representation
- 3D representation
- elevations
- furniture geometry
- BOM
- BOQ
- pricing
- cutlist
- manufacturing data
- nesting
- CNC data
- production information

The system MUST NOT create independent disconnected versions of these artifacts without explicit snapshot/revision semantics.

---

# 7. Technology Requirements

## SRS-TECH-001 — Backend

The backend MUST use PHP 8.x.

## SRS-TECH-002 — Database

The primary relational database MUST be MySQL 8.x.

## SRS-TECH-003 — Frontend

The initial frontend MUST use:

- HTML5
- CSS3
- JavaScript ES6+
- ES Modules

## SRS-TECH-004 — 3D

Three.js SHALL be used for initial 3D rendering.

## SRS-TECH-005 — API

Backend/frontend communication SHALL use versioned REST APIs.

Base path:

```text
/api/v1/
```

## SRS-TECH-006 — Storage

Large binary assets MUST use filesystem/object storage rather than normal relational columns.

---

# 8. System Architecture Requirements

## SRS-ARCH-001

The system MUST follow modular domain boundaries.

Recommended domains:

```text
Identity
Tenant
CRM
Project
Architecture
Furniture
Catalog
Pricing
BOM
BOQ
Manufacturing
MES
Documents
AI
```

## SRS-ARCH-002

Controllers MUST NOT contain complex business logic.

## SRS-ARCH-003

Frontend code MUST NOT contain authoritative business calculations.

## SRS-ARCH-004

SQL MUST NOT be embedded in frontend code.

## SRS-ARCH-005

Three.js scene objects MUST NOT be the authoritative source of project state.

## SRS-ARCH-006

Business rules MUST be centralized in domain/application services or engines.

---

# 9. Authentication & Identity

## SRS-AUTH-001 — Login

The system MUST allow registered users to authenticate.

Inputs:

- email/username
- password

Output:

- authenticated session/token
- user profile
- tenant context
- permissions

## SRS-AUTH-002 — Password Security

Passwords MUST be securely hashed using PHP's supported password hashing mechanism.

Plaintext passwords MUST never be stored.

## SRS-AUTH-003 — Logout

Logout MUST invalidate the active session/token.

## SRS-AUTH-004 — Session Expiration

Sessions MUST support configurable expiration.

## SRS-AUTH-005 — Password Reset

The system SHOULD support secure password reset through time-limited tokens.

## SRS-AUTH-006 — Authentication Errors

The system MUST NOT reveal whether an email exists when that information could enable account enumeration.

---

# 10. Multi-Tenant Requirements

## SRS-TENANT-001

Every tenant-owned entity MUST have a tenant association.

## SRS-TENANT-002

All tenant-scoped queries MUST enforce tenant filtering.

## SRS-TENANT-003

A user MUST NOT access another tenant's data by manipulating IDs.

## SRS-TENANT-004

Tenant isolation MUST be enforced server-side.

## SRS-TENANT-005

Tenant configuration SHALL include:

- name
- logo
- branding
- domain
- email configuration
- tax information
- currency
- measurement preferences
- feature configuration

---

# 11. RBAC Requirements

## SRS-RBAC-001

The system MUST support:

```text
User
Role
Permission
User-Role
Role-Permission
```

## SRS-RBAC-002

Permissions MUST be granular.

Examples:

```text
project.view
project.create
project.edit
project.delete

design.view
design.create
design.edit

furniture.view
furniture.create
furniture.edit
furniture.approve

bom.view
bom.generate

boq.view
boq.edit

pricing.view
pricing.edit

manufacturing.view
manufacturing.generate
manufacturing.release

production.view
production.update

qc.view
qc.update
```

## SRS-RBAC-003

Authorization MUST be enforced by backend services/middleware.

## SRS-RBAC-004

Frontend permission checks are for UX only and MUST NOT replace backend authorization.

---

# 12. CRM Requirements

## SRS-CRM-001 — Lead

System MUST allow creation of leads.

Fields:

- name
- company
- contact
- source
- status
- owner
- notes

## SRS-CRM-002 — Client

System MUST support client records.

Fields:

- client ID
- name
- company
- email
- phone
- address
- tax information
- contacts

## SRS-CRM-003 — Opportunity

System MUST allow opportunities to be associated with clients.

## SRS-CRM-004

An opportunity MAY be converted into a project.

---

# 13. Project Requirements

## SRS-PROJECT-001

Users with permission MUST be able to create projects.

Minimum fields:

- project ID
- project name
- client
- project type
- status
- start date
- expected completion
- assigned users

## SRS-PROJECT-002

Project statuses SHALL support:

```text
DRAFT
ACTIVE
ON_HOLD
COMPLETED
ARCHIVED
```

## SRS-PROJECT-003

A project MAY contain multiple buildings.

## SRS-PROJECT-004

A building MAY contain multiple floors.

## SRS-PROJECT-005

A floor MAY contain multiple rooms.

---

# 14. Room Requirements

## SRS-ROOM-001

A room MUST have:

- ID
- name
- floor
- dimensions where applicable
- geometry
- status

## SRS-ROOM-002

A room MAY contain:

- walls
- doors
- windows
- columns
- beams
- ceilings
- flooring
- furniture
- fixtures
- annotations

---

# 15. Generic Design Object Requirements

## SRS-DESIGN-001

A design object MUST have:

```text
id
project_id
room_id
parent_id
object_type
geometry
parameters
materials
metadata
revision
status
created_by
updated_by
timestamps
```

## SRS-DESIGN-002

Objects MUST support stable identifiers.

## SRS-DESIGN-003

Object types MUST be extensible.

Initial types:

```text
WALL
DOOR
WINDOW
COLUMN
BEAM
FLOOR
CEILING
FURNITURE
FIXTURE
ANNOTATION
```

---

# 16. Coordinate System

## SRS-GEO-001

The canonical internal unit MUST be millimeter.

## SRS-GEO-002

The canonical coordinate system MUST be documented.

Recommended:

```text
X = width
Y = depth
Z = height
```

## SRS-GEO-003

All unit conversions MUST pass through a centralized utility.

## SRS-GEO-004

Rounding MUST NOT occur repeatedly during calculations.

---

# 17. Wall Requirements

## SRS-WALL-001

Users with design permission MUST be able to create walls.

## SRS-WALL-002

Wall properties:

- start
- end
- thickness
- height
- base elevation
- material
- finish
- interior finish
- exterior finish

## SRS-WALL-003

Changing wall dimensions MUST update 2D and 3D representations.

## SRS-WALL-004

Walls MUST support openings.

---

# 18. Door Requirements

## SRS-DOOR-001

Door properties:

- width
- height
- thickness
- frame
- shutter
- swing
- offset
- material

## SRS-DOOR-002

Door geometry MUST be generated from parameters.

## SRS-DOOR-003

Door changes MUST update all relevant views.

---

# 19. Window Requirements

## SRS-WINDOW-001

Window properties:

- width
- height
- sill height
- frame
- glass
- opening type
- material

## SRS-WINDOW-002

Window geometry MUST be generated from parameters.

---

# 20. 2D Workspace Requirements

## SRS-2D-001

The system MUST provide a CAD-like 2D workspace.

## SRS-2D-002

Workspace MUST support:

- pan
- zoom
- selection
- multi-selection
- move
- copy
- rotate
- mirror
- delete
- dimensions
- annotations

## SRS-2D-003

Workspace SHOULD support:

- grid
- snap
- object snap
- angle snap
- orthogonal mode
- alignment
- offset
- trim
- extend

## SRS-2D-004

Selected objects MUST display their properties.

## SRS-2D-005

Changing a property MUST update the domain model.

---

# 21. 2D Rendering Requirements

## SRS-2D-006

The 2D renderer MUST consume domain model data.

## SRS-2D-007

The 2D renderer MUST NOT maintain an independent permanent copy of business data.

## SRS-2D-008

The system SHOULD use SVG or Canvas according to object/rendering requirements.

---

# 22. 3D Requirements

## SRS-3D-001

The system MUST generate a 3D scene from the project model.

## SRS-3D-002

3D MUST support:

- camera
- orbit
- pan
- zoom
- selection
- object visibility
- materials
- basic lighting

## SRS-3D-003

The 3D scene MUST be regenerated/updated when domain geometry changes.

## SRS-3D-004

3D mesh IDs SHOULD map back to domain object IDs.

---

# 23. 2D/3D Synchronization

## SRS-SYNC-001

2D and 3D MUST use the same domain objects.

## SRS-SYNC-002

A change to a domain object MUST notify relevant renderers.

Example:

```text
Wall Updated
 ↓
State Update
 ↓
2D Refresh
 ↓
3D Refresh
```

## SRS-SYNC-003

The system MUST NOT require manual export/import between 2D and 3D.

---

# 24. Selection Requirements

## SRS-SELECTION-001

The application MUST maintain centralized selection state.

## SRS-SELECTION-002

Selecting an object in 2D SHOULD highlight it in 3D.

## SRS-SELECTION-003

Selecting an object in 3D SHOULD highlight it in 2D/property panels.

---

# 25. Command / Undo / Redo Requirements

## SRS-CMD-001

Design actions MUST be represented as commands where practical.

Examples:

```text
CreateWall
MoveWall
ResizeWall
DeleteWall
CreateFurniture
MoveFurniture
ResizeFurniture
ChangeMaterial
AddComponent
DeleteComponent
```

## SRS-CMD-002

Commands SHOULD expose:

```text
execute()
undo()
redo()
```

## SRS-CMD-003

Undo/redo MUST maintain application state consistency.

---

# 26. Interior Design Requirements

## SRS-INTERIOR-001

Users MUST be able to assign materials to relevant surfaces/objects.

## SRS-INTERIOR-002

The system SHOULD support:

- flooring
- paint
- wallpaper
- tile
- false ceiling
- wall treatments
- fixtures
- decorative elements

## SRS-INTERIOR-003

Material assignments MUST be represented in the project model.

---

# 27. Furniture Template Requirements

## SRS-FURN-TEMPLATE-001

The system MUST support reusable furniture templates.

Template properties:

- template ID
- name
- category
- version
- parameters
- component definitions
- rules
- material mappings
- hardware mappings
- manufacturing rules
- status

## SRS-FURN-TEMPLATE-002

Templates MUST support versioning.

## SRS-FURN-TEMPLATE-003

Changing a published template MUST NOT silently modify historical project instances.

---

# 28. Furniture Categories

Initial categories:

```text
WARDROBE
KITCHEN_BASE
KITCHEN_WALL
KITCHEN_TALL
KITCHEN_ISLAND
TV_UNIT
STORAGE
VANITY
BOOKSHELF
STUDY_UNIT
CUSTOM_CABINET
```

---

# 29. Parametric Furniture Requirements

## SRS-FURN-001

Users MUST be able to create furniture from templates.

## SRS-FURN-002

Furniture MUST expose configurable parameters.

Example:

```text
Width
Height
Depth
Thickness
Shelf Count
Drawer Count
Shutter Count
```

## SRS-FURN-003

Parameters MUST support:

- default value
- minimum
- maximum
- unit
- required/optional
- validation

## SRS-FURN-004

Changing a parameter MUST recalculate dependent parameters.

---

# 30. Parametric Rule Requirements

## SRS-RULE-001

Rules MUST be centrally managed.

## SRS-RULE-002

Rules MAY contain controlled formulas.

Example:

```text
shelf_width =
internal_width - (2 * carcass_thickness)
```

## SRS-RULE-003

The system MUST NOT execute arbitrary PHP or JavaScript supplied as a formula.

## SRS-RULE-004

Rules MUST produce deterministic results for the same input/version.

---

# 31. Component Requirements

## SRS-COMP-001

Furniture components MUST be reusable.

Examples:

- side panel
- top
- bottom
- shelf
- partition
- drawer
- shutter
- back
- toe kick

## SRS-COMP-002

Components MUST support parameters and rules.

## SRS-COMP-003

Components MUST be capable of generating manufacturing data.

---

# 32. Component Designer Requirements

## SRS-COMP-DESIGN-001

Authorized users SHOULD be able to create custom components.

## SRS-COMP-DESIGN-002

Component Designer SHALL support:

- parameters
- constraints
- geometry
- materials
- hardware
- manufacturing metadata
- pricing metadata

## SRS-COMP-DESIGN-003

Custom components MUST be versioned.

---

# 33. Material Requirements

## SRS-MAT-001

The system MUST maintain a material catalog.

Categories:

```text
BOARD
LAMINATE
EDGE_BAND
HARDWARE
PROFILE
ACCESSORY
```

## SRS-MAT-002

Materials MUST have stable IDs.

## SRS-MAT-003

Materials SHOULD have active/inactive status.

---

# 34. Board Requirements

Board fields:

```text
id
brand
code
material_type
thickness
length
width
finish
color
grain_direction
cost
selling_price
status
```

---

# 35. Laminate Requirements

Laminate fields:

```text
id
brand
collection
code
name
finish
thickness
sheet_length
sheet_width
cost
selling_price
image
status
```

---

# 36. Edge Band Requirements

Fields:

```text
id
material
thickness
width
color
cost_per_meter
status
```

---

# 37. Hardware Requirements

Hardware fields:

```text
id
category
brand
code
name
unit
cost
selling_price
attributes
status
```

---

# 38. Catalog Import

## SRS-CATALOG-001

The system SHOULD support CSV/Excel catalog imports.

## SRS-CATALOG-002

Import workflow:

```text
Upload
 ↓
Parse
 ↓
Validate
 ↓
Preview
 ↓
Confirm
 ↓
Import
 ↓
Audit
```

## SRS-CATALOG-003

Invalid rows MUST be reported without silently corrupting valid records.

---

# 39. BOM Requirements

## SRS-BOM-001

The system MUST generate BOM from the project/furniture model.

## SRS-BOM-002

BOM MUST support:

- item
- type
- material
- quantity
- unit
- source object
- source revision

## SRS-BOM-003

BOM generation MUST be deterministic.

## SRS-BOM-004

BOM output SHOULD identify affected items after design changes.

---

# 40. BOQ Requirements

## SRS-BOQ-001

The system MUST generate BOQ from design/BOM/commercial mapping.

## SRS-BOQ-002

BOQ fields:

```text
item
description
category
quantity
unit
rate
discount
tax
total
```

## SRS-BOQ-003

Authorized users MAY edit commercial values without modifying source design geometry.

---

# 41. Pricing Requirements

## SRS-PRICE-001

Pricing MUST support raw-material pricing.

## SRS-PRICE-002

Pricing MUST support panel/unit pricing.

## SRS-PRICE-003

Pricing components MAY include:

- material
- hardware
- edge band
- labour
- manufacturing
- installation
- overhead
- markup
- discount
- tax

## SRS-PRICE-004

Pricing rules MUST be tenant configurable.

---

# 42. Pricing Versioning

## SRS-PRICE-005

Approved quotations MUST preserve the pricing basis used to calculate them.

## SRS-PRICE-006

Changing current rates MUST NOT change historical approved quotations.

---

# 43. Quotation Requirements

## SRS-QUOTE-001

Authorized users MUST be able to create quotations.

## SRS-QUOTE-002

Quotation status:

```text
DRAFT
INTERNAL_REVIEW
SENT
CLIENT_REVIEW
APPROVED
REJECTED
EXPIRED
CANCELLED
```

## SRS-QUOTE-003

Quotation MUST reference:

- client
- project
- BOQ
- pricing version

---

# 44. Proposal Requirements

## SRS-PROPOSAL-001

System SHOULD generate branded client proposals.

Proposal sections may include:

- cover
- client
- project
- floor plans
- 3D views
- materials
- scope
- BOQ summary
- price
- terms

---

# 45. Engineering Validation

## SRS-ENG-001

Furniture MUST be validated before manufacturing.

## SRS-ENG-002

Validation MUST check:

- dimensions
- parameter ranges
- component rules
- materials
- hardware
- panel sizes
- edge banding
- drilling
- routing
- manufacturing constraints

## SRS-ENG-003

Validation result severity:

```text
INFO
WARNING
ERROR
BLOCKER
```

## SRS-ENG-004

BLOCKER errors MUST prevent manufacturing release.

---

# 46. Manufacturing Decomposition

## SRS-MFG-001

The manufacturing engine MUST convert furniture into manufacturable components.

Example:

```text
Wardrobe
 ↓
Side panels
Top
Bottom
Shelves
Partitions
Back
Shutters
Drawers
Hardware
```

## SRS-MFG-002

Each panel MUST retain source references.

---

# 47. Panel Requirements

## SRS-PANEL-001

Panel fields:

```text
panel_id
project_id
room_id
furniture_id
component_id
material_id
thickness
length
width
quantity
grain_direction
edge_top
edge_bottom
edge_left
edge_right
drilling_data
routing_data
status
revision
```

---

# 48. Edge Banding Requirements

## SRS-EDGE-001

Each panel MUST support edge definitions:

```text
TOP
BOTTOM
LEFT
RIGHT
```

Each edge MAY contain:

- material
- thickness
- width
- operation

## SRS-EDGE-002

Edge-band quantities MUST be calculated from panel geometry.

---

# 49. Hardware Requirements

## SRS-HARDWARE-001

Hardware quantities MUST be derived from furniture rules where configured.

Examples:

```text
Hinge count
Drawer channel count
Handles
Connectors
Screws
```

---

# 50. Nesting Requirements

## SRS-NEST-001

The nesting engine MUST accept:

- panels
- sheets
- grain constraints
- kerf
- spacing
- rotation rules

## SRS-NEST-002

The engine MUST produce:

- sheet count
- placement
- waste
- utilization
- layout

## SRS-NEST-003

Nesting algorithms MUST be isolated behind an interface.

---

# 51. Nesting Calculation

The system SHOULD calculate:

```text
Total Sheet Area
Total Panel Area
Used Area
Waste Area
Waste %
```

## SRS-NEST-004

Nesting results MUST reference the manufacturing revision.

---

# 52. CNC/CAM Requirements

## SRS-CNC-001

The system MUST define a machine-neutral manufacturing representation.

## SRS-CNC-002

CNC generation MUST use adapters.

Potential adapters:

```text
DXF
CSV
Generic CNC
Biesse
Homag
KDT
```

## SRS-CNC-003

Machine-specific formatting MUST NOT leak into the core manufacturing domain.

---

# 53. CNC Validation

Before generating machine output, the system MUST validate:

- panel dimensions
- operations
- coordinates
- tool/path requirements
- machine constraints where configured

Invalid outputs MUST be rejected or clearly marked.

---

# 54. Manufacturing Release

## SRS-MFG-RELEASE-001

Manufacturing release MUST require:

- approved design
- successful engineering validation
- no blocker errors
- BOM generated
- manufacturing data generated
- required materials defined

## SRS-MFG-RELEASE-002

Release MUST create a manufacturing snapshot.

---

# 55. Manufacturing Snapshot

Snapshot MUST preserve:

- project revision
- furniture revision
- template version
- material version
- manufacturing rules version
- BOM
- panels
- cutlist
- nesting
- CNC outputs

---

# 56. Stale Dependency Requirements

If a released/unreleased design is changed, dependent artifacts MUST be marked stale where appropriate.

Example:

```text
Furniture Changed
 ↓
BOM = STALE
BOQ = STALE
Cutlist = STALE
Nesting = STALE
CNC = STALE
```

The UI MUST communicate stale state.

---

# 57. MES Requirements

## SRS-MES-001

The system MUST support manufacturing jobs.

## SRS-MES-002

Production stages:

```text
PLANNED
READY
CUTTING
EDGE_BANDING
DRILLING
ROUTING
ASSEMBLY
QC
PACKING
DISPATCHED
COMPLETED
```

## SRS-MES-003

Invalid state transitions MUST be rejected.

---

# 58. Production Job Requirements

Production job fields:

```text
job_id
project_id
manufacturing_revision
priority
status
assigned_team
scheduled_date
start_time
completion_time
notes
```

---

# 59. Panel Production Tracking

## SRS-MES-004

Each panel SHOULD have an individual production status.

## SRS-MES-005

Panel status changes MUST be auditable.

## SRS-MES-006

System SHOULD support batch operations for multiple panels.

---

# 60. QR Requirements

## SRS-QR-001

Each panel MUST have a unique public identifier.

## SRS-QR-002

System MUST generate printable QR labels.

## SRS-QR-003

QR scanning MUST resolve panel information through an authenticated/controlled endpoint.

## SRS-QR-004

Sensitive business data MUST NOT be embedded directly in the QR payload.

---

# 61. QC Requirements

## SRS-QC-001

QC records MUST contain:

- panel/job
- inspector
- checklist
- result
- defects
- notes
- timestamp

## SRS-QC-002

QC outcomes:

```text
PASS
FAIL
REWORK
HOLD
```

## SRS-QC-003

A failed QC item MUST support controlled rework.

---

# 62. Packing Requirements

## SRS-PACK-001

System MUST support package records.

Fields:

- package ID
- project
- furniture
- panels/items
- dimensions
- status
- QR/barcode

## SRS-PACK-002

Packing SHOULD require appropriate QC completion based on tenant workflow.

---

# 63. Dispatch Requirements

## SRS-DISPATCH-001

System MUST support dispatch records.

Fields:

- dispatch ID
- project
- packages
- date
- carrier/vehicle
- status
- delivery details
- notes

---

# 64. Installation Requirements

Initial implementation MAY provide basic installation tracking:

```text
NOT_STARTED
SCHEDULED
IN_PROGRESS
COMPLETED
ISSUE
```

---

# 65. Document Requirements

System SHOULD generate:

- floor plan
- elevation
- section
- BOM
- BOQ
- quotation
- proposal
- cutlist
- manufacturing report
- panel labels

Generated documents MUST reference relevant revision/version information.

---

# 66. Document Storage

Documents MUST be associated with:

- tenant
- project
- document type
- revision
- created by
- creation timestamp

---

# 67. Revision Requirements

## SRS-REV-001

Projects MUST support revisions.

## SRS-REV-002

Furniture instances MUST support revisions where needed.

## SRS-REV-003

Manufacturing jobs MUST reference a specific revision.

## SRS-REV-004

Old revisions MUST remain readable.

## SRS-REV-005

Restoring a revision MUST create a new current revision rather than corrupting history.

---

# 68. Approval Requirements

Approval types:

```text
INTERNAL_DESIGN
CLIENT
ENGINEERING
MANUFACTURING
QC
```

Approval record:

```text
approver
status
timestamp
comments
revision
```

---

# 69. Project State Machine

Recommended:

```text
DRAFT
 ↓
DESIGN
 ↓
INTERNAL_REVIEW
 ↓
CLIENT_REVIEW
 ↓
CLIENT_APPROVED
 ↓
ENGINEERING
 ↓
PRODUCTION_READY
 ↓
MANUFACTURING_RELEASED
 ↓
IN_PRODUCTION
 ↓
COMPLETED
```

The exact workflow MAY be configurable.

---

# 70. Audit Requirements

## SRS-AUDIT-001

Critical operations MUST create audit records.

Minimum fields:

```text
id
tenant_id
user_id
entity_type
entity_id
action
before_data
after_data
timestamp
request_id
```

## SRS-AUDIT-002

Audit records SHOULD be append-only.

---

# 71. Notification Requirements

System SHOULD support:

- in-app notifications
- email notifications

Events:

- approval request
- approval completed
- manufacturing generated
- manufacturing failed
- production milestone
- QC failure
- dispatch

---

# 72. Background Job Requirements

Long-running processes MUST NOT block standard HTTP requests.

Candidate jobs:

- BOM generation for large projects
- nesting
- CNC generation
- PDF generation
- catalog import
- AI processing
- large image processing

---

# 73. Job Status

Background jobs:

```text
QUEUED
RUNNING
COMPLETED
FAILED
CANCELLED
```

Job record MUST contain error information if failed.

---

# 74. Web Worker Requirements

Browser Web Workers SHOULD be used for heavy client-side operations such as:

- geometry calculations
- large scene preparation
- image preprocessing
- client-side previews
- large data processing

---

# 75. API Requirements

Base URL:

```text
/api/v1/
```

API categories:

```text
/auth
/tenants
/users
/roles
/clients
/projects
/buildings
/floors
/rooms
/design
/furniture
/components
/materials
/bom
/boq
/pricing
/quotes
/manufacturing
/nesting
/cnc
/production
/qc
/packing
/dispatch
/documents
/notifications
```

---

# 76. API Response Standard

Success:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Error:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid dimensions",
    "details": []
  }
}
```

---

# 77. API Error Codes

Minimum codes:

```text
AUTH_REQUIRED
ACCESS_DENIED
RESOURCE_NOT_FOUND
VALIDATION_ERROR
INVALID_STATE
STALE_DATA
REVISION_CONFLICT
MANUFACTURING_LOCKED
DEPENDENCY_STALE
JOB_FAILED
FILE_INVALID
INTERNAL_ERROR
```

---

# 78. HTTP Status Requirements

Recommended:

```text
200 OK
201 Created
204 No Content
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
```

---

# 79. API Idempotency

Operations that may be retried SHOULD support idempotency.

Especially:

- manufacturing generation
- CNC generation
- release
- document generation
- production creation

---

# 80. Concurrency Requirements

Project records SHOULD contain a version number.

Example:

```text
Current version = 8
Client sends version = 7
```

System MUST reject or safely resolve stale updates rather than silently overwriting newer data.

---

# 81. Database Requirements

The database MUST use relational integrity for core entities.

Expected major entities:

```text
tenants
organizations
users
roles
permissions
user_roles
role_permissions

clients
contacts
leads
opportunities

projects
buildings
floors
rooms

design_objects

furniture_templates
furniture_template_versions
furniture_instances
furniture_components
component_rules

materials
boards
laminates
edge_bands
hardware

bom
bom_items

boq
boq_items

pricing_rules
pricing_versions
quotations

project_revisions
furniture_revisions
manufacturing_revisions

panels
cutlists
nesting_jobs
nesting_sheets
cnc_jobs

production_jobs
production_operations
production_events

qc_records
packing_records
dispatch_records

documents
document_templates

notifications
audit_logs
jobs
```

---

# 82. JSON Data Requirements

JSON MAY be used for:

- geometry
- flexible parameters
- metadata
- drilling definitions
- routing definitions
- machine-specific configuration

JSON MUST NOT replace relational fields that are frequently queried or joined.

---

# 83. Database Indexing

Frequently queried fields MUST be indexed.

Tenant-scoped queries SHOULD use composite indexes.

Examples:

```text
tenant_id + project_id
tenant_id + status
tenant_id + created_at
project_id + object_type
furniture_id + revision
```

---

# 84. Soft Delete

Soft delete SHOULD be used for business entities where historical references must remain.

Example:

```text
deleted_at
```

Released manufacturing data MUST NOT be physically deleted if it is required for traceability.

---

# 85. File Storage Requirements

The system MUST support an abstraction such as:

```text
StorageInterface
```

Initial implementation may use local filesystem.

Future implementation may support:

- S3-compatible storage
- cloud object storage

---

# 86. File Upload Requirements

Uploads MUST validate:

- extension
- MIME type
- file size
- filename
- storage path

Uploaded files MUST NOT be executable.

---

# 87. Search Requirements

Initial search MAY use MySQL.

Searchable entities:

- projects
- clients
- furniture
- materials
- panels
- production jobs

---

# 88. Import Requirements

Import workflow:

```text
Upload
 ↓
Parse
 ↓
Validate
 ↓
Preview
 ↓
Confirm
 ↓
Persist
 ↓
Audit
```

Failed rows MUST be reported.

---

# 89. Export Requirements

System SHOULD support export of:

- BOM CSV
- BOQ CSV
- cutlist CSV
- nesting data
- manufacturing data
- reports
- project data

---

# 90. Internationalization Requirements

UI text MUST be separated from business logic.

The architecture SHOULD support:

- English
- future regional languages

---

# 91. Currency Requirements

The system SHOULD support configurable currency.

Initial configuration may be:

```text
INR
```

Pricing calculations MUST use currency configuration rather than hard-coded symbols.

---

# 92. Tax Requirements

Tax must be configurable.

Initial deployment may support India GST-related configuration.

Tax calculations MUST be isolated from furniture rules.

---

# 93. Security Requirements

The system MUST implement:

- secure password hashing
- SQL parameterization
- CSRF protection
- XSS protection
- input validation
- output encoding
- authorization
- tenant isolation
- secure sessions
- file validation
- rate limiting for sensitive endpoints

---

# 94. Secrets Management

Secrets MUST NOT be committed to Git.

Configuration MUST use environment variables or secure configuration.

Provide:

```text
.env.example
```

without real credentials.

---

# 95. Logging Requirements

Logs SHOULD contain:

- timestamp
- severity
- module
- request ID
- tenant ID
- user ID
- operation
- error information

Secrets MUST NOT be logged.

---

# 96. Performance Requirements

The system SHOULD target:

- normal API responses under 500 ms where practical
- immediate UI feedback for common operations
- asynchronous processing for long tasks
- responsive 2D editing
- graceful 3D degradation

Performance targets should be measured using representative projects.

---

# 97. Scalability Requirements

Architecture SHOULD support:

- multiple tenants
- large projects
- thousands of material records
- hundreds of design objects per room
- thousands of panels per manufacturing job

---

# 98. Availability Requirements

Production environments SHOULD provide:

- monitoring
- database backup
- file backup
- health checks
- application logging
- failure alerts

---

# 99. Backup Requirements

Production MUST have:

- automated database backups
- file backups
- retention policy
- restore testing

A backup should not be considered valid until restore has been tested.

---

# 100. Testing Requirements

The application MUST include:

## Unit Tests

For:

- geometry calculations
- parametric formulas
- BOM
- BOQ
- pricing
- panel generation
- edge banding
- nesting
- state transitions

## Integration Tests

For:

- authentication
- tenant isolation
- API
- database
- manufacturing flow

## UI Tests

For critical user workflows.

---

# 101. Calculation Determinism

For identical inputs and versions:

```text
Project Revision
Furniture Revision
Template Version
Material Version
Pricing Version
Manufacturing Rules Version
```

the system MUST produce reproducible results.

---

# 102. Geometry Test Requirements

Geometry tests MUST validate:

- dimensions
- position
- transformations
- bounding boxes
- intersections
- openings
- panel sizes

Floating-point comparisons SHOULD use tolerance.

---

# 103. Manufacturing Test Requirements

Test cases MUST include:

- standard cabinet
- oversized cabinet
- minimum dimensions
- maximum dimensions
- missing material
- invalid hardware
- panel exceeding sheet size
- grain direction constraint
- edge-band combinations
- drilling
- routing
- revision change

---

# 104. Error Handling

Errors MUST be categorized.

Example:

```text
Validation
Authorization
Not Found
Conflict
Calculation
Manufacturing
External Integration
System
```

User-facing messages MUST be understandable.

Technical details may be logged but should not expose secrets.

---

# 105. Validation Framework

Validation should occur at multiple levels:

```text
UI Validation
 ↓
API Validation
 ↓
Domain Validation
 ↓
Database Constraints
```

The backend remains authoritative.

---

# 106. State Transition Validation

All major workflows MUST use explicit state transitions.

Example:

A production job in:

```text
QC
```

must not directly become:

```text
CUTTING
```

unless a permitted rework flow exists.

---

# 107. White-Label Requirements

Tenant branding must apply to:

- application
- email
- quotation
- proposal
- PDF
- document headers
- client-facing pages

---

# 108. Custom Domain Requirements

Domain resolution should follow:

```text
Incoming Host
 ↓
Tenant Resolver
 ↓
Tenant Configuration
 ↓
Application
```

The implementation should not hard-code a single customer domain.

---

# 109. Feature Flags

System SHOULD support feature flags.

Examples:

```text
enable_advanced_nesting
enable_cnc_biesse
enable_ai_floorplan
enable_component_designer
enable_mes
```

---

# 110. AI Requirements

AI integrations MUST be isolated behind service interfaces.

Potential capabilities:

- floorplan recognition
- image-to-3D
- design assistance
- automated suggestions

AI outputs MUST be:

- versioned
- editable
- reviewable
- validated

---

# 111. AI Floorplan Functional Flow

```text
Upload Image
 ↓
Create AI Job
 ↓
Process
 ↓
Detect Walls
 ↓
Detect Rooms
 ↓
Detect Doors/Windows
 ↓
Generate Geometry Proposal
 ↓
User Review
 ↓
Create Parametric Objects
 ↓
Generate 3D
```

---

# 112. AI Safety/Accuracy Requirement

AI-generated design information MUST NOT automatically become manufacturing release data.

It must pass the same engineering validation as manually created designs.

---

# 113. Document Versioning

Documents MUST reference:

- project
- revision
- template version
- creator
- creation timestamp

---

# 114. Project Export

System SHOULD support complete project export for portability.

Export MAY include:

```text
project metadata
design objects
furniture
materials
BOM
BOQ
manufacturing data
documents
```

---

# 115. Project Import

Future import should validate:

- schema version
- object references
- material references
- template references
- revisions

Invalid imports MUST fail safely.

---

# 116. Schema Versioning

Project JSON/data formats MUST have schema versions.

Example:

```text
schema_version = 1
```

Future migrations can then transform:

```text
v1 → v2 → v3
```

---

# 117. API Documentation

All API endpoints MUST be documented.

Documentation should include:

- method
- URL
- authentication
- permissions
- request body
- response
- errors
- example

OpenAPI/Swagger SHOULD be considered.

---

# 118. Architecture Documentation

Repository MUST contain:

```text
/docs
  /architecture
  /api
  /database
  /domain
  /manufacturing
  /frontend
  /deployment
```

---

# 119. Architecture Decision Records

Major technical decisions SHOULD be recorded.

Examples:

```text
ADR-001 Modular Monolith
ADR-002 Unified Project Model
ADR-003 Three.js Rendering
ADR-004 Manufacturing Snapshot
ADR-005 Nesting Adapter
ADR-006 CNC Adapter
ADR-007 Multi-Tenancy
ADR-008 Parametric Rule Engine
```

---

# 120. Cursor Initial Analysis Requirement

When Cursor receives this SRS, it MUST NOT immediately start rewriting code.

It MUST first:

1. inspect repository
2. inspect package/dependency files
3. inspect PHP architecture
4. inspect database schema
5. inspect APIs
6. inspect frontend modules
7. inspect Three.js implementation
8. inspect authentication
9. inspect RBAC
10. inspect current furniture/design logic
11. inspect manufacturing logic
12. inspect tests
13. identify gaps

Then produce:

```text
CURRENT STATE
TARGET STATE
REQUIREMENT COVERAGE
GAPS
RISKS
DEPENDENCIES
MIGRATION PLAN
IMPLEMENTATION ORDER
```

---

# 121. Cursor Requirement Traceability

Cursor MUST maintain:

| Requirement ID | Description | Module | Existing Code | New Code | DB Change | API | UI | Test | Status |
|---|---|---|---|---|---|---|---|---|---|

Every implemented requirement should be traceable.

---

# 122. Cursor Implementation Rule

For each module:

```text
Requirement
 ↓
Design
 ↓
Database
 ↓
API
 ↓
Service
 ↓
UI
 ↓
Tests
 ↓
Documentation
```

Do not implement only the UI and consider the feature complete.

---

# 123. Cursor Code Quality Rules

Cursor MUST:

- use modular ES6
- use PHP classes/services
- use repositories for database access
- validate input
- enforce authorization
- use parameterized queries
- add tests
- avoid duplicate logic
- preserve existing behavior
- document non-obvious business rules

---

# 124. Cursor Prohibited Patterns

Cursor MUST NOT:

- create giant app.js
- create giant controllers
- duplicate business rules
- hard-code material prices
- hard-code tenant configuration
- hard-code CNC formats into core logic
- use Three.js as database state
- put SQL in frontend
- trust client-side permissions
- silently overwrite production data
- delete working features without approval
- introduce unnecessary frameworks
- execute arbitrary formula code

---

# 125. Vertical Slice Requirement

Before building every furniture category, the system MUST prove the architecture with one complete furniture type.

Recommended:

## Wardrobe

Input:

```text
Width
Height
Depth
Carcass Thickness
Back Thickness
Shelf Count
Drawer Count
Shutter Count
Material
```

Output:

```text
2D
3D
Components
Panels
BOM
BOQ
Pricing
Cutlist
Nesting
```

---

# 126. Vertical Slice Acceptance Criteria

The wardrobe vertical slice is successful if:

1. user creates wardrobe
2. user enters dimensions
3. system validates dimensions
4. components are generated
5. 2D view updates
6. 3D view updates
7. materials are assigned
8. BOM is generated
9. BOQ is generated
10. price is calculated
11. panels are generated
12. cutlist is generated
13. nesting is generated
14. revision is stored

---

# 127. MVP Implementation Sequence

## Phase 1

```text
Authentication
Tenant
RBAC
Database
API
Audit
```

## Phase 2

```text
Client
Project
Building
Floor
Room
Revision
```

## Phase 3

```text
2D
Walls
Doors
Windows
3D
Selection
Undo/Redo
```

## Phase 4

```text
Furniture Templates
Parametric Engine
Components
Materials
Wardrobe
```

## Phase 5

```text
BOM
BOQ
Pricing
Quotation
```

## Phase 6

```text
Engineering
Panel Engine
Cutlist
Edge Banding
Nesting
```

## Phase 7

```text
CNC
Manufacturing Release
Production
QR
```

## Phase 8

```text
QC
Packing
Dispatch
```

## Phase 9

```text
White Label
Custom Domains
Advanced Catalog
```

## Phase 10

```text
AI
Advanced CNC
Advanced MES
Analytics
```

---

# 128. SRS Acceptance Criteria

The SRS implementation is considered aligned when:

- all P0 requirements have an implementation mapping
- all P0 requirements have test cases
- all core entities have defined ownership
- all APIs have documented permissions
- all critical calculations have deterministic tests
- manufacturing has revision control
- tenant isolation is enforced
- 2D and 3D use common data
- parametric furniture drives manufacturing
- historical quotations remain reproducible
- released manufacturing data is protected
- background processing exists for heavy operations

---

# 129. End-to-End System Acceptance Test

The complete system should eventually support:

```text
Create Tenant
 ↓
Create User
 ↓
Create Client
 ↓
Create Project
 ↓
Create Building
 ↓
Create Floor
 ↓
Create Room
 ↓
Draw Walls
 ↓
Place Door/Window
 ↓
View 3D
 ↓
Add Wardrobe
 ↓
Configure Dimensions
 ↓
Assign Materials
 ↓
Generate BOM
 ↓
Generate BOQ
 ↓
Calculate Price
 ↓
Generate Quotation
 ↓
Approve Design
 ↓
Engineering Validation
 ↓
Release Manufacturing
 ↓
Generate Cutlist
 ↓
Generate Nesting
 ↓
Generate CNC Output
 ↓
Create Production Job
 ↓
Generate QR Labels
 ↓
Track Production
 ↓
QC
 ↓
Packing
 ↓
Dispatch
```

---

# 130. Traceability Matrix — Core

| Requirement Area | Primary Domain | Critical |
|---|---|---|
| Authentication | Identity | Yes |
| Tenant Isolation | Tenant | Yes |
| RBAC | Identity | Yes |
| Project | Project | Yes |
| 2D Design | Architecture | Yes |
| 3D | Architecture | Yes |
| Parametric Furniture | Furniture | Yes |
| Materials | Catalog | Yes |
| BOM | BOM | Yes |
| BOQ | BOQ | Yes |
| Pricing | Pricing | Yes |
| Engineering | Manufacturing | Yes |
| Cutlist | Manufacturing | Yes |
| Nesting | Manufacturing | Yes |
| CNC | Manufacturing | P1 |
| MES | MES | P1 |
| QR | MES | P1 |
| QC | MES | P1 |
| Packing | MES | P1 |
| Dispatch | MES | P1 |
| AI | AI | P3 |
| White Label | Tenant | P1 |

---

# 131. Business Rule Traceability

Critical business rules MUST be represented in automated tests.

Examples:

```text
BR-001 Tenant isolation
BR-002 Parameter validation
BR-003 Dimension propagation
BR-004 BOM recalculation
BR-005 Pricing reproducibility
BR-006 Manufacturing blocker validation
BR-007 Manufacturing snapshot
BR-008 Revision protection
BR-009 Production state transition
BR-010 QC rework
```

---

# 132. Final System Behavior Principle

The system should behave as:

```text
User Input
    ↓
Domain Model
    ↓
Parametric Rules
    ↓
Derived Geometry
    ↓
Commercial Data
    ↓
Manufacturing Data
    ↓
Factory Data
```

not:

```text
User Input
 ↓
Screen
 ↓
Manual Excel
 ↓
Manual Engineering
 ↓
Manual Factory Data
```

---

# 133. Final SRS Principle

The single most important software requirement is:

> **A significant change to an authoritative project parameter must propagate to every dependent representation and identify all downstream artifacts requiring recalculation, review or re-release.**

Example:

```text
Wardrobe Width
2400 → 2700
```

The system must identify:

```text
2D       → UPDATE
3D       → UPDATE
Elevation→ UPDATE
BOM      → STALE/RECALCULATE
BOQ      → STALE/RECALCULATE
Pricing  → STALE/RECALCULATE
Cutlist  → STALE/RECALCULATE
Nesting  → STALE/RECALCULATE
CNC      → STALE/RECALCULATE
Production→ PROTECTED IF RELEASED
```

---

# 134. Final Implementation Principle

The software should be built as a **parametric design-to-manufacturing platform**, not as a collection of screens.

The core architecture must preserve:

```text
ONE PROJECT MODEL
        ↓
ONE PARAMETRIC TRUTH
        ↓
MULTIPLE DERIVED OUTPUTS
```

Those outputs include:

```text
2D
3D
BIM-like Objects
Drawings
BOM
BOQ
Pricing
Quotation
Cutlist
Nesting
CNC
MES
QR
QC
Packing
Dispatch
```

---

# 135. Final Cursor Instruction

Treat this document as a software requirement baseline.

Before implementation:

1. Analyze the existing repository.
2. Map current functionality to SRS requirements.
3. Identify missing functionality.
4. Identify architectural conflicts.
5. Identify database changes.
6. Identify API changes.
7. Identify UI changes.
8. Identify test requirements.
9. Create an implementation plan.
10. Implement incrementally.
11. Run tests after every major module.
12. Update the traceability matrix.
13. Document deviations.
14. Never silently reinterpret a requirement.
15. Ask for clarification only when a requirement creates a material business or architectural ambiguity.

The implementation must preserve the central product principle:

> **Design once, derive everything, manufacture from the same authoritative model.**
