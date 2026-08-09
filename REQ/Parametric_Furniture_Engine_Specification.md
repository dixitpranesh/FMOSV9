# Parametric Furniture Engine Specification
## Interior Design, Modular Furniture Engineering, BOM, Cutlist, CNC & MES Platform

**Document ID:** PFE-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript/ES6 Developers, CAD/Geometry Engineers, Furniture Engineers, Manufacturing Engineers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**3D Engine:** Three.js / WebGL  
**API:** REST `/api/v1`  
**Primary Unit:** millimetres (mm)  
**Primary Domain:** Parametric modular furniture  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for a **Parametric Furniture Engine (PFE)** for an end-to-end interior design and furniture manufacturing platform.

The engine is responsible for transforming:

```text
Furniture Intent
+
Dimensions
+
Design Parameters
+
Construction Rules
+
Material Rules
+
Hardware Rules
+
Manufacturing Rules
```

into:

```text
2D Furniture Representation
+
3D Furniture Geometry
+
Component/Part Structure
+
BOM
+
BOQ
+
Cutlist
+
Edge Banding
+
Hardware List
+
Drilling/Routing/Grooving Operations
+
Panel Labels
+
Nesting Input
+
CNC Input
+
Manufacturing Traceability
```

The engine MUST be **parametric, deterministic, rule-driven and manufacturing-aware**.

It MUST NOT be implemented as a collection of manually modeled meshes.

---

# 2. Product Objective

The Parametric Furniture Engine is the bridge between:

```text
Interior Design
       ↓
Furniture Design
       ↓
Furniture Engineering
       ↓
Manufacturing
       ↓
MES
```

The same furniture object must remain traceable through the entire lifecycle.

Example:

```text
Wardrobe W-001
      │
      ├── 3D representation
      ├── 2D plan/elevation
      ├── Carcass
      ├── Shutters
      ├── Shelves
      ├── Drawers
      ├── Hardware
      ├── Panels
      ├── BOM
      ├── Cutlist
      ├── Nesting
      ├── CNC operations
      └── Production/MES tracking
```

---

# 3. Core Principle

The **parametric furniture definition is the source of truth**.

The following are derived artifacts:

```text
3D Mesh
2D Drawing
BOM
BOQ
Cutlist
Nesting
CNC
Labels
MES Jobs
```

Do NOT make the generated 3D mesh the authoritative model.

Architecture:

```text
Furniture Template
        +
Instance Parameters
        +
Design Rules
        +
Construction Rules
        +
Material Rules
        +
Hardware Rules
        +
Manufacturing Rules
        ↓
PARAMETRIC FURNITURE MODEL
        ↓
┌───────┼────────┬────────┬──────────┐
↓       ↓        ↓        ↓          ↓
2D     3D       BOM      BOQ     Manufacturing
                                   ↓
                         Cutlist / Nesting / CNC
```

---

# 4. Supported Furniture Categories

The engine MUST support a generic framework and specialized templates for:

## Kitchen

```text
Base Cabinet
Wall Cabinet
Tall Unit
Sink Unit
Hob Unit
Oven Unit
Microwave Unit
Dishwasher Unit
Corner Unit
Blind Corner Unit
Drawer Unit
Bottle Unit
Pantry
Open Shelf
Countertop
Island
Breakfast Counter
```

## Wardrobe

```text
Single Door Wardrobe
Sliding Wardrobe
Walk-in Wardrobe
Loft Wardrobe
Corner Wardrobe
Drawer Wardrobe
Open Wardrobe
```

## Storage

```text
TV Unit
Bookshelf
Display Unit
Sideboard
Console
Shoe Rack
Utility Cabinet
Storage Cabinet
```

## Bedroom

```text
Bed
Bedside Table
Headboard
Study Table
Dresser
Vanity
```

## Living Room

```text
TV Unit
Display Cabinet
Media Console
Bar Unit
Partition
Wall Panel
```

## Office

```text
Workstation
Storage
Credenza
Overhead Cabinet
Reception Counter
```

## Bathroom

```text
Vanity
Mirror Cabinet
Storage Unit
```

The framework MUST support custom enterprise furniture types.

---

# 5. Parametric Furniture Definition

A furniture template MUST contain:

```text
Identity
Geometry
Parameters
Components
Rules
Materials
Hardware
Constraints
Connectors
Manufacturing Rules
BOM Rules
Pricing Rules
Labels
Validation Rules
Version
```

---

# 6. Furniture Template

Example:

```json
{
  "id": "wardrobe-standard",
  "version": 3,
  "name": "Standard Wardrobe",
  "category": "WARDROBE",
  "parameters": {},
  "components": [],
  "rules": [],
  "materials": [],
  "hardware": [],
  "manufacturing_rules": []
}
```

---

# 7. Furniture Instance

An instance references a template.

```json
{
  "id": "F-001",
  "template_id": "wardrobe-standard",
  "template_version": 3,
  "parameters": {
    "width": 2400,
    "depth": 600,
    "height": 2400
  },
  "position": {
    "x": 1200,
    "y": 0,
    "z": 450
  },
  "rotation": 0
}
```

---

# 8. Template vs Instance

Template:

```text
How the furniture is designed.
```

Instance:

```text
Where and how that furniture is used in a project.
```

Changing an instance MUST NOT modify the template.

---

# 9. Template Versioning

Every template MUST be versioned.

Example:

```text
Wardrobe Standard v1
Wardrobe Standard v2
Wardrobe Standard v3
```

Existing project instances MUST continue using their original template version unless explicitly migrated.

---

# 10. Template Lifecycle

Template states:

```text
DRAFT
IN_REVIEW
APPROVED
PUBLISHED
DEPRECATED
ARCHIVED
```

Only:

```text
PUBLISHED
```

templates may be used by normal project users.

---

# 11. Parametric Parameter Types

Support:

```text
INTEGER
DECIMAL
BOOLEAN
ENUM
STRING
MATERIAL
HARDWARE
DIMENSION
ANGLE
REFERENCE
FORMULA
```

---

# 12. Parameter Definition

Each parameter:

```text
id
name
label
type
unit
default_value
min_value
max_value
step
required
visible
editable
driving
formula
category
```

Example:

```json
{
  "name": "width",
  "type": "DIMENSION",
  "unit": "mm",
  "default_value": 1800,
  "min_value": 300,
  "max_value": 6000,
  "required": true,
  "driving": true
}
```

---

# 13. Parameter Categories

Organize parameters into:

```text
Overall Dimensions
Carcass
Shutters
Drawers
Shelves
Partitions
Back Panel
Toe Kick
Countertop
Materials
Hardware
Finish
Manufacturing
Installation
Pricing
```

---

# 14. Overall Parameters

Standard parameters:

```text
width
depth
height
rotation
base_elevation
wall_offset
```

---

# 15. Dimension Constraints

Every dimension parameter SHOULD support:

```text
minimum
maximum
default
step
validation
```

Example:

```text
wardrobe width:
min = 600
max = 6000
step = 1
```

---

# 16. Formula Parameters

Parameters may be derived.

Example:

```text
internal_width =
width
-
left_panel_thickness
-
right_panel_thickness
```

Formula parameters MUST NOT be manually edited unless explicitly configured.

---

# 17. Formula Engine

Support operators:

```text
+
-
*
/
%
()
```

Functions:

```text
min()
max()
round()
ceil()
floor()
abs()
```

The formula engine MUST be deterministic.

---

# 18. Parameter Dependency Graph

Example:

```text
width
 ↓
internal_width
 ↓
shelf_width
 ↓
shelf_panel_geometry
 ↓
BOM
```

