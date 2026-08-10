# CHANGE_REQUEST_FURNITURE_CENTRIC_WORKFLOW

## Change Request ID

**CR-001**

## Title

**Furniture-Centric Design-to-Manufacturing Workflow Refactor**

## Status

**Decisions recorded (2026-08-10).** Analysis and implementation plan produced under `CR/`.  
**Application code must not change until owner replies `APPROVE CR-001 PLAN`.**

See:
- `CR/01_DECISION_REGISTER.md`
- `CR/02_ANALYSIS_AND_GAP.md`
- `CR/03_IMPLEMENTATION_PLAN.md`

---

# 1. Executive Summary

The current application workflow is approximately:

```text
Project
  ↓
Floor Design
  ↓
Room
  ↓
Furniture
  ↓
Manufacturing
  ↓
Nesting
```

The required workflow is:

```text
Project
  ↓
Furniture Module(s)
  ↓
Furniture Specification
  ↓
Internal Component Design
  ↓
2D Design
  ↓
3D Design
  ↓
Design Validation
  ↓
Manufacturing
  ↓
Panel Generation
  ↓
BOM
  ↓
Optimized Sheet Layout / Nesting
  ↓
Cutlist
  ↓
Future CNC / Production
```

The central architectural change is:

> **Furniture must become a first-class project entity and must no longer depend on Floor or Room creation.**

Floor/Room design must remain available where useful, but it becomes optional context rather than a prerequisite for furniture design and manufacturing.

---

# 2. Critical Instruction to Cursor

This is an **architectural change request**, not simply a UI feature request.

Do not immediately modify the existing UI.

Before implementation:

1. Analyze the complete repository.
2. Analyze the database/schema.
3. Analyze JSON usage.
4. Analyze the existing Project → Floor → Room → Furniture workflow.
5. Analyze the existing furniture/component model.
6. Analyze 2D rendering.
7. Analyze 3D rendering.
8. Analyze materials and finishes.
9. Analyze manufacturing.
10. Analyze panel generation.
11. Analyze BOM.
12. Analyze cutlist.
13. Analyze nesting.
14. Analyze exports.
15. Analyze tests.
16. Identify dependencies and duplicated logic.
17. Produce a current-to-target architecture map.
18. Produce a phased implementation plan.

**Do not implement until the analysis and implementation plan are completed and reviewed.**

---

# 3. Reference Output Requirements

Cursor does **not** have access to the reference PDFs/XLSX used to prepare this change request.

Therefore, do not expect external reference files to be available.

The reference outputs establish the required class of result:

### Furniture Design Output

The system must produce professional furniture drawings with:

- Plan
- Front elevation
- Back elevation where applicable
- Left/right elevation where applicable
- Sectional elevation
- Internal elevation where applicable
- Overall dimensions
- Internal dimensions
- Component dimensions
- Material callouts
- External finish
- Internal finish
- Carcass information
- Thickness
- Skirting
- Furniture depth
- Door/shutter information
- Component labels
- Title block
- Project/client information
- Designer
- Checker
- Approver
- Date
- Revision
- 3D presentation/rendered views

### Sheet Layout Output

The system must produce a visual manufacturing sheet layout containing:

- Sheet number
- Sheet dimensions
- Material
- Thickness
- Laminate/finish
- Margin
- Cutting gap/kerf
- Graphical panel placement
- Panel dimensions
- Panel/component identification
- Orientation
- Furniture/module reference
- Remaining waste/offcut area

The reference layout uses a sheet concept around 2440 × 1220 mm, but these values are examples only and **must not be hard-coded**.

### Cutlist Output

The cutlist must support, at minimum:

- Serial number
- Furniture/module
- Component
- Description
- Finishing length
- Finishing width
- Cutting length
- Cutting width
- Quantity
- Rotation/orientation
- Thickness
- Material
- Colour
- Edge-band colour
- Edge 1
- Edge 2
- Edge 3
- Edge 4
- Notes

**Finishing Size and Cutting Size are separate concepts.**

The system must calculate them using manufacturing rules.

