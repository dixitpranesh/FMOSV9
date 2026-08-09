# CNC / CAM Engine Specification
## Interior Design → Parametric Furniture → Nesting → CNC/CAM → MES

**Document ID:** CNC-CAM-ENG-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, CNC Engineers, Manufacturing Engineers, Factory Managers, MES Developers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Geometry Unit:** mm  
**Primary Time Unit:** seconds  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the requirements for the **CNC/CAM Engine** that converts validated manufacturing and nesting data into machine-ready manufacturing programs.

The CNC/CAM Engine must bridge:

```text
Parametric Furniture
        ↓
Manufacturing BOM
        ↓
Panels
        ↓
Machining Operations
        ↓
Nesting
        ↓
Canonical CNC Model
        ↓
Toolpath Generation
        ↓
Machine Post Processor
        ↓
Machine-Specific CNC/CAM File
        ↓
Factory / MES
```

The engine must support the production of:

```text
Drilling
Boring
Routing
Grooving
Pockets
Contours
Profiles
Cutouts
Dadoes
Rebates
Hinge cups
Dowel holes
Cam holes
Screw holes
Shelf pin holes
Handle drilling
Connector drilling
```

The architecture must support machine-specific outputs such as:

```text
DXF
G-code
CSV
XML
vendor-specific CNC formats
```

Examples of machine ecosystems may include:

```text
Biesse
Homag
KDT
SCM
Generic CNC
```

Vendor-specific formats must be implemented through post processors rather than hard-coded into the core engine.

---

# 2. Core Principle

The CNC/CAM Engine must use a **canonical machining representation**.

The core engine must NOT directly generate vendor-specific machine syntax.

Architecture:

```text
Manufacturing Operations
        ↓
Canonical CNC Model
        ↓
Toolpath / CAM Engine
        ↓
Machine Profile
        ↓
Post Processor
        ↓
Machine File
```

This allows the same furniture design to be manufactured on different machines without changing the furniture engineering logic.

---

# 3. CNC/CAM Responsibilities

The engine owns:

```text
Machining operation validation
Tool selection
Toolpath generation
Cut path generation
Drilling path generation
Routing path generation
Grooving path generation
Pocket path generation
Contour path generation
Operation sequencing
Tool changes
Machine limits validation
Coordinate transformation
Machine-specific post processing
CNC file generation
CNC file validation
Program versioning
Program checksum
Simulation data
CAM preview
```

---

# 4. Non-Responsibilities

The CNC/CAM Engine must not own:

```text
2D design authoring
3D BIM authoring
Furniture parametric design
BOM ownership
Material catalog ownership
Commercial pricing
Inventory ownership
Production scheduling
Accounting
```

It consumes data from those modules.

---

# 5. Source of Truth

CNC/CAM consumes:

```text
Manufacturing Revision
Panel Definition
Panel Geometry
Machining Operations
Nesting Result
Material
Machine Profile
Tool Library
Manufacturing Rules
Post Processor
```

---

# 6. Canonical CNC Model

Every machine program must originate from a canonical model:

```text
CNCJob
 ├── Machine
 ├── Material
 ├── Sheet
 ├── Panel
 ├── Setup
 ├── Tools
 ├── Operations
 │    ├── Drill
 │    ├── Route
 │    ├── Groove
 │    ├── Pocket
 │    ├── Contour
 │    └── Cut
 └── Program Metadata
```

---

# 7. CNC Job

A CNC job represents a machine-ready manufacturing program.

Fields:

```text
cnc_job_id
manufacturing_revision_id
nesting_revision_id
machine_profile_id
material_id
sheet_id
status
program_version
```

---

# 8. CNC Job Status

Support:

```text
DRAFT
GENERATING
GENERATED
VALIDATING
VALID
INVALID
APPROVED
RELEASED
SUPERSEDED
CANCELLED
```

---

# 9. CNC Program Identity

Every generated program must have:

```text
program_id
program_number
program_version
machine_profile_version
post_processor_version
manufacturing_revision
nesting_revision
```

---

# 10. Program Immutability

Once a CNC program is:

```text
APPROVED
```

or:

```text
RELEASED
```

it must not be overwritten.

Changes create:

```text
new program version
```

---

# 11. CNC Program Traceability

Every CNC program must be traceable to:

```text
CNC Program
 ↓
Post Processor
 ↓
Machine Profile
 ↓
Nesting Revision
 ↓
Manufacturing Revision
 ↓
BOM Revision
 ↓
Furniture Revision
 ↓
Design Revision
```

---

# 12. Coordinate System

The canonical system must use:

```text
X = horizontal
Y = vertical
Z = tool depth
```

with:

```text
positive Z = above panel surface
negative Z = into panel
```

The exact machine coordinate system is transformed by the post processor.

---

# 13. Coordinate Origin

Canonical origin must be configurable:

```text
TOP_LEFT
TOP_RIGHT
BOTTOM_LEFT
BOTTOM_RIGHT
CENTER
CUSTOM
```

Machine profile determines actual machine origin.

---

# 14. Panel Coordinate System

Each panel has a local coordinate system:

```text
(0,0)
  ┌──────────────────────→ X
  │
  │
  │
  ↓
  Y
```

---

# 15. Sheet Coordinate System

Nesting defines panel position:

```text
sheet_x
sheet_y
rotation
```

The CNC engine combines:

```text
sheet coordinate
+
panel local coordinate
```

to generate machine coordinates.

---

# 16. Face Model

Support:

```text
TOP
BOTTOM
FRONT
BACK
LEFT
RIGHT
```

---

# 17. Face Mapping

Machine profile maps canonical faces to machine faces.

Example:

```text
TOP → machine top
BOTTOM → machine bottom
```

or:

```text
TOP → machine face 1
BOTTOM → machine face 2
```

---

# 18. Two-Sided Machining

Support:

```text
SINGLE_FACE
DOUBLE_FACE
MULTI_SETUP
```

---

# 19. Setup

A CNC setup represents one machine orientation.

Fields:

```text
setup_id
sequence
face
origin
fixture
vacuum_configuration
```

---

# 20. Setup Sequence

Example:

```text
Setup 1
TOP

Setup 2
BOTTOM
```

---

# 21. Flip Operation

If a panel requires second-side machining:

```text
FLIP_REQUIRED
```

must be generated.

---

# 22. Flip Traceability

Store:

```text
original_orientation
flipped_orientation
flip_axis
flip_reference
```

---

# 23. Tool Library

Every CNC machine must have a tool library.

Tool fields:

```text
tool_id
tool_code
name
type
diameter
cutting_length
overall_length
shank_diameter
max_depth
rotation_direction
```

---

# 24. Tool Types

Minimum:

```text
DRILL
END_MILL
UPCUT
DOWNCUT
COMPRESSION
VBIT
BALL_NOSE
FORM_TOOL
SAW
SPECIAL
```

---

# 25. Tool Parameters

Support:

```text
diameter
flute_count
cutting_length
feed_rate
plunge_rate
rpm
max_depth
step_down
step_over
```

---

# 26. Tool Compatibility

Tool compatibility depends on:

```text
machine
material
thickness
operation
```

---

# 27. Tool Assignment

Operations may specify:

```text
explicit tool
tool class
automatic tool selection
```

---

# 28. Automatic Tool Selection

If automatic selection is enabled, choose a compatible tool based on:

```text
operation
diameter
depth
material
machine
tool availability
```

---

# 29. Tool Change

The engine must group operations to minimize unnecessary:

```text
tool changes
```

subject to machining safety.

---

# 30. Tool Change Optimization

Recommended sequence:

```text
Tool 1
 → all compatible operations

Tool 2
 → all compatible operations
```

unless operation dependencies require another order.

---

# 31. Operation Model

Every operation must contain:

```text
operation_id
panel_id
sequence
type
face
geometry
tool
depth
feed
speed
```

---

# 32. Operation Types

Minimum:

```text
DRILL
BORE
HINGE_CUP
ROUTE
CONTOUR
POCKET
GROOVE
DADO
REBATE
CUT
PROFILE
CHAMFER
ENGRAVE
```

---

# 33. Drill Operation

Fields:

```text
x
y
diameter
depth
tool
face
```

---

# 34. Through Hole

Support:

```text
THROUGH_HOLE
```

Depth calculation:

```text
panel_thickness + safety_allowance
```

must be controlled by machine/material rules.

---

# 35. Blind Hole

Support:

```text
BLIND_HOLE
```

with explicit:

```text
depth
bottom_clearance
```

---

# 36. Hinge Cup

Support:

```text
cup_diameter
cup_depth
x
y
```

Example:

```text
35 mm cup
12 mm depth
```

Actual values must come from hardware/manufacturing rules.

---

# 37. Dowel Drilling

Support:

```text
dowel_diameter
dowel_depth
spacing
pattern
```

---

# 38. Shelf Pin Drilling

Support:

```text
diameter
vertical_pitch
start_offset
hole_count
```

---

# 39. Cam Drilling

Support:

```text
cam_diameter
cam_depth
connector_position
```

---

# 40. Screw Drilling

Support:

```text
pilot_hole
clearance_hole
countersink
```

---

# 41. Routing Operation

Routing supports:

```text
line
polyline
arc
circle
polygon
bezier
```

where supported by geometry engine.

---

# 42. Contour Routing

Support:

```text
inside contour
outside contour
centerline
```

---

# 43. Tool Compensation

Support:

```text
LEFT
RIGHT
CENTER
```

compensation.

The post processor may convert canonical compensation into machine-specific syntax.

---

# 44. Pocket Operation

Fields:

```text
boundary
depth
step_down
step_over
tool
```

---

# 45. Pocket Strategy

Support:

```text
OFFSET
RASTER
SPIRAL
ADAPTIVE
```

---

# 46. Groove Operation

Fields:

```text
path
width
depth
tool
face
```

---

# 47. Dado Operation

Support:

```text
width
depth
length
position
```

---

# 48. Rebate Operation

Support:

```text
width
depth
length
face
```

---

# 49. Profile Operation

Support:

```text
profile geometry
depth
tool
direction
```

---

# 50. Cut Operation

Support:

```text
sheet cut
panel separation
internal cut
external cut
```

---

# 51. Cut Direction

Support:

```text
CLIMB
CONVENTIONAL
AUTO
```

---

# 52. Feed Rate

Every operation should support:

```text
feed_rate
```

in:

```text
mm/min
```

---

# 53. Plunge Rate

Support:

```text
plunge_rate
```

---

# 54. Spindle Speed

Support:

```text
rpm
```

---

# 55. Depth Per Pass

For routing:

```text
step_down
```

must be configurable.

---

# 56. Step Over

For pockets:

```text
step_over
```

must be configurable.

---

# 57. Ramp Entry

Support:

```text
vertical plunge
ramp
helix
```

where machine/tool permits.

---

# 58. Lead-In

Support:

```text
lead_in
```

for contour operations.

---

# 59. Lead-Out

Support:

```text
lead_out
```

for contour operations.

---

# 60. Safe Z

Machine profile defines:

```text
safe_z
```

for rapid travel.

---

# 61. Clearance Plane

Support:

```text
clearance_z
```

above material.

---

# 62. Rapid Movement

Rapid moves must never enter:

```text
material
fixture
clamp zone
vacuum obstruction
```

---

# 63. Plunge Safety

Validate:

```text
plunge depth
material thickness
tool cutting length
machine Z limit
```

---

# 64. Machine Profile

Machine profile must define:

```text
machine_id
manufacturer
model
controller
machine_type
axis_count
work_area
coordinate_system
origin
limits
post_processor
```