When a driving parameter changes:

```text
all dependent parameters recalculate
```

---

# 19. Circular Dependencies

The engine MUST detect:

```text
A → B → C → A
```

and reject the configuration.

Do not silently produce invalid values.

---

# 20. Parameter Validation

Validate:

```text
type
range
unit
formula
dependency
manufacturing constraints
hardware constraints
```

---

# 21. Parameter State

Each parameter can be:

```text
VALID
INVALID
WARNING
LOCKED
DERIVED
```

---

# 22. Construction Method

Furniture templates MUST define a construction method.

Examples:

```text
CONFIRMAT
MINIFIX
DOWEL
CAM_LOCK
POCKET_SCREW
RTA
TRADITIONAL_JOINERY
CUSTOM
```

---

# 23. Carcass Construction

Support standard carcass components:

```text
Left Side
Right Side
Top
Bottom
Back
Partition
Shelf
Plinth
Toe Kick
```

---

# 24. Component Definition

Each component:

```text
id
name
type
parent_id
geometry_rule
material_rule
thickness
edge_band_rule
hardware_rule
manufacturing_rule
```

---

# 25. Component Hierarchy

Example:

```text
Wardrobe
 ├── Carcass
 │    ├── Left Side
 │    ├── Right Side
 │    ├── Top
 │    ├── Bottom
 │    ├── Back
 │    └── Partition
 ├── Internal
 │    ├── Shelf
 │    ├── Drawer
 │    └── Hanging Rail
 ├── Front
 │    ├── Shutter
 │    └── Drawer Front
 └── Hardware
```

---

# 26. Component Types

Support:

```text
PANEL
SHELF
PARTITION
SHUTTER
DRAWER
BACK_PANEL
COUNTERTOP
PLINTH
TOE_KICK
PROFILE
ROD
HARDWARE
ACCESSORY
```

---

# 27. Panel Definition

Panel properties:

```text
panel_id
component_id
length
width
thickness
material_id
grain_direction
rotation_allowed
edge_banding
finish
```

---

# 28. Panel Orientation

Support:

```text
length
width
thickness
```

and:

```text
grain direction
```

Example:

```text
grain = along length
```

---

# 29. Grain Rules

A material may specify:

```text
grain_required = true
grain_direction = fixed
```

Nesting MUST respect grain direction.

---

# 30. Panel Geometry

Panel may contain:

```text
rectangle
polygon
cutout
notch
arc
drill holes
grooves
routing
```

---

# 31. Panel Thickness

Thickness should normally come from:

```text
material
```

but MAY be overridden by component rules.

Example:

```text
Carcass = 18 mm
Back = 6 mm
Shelf = 18 mm
```

---

# 32. Material Rules

Furniture templates may specify:

```text
carcass_material
shutter_material
back_material
shelf_material
countertop_material
```

---

# 33. Material Resolution

Resolution priority:

```text
Instance Override
        ↓
Template Rule
        ↓
Furniture Category Default
        ↓
Tenant Default
        ↓
System Default
```

---

# 34. Material Compatibility

Rules can restrict:

```text
material thickness
material category
edge banding
grain
hardware compatibility
```

---

# 35. Edge Banding

Each panel edge may define:

```text
TOP
BOTTOM
LEFT
RIGHT
FRONT
BACK
```

Properties:

```text
material
thickness
width
color
```

---

# 36. Edge Band Rules

Example:

```text
Shelf:
front edge = 1 mm edge band

Side panel:
front edge = 2 mm edge band

Back:
no edge band
```

---

# 37. Edge Band Length

Calculate:

```text
edge_band_length
```

per edge.

Aggregate into:

```text
BOM
BOQ
cutting/edge-banding job
```

---

# 38. Edge Band Manufacturing Data

Store:

```text
panel_id
edge
material
thickness
length
machine_operation
```

---

# 39. Back Panel Rules

Support:

```text
surface-mounted
grooved
recessed
screw-fixed
nail-fixed
```

Parameters:

```text
thickness
recess_depth
offset
material
```

---

# 40. Back Panel Geometry

Back panel may be:

```text
full width
between sides
recessed
overlay
```

---

# 41. Toe Kick / Plinth

Parameters:

```text
height
depth
recess
material
```

Rules:

```text
cabinet_height
-
toe_kick_height
=
usable_carcass_height
```

---

# 42. Shelves

Shelf parameters:

```text
count
thickness
spacing
front_offset
back_offset
adjustable
fixed
```

---

# 43. Shelf Distribution

Support:

```text
equal spacing
manual spacing
fixed positions
formula-based spacing
```

---

# 44. Shelf Load Rules

Optional:

```text
maximum span
maximum load
recommended thickness
```

If violated:

```text
WARNING
```

---

# 45. Partitions

Support:

```text
vertical partitions
horizontal partitions
fixed partitions
adjustable partitions
```

---

# 46. Partition Rules

Partition placement can be:

```text
equal
manual
formula
template-defined
```

---

# 47. Drawer System

Drawer definition:

```text
drawer_box
drawer_front
drawer_runner
drawer_handle
```

Parameters:

```text
width
height
depth
side_clearance
bottom_thickness
runner_type
```

---

# 48. Drawer Hardware

Support:

```text
soft-close
normal
push-to-open
undermount
side-mount
```

---

# 49. Drawer Clearance

Calculate:

```text
drawer_box_width
=
opening_width
-
left_clearance
-
right_clearance
```

Hardware rules must validate compatible dimensions.

---

# 50. Shutters

Support:

```text
single shutter
double shutter
multi-shutter
sliding shutter
frame shutter
glass shutter
```

---

# 51. Shutter Distribution

For a furniture opening:

```text
opening_width
```

and:

```text
shutter_count
```

calculate:

```text
shutter_width
```

with configurable gaps.

---

# 52. Shutter Gaps

Parameters:

```text
left_gap
right_gap
top_gap
bottom_gap
between_gap
```

---

# 53. Hinges

Hinge rules can depend on:

```text
door height
door weight
door width
material
hinge capacity
```

---

# 54. Hinge Quantity

Example rule:

```text
height <= 900 → 2 hinges
900 < height <= 1800 → 3 hinges
height > 1800 → 4 or more
```

Actual values MUST be configurable.

---

# 55. Hinge Placement

Calculate:

```text
top_offset
bottom_offset
intermediate_spacing
```

---

# 56. Handle Placement

Support:

```text
vertical
horizontal
center
custom
```

Parameters:

```text
position
offset
length
orientation
```

---

# 57. Sliding Door System

Support:

```text
top track
bottom track
rollers
shutters
overlap
```

Parameters:

```text
door_count
overlap
track_type
```

---

# 58. Sliding Door Calculation

Example:

```text
total_opening
+
overlap requirements
=
shutter widths
```

Rules must validate track capacity.

---

# 59. Kitchen Base Cabinet

Parameters:

```text
width
depth
height
toe_kick
countertop_thickness
carcass_thickness
```

---

# 60. Kitchen Wall Cabinet

Parameters:

```text
width
depth
height
mounting_height
door_count
```

---

# 61. Kitchen Tall Unit

Support:

```text
full-height
oven tower
microwave tower
pantry
refrigerator housing
```

---

# 62. Kitchen Corner Cabinet

Support:

```text
L corner
blind corner
magic corner
carousel
```

Corner rules MUST calculate usable geometry.

---

# 63. Sink Cabinet

Support:

```text
sink cutout
plumbing clearance
waste pipe clearance
drawer restrictions
```

---

# 64. Hob Unit

Support:

```text
hob cutout
drawer
oven integration
ventilation clearance
```

