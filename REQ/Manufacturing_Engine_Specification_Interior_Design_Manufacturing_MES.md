# Manufacturing Engine Specification
## End-to-End Interior Design, Parametric Furniture, Factory Production & MES Platform

**Document ID:** MFG-ENG-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, Manufacturing Engineers, CNC Engineers, Factory Managers, Production Supervisors, QA, Procurement, MES Developers  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Units:** mm, m, m², m³, pcs, kg, sheets, rolls, minutes, hours  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the **Manufacturing Engine** that converts approved interior/furniture engineering data into executable factory production information.

The Manufacturing Engine must bridge:

```text
Design
 ↓
Parametric Furniture
 ↓
BOM
 ↓
Manufacturing BOM
 ↓
Panel / Component Generation
 ↓
Cutlist
 ↓
Nesting
 ↓
Machining Operations
 ↓
CNC / CAM Outputs
 ↓
Panel Identification
 ↓
Production Jobs
 ↓
MES Shop Floor
 ↓
Quality
 ↓
Assembly
 ↓
Packing
 ↓
Dispatch
```

The engine is intended for:

```text
Modular Kitchens
Wardrobes
Storage Units
Vanity Units
TV Units
Office Furniture
Retail Fixtures
Custom Cabinetry
Panel Furniture
Factory-Manufactured Interior Products
```

---

# 2. Core Principle

The Manufacturing Engine must be **engineering-driven, deterministic, revision-controlled and machine-aware**.

It must NOT require factory staff to manually reinterpret a 3D design into production instructions.

The approved parametric model and released BOM are the engineering source of truth.

---

# 3. Manufacturing Source of Truth

The manufacturing pipeline consumes:

```text
Project
Design Revision
Furniture Revision
Parametric Components
Material Catalog
Hardware Catalog
BOM Revision
Manufacturing Rules
Machine Profiles
Factory Configuration
```

and generates:

```text
Manufacturing BOM
Panels
Cutlist
Edge Banding
Drilling
Routing
Grooving
Nesting
CNC Programs
Labels
Production Jobs
Quality Checks
```

---

# 4. Manufacturing Architecture

```text
                    APPROVED DESIGN
                          │
                          ↓
                  PARAMETRIC MODEL
                          │
                          ↓
                         BOM
                          │
                          ↓
              MANUFACTURING ENGINE
                          │
       ┌──────────────────┼──────────────────┐
       ↓                  ↓                  ↓
    PANELS             HARDWARE           PROFILES
       │                  │                  │
       └──────────────────┼──────────────────┘
                          ↓
                     CUTLIST ENGINE
                          ↓
                    NESTING ENGINE
                          ↓
                 MACHINING ENGINE
                          ↓
                  CNC/CAM GENERATOR
                          ↓
                    LABEL ENGINE
                          ↓
                   PRODUCTION JOB
                          ↓
                         MES
```

---

# 5. Manufacturing Engine Responsibilities

The engine must handle:

```text
Manufacturing BOM generation
Component manufacturing classification
Panel generation
Panel dimension calculation
Edge-band calculation
Machining operation generation
Hardware drilling
Grooving
Routing
Nesting
CNC output
DXF output
G-code where supported
Machine-specific post-processing
Panel labels
Production jobs
Routing/work centers
Quality checkpoints
Production status
Rework
Scrap
Traceability
```

---

# 6. Non-Responsibilities

The Manufacturing Engine should NOT own:

```text
customer quotation pricing
design geometry authoring
catalog master ownership
CRM
sales pipeline
accounting
invoice generation
```

It may consume data from those modules.

---

# 7. Manufacturing Release

Only approved revisions may enter production.

Required state:

```text
DESIGN_APPROVED
BOM_APPROVED
MANUFACTURING_READY
```

---

# 8. Manufacturing Revision

Every manufacturing release must reference:

```text
project_revision
furniture_revision
bom_revision
catalog_version
manufacturing_rule_version
machine_profile_version
```

---

# 9. Immutable Release

Once released to production:

```text
DO NOT OVERWRITE
```

Create:

```text
Manufacturing Revision N+1
```

for changes.

---

# 10. Manufacturing Status

Support:

```text
DRAFT
VALIDATING
READY
RELEASED
IN_PRODUCTION
PARTIALLY_COMPLETE
COMPLETED
BLOCKED
CANCELLED
SUPERSEDED
```

---

# 11. Manufacturing BOM

Manufacturing BOM should contain:

```text
assembly
subassembly
panel
hardware
edge band
profile
glass
stone
accessory
consumable
```

---

# 12. Manufacturing Item Types

Minimum:

```text
PANEL
EDGE_BAND
HARDWARE
PROFILE
GLASS
STONE
ASSEMBLY
SUB_ASSEMBLY
CONSUMABLE
PACKAGING
EXTERNAL_COMPONENT
```

---

# 13. Manufacturing Item Identity

Every manufacturing item must have:

```text
manufacturing_item_id
item_code
logical_key
description
source_bom_item_id
source_component_id
material_id
variant_id
quantity
uom
revision
```

---

# 14. Panel Manufacturing Model

Each panel must store:

```text
panel_id
panel_code
length
width
thickness
material
finish
grain_direction
quantity
```

---

# 15. Panel Orientation

Support:

```text
grain_direction
grain_required
rotation_allowed
rotation_forbidden
```

---

# 16. Panel Dimensions

Panel dimensions must be generated from parametric component rules.

Example:

```text
Cabinet width
- side thickness
- required clearance
```

The exact formula must come from the component rule, not hard-coded globally.

---

# 17. Manufacturing Allowance

Support:

```text
cutting allowance
edge allowance
machining allowance
finishing allowance
```

Allowances must be configurable.

---

# 18. Finished vs Raw Dimensions

Store separately:

```text
finished_length
finished_width
finished_thickness

raw_length
raw_width
raw_thickness
```

---

# 19. Panel Grain

Support:

```text
NO_GRAIN
GRAIN_HORIZONTAL
GRAIN_VERTICAL
GRAIN_FIXED
GRAIN_ANY_DIRECTION
```

---

# 20. Finish Orientation

Support:

```text
TOP
BOTTOM
FRONT
BACK
LEFT
RIGHT
```

where required for manufacturing.

---

# 21. Material Compatibility

Validate:

```text
panel thickness
machine capability
edge band compatibility
drilling compatibility
routing compatibility
finish compatibility
```

---

# 22. Panel Edge Specification

Each panel edge must support:

