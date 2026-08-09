# Nesting Engine Specification
## Interior Design → Furniture Engineering → Cutlist → Panel Optimization → CNC/MES

**Document ID:** NEST-ENG-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, Manufacturing Engineers, CNC Engineers, Factory Managers, Optimization Engineers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Units:** mm, m², pcs, sheets  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the **2D Panel Nesting Engine**.

The Nesting Engine converts manufacturing-ready panel requirements into optimized sheet layouts while respecting real factory constraints.

It must optimize:

```text
Material utilization
Sheet count
Waste
Cutting efficiency
Grain direction
Panel rotation
Kerf
Trim
Defect zones
Edge restrictions
Machine constraints
Remnant reuse
Sheet inventory
Batch requirements
```

The engine must support:

```text
MDF
HDF
Plywood
Particle Board
OSB
Laminate Board
Acrylic Board
PVC Board
Compact Board
Other rectangular sheet materials
```

The architecture must allow future support for:

```text
glass
stone
metal sheet
composite sheets
```

where appropriate.

---

# 2. Core Principle

The nesting engine is an **optimization engine**, not a drawing tool.

Its responsibility is:

```text
Manufacturing Panels
        ↓
Nesting Constraints
        ↓
Optimization Algorithm
        ↓
Optimized Sheet Layout
        ↓
Canonical Cut Paths
        ↓
CNC / Panel Saw
```

It must never modify the original engineering dimensions.

---

# 3. Source of Truth

Nesting consumes:

```text
Manufacturing Revision
Panel List
Material Master
Sheet Master
Inventory
Machine Profile
Nesting Rules
Grain Rules
Edge Rules
Kerf Rules
Trim Rules
Remnant Rules
```

The original panel engineering data remains immutable.

---

# 4. Nesting Architecture

```text
                    MANUFACTURING BOM
                           │
                           ↓
                         PANELS
                           │
                           ↓
                  NESTING INPUT BUILDER
                           │
             ┌─────────────┼─────────────┐
             ↓             ↓             ↓
          SHEETS         RULES        MACHINE
             │             │             │
             └─────────────┼─────────────┘
                           ↓
                    NESTING ENGINE
                           │
          ┌────────────────┼────────────────┐
          ↓                ↓                ↓
      ALGORITHM         CONSTRAINTS      SCORING
          │                │                │
          └────────────────┼────────────────┘
                           ↓
                    NESTING RESULT
                           │
             ┌─────────────┼─────────────┐
             ↓             ↓             ↓
        SHEET LAYOUT     WASTE       UTILIZATION
             │
             ↓
      CUT SEQUENCE / PATH
             │
             ↓
          CNC / SAW
```

---

# 5. Nesting Input

Each panel must provide:

```text
panel_id
part_number
length
width
thickness
material_id
variant_id
quantity
grain_direction
rotation_allowed
edge_restrictions
finish_orientation
priority
```

---

# 6. Sheet Input

Each available sheet must provide:

```text
sheet_id
material_id
variant_id
length
width
thickness
grain_direction
usable_area
inventory_status
location
```

---

# 7. Sheet Types

Support:

```text
STANDARD_SHEET
CUSTOM_SHEET
REMNANT
RESERVED_SHEET
```

---

# 8. Standard Sheet Sizes

Do not hard-code sheet sizes.

Maintain a catalog:

```text
2440 × 1220
2750 × 1830
2800 × 2070
3050 × 1220
```

Example values are illustrative only.

---

# 9. Remnant Sheets

Remnants must behave as valid nesting stock if:

```text
status = AVAILABLE
dimensions >= minimum reusable dimensions
material matches
thickness matches
finish matches
grain constraints satisfied
```

---

# 10. Nesting Unit

Internally standardize geometry to:

```text
millimetres
```

Persist original UOM separately where required.

---

# 11. Decimal Precision

Geometry calculations should support:

```text
0.01 mm
```

or finer if machine requirements demand it.

---

# 12. Panel Rectangle

Default nesting geometry:

```text
x
y
width
height
```

with optional:

```text
rotation
```

---

# 13. Irregular Panels

The engine must support two modes:

```text
RECTANGULAR
IRREGULAR
```

Version 1 may optimize rectangular panels first.

Irregular nesting must use polygon geometry.

---

# 14. Panel Geometry

For irregular panels store:

```text
polygon_points
bounding_box
holes
cutouts
```

---

# 15. Panel Holes

Internal holes should normally not affect rectangular placement unless:

```text
hole affects clamping
hole affects cutting path
hole creates usable internal space
```

---

# 16. Panel Cutouts

Cutouts must be represented separately from the outer profile.

---

# 17. Grain Direction

Supported states:

```text
NONE
HORIZONTAL
VERTICAL
FIXED
ANY
```

---

# 18. Grain Rules

If grain is fixed:

```text
panel rotation = 0°
```

or an explicitly allowed orientation.

---

# 19. Rotation

Support:

```text
0°
90°
180°
270°
```

For rectangular grain-neutral panels:

```text
0°
90°
```

may be sufficient.

---

# 20. Rotation Restrictions

Each panel may specify:

```text
rotation_allowed = true/false
allowed_rotations = [...]
```

---

# 21. Finish Direction

Some panels require visual orientation independent of grain.

Support:

```text
finish_direction
front_face
decorative_direction
```

---

# 22. Edge Restrictions

Panel edges may have:

```text
edge_band
profile
exposed_edge
finished_edge
machine_restriction
```

---

# 23. Edge Restrictions and Placement

Nesting must optionally preserve:

```text
finished edge
```

away from:

```text
factory cut edge
sheet edge
trim zone
```

when configured.

---

# 24. Edge Banding Direction

Where relevant, nesting must preserve the intended orientation of:

```text
top edge
bottom edge
left edge
right edge
```

---

# 25. Sheet Trim

Each sheet can have:

```text
left_trim
right_trim
top_trim
bottom_trim
```

The usable nesting rectangle is:

```text
usable_width =
sheet_width - left_trim - right_trim

usable_height =
sheet_height - top_trim - bottom_trim
```

---

# 26. Machine Clamp Zones

Some CNC machines require unusable areas.

Support:

```text
clamp_zone
vacuum_zone
machine_margin
```

---

# 27. Machine Exclusion Zones

Represent exclusion zones as:

```text
x
y
width
height
```

or polygons.

---

# 28. Sheet Defect Zones

Support:

```text
knots
cracks
damage
printing defects
holes
pre-existing cuts
```

Each defect is represented as an exclusion geometry.

---

# 29. Defect Zone Types

```text
RECTANGLE
CIRCLE
POLYGON
```

---

# 30. Defect Handling

Panel placement must not overlap defect zones.

---

# 31. Defect Tolerance

Optional minimum clearance:

```text
defect_clearance
```

---

# 32. Kerf

Kerf represents material consumed by the cutting tool.

Store:

```text
kerf_width
```

per machine/tool/profile.

---

# 33. Kerf Application

The nesting algorithm must account for kerf when determining whether two panels can fit.

---

# 34. Kerf by Machine

Different machines may use:

```text
different kerf
```

The nesting job must reference the machine profile.

---

# 35. Kerf by Material

Optional:

```text
material-specific kerf
```

---

# 36. Kerf by Tool

Optional:

```text
tool-specific kerf
```

---

# 37. Minimum Gap

Support:

```text
minimum_panel_gap
```

separate from kerf.

---

# 38. Cutting Gap

Support:

```text
cutting_clearance
```

---

# 39. Border Margin

Support:

```text
sheet_border_margin
```

---

# 40. Effective Nesting Area

The engine must calculate:

```text
sheet usable region
```

after:

```text
trim
machine exclusion
defects
margins
```

---

# 41. Panel Quantity

The engine must expand quantity:

```text
panel quantity = 5
```

into five nestable instances while preserving:

```text
logical_panel_id
instance_id
```

---

# 42. Panel Instance

Example:

```text
Logical:
WARD-LEFT-SIDE

Instances:
WARD-LEFT-SIDE-001
WARD-LEFT-SIDE-002
WARD-LEFT-SIDE-003
```

---

# 43. Duplicate Panels

Identical panels may be grouped for optimization.

The engine must retain individual identity for:

```text
labeling
traceability
production
```

---

# 44. Panel Grouping

Group compatible panels by:

```text
material
thickness
finish
grain
sheet family
machine
nesting rules
```

---

# 45. Nesting Group

A nesting group should never combine incompatible:

```text
material
thickness
finish
machine constraints
```

---

# 46. Mixed Material Nesting

Do NOT place different materials on the same sheet unless explicitly supported by the material model.

Default:

```text
mixed material = prohibited
```

---

# 47. Mixed Thickness

Default:

```text
mixed thickness = prohibited
```

---

# 48. Mixed Finish

Default:

```text
mixed finish = prohibited
```

---

# 49. Sheet Selection

Sheet selection may consider:

```text
available stock
remnants
standard sheets
reserved sheets
cost
waste
priority
```

---

# 50. Stock Priority

Configurable order:

```text
REMNANT_FIRST
OLDEST_STOCK_FIRST
LOWEST_COST_FIRST
STANDARD_SHEET_FIRST
CUSTOM
```

---

# 51. Remnant-First Optimization

When enabled:

```text
usable remnant
```

should be preferred if it provides an acceptable nesting result.

---

# 52. Material Waste

Calculate:

```text
waste_area =
usable_sheet_area
- sum(panel_areas)
- cutting_loss
```

---

# 53. Utilization

Basic utilization:

```text
utilization =
sum(panel_area) / usable_sheet_area × 100
```

---

# 54. Effective Utilization

Optional:

```text
effective_utilization =
panel_area / available_material_area × 100
```

where actual sheet material consumed is considered.

---

# 55. Waste Percentage

```text
waste_percent =
100 - utilization_percent
```

---

# 56. Cutting Loss

Track separately:

```text
panel geometry waste
kerf loss
trim loss
defect loss
```

---

# 57. Waste Breakdown

Result should expose:

```text
usable_area
panel_area
kerf_loss
trim_loss
defect_loss
remaining_waste
```

---

# 58. Material Utilization KPI

Track:

```text
sheet utilization
material waste
number of sheets
remnant generated
```

---

# 59. Objective Function

Nesting should support configurable optimization objectives.

Possible objective:

```text
MINIMIZE_SHEET_COUNT
MINIMIZE_WASTE
MAXIMIZE_UTILIZATION
MINIMIZE_COST
MINIMIZE_CUT_LENGTH
MINIMIZE_CUT_COUNT
BALANCED
```

---

# 60. Weighted Objective

Support:

```text
score =
w1 × sheet_count_score
+ w2 × waste_score
+ w3 × utilization_score
+ w4 × cut_score
+ w5 × remnant_score
```

Weights must be configurable.

---

# 61. Default Objective

Recommended default:

```text
1. minimize number of sheets
2. minimize waste
3. maximize utilization
4. minimize cutting complexity
```

---

# 62. Optimization Result Ranking

If multiple candidate layouts are generated:

```text
rank candidates
```

using the configured objective function.

---

# 63. Candidate Solutions

The engine should support generating:

```text
candidate 1
candidate 2
candidate 3
...
```

and selecting the best.

---

# 64. Algorithm Abstraction

The core interface should resemble:

```text
NestingAlgorithm
    solve(input, constraints, objective)
    validate(input)
    score(solution)
```

---

# 65. Supported Algorithms

Architecture should permit:

```text
Guillotine
MaxRects
Skyline
First Fit Decreasing
Best Fit Decreasing
Shelf
Hybrid
```

---

# 66. First Implementation Algorithm

Recommended initial production algorithm:

```text
MaxRects
```

with optional:

```text
Guillotine
```

for saw-cut workflows.

---

# 67. Algorithm Version

Every nesting result must record:

```text
algorithm
algorithm_version
configuration
```