---

# 65. Oven Unit

Support:

```text
oven opening
microwave opening
ventilation
front clearance
```

---

# 66. Dishwasher Unit

Support:

```text
dishwasher opening
front panel
service clearance
```

---

# 67. Refrigerator Housing

Support:

```text
cabinet sides
top cabinet
ventilation
door clearance
```

---

# 68. Countertop

Parameters:

```text
material
thickness
overhang
backsplash
cutouts
edge profile
```

---

# 69. Countertop Cutouts

Support:

```text
sink
hob
tap
socket
```

Each cutout must be represented as geometry.

---

# 70. Wardrobe Engine

Wardrobe parameters:

```text
width
depth
height
shutter_count
loft_height
drawer_count
partition_count
shelf_count
hanging_sections
```

---

# 71. Wardrobe Internal Layout

Support:

```text
left section
center section
right section
loft
drawer bank
shelf bank
hanging rail
shoe section
accessory section
```

---

# 72. Wardrobe Layout Modes

```text
Equal
Preset
Manual
Formula
AI-assisted
```

---

# 73. Walk-in Wardrobe

Support:

```text
multiple furniture runs
corner transitions
island
clearance zones
```

---

# 74. Furniture-to-Wall Constraint

Furniture can be constrained to:

```text
wall
corner
floor
ceiling
reference line
```

---

# 75. Wall Alignment

Support:

```text
back-to-wall
front-to-wall
left-to-wall
right-to-wall
center-to-wall
```

---

# 76. Furniture Clearance

Rules:

```text
wall clearance
door clearance
walkway clearance
appliance clearance
service clearance
drawer clearance
```

---

# 77. Furniture Collision

Detect:

```text
furniture vs furniture
furniture vs wall
furniture vs door
furniture vs window
furniture vs column
```

Severity:

```text
INFO
WARNING
ERROR
BLOCKER
```

---

# 78. Room Fit Validation

Furniture SHOULD validate:

```text
inside room
required clearance
door access
window access
service access
```

---

# 79. Constraint System

Support:

```text
fixed width
fixed height
fixed depth
equal width
equal spacing
alignment
symmetry
wall attachment
level attachment
center alignment
```

---

# 80. Parametric Constraint Example

```text
Wardrobe width = 2400

Shutter count = 4

Shutter width =
(
  2400
  -
  left_gap
  -
  right_gap
  -
  3 * inter_gap
) / 4
```

---

# 81. Rule Engine

Furniture rules MUST be data-driven.

Do not hard-code every furniture type into one giant PHP class.

Recommended:

```text
Template
+
Rule Definitions
+
Rule Engine
+
Geometry Generators
```

---

# 82. Rule Types

Support:

```text
VALIDATION
CALCULATION
GEOMETRY
MATERIAL
HARDWARE
BOM
PRICING
MANUFACTURING
VISIBILITY
PLACEMENT
```

---

# 83. Rule Priority

Rules should support:

```text
priority
```

Recommended:

```text
SYSTEM
TENANT
TEMPLATE
INSTANCE
```

Instance-level rules may override template defaults where allowed.

---

# 84. Rule Conflict

If rules conflict:

```text
RULE_CONFLICT
```

The engine MUST return:

```text
rule IDs
affected parameter
current value
allowed resolution
```

---

# 85. Geometry Generation Pipeline

Required:

```text
Input Parameters
       ↓
Normalize Units
       ↓
Validate Parameters
       ↓
Resolve Materials
       ↓
Resolve Hardware
       ↓
Resolve Rules
       ↓
Generate Components
       ↓
Generate Panel Geometry
       ↓
Generate 3D Geometry
       ↓
Generate 2D Geometry
       ↓
Generate BOM
       ↓
Generate Manufacturing Data
       ↓
Validate
       ↓
Publish Derived Model
```

---

# 86. Deterministic Generation

Given identical:

```text
template version
parameters
materials
rules
hardware
```

the engine MUST produce equivalent output.

---

# 87. Generation Version

Store:

```text
engine_version
template_version
rule_version
material_version
hardware_version
```

This is essential for reproducibility.

---

# 88. Geometry Version

Every generated model should have:

```text
geometry_version
```

If generation logic changes:

```text
geometry_version changes
```

---

# 89. Preview vs Commit

During interactive editing:

```text
PREVIEW
```

may be generated client-side.

On commit:

```text
AUTHORITATIVE SERVER GENERATION
```

must validate and persist the result.

---

# 90. Server Authority

Client-generated geometry MUST NOT be trusted for:

```text
BOM
pricing
cutlist
CNC
manufacturing
```

The server-side domain engine MUST validate/recalculate authoritative manufacturing data.

---

# 91. Client Preview

JavaScript can generate:

```text
preview mesh
preview dimensions
preview panels
```

to provide responsive UX.

---

# 92. Server Generation

PHP backend may generate:

```text
canonical component model
BOM
BOQ
cutlist
manufacturing operations
```

For computationally heavy operations, a separate worker/service may be introduced later.

---

# 93. Engine Separation

Recommended:

```text
Furniture Domain Engine
        ↓
Geometry Engine
        ↓
Manufacturing Engine
```

Do not mix:

```text
UI
geometry
pricing
manufacturing
```

in the same classes.

---

# 94. Component Geometry API

Every component generator SHOULD expose:

```javascript
generate2D(context)
generate3D(context)
generateManufacturing(context)
calculateQuantities(context)
validate(context)
```

---

# 95. Component Context

Context:

```text
furniture
parameters
parent
materials
hardware
rules
units
project
room
manufacturing_profile
```

---

# 96. Geometry Primitive Library

Support:

```text
box
panel
extrusion
cylinder
profile
cutout
boolean subtraction
boolean union
hole
groove
slot
```

---

# 97. Boolean Geometry

Use boolean operations where needed for:

```text
countertop cutouts
sink openings
hob openings
complex panels
```

Boolean output MUST be validated.

---

# 98. Manufacturing Geometry

Manufacturing geometry should distinguish:

```text
nominal geometry
finished geometry
machine geometry
```

---

# 99. Panel Manufacturing Object

A panel must contain:

```text
id
component_id
material
thickness
finished_length
finished_width
grain
edges
operations
```

---

# 100. Machining Operations

Support:

```text
DRILL
ROUTE
GROOVE
POCKET
CUTOUT
DADO
REBATE
HINGE_BORE
CAM_BORE
DOWEL_BORE
```

---

# 101. Operation Definition

Each operation:

```text
id
panel_id
type
x
y
z
diameter
depth
length
width
direction
tool
machine
sequence
```

---

# 102. Drill Holes

Support:

```text
through hole
blind hole
hinge cup
dowel
cam
shelf pin
connector
```

---

# 103. Drill Reference System

Drilling coordinates MUST be stored relative to a panel reference.

Example:

```text
origin = bottom-left
x = 120
y = 300
```

---

# 104. Face Reference

Operations may specify:

```text
TOP
BOTTOM
FRONT
BACK
LEFT
RIGHT
```

---

# 105. Edge Reference

Operations may specify:

```text
distance from edge
edge reference
```

---

# 106. Hardware Rules

Hardware definitions:

```text
id
name
brand
code
type
dimensions
clearance
compatible_materials
compatible_thicknesses
drilling_pattern
```

---

# 107. Hardware Catalog

Support:

```text
hinges
drawer runners
drawer boxes
handles
knobs
cam locks
dowels
confirmat
screws
connectors
shelf pins
lift-up mechanisms
sliding systems
```

---

# 108. Hardware Compatibility

Example:

```text
18 mm panel
+
specific hinge
=
compatible
```

If incompatible:

```text
ERROR
```

---

# 109. Hardware Auto-Placement

Hardware can be generated from rules.

Example:

```text
door height
 ↓
hinge count
 ↓
hinge positions
```

---

# 110. Hardware BOM

Every hardware item contributes:

```text
SKU
description
quantity
unit_price
supplier
```

---

# 111. BOM Structure

BOM hierarchy:

```text
Furniture
 ├── Panels
 ├── Edge Band
 ├── Hardware
 ├── Accessories
 └── Consumables
```

---

# 112. BOM Levels

Support:

```text
single-level BOM
multi-level BOM
```

Recommended:

```text
Furniture
 → Assembly
 → Component
 → Hardware
```

---

# 113. BOM Quantity

Quantities are derived from:

```text
component generation
```

not manually typed.

---

# 114. BOQ

BOQ may aggregate:

```text
material
quantity
unit
rate
amount
```

---

# 115. Dual Pricing Engine

The furniture engine MUST support both:

## Raw Material Pricing

Based on:

```text
board area
edge-band length
hardware quantity
other materials
```

## Panel-Based Pricing

Based on:

```text
panel count
panel dimensions
fixed panel rates
```

---

# 116. Pricing Strategy

Furniture instance can specify:

```text
RAW_MATERIAL
PANEL_BASED
HYBRID
```

---

# 117. Raw Material Cost

Example:

```text
board_area × board_rate
+
edge_band_length × edge_rate
+
hardware_quantity × hardware_rate
```

---

# 118. Panel-Based Cost

Example:

```text
panel_count × panel_rate
```

Optional:

```text
panel_size_band
```

---

# 119. Pricing Rules

Pricing rules can be:

```text
tenant-specific
factory-specific
customer-specific
project-specific
```

---

# 120. Cost vs Selling Price

Store separately:

```text
cost
selling_price
margin
```

Client users MUST NOT automatically receive internal cost/margin.

---

# 121. Manufacturing Profile

A factory should have a manufacturing profile:

```text
factory_id
board_sizes
machine_capabilities
edge_band_machine
drilling_machine
CNC_machine
cutting_machine
nesting_rules
```

---

# 122. Manufacturing Rules

Furniture templates may depend on:

```text
factory profile
```

Example:

```text
maximum panel length
available board thicknesses
CNC capability
edge-banding capability
```

---

# 123. Factory-Specific Generation

Same furniture design can produce different manufacturing outputs depending on:

```text
factory profile
```

Example:

```text
Factory A → 2800 × 2070 board
Factory B → 2440 × 1220 board
```

---

# 124. Manufacturing Profile Version

Profiles MUST be versioned.

Existing production jobs reference the exact profile version used.

---

# 125. Cutlist Generation

The engine must generate:

```text
panel_id
part_name
length
width
thickness
material
grain
edge_banding
quantity
```

---

# 126. Cutlist Grouping

Group compatible panels by:

```text
material
thickness
grain
finish
edge band
```

---

# 127. Cutlist Status

```text
NOT_GENERATED
CURRENT
STALE
INVALID
RELEASED
```

---

# 128. Nesting Input

Nesting receives:

```text
panels
board_sizes
grain_rules
kerf
trim
edge restrictions
rotation rules
```

---

# 129. Nesting Output

Store:

```text
board_id
panel placements
x
y
rotation
waste
utilization
cut sequence
```

---

# 130. Nesting Is a Separate Engine

Do not implement complex nesting logic inside the furniture template.

Architecture:

```text
Furniture Engine
 ↓
Cutlist
 ↓
Nesting Engine
```

---

# 131. CNC Generation

Furniture engine provides operations.

CNC engine converts:

```text
operations
+
machine profile
```

into:

```text
machine-specific output
```

---

# 132. CNC Machine Profiles

Support future profiles:

```text
Biesse
Homag
KDT
Generic CNC
```

Machine output adapters MUST be separate.

---

# 133. Manufacturing Output Traceability

Every output must reference:

```text
project
furniture
assembly
component
panel
revision
engine_version
template_version
factory_profile_version
```

---

# 134. Panel Label Data

Label must support:

```text
panel ID
project
room
furniture
component
material
dimensions
quantity
QR
barcode
sequence
```

---

# 135. MES Integration

Panel lifecycle:

```text
DESIGN
ENGINEERING
CUTTING
EDGE_BANDING
DRILLING
ROUTING
ASSEMBLY
QC
PACKING
DISPATCH
INSTALLATION
COMPLETED
```

---

# 136. MES Traceability

Scanning panel QR should locate:

```text
panel
component
furniture
room
project
production job
current status
```

---

# 137. Manufacturing Release

A furniture item may be released only if:

```text
parameters valid
geometry valid
BOM current
cutlist current
nesting current if required
CNC current if required
no manufacturing blockers
```

---

# 138. Release State

```text
DRAFT
ENGINEERING
READY_FOR_REVIEW
APPROVED
RELEASED
IN_PRODUCTION
COMPLETED
CANCELLED
```

---

# 139. Engineering Validation

Before release:

```text
all panels valid
all materials resolved
all hardware resolved
all dimensions valid
all machine operations valid
```

---

# 140. Manufacturing Blockers

Examples:

```text
invalid panel dimensions
unsupported material
missing thickness
missing edge band
invalid drilling
missing hardware
unsupported machine operation
grain conflict
```

---

# 141. Furniture Assembly Model

Furniture can have assemblies:

```text
Carcass
Drawer Assembly
Door Assembly
Shelf Assembly
Countertop Assembly
Hardware Assembly
```

---

# 142. Assembly Sequence

Optional metadata:

```text
sequence
dependency
tool
operator
```

---

# 143. Installation Data

Furniture may contain:

```text
installation position
wall anchors
floor anchors
clearances
assembly instructions
```

---

# 144. Installation Rules

Examples:

```text
wall-mounted cabinet requires wall anchor
tall unit requires anti-tip
overhead unit requires mounting support
```

These should be configurable.

---

# 145. Parametric Furniture 2D Output

Generate:

```text
plan
front elevation
side elevation
internal elevation
```

---

# 146. Parametric Furniture 3D Output

Generate:

```text
assembly
components
materials
hardware
```

---

# 147. Exploded View

Support optional exploded views:

```text
carcass panels
shutters
drawers
hardware
```

Useful for:

```text
engineering
assembly instructions
client visualization
```

---

# 148. Assembly Instructions

Optional generated output:

```text
Step 1
Step 2
Step 3
...
```

based on assembly dependencies.

---

# 149. Furniture Documentation

Auto-generate:

```text
dimensioned drawings
component schedule
BOM
hardware schedule
cutlist
assembly drawings
```

---

# 150. Furniture Drawing Views

Support:

```text
front
back
left
right
top
bottom
section
exploded
```

---

# 151. Detail Views

Support detail callouts for:

```text
hinge
drawer
edge band
joinery
countertop
panel connection
```

---

# 152. Revision Cloud / Change Marker

Optional:

```text
revision cloud
change note
revision tag
```

---

# 153. Parametric Furniture AI Integration

The engine SHOULD provide an integration point for AI-assisted design.

Possible inputs:

```text
"Create a 2400 x 600 x 2400 wardrobe"
"Add four shutters and three drawers"
"Use 18 mm MDF"
```

AI must produce:

```text
structured parameters
```

not arbitrary geometry.

---

# 154. AI Safety Boundary

AI-generated furniture definitions MUST pass:

```text
schema validation
parameter validation
construction validation
manufacturing validation
```

before becoming authoritative.