```text
edge_1
edge_2
edge_3
edge_4
```

Each edge can specify:

```text
none
edge band
PVC
ABS
veneer
solid wood
profile
```

---

# 23. Edge Band Thickness

Store:

```text
edge_band_material
edge_band_thickness
edge_band_width
```

---

# 24. Edge Band Length

Calculate from selected panel edges.

Example:

```text
Top + Bottom + Left + Right
```

must be represented explicitly rather than assuming all four edges.

---

# 25. Edge Band Processing

Support:

```text
edge banding
trimming
scraping
polishing
```

as machine operations where applicable.

---

# 26. Hardware Manufacturing Data

Hardware should reference:

```text
catalog hardware
mounting rule
drilling template
required quantity
positioning rule
```

---

# 27. Hardware Placement

Parametric rules should define:

```text
distance from edge
distance from top
distance between holes
clearance
orientation
```

---

# 28. Drilling Operations

Each drilling operation should include:

```text
operation_id
panel_id
face
x
y
diameter
depth
tool
operation_type
```

---

# 29. Drilling Types

Support:

```text
THROUGH_HOLE
BLIND_HOLE
HINGE_CUP
DOWEL
CAM
SCREW
SHELF_PIN
MINIFIX
CUSTOM
```

---

# 30. Routing Operations

Support:

```text
slot
groove
profile
pocket
contour
cutout
```

---

# 31. Groove Operation

Fields:

```text
start_x
start_y
end_x
end_y
depth
width
tool
face
```

---

# 32. Pocket Operation

Fields:

```text
x
y
width
height
depth
corner_radius
tool
face
```

---

# 33. Contour Operation

Support:

```text
closed path
open path
internal contour
external contour
```

---

# 34. Tooling

Tool master:

```text
tool_id
tool_code
tool_type
diameter
length
cutting_depth
machine_compatibility
```

---

# 35. Tool Assignment

Operation rules can select:

```text
specific tool
tool class
automatic compatible tool
```

---

# 36. Machine Profile

Each machine must have:

```text
machine_id
machine_code
machine_type
manufacturer
model
controller
post_processor
```

---

# 37. Machine Capabilities

Store:

```text
max_length
max_width
max_thickness
axis_count
tool_count
vacuum_support
drilling_support
routing_support
grooving_support
```

---

# 38. Machine Constraints

Validate:

```text
panel dimensions
material
thickness
tool availability
axis capability
operation support
```

---

# 39. Machine Types

Support:

```text
PANEL_SAW
CNC_ROUTER
POINT_TO_POINT
EDGE_BANDER
DRILLING_MACHINE
BORING_MACHINE
SAW
CUTTING_CENTER
```

---

# 40. Machine Profiles

Support major factory machine ecosystems.

The system must NOT hard-code vendor-specific assumptions.

Use:

```text
machine profile
post processor
```

Examples may include:

```text
Biesse
Homag
KDT
SCM
Generic CNC
```

---

# 41. Post Processor

A post processor converts canonical machining operations into machine-specific output.

Architecture:

```text
Canonical Operations
        ↓
Post Processor
        ↓
Machine File
```

---

# 42. Canonical Manufacturing Model

Never generate vendor-specific operations directly from UI code.

Use:

```text
ManufacturingOperation
```

as the intermediate representation.

---

# 43. Operation Types

Minimum:

```text
CUT
DRILL
ROUTE
GROOVE
POCKET
EDGE_BAND
SAW
TRIM
PROFILE
```

---

# 44. Operation Sequence

Operations must have:

```text
sequence_number
```

Example:

```text
1 CUT
2 EDGE BAND
3 DRILL
4 GROOVE
5 ROUTE
```

Actual sequence depends on machine/process rules.

---

# 45. Work Center

Work center master:

```text
work_center_id
code
name
type
factory
capacity
```

---

# 46. Manufacturing Routing

A product may have routing:

```text
Cutting
 ↓
Edge Banding
 ↓
Drilling
 ↓
Routing
 ↓
Assembly
 ↓
Quality
 ↓
Packing
```

---

# 47. Routing Version

Routing must be versioned:

```text
routing_version
effective_from
effective_to
```

---

# 48. Work Center Assignment

Each operation maps to:

```text
work_center
machine
operator skill
```

---

# 49. Operation Time

Support:

```text
setup_time
run_time
handling_time
inspection_time
```

---

# 50. Cycle Time

Store:

```text
cycle_time
```

and optionally calculate:

```text
total_time =
setup_time + cycle_time × quantity + handling_time
```

---

# 51. Production Capacity

MES integration should calculate:

```text
required_hours
available_hours
capacity
```

---

# 52. Cutlist

Cutlist is a manufacturing output derived from panels.

Each cutlist row:

```text
panel_code
material
thickness
length
width
quantity
grain
edge_band
```

---

# 53. Cutlist Grouping

Group compatible panels by:

```text
material
thickness
finish
grain
sheet_size
```

---

# 54. Cutlist Optimization Input

Nesting receives:

```text
panel requirements
sheet inventory
sheet dimensions
material
grain
kerf
trim allowance
rotation rules
```

---

# 55. Nesting Engine

Nesting must optimize:

```text
material utilization
waste
number of sheets
cut efficiency
grain constraints
```

---

# 56. Nesting Algorithm

Architecture must permit multiple algorithms:

```text
Guillotine
MaxRects
Skyline
First Fit
Best Fit
Hybrid
```

The algorithm should be replaceable.

---

# 57. Nesting Output

Return:

```text
sheet_id
material
sheet_size
placed_panels
x
y
rotation
waste
utilization
```

---

# 58. Nesting Result Version

Store:

```text
nesting_revision
algorithm
algorithm_version
input_hash
output_hash
```

---

# 59. Nesting Constraints

Support:

```text
minimum trim
kerf
grain
rotation
defect zones
edge restrictions
clamp zones
machine zones
```

---

# 60. Sheet Defect Zones

Optional sheet defects:

```text
x
y
width
height
```

Nesting must avoid them.

---

# 61. Kerf

Kerf is machine/profile-specific.

Store:

```text
kerf_width
```

and include it in optimization.

---

# 62. Sheet Trim

Support:

```text
left_trim
right_trim
top_trim
bottom_trim
```

---

# 63. Remnant Management

Track leftover sheet pieces:

```text
remnant_id
material
thickness
length
width
location
status
```

---

# 64. Remnant Reuse

Nesting may use valid remnants before new sheets according to factory policy.