---

# 68. Deterministic Seed

If randomized optimization is introduced, support:

```text
random_seed
```

to reproduce a result.

---

# 69. Deterministic Mode

Production nesting must support:

```text
deterministic = true
```

---

# 70. Optimization Timeout

Support:

```text
max_runtime_ms
```

Example:

```text
5000 ms
```

---

# 71. Optimization Iterations

Optional:

```text
max_iterations
```

---

# 72. Quality vs Runtime

Allow:

```text
FAST
BALANCED
HIGH_QUALITY
```

optimization profiles.

---

# 73. FAST Profile

Prioritize:

```text
low runtime
acceptable utilization
```

---

# 74. BALANCED Profile

Prioritize:

```text
reasonable runtime
good utilization
```

---

# 75. HIGH_QUALITY Profile

Prioritize:

```text
best achievable utilization
```

within configured time.

---

# 76. Placement Validation

Before accepting a panel placement:

```text
inside usable sheet
no overlap
no defect overlap
no exclusion overlap
rotation allowed
grain valid
edge restrictions valid
gap valid
```

---

# 77. Collision Detection

For rectangles:

```text
AABB / rectangle intersection
```

must be used.

---

# 78. Polygon Collision

For irregular nesting:

Support:

```text
polygon intersection
polygon containment
offset geometry
```

---

# 79. Geometry Library

Geometry functions must be isolated behind:

```text
GeometryEngine
```

so the underlying library can be replaced.

---

# 80. No Floating-Point Drift

Use normalized precision for geometric comparisons.

Avoid placement errors caused by:

```text
0.0000001 mm
```

type floating-point artifacts.

---

# 81. Coordinate System

Sheet origin:

```text
(0,0)
```

Machine-specific origin is handled later by the CNC post processor.

---

# 82. Rotation Transform

For a panel:

```text
rotation = 90°
```

dimensions must be transformed safely.

---

# 83. Placement Record

Each placed panel must store:

```text
instance_id
sheet_id
x
y
width
height
rotation
```

---

# 84. Placement Metadata

Also store:

```text
grain_orientation
edge_orientation
algorithm_score
```

---

# 85. Cut Sequence

Nesting result may generate:

```text
cut_sequence
```

but machine-specific cut optimization belongs to the downstream cut/CNC engine.

---

# 86. Guillotine Cuts

For panel saw workflows, support hierarchical guillotine cuts.

Example:

```text
Sheet
 ├── Cut A
 │    ├── Panel 1
 │    └── Panel 2
 └── Cut B
      ├── Panel 3
      └── Panel 4
```

---

# 87. Guillotine Constraint

A guillotine solution must be fully separable through straight cuts.

---

# 88. CNC Nesting

CNC nesting may allow:

```text
common-line cutting
internal contours
tabs
bridges
```

These should be handled by CNC-specific logic.

---

# 89. Common-Line Cutting

Optional optimization:

```text
two adjacent panel edges share one cut
```

Must be enabled only when:

```text
machine supports it
material permits it
edge rules permit it
```

---

# 90. Common-Line Safety

Do not use common-line cutting when it violates:

```text
edge band
grain
panel integrity
machine constraints
```

---

# 91. Tabs and Bridges

For small or complex parts, support:

```text
tabs
bridges
micro-joints
```

as downstream CNC constraints.

---

# 92. Small Panel Rule

If a panel is below a configurable size:

```text
minimum_panel_dimension
```

apply special handling:

```text
grouping
tabs
manual processing
```

---

# 93. Part Priority

Support priority:

```text
HIGH
NORMAL
LOW
```

High-priority panels should be placed first where configured.

---

# 94. Large Panel Priority

Large panels should normally be placed before small panels for rectangle packing.

---

# 95. Sorting Strategies

Support:

```text
AREA_DESC
MAX_SIDE_DESC
WIDTH_DESC
HEIGHT_DESC
PRIORITY
CUSTOM
```

---

# 96. Panel Preprocessing

Before nesting:

```text
normalize
validate
expand quantity
resolve orientation
calculate effective geometry
group
sort
```

---

# 97. Input Validation

Reject:

```text
length <= 0
width <= 0
thickness <= 0
quantity <= 0
unknown material
unknown sheet family
invalid grain
```

---

# 98. Oversized Panel

If panel cannot fit any available sheet:

```text
NESTING_PANEL_TOO_LARGE
```

The system must not silently scale the panel.

---

# 99. Oversized Panel Options

Configured alternatives:

```text
manual handling
split panel
alternate sheet size
alternate material
external processing
```

These require explicit engineering approval.

---

# 100. Sheet Shortage

If available sheets are insufficient:

```text
NESTING_STOCK_SHORTAGE
```

Return:

```text
required
available
shortage
```

---

# 101. Partial Nesting

Support:

```text
PARTIAL_SUCCESS
```

where some panels are nested and others remain unallocated.

---

# 102. Partial Result

Result must clearly identify:

```text
nested panels
unplaced panels
reason
```

---

# 103. Nesting Failure

Never return an apparently successful result if panels remain unplaced.

---

# 104. Result Status

Support:

```text
DRAFT
RUNNING
SUCCESS
PARTIAL
FAILED
APPROVED
RELEASED
SUPERSEDED
```

---

# 105. Nesting Revision

Every nesting run creates:

```text
nesting_revision
```

---

# 106. Nesting Immutability

Once nesting is released:

```text
do not overwrite
```

Create another nesting revision.

---

# 107. Input Hash

Generate:

```text
input_hash
```

from:

```text
panel list
sheet list
constraints
machine profile
algorithm
algorithm version
```

---

# 108. Output Hash

Generate:

```text
output_hash
```

for the final nesting result.

---

# 109. Reproducibility

The system must be able to reproduce a nesting result using:

```text
input_hash
algorithm version
configuration
seed
```

---

# 110. Nesting Approval

Before manufacturing release:

```text
nesting result
```

may require approval.

---

# 111. Approval Roles