---

# 155. AI Floorplan Integration

If AI extracts:

```text
wall
room
dimensions
```

the furniture engine can use:

```text
room geometry
clearance
wall constraints
```

to propose furniture layouts.

---

# 156. AI Suggested Layout

AI output:

```text
SUGGESTED
```

not:

```text
APPROVED
```

Human confirmation required.

---

# 157. Furniture Template Designer

Enterprise users need a visual component designer.

Workspace:

```text
Parameter Panel
Component Tree
2D Preview
3D Preview
Rule Editor
Material Panel
Hardware Panel
BOM Preview
Manufacturing Preview
Validation Panel
```

---

# 158. Component Tree Editor

Allow:

```text
add component
remove component
duplicate component
reorder
nest
rename
```

---

# 159. Rule Editor

Allow rules:

```text
IF
THEN
ELSE
```

Example:

```text
IF width > 1800
THEN add center partition
```

---

# 160. Formula Editor

Support:

```text
parameter references
math
conditions
functions
```

---

# 161. Template Preview

Changing parameter:

```text
parameter
 ↓
2D preview
 ↓
3D preview
 ↓
BOM preview
 ↓
cutlist preview
```

---

# 162. Template Validation

Before publishing:

```text
parameter validation
geometry validation
rule validation
BOM validation
manufacturing validation
```

---

# 163. Template Test Cases

Templates SHOULD support embedded test cases.

Example:

```text
Input:
width = 1800

Expected:
shutter_count = 3
```

---

# 164. Regression Tests for Templates

When template rules change:

```text
existing test cases must run
```

Publishing should fail if mandatory tests fail.

---

# 165. Rule Debugger

Enterprise template designers need a rule trace:

```text
Parameter width = 2400
 ↓
Rule R12 triggered
 ↓
Partition count = 2
 ↓
Rule R18 triggered
 ↓
Shelf count = 5
```

---

# 166. Calculation Trace

Provide:

```text
input
formula
result
dependencies
```

for debugging.

---

# 167. Geometry Debugging

Developer mode should expose:

```text
component IDs
panel IDs
bounding boxes
anchor points
constraints
origin
coordinate axes
```

---

# 168. Manufacturing Debugging

Developer mode should expose:

```text
panel geometry
edge-band assignments
drill points
routing paths
grain direction
machine operations
```

---

# 169. Object Identity

Every generated object must have stable IDs:

```text
furniture_id
assembly_id
component_id
panel_id
operation_id
hardware_id
```

---

# 170. Derived Object Identity

Generated objects should use deterministic identity where practical.

Example:

```text
F001:CABINET:LEFT_PANEL
```

Actual implementation may use UUIDs plus stable logical keys.

---

# 171. Regeneration Identity

Regenerating a furniture item should not unnecessarily create a completely new identity for unchanged logical components.

This is important for:

```text
MES traceability
revision comparison
audit
```

---

# 172. Change Detection

Compare:

```text
previous component model
vs
new component model
```

Detect:

```text
added
removed
modified
unchanged
```

---

# 173. Revision Delta

Store delta where practical:

```text
parameter changed
component changed
material changed
hardware changed
geometry changed
```

---

# 174. Manufacturing Revision Rule

If a released furniture item changes:

```text
new engineering revision required
```

Do not overwrite the released manufacturing revision.

---

# 175. Engineering Approval

Approval can require roles such as:

```text
Designer
Furniture Engineer
Manufacturing Engineer
Approver
```

based on tenant RBAC.

---

# 176. Separation of Duties

A user who creates a manufacturing release SHOULD NOT automatically be allowed to approve it if tenant policy requires separation of duties.

---

# 177. Multi-Tenant Templates

Support:

```text
System Templates
Tenant Templates
Factory Templates
Project Templates
```

Precedence:

```text
Project
 ↓
Factory
 ↓
Tenant
 ↓
System
```

---

# 178. Template Permissions

Permissions should distinguish:

```text
template.view
template.create
template.edit
template.publish
template.archive
template.clone
```

---

# 179. Template Cloning

Users can clone approved templates into tenant-owned templates.

Cloning creates:

```text
new template ID
new version
source template reference
```

---

# 180. Data Persistence

Minimum tables:

```text
furniture_templates
furniture_template_versions
furniture_template_parameters
furniture_template_components
furniture_template_rules
furniture_template_material_rules
furniture_template_hardware_rules
furniture_instances
furniture_instance_parameters
furniture_components
furniture_panels
furniture_panel_edges
furniture_operations
furniture_hardware
furniture_bom
furniture_boq
furniture_revisions
furniture_validation_results
furniture_generation_runs
furniture_generation_artifacts
furniture_manufacturing_releases
```

---

# 181. Furniture Template Table

Fields:

```text
id
tenant_id
category
name
code
description
status
current_version
source_template_id
created_by
updated_by
created_at
updated_at
```

---

# 182. Template Version Table

Fields:

```text
id
template_id
version
engine_version
rule_version
status
definition_json
created_by
created_at
published_by
published_at
```

---

# 183. Instance Table

Fields:

```text
id
tenant_id
project_id
room_id
template_id
template_version_id
name
position_x
position_y
position_z
rotation_x
rotation_y
rotation_z
status
revision
created_by
updated_by
created_at
updated_at
```

---

# 184. Instance Parameters

Fields:

```text
id
furniture_instance_id
parameter_id
value
value_type
source
is_override
created_at
updated_at
```

---

# 185. Component Table

Fields:

```text
id
furniture_instance_id
template_component_id
logical_key
component_type
parent_id
sequence
status
geometry_version
metadata_json
```

---

# 186. Panel Table

Fields:

```text
id
component_id
logical_key
length
width
thickness
material_id
grain_direction
quantity
status
revision
```

---

# 187. Edge Table

Fields:

```text
id
panel_id
edge
material_id
thickness
width
length
status
```

---

# 188. Operation Table

Fields:

```text
id
panel_id
operation_type
face
x
y
z
width
length
diameter
depth
angle
tool_id
machine_profile_id
sequence
status
```

---

# 189. Generation Run

Every authoritative generation SHOULD record:

```text
id
furniture_instance_id
template_version
engine_version
rule_version
input_hash
output_hash
status
started_at
completed_at
error_data
```

---

# 190. Idempotency

If the same:

```text
template
+
parameters
+
rules
+
materials
+
hardware
+
engine version
```

are submitted again, generation SHOULD be idempotent.

---

# 191. Generation Hash

Calculate:

```text
input_hash
```

from canonicalized inputs.

Use it to detect unchanged models.

---

# 192. Caching

Generated results may be cached using:

```text
input_hash
```

Cache:

```text
2D geometry
3D geometry
BOM
cutlist
```

---

# 193. Cache Invalidation

Invalidate when:

```text
parameter changes
template changes
rule changes
material changes
hardware changes
engine version changes
```

---

# 194. API Requirements

## Templates

```http
GET    /api/v1/furniture/templates
POST   /api/v1/furniture/templates
GET    /api/v1/furniture/templates/{id}
PATCH  /api/v1/furniture/templates/{id}
DELETE /api/v1/furniture/templates/{id}
POST   /api/v1/furniture/templates/{id}/clone
POST   /api/v1/furniture/templates/{id}/publish
POST   /api/v1/furniture/templates/{id}/archive
```

---

# 195. Template Versions

```http
GET  /api/v1/furniture/templates/{id}/versions
POST /api/v1/furniture/templates/{id}/versions
GET  /api/v1/furniture/template-versions/{versionId}
POST /api/v1/furniture/template-versions/{versionId}/validate
POST /api/v1/furniture/template-versions/{versionId}/publish
```