---

# 65. Remnant Rules

Minimum remnant size must be configurable.

---

# 66. Material Inventory Integration

Manufacturing engine may query:

```text
available sheets
available remnants
available hardware
```

but inventory remains a separate system of record.

---

# 67. Shortage Detection

Before release:

```text
required material
vs
available material
```

produce:

```text
AVAILABLE
PARTIAL
SHORTAGE
```

---

# 68. Manufacturing Readiness

A job is READY only when required validations pass.

---

# 69. Manufacturing Validation

Validate:

```text
missing material
invalid panel
invalid dimension
unsupported thickness
unsupported machine
missing tool
missing operation
missing hardware
missing routing
invalid nesting
```

---

# 70. Validation Severity

Support:

```text
INFO
WARNING
ERROR
BLOCKER
```

---

# 71. Manufacturing Release Checklist

Before release:

```text
[ ] BOM approved
[ ] Materials resolved
[ ] Panels generated
[ ] Dimensions validated
[ ] Edge bands resolved
[ ] Hardware resolved
[ ] Machining operations resolved
[ ] Machine selected
[ ] Nesting completed
[ ] CNC output generated
[ ] Labels generated
[ ] Routing generated
[ ] Quality plan available
```

---

# 72. Production Job

Production job fields:

```text
production_job_id
job_number
project_id
manufacturing_revision
priority
planned_start
planned_end
status
factory
```

---

# 73. Production Job Structure

```text
Production Job
 ├── Work Orders
 │    ├── Cutting
 │    ├── Edge Banding
 │    ├── Drilling
 │    ├── Routing
 │    ├── Assembly
 │    └── Packing
```

---

# 74. Work Order

Fields:

```text
work_order_id
production_job_id
work_center_id
machine_id
sequence
planned_qty
completed_qty
scrap_qty
status
```

---

# 75. Work Order Status

```text
PLANNED
READY
STARTED
PAUSED
COMPLETED
BLOCKED
CANCELLED
```

---

# 76. Production Priority

Support:

```text
LOW
NORMAL
HIGH
URGENT
```

---

# 77. Scheduling

Support:

```text
planned start
planned end
machine availability
work center capacity
priority
```

Advanced scheduling may be a separate module.

---

# 78. Batch Manufacturing

Support batching by:

```text
material
thickness
finish
machine
customer
production date
```

---

# 79. Batch Job

Example:

```text
Batch:
18mm White MDF

Panels:
P001
P002
P003
...
```

---

# 80. Panel Identity

Every physical panel must have a unique:

```text
panel_id
```

---

# 81. Panel Code

Human-readable:

```text
PRJ001-F001-P012
```

Format should be configurable.

---

# 82. QR / Barcode

Each panel label may contain:

```text
panel_id
job_id
project_code
furniture_code
revision
```

Do not place confidential cost data in the code.

---

# 83. Label Content

Label should support:

```text
Project
Room
Furniture
Panel Code
Panel Description
Dimensions
Material
Finish
Grain
Edge Band
QR
Barcode
Revision
```

---

# 84. Label Formats

Support:

```text
A4
A5
thermal label
custom label size
```

---

# 85. Label Printing

Support:

```text
single
batch
reprint
```

Reprints must be audited.

---

# 86. Label Status

```text
NOT_PRINTED
PRINTED
REPRINTED
DAMAGED
REPLACED
```

---

# 87. Scan Events

Each scan records:

```text
panel_id
work_order_id
stage
user_id
timestamp
device_id
status
```

---

# 88. Production Stages

Minimum:

```text
CUTTING
EDGE_BANDING
DRILLING
ROUTING
FINISHING
ASSEMBLY
QUALITY
PACKING
SHIPPING
```

---

# 89. Panel Lifecycle

```text
CREATED
RELEASED
CUT
EDGE_BANDED
DRILLED
ROUTED
ASSEMBLED
QC_PASSED
PACKED
SHIPPED
```

---

# 90. Missing Panel Detection

If expected panel is not scanned at the next stage:

```text
MISSING
```

or:

```text
NOT_RECEIVED
```

based on workflow.

---

# 91. Wrong Panel Detection

Scan must validate:

```text
panel belongs to job
panel belongs to stage
panel revision matches
```

Wrong panel must be blocked.

---

# 92. Rework

Support:

```text
rework_reason
source_panel
new_panel
operation
status
```

---

# 93. Scrap

Support:

```text
scrap_reason
quantity
material
panel_id
stage
operator
```

---

# 94. Scrap Classification

```text
CUTTING_DEFECT
MATERIAL_DEFECT
MACHINING_ERROR
FINISH_DEFECT
ASSEMBLY_ERROR
TRANSPORT_DAMAGE
OTHER
```

---

# 95. Scrap Replacement

If a panel is scrapped:

```text
create replacement panel
```

linked to:

```text
original panel
scrap event
manufacturing revision
```

---

# 96. Engineering Change During Production

If design changes after production begins:

```text
old manufacturing revision remains intact
new revision created
impact analysis generated
```

---

# 97. Change Impact

Identify:

```text
completed panels
in-process panels
not-started panels
obsolete panels
```

---

# 98. Change Decision

Factory must be able to choose:

```text
CONTINUE_OLD_REVISION
STOP_AND_REWORK
STOP_AND_REPLACE
FINISH_OLD_BATCH
```

according to authorized workflow.

---

# 99. Engineering Change Order

Support:

```text
ECO
```

with:

```text
reason
old revision
new revision
affected components
approval
effective stage
```

---

# 100. Manufacturing ECO Status

```text
DRAFT
REVIEW
APPROVED
RELEASED
IMPLEMENTED
CLOSED
```

---

# 101. Quality Plan

Each manufacturing item may have:

```text
inspection_points
tolerance
measurement
acceptance_rule
```

---

# 102. Dimensional Quality

Example:

```text
Target = 600 mm
Tolerance = ±1 mm
```

---

# 103. Visual Quality

Support:

```text
finish check
edge check
laminate check
surface defect
colour match
```

---

# 104. QC Status

```text
PASS
FAIL
REWORK
HOLD
```

---

# 105. QC Traceability

QC event must reference:

```text
panel
work order
operator
inspector
revision
timestamp
```

---

# 106. Assembly BOM

Assembly stage should know:

```text
components
hardware
sequence
instructions
```

---

# 107. Assembly Instructions

Support:

```text
text
image
diagram
3D view
video reference
```

---

# 108. Hardware Kit