Configurable:

```text
Production Engineer
Factory Manager
Manufacturing Engineer
```

---

# 112. Approval Information

Show:

```text
sheet count
utilization
waste
unplaced panels
remnants used
algorithm
```

---

# 113. Nesting Comparison

Allow users to compare:

```text
Nesting Revision A
Nesting Revision B
```

with:

```text
sheet count
waste
utilization
cost
```

---

# 114. Material Cost Integration

Nesting can calculate material consumption:

```text
sheets_used × sheet_cost
```

or:

```text
actual sheet cost
```

depending on pricing configuration.

---

# 115. Nesting Cost

Output:

```text
material_cost
waste_cost
estimated_cutting_cost
```

where enabled.

---

# 116. Material Cost Objective

Optional objective:

```text
minimize material cost
```

instead of purely minimizing waste.

---

# 117. Sheet Price Variation

Different sheet sizes may have different rates.

Example:

```text
2440×1220 = ₹X
2800×2070 = ₹Y
```

The optimizer can choose based on cost if configured.

---

# 118. Remnant Cost

Remnants may have:

```text
zero incremental cost
reduced cost
normal cost
```

according to pricing policy.

---

# 119. Remnant Reservation

When a nesting job uses a remnant:

```text
reserve remnant
```

until the manufacturing release is completed or cancelled.

---

# 120. Reservation Conflict

If another job attempts to use a reserved remnant:

```text
reject
```

or request explicit override.

---

# 121. Inventory Integration Boundary

Nesting should request stock through:

```text
InventoryService
```

rather than directly modifying inventory tables.

---

# 122. Inventory Transaction

Nesting creates:

```text
reservation
```

not consumption.

Actual material consumption happens during production.

---

# 123. Sheet Reservation

Store:

```text
reservation_id
sheet_id
nesting_job_id
quantity
status
```

---

# 124. Reservation Lifecycle

```text
RESERVED
USED
RELEASED
CANCELLED
EXPIRED
```

---

# 125. Batch Optimization

Support nesting across multiple furniture items if:

```text
same material
same thickness
same finish
compatible machine
```

---

# 126. Batch Group

Example:

```text
Kitchen A
Wardrobe B
TV Unit C
```

may share:

```text
18mm White MDF
```

and be nested together.

---

# 127. Batch Traceability

Every panel retains:

```text
project
room
furniture
component
```

after batch nesting.

---

# 128. Multi-Project Nesting

Optional future capability.

Must enforce:

```text
tenant
factory
customer
material
production release
```

rules.

---

# 129. Priority in Multi-Project Nesting

Support:

```text
delivery date
job priority
customer priority
```

---

# 130. Sheet Reservation in Batch

The optimizer should reserve enough stock for the complete approved batch.

---

# 131. Sheet Utilization Report

For each sheet:

```text
sheet dimensions
usable area
panel area
waste area
utilization %
kerf loss
trim loss
```

---

# 132. Nesting Summary

Show:

```text
Panels Required
Panels Nested
Sheets Used
Remnants Used
New Sheets Used
Total Material Area
Panel Area
Waste Area
Utilization %
```

---

# 133. Waste Report

Show:

```text
Material
Thickness
Sheets
Total Area
Panel Area
Kerf
Trim
Waste
Utilization
```

---

# 134. Remnant Report

Show:

```text
Remnant Used
Remnant Created
Remnant Size
Material
Project
Nesting Job
```

---

# 135. Nesting Visualization

Frontend must display:

```text
sheet rectangle
panel rectangles
panel labels
grain arrows
edge indicators
defect zones
trim zones
```

---

# 136. Visualization Interaction

Support:

```text
zoom
pan
fit to sheet
select panel
highlight panel
toggle grain
toggle edges
toggle defects
```

---

# 137. Panel Selection

Selecting a panel should show:

```text
panel ID
dimensions
material
furniture
room
rotation
sheet
```

---

# 138. Sheet Selection

Selecting a sheet should show:

```text
sheet ID
material
dimensions
utilization
waste
panels
```

---

# 139. Visualization Accuracy

The nesting visualization must use the same geometry data as the actual nesting result.

Do not independently recalculate placement in the browser.

---

# 140. Browser Responsibility

Browser:

```text
render
inspect
filter
approve
```

Server/worker:

```text
optimize
validate
persist
```

---

# 141. Nesting Editor

Optional manual correction mode may allow:

```text
move panel
rotate panel
change sheet
```

but every manual change must be validated.

---

# 142. Manual Placement

If user manually moves a panel:

```text
mark result as MANUALLY_MODIFIED
```

---

# 143. Manual Placement Validation

Check:

```text
bounds
overlap
grain
edge
defect
trim
machine
```

---

# 144. Manual Rotation

Only allowed if:

```text
panel.rotation_allowed = true
```

---

# 145. Manual Override Audit

Store:

```text
user
old_position
new_position
old_rotation
new_rotation
reason
timestamp
```

---

# 146. Re-Optimize

User can request:

```text
Re-optimize remaining panels
```

The engine must preserve approved/manual placements when configured.

---

# 147. Locked Panels

Support:

```text
placement_locked
```

Locked panels are excluded from movement during optimization.

---

# 148. Locked Sheets

Support:

```text
sheet_locked
```

for approved/production-use sheets.

---

# 149. Optimization Modes

Support:

```text
ALL_NEW
REUSE_EXISTING
LOCKED_AREAS
REMAINING_ONLY
```

---

# 150. Cut Path Output

Nesting may output a canonical cut path:

```text
outer cut
internal cut
sequence
```

Actual machine code remains the responsibility of CNC post processing.

---

# 151. Cut Path Safety

Ensure:

```text
panel identity preserved
cut paths do not intersect incorrectly
kerf accounted
```

---

# 152. Cut Sequence Optimization

Optional optimization goals:

```text
minimum travel
minimum tool changes
safe cutting order
```

---

# 153. Cut Sequence Boundary

Do not embed machine-specific syntax in the nesting engine.