The examples above are output requirements, not source data to hard-code.

---

# 4. Reference Data Must Never Be Hard-Coded

The reference examples are illustrative only.

Do NOT hard-code:

- client names
- project names
- furniture dimensions
- laminate codes
- material names
- sheet dimensions
- sample component dimensions
- sample cutlist rows
- sample drawing data

The application must dynamically generate outputs from actual project/furniture/manufacturing data.

---

# 5. Target User Journey

## Step 1 — Create Project

User creates:

- Project Name
- Project Code
- Client
- Description
- Designer
- Created Date
- Status

Immediately after project creation, the user must be able to add furniture.

```text
Project
  ↓
Add Furniture
```

No Floor or Room is required.

---

## Step 2 — Add Furniture Module

A project supports one or many furniture modules.

Example:

```text
Project
├── TV Unit
├── Master Wardrobe
├── Kids Wardrobe
├── Dining Unit
├── Kitchen Cabinet
└── Study Unit
```

Each furniture module is independently manageable.

---

## Step 3 — Define Furniture Specification

The user defines:

### General

- Name
- Code
- Category
- Type
- Quantity
- Width
- Height
- Depth

### Construction

- Carcass material
- Carcass thickness
- Back material
- Back thickness
- Shelf thickness
- Partition thickness
- Shutter thickness
- Plinth
- Skirting
- Construction method

### Finish

- External finish
- Internal finish
- Laminate
- Acrylic
- PU
- Veneer
- Paint
- Glass
- Metal
- Edge band

### Hardware

- Hinges
- Drawer slides
- Handles
- Connectors
- Shelf supports
- Lift-up hardware
- Sliding hardware
- Other hardware

### Manufacturing

- Grain direction
- Edge banding
- Groove
- Routing
- Drilling
- Joinery
- CNC requirements

---

# 6. Furniture Must Be a First-Class Entity

High-level relationship:

```text
Project
├── Furniture
├── Furniture
├── Furniture
├── Optional Floor Design
└── Manufacturing
```

Furniture must not require:

- Building ID
- Floor ID
- Room ID
- Wall ID

These may be optional references.

A furniture module can later be associated with a Room/Floor for placement/context.

---

# 7. Furniture Domain Model

Minimum conceptual model:

```text
Project
  ↓
Furniture
  ↓
Furniture Component Tree
  ↓
Panels / Assemblies / Hardware
```

A furniture entity should include at least:

- id
- project_id
- name
- code
- category
- type
- width
- height
- depth
- quantity
- status
- revision
- created_by
- created_at
- updated_at

Additional fields should be determined after codebase analysis.

---

# 8. Component Model

Furniture must support real internal construction.

Example:

```text
Wardrobe
├── Left Side
├── Right Side
├── Top
├── Bottom
├── Back
├── Vertical Partition
├── Shelves
├── Drawers
├── Drawer Fronts
├── Doors
├── Loft
├── Hanging Rod
└── Hardware
```

Example TV Unit:

```text
TV Unit
├── Carcass
├── Back Panel
├── Shelves
├── Drawers
├── Drawer Fronts
├── Exposed Panels
└── Hardware
```

Each component must be independently identifiable.

---

# 9. Component Data

Conceptual component fields:

- id
- furniture_id
- parent_component_id
- component_type
- name
- width
- height
- depth
- thickness
- material_id
- finish_id
- quantity
- geometry
- manufacturing_data
- status

Do not create unnecessary fields before examining the existing implementation.

Reuse existing component concepts where they are sound.

---

# 10. Single Canonical Furniture Model

This is one of the most important requirements.

There must be one canonical furniture/component model driving:

```text
                 Furniture Model
                       │
              ┌────────┼────────┐
              ↓        ↓        ↓
             2D        3D   Manufacturing
                                │
                           ┌────┼─────┐
                           ↓    ↓     ↓
                         Panels BOM Cutlist
                                      │
                                   Nesting
```

Do not create disconnected:

- 2D furniture data
- 3D furniture data
- manufacturing furniture data

that drift apart.

---

# 11. 2D Furniture Design Workspace