Generate hardware kits by furniture:

```text
hinges
screws
dowels
cam locks
handles
runners
```

---

# 109. Hardware Kit Label

Support QR/barcode:

```text
furniture_id
kit_id
revision
```

---

# 110. Packaging

Generate package units:

```text
package_id
contents
weight
dimensions
destination
```

---

# 111. Package Traceability

Package must reference:

```text
project
room
furniture
panel IDs
hardware kit
```

---

# 112. Shipping Readiness

Furniture can be marked ready only when:

```text
all required panels complete
QC complete
hardware complete
packaging complete
```

---

# 113. Production Completion

Production job completes when:

```text
planned quantities
- scrap
- rework pending
= completed quantities
```

and required QC passes.

---

# 114. Manufacturing Metrics

Track:

```text
planned quantity
completed quantity
scrap quantity
rework quantity
cycle time
downtime
machine utilization
material utilization
```

---

# 115. OEE Integration

Optional:

```text
Availability
Performance
Quality
OEE
```

MES may calculate OEE using production telemetry.

---

# 116. Machine Downtime

Support:

```text
machine_id
start_time
end_time
reason
planned/unplanned
```

---

# 117. Downtime Reasons

```text
BREAKDOWN
MAINTENANCE
NO_MATERIAL
NO_OPERATOR
CHANGEOVER
QUALITY_HOLD
OTHER
```

---

# 118. Production Actuals

Capture:

```text
actual_start
actual_end
actual_quantity
scrap
rework
machine_time
labour_time
```

---

# 119. Manufacturing Cost Integration

Actual production can feed:

```text
actual material consumption
actual machine cost
actual labour cost
```

to the Pricing/Cost Analytics subsystem.

---

# 120. Manufacturing Cost Boundary

Manufacturing Engine records production facts.

Pricing Engine calculates commercial cost.

Do not duplicate financial formulas in MES.

---

# 121. Material Consumption

Track:

```text
planned
issued
consumed
returned
scrapped
```

---

# 122. Material Issue

Production may request:

```text
material issue
```

Inventory handles stock transaction.

---

# 123. Material Return

Unused material may be returned.

Track:

```text
issued_qty
returned_qty
net_consumed_qty
```

---

# 124. Remnant Creation

Usable leftover material can become:

```text
remnant
```

instead of scrap.

---

# 125. Remnant Status

```text
AVAILABLE
RESERVED
CONSUMED
SCRAPPED
EXPIRED
```

---

# 126. Factory Configuration

Factory profile should define:

```text
machines
work centers
shift calendars
material rules
routing rules
label formats
QC rules
nesting preferences
```

---

# 127. Factory-Specific Rules

A material may have different manufacturing behavior by factory.

Example:

```text
Factory A → CNC drilling
Factory B → manual drilling
```

Rules must be factory-scoped.

---

# 128. Multi-Tenant Manufacturing

Every manufacturing record must be tenant-scoped.

Server-side authorization is mandatory.

---

# 129. Multi-Factory

Support:

```text
Tenant
 ├── Factory A
 ├── Factory B
 └── Factory C
```

---

# 130. Factory Selection

A project may be assigned to a factory based on:

```text
location
capacity
capability
customer
material
machine
priority
```

---

# 131. Manufacturing Capability Matrix

Factory capability should map:

```text
material
thickness
operation
machine
```

Example:

```text
18mm MDF
→ CNC routing
→ Factory A
```

---

# 132. Capability Validation

If factory cannot perform an operation:

```text
BLOCK_RELEASE
```

or:

```text
ROUTE_TO_EXTERNAL_SUPPLIER
```

if configured.

---

# 133. External Processing

Support operations outsourced to:

```text
external vendor
```

with:

```text
vendor
operation
quantity
due date
status
```

---

# 134. Manufacturing Route

Route can include:

```text
internal
external
manual
machine
```

---

# 135. Alternate Route

Support alternate manufacturing route:

```text
Route A:
CNC → Edge Bander

Route B:
Panel Saw → Manual Edge
```

---

# 136. Route Selection

Selection may depend on:

```text
factory
machine availability
material
priority
cost
capacity
```

---

# 137. Manufacturing Rules Engine

Rules must support:

```text
IF material = X
THEN operation = Y

IF thickness > X
THEN machine = Z
```

---

# 138. Manufacturing Rule Example

```json
{
  "code": "HINGE_CUP_35MM",
  "condition": "hardware.type == 'HINGE'",
  "operation": "HINGE_CUP_DRILL",
  "diameter": 35,
  "depth": 12
}
```

---

# 139. Manufacturing Rule Security

Rules must use a restricted expression engine.

Do not execute arbitrary PHP/JavaScript.

---

# 140. Rule Versioning

Every manufacturing rule must have:

```text
version
effective_from
effective_to
status
```

---

# 141. Rule Traceability

Manufacturing output must identify:

```text
rule_id
rule_version
```

that generated an operation.

---

# 142. Operation Traceability

Every CNC operation should be traceable to:

```text
component
panel
BOM item
rule
machine
manufacturing revision
```

---

# 143. CNC Output

Canonical output should support:

```text
machine-specific file
```

Examples:

```text
G-code
CIX
DXF
XML
CSV
vendor-specific formats
```

Actual supported formats depend on post processors.

---

# 144. DXF Export

DXF may represent:

```text
panel outline
holes
slots
grooves
machining paths
```

---

# 145. G-code

G-code generation must be isolated behind a post processor.

Do not hard-code one machine's dialect into the core engine.

---

# 146. CNC Validation

Before export:

```text
machine capability
tool availability
axis limits
panel dimensions
operation coordinates
depth
```

must be validated.

---

# 147. CNC Safety Limits

Reject:

```text
negative depth
outside panel boundary
unsupported tool
excessive depth
invalid coordinate
```

---

# 148. Coordinate System

Machine profile defines:

```text
origin
X direction
Y direction
Z direction
face orientation
```

---

# 149. Face Mapping

Support:

```text
TOP
BOTTOM
FRONT
BACK
LEFT
RIGHT
```

and machine-specific mapping.

---

# 150. Coordinate Transformation

Post processor may transform canonical coordinates to machine coordinates.

---

# 151. CNC Program Version

Store:

```text
program_id
manufacturing_revision
machine_profile_version
post_processor_version
generated_at
checksum
```

---

# 152. CNC Program Integrity

Store checksum/hash.

At machine release:

```text
verify checksum
```

---

# 153. CNC File Status