---

# 154. CNC Handoff

Nesting result must provide:

```text
sheet geometry
panel placement
panel orientation
cut geometry
```

to CNC engine.

---

# 155. CNC Handoff Contract

Example:

```json
{
  "sheet": {
    "width": 2440,
    "height": 1220,
    "thickness": 18
  },
  "panels": [
    {
      "panel_id": "P001",
      "x": 0,
      "y": 0,
      "width": 600,
      "height": 720,
      "rotation": 0
    }
  ],
  "kerf": 3.2
}
```

---

# 156. Nesting API — Generate

```http
POST /api/v1/nesting/jobs
```

Example:

```json
{
  "manufacturing_revision_id": "MFG-R4",
  "factory_id": "F01",
  "algorithm": "MAX_RECTS",
  "profile": "BALANCED"
}
```

---

# 157. Nesting API — Execute

```http
POST /api/v1/nesting/jobs/{id}/run
```

---

# 158. Nesting API — Status

```http
GET /api/v1/nesting/jobs/{id}
```

---

# 159. Nesting API — Result

```http
GET /api/v1/nesting/jobs/{id}/result
```

---

# 160. Nesting API — Sheets

```http
GET /api/v1/nesting/jobs/{id}/sheets
```

---

# 161. Nesting API — Panels

```http
GET /api/v1/nesting/jobs/{id}/panels
```

---

# 162. Nesting API — Re-optimize

```http
POST /api/v1/nesting/jobs/{id}/reoptimize
```

---

# 163. Nesting API — Manual Placement

```http
PATCH /api/v1/nesting/placements/{id}
```

---

# 164. Nesting API — Approve

```http
POST /api/v1/nesting/jobs/{id}/approve
```

---

# 165. Nesting API — Release

```http
POST /api/v1/nesting/jobs/{id}/release
```

---

# 166. Nesting API — Reserve Stock

```http
POST /api/v1/nesting/jobs/{id}/reserve-stock
```

---

# 167. Nesting API — Release Stock

```http
POST /api/v1/nesting/jobs/{id}/release-stock
```

---

# 168. Nesting API — Compare

```http
GET /api/v1/nesting/compare?job_a=...&job_b=...
```

---

# 169. Nesting API — Export

```http
GET /api/v1/nesting/jobs/{id}/export
```

Support:

```text
CSV
JSON
PDF layout
DXF where applicable
```

---

# 170. Database Tables

Minimum:

```text
nesting_jobs
nesting_job_inputs
nesting_job_constraints
nesting_job_objectives
nesting_groups
nesting_panels
nesting_panel_instances
nesting_sheets
nesting_sheet_defects
nesting_sheet_exclusions
nesting_placements
nesting_cut_paths
nesting_results
nesting_result_metrics
nesting_remnant_usage
nesting_reservations
nesting_manual_overrides
nesting_audit_logs
```

---

# 171. Nesting Job Table

Fields:

```text
id
tenant_id
factory_id
manufacturing_revision_id
status
algorithm
algorithm_version
profile
random_seed
max_runtime_ms
input_hash
output_hash
created_by
created_at
completed_at
```

---

# 172. Nesting Input Table

Fields:

```text
id
nesting_job_id
panel_id
instance_id
length
width
thickness
material_id
grain_direction
rotation_allowed
allowed_rotations
edge_data_json
priority
```

---

# 173. Nesting Sheet Table

Fields:

```text
id
nesting_job_id
sheet_id
material_id
length
width
thickness
grain_direction
sheet_type
source_type
cost
status
```

---

# 174. Defect Table

Fields:

```text
id
sheet_id
defect_type
geometry_type
geometry_json
clearance
severity
```

---

# 175. Placement Table

Fields:

```text
id
nesting_job_id
sheet_id
panel_instance_id
x
y
width
height
rotation
locked
placement_type
score
```

---

# 176. Cut Path Table

Fields:

```text
id
nesting_job_id
sheet_id
sequence
path_type
geometry_json
kerf
machine_profile_id
```

---

# 177. Result Metrics Table

Fields:

```text
id
nesting_job_id
sheet_count
panel_count
nested_panel_count
unplaced_panel_count
total_sheet_area
usable_area
panel_area
kerf_loss
trim_loss
defect_loss
waste_area
utilization_percent
material_cost
```

---

# 178. Manual Override Table

Fields:

```text
id
nesting_job_id
placement_id
user_id
old_data_json
new_data_json
reason
created_at
```

---

# 179. Reservation Table

Fields:

```text
id
nesting_job_id
sheet_id
remnant_id
quantity
status
reserved_at
released_at
```

---

# 180. Frontend Architecture

Recommended:

```text
/src/nesting/

domain/
  NestingJob.js
  NestingPanel.js
  NestingSheet.js
  NestingPlacement.js

engine/
  NestingEngine.js
  NestingAlgorithm.js

algorithms/
  MaxRects.js
  Guillotine.js
  Skyline.js

geometry/
  GeometryEngine.js
  Rectangle.js
  Polygon.js
  Collision.js
  Offset.js

constraints/
  GrainConstraint.js
  EdgeConstraint.js
  DefectConstraint.js
  MachineConstraint.js
  KerfConstraint.js

scoring/
  NestingScore.js
  ObjectiveFunction.js

visualization/
  NestingCanvas.js
  SheetRenderer.js
  PanelRenderer.js

manual/
  PlacementEditor.js
  PlacementValidator.js

reports/
  UtilizationReport.js
  WasteReport.js
```

---

# 181. PHP Architecture

Recommended:

```text
src/
  Nesting/
    Domain/
    Engine/
    Algorithms/
    Geometry/
    Constraints/
    Scoring/
    Inventory/
    Remnants/
    Validation/
    Visualization/
    Export/
    Repositories/
    DTO/
    Policies/
```

Services:

```text
NestingEngine
NestingInputBuilder
NestingValidator
NestingConstraintEngine
NestingScoringService
NestingResultService
RemnantService
SheetReservationService
NestingComparisonService
```