---

# 196. Furniture Instances

```http
GET    /api/v1/furniture/instances
POST   /api/v1/furniture/instances
GET    /api/v1/furniture/instances/{id}
PATCH  /api/v1/furniture/instances/{id}
DELETE /api/v1/furniture/instances/{id}
```

---

# 197. Parameter APIs

```http
GET   /api/v1/furniture/instances/{id}/parameters
PATCH /api/v1/furniture/instances/{id}/parameters
POST  /api/v1/furniture/instances/{id}/validate
```

---

# 198. Generation API

```http
POST /api/v1/furniture/instances/{id}/generate
GET  /api/v1/furniture/instances/{id}/generation-status
GET  /api/v1/furniture/instances/{id}/generation-runs
```

---

# 199. Derived Output APIs

```http
GET /api/v1/furniture/instances/{id}/components
GET /api/v1/furniture/instances/{id}/panels
GET /api/v1/furniture/instances/{id}/bom
GET /api/v1/furniture/instances/{id}/boq
GET /api/v1/furniture/instances/{id}/cutlist
GET /api/v1/furniture/instances/{id}/operations
```

---

# 200. Release API

```http
POST /api/v1/furniture/instances/{id}/release
POST /api/v1/furniture/instances/{id}/approve
POST /api/v1/furniture/instances/{id}/supersede
```

---

# 201. Validation API

```http
POST /api/v1/furniture/instances/{id}/validate
GET  /api/v1/furniture/instances/{id}/validation-results
```

---

# 202. Export APIs

```http
GET /api/v1/furniture/instances/{id}/export/json
GET /api/v1/furniture/instances/{id}/export/cutlist
GET /api/v1/furniture/instances/{id}/export/bom
GET /api/v1/furniture/instances/{id}/export/dxf
GET /api/v1/furniture/instances/{id}/export/glb
```

---

# 203. API Command Model

Complex edits SHOULD use:

```http
POST /api/v1/furniture/instances/{id}/commands
```

Example:

```json
{
  "commands": [
    {
      "type": "SET_PARAMETER",
      "parameter": "width",
      "value": 2400
    },
    {
      "type": "SET_PARAMETER",
      "parameter": "shutter_count",
      "value": 4
    }
  ]
}
```

---

# 204. Optimistic Concurrency

Request:

```json
{
  "base_version": 12,
  "commands": []
}
```

If current version differs:

```http
409 Conflict
```

No silent overwrite.

---

# 205. Client-Side Architecture

Recommended:

```text
/src/furniture/

domain/
  FurnitureInstance.js
  FurnitureTemplate.js
  FurnitureComponent.js
  FurniturePanel.js
  FurnitureParameter.js

parameters/
  ParameterEngine.js
  FormulaEngine.js
  DependencyGraph.js

rules/
  RuleEngine.js
  RuleEvaluator.js
  RuleValidator.js

geometry/
  FurnitureGeometryEngine.js
  PanelGenerator.js
  CarcassGenerator.js
  ShutterGenerator.js
  DrawerGenerator.js
  CountertopGenerator.js

manufacturing/
  ManufacturingGeometry.js
  EdgeBandEngine.js
  DrillingEngine.js
  HardwareEngine.js

bom/
  BomEngine.js
  BoqEngine.js

pricing/
  PricingEngine.js

validation/
  FurnitureValidator.js
  ManufacturingValidator.js

generation/
  GenerationManager.js
  GenerationCache.js

templates/
  TemplateRegistry.js
```

---

# 206. PHP Backend Architecture

Recommended:

```text
src/
  Furniture/
    Domain/
    Services/
    Repositories/
    Rules/
    Geometry/
    Manufacturing/
    Pricing/
    Validation/
    Generation/
    DTO/
    Commands/
    Events/
```

Services:

```text
FurnitureTemplateService
FurnitureInstanceService
FurnitureGenerationService
FurnitureParameterService
FurnitureRuleService
FurnitureGeometryService
FurnitureBomService
FurniturePricingService
FurnitureValidationService
FurnitureManufacturingService
FurnitureReleaseService
```

---

# 207. Rule Engine Boundary

Do not implement:

```text
if ($type === 'wardrobe') { ... huge logic ... }
```

throughout the application.

Prefer:

```text
Template
→ Rule Set
→ Component Definitions
→ Generator
```

---

# 208. Generic Component Engine

The engine should support:

```text
PanelComponent
AssemblyComponent
HardwareComponent
AccessoryComponent
ProfileComponent
```

---

# 209. Component Generator Interface

Conceptually:

```javascript
generate(context) {
  return {
    geometry2D,
    geometry3D,
    manufacturing,
    quantities,
    validation
  };
}
```

---

# 210. Server-Side Canonical Output

Canonical generated model:

```json
{
  "furniture": {},
  "components": [],
  "panels": [],
  "edges": [],
  "hardware": [],
  "operations": [],
  "bom": [],
  "quantities": [],
  "validation": []
}
```

---

# 211. 2D Integration

Furniture engine must expose 2D representation:

```text
plan
front
side
internal
```

2D objects must reference:

```text
furniture_id
component_id
panel_id
```

where relevant.

---

# 212. 3D Integration

Furniture engine must generate:

```text
Three.js-compatible scene representation
```

but BIM/furniture IDs remain authoritative.

---

# 213. 2D/3D Synchronization

Example:

```text
Change wardrobe width
 ↓
Furniture instance parameter changes
 ↓
Component model regenerates
 ↓
2D updates
 ↓
3D updates
 ↓
BOM updates
 ↓
Cutlist becomes stale
 ↓
Nesting becomes stale
 ↓
CNC becomes stale
```

---

# 214. Change Impact Analysis

Before regeneration, engine SHOULD identify:

```text
affected components
affected panels
affected BOM items
affected BOQ items
affected manufacturing jobs
```

---

# 215. Dependency Status

Every derived artifact:

```text
CURRENT
STALE
INVALID
NOT_GENERATED
```

---

# 216. Manufacturing Dependency

Example:

```text
Furniture parameter change
       ↓
Geometry changed
       ↓
Panel dimensions changed
       ↓
Cutlist STALE
       ↓
Nesting STALE
       ↓
CNC STALE
```

---

# 217. Automatic Regeneration Policy

Low-cost:

```text
2D preview
3D preview
```

may regenerate immediately.

High-cost:

```text
BOM
cutlist
nesting
CNC
```

may regenerate:

```text
debounced
asynchronously
on commit
on release
```

---

# 218. Audit Trail

Record:

```text
template created
template edited
template published
instance created
parameter changed
material changed
hardware changed
generation executed
generation failed
BOM generated
cutlist generated
release created
approval completed
```

---

# 219. Revision Model

Furniture revision stores:

```text
revision_number
parameter_snapshot
template_version
engine_version
rule_version
generated_output_hash
created_by
created_at
change_summary
```

---

# 220. Release Immutability

Once manufacturing release is created:

```text
released revision = immutable
```

Changes require:

```text
new revision
new release
```

---

# 221. Approval Workflow

Configurable:

```text
Designer
→ Engineer
→ Reviewer
→ Manufacturing Approver
→ Released
```

Some tenants may use:

```text
Designer
→ Approver
→ Released
```

---

# 222. RBAC Integration

Required permissions may include:

```text
furniture.template.view
furniture.template.create
furniture.template.edit
furniture.template.publish
furniture.instance.create
furniture.instance.edit
furniture.instance.delete
furniture.generate
furniture.bom.view
furniture.cutlist.view
furniture.manufacturing.release
furniture.approve
```