```text
GENERATED
VALIDATED
APPROVED
RELEASED
SUPERSEDED
CANCELLED
```

---

# 154. File Storage

Large machine files should be stored outside relational tables where practical.

Database stores:

```text
storage_key
file_name
mime_type
checksum
size
version
```

---

# 155. Storage Architecture

Use abstraction:

```text
Local
S3-compatible
Cloud Storage
```

Do not hard-code filesystem paths throughout the application.

---

# 156. Manufacturing API — Generate

```http
POST /api/v1/manufacturing/generate
```

Input:

```json
{
  "project_id": "P001",
  "bom_revision_id": "BOM-R4",
  "factory_id": "F01"
}
```

---

# 157. Manufacturing API — Validate

```http
POST /api/v1/manufacturing/{id}/validate
```

---

# 158. Manufacturing API — Release

```http
POST /api/v1/manufacturing/{id}/release
```

---

# 159. Manufacturing API — Panels

```http
GET /api/v1/manufacturing/{id}/panels
GET /api/v1/manufacturing/panels/{panelId}
```

---

# 160. Manufacturing API — Cutlist

```http
GET /api/v1/manufacturing/{id}/cutlist
POST /api/v1/manufacturing/{id}/cutlist/generate
```

---

# 161. Manufacturing API — Nesting

```http
POST /api/v1/manufacturing/{id}/nest
GET /api/v1/manufacturing/{id}/nesting
```

---

# 162. Manufacturing API — Operations

```http
GET /api/v1/manufacturing/{id}/operations
POST /api/v1/manufacturing/{id}/operations/generate
```

---

# 163. Manufacturing API — CNC

```http
POST /api/v1/manufacturing/{id}/cnc/generate
GET /api/v1/manufacturing/{id}/cnc/files
POST /api/v1/manufacturing/cnc/{fileId}/validate
```

---

# 164. Manufacturing API — Labels

```http
POST /api/v1/manufacturing/{id}/labels/generate
GET /api/v1/manufacturing/{id}/labels
POST /api/v1/manufacturing/labels/{id}/reprint
```

---

# 165. Manufacturing API — Jobs

```http
POST /api/v1/manufacturing/jobs
GET /api/v1/manufacturing/jobs
GET /api/v1/manufacturing/jobs/{id}
POST /api/v1/manufacturing/jobs/{id}/start
POST /api/v1/manufacturing/jobs/{id}/complete
```

---

# 166. Manufacturing API — Scan

```http
POST /api/v1/manufacturing/panels/{panelId}/scan
```

Input:

```json
{
  "stage": "EDGE_BANDING",
  "work_order_id": "WO-001",
  "device_id": "DEVICE-01"
}
```

---

# 167. Manufacturing API — Scrap

```http
POST /api/v1/manufacturing/panels/{panelId}/scrap
```

---

# 168. Manufacturing API — Rework

```http
POST /api/v1/manufacturing/panels/{panelId}/rework
```

---

# 169. Manufacturing API — ECO

```http
POST /api/v1/manufacturing/eco
GET /api/v1/manufacturing/eco/{id}
POST /api/v1/manufacturing/eco/{id}/approve
POST /api/v1/manufacturing/eco/{id}/release
```

---

# 170. Manufacturing API — QC

```http
POST /api/v1/manufacturing/qc
GET /api/v1/manufacturing/qc/{id}
POST /api/v1/manufacturing/qc/{id}/pass
POST /api/v1/manufacturing/qc/{id}/fail
```

---

# 171. Database Tables

Minimum:

```text
manufacturing_headers
manufacturing_revisions
manufacturing_items
manufacturing_panels
manufacturing_panel_edges
manufacturing_operations
manufacturing_operation_tools
manufacturing_routings
manufacturing_routing_steps
manufacturing_machine_profiles
manufacturing_post_processors
manufacturing_cutlists
manufacturing_nesting_jobs
manufacturing_nesting_results
manufacturing_sheets
manufacturing_remnants
manufacturing_jobs
manufacturing_work_orders
manufacturing_scan_events
manufacturing_scrap_events
manufacturing_rework_events
manufacturing_qc_plans
manufacturing_qc_results
manufacturing_eco
manufacturing_eco_items
manufacturing_cnc_programs
manufacturing_labels
manufacturing_packages
manufacturing_material_transactions
manufacturing_audit_logs
```

---

# 172. Manufacturing Header

Fields:

```text
id
tenant_id
project_id
factory_id
status
current_revision
created_by
created_at
updated_at
```

---

# 173. Manufacturing Revision

Fields:

```text
id
manufacturing_id
revision_number
source_design_revision
source_bom_revision
catalog_version
manufacturing_rule_version
machine_profile_version
input_hash
output_hash
status
created_by
created_at
released_at
```

---

# 174. Manufacturing Panel

Fields:

```text
id
manufacturing_revision_id
panel_code
source_component_id
source_bom_item_id
material_id
variant_id
finished_length
finished_width
finished_thickness
raw_length
raw_width
raw_thickness
grain_direction
quantity
status
```

---

# 175. Panel Edge Table

Fields:

```text
id
panel_id
edge_position
edge_material_id
edge_thickness
edge_width
edge_length
processing_required
```

---

# 176. Operation Table

Fields:

```text
id
manufacturing_revision_id
panel_id
operation_code
operation_type
sequence
face
x
y
z
width
length
depth
diameter
tool_id
machine_id
work_center_id
rule_id
rule_version
status
```

---

# 177. Routing Table

Fields:

```text
id
manufacturing_revision_id
routing_code
version
status
```

---

# 178. Routing Step

Fields:

```text
id
routing_id
sequence
operation_type
work_center_id
machine_id
setup_time
run_time
required_skill
```

---

# 179. Machine Profile

Fields:

```text
id
factory_id
name
manufacturer
model
controller
axis_count
capabilities_json
limits_json
coordinate_system_json
post_processor_id
version
status
```

---

# 180. Post Processor

Fields:

```text
id
name
machine_type
format
version
config_json
status
```

---

# 181. Cutlist Table

Fields:

```text
id
manufacturing_revision_id
panel_id
material_id
length
width
thickness
quantity
grain
edge_data_json
```

---

# 182. Nesting Job

Fields:

```text
id
manufacturing_revision_id
algorithm
algorithm_version
kerf
trim_json
constraints_json
status
input_hash
output_hash
```

---

# 183. Nesting Result

Fields:

```text
id
nesting_job_id
sheet_id
utilization_percent
waste_area
placed_panels_json
```

---

# 184. Sheet Table

Fields:

```text
id
material_id
variant_id
length
width
thickness
grain
inventory_reference
status
```

---

# 185. Remnant Table

Fields:

```text
id
source_sheet_id
material_id
length
width
thickness
grain
location
status
```

---

# 186. Production Job Table

Fields:

```text
id
tenant_id
factory_id
project_id
manufacturing_revision_id
job_number
priority
planned_start
planned_end
status
```

---

# 187. Work Order Table

Fields:

```text
id
production_job_id
sequence
work_center_id
machine_id
planned_quantity
completed_quantity
scrap_quantity
status
```

---

# 188. Scan Event Table

Fields:

```text
id
panel_id
work_order_id
stage
user_id
device_id
timestamp
result
```

---

# 189. Scrap Table

Fields:

```text
id
panel_id
work_order_id
reason
quantity
material_id
user_id
created_at
```

---

# 190. Rework Table

Fields:

```text
id
original_panel_id
replacement_panel_id
reason
operation
status
created_at
```

---

# 191. QC Plan

Fields:

```text
id
manufacturing_revision_id
operation_type
check_type
parameter
target
tolerance
acceptance_rule
```

---

# 192. QC Result

Fields:

```text
id
qc_plan_id
panel_id
work_order_id
actual_value
result
inspector_id
timestamp
comments
```

---

# 193. CNC Program Table

Fields:

```text
id
manufacturing_revision_id
machine_id
post_processor_id
file_name
storage_key
checksum
size
version
status
generated_at
```

---

# 194. Label Table

Fields:

```text
id
panel_id
label_type
template_version
print_count
status
printed_by
printed_at
```

---

# 195. Package Table

Fields:

```text
id
production_job_id
package_code
weight
length
width
height
destination
status
```

---

# 196. Audit Table

Record:

```text
manufacturing_created
revision_created
manufacturing_released
panel_generated
nesting_generated
cnc_generated
label_printed
scan_completed
scrap_created
rework_created
qc_completed
eco_created
eco_released
```

---

# 197. Frontend Architecture

Recommended:

```text
/src/manufacturing/

domain/
  ManufacturingJob.js
  ManufacturingRevision.js
  ManufacturingPanel.js
  ManufacturingOperation.js
  WorkOrder.js

generation/
  ManufacturingGenerator.js
  PanelGenerator.js
  OperationGenerator.js
  RoutingGenerator.js

cutlist/
  CutlistEngine.js

nesting/
  NestingEngine.js
  NestingAlgorithm.js
  MaxRects.js
  Guillotine.js

cnc/
  CanonicalCncModel.js
  PostProcessor.js
  DxfGenerator.js
  GcodeGenerator.js

labels/
  LabelGenerator.js
  QrGenerator.js

validation/
  ManufacturingValidator.js
  MachineValidator.js
  CncValidator.js

production/
  ProductionJob.js
  WorkOrder.js
  ScanManager.js

quality/
  QualityManager.js

eco/
  EcoManager.js
```

---

# 198. PHP Architecture

Recommended:

```text
src/
  Manufacturing/
    Domain/
    Services/
    Generators/
    Panels/
    Operations/
    Routing/
    Nesting/
    CNC/
    PostProcessors/
    Labels/
    Production/
    Quality/
    ECO/
    Rework/
    Scrap/
    Validation/
    Repositories/
    DTO/
    Policies/
```

Core services:

```text
ManufacturingEngine
PanelEngine
OperationEngine
RoutingEngine
CutlistEngine
NestingEngine
CncEngine
PostProcessorManager
LabelEngine
ProductionJobService
QualityService
EcoService
```

---

# 199. Canonical Data Flow

```text
BOM
 ↓
ManufacturingGenerator
 ↓
Manufacturing Model
 ↓
Panel Generator
 ↓
Operation Generator
 ↓
Routing Generator
 ↓
Cutlist
 ↓
Nesting
 ↓
Canonical CNC Operations
 ↓
Post Processor
 ↓
Machine File
```

---

# 200. Manufacturing Validation Pipeline

```text
Input Validation
 ↓
Engineering Validation
 ↓
Material Validation
 ↓
Panel Validation
 ↓
Operation Validation
 ↓
Machine Capability Validation
 ↓
Nesting Validation
 ↓
CNC Validation
 ↓
Release Validation
```

---

# 201. Manufacturing Readiness Score

Optional score:

```text
Materials = 20%
Panels = 20%
Operations = 20%
Machine = 15%
Nesting = 15%
CNC = 10%
```

A job must still obey hard blockers regardless of score.

---

# 202. Manufacturing Dashboard

Show:

```text
Jobs Today
Panels Pending
Cutting
Edge Banding
Drilling
Assembly
QC
Packing
Blocked Jobs
Scrap
Rework
```

---

# 203. Factory Dashboard

Show:

```text
Machine Status
Work Center Load
Jobs Waiting
Jobs Running
Jobs Completed
Material Shortages
Quality Holds
```

---

# 204. Production Board

Kanban:

```text
READY
CUTTING
EDGE
DRILLING
ROUTING
ASSEMBLY
QC
PACKING
DONE
```

---

# 205. Operator View

Operator should see only relevant information:

```text
Current Job
Current Panel
Dimensions
Material
Operations
Tool
Machine
Instructions
Scan
Complete
Reject
Rework
```

---

# 206. Supervisor View

Supervisor sees:

```text
work center
machine
queue
capacity
progress
exceptions
scrap
rework
```

---

# 207. Factory Manager View

Manager sees:

```text
production throughput
capacity
utilization
waste
quality
delivery
```

---

# 208. Mobile/Tablet Support

Shop-floor scanning UI should be responsive and optimized for:

```text
tablet
mobile
industrial handheld
```

---

# 209. Offline Scan Support

Optional future capability:

```text
offline scan queue
```

with synchronization when connectivity returns.

Conflicts must be handled safely.

---

# 210. Device Registration

Shop-floor devices may have:

```text
device_id
factory_id
work_center_id
status
```

---

# 211. Device Security

Devices must authenticate before submitting production scans.

---

# 212. Production Notifications

Support events:

```text
job blocked
material shortage
machine down
QC failure
panel missing
production delayed
ECO released
```

---

# 213. Event Architecture

Manufacturing events may publish:

```text
ManufacturingReleased
PanelCreated
PanelCut
PanelEdgeBanded
PanelDrilled
PanelRouted
PanelScrapped
PanelReworked
QualityPassed
QualityFailed
JobCompleted
```

---

# 214. Event Consumers

Other modules can consume events:

```text
Inventory
Procurement
Pricing Analytics
Notifications
Reporting
Shipping
```

---

# 215. Idempotency

Manufacturing commands must support idempotency.

Example:

```text
generate manufacturing revision
```

must not create duplicate outputs if the same request is retried.

---

# 216. Concurrency

Use optimistic locking for:

```text
manufacturing revision
production job
work order
panel state
```

---

# 217. State Transition Rules

Example:

```text
READY → STARTED
STARTED → COMPLETED
STARTED → PAUSED
STARTED → BLOCKED
```

Invalid transitions must return structured errors.

---

# 218. Error Codes

Minimum:

```text
MFG_INVALID_REVISION
MFG_MISSING_MATERIAL
MFG_INVALID_PANEL
MFG_MACHINE_UNSUPPORTED
MFG_TOOL_UNAVAILABLE
MFG_OPERATION_INVALID
MFG_NESTING_FAILED
MFG_CNC_VALIDATION_FAILED
MFG_RELEASE_BLOCKED
MFG_PANEL_WRONG_JOB
MFG_PANEL_WRONG_STAGE
MFG_REVISION_CONFLICT
MFG_ECO_REQUIRED
MFG_PERMISSION_DENIED
```

---

# 219. Security

Manufacturing data must be protected by:

```text
tenant authorization
factory authorization
role authorization
project authorization
```

---

# 220. Manufacturing RBAC

Minimum permissions:

```text
manufacturing.view
manufacturing.generate
manufacturing.edit
manufacturing.validate
manufacturing.release
manufacturing.cnc.generate
manufacturing.cnc.release
manufacturing.nesting.run
manufacturing.labels.print
manufacturing.jobs.create
manufacturing.jobs.start
manufacturing.jobs.complete
manufacturing.scrap.create
manufacturing.rework.create
manufacturing.qc.perform
manufacturing.eco.create
manufacturing.eco.approve
manufacturing.audit.view
```

---

# 221. Sensitive Information

Operator should not need access to:

```text
customer price
supplier price
internal margin
commercial terms
```

unless explicitly authorized.

---

# 222. Performance Requirements

Target:

```text
1,000 panels → manufacturing generation < 5 seconds
5,000 panels → < 15 seconds where practical
```

Heavy operations may be asynchronous:

```text
nesting
CNC generation
large label batches
```

---

# 223. Asynchronous Jobs

Provide job tracking:

```text
QUEUED
RUNNING
COMPLETED
FAILED
CANCELLED
```

---

# 224. Progress Reporting

UI should show:

```text
Generating panels 65%
Generating operations 80%
Generating nesting 92%
Generating CNC 100%
```

---

# 225. Incremental Regeneration

If one furniture item changes:

```text
regenerate affected manufacturing subtree
```

where safe.

---

# 226. Cache

Cache:

```text
machine profiles
compiled manufacturing rules
tool profiles
catalog compatibility
```

---

# 227. Hash-Based Generation

Manufacturing input should generate:

```text
input_hash
```

Same input should produce the same output.

---

# 228. Deterministic Output

Given identical:

```text
design revision
BOM revision
catalog version
rule version
machine profile
post processor
```

manufacturing generation should be deterministic.

---

# 229. Testing — Unit

Required:

```text
panel dimensions
edge calculation
hardware placement
drilling
routing
grooving
machine validation
tool selection
UOM
```

---

# 230. Testing — Nesting

Test:

```text
grain
rotation
kerf
trim
defects
remnants
utilization
```

---

# 231. Testing — CNC

Test:

```text
coordinates
depth
tool
face
machine limits
post processor
checksum
```

---

# 232. Testing — Production

Test:

```text
panel scan
wrong panel
wrong stage
duplicate scan
scrap
rework
QC
completion
```

---

# 233. Testing — Revision

Verify:

```text
Manufacturing v1
→ production

Design change
→ Manufacturing v2

v1 remains immutable
```

---

# 234. Testing — ECO

Verify:

```text
ECO created
→ approval
→ release
→ affected production identified
```

---

# 235. Testing — Security

Verify:

```text
Tenant isolation
Factory isolation
Role permissions
Device authentication
CNC file access
Audit logs
```

---

# 236. Testing — Integration

End-to-end:

```text
Design
 ↓
Furniture
 ↓
BOM
 ↓
Manufacturing
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
 ↓
Label
 ↓
Production
 ↓
QC
 ↓
Packing
```

---

# 237. Manufacturing Acceptance Scenario

Use a sample wardrobe:

```text
Width: 2400 mm
Depth: 600 mm
Height: 2400 mm
Material: 18mm MDF
Back: 6mm
Shutters: 4
Drawers: 3
Hinges: 12
Handles: 4
```

System must generate:

```text
Panels
Edge Bands
Hardware
Drilling
Routing
Cutlist
Nesting
Labels
CNC operations
Production routing
```

---

# 238. Acceptance — Panel

Every panel must have:

```text
unique ID
dimensions
material
grain
edges
source component
```

---

# 239. Acceptance — Cutlist

Cutlist must show:

```text
panel
material
dimensions
quantity
grain
edge data
```

---

# 240. Acceptance — Nesting

Nesting must show:

```text
sheet
placed panels
waste
utilization
```

---

# 241. Acceptance — CNC

CNC output must:

```text
match machine profile
contain required operations
pass validation
have checksum
reference manufacturing revision
```

---

# 242. Acceptance — Labels

Each physical panel must have:

```text
unique QR/barcode
dimensions
material
furniture
panel code
```

---

# 243. Acceptance — MES

Scanning must move panel through:

```text
CUT
→ EDGE
→ DRILL
→ ASSEMBLY
→ QC
→ PACK
```

and prevent invalid transitions.

---

# 244. Acceptance — Scrap

Scrapped panel must:

```text
leave active production quantity
record reason
record operator
create replacement requirement if applicable
```

---

# 245. Acceptance — Rework

Rework must:

```text
preserve original traceability
record reason
create work instruction
track completion
```

---

# 246. Acceptance — Change Management

A design revision after release must:

```text
create new manufacturing revision
identify affected panels
identify affected CNC programs
identify affected labels
identify affected production jobs
```

---

# 247. Cursor Pre-Implementation Analysis

Before coding, Cursor MUST inspect the existing codebase for:

```text
2D CAD
3D/BIM
Parametric Furniture Engine
Material Catalog
BOM
BOQ
Pricing Engine
Cutlist
Nesting
CNC
MES
RBAC
Revision System
File Storage
QR/Barcode
```

Cursor must produce:

```text
CURRENT MANUFACTURING ARCHITECTURE
CURRENT CUTLIST LOGIC
CURRENT NESTING LOGIC
CURRENT CNC LOGIC
CURRENT MES LOGIC
CURRENT DATABASE TABLES
CURRENT APIs
DUPLICATE IMPLEMENTATIONS
MISSING CAPABILITIES
DATA MODEL GAPS
INTEGRATION GAPS
MIGRATION RISKS
TARGET ARCHITECTURE
IMPLEMENTATION PLAN
```

Do NOT create duplicate engines where equivalent implementation already exists.

---

# 248. Cursor Implementation Sequence

## Phase 1 — Manufacturing Domain

```text
Manufacturing Revision
Manufacturing Item
Panel
Operation
Routing
```

## Phase 2 — Panel Engine

```text
Panel generation
Dimensions
Materials
Edges
Grain
```

## Phase 3 — Operation Engine

```text
Drilling
Routing
Grooving
Hardware
```

## Phase 4 — Cutlist

```text
Cutlist
Aggregation
Export
```

## Phase 5 — Nesting

```text
Sheet
Remnant
Kerf
Trim
Grain
Algorithm
```

## Phase 6 — CNC

```text
Canonical Operations
Post Processor
DXF
G-code
Machine Formats
```

## Phase 7 — Labels

```text
QR
Barcode
Templates
Printing
```

## Phase 8 — Production

```text
Jobs
Work Orders
Routing
Scanning
```

## Phase 9 — Quality

```text
QC Plans
QC Results
Rework
Scrap
```

## Phase 10 — ECO

```text
Change Management
Revision Impact
Production Impact
```

## Phase 11 — Analytics

```text
Throughput
Waste
Scrap
Machine Utilization
Production Cost
```

## Phase 12 — Optimization

```text
Incremental generation
Caching
Async processing
Performance
```

---

# 249. Definition of Done

The Manufacturing Engine is complete when:

```text
[ ] Manufacturing revisions implemented
[ ] Manufacturing BOM implemented
[ ] Panel generation implemented
[ ] Panel dimensions implemented
[ ] Finished/raw dimensions implemented
[ ] Material validation implemented
[ ] Grain direction implemented
[ ] Edge-band calculation implemented
[ ] Hardware placement implemented
[ ] Drilling operations implemented
[ ] Routing operations implemented
[ ] Grooving implemented
[ ] Tool management implemented
[ ] Machine profiles implemented
[ ] Machine capability validation implemented
[ ] Manufacturing routing implemented
[ ] Work centers implemented
[ ] Cutlist implemented
[ ] Nesting implemented
[ ] Kerf implemented
[ ] Trim implemented
[ ] Defect zones implemented
[ ] Remnant management implemented
[ ] CNC canonical model implemented
[ ] Post processors implemented
[ ] DXF export implemented
[ ] G-code/vendor output architecture implemented
[ ] CNC validation implemented
[ ] CNC checksum implemented
[ ] QR labels implemented
[ ] Barcode labels implemented
[ ] Production jobs implemented
[ ] Work orders implemented
[ ] Shop-floor scanning implemented
[ ] Panel traceability implemented
[ ] Scrap implemented
[ ] Rework implemented
[ ] QC implemented
[ ] Packaging implemented
[ ] ECO implemented
[ ] Revision impact implemented
[ ] Material shortage validation implemented
[ ] Factory capability validation implemented
[ ] RBAC implemented
[ ] Tenant isolation implemented
[ ] Audit trail implemented
[ ] APIs implemented
[ ] Database implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Nesting tests implemented
[ ] CNC tests implemented
[ ] Security tests implemented
[ ] Performance tests implemented
```

---

# 250. Final End-to-End Manufacturing Flow

```text
                    INTERIOR DESIGN
                          │
                          ↓
                  PARAMETRIC FURNITURE
                          │
                          ↓
                         BOM
                          │
                          ↓
                MANUFACTURING RELEASE
                          │
              ┌───────────┼───────────┐
              ↓           ↓           ↓
           PANELS      HARDWARE     ACCESSORIES
              │
              ↓
          CUTLIST
              │
              ↓
           NESTING
              │
              ↓
        MATERIAL / SHEETS
              │
              ↓
        MACHINING OPERATIONS
              │
       ┌──────┼─────────┐
       ↓      ↓         ↓
     DRILL  ROUTE     GROOVE
       │      │         │
       └──────┼─────────┘
              ↓
        CANONICAL CNC
              │
              ↓
       MACHINE POST PROCESSOR
              │
              ↓
        CNC MACHINE FILE
              │
              ↓
            LABEL
              │
              ↓
        PRODUCTION JOB
              │
       ┌──────┼─────────────┐
       ↓      ↓             ↓
     CUT    EDGE          DRILL
       │      │             │
       └──────┼─────────────┘
              ↓
          ASSEMBLY
              ↓
              QC
              ↓
           PACKING
              ↓
           SHIPPING
```

---

# 251. Final Product Principle

The Manufacturing Engine must make it possible to answer:

```text
What needs to be manufactured?
Which panels are required?
What material is each panel made from?
What are its finished and raw dimensions?
Which edges need treatment?
Which holes need drilling?
Which grooves/routings are required?
Which machine should process it?
Which tool should be used?
Which CNC program was generated?
Which revision generated the program?
Where is the physical panel now?
Which production stage has completed?
Was it scrapped or reworked?
Did it pass quality?
Which package contains it?
Which customer/project/room/furniture does it belong to?
```

The fundamental rule is:

> **The Manufacturing Engine must transform an approved parametric furniture model into deterministic, revision-controlled and traceable production instructions without forcing factory operators to reinterpret design intent manually.**

Every physical production artifact must remain traceable:

```text
Physical Panel
 ↓
Panel ID
 ↓
Manufacturing Revision
 ↓
BOM Item
 ↓
Furniture Component
 ↓
Furniture
 ↓
Room
 ↓
Project
```

And every machine output must remain traceable:

```text
CNC Program
 ↓
Post Processor Version
 ↓
Machine Profile
 ↓
Manufacturing Revision
 ↓
Panel
 ↓
BOM
 ↓
Design Revision
```