---

# 182. Algorithm Interface

Conceptually:

```php
interface NestingAlgorithmInterface
{
    public function solve(
        NestingInput $input,
        NestingConstraints $constraints,
        NestingObjective $objective
    ): NestingSolution;
}
```

---

# 183. Constraint Interface

Conceptually:

```php
interface NestingConstraintInterface
{
    public function validatePlacement(
        PanelInstance $panel,
        Placement $placement,
        NestingContext $context
    ): ConstraintResult;
}
```

---

# 184. Scoring Interface

Conceptually:

```php
interface NestingObjectiveInterface
{
    public function score(NestingSolution $solution): float;
}
```

---

# 185. Geometry Boundary

All geometry operations should be isolated:

```text
GeometryEngine
```

Do not scatter polygon mathematics across:

```text
controllers
repositories
UI components
```

---

# 186. Algorithm Independence

The rest of the application must not depend directly on:

```text
MaxRects
Guillotine
Skyline
```

Use the algorithm interface.

---

# 187. Nesting Configuration

Tenant/factory configuration should define:

```text
default_algorithm
default_profile
kerf
trim
minimum_gap
minimum_remnant
default_objective
```

---

# 188. Material Configuration

Material may define:

```text
grain
standard_sheet_sizes
allowed_thicknesses
default_kerf
edge_constraints
```

---

# 189. Machine Configuration

Machine may define:

```text
max_sheet_size
min_sheet_size
kerf
clamp_zones
vacuum_zones
cutting_method
supports_common_line
supports_irregular
```

---

# 190. Nesting Profiles

Support reusable profiles:

```text
CNC_STANDARD
PANEL_SAW_STANDARD
HIGH_UTILIZATION
LOW_WASTE
FAST_PRODUCTION
REMNANT_FIRST
```

---

# 191. Profile Versioning

Each profile must support:

```text
version
effective_from
effective_to
status
```

---

# 192. Rule Precedence

Recommended:

```text
Job Override
 ↓
Project Rule
 ↓
Factory Rule
 ↓
Tenant Rule
 ↓
System Default
```

---

# 193. Rule Audit

Every nesting result must record resolved:

```text
kerf
trim
gap
rotation rules
grain rules
objective
algorithm
```

---

# 194. Multi-Tenant Security

Every query must enforce:

```text
tenant_id
```

from authenticated context.

Never trust:

```text
tenant_id
```

from browser payload.

---

# 195. Factory Security

Users should only access nesting jobs for authorized factories.

---

# 196. Project Security

Users should only access nesting jobs for authorized projects.

---

# 197. RBAC

Minimum permissions:

```text
nesting.view
nesting.create
nesting.run
nesting.edit
nesting.reoptimize
nesting.manual_override
nesting.approve
nesting.release
nesting.export
nesting.reserve_stock
nesting.release_stock
nesting.admin
```

---

# 198. Audit Events

Record:

```text
NESTING_CREATED
NESTING_STARTED
NESTING_COMPLETED
NESTING_FAILED
NESTING_REOPTIMIZED
PLACEMENT_CHANGED
PLACEMENT_LOCKED
NESTING_APPROVED
NESTING_RELEASED
STOCK_RESERVED
STOCK_RELEASED
```

---

# 199. Performance Targets

Recommended:

```text
100 panels → < 1 second
500 panels → < 3 seconds
1,000 panels → < 10 seconds
5,000 panels → asynchronous
```

Actual performance depends on geometry and algorithm.

---

# 200. Memory

Avoid loading unlimited geometry into browser memory.

For large jobs:

```text
server-side calculation
paged result retrieval
progressive visualization
```

---

# 201. Async Nesting

Large jobs must support:

```text
QUEUE
RUN
PROGRESS
COMPLETE
FAIL
```

---

# 202. Progress Events

Expose:

```text
preprocessing
grouping
sheet allocation
optimization
validation
result persistence
```

---

# 203. Cancellation

Support:

```http
POST /api/v1/nesting/jobs/{id}/cancel
```

Cancellation must safely release temporary resources.

---

# 204. Retry

Failed async jobs may be retried.

Retries must not create duplicate released results.

---

# 205. Idempotency

Create/run requests should support:

```text
Idempotency-Key
```

for safe retries.

---

# 206. Caching

Cache:

```text
compiled constraints
material rules
machine rules
sheet definitions
geometry primitives
```

---

# 207. Result Caching

If:

```text
input_hash
+
algorithm_version
+
configuration
```

matches an existing valid result:

```text
reuse result
```

where policy allows.

---

# 208. Cache Invalidation

Invalidate when:

```text
panel geometry changes
material changes
sheet stock changes
machine profile changes
nesting rule changes
algorithm changes
```

---

# 209. Stock Change

If inventory changes after nesting:

```text
existing result becomes STALE
```

if it depends on unavailable stock.

---

# 210. Stale Nesting

Display:

```text
Nesting result is based on outdated stock.
Re-run nesting.
```

---

# 211. Manufacturing Revision Change

If BOM/manufacturing revision changes:

```text
nesting result = SUPERSEDED
```

---

# 212. Approval Freeze

Approved nesting must not be modified.

Manual changes require:

```text
new revision
```

or explicit controlled override workflow.

---

# 213. Nesting Comparison

Comparison should include:

```text
Revision
Algorithm
Sheets
Waste
Utilization
Cost
Remnants
Runtime
```

---

# 214. Cost Comparison Example

```text
Solution A:
10 sheets
Waste 8%
Cost ₹20,000

Solution B:
9 sheets
Waste 12%
Cost ₹18,500
```

The engine should allow the user to choose based on configured objectives.

---

# 215. Optimization Recommendation

The UI may recommend:

```text
Solution B saves ₹1,500
but increases waste by 4%
```

The recommendation must remain explainable.

---

# 216. Explainability

Every optimization result must be able to answer:

```text
Why was this sheet selected?
Why was this panel rotated?
Why could this panel not be placed?
Why was this remnant selected?
Why did this solution win?
```