---

# 65. Machine Types

Support:

```text
CNC_ROUTER
POINT_TO_POINT
BORING_MACHINE
DRILLING_CENTER
PANEL_PROCESSING_CENTER
```

---

# 66. Axis Support

Support:

```text
3-axis
4-axis
5-axis
```

architecture.

---

# 67. Axis Limits

Store:

```text
x_min
x_max
y_min
y_max
z_min
z_max
```

and:

```text
a/b/c
```

where applicable.

---

# 68. Rotary Axes

Future support:

```text
A
B
C
```

must be possible without changing the canonical operation model.

---

# 69. Machine Work Envelope

Validate all generated paths against:

```text
machine work envelope
```

---

# 70. Machine Safety Zones

Support:

```text
clamp zones
vacuum zones
tool changer zone
machine exclusion zone
```

---

# 71. Material Profile

Material profile should define:

```text
material_id
thickness
hardness_class
recommended_feed
recommended_rpm
recommended_step_down
```

---

# 72. Machining Presets

Support:

```text
material + tool + operation
```

preset combinations.

---

# 73. Cutting Preset

Example:

```text
18mm MDF
Compression bit 6mm
RPM 18000
Feed 5000 mm/min
Stepdown 6mm
```

Values are configurable and illustrative.

---

# 74. Preset Versioning

Every preset must be versioned.

---

# 75. Manufacturing Rule Integration

The CNC engine consumes operations generated by the Manufacturing Rule Engine.

It must not independently reinterpret furniture engineering rules.

---

# 76. Example Rule

```json
{
  "rule_code": "HINGE_35",
  "operation_type": "HINGE_CUP",
  "diameter": 35,
  "depth": 12,
  "tool_class": "HINGE_CUTTER"
}
```

---

# 77. Rule Traceability

Each operation must retain:

```text
rule_id
rule_version
```

when generated from a rule.

---

# 78. Geometry Source

Operation geometry may originate from:

```text
parametric furniture
hardware rule
nesting
manual CAM operation
```

---

# 79. Manual CAM Operation

Authorized users may create:

```text
manual drill
manual route
manual groove
manual pocket
```

---

# 80. Manual CAM Audit

Manual operations require:

```text
user
timestamp
reason
operation data
```

---

# 81. Operation Dependencies

Operations may require:

```text
operation A before B
```

Example:

```text
rough pocket
→ finish contour
```

---

# 82. Operation Ordering

Ordering should consider:

```text
setup
face
tool
operation dependency
machine safety
```

---

# 83. Recommended Default Sequence

Typical:

```text
1 Reference / drilling
2 Internal pockets
3 Grooves
4 Internal contours
5 External contour
6 Separation
```

Actual sequence must be machine/material configurable.

---

# 84. Tool Change Grouping

Group operations by:

```text
setup
tool
face
```

while respecting dependencies.

---

# 85. Program Header

Generated machine files should include metadata where format allows:

```text
Program ID
Project
Job
Panel
Material
Thickness
Machine
Revision
Generated timestamp
```

Do not include sensitive commercial information unless explicitly configured.

---

# 86. Program Footer

Where machine format supports:

```text
program end
spindle stop
coolant stop
safe position
```

---

# 87. Machine Initialization

Post processor should support:

```text
units
absolute/incremental mode
plane selection
coordinate system
spindle state
tool state
```

---

# 88. Units

Canonical:

```text
mm
```

Machine output may require:

```text
mm
inch
```

according to machine profile.

---

# 89. Absolute / Incremental

Support:

```text
ABSOLUTE
INCREMENTAL
```

machine output modes.

Default recommendation:

```text
ABSOLUTE
```

where supported.

---

# 90. Arc Representation

Canonical model should support:

```text
arc center
radius
start angle
end angle
clockwise
counterclockwise
```

---

# 91. Arc Output

Post processor converts arcs to:

```text
machine-specific arc syntax
```

or linear segments if required.

---

# 92. Arc Tolerance

If arcs are converted to lines:

```text
chordal_tolerance
```

must be configurable.

---

# 93. Curve Approximation

Support:

```text
max_segment_length
max_deviation
```

---

# 94. Spline Support

Canonical geometry may support:

```text
Bezier
B-spline
```

Post processor may approximate if machine does not support them.

---

# 95. Polygon Support

Canonical geometry must support:

```text
closed polygon
```

for contours.

---

# 96. Geometry Validation

Validate:

```text
self intersection
open contour
duplicate points
zero-length segment
invalid arc
```

---

# 97. Contour Closure

Closed operations must explicitly indicate:

```text
closed = true
```

---

# 98. Toolpath Offset

For cutter diameter:

```text
tool radius
```

must be considered when generating compensated toolpaths.

---

# 99. Offset Engine

Use isolated:

```text
ToolpathOffsetEngine
```

for:

```text
inside offset
outside offset
centerline
```

---

# 100. Toolpath Validation

Check:

```text
toolpath stays within valid geometry
toolpath does not cross forbidden zones
depth is valid
tool is valid
```

---

# 101. Tabs / Bridges

For through-cut contours:

Support:

```text
tab_count
tab_width
tab_height
tab_positions
```

---

# 102. Tab Strategy

Support:

```text
AUTO
MANUAL
NONE
```

---

# 103. Small-Part Strategy

Small panels may require:

```text
tabs
vacuum support
sacrificial holding
```

depending on machine configuration.

---

# 104. Vacuum Fixture

Machine profile may define:

```text
vacuum zones
vacuum pods
fixture areas
```

---

# 105. Fixture Awareness

Toolpaths must not enter:

```text
fixture
pod
clamp
```

zones.

---

# 106. Clamp Collision

CNC validation must detect potential:

```text
tool / clamp collision
```

when fixture data is available.

---

# 107. Tool Collision

Support basic tool collision checks against:

```text
fixture
clamp
machine limits
```

---

# 108. Multi-Toolpath Collision

Operations must be checked for:

```text
unintended collision
```

where practical.

---

# 109. Simulation Model

Provide canonical simulation data:

```text
stock
tool
toolpath
operations
fixture
```

---

# 110. CAM Simulation

Frontend should render:

```text
stock
toolpath
operation order
tool position
```

---

# 111. Simulation Controls

Support:

```text
play
pause
step
restart
speed
operation selection
```

---

# 112. Simulation Color Coding

Use configurable visualization categories:

```text
drilling
routing
grooving
cutting
rapid
```

---

# 113. Simulation Accuracy

Simulation must use the same canonical toolpaths used for machine output.

Do not maintain a separate approximate toolpath implementation.

---

# 114. Toolpath Preview

Show:

```text
rapid moves
cutting moves
tool changes
depth
operation sequence
```

---

# 115. Program Validation

Before release:

```text
geometry validation
tool validation
machine validation
coordinate validation
depth validation
sequence validation
post processor validation
```

must pass.

---

# 116. Validation Severity

Support:

```text
INFO
WARNING
ERROR
BLOCKER
```

---

# 117. CNC Validation Examples

Block:

```text
tool not found
invalid depth
outside machine boundary
unknown operation
missing machine profile
missing post processor
invalid geometry
```

---

# 118. Warning Examples

Warn:

```text
unusual feed rate
low utilization
manual CAM operation
approaching machine limit
```

---

# 119. Machine Limit Validation

Every point must satisfy:

```text
x_min <= x <= x_max
y_min <= y <= y_max
z_min <= z <= z_max
```

---

# 120. Material Thickness Validation

For through-cut:

```text
depth >= material thickness
```

subject to configured machine/material safety allowance.

---

# 121. Blind Depth Validation

For blind holes:

```text
depth < material thickness
```

with minimum remaining material.

---

# 122. Tool Length Validation

Require:

```text
tool_cutting_length >= required_depth
```

---

# 123. Diameter Validation

Require:

```text
tool_diameter
```

compatible with operation geometry.

---

# 124. Minimum Feature Size

Material/tool profile may define:

```text
minimum_slot_width
minimum_inside_radius
minimum_hole_diameter
```

---

# 125. Feature Manufacturability

Block or warn if:

```text
feature smaller than tool capability
```

---

# 126. Post Processor Architecture

Use:

```text
PostProcessorInterface
```

with methods conceptually:

```text
beginProgram()
beginSetup()
changeTool()
rapidMove()
linearMove()
arcMove()
drill()
spindleOn()
spindleOff()
endSetup()
endProgram()
```

---

# 127. Post Processor Interface

Conceptually:

```php
interface PostProcessorInterface
{
    public function generate(CncProgram $program): PostProcessResult;
}
```

---

# 128. Post Processor Examples

Architecture may provide:

```text
GenericGCodePostProcessor
BiessePostProcessor
HomagPostProcessor
KdtPostProcessor
DxfPostProcessor
```

Only implement formats for which machine specifications are known and validated.

---

# 129. Generic G-code

Generic G-code post processor should support:

```text
G0
G1
G2
G3
tool change
spindle
feed
program end
```

Actual controller dialect must be configured.

---

# 130. DXF Output

DXF exporter should support:

```text
sheet boundary
panel boundary
holes
slots
grooves
labels
layer mapping
```

---

# 131. DXF Layers

Configurable layers:

```text
OUTLINE
HOLES
GROOVES
ROUTES
CUT
REFERENCE
LABEL
```

---

# 132. DXF Units

Export must explicitly define:

```text
millimetres
```

where format supports it.

---

# 133. CSV Output

For machines accepting CSV-like formats:

```text
panel
operation
x
y
z
tool
depth
diameter
```

---

# 134. XML Output

Architecture should support machine XML through post processors.

Do not embed XML schema assumptions in the core engine.

---

# 135. Vendor Format Configuration

Each post processor should define:

```text
file_extension
encoding
decimal_precision
coordinate_precision
header
footer
tool_change_format
spindle_format
feed_format
```

---

# 136. Decimal Formatting

Machine profile defines:

```text
decimal_places
decimal_separator
```

Default:

```text
.
```

---

# 137. Coordinate Precision

Output precision should be machine-configurable.

---

# 138. Program Naming

Configurable naming:

```text
{project}_{job}_{panel}_{revision}
```

Example:

```text
PRJ001_JOB002_SHEET01_R04.nc
```

---

# 139. File Naming Safety

Sanitize:

```text
path separators
invalid characters
reserved names
```

---

# 140. File Storage

Store machine files through:

```text
FileStorageService
```

supporting:

```text
local
S3-compatible
cloud
```

---

# 141. File Metadata

Store:

```text
file_name
mime_type
storage_key
size
checksum
created_at
```

---

# 142. Checksum

Use cryptographic hash:

```text
SHA-256
```

for released machine files.

---

# 143. File Integrity

Before release:

```text
generate checksum
persist checksum
```

Before download:

```text
verify file exists
```

---

# 144. Program Download

API:

```http
GET /api/v1/cnc/programs/{id}/download
```

Authorization must be checked server-side.

---

# 145. Program Release

API:

```http
POST /api/v1/cnc/programs/{id}/release
```

Release requires:

```text
valid program
authorized user
valid machine profile
```

---

# 146. Program Approval

API:

```http
POST /api/v1/cnc/programs/{id}/approve
```

---

# 147. Program Regeneration

API:

```http
POST /api/v1/cnc/jobs/{id}/regenerate
```

This must create a new program version.

---

# 148. CNC Job API