Furniture 2D design must be independent of floor-plan design.

Supported views:

- Plan
- Front Elevation
- Back Elevation
- Left Elevation
- Right Elevation
- Section
- Internal Elevation
- Component Detail

The UI should allow switching between views.

---

# 12. 2D Dimensioning

Dimensions must be generated from actual geometry.

Support:

- overall width
- overall height
- overall depth
- opening width
- opening height
- shelf width
- shelf depth
- shelf clearance
- drawer width
- drawer height
- door width
- partition width
- component dimensions
- clearances

Do not hard-code dimension text.

If a dimension changes, the drawing must update.

---

# 13. Professional Drawing Metadata

Drawing title block should support:

```text
TITLE:
PROJECT:
CLIENT:

FURNITURE:
CODE:
REVISION:

DESIGN BY:
CHECKED BY:
APPROVED BY:

DATE:
```

Drawing notes should support:

- materials
- finishes
- laminate codes
- carcass
- thickness
- skirting
- depth
- hardware
- component annotations
- manufacturing notes

---

# 14. 2D Export

Minimum:

- PDF

Preferred future formats:

- SVG
- DXF
- PNG/JPG

PDF should include:

- project information
- furniture information
- revision
- drawing views
- dimensions
- material information
- notes
- title block

---

# 15. 3D Furniture Design

3D must be generated from the same canonical furniture model.

Support:

- orbit
- zoom
- pan
- perspective
- front
- back
- left
- right
- top
- component visibility
- component isolation
- material visualization
- internal structure

The 3D model must represent actual furniture components, not merely an approximate box.

---

# 16. 3D Export

Minimum:

- rendered image export

Preferred:

- GLB
- GLTF

The exported representation must correspond to the current furniture revision.

---

# 17. Design Validation

Before manufacturing, validate:

- overall dimensions
- component dimensions
- component hierarchy
- material assignment
- thickness assignment
- geometry
- edge banding
- hardware
- manufacturing rules
- required parameters

Example:

```text
MANUFACTURING VALIDATION

✓ Dimensions valid
✓ Components valid
✓ Materials assigned
✓ Thickness assigned
✓ Geometry valid
✓ Edge banding valid
✓ Hardware valid
✓ Manufacturing rules valid
```

If validation fails, manufacturing must not silently proceed.

---

# 18. Manufacturing Initiation

Provide:

```text
[ INITIATE MANUFACTURING ]
```

Manufacturing pipeline:

```text
Furniture
 ↓
Component Tree
 ↓
Panel Generator
 ↓
Panel Geometry
 ↓
Edge Banding
 ↓
Machining Features
 ↓
BOM
 ↓
Cutlist
 ↓
Nesting
 ↓
Future CNC
```

---

# 19. Manufacturing Data Must Be Derived

Do not manually duplicate furniture information into manufacturing records.

Manufacturing data must be derived from:

- furniture
- components
- materials
- finishes
- manufacturing rules

Generated artifacts may be persisted as snapshots.

---

# 20. Panel Entity

Every manufacturing panel must maintain traceability.

Conceptual fields:

- panel_id
- project_id
- furniture_id
- component_id
- description
- panel_type
- finishing_length
- finishing_width
- cutting_length
- cutting_width
- quantity
- thickness
- material_id
- finish_id
- laminate_id
- grain_direction
- edge_left
- edge_right
- edge_top
- edge_bottom
- machining_data
- notes

Exact database design must be determined after repository analysis.

---

# 21. Finishing Size vs Cutting Size

The application must explicitly distinguish:

```text
FINISHING SIZE
```

from:

```text
CUTTING SIZE
```

They must be stored/calculated separately.

The calculation must use configurable manufacturing rules.

Do not hard-code sample allowances.

---

# 22. Edge Banding

Support independent edge data for:

- Top
- Bottom
- Left
- Right

Each edge may contain:

- required/not required
- material
- colour
- thickness
- rule/allowance

This information must flow through:

```text
Furniture
 ↓
Component
 ↓
Panel
 ↓
Cutlist
 ↓
Manufacturing
```