---

# 217. Unplaced Panel Explanation

Example:

```text
Panel P103 not placed.

Reasons:
- no compatible sheet
- required orientation unavailable
- remaining stock insufficient
```

---

# 218. Placement Explanation

Optional:

```text
Panel P103 placed at X=1240,Y=600
Rotation=90°
Reason:
best score among valid candidates
```

---

# 219. Nesting Explainability Data

Store:

```text
candidate_score
selected_score
constraint_results
```

for debug mode.

---

# 220. Debug Mode

Administrators/developers can enable:

```text
NESTING_DEBUG
```

to inspect:

```text
candidate placements
collision failures
constraint failures
scores
```

Debug data should not be exposed to normal users.

---

# 221. Test Data

Create standard benchmark sets:

```text
SMALL_10
MEDIUM_100
LARGE_500
LARGE_1000
GRAIN_CONSTRAINED
REMNANT_HEAVY
DEFECT_HEAVY
MIXED_DIMENSIONS
```

---

# 222. Benchmark Metrics

Track:

```text
runtime
sheet count
utilization
waste
memory
```

---

# 223. Unit Tests

Minimum:

```text
rectangle collision
rotation
bounds
grain
edge restrictions
kerf
trim
defects
gap
sheet selection
```

---

# 224. Algorithm Tests

For each algorithm:

```text
same input
valid output
no overlap
all constraints satisfied
```

---

# 225. Optimization Tests

Verify:

```text
better solution scores higher
objective weights affect ranking
deterministic seed reproduces output
```

---

# 226. Geometry Tests

Test:

```text
rectangles
polygons
rotation
intersection
containment
offsets
precision
```

---

# 227. Inventory Tests

Test:

```text
available sheet
reserved sheet
insufficient stock
remnant
reservation release
```

---

# 228. Manual Override Tests

Verify:

```text
manual placement valid
invalid placement blocked
override audited
locked placement preserved
```

---

# 229. Revision Tests

Verify:

```text
Nesting v1
→ approved

Manufacturing v2
→ Nesting v2

Nesting v1 unchanged
```

---

# 230. Security Tests

Verify:

```text
Tenant A cannot see Tenant B nesting
Factory A cannot see Factory B restricted jobs
Unauthorized user cannot approve
Unauthorized user cannot override
```

---

# 231. Performance Tests

Benchmark:

```text
100
500
1000
5000
```

panels.

---

# 232. Failure Recovery

If nesting fails:

```text
job = FAILED
```

and no production release should occur.

---

# 233. Transaction Safety

Result persistence should use transaction boundaries.

Do not leave:

```text
half-created nesting result
```

after database failure.

---

# 234. File Export

Exports must reference:

```text
nesting_revision
```

and include:

```text
generated_at
algorithm
version
```

---

# 235. CSV Export

Minimum columns:

```text
Sheet
Panel
Material
Length
Width
Thickness
X
Y
Rotation
Grain
```

---

# 236. JSON Export

JSON should contain:

```text
job
algorithm
configuration
sheets
placements
metrics
```

---

# 237. PDF Layout

PDF should display:

```text
sheet layout
panel labels
dimensions
material
utilization
waste
```

---

# 238. DXF Export

Where supported, DXF should contain:

```text
sheet boundary
panel boundaries
cut paths
panel IDs
```

---

# 239. CNC Boundary

Nesting does not generate final vendor-specific CNC syntax.

It provides:

```text
canonical geometry
```

to the CNC/Post Processor subsystem.

---

# 240. Integration With Manufacturing Engine

```text
Manufacturing Engine
        ↓
Panel List
        ↓
Nesting Engine
        ↓
Nesting Result
        ↓
Manufacturing Engine
        ↓
CNC / Production
```

---

# 241. Integration With Pricing Engine

Nesting can provide:

```text
sheets_used
material_area
waste_area
remnants_used
```

to pricing.

---

# 242. Integration With Inventory

Nesting can:

```text
request available stock
reserve stock
release stock
```

Inventory performs actual consumption.

---

# 243. Integration With MES

MES consumes:

```text
nesting revision
sheet assignments
panel placement
cut sequence
```

---

# 244. Integration With Label Engine

Every placement must retain:

```text
panel_instance_id
```

so labels can be generated correctly.

---

# 245. Integration With Procurement

Nesting can produce:

```text
new sheets required
remnants required
material shortage
```

for procurement planning.

---

# 246. Integration With BOQ

Nesting can provide actual:

```text
sheet procurement quantity
```

for BOQ/procurement calculations.

---

# 247. End-to-End Example

Input:

```text
Material: 18mm MDF
Sheet: 2440 × 1220
Kerf: 3.2 mm

Panels:
A = 600 × 720 × 2
B = 450 × 700 × 4
C = 300 × 500 × 6
D = 900 × 400 × 2
```

Engine:

```text
Validate
 ↓
Group
 ↓
Sort
 ↓
Generate candidate placements
 ↓
Check grain
 ↓
Check kerf
 ↓
Check trim
 ↓
Check overlap
 ↓
Score
 ↓
Select best solution
```

Output:

```text
Sheet 1
 ├── A1
 ├── A2
 ├── B1
 ├── B2
 └── C1

Sheet 2
 ├── B3
 ├── B4
 ├── C2
 ├── C3
 ├── C4
 ├── C5
 ├── C6
 ├── D1
 └── D2
```

Actual layout is determined by the algorithm.

---

# 248. Acceptance Criteria — Validity

The engine must guarantee:

```text
[ ] No panel overlaps another
[ ] No panel exceeds sheet bounds
[ ] No panel enters trim zone
[ ] No panel enters defect zone
[ ] No panel violates exclusion zones
[ ] Grain restrictions are respected
[ ] Rotation restrictions are respected
[ ] Edge restrictions are respected
[ ] Kerf is accounted for
[ ] Minimum gap is respected
```

---

# 249. Acceptance Criteria — Optimization

The engine must:

```text
[ ] Minimize sheet count where configured
[ ] Minimize waste where configured
[ ] Maximize utilization where configured
[ ] Support weighted objectives
[ ] Support multiple candidate solutions
[ ] Support optimization profiles
[ ] Support deterministic results
[ ] Support runtime limits
```

---

# 250. Acceptance Criteria — Traceability

Every placed panel must be traceable:

```text
Placement
 ↓
Panel Instance
 ↓
Logical Panel
 ↓
Manufacturing Component
 ↓
BOM
 ↓
Furniture
 ↓
Room
 ↓
Project
```

Every sheet must be traceable:

```text
Placement
 ↓
Sheet
 ↓
Inventory / Remnant
 ↓
Material
```

---

# 251. Acceptance Criteria — Production

The nesting output must be usable by:

```text
Cutlist
CNC
Panel Saw
Label Printing
MES
Inventory
Procurement
Pricing
```

---

# 252. Acceptance Criteria — Revision

When manufacturing data changes:

```text
old nesting remains immutable
new nesting revision is generated
old production output is not silently modified
```

---

# 253. Cursor Pre-Implementation Analysis

Before implementing, Cursor MUST inspect the existing codebase for:

```text
2D CAD geometry
3D/BIM geometry
Parametric Furniture Engine
Material Catalog
BOM
BOQ
Manufacturing Engine
Cutlist
CNC
MES
Inventory
Pricing
Revision management
File storage
```

Cursor must identify:

```text
CURRENT NESTING LOGIC
CURRENT PANEL GEOMETRY
CURRENT CUTLIST
CURRENT MATERIAL DATA
CURRENT SHEET DATA
CURRENT CNC DATA
CURRENT INVENTORY DATA
CURRENT REVISION MODEL
DUPLICATE LOGIC
MISSING CAPABILITIES
DATABASE GAPS
API GAPS
PERFORMANCE RISKS
```

Then produce:

```text
CURRENT STATE
TARGET STATE
MIGRATION PLAN
IMPLEMENTATION PLAN
TEST PLAN
```

Do not create a second nesting implementation if one already exists.

---

# 254. Cursor Implementation Sequence

## Phase 1 — Geometry Foundation

```text
Rectangle
Polygon
Collision
Rotation
Offset
Precision
```

## Phase 2 — Domain Model

```text
Panel
Panel Instance
Sheet
Remnant
Defect
Placement
```

## Phase 3 — Constraints

```text
Grain
Rotation
Edge
Kerf
Trim
Defect
Machine
Gap
```

## Phase 4 — Algorithm

```text
MaxRects
Guillotine
Algorithm Interface
Scoring
```

## Phase 5 — Inventory

```text
Sheet Availability
Remnants
Reservations
```

## Phase 6 — Optimization

```text
Objective
Weights
Profiles
Candidates
Deterministic Seed
```

## Phase 7 — Visualization

```text
Sheet Viewer
Panel Viewer
Placement Editor
```

## Phase 8 — Production Handoff

```text
Cut Paths
CNC Handoff
Labels
MES
```

## Phase 9 — Governance

```text
Revision
Approval
Audit
RBAC
```

## Phase 10 — Performance

```text
Async Jobs
Caching
Incremental Optimization
Benchmarks
```

---

# 255. Recommended Service Flow

```text
NestingRequest
      ↓
InputBuilder
      ↓
InputValidator
      ↓
PanelNormalizer
      ↓
GroupBuilder
      ↓
SheetResolver
      ↓
ConstraintResolver
      ↓
AlgorithmSelector
      ↓
NestingAlgorithm
      ↓
SolutionScorer
      ↓
ResultValidator
      ↓
MetricsCalculator
      ↓
ResultPersistence
      ↓
StockReservation
      ↓
CNC / MES Handoff
```

---

# 256. Final Architecture

```text
                         NESTING ENGINE
                              │
       ┌──────────────────────┼──────────────────────┐
       ↓                      ↓                      ↓
     PANELS                  SHEETS                RULES
       │                      │                      │
       └──────────────────────┼──────────────────────┘
                              ↓
                     PREPROCESSING
                              ↓
                       GROUPING
                              ↓
                    CONSTRAINT ENGINE
                              ↓
                     OPTIMIZATION
                              ↓
          ┌───────────────────┼───────────────────┐
          ↓                   ↓                   ↓
       PLACEMENT           SCORING            VALIDATION
          │                   │                   │
          └───────────────────┼───────────────────┘
                              ↓
                       NESTING RESULT
                              ↓
              ┌───────────────┼───────────────┐
              ↓               ↓               ↓
           CUTLIST          CNC              MES
              │               │               │
              ↓               ↓               ↓
          PROCUREMENT      MACHINE         PRODUCTION
```

---

# 257. Final Product Principle

The Nesting Engine must answer:

```text
Which sheets should be used?
Which panels go on each sheet?
Where exactly should each panel be placed?
Can the panel rotate?
What grain direction must be preserved?
What edge restrictions apply?
What kerf is required?
What trim is required?
Are there defects?
Are there machine exclusion zones?
How much material is wasted?
How many sheets are required?
Can an existing remnant be reused?
Why was this layout selected?
Can the exact result be reproduced?
Which manufacturing revision produced it?
```

The fundamental rule is:

> **Nesting must optimize material usage without ever violating engineering intent, grain direction, manufacturing constraints, machine constraints, or panel traceability.**

The final output must remain traceable:

```text
Optimized Placement
        ↓
Panel Instance
        ↓
Panel
        ↓
Manufacturing Component
        ↓
BOM Item
        ↓
Furniture
        ↓
Room
        ↓
Project
```

And the material must remain traceable:

```text
Panel Placement
        ↓
Sheet / Remnant
        ↓
Inventory
        ↓
Material
        ↓
Supplier / Catalog
```

The engine should therefore be treated as a **specialized optimization service between Manufacturing Engineering and CNC/MES**, rather than as a feature embedded inside the 2D CAD UI.