```http
POST /api/v1/cnc/jobs
GET /api/v1/cnc/jobs
GET /api/v1/cnc/jobs/{id}
POST /api/v1/cnc/jobs/{id}/generate
POST /api/v1/cnc/jobs/{id}/validate
POST /api/v1/cnc/jobs/{id}/approve
POST /api/v1/cnc/jobs/{id}/release
```

---

# 149. Tool API

```http
GET /api/v1/cnc/tools
POST /api/v1/cnc/tools
PATCH /api/v1/cnc/tools/{id}
```

---

# 150. Machine API

```http
GET /api/v1/cnc/machines
POST /api/v1/cnc/machines
PATCH /api/v1/cnc/machines/{id}
```

---

# 151. Post Processor API

```http
GET /api/v1/cnc/post-processors
POST /api/v1/cnc/post-processors
PATCH /api/v1/cnc/post-processors/{id}
```

---

# 152. Operation API

```http
GET /api/v1/cnc/jobs/{id}/operations
GET /api/v1/cnc/operations/{id}
```

---

# 153. Simulation API

```http
GET /api/v1/cnc/jobs/{id}/simulation
```

For large jobs, simulation may be asynchronous.

---

# 154. Validation API

```http
POST /api/v1/cnc/jobs/{id}/validate
GET /api/v1/cnc/jobs/{id}/validation
```

---

# 155. Database Tables

Minimum:

```text
cnc_jobs
cnc_programs
cnc_program_versions
cnc_operations
cnc_toolpaths
cnc_toolpath_segments
cnc_setups
cnc_tools
cnc_tool_assignments
cnc_machine_profiles
cnc_machine_limits
cnc_machine_exclusions
cnc_post_processors
cnc_material_profiles
cnc_machining_presets
cnc_validation_results
cnc_simulation_jobs
cnc_program_files
cnc_program_audit_logs
cnc_manual_operations
```

---

# 156. CNC Jobs Table

Fields:

```text
id
tenant_id
factory_id
manufacturing_revision_id
nesting_revision_id
machine_profile_id
status
program_version
created_by
created_at
updated_at
```

---

# 157. CNC Program Table

Fields:

```text
id
cnc_job_id
program_number
version
status
post_processor_id
post_processor_version
machine_profile_version
input_hash
output_hash
checksum
file_id
generated_at
approved_at
released_at
```

---

# 158. CNC Operation Table

Fields:

```text
id
cnc_job_id
panel_id
setup_id
sequence
operation_type
face
tool_id
geometry_json
depth
feed_rate
plunge_rate
rpm
step_down
step_over
status
source_rule_id
source_rule_version
```

---

# 159. Tool Table

Fields:

```text
id
machine_id
tool_code
name
type
diameter
flute_count
cutting_length
overall_length
max_depth
rpm_min
rpm_max
status
version
```

---

# 160. Machine Profile Table

Fields:

```text
id
factory_id
name
manufacturer
model
controller
machine_type
axis_count
work_area_json
coordinate_system_json
limits_json
fixture_json
post_processor_id
version
status
```

---

# 161. Post Processor Table

Fields:

```text
id
name
format
version
configuration_json
status
```

---

# 162. Material Profile Table

Fields:

```text
id
material_id
thickness
feed_rate
plunge_rate
rpm
step_down
step_over
minimum_feature
version
```

---

# 163. Preset Table

Fields:

```text
id
machine_id
material_id
tool_id
operation_type
parameters_json
version
status
```

---

# 164. Validation Result Table

Fields:

```text
id
cnc_job_id
severity
code
message
operation_id
created_at
```

---

# 165. Toolpath Table

Fields:

```text
id
operation_id
path_type
geometry_json
sequence
```

---

# 166. Toolpath Segment Table

Fields:

```text
id
toolpath_id
sequence
segment_type
start_x
start_y
start_z
end_x
end_y
end_z
i
j
k
feed_rate
```

---

# 167. Simulation Job Table

Fields:

```text
id
cnc_job_id
status
progress
result_storage_key
created_at
completed_at
```

---

# 168. File Table

Prefer reuse of centralized file storage if available.

Store:

```text
id
storage_key
file_name
mime_type
size
checksum
```

---

# 169. Audit Table

Track:

```text
CNC_JOB_CREATED
CNC_GENERATED
CNC_VALIDATED
CNC_APPROVED
CNC_RELEASED
CNC_REGENERATED
CNC_DOWNLOADED
MANUAL_OPERATION_ADDED
TOOL_CHANGED
MACHINE_CHANGED
POST_PROCESSOR_CHANGED
```

---

# 170. Frontend Architecture

Recommended:

```text
/src/cnc/

domain/
  CncJob.js
  CncProgram.js
  CncOperation.js
  CncTool.js
  CncMachine.js

geometry/
  CncGeometry.js
  Arc.js
  Line.js
  Polygon.js

toolpath/
  Toolpath.js
  ToolpathGenerator.js
  OffsetEngine.js
  PocketStrategy.js
  ContourStrategy.js

simulation/
  CncSimulator.js
  ToolRenderer.js
  StockRenderer.js
  ToolpathRenderer.js

validation/
  CncValidator.js
  MachineValidator.js
  ToolValidator.js

post/
  PostProcessorClient.js

ui/
  CncJobView.js
  ToolLibraryView.js
  MachineProfileView.js
  ProgramViewer.js
  SimulationView.js
```

---

# 171. PHP Architecture

Recommended:

```text
src/
  CNC/
    Domain/
    Operations/
    Toolpaths/
    Geometry/
    Simulation/
    Validation/
    Machines/
    Tools/
    Presets/
    PostProcessors/
    Programs/
    Storage/
    Repositories/
    DTO/
    Policies/
```

Core services:

```text
CncEngine
CncOperationService
ToolpathEngine
ToolSelectionService
CncValidationService
MachineValidationService
SimulationService
PostProcessorManager
ProgramGenerator
ProgramReleaseService
```

---

# 172. CNC Pipeline

Recommended implementation:

```text
Nesting Result
      ↓
CNC Input Builder
      ↓
Operation Resolver
      ↓
Geometry Validator
      ↓
Tool Resolver
      ↓
Toolpath Generator
      ↓
Toolpath Validator
      ↓
Operation Sequencer
      ↓
Machine Validator
      ↓
Post Processor
      ↓
Program Validator
      ↓
File Generator
      ↓
Checksum
      ↓
Approval
      ↓
Release
```

---

# 173. CNC Input Builder

Combines:

```text
panel geometry
nesting placement
machining operations
material
machine
tool
```

into canonical CNC input.

---

# 174. Tool Resolver

Determines:

```text
tool
diameter
feed
rpm
stepdown
```

from:

```text
explicit operation values
material preset
machine preset
tool defaults
```

using configured precedence.

---

# 175. Tool Parameter Precedence

Recommended:

```text
Operation Override
 ↓
Job Override
 ↓
Material + Tool Preset
 ↓
Machine + Tool Preset
 ↓
Tool Default
```

---

# 176. Toolpath Generation

Toolpath engine converts:

```text
operation geometry
```

into:

```text
rapid moves
cutting moves
arcs
plunges
retracts
```

---

# 177. Toolpath Separation

Do not mix:

```text
engineering geometry
```

with:

```text
machine movement
```

Keep separate models.

---

# 178. Engineering Geometry

Example:

```text
hole center = (250, 300)
diameter = 35
depth = 12
```

---

# 179. Toolpath Geometry

Example:

```text
move to safe Z
move to X250 Y300
plunge to Z-12
retract
```

---

# 180. Toolpath Optimization

Support:

```text
travel minimization
tool change minimization
operation grouping
```

without violating dependencies.

---

# 181. Rapid Optimization

Reduce:

```text
non-cutting travel
```

where safe.

---

# 182. Drilling Optimization

Group holes by:

```text
tool
face
depth
```

where supported.

---

# 183. Hole Ordering

Support strategies:

```text
LEFT_TO_RIGHT
TOP_TO_BOTTOM
NEAREST_NEIGHBOR
ROW_MAJOR
CUSTOM
```

---

# 184. Routing Optimization

Support:

```text
continuous contour
minimum retract
safe transition
```

---

# 185. Pocket Optimization

Support:

```text
raster direction
stepover
stepdown
boundary finishing
```

---

# 186. Cut Optimization

Support:

```text
inner cuts before outer cuts
```

when required.

---

# 187. Outer Contour Rule

For through-cut panels:

```text
internal features
→ external contour
```

is the default safe strategy.

---

# 188. Common-Line Integration

If nesting produces common-line geometry:

```text
CNC engine
```

must preserve the common-line instruction.

---

# 189. CNC Job Splitting

If a job exceeds machine limits:

Support:

```text
split into setups
```

only when engineering rules allow.

Do not silently split a panel.

---

# 190. Multi-Sheet Program

Support:

```text
one program per sheet
```

or:

```text
one program per batch
```

according to machine profile.

---

# 191. Program Granularity

Configurable:

```text
PER_SHEET
PER_PANEL
PER_JOB
PER_BATCH
```

---

# 192. Program Dependencies

If multiple programs are generated:

```text
program sequence
```

must be stored.

---

# 193. Machine Program Numbering

Support configurable numbering:

```text
sequence
project code
job code
factory code
```

---

# 194. Machine Communication

Architecture should permit future integration with:

```text
DNC
SFTP
machine gateway
MES connector
```

but direct machine communication is outside the core MVP.

---

# 195. CNC File Transfer

Future architecture:

```text
CNC Engine
 ↓
File Gateway
 ↓
Machine
```

---

# 196. Machine Acknowledgement

Future capability:

```text
SENT
RECEIVED
LOADED
EXECUTED
```

---

# 197. Execution Feedback

MES may report:

```text
program started
program completed
program failed
```

The CNC engine should consume this as external production events.

---

# 198. Simulation

Simulation should verify:

```text
toolpath bounds
depth
operation order
fixture clearance
```

It is not a substitute for physical machine validation.

---

# 199. Simulation Limitation

Never claim:

```text
collision-free
```

unless the simulation model contains the required machine/fixture geometry.

---

# 200. Safety

The system must clearly distinguish:

```text
software validation
```

from:

```text
physical machine safety
```

Final machine safety remains the responsibility of qualified factory personnel and machine controls.

---

# 201. CNC Release Checklist

Before release:

```text
[ ] Manufacturing revision valid
[ ] Nesting revision valid
[ ] Machine profile valid
[ ] Tool library valid
[ ] Material profile valid
[ ] Operations valid
[ ] Toolpaths valid
[ ] Machine limits valid
[ ] Fixture constraints valid
[ ] Post processor valid
[ ] Program generated
[ ] Program checksum generated
[ ] Program approved
```

---

# 202. CNC Error Codes

Minimum:

```text
CNC_INVALID_OPERATION
CNC_INVALID_GEOMETRY
CNC_TOOL_NOT_FOUND
CNC_TOOL_DEPTH_INVALID
CNC_MACHINE_LIMIT
CNC_MACHINE_UNSUPPORTED
CNC_POST_PROCESSOR_NOT_FOUND
CNC_POST_PROCESSOR_FAILED
CNC_PROGRAM_VALIDATION_FAILED
CNC_FIXTURE_COLLISION
CNC_INVALID_FACE
CNC_INVALID_SETUP
CNC_INVALID_COORDINATE
CNC_FILE_GENERATION_FAILED
CNC_PERMISSION_DENIED
CNC_REVISION_CONFLICT
```

---

# 203. Security

CNC files are production-critical assets.

Protect with:

```text
tenant isolation
factory authorization
RBAC
download authorization
audit logging
checksum validation
```

---

# 204. RBAC

Minimum permissions:

```text
cnc.view
cnc.create
cnc.generate
cnc.validate
cnc.simulate
cnc.edit_operation
cnc.manage_tools
cnc.manage_machines
cnc.manage_presets
cnc.approve
cnc.release
cnc.download
cnc.admin
```

---

# 205. Tenant Isolation

Every query must derive:

```text
tenant_id
```

from authenticated server context.

---

# 206. Factory Isolation

Users should only access machine profiles and CNC programs for authorized factories.

---

# 207. Sensitive Information

CNC programs should not contain:

```text
customer price
margin
supplier price
commercial terms
```

unless explicitly required.

---

# 208. Performance Targets

Recommended:

```text
100 panels / sheet → < 2 seconds
500 operations → < 3 seconds
5,000 operations → < 15 seconds
large batch → asynchronous
```

Performance depends on geometry complexity and post processor.

---

# 209. Async Generation

Large CNC jobs should support:

```text
QUEUED
RUNNING
VALIDATING
COMPLETED
FAILED
CANCELLED
```

---

# 210. Progress

Expose:

```text
Input preparation
Operation generation
Toolpath generation
Validation
Post processing
File creation
```

---

# 211. Idempotency

CNC generation requests must support:

```text
Idempotency-Key
```

to avoid duplicate programs.

---

# 212. Deterministic Generation

Given identical:

```text
manufacturing revision
nesting revision
machine profile
tool profile
post processor version
```

the generated canonical toolpaths should be deterministic.

---

# 213. Program Hash

Generate:

```text
input_hash
canonical_output_hash
file_checksum
```

---

# 214. Regeneration

If any of the following changes:

```text
machine profile
tool
material preset
manufacturing revision
nesting revision
post processor
```

the program must be regenerated.

---

# 215. Stale Program

Mark previous program:

```text
SUPERSEDED
```

when a new valid version is released.

---

# 216. Tool Version Change

A tool geometry change should invalidate affected CNC programs.

---

# 217. Machine Profile Change

A machine limit/post processor change should invalidate affected programs.

---

# 218. Post Processor Version

Program must store:

```text
post_processor_version
```

so output can be reproduced.

---

# 219. Audit Trail

Record:

```text
who generated
who validated
who approved
who released
who downloaded
what changed
when it changed
```

---

# 220. Testing — Geometry

Test:

```text
line
arc
circle
polygon
Bezier
offset
rotation
intersection
```

---

# 221. Testing — Operations

Test:

```text
drill
bore
hinge cup
groove
pocket
route
contour
cut
```

---

# 222. Testing — Toolpath

Verify:

```text
correct depth
correct tool
correct feed
correct spindle
correct path
correct sequence
```

---

# 223. Testing — Machine Limits

Verify:

```text
inside work envelope
outside limits rejected
fixture collision rejected
```

---

# 224. Testing — Post Processor

For every supported format:

```text
canonical input
→ expected machine output
```

must have golden-file tests.

---

# 225. Golden File Testing

Store approved reference files:

```text
fixtures/cnc/
  biesse/
  homag/
  kdt/
  generic/
```

Do not include proprietary machine files unless legally authorized.

---

# 226. Testing — Regression

Every change to:

```text
toolpath engine
post processor
machine profile
```

must run CNC regression tests.

---

# 227. Testing — Revision

Verify:

```text
CNC Program v1
→ released

Tool change
→ CNC Program v2

v1 remains immutable
```

---

# 228. Testing — Security

Verify:

```text
tenant isolation
factory isolation
download permissions
program release permissions
audit logs
```

---

# 229. Testing — Integration

End-to-end:

```text
Parametric Furniture
 ↓
BOM
 ↓
Manufacturing
 ↓
Nesting
 ↓
CNC/CAM
 ↓
Machine File
 ↓
MES
```

---

# 230. Acceptance Scenario

Use a sample wardrobe panel:

```text
Panel:
600 × 720 × 18 mm

Operations:
4 hinge holes
6 shelf-pin holes
1 groove
1 external contour
```

The engine must generate:

```text
Canonical operations
Tool assignments
Toolpaths
Machine validation
Machine program
Checksum
Simulation data
```

---

# 231. Acceptance — Drilling

Every drilling operation must contain:

```text
X
Y
diameter
depth
tool
face
```

---

# 232. Acceptance — Routing

Every routing operation must contain:

```text
geometry
tool
depth
feed
speed
direction
```

---

# 233. Acceptance — Machine File

Generated file must:

```text
match selected machine profile
use selected post processor
contain all required operations
pass validation
have checksum
reference correct revision
```

---

# 234. Acceptance — Revision

Changing:

```text
tool
machine
nesting
manufacturing revision
```

must create a new CNC program version.

---

# 235. Acceptance — Traceability

Given a CNC program, user must be able to identify:

```text
Project
Room
Furniture
Panel
BOM
Manufacturing Revision
Nesting Revision
Machine
Tool
Post Processor
```

---

# 236. Cursor Pre-Implementation Analysis

Before coding, Cursor MUST inspect the existing codebase for:

```text
2D CAD
3D/BIM
Parametric Furniture Engine
BOM
Manufacturing Engine
Nesting Engine
Material Catalog
Hardware Catalog
Tool Library
Machine Data
Existing CNC
Existing DXF
Existing G-code
MES
File Storage
Revision System
RBAC
```

Cursor must produce:

```text
CURRENT CNC/CAM LOGIC
CURRENT MACHINING OPERATIONS
CURRENT TOOL DATA
CURRENT MACHINE DATA
CURRENT DXF/G-CODE IMPLEMENTATION
CURRENT NESTING HANDOFF
CURRENT DATABASE
CURRENT API
DUPLICATE LOGIC
MISSING CAPABILITIES
MIGRATION RISKS
TARGET ARCHITECTURE
IMPLEMENTATION PLAN
```

Do not create a duplicate CNC engine if an existing implementation can be refactored.