---

# 23. BOM

Generate a BOM from the canonical component/manufacturing model.

BOM should support:

- material
- component
- quantity
- dimensions
- thickness
- finish
- hardware
- consumables where applicable

BOM must remain traceable to furniture/component.

---

# 24. Sheet Definition

A raw sheet should support:

- sheet_id
- material
- thickness
- length
- width
- laminate/finish
- grain direction
- margin
- cutting gap/kerf
- usable area

Values must be configurable.

Example values such as 2440 × 1220 mm, 18 mm, 10 mm margin and 5 mm gap are illustrative only.

---

# 25. Nesting / Sheet Optimization

Provide:

```text
[ OPTIMIZE SHEETS ]
```

The optimization engine should consider:

- sheet dimensions
- material
- thickness
- laminate
- quantity
- grain direction
- rotation restrictions
- margin
- cutting gap/kerf
- edge requirements
- usable area

Output:

- sheets required
- utilization percentage
- waste percentage
- offcut area
- panel placement
- sheet assignment

---

# 26. Visual Sheet Layout

The result must be graphical.

Example:

```text
--------------------------------------------------
SHEET 1

Material: Plywood
Thickness: 18 mm
Laminate: <configured>
Sheet Size: <configured>
Margin: <configured>
Gap/Kerf: <configured>
--------------------------------------------------

+-----------------------------------------------+
|                                               |
|               PANEL A                         |
|                                               |
|-----------------------+-----------------------|
| PANEL B              | PANEL C               |
|                      |                       |
|----------------------+-----------------------|
| PANEL D              | PANEL E               |
|                                               |
+-----------------------------------------------+
```

Each panel should display, where practical:

- panel ID
- component
- furniture/module
- dimensions
- orientation

---

# 27. Nesting Manual Override

Users should be able to:

- move a panel
- rotate if permitted
- assign to another compatible sheet
- lock a panel
- reset optimization
- re-optimize

The system must validate manual placements.

Panels must not be allowed to overlap or exceed valid sheet boundaries.

---

# 28. Cutlist

Generate a professional cutlist.

Minimum columns:

| Field |
|---|
| SL NO |
| FURNITURE |
| COMPONENT |
| DESCRIPTION |
| FINISHING LENGTH |
| FINISHING WIDTH |
| CUTTING LENGTH |
| CUTTING WIDTH |
| QTY |
| ROTATION |
| THICKNESS |
| MATERIAL |
| COLOUR |
| EDGE BAND COLOUR |
| EDGE 1 |
| EDGE 2 |
| EDGE 3 |
| EDGE 4 |
| NOTES |

The exact presentation can be adapted to the existing application.

---

# 29. Cutlist Views

Support:

### Furniture Cutlist

Cutlist for one furniture module.

### Selected Furniture Cutlist

Cutlist for selected modules.

### Project Cutlist

Consolidated cutlist for compatible project manufacturing.

Filters:

- furniture
- component
- material
- thickness
- finish
- manufacturing job
- sheet

---

# 30. Project-Level Manufacturing

Users may manufacture:

### One furniture module

or

### Multiple furniture modules together

Example:

```text
Project
├── TV Unit
├── Wardrobe
└── Dining Unit
```

User selects:

```text
[x] TV Unit
[x] Wardrobe
[ ] Dining Unit
```

and runs optimization.

Only compatible panels may be grouped.

Compatibility should consider:

- material
- thickness
- laminate/finish
- grain requirements
- manufacturing rules

Every panel must retain:

```text
Project
 ↓
Furniture
 ↓
Component
```

traceability.

---

# 31. Revision Management

Furniture must support revisions.

Example:

```text
Wardrobe
R01
 ↓
R02
 ↓
R03
```

Changes to:

- dimensions
- components
- materials
- finishes
- hardware
- doors
- drawers
- shelves

should be revision-aware.

Previous manufacturing outputs must remain traceable.

---

# 32. Artifact Status

Derived artifacts should support:

- NOT_GENERATED
- CURRENT
- STALE
- INVALID

Example:

```text
Furniture = CURRENT
2D = CURRENT
3D = CURRENT
BOM = STALE
Panels = STALE
Cutlist = STALE
Nesting = STALE
```

If the user changes a furniture dimension, downstream artifacts must become stale or be regenerated.

---

# 33. Manufacturing Snapshot

When manufacturing is initiated, create a snapshot containing:

- furniture revision
- component definitions
- dimensions
- materials
- finishes
- hardware
- manufacturing rules
- panels
- BOM
- cutlist
- nesting data

Historical manufacturing data must not silently change because the user edited the furniture later.

---

# 34. Floor Design Decoupling

Do not delete the current floor/room functionality.

Change the dependency.

Current:

```text
Project
 ↓
Floor
 ↓
Room
 ↓
Furniture
```

Target:

```text
Project
├── Furniture
│
└── Optional Floor
      ↓
     Room
       ↓
   Optional Furniture Placement
```

Furniture may optionally reference:

- building
- floor
- room
- wall
- placement coordinates

but these references must be nullable/optional.

---

# 35. JSON Refactoring

The current application contains significant JSON usage.

Do not perform a blind global JSON rewrite.

First inventory all JSON.

Classify:

### A. Canonical Business Data

Examples:

- project
- furniture
- component
- manufacturing job

These should not exist only inside opaque JSON.

### B. Configuration

May remain JSON if appropriate.

### C. Geometry

May remain JSON where suitable.

### D. UI State

May remain JSON.

### E. Cache / Generated State

May remain JSON where appropriate.

### F. Snapshots

May use JSON deliberately.

### G. Legacy

Identify migration/deprecation requirements.

---

# 36. Database Strategy

Before changing the schema:

1. Inspect existing tables.
2. Identify existing furniture tables.
3. Identify component tables.
4. Identify manufacturing tables.
5. Identify JSON columns.
6. Identify foreign keys.
7. Identify duplicated entities.
8. Identify migration risks.

Do not create duplicate tables if existing structures can be safely evolved.

---

# 37. API Strategy

Target resource concepts:

```text
POST /api/projects

GET /api/projects/{id}

POST /api/projects/{id}/furniture

GET /api/furniture/{id}

PUT /api/furniture/{id}

GET /api/furniture/{id}/components

POST /api/furniture/{id}/components

GET /api/furniture/{id}/2d

GET /api/furniture/{id}/3d

POST /api/furniture/{id}/validate

POST /api/furniture/{id}/manufacturing

POST /api/manufacturing/{id}/panels

POST /api/manufacturing/{id}/optimize

GET /api/manufacturing/{id}/sheets

GET /api/manufacturing/{id}/cutlist

POST /api/manufacturing/{id}/cutlist/export
```

These are conceptual endpoints.

Adapt them to the existing application architecture.

Do not create duplicate APIs unnecessarily.

---

# 38. UI Navigation

Recommended project navigation:

```text
PROJECT
├── Overview
├── Furniture
├── Design
├── Manufacturing
│   ├── Components / Panels
│   ├── BOM
│   ├── Sheet Optimization
│   ├── Cutlist
│   └── CNC
├── Documents
└── Revisions
```

Floor Design becomes optional/contextual.

---

# 39. Furniture Workspace

Recommended layout:

```text
--------------------------------------------------
Furniture: Master Bedroom Wardrobe
Code: WARD-001
Revision: R03
Status: DESIGN
--------------------------------------------------

[ SPECIFICATION ]
[ COMPONENTS ]
[ 2D ]
[ 3D ]
[ MATERIALS ]
[ MANUFACTURING ]

--------------------------------------------------
|                                                |
|                DESIGN CANVAS                   |
|                                                |
--------------------------------------------------

Right panel:

Dimensions
Width:
Height:
Depth:

Materials
Carcass:
External:
Internal:

--------------------------------------------------

[ SAVE ]
[ EXPORT 2D ]
[ EXPORT 3D ]
[ VALIDATE ]
[ INITIATE MANUFACTURING ]
```

---

# 40. Manufacturing Workspace