---

# 223. Internal Cost Security

Fields such as:

```text
raw_material_cost
factory_cost
supplier_cost
internal_margin
```

must be protected by RBAC.

---

# 224. Tenant Isolation

All furniture data MUST be scoped by:

```text
tenant_id
```

and where applicable:

```text
project_id
```

---

# 225. File/Asset Security

Furniture assets and generated files must be stored securely.

Support:

```text
GLB
GLTF
textures
DXF
PDF
cutlist
CNC
labels
```

Only authorized users may download them.

---

# 226. Performance Requirements

Target standard furniture:

```text
100–500 components
```

Generation target:

```text
< 500 ms client preview
```

for normal parametric furniture.

Server generation:

```text
< 2 seconds
```

for typical furniture before heavy downstream optimization.

---

# 227. Large Furniture

For complex furniture:

```text
500–2,000 components
```

generation may become asynchronous.

UI must display:

```text
GENERATING
```

and progress.

---

# 228. Progressive Generation

Pipeline:

```text
Parameters validated
 ↓
Preview generated
 ↓
3D generated
 ↓
Components generated
 ↓
BOM generated
 ↓
Manufacturing generated
```

---

# 229. Error Recovery

If manufacturing generation fails:

```text
3D preview should remain available
```

Error should identify:

```text
component
rule
parameter
operation
```

---

# 230. Example Error

Bad:

```text
Generation failed.
```

Good:

```text
Left shutter cannot be generated:
calculated width = 287 mm, minimum supported shutter width = 300 mm.
Affected component: SHUTTER-02.
Rule: SHUTTER_MIN_WIDTH.
```

---

# 231. Validation Dashboard

Display:

```text
Parameter Errors
Geometry Errors
Construction Errors
Hardware Errors
Material Errors
Manufacturing Errors
Pricing Warnings
```

---

# 232. Validation Navigation

Click an error:

```text
select component
highlight geometry
open parameter
show rule
```

---

# 233. Unit Tests

Required tests:

```text
parameter validation
formula calculation
dependency resolution
component generation
panel dimensions
edge band calculation
hardware placement
BOM generation
pricing
cutlist
manufacturing operations
```

---

# 234. Furniture Test Example

Input:

```text
Wardrobe
W = 2400
D = 600
H = 2400
Carcass = 18
Back = 6
Shutters = 4
```

Expected:

```text
valid carcass
valid shutters
valid panels
valid BOM
valid cutlist
```

---

# 235. Regression Test

Every discovered furniture-engine bug MUST result in:

```text
automated regression test
```

---

# 236. Template Test Suite

Every published template SHOULD have:

```text
minimum dimension test
maximum dimension test
normal dimension test
invalid dimension test
material compatibility test
manufacturing test
```

---

# 237. Manufacturing Test

Verify:

```text
panel dimensions
edge banding
grain
drill coordinates
hardware
machine operations
```

---

# 238. Pricing Test

Verify both:

```text
raw-material pricing
panel-based pricing
```

against expected totals.

---

# 239. Round-Trip Test

```text
Create furniture
 ↓
Generate
 ↓
Save
 ↓
Reload
 ↓
Generate again
```

Expected:

```text
same canonical output hash
```

when engine/template versions are unchanged.

---

# 240. Version Compatibility Test

Old furniture instance:

```text
template v2
```

must continue generating using:

```text
template v2
```

even after:

```text
template v3
```

is published.

---

# 241. Manufacturing Revision Test

```text
Release v1
 ↓
Change width
 ↓
v2 created
 ↓
v1 remains unchanged
```

---

# 242. Security Tests

Verify:

```text
Tenant A cannot access Tenant B furniture
Client cannot access internal cost
Designer cannot release manufacturing if policy denies
Operator cannot modify design parameters
Archived template cannot be edited
Released furniture cannot be overwritten
```

---

# 243. Browser Tests

Test:

```text
template selection
parameter editing
preview
3D generation
2D generation
BOM
cutlist
validation
save
undo
redo
```

---

# 244. UI Requirements — Furniture Designer

Workspace:

```text
+------------------------------------------------------+
| Toolbar                                               |
+------------+-------------------------+---------------+
| Parameters |      2D / 3D View       | Components    |
|            |                         |               |
| Dimensions |                         | Carcass       |
| Materials  |                         | Shelves       |
| Hardware   |                         | Shutters      |
| Rules      |                         | Drawers       |
+------------+-------------------------+---------------+
| Validation | BOM | Cutlist | Cost | Manufacturing   |
+------------------------------------------------------+
```

---

# 245. Parameter Panel

Show:

```text
parameter
current value
unit
min
max
source
formula
validation
```

---

# 246. Component Tree

Show:

```text
Furniture
 ├── Carcass
 ├── Shelves
 ├── Drawers
 ├── Shutters
 └── Hardware
```

Selecting component highlights it in:

```text
2D
3D
BOM
```

---

# 247. BOM Panel

Show:

```text
Item
Category
Material
Specification
Quantity
Unit
Rate
Amount
```

---

# 248. Cutlist Panel

Show:

```text
Panel ID
Part
Length
Width
Thickness
Material
Grain
Edges
Quantity
```

---

# 249. Manufacturing Panel

Show:

```text
Panel
Drilling
Routing
Grooving
Edge Banding
Machine
Status
```

---

# 250. Validation Panel

Group:

```text
BLOCKER
ERROR
WARNING
INFO
```

---

# 251. Pricing Panel

Show authorized users:

```text
Material Cost
Hardware Cost
Processing Cost
Labor
Overhead
Margin
Selling Price
```

Client view should only show authorized commercial information.

---

# 252. Template Designer UI

Enterprise template designer:

```text
Template
Parameters
Components
Rules
Materials
Hardware
BOM
Manufacturing
Tests
Publish
```

---

# 253. Drag-and-Drop Component Designer

Support adding:

```text
Panel
Shelf
Partition
Drawer
Shutter
Back
Hardware
```

---

# 254. Visual Parameter Handles

Template designer SHOULD support:

```text
drag width
drag height
drag depth
```

and automatically update:

```text
parameter
geometry
formulas
```

---

# 255. Rule Builder UI

Example:

```text
IF
[Width] [>] [1800]
THEN
[Add Component] [Center Partition]
```

---

# 256. Manufacturing Rule Builder

Example:

```text
IF
[Panel Width] > [Machine Max Width]
THEN
[Validation] = BLOCKER
```

---

# 257. Hardware Rule Builder

Example:

```text
IF
[Shutter Height] > 1800
THEN
[Hinge Count] = 4
```

---

# 258. BOM Rule Builder

Example:

```text
IF
[Back Panel Material] exists
THEN
Add BOM Item
```

---

# 259. Pricing Rule Builder

Example:

```text
Board Area × Material Rate
+
Edge Length × Edge Rate
+
Hardware Qty × Hardware Rate
```

---

# 260. Template Publishing Gate

Template cannot be published if:

```text
invalid rules
missing required parameters
broken formulas
invalid geometry
invalid manufacturing operations
failed mandatory tests
```

---

# 261. Template Preview

Before publishing show:

```text
2D
3D
BOM
Cutlist
Hardware
Validation
Pricing
```

---

# 262. Furniture Import / Export

Support template definition export:

```text
JSON
```

The export should include:

```text
parameters
components
rules
materials references
hardware references
manufacturing rules
test cases
```

---

# 263. Template Portability

Template should be portable between tenants only when:

```text
referenced materials
hardware
assets
rules
```

can be resolved.

Missing dependencies must be reported.

---

# 264. Dependency Manifest