---

# 237. Cursor Implementation Sequence

## Phase 1 — Canonical CNC Domain

```text
CncJob
CncProgram
CncOperation
Setup
Tool
Machine
```

## Phase 2 — Geometry

```text
Line
Arc
Circle
Polygon
Offset
Transform
```

## Phase 3 — Operations

```text
Drill
Bore
Hinge
Groove
Pocket
Route
Contour
Cut
```

## Phase 4 — Toolpath Engine

```text
Rapid
Linear
Arc
Plunge
Retract
Compensation
```

## Phase 5 — Tool Management

```text
Tool Library
Tool Resolver
Presets
Material Parameters
```

## Phase 6 — Machine Profiles

```text
Machine limits
Axes
Origins
Fixtures
Coordinate systems
```

## Phase 7 — Validation

```text
Geometry
Tool
Depth
Machine
Fixture
Sequence
```

## Phase 8 — Post Processors

```text
Generic G-code
DXF
Vendor-specific adapters
```

## Phase 9 — Simulation

```text
Stock
Tool
Toolpath
Sequence
```

## Phase 10 — Governance

```text
Revision
Approval
Release
Checksum
Audit
```

## Phase 11 — Integration

```text
Nesting
MES
Labels
Inventory
Manufacturing
```

---

# 238. Recommended Service Flow

```text
CncJobRequest
      ↓
CncInputBuilder
      ↓
OperationResolver
      ↓
GeometryValidator
      ↓
ToolResolver
      ↓
ToolpathEngine
      ↓
ToolpathValidator
      ↓
OperationSequencer
      ↓
MachineValidator
      ↓
PostProcessor
      ↓
ProgramValidator
      ↓
FileGenerator
      ↓
ChecksumService
      ↓
Approval
      ↓
Release
```

---

# 239. Definition of Done

```text
[ ] Canonical CNC model implemented
[ ] CNC job implemented
[ ] CNC program revisioning implemented
[ ] Coordinate system implemented
[ ] Face mapping implemented
[ ] Multi-setup architecture implemented
[ ] Tool library implemented
[ ] Tool selection implemented
[ ] Material/tool presets implemented
[ ] Drilling implemented
[ ] Boring implemented
[ ] Hinge cup implemented
[ ] Dowel drilling implemented
[ ] Shelf-pin drilling implemented
[ ] Screw drilling implemented
[ ] Routing implemented
[ ] Grooving implemented
[ ] Pocketing implemented
[ ] Dado implemented
[ ] Rebate implemented
[ ] Contour implemented
[ ] Cut implemented
[ ] Tool compensation implemented
[ ] Feed/rpm implemented
[ ] Stepdown implemented
[ ] Stepover implemented
[ ] Lead-in/out implemented
[ ] Safe-Z implemented
[ ] Fixture validation implemented
[ ] Machine limits implemented
[ ] Tool collision validation implemented
[ ] Toolpath generation implemented
[ ] Toolpath optimization implemented
[ ] Simulation implemented
[ ] DXF export implemented
[ ] G-code architecture implemented
[ ] Vendor post-processor architecture implemented
[ ] CNC validation implemented
[ ] CNC checksum implemented
[ ] File storage implemented
[ ] Program approval implemented
[ ] Program release implemented
[ ] Program download implemented
[ ] Revision management implemented
[ ] Audit logging implemented
[ ] RBAC implemented
[ ] Tenant isolation implemented
[ ] Factory isolation implemented
[ ] Async generation implemented
[ ] Idempotency implemented
[ ] Unit tests implemented
[ ] Geometry tests implemented
[ ] Toolpath tests implemented
[ ] Post-processor golden tests implemented
[ ] Machine validation tests implemented
[ ] Security tests implemented
[ ] Integration tests implemented
```

---

# 240. Final End-to-End CNC/CAM Flow

```text
                         DESIGN
                           ↓
                   PARAMETRIC FURNITURE
                           ↓
                          BOM
                           ↓
                    MANUFACTURING
                           ↓
                         PANELS
                           ↓
                        NESTING
                           ↓
                  NESTING PLACEMENT
                           ↓
                 CANONICAL CNC INPUT
                           ↓
                  MACHINING OPERATIONS
                           ↓
                    TOOL RESOLUTION
                           ↓
                    TOOLPATH ENGINE
                           ↓
                  TOOLPATH VALIDATION
                           ↓
                  MACHINE VALIDATION
                           ↓
                    POST PROCESSOR
                           ↓
                 MACHINE-SPECIFIC FILE
                           ↓
                     CNC PROGRAM
                           ↓
                       APPROVAL
                           ↓
                       RELEASE
                           ↓
                       FACTORY
                           ↓
                         MES
```

---

# 241. Final Product Principle

The CNC/CAM Engine must be able to answer:

```text
What machine will process this panel?
Which tool will be used?
What operation will be performed?
At what coordinate?
At what depth?
At what feed rate?
At what spindle speed?
In what sequence?
Which face of the panel?
Which setup?
Which nesting revision?
Which manufacturing revision?
Which post processor generated the file?
Which exact program version was released?
```

The fundamental architecture rule is:

> **Engineering defines what must be manufactured. Nesting defines where it is manufactured on the sheet. CNC/CAM defines how the machine executes it. The post processor defines how that instruction is expressed for a specific machine.**

Therefore:

```text
Engineering Intent
        ↓
Canonical Manufacturing Operation
        ↓
Canonical Toolpath
        ↓
Machine Profile
        ↓
Post Processor
        ↓
Machine Program
```

The CNC/CAM Engine must never allow a vendor-specific machine format to leak into the upstream:

```text
2D CAD
3D/BIM
Parametric Furniture
BOM
Manufacturing
Nesting
```

layers.

This separation is mandatory for long-term support of multiple CNC vendors, factories, controllers and machine configurations.