```text
--------------------------------------------------
MANUFACTURING
--------------------------------------------------

Furniture:

[x] TV Unit
[x] Wardrobe
[ ] Dining Unit

--------------------------------------------------

Validation

✓ Geometry
✓ Material
✓ Thickness
✓ Components
✓ Edge Banding
✓ Hardware

--------------------------------------------------

[ GENERATE PANELS ]
[ GENERATE BOM ]
[ GENERATE CUTLIST ]
[ OPTIMIZE SHEETS ]

--------------------------------------------------

Optimization

Sheets Required: X
Utilization: XX%
Waste: XX%
Panels: XX

--------------------------------------------------

[ VIEW SHEETS ]
[ VIEW CUTLIST ]
[ EXPORT PDF ]
[ EXPORT EXCEL ]
```

---

# 41. Export Packages

## Furniture Design Package

PDF should contain:

1. Cover
2. Furniture summary
3. Specification
4. Plan
5. Front elevation
6. Side elevation
7. Section
8. Internal views
9. Dimensions
10. Material/finish information
11. Notes
12. 3D rendered views
13. Revision/title block

## Manufacturing Package

Should support:

- furniture summary
- component list
- panel list
- BOM
- cutlist
- material summary
- edge-band summary
- hardware
- sheet optimization
- sheet layouts
- future CNC information

---

# 42. Backward Compatibility

Existing projects must be considered.

Before migration, determine:

- existing project structure
- existing floor structure
- existing furniture records
- JSON format versions
- existing manufacturing records
- existing nesting records

Define whether each existing project should be:

- migrated automatically
- migrated on open
- retained in legacy mode
- manually migrated

Do not destroy existing customer/project data.

---

# 43. Migration Requirements

Migration must be:

- reversible where practical
- logged
- testable
- versioned

Provide:

```text
Old Model
   ↓
Migration Layer
   ↓
New Canonical Model
```

Do not silently transform production data.

---

# 44. Security and Authorization

Existing RBAC must be preserved.

At minimum, verify permissions around:

- create project
- create furniture
- edit furniture
- approve design
- initiate manufacturing
- optimize sheets
- generate cutlist
- export manufacturing package
- revise furniture

Do not introduce authorization bypasses during the refactor.

---

# 45. Audit Requirements

Track significant actions:

- furniture created
- furniture updated
- component added
- component removed
- material changed
- design revised
- manufacturing initiated
- panels generated
- nesting optimized
- cutlist generated
- export created

Existing audit mechanisms should be reused where possible.

---

# 46. Testing Strategy

Testing must cover:

## Unit

- dimension calculations
- component calculations
- panel generation
- finishing size
- cutting size
- edge banding
- BOM
- nesting calculations
- utilization
- waste
- stale-state propagation

## Integration

- Project → Furniture
- Furniture → Components
- Components → 2D
- Components → 3D
- Furniture → Manufacturing
- Manufacturing → Panels
- Panels → BOM
- Panels → Cutlist
- Panels → Nesting

## UI

- create project
- add furniture
- edit dimensions
- add components
- view 2D
- view 3D
- validate
- manufacture
- optimize
- view cutlist
- export

## Regression

Existing floor/room functionality must continue to work.

---

# 47. Acceptance Criteria

## AC-001

A project can be created without a floor.

## AC-002

Furniture can be added directly to a project.

## AC-003

Multiple furniture modules can exist in one project.

## AC-004

Furniture can exist without a Room ID.

## AC-005

Furniture has structured specifications.

## AC-006

Furniture supports internal components.

## AC-007

2D drawings are generated from canonical furniture data.

## AC-008

2D dimensions update when furniture dimensions change.

## AC-009

3D is generated from the same canonical furniture model.

## AC-010

2D can be exported to PDF.

## AC-011

3D can be exported as a rendered image.

## AC-012

Manufacturing can be initiated from furniture.

## AC-013

Panels are generated from components.

## AC-014

Finishing and cutting sizes are distinct.

## AC-015

Edge banding is represented per edge.

## AC-016

BOM is generated from canonical manufacturing data.

## AC-017