Template export should include:

```text
materials
hardware
assets
rule libraries
```

---

# 265. Dependency Resolution

Import process:

```text
Import Template
 ↓
Check Dependencies
 ↓
Resolve IDs
 ↓
Report Missing
 ↓
Validate
 ↓
Create Draft
```

---

# 266. No Silent Dependency Substitution

Do not silently replace:

```text
material
hardware
asset
```

with a different item.

Require explicit mapping.

---

# 267. AI-Assisted Furniture Creation

Future API:

```http
POST /api/v1/furniture/ai/generate-definition
```

Input:

```json
{
  "description": "2400mm wide wardrobe with four shutters and three drawers"
}
```

Output:

```json
{
  "template_parameters": {},
  "suggested_components": [],
  "warnings": []
}
```

AI output is always:

```text
DRAFT
```

until validated and approved.

---

# 268. AI Modification

Support natural-language commands such as:

```text
Increase wardrobe width to 2700 mm.
Add two drawers.
Use 18 mm plywood.
Change shutters to laminate.
```

The AI layer should translate requests into structured commands.

---

# 269. AI Command Boundary

AI MUST NOT directly execute arbitrary SQL or machine commands.

Architecture:

```text
Natural Language
 ↓
Structured Command
 ↓
Authorization
 ↓
Validation
 ↓
Execution
```

---

# 270. AI Audit

Record:

```text
AI request
structured command
user
timestamp
result
validation
```

Do not store sensitive prompts unnecessarily.

---

# 271. Final Furniture Data Flow

```text
USER
 │
 ↓
FURNITURE TEMPLATE
 │
 ↓
PARAMETERS
 │
 ↓
RULE ENGINE
 │
 ├─────────────┐
 ↓             ↓
COMPONENTS   HARDWARE
 │             │
 ↓             ↓
PANELS       OPERATIONS
 │             │
 └──────┬──────┘
        ↓
   CANONICAL MODEL
        │
 ┌──────┼────────┬──────────┐
 ↓      ↓        ↓          ↓
2D     3D       BOM        BOQ
                        │
                        ↓
                   CUTLIST
                        │
                        ↓
                    NESTING
                        │
                        ↓
                       CNC
                        │
                        ↓
                       MES
```

---

# 272. Final Architecture Rules

The Parametric Furniture Engine MUST:

1. Be template-driven.
2. Be parameter-driven.
3. Be rule-driven.
4. Be deterministic.
5. Be versioned.
6. Be manufacturing-aware.
7. Be renderer-independent.
8. Support both 2D and 3D output.
9. Generate structured component data.
10. Generate structured manufacturing data.
11. Preserve stable identities.
12. Support revision comparison.
13. Support tenant-specific customization.
14. Enforce RBAC.
15. Preserve audit history.

---

# 273. Most Important Design Rule

Never implement a furniture item like:

```javascript
createWardrobeMesh(width, height, depth)
```

as the entire solution.

Instead:

```text
Wardrobe Template
       ↓
Parameter Definition
       ↓
Rule Evaluation
       ↓
Component Graph
       ↓
Panel Graph
       ↓
Manufacturing Graph
       ↓
Geometry Generators
       ↓
2D + 3D + BOM + Cutlist
```

The mesh is only one output.

---

# 274. Cursor Pre-Implementation Instruction

Before writing code, Cursor MUST:

1. Analyze the existing repository.
2. Identify current furniture implementation.
3. Identify existing 2D CAD model.
4. Identify existing Three.js model.
5. Identify current material catalog.
6. Identify current laminate/board database.
7. Identify current hardware database.
8. Identify BOM/BOQ implementation.
9. Identify cutlist implementation.
10. Identify nesting implementation.
11. Identify CNC implementation.
12. Identify MES/production implementation.
13. Identify existing database tables.
14. Identify APIs.
15. Identify RBAC.
16. Identify revision mechanisms.
17. Identify tests.
18. Identify duplication.
19. Identify architectural gaps.
20. Produce a migration plan before modifying core structures.

---

# 275. Cursor Implementation Order

## Phase 1

```text
Parameter Model
Template Model
Component Model
Rule Engine
Dependency Graph
```

## Phase 2

```text
Panel Generator
Carcass Generator
Shelf Generator
Shutter Generator
Drawer Generator
```

## Phase 3

```text
Material Engine
Edge Band Engine
Hardware Engine
```

## Phase 4

```text
2D Generator
3D Generator
```

## Phase 5

```text
BOM
BOQ
Pricing
```

## Phase 6

```text
Cutlist
Manufacturing Operations
```

## Phase 7

```text
Nesting Integration
CNC Integration
```

## Phase 8

```text
MES Traceability
QR Labels
Production Release
```

## Phase 9

```text
Template Designer
Rule Builder
Template Testing
Publishing
```

## Phase 10

```text
AI-Assisted Parametric Design
Advanced Optimization
```

---

# 276. Definition of Done

The Parametric Furniture Engine is considered implementation-ready when:

```text
[ ] Template engine implemented
[ ] Versioning implemented
[ ] Parameter engine implemented
[ ] Formula engine implemented
[ ] Dependency graph implemented
[ ] Rule engine implemented
[ ] Component hierarchy implemented
[ ] Carcass generator implemented
[ ] Panel generator implemented
[ ] Shelf generator implemented
[ ] Partition generator implemented
[ ] Shutter generator implemented
[ ] Drawer generator implemented
[ ] Hardware engine implemented
[ ] Edge band engine implemented
[ ] Material engine implemented
[ ] Kitchen templates implemented
[ ] Wardrobe templates implemented
[ ] Storage templates implemented
[ ] Furniture collision validation implemented
[ ] Clearance validation implemented
[ ] 2D output implemented
[ ] 3D output implemented
[ ] BOM implemented
[ ] BOQ implemented
[ ] Dual pricing implemented
[ ] Cutlist implemented
[ ] Manufacturing operations implemented
[ ] Nesting integration implemented
[ ] CNC integration interface implemented
[ ] Panel labels implemented
[ ] MES traceability implemented
[ ] Revisioning implemented
[ ] Release workflow implemented
[ ] RBAC implemented
[ ] Tenant isolation implemented
[ ] Audit trail implemented
[ ] Template designer implemented
[ ] Rule builder implemented
[ ] Automated template tests implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Browser tests implemented
[ ] Performance tests completed
[ ] Manufacturing regression tests completed
```

---

# 277. Final Product Principle

The Parametric Furniture Engine should ultimately make this possible:

```text
Designer enters:

Width = 2400
Depth = 600
Height = 2400
Material = 18mm MDF
Shutters = 4
Drawers = 3
```

and the platform automatically produces:

```text
                    WARDROBE
                       │
          ┌────────────┼────────────┐
          ↓            ↓            ↓
         2D           3D          BIM
          │            │            │
          └────────────┼────────────┘
                       ↓
                 COMPONENT TREE
                       ↓
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
      PANELS         HARDWARE       MATERIALS
        │              │              │
        └──────────────┼──────────────┘
                       ↓
                      BOM
                       ↓
                      BOQ
                       ↓
                   PRICING
                       ↓
                    CUTLIST
                       ↓
                    NESTING
                       ↓
                     CNC
                       ↓
                   LABELS / QR
                       ↓
                      MES
```

This is the core architectural requirement:

> **A parametric furniture object is not merely a 3D cabinet. It is a structured engineering and manufacturing definition from which the 2D drawing, 3D model, BOM, BOQ, cutlist, machine operations and MES traceability are all derived.**

That is the boundary Cursor must preserve throughout implementation.