Nesting produces visual sheet layouts.

## AC-018

Sheet layout shows sheet/material/thickness/finish/margin/gap.

## AC-019

Panels display identity and dimensions.

## AC-020

Cutlist contains the required manufacturing fields.

## AC-021

Panels are traceable to furniture and components.

## AC-022

Multiple compatible furniture modules can be optimized together.

## AC-023

Changing furniture marks dependent manufacturing artifacts stale.

## AC-024

Previous manufacturing revisions remain traceable.

## AC-025

Floor/Room functionality remains available.

## AC-026

Furniture does not require Floor/Room.

## AC-027

Existing production/customer data is not destroyed.

## AC-028

Existing RBAC continues to apply.

## AC-029

Existing audit mechanisms continue to work.

---

# 48. Required Cursor Phase 1 Deliverable

Before changing application code, Cursor must produce:

## 48.1 Architecture Map

Document:

- frontend
- backend
- database
- APIs
- services
- JSON
- geometry
- rendering
- manufacturing
- nesting
- exports

## 48.2 Current Workflow

Document:

```text
Current Project
    ↓
Current Floor
    ↓
Current Room
    ↓
Current Furniture
    ↓
Current Manufacturing
    ↓
Current Nesting
```

## 48.3 Dependency Map

Identify every place where furniture depends on:

- floor
- room
- wall
- building

## 48.4 JSON Inventory

Produce:

| JSON Location | Purpose | Consumer | Canonical? | Proposed Treatment |
|---|---|---|---|---|

## 48.5 Database Inventory

Produce:

| Table | Purpose | Related Entities | JSON Columns | Migration |
|---|---|---|---|---|

## 48.6 Existing Engine Inventory

Document:

- 2D engine
- 3D engine
- rules engine
- component engine
- panel engine
- BOM engine
- cutlist engine
- nesting engine
- export engine

## 48.7 Gap Analysis

Produce:

| Domain | Current | Target | Gap | Risk | Proposed Change |
|---|---|---|---|---|---|

---

# 49. Required Cursor Phase 2 Deliverable

After analysis, produce:

## Target Architecture

Show:

```text
Project
│
├── Furniture
│   ├── Specification
│   ├── Components
│   ├── Materials
│   ├── Hardware
│   ├── 2D
│   ├── 3D
│   ├── BOM
│   ├── Panels
│   └── Manufacturing
│
├── Optional Floor
│   └── Room
│       └── Optional Furniture Placement
│
└── Manufacturing Jobs
    ├── Panels
    ├── BOM
    ├── Sheets
    ├── Nesting
    └── Cutlist
```

---

# 50. Required Cursor Phase 3 Deliverable

Produce a phased implementation plan.

Each phase must specify:

- objective
- dependencies
- affected files
- affected tables
- API changes
- frontend changes
- backend changes
- migration
- tests
- rollback
- risks

Recommended phases:

### Phase 1
Architecture analysis

### Phase 2
Canonical furniture domain model

### Phase 3
Decouple furniture from floor/room

### Phase 4
Furniture specification/component model

### Phase 5
2D integration

### Phase 6
3D integration

### Phase 7
Manufacturing/panel generation

### Phase 8
BOM

### Phase 9
Cutlist

### Phase 10
Nesting/sheet optimization

### Phase 11
Exports

### Phase 12
Migration/backward compatibility

### Phase 13
Regression and acceptance testing

---

# 51. Conflict Handling

If Cursor discovers an existing implementation that conflicts with this request:

DO NOT silently choose.

Report:

```text
CONFLICT #X

Existing Behavior:
...

New Requirement:
...

Affected Code:
...

Affected Data:
...

Technical Impact:
...

Options:
A. ...
B. ...

Recommendation:
...

Decision Required:
YES
```

Stop before making an irreversible decision.

---

# 52. Non-Goals

This change does NOT mean:

- delete Floor Design
- delete Rooms
- delete Walls
- delete existing 3D
- delete manufacturing
- delete nesting
- delete JSON globally
- rewrite the entire application
- replace all existing APIs
- discard existing projects

The objective is to:

**Decouple → Normalize where necessary → Reuse → Connect → Preserve**

---

# 53. Architectural Principles

## Principle 1 — Furniture First

Furniture is a primary business entity.

## Principle 2 — Floor Is Optional

Furniture must work without Floor/Room.

## Principle 3 — One Source of Truth

The canonical furniture/component model drives all downstream artifacts.

## Principle 4 — Derived Data Must Be Traceable

Every panel, BOM row, cutlist row and nesting placement must trace back to furniture/components.

## Principle 5 — Generated Data Has Lifecycle

Artifacts can be:

- current
- stale
- invalid
- historical

## Principle 6 — Do Not Destroy Working Functionality

Reuse existing engines wherever technically sound.

## Principle 7 — Do Not Blindly Rewrite JSON

Classify before migrating.

## Principle 8 — Do Not Hard-Code Reference Examples

Examples define expected output, not application data.

---

# 54. Final Target Workflow

The final user experience must be:

```text
CREATE PROJECT
      ↓
ADD ONE OR MORE FURNITURE MODULES
      ↓
DEFINE FURNITURE SPECIFICATION
      ↓
DEFINE INTERNAL COMPONENTS
      ↓
DESIGN / EDIT 2D
      ↓
GENERATE / REVIEW 3D
      ↓
EXPORT DESIGN
      ↓
VALIDATE
      ↓
INITIATE MANUFACTURING
      ↓
GENERATE PANELS
      ↓
GENERATE BOM
      ↓
OPTIMIZE SHEETS
      ↓
GENERATE CUTLIST
      ↓
EXPORT MANUFACTURING PACKAGE
      ↓
FUTURE CNC / PRODUCTION
```

Optional contextual workflow:

```text
Project
   ↓
Optional Floor
   ↓
Optional Room
   ↓
Optional Furniture Placement
```

The primary FMOS workflow must remain:

```text
PROJECT
   ↓
FURNITURE
   ↓
DESIGN
   ↓
MANUFACTURING
```

---

# 55. Final Architectural Outcome

The target architecture is:

```text
                         PROJECT
                            │
             ┌──────────────┴──────────────┐
             │                             │
             ▼                             ▼
        FURNITURE                     FLOOR DESIGN
             │                         (OPTIONAL)
             │                             │
       SPECIFICATION                     ROOM
             │                             │
       COMPONENT TREE              OPTIONAL PLACEMENT
             │
      ┌──────┼──────┐
      ▼      ▼      ▼
     2D     3D   MANUFACTURING
                    │
          ┌─────────┼──────────┐
          ▼         ▼          ▼
        PANELS      BOM      CUTLIST
          │
       NESTING
          │
        SHEETS
          │
       CNC/PRODUCTION
```

The core requirement is:

> **One canonical Furniture + Component model must drive 2D, 3D, Manufacturing, Panels, BOM, Cutlist and Nesting.**

Do not implement the target as independent disconnected modules.

---

# 56. Definition of Done

This change is complete only when:

- Project can be created without Floor.
- Furniture can be added directly to Project.
- Multiple furniture modules are supported.
- Furniture has structured specifications.
- Internal components are supported.
- 2D furniture drawings are generated.
- 2D dimensions are derived from geometry.
- 2D exports work.
- 3D is generated from the canonical model.
- 3D exports work.
- Manufacturing can be initiated directly from furniture.
- Panels are generated from components.
- BOM is generated.
- Finishing and cutting sizes are correctly separated.
- Edge banding is represented.
- Nesting generates visual sheet layouts.
- Sheet optimization provides utilization/waste metrics.
- Cutlist is generated from manufacturing data.
- Furniture/component traceability exists throughout manufacturing.
- Revision/stale-state handling works.
- Manufacturing snapshots exist.
- Existing Floor/Room functionality remains available.
- Existing projects are protected/migratable.
- Existing RBAC is preserved.
- Existing audit behavior is preserved.
- Automated tests cover the new workflow.
- Regression tests pass.
- No critical architectural dependency remains that forces Furniture to require Floor/Room.
