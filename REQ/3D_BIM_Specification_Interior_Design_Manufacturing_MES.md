# 3D / BIM Specification
## Interior Design, Parametric Furniture, Architectural Modeling & Manufacturing Platform

**Document ID:** BIM-3D-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript/ES6 Developers, 3D/Geometry Engineers, BIM Engineers, QA, DevOps  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**3D Engine:** Three.js / WebGL  
**API:** REST `/api/v1`  
**Primary Internal Unit:** millimetres (mm)  
**Coordinate System:** Project-local Cartesian XYZ  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the platform's 3D/BIM subsystem.

The 3D/BIM subsystem MUST provide a structured, data-rich 3D representation of:

- Buildings
- Floors
- Levels
- Rooms
- Walls
- Doors
- Windows
- Columns
- Beams
- Slabs
- Ceilings
- Floor finishes
- Stairs
- Architectural elements
- MEP placeholders
- Modular furniture
- Parametric cabinets
- Kitchens
- Wardrobes
- Storage units
- Appliances
- Fixtures
- Materials
- Hardware
- Manufacturing components
- BIM metadata
- Quantities
- Cost data
- Relationships
- Design alternatives
- Revisions

The system MUST NOT be implemented as a collection of independent Three.js meshes.

Three.js is the visualization layer.

The authoritative model MUST be a structured BIM/domain model that can drive:

```text
3D Visualization
+
2D Plan
+
Elevations
+
Sections
+
BOQ/BOM
+
Pricing
+
Engineering
+
Manufacturing
+
MES
```

---

# 2. Product Objective

The 3D/BIM engine must create a unified digital representation of the interior project.

Target architecture:

```text
2D CAD
   ↓
BIM Semantic Model
   ↓
3D Geometry
   ↓
Materials / Metadata
   ↓
Quantities
   ↓
BOM / BOQ
   ↓
Engineering
   ↓
Manufacturing
```

The same semantic object must be represented consistently across:

```text
Plan
Elevation
3D
Schedule
BOM
BOQ
Manufacturing
```

---

# 3. Core BIM Principle

Every BIM element MUST have:

```text
Identity
+
Semantic Type
+
Geometry
+
Parameters
+
Relationships
+
Materials
+
Quantities
+
Level
+
Room
+
Phase
+
Revision
+
Metadata
+
Audit Information
```

Example:

```json
{
  "id": "uuid",
  "type": "wall",
  "category": "ARCHITECTURAL_WALL",
  "level_id": "L1",
  "room_ids": ["R1", "R2"],
  "geometry": {},
  "parameters": {
    "length": 4200,
    "height": 3000,
    "thickness": 150
  },
  "materials": [],
  "quantities": {},
  "properties": {},
  "revision": 4
}
```

---

# 4. 3D vs BIM

The implementation MUST distinguish:

## 3D Geometry

Answers:

```text
What does the object look like?
```

## BIM Data

Answers:

```text
What is the object?
Where is it?
What is it made of?
What are its dimensions?
What room does it belong to?
What depends on it?
What does it cost?
How is it manufactured?
```

The BIM model MUST remain usable even if the 3D renderer is replaced.

---

# 5. BIM Object Hierarchy

Required hierarchy:

```text
Tenant
 └── Project
      └── Building
           ├── Site
           └── Building
                ├── Level
                │    ├── Room
                │    │    ├── Wall
                │    │    ├── Door
                │    │    ├── Window
                │    │    ├── Ceiling
                │    │    ├── Floor
                │    │    └── Furniture
                │    └── Structural Elements
                └── Shared Elements
```

---

# 6. Building

Building object:

```text
id
project_id
name
code
location
orientation
elevation
levels
properties
```

Support multiple buildings per project.

---

# 7. Site

Optional site model:

```text
site boundary
north direction
building footprint
site levels
landscape placeholder
external structures
```

Initial release may keep site modeling lightweight.

---

# 8. Levels / Floors

Each level MUST contain:

```text
id
building_id
name
code
elevation
height
default_wall_height
order
```

Example:

```text
Ground Floor
elevation = 0

First Floor
elevation = 3200
```

---

# 9. Level Relationships

Objects SHOULD reference their level.

Example:

```text
wall.level_id
door.level_id
window.level_id
furniture.level_id
```

Changing level elevation MUST update dependent world transforms.

---

# 10. Room BIM Object

Room:

```text
id
level_id
name
number
type
boundary
height
floor_finish
ceiling_finish
properties
```

Derived quantities:

```text
area
perimeter
volume
wall_finish_area
ceiling_area
floor_area
```

---

# 11. Space Semantics

Support room categories:

```text
Living Room
Bedroom
Master Bedroom
Kitchen
Dining
Utility
Bathroom
Toilet
Balcony
Corridor
Office
Store
Puja
Other
Custom
```

Tenant-custom categories MUST be supported.

---

# 12. Wall BIM Object

Wall MUST be semantic.

Properties:

```text
type
length
thickness
height
base_offset
top_offset
material
finish_inside
finish_outside
structural
fire_rating
```

Geometry is generated from:

```text
centerline
thickness
height
joins
openings
```

---

# 13. Wall 3D Generation

Wall mesh MUST be generated from wall parameters.

Conceptually:

```text
2D wall centerline
        ↓
Offset
        ↓
Wall polygon
        ↓
Extrusion
        ↓
3D wall
```

Do not manually store arbitrary wall meshes as the primary source.

---

# 14. Wall Openings

Doors/windows create wall openings.

The BIM relationship:

```text
Wall
 ├── Door Opening
 └── Window Opening
```

Changing door/window position or size MUST regenerate the wall geometry.

---

# 15. Door BIM Object

Parameters:

```text
width
height
frame_width
frame_depth
sill_height
wall_offset
swing_direction
swing_angle
hinge_side
door_type
material
```

Door types:

```text
single swing
double swing
sliding
pocket
folding
bi-fold
```

---

# 16. Door Geometry

Door instance should generate:

```text
frame
shutter
hardware placeholders
swing representation
opening
```

The swing arc is primarily a 2D representation.

3D must display the actual door leaf orientation.

---

# 17. Window BIM Object

Parameters:

```text
width
height
sill_height
head_height
frame_depth
wall_offset
window_type
frame_material
glass_material
```

Types:

```text
fixed
sliding
casement
awning
bay
corner
```

---

# 18. Window Geometry

Generate:

```text
frame
glass
opening
sill
head
```

Wall opening MUST remain associative.

---

# 19. Floor BIM Object

Floor may contain:

```text
slab
floor_finish
skirting
level
thickness
material
```

Separate:

```text
structural slab
finish layer
```

where possible.

---

# 20. Ceiling BIM Object

Support:

```text
flat ceiling
dropped ceiling
bulkhead
cove
false ceiling
multi-level ceiling
```

Parameters:

```text
elevation
thickness
boundary
material
drop
```

---

# 21. False Ceiling

False ceiling should support semantic components:

```text
ceiling panel
bulkhead
cove
profile
light
access panel
```

---

# 22. Column BIM Object

Parameters:

```text
shape
width
depth
diameter
height
base_elevation
rotation
material
structural
```

Shapes:

```text
square
rectangular
circular
custom
```

---

# 23. Beam BIM Object

Parameters:

```text
width
depth
length
elevation
rotation
material
structural
```

Beam geometry MUST remain connected to structural metadata.

---

# 24. Stair BIM Object

Initial support:

```text
straight stair
L stair
U stair
```

Parameters:

```text
width
riser_height
tread_depth
riser_count
total_rise
total_run
```

---

# 25. Furniture BIM Object

Furniture is a first-class BIM object.

Required:

```text
furniture_instance
template_id
template_version
category
width
depth
height
location
rotation
parameters
materials
components
```

---

# 26. Parametric Furniture

Furniture must be generated from:

```text
template
+
parameters
+
rules
+
materials
```

Example:

```text
Wardrobe
width = 2400
depth = 600
height = 2400
shutter_count = 4
drawer_count = 3
shelf_count = 8
```

Changing width MUST regenerate the model.

---

# 27. Parametric Furniture Rule

The 3D furniture mesh is a generated artifact.

Authoritative source:

```text
Furniture Template
+
Parameter Values
+
Manufacturing Rules
```

Not:

```text
GLTF mesh
```

---

# 28. Furniture Component Hierarchy

Example:

```text
Wardrobe
 ├── Carcass
 │    ├── Left Panel
 │    ├── Right Panel
 │    ├── Top
 │    ├── Bottom
 │    └── Back
 ├── Partitions
 ├── Shelves
 ├── Shutters
 ├── Drawers
 ├── Hardware
 └── Edge Bands
```

Each component SHOULD be a BIM/manufacturing object.

---

# 29. Component Metadata

Each furniture component should contain:

```text
component_id
component_type
parent_furniture_id
dimensions
material
thickness
edge_banding
hardware
manufacturing_role
```

---

# 30. Manufacturing Relationship

Furniture BIM:

```text
Furniture
 ↓
Components
 ↓
Panels
 ↓
BOM
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
```

The 3D model must not bypass the manufacturing data model.

---

# 31. Appliances

Support:

```text
refrigerator
oven
hob
dishwasher
microwave
washing machine
dryer
TV
AC indoor unit
AC outdoor unit
```

Appliances may use:

```text
catalog dimensions
3D asset
clearance rules
connection points
```

---

# 32. Appliance Clearance

Each appliance MAY define:

```text
front_clearance
side_clearance
top_clearance
rear_clearance
service_clearance
```

The validation engine should detect violations.

---

# 33. Fixtures

Support:

```text
sink
wash basin
toilet
shower
bathtub
mirror
light
fan
switch
socket
```

Fixtures may be represented by reusable BIM families/templates.

---

# 34. BIM Families / Templates

Reusable objects SHOULD be represented as:

```text
BIM Family
 ↓
Family Version
 ↓
Family Type
 ↓
Instance
```

Example:

```text
Door Family
 ├── 750 × 2100
 ├── 800 × 2100
 └── 900 × 2100
```

---

# 35. Family Versioning

Changing a family MUST NOT silently alter existing project instances.

Instances reference:

```text
family_id
family_version
type_id
```

Migration must be explicit.

---

# 36. Materials

Every applicable BIM object MAY reference one or more materials.

Material:

```text
id
tenant_id
name
category
brand
code
texture
color
thickness
density
cost
selling_price
properties
```

---

# 37. Material Categories

```text
MDF
HDF
Plywood
Particle Board
Laminate
Veneer
Acrylic
Glass
Stone
Quartz
Metal
Paint
Tile
Wood
Fabric
PVC
Aluminium
Other
```

---

# 38. PBR Materials

3D rendering should support:

```text
base_color
metalness
roughness
normal_map
opacity
emissive
ao
```

Three.js materials SHOULD use physically based rendering where practical.

---

# 39. Material Assignment

Material changes in BIM MUST update:

```text
3D appearance
2D representation
BOM
BOQ
pricing
manufacturing data
```

where applicable.

---

# 40. Material Instance vs Material Definition

Separate:

```text
Material Definition
```

from:

```text
Material Assignment
```

Example:

```text
Greenlam 1234
```

is the definition.

```text
Kitchen Shutter = Greenlam 1234
```

is the assignment.

---

# 41. Texture Management

Textures SHOULD support:

```text
diffuse/base color
normal
roughness
metalness
alpha
```

Texture mapping:

```text
scale
rotation
offset
mapping mode
```

---

# 42. Real-World Texture Scale

Texture dimensions MUST be physically meaningful.

Example:

```text
laminate repeat = 600 mm
wood grain direction = horizontal
```

Avoid arbitrary UV scaling.

---

# 43. Grain Direction

Manufacturing materials SHOULD support:

```text
NONE
HORIZONTAL
VERTICAL
CUSTOM
```

Grain direction affects:

```text
panel orientation
nesting
cutlist
CNC
```

---

# 44. Camera System

Support:

```text
perspective camera
orthographic camera
```

Modes:

```text
Perspective
Top
Front
Back
Left
Right
Bottom
Axonometric
```

---

# 45. Camera Navigation

Support:

```text
orbit
pan
zoom
dolly
fly
walk
```

Optional:

```text
first-person
```

---

# 46. Camera Controls

Recommended:

```text
left drag = orbit
middle drag = pan
wheel = zoom
```

Allow alternative navigation schemes.

---

# 47. Camera Bookmarks

Users can save:

```text
camera position
target
zoom
projection
visibility
```

Example:

```text
Living Room View
Kitchen View
Master Bedroom View
```

---

# 48. Walkthrough Mode

Optional first release.

Support:

```text
W/A/S/D
mouse look
collision
floor restriction
```

Prevent camera from passing through walls.

---

# 49. Scene Graph

Three.js scene graph MUST be generated from BIM/domain data.

Recommended:

```text
Scene
 ├── Building
 │    ├── Level
 │    │    ├── Rooms
 │    │    ├── Architecture
 │    │    ├── Furniture
 │    │    └── MEP
 │    └── Level
 ├── Selection
 ├── Helpers
 └── Environment
```

---

# 50. BIM Scene vs Render Scene

Do not use the Three.js scene as the database.

Architecture:

```text
BIM Domain Model
       ↓
Scene Builder
       ↓
Three.js Scene
```

---

# 51. Scene Synchronization

When BIM object changes:

```text
BIM object changed
 ↓
mark renderer dirty
 ↓
regenerate affected geometry
 ↓
update mesh
 ↓
update material
 ↓
update metadata
```

Do not rebuild the entire scene for a single object change.

---

# 52. Object Registry

Maintain:

```javascript
bimObjectRegistry
```

Example:

```javascript
registry.get(objectId)
```

Mapping:

```text
BIM Object ID
     ↕
Three.js Object3D
```

---

# 53. Stable IDs

Three.js `uuid` MUST NOT be the BIM identity.

Use application-level:

```text
bim_object_id
```

Three.js UUID is rendering infrastructure only.

---

# 54. Object Metadata in Three.js

Each relevant Object3D SHOULD contain:

```javascript
userData = {
  bimObjectId,
  objectType,
  tenantId,
  projectId,
  levelId
};
```

Do not store sensitive business data unnecessarily.

---

# 55. Selection in 3D

Support:

```text
single selection
multi-selection
box selection
click hierarchy
select parent
select component
```

Raycasting MUST resolve:

```text
mesh
→ component
→ furniture
→ room
→ BIM object
```

---

# 56. 3D Properties Panel

Selecting an object shows:

```text
Name
Type
Level
Room
Dimensions
Position
Rotation
Material
Parameters
BIM Properties
Manufacturing Properties
```

Only authorized fields are displayed.

---

# 57. 3D Transform

Objects may support:

```text
move
rotate
scale
```

But parametric BIM objects MUST distinguish:

```text
Transform
```

from:

```text
Parametric Dimension
```

Example:

```text
Wardrobe width
```

should not be changed by arbitrary mesh scaling if the intended operation is to change the furniture parameter.

---

# 58. Parametric Transform Rules

For parametric objects:

```text
position → transform
rotation → transform
width/depth/height → parameter
```

Scaling should normally update parameters rather than simply scaling geometry.

---

# 59. Gizmos

Support:

```text
translate gizmo
rotate gizmo
scale gizmo
```

Parametric objects SHOULD expose dimension handles where practical.

---

# 60. Dimension Handles

Example:

```text
Wardrobe
 ├── width handle
 ├── depth handle
 └── height handle
```

Dragging a handle:

```text
updates parameter
 ↓
recalculates geometry
 ↓
updates 2D
 ↓
updates BOM
```

---

# 61. Wall 3D Editing

Selecting wall allows:

```text
move
stretch
change thickness
change height
change material
```

The system MUST preserve:

```text
connected wall relationships
openings
room boundaries
```

---

# 62. Door/Window 3D Editing

Dragging a door/window along a wall:

```text
updates opening position
updates 2D
updates 3D
updates elevations
```

Changing width:

```text
updates opening
updates wall
updates 2D
```

---

# 63. Room 3D Visualization

Rooms should support:

```text
room isolation
room highlight
room clipping
room metadata
```

Selecting a room:

```text
highlight boundary
highlight walls
show furniture
show finishes
```

---

# 64. Section Box / Clipping

Support a clipping box for:

```text
inspection
interior review
construction review
```

Optional per-axis clipping:

```text
X
Y
Z
```

---

# 65. Floor Isolation

User can:

```text
show all floors
show selected floor
show adjacent floors
hide all other floors
```

---

# 66. Level Visibility

Support:

```text
CURRENT
ABOVE
BELOW
ALL
```

Example:

```text
Current floor = visible
Floor above = ghosted
Floor below = hidden
```

---

# 67. Ghost / Underlay Mode

Non-active levels may be rendered as:

```text
ghosted
semi-transparent
non-selectable
```

---

# 68. 3D View Modes

Required:

```text
Shaded
Material Preview
Realistic
Wireframe
X-Ray
Hidden Line
```

---

# 69. Presentation Mode

Presentation mode should hide:

```text
selection outlines
construction helpers
grid
BIM metadata
debug objects
```

unless enabled.

---

# 70. Real-Time Lighting

Support:

```text
ambient/environment light
directional/sun light
point lights
spot lights
area-light approximation where practical
```

---

# 71. Sun / Natural Lighting

Optional but recommended:

```text
date
time
latitude
longitude
north direction
```

Sun position can then be calculated.

---

# 72. Artificial Lighting

Light objects should have:

```text
type
power
color
temperature
beam_angle
position
```

Lights may be linked to electrical BIM objects.

---

# 73. Render Quality Profiles

Provide:

```text
LOW
MEDIUM
HIGH
PRESENTATION
```

Profiles control:

```text
shadow quality
texture resolution
antialiasing
post-processing
ambient occlusion
```

---

# 74. Performance Architecture

The 3D engine MUST support:

```text
frustum culling
object visibility
LOD
instancing
geometry reuse
material reuse
lazy loading
progressive loading
```

---

# 75. Geometry Instancing

Repeated objects such as:

```text
chairs
lights
handles
tiles
hardware
```

SHOULD use GPU instancing where appropriate.

---

# 76. Geometry Reuse

Identical geometry SHOULD be reused.

Avoid:

```text
new geometry per identical object
```

where instancing is possible.

---

# 77. Material Reuse

Material definitions should be cached.

Avoid creating duplicate Three.js materials for identical BIM materials.

---

# 78. Large Model Loading

Load in stages:

```text
Project metadata
 ↓
Levels
 ↓
Rooms
 ↓
Architecture
 ↓
Furniture
 ↓
Materials
 ↓
Detailed components
```

---

# 79. Progressive Rendering

Initial display:

```text
simplified geometry
```

then:

```text
detailed geometry
```

as resources load.

---

# 80. 3D Asset Formats

Support:

```text
GLB / GLTF
```

as the preferred external 3D asset format.

Optional future:

```text
OBJ
FBX
```

Do not make proprietary formats foundational.

---

# 81. Asset Library

3D assets should have:

```text
asset_id
name
category
version
file
thumbnail
bounding_box
default_scale
origin
material_dependencies
metadata
```

---

# 82. Asset Origin

Imported assets MUST be normalized to a predictable origin.

Support:

```text
center
bottom-center
custom origin
```

For furniture:

```text
bottom-center
```

is recommended.

---

# 83. Asset Scale

Asset metadata MUST define:

```text
source_unit
scale_factor
real_world_dimensions
```

Avoid silently scaling assets.

---

# 84. Asset Validation

When importing GLB/GLTF:

```text
validate file
check geometry
check materials
check texture references
check bounding box
check units
check polygon count
```

---

# 85. Asset Optimization

For web delivery:

```text
mesh simplification
texture compression
LOD
DRACO/Meshopt where supported
```

---

# 86. BIM Metadata

Every BIM object may contain custom properties:

```text
property_set
property_name
value
data_type
unit
source
```

Example:

```text
Fire Rating = 2 hr
Manufacturer = ABC
Product Code = XYZ-123
```

---

# 87. Property Sets

Support predefined groups:

```text
Identity
Dimensions
Materials
Construction
Manufacturer
Cost
Manufacturing
Installation
Maintenance
Custom
```

---

# 88. BIM Classification

Support classification fields:

```text
category
subcategory
classification_system
classification_code
```

The system should be extensible for:

```text
IFC categories
Uniclass
OmniClass
Custom enterprise classifications
```

---

# 89. IFC Readiness

The internal BIM model SHOULD be designed so it can later map to IFC.

Do not claim full IFC compatibility unless an actual IFC parser/exporter is implemented.

Initial mapping should cover concepts such as:

```text
IfcProject
IfcSite
IfcBuilding
IfcBuildingStorey
IfcSpace
IfcWall
IfcDoor
IfcWindow
IfcSlab
IfcBeam
IfcColumn
IfcStair
IfcFurniture
IfcBuildingElementProxy
```

---

# 90. IFC Export

Future/export phase SHOULD support:

```text
IFC4
```

Export should map:

```text
BIM identity
geometry
materials
properties
relationships
levels
spaces
```

Unsupported objects MUST be reported.

---

# 91. IFC Import

Future phase MAY support IFC import.

Import pipeline:

```text
IFC
 ↓
Parser
 ↓
Mapping
 ↓
Internal BIM Model
 ↓
Validation
 ↓
3D Scene
```

Do not use IFC objects directly as Three.js objects.

---

# 92. BIM Relationships

Support:

```text
contains
contained_by
hosts
hosted_by
connects_to
connected_by
belongs_to_room
belongs_to_level
depends_on
generated_from
generated_by
manufactured_from
```

---

# 93. Relationship Examples

```text
Door
 → hosted_by
Wall
```

```text
Furniture
 → located_in
Room
```

```text
Room
 → contained_in
Level
```

```text
Panel
 → generated_from
Furniture Component
```

---

# 94. Spatial Containment

The engine should determine:

```text
Furniture → Room
Room → Level
Level → Building
Building → Project
```

If furniture moves into another room, relationship SHOULD update automatically.

---

# 95. Room Detection in 3D

Room volume may be derived from:

```text
floor boundary
wall boundary
ceiling height
```

Calculate:

```text
volume
```

where appropriate.

---

# 96. Quantity Takeoff

BIM model must generate quantities.

Examples:

```text
Wall area
Wall length
Wall volume
Floor area
Ceiling area
Door count
Window count
Furniture count
Panel area
Panel volume
```

---

# 97. Quantity Rules

Quantity extraction must use semantic geometry.

Example:

```text
wall gross area
-
door opening area
-
window opening area
=
net wall finish area
```

---

# 98. BOQ Integration

BIM quantities feed:

```text
BOQ
```

Example:

```text
Floor Tile
Quantity = 42.5 m²
```

Changing room boundary:

```text
BIM geometry changes
 ↓
quantity changes
 ↓
BOQ becomes stale
```

---

# 99. BOM Integration

Furniture components feed:

```text
BOM
```

Example:

```text
18mm MDF
Quantity = 14 panels
```

---

# 100. Pricing Integration

BIM object/material assignment feeds pricing.

Example:

```text
Material
+
Quantity
+
Pricing Rule
=
Cost
```

Do not store calculated pricing as the only source of truth.

---

# 101. 3D-to-2D Projection

The BIM model must support projections:

```text
Top
Front
Back
Left
Right
Section
Elevation
```

---

# 102. Plan Generation

Generate 2D plan from BIM objects.

For example:

```text
Wall 3D
 ↓
Top projection
 ↓
2D wall geometry
```

The generated plan must retain BIM object IDs.

---

# 103. Elevation Generation

Generate wall elevations from:

```text
wall
doors
windows
furniture
finishes
```

The elevation objects remain associative.

---

# 104. Section Generation

Section engine should:

```text
define cut plane
intersect BIM geometry
project cut objects
generate 2D section
```

Initial release may support simplified sections.

---

# 105. View-Dependent Representation

A BIM object can have different representations:

```text
plan
elevation
3D
manufacturing
```

Example:

```text
Door:
Plan → swing symbol
Elevation → door leaf
3D → full door assembly
```

---

# 106. BIM View Templates

Support:

```text
Architectural
Furniture
Electrical
Plumbing
Ceiling
Manufacturing
Presentation
```

Each view template defines:

```text
visibility
materials
line style
camera
section
filters
```

---

# 107. Object Filters

Support filters:

```text
category
family
type
material
level
room
manufacturer
status
phase
```

---

# 108. Search

3D/BIM search must support:

```text
name
ID
category
room
level
material
family
manufacturer
product code
```

---

# 109. Object Tree

3D BIM browser:

```text
Building
 ├── Ground Floor
 │    ├── Living Room
 │    │    ├── Walls
 │    │    ├── Doors
 │    │    ├── Windows
 │    │    └── Furniture
 │    └── Kitchen
 └── First Floor
```

Tree and 3D selection MUST stay synchronized.

---

# 110. Isolation

Support:

```text
isolate selected
hide selected
hide others
show all
```

---

# 111. Section / Clipping

Support:

```text
section box
section plane
clip plane
```

Section plane properties:

```text
position
normal
enabled
```

---

# 112. Measurement in 3D

Support:

```text
point-to-point
horizontal distance
vertical distance
angle
area
```

Measurement can be:

```text
temporary
persistent
```

---

# 113. Collision Detection

3D validation should detect:

```text
furniture-furniture
furniture-wall
door-furniture
furniture-window
fixture-wall
ceiling-light
```

---

# 114. Clearance Validation

Support:

```text
walking clearance
door swing clearance
drawer clearance
cabinet clearance
appliance clearance
maintenance clearance
```

---

# 115. Design Rules

Rules should be configurable.

Example:

```text
minimum walkway = 900 mm
minimum door clearance = 600 mm
```

Rules are data-driven.

---

# 116. Parametric Dependency Graph

Example:

```text
Room width
 ↓
Wall length
 ↓
Furniture placement
 ↓
Furniture parameter
 ↓
Panel geometry
 ↓
BOM
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
```

---

# 117. Stale State

Every derived object SHOULD expose:

```text
CURRENT
STALE
INVALID
NOT_GENERATED
```

Example:

```text
3D = CURRENT
BOM = STALE
CUTLIST = STALE
CNC = STALE
```

---

# 118. Regeneration

User can explicitly regenerate:

```text
3D
BOM
BOQ
Engineering
Manufacturing
```

Do not automatically regenerate expensive artifacts synchronously during every mouse movement.

---

# 119. Debounced Parametric Updates

During interactive dragging:

```text
preview geometry
```

After user commits:

```text
persist
regenerate dependencies
```

This prevents excessive API calls.

---

# 120. 3D Undo / Redo

Every semantic edit must use the same command system as 2D.

Example:

```text
Change wardrobe width
```

Command:

```text
old_value = 1800
new_value = 2000
```

Undo restores 1800.

---

# 121. Revisioning

BIM revisions:

```text
DRAFT
IN_REVIEW
APPROVED
LOCKED
SUPERSEDED
ARCHIVED
```

Approved BIM models SHOULD NOT be directly modified.

Create a new revision.

---

# 122. Design Alternatives

Optional:

```text
Option A
Option B
Option C
```

Each alternative references:

```text
parent revision
changed objects
```

Useful for client presentations.

---

# 123. Design Freeze

Support freeze states:

```text
architecture_frozen
furniture_frozen
materials_frozen
commercial_frozen
manufacturing_frozen
```

Editing a frozen area requires a new revision/change request.

---

# 124. 3D Sync with 2D

2D and 3D must share semantic IDs.

Example:

```text
2D Wall ID = W123
3D Wall ID = W123
```

They are two representations of the same BIM object.

---

# 125. 2D → 3D Sync

When 2D changes:

```text
2D geometry
 ↓
BIM update
 ↓
3D geometry regeneration
```

---

# 126. 3D → 2D Sync

Only semantic changes should update 2D.

Example:

```text
3D wall height changed
```

can update:

```text
elevation
section
wall metadata
```

Plan geometry may not change.

---

# 127. Conflict Handling

If 2D and 3D modify the same parameter:

```text
detect conflict
```

Display:

```text
2D value
3D value
last editor
timestamp
```

User chooses resolution.

---

# 128. 3D Save API

```http
POST /api/v1/bim/models/{id}/save
```

Request:

```json
{
  "base_version": 10,
  "commands": [],
  "client_session_id": "uuid"
}
```

---

# 129. BIM API

Required:

```http
GET    /api/v1/bim/models
POST   /api/v1/bim/models
GET    /api/v1/bim/models/{id}
PATCH  /api/v1/bim/models/{id}
DELETE /api/v1/bim/models/{id}
```

---

# 130. BIM Object API

```http
GET    /api/v1/bim/models/{id}/objects
POST   /api/v1/bim/models/{id}/objects
GET    /api/v1/bim/objects/{id}
PATCH  /api/v1/bim/objects/{id}
DELETE /api/v1/bim/objects/{id}
```

---

# 131. BIM Relationship API

```http
GET  /api/v1/bim/objects/{id}/relationships
POST /api/v1/bim/objects/{id}/relationships
DELETE /api/v1/bim/relationships/{id}
```

---

# 132. BIM Quantity API

```http
GET  /api/v1/bim/models/{id}/quantities
POST /api/v1/bim/models/{id}/quantities/recalculate
```

---

# 133. BIM Validation API

```http
POST /api/v1/bim/models/{id}/validate
GET  /api/v1/bim/models/{id}/validation-results
```

---

# 134. BIM Views API

```http
GET  /api/v1/bim/models/{id}/views
POST /api/v1/bim/models/{id}/views
PATCH /api/v1/bim/views/{id}
DELETE /api/v1/bim/views/{id}
```

---

# 135. BIM Export API

Support:

```http
GET /api/v1/bim/models/{id}/export/glb
GET /api/v1/bim/models/{id}/export/gltf
GET /api/v1/bim/models/{id}/export/json
GET /api/v1/bim/models/{id}/export/ifc
```

IFC endpoint should only be enabled when actual IFC support is implemented.

---

# 136. Asset API

```http
GET    /api/v1/assets
POST   /api/v1/assets
GET    /api/v1/assets/{id}
PATCH  /api/v1/assets/{id}
DELETE /api/v1/assets/{id}
POST   /api/v1/assets/{id}/validate
```

---

# 137. Family API

```http
GET    /api/v1/bim/families
POST   /api/v1/bim/families
GET    /api/v1/bim/families/{id}
PATCH  /api/v1/bim/families/{id}
POST   /api/v1/bim/families/{id}/versions
POST   /api/v1/bim/families/{id}/publish
```

---

# 138. Database Requirements

Minimum tables:

```text
bim_models
bim_levels
bim_spaces
bim_objects
bim_object_parameters
bim_object_relationships
bim_properties
bim_property_sets
bim_material_assignments
bim_quantities
bim_views
bim_view_filters
bim_revisions
bim_validation_results
bim_families
bim_family_versions
bim_family_types
bim_instances
bim_assets
bim_asset_versions
bim_asset_materials
```

---

# 139. BIM Object Table

Recommended:

```text
id
tenant_id
project_id
bim_model_id
level_id
room_id
parent_id
object_type
category
family_id
family_version_id
type_id
name
code
status
geometry_reference
parameter_data
property_data
metadata
revision
created_by
updated_by
created_at
updated_at
```

---

# 140. Geometry Storage

Do not store only the final mesh.

Store:

```text
parametric source
```

and optionally:

```text
generated geometry reference
```

Example:

```text
wall parameters
→ geometry generator
→ cached mesh
```

---

# 141. Mesh Cache

Generated mesh can be cached.

Cache key:

```text
object_id
+
object_revision
+
geometry_version
```

If source parameters change:

```text
cache invalidated
```

---

# 142. Geometry Representation

Use appropriate representations:

```text
primitive parameters
B-Rep where necessary
mesh for rendering
2D profile for plan/elevation
```

Do not use high-poly meshes for objects that can be generated procedurally.

---

# 143. Procedural Geometry

Recommended for:

```text
walls
doors
windows
ceilings
cabinets
wardrobes
shelves
panels
countertops
```

---

# 144. Asset Geometry

Use external GLB/GLTF assets for:

```text
decorative furniture
chairs
sofas
plants
decor
complex appliances
sanitary fixtures
```

where procedural geometry is unnecessary.

---

# 145. Geometry Quality

The renderer should support:

```text
low-poly
medium
high
```

depending on use case.

---

# 146. LOD

Example:

```text
LOD 0 = thumbnail
LOD 1 = simplified
LOD 2 = normal
LOD 3 = detailed
```

---

# 147. Visibility Rules

Objects can be:

```text
visible
hidden
isolated
ghosted
locked
```

Visibility state belongs to the view, not the BIM object globally.

---

# 148. View-Specific Visibility

Example:

```text
Furniture Plan:
walls = visible
furniture = visible
electrical = hidden
plumbing = hidden
```

---

# 149. BIM View State

View stores:

```text
camera
projection
visibility
filters
section
display_mode
environment
```

---

# 150. Saved Views

Users can save:

```text
3D View
Top View
Kitchen View
Living Room View
Ceiling View
Furniture View
Client Presentation View
```

---

# 151. Client Presentation Mode

Client mode SHOULD provide:

```text
clean UI
high-quality materials
camera bookmarks
room navigation
annotations hidden
internal metadata hidden
cost data hidden
```

Client users MUST only see authorized project content.

---

# 152. Design Presentation

Support:

```text
full-screen
slideshow
camera transitions
room navigation
```

Optional:

```text
before/after
design alternatives
```

---

# 153. VR/AR Readiness

Do not implement VR/AR initially, but architecture SHOULD avoid assumptions that prevent future WebXR support.

---

# 154. BIM-to-Manufacturing

Manufacturing engine should consume semantic furniture components.

Example:

```text
Wardrobe BIM
 ↓
Component tree
 ↓
Panel definitions
 ↓
Edge banding
 ↓
Hardware
 ↓
Drilling
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
```

---

# 155. Manufacturing Data Separation

BIM object:

```text
visual + semantic
```

Manufacturing object:

```text
production-specific
```

A manufacturing panel SHOULD reference the BIM component rather than replacing it.

---

# 156. Manufacturing Traceability

Example:

```text
Panel P123
 ↓
Component C456
 ↓
Furniture F789
 ↓
Room R1
 ↓
Project P1
```

This lineage MUST be queryable.

---

# 157. Panel 3D Metadata

Panel may store:

```text
length
width
thickness
grain
material
edge_banding
drilling
routing
grooving
source_component_id
```

---

# 158. QR Traceability

A manufactured component may receive:

```text
QR
barcode
panel_id
production_job_id
```

3D/BIM model should be able to locate the source component from a scanned panel.

---

# 159. Reverse Traceability

From a panel:

```text
Panel
 ↓
Furniture
 ↓
Room
 ↓
3D location
 ↓
2D location
```

This is important for factory troubleshooting.

---

# 160. BIM Validation

Validation categories:

```text
Geometry
Topology
Spatial
Parametric
Material
Clearance
Architecture
Manufacturing
Data Completeness
```

---

# 161. BIM Validation Examples

Errors:

```text
Door outside wall
Window outside wall
Room has no boundary
Furniture outside room
Wall disconnected
Invalid level
Missing material
Invalid furniture parameter
```

Warnings:

```text
low clearance
overlap
missing manufacturer
missing product code
```

---

# 162. Validation Severity

```text
INFO
WARNING
ERROR
BLOCKER
```

---

# 163. Approval Blocking

BIM approval MUST be blocked when relevant:

```text
BLOCKER
```

errors exist.

---

# 164. BIM Revision Comparison

Compare:

```text
Revision 10
vs
Revision 11
```

Detect:

```text
added
deleted
moved
resized
material changed
parameter changed
relationship changed
```

---

# 165. Visual Revision Diff

Provide optional visual diff:

```text
Added
Removed
Modified
```

with configurable highlighting.

---

# 166. Audit

Audit:

```text
object created
object changed
object deleted
material changed
family changed
parameter changed
revision created
revision approved
revision locked
view created
asset imported
IFC imported/exported
```

---

# 167. Authorization

All BIM APIs MUST enforce:

```text
authentication
tenant isolation
RBAC
project access
resource access
```

Sensitive fields such as internal pricing MUST be permission-controlled.

---

# 168. Client Data Protection

Client portal MUST NOT expose by default:

```text
supplier costs
internal margins
manufacturing rules
machine settings
internal QC notes
employee data
internal audit information
```

---

# 169. Multi-Tenant BIM

Every BIM object MUST carry tenant context.

Every query MUST scope:

```sql
tenant_id = current_tenant
```

Never trust browser-supplied tenant IDs.

---

# 170. Concurrent Editing

Use optimistic concurrency.

Each model/revision has:

```text
version
```

Save request contains:

```text
base_version
```

Mismatch:

```text
409 Conflict
```

---

# 171. Command-Based Editing

3D edits should use semantic commands:

```text
MoveObject
RotateObject
ChangeParameter
AssignMaterial
ChangeLevel
AddObject
DeleteObject
```

This supports:

```text
undo
redo
audit
sync
collaboration
```

---

# 172. Event Architecture

Events:

```text
BIM_OBJECT_CREATED
BIM_OBJECT_UPDATED
BIM_OBJECT_DELETED
BIM_PARAMETER_CHANGED
BIM_MATERIAL_CHANGED
BIM_LEVEL_CHANGED
BIM_RELATIONSHIP_CHANGED
BIM_REVISION_CREATED
BIM_MODEL_APPROVED
BIM_MODEL_LOCKED
```

---

# 173. Downstream Events

Example:

```text
BIM_PARAMETER_CHANGED
 ↓
3D geometry regeneration
 ↓
2D synchronization
 ↓
quantity recalculation
 ↓
BOM/BOQ invalidation
 ↓
manufacturing invalidation
```

---

# 174. Performance Requirements

Target standard interior project:

```text
1–5 buildings
1–20 levels
10–100 rooms
1,000–10,000 BIM objects
```

Initial model should load progressively.

---

# 175. Performance Targets

Target:

```text
Initial interactive scene < 5 sec
```

for standard projects after metadata load.

Interactive navigation:

```text
target ≥ 45 FPS
```

on supported desktop hardware.

---

# 176. Geometry Generation Performance

Single parametric object regeneration:

```text
target < 100 ms
```

for standard furniture.

Large furniture:

```text
asynchronous regeneration allowed
```

---

# 177. Avoid Full Scene Rebuild

Changing:

```text
one cabinet width
```

MUST NOT regenerate:

```text
entire building scene
```

Only affected geometry/dependencies should regenerate.

---

# 178. Memory Management

Dispose unused:

```text
BufferGeometry
Material
Texture
RenderTarget
```

Three.js memory leaks MUST be tested.

---

# 179. Renderer Lifecycle

When removing an object:

```javascript
disposeGeometry()
disposeMaterials()
removeFromScene()
removeFromRegistry()
```

---

# 180. Texture Cache

Use texture cache keyed by:

```text
texture_id
resolution
color_space
```

Dispose only when reference count reaches zero.

---

# 181. Browser Support

Target:

```text
Chrome
Edge
Firefox
Safari
```

Desktop-first.

WebGL capability MUST be detected.

---

# 182. WebGL Fallback

If WebGL is unavailable:

```text
show supported-browser error
```

Do not silently fail.

---

# 183. Mobile

Initial release:

```text
desktop-first
```

Tablet support may be limited.

Do not optimize the first release around mobile at the expense of CAD usability.

---

# 184. Units

Internal:

```text
mm
```

Display:

```text
mm
cm
m
inch
feet-inch
```

Three.js scene units SHOULD be normalized consistently, for example:

```text
1 Three.js unit = 1 mm
```

or another clearly documented conversion.

The application MUST use one consistent conversion layer.

---

# 185. Coordinate System

Recommended:

```text
X = width
Y = elevation
Z = depth
```

or:

```text
X = width
Y = depth
Z = elevation
```

The chosen system MUST be documented and used consistently across:

```text
2D
3D
export
BIM
manufacturing
```

For Three.js, a recommended mapping is:

```text
X = horizontal
Y = vertical/elevation
Z = depth
```

2D CAD:

```text
X = horizontal
Y = depth
```

Mapping:

```text
CAD X → 3D X
CAD Y → 3D Z
CAD elevation → 3D Y
```

---

# 186. Transform Matrix

Every object should have:

```text
position
rotation
scale
```

But semantic dimensions remain separate.

Example:

```text
position
rotation
width
depth
height
```

---

# 187. Pivot / Origin

Every parametric object MUST define its pivot.

Examples:

```text
Furniture → bottom-center
Door → hinge
Window → center
Wall → centerline origin
```

---

# 188. Snapping in 3D

Support:

```text
vertex
edge
face
center
grid
object
wall
level
```

---

# 189. 3D Grid

Support:

```text
horizontal grid
vertical guides
level grid
```

Grid spacing based on project units.

---

# 190. 3D Alignment

Support:

```text
align left
align right
align top
align bottom
align center
align to wall
align to level
```

---

# 191. 3D Duplication

Support:

```text
duplicate
array
linear array
radial array
```

Useful for:

```text
lights
chairs
cabinets
panels
```

---

# 192. BIM Schedules

Provide schedule data:

```text
Doors
Windows
Furniture
Rooms
Materials
Panels
```

Schedule fields can include:

```text
ID
Name
Type
Level
Room
Dimensions
Material
Quantity
Cost
Status
```

---

# 193. Schedule ↔ Model Synchronization

Selecting schedule row:

```text
select object in 3D
```

Selecting 3D object:

```text
highlight schedule row
```

---

# 194. Quantity Schedule

Example:

```text
Door Type D01
Quantity = 8
```

If a door is deleted:

```text
quantity = 7
```

---

# 195. Room Schedule

Fields:

```text
room number
room name
level
area
perimeter
ceiling height
floor finish
wall finish
ceiling finish
```

---

# 196. Furniture Schedule

Fields:

```text
furniture ID
name
type
room
width
depth
height
material
quantity
status
```

---

# 197. Material Schedule

Fields:

```text
material
brand
code
category
location
quantity
area
volume
cost
```

---

# 198. BIM Data Completeness

Before approval, required fields can be configured.

Example:

```text
Wall:
material required

Door:
manufacturer optional

Furniture:
template version required
```

---

# 199. BIM Data Validation

Missing required data:

```text
WARNING
```

or:

```text
BLOCKER
```

depending on project rules.

---

# 200. API Security

All APIs must validate:

```text
authentication
tenant
permission
resource
revision
input schema
```

Do not trust:

```text
tenant_id
created_by
updated_by
```

from clients.

---

# 201. File Security

3D assets:

```text
GLB
GLTF
textures
HDRI
images
```

must be stored securely.

Validate:

```text
MIME
extension
file size
asset structure
```

---

# 202. Asset Download Authorization

Users can download only assets they are authorized to access.

Use:

```text
signed URLs
```

or authenticated streaming.

---

# 203. API Response Model

Example:

```json
{
  "data": {
    "id": "BIM-001",
    "type": "WALL",
    "level_id": "L1",
    "parameters": {
      "length": 4200,
      "height": 3000,
      "thickness": 150
    }
  },
  "meta": {
    "version": 12
  }
}
```

---

# 204. Error Model

Use:

```text
BIM_INVALID_OBJECT
BIM_INVALID_PARAMETER
BIM_GEOMETRY_ERROR
BIM_VALIDATION_ERROR
BIM_VERSION_CONFLICT
BIM_PERMISSION_DENIED
BIM_ASSET_ERROR
BIM_SYNC_CONFLICT
BIM_EXPORT_ERROR
```

---

# 205. Testing Strategy

## Unit Tests

Test:

```text
wall generation
door opening
window opening
room volume
furniture generation
parametric updates
transforms
quantities
material assignment
relationships
```

---

# 206. Geometry Tests

Example:

```text
Wall:
length = 4000
thickness = 150
height = 3000
```

Expected:

```text
volume = 1.8 m³
```

before subtracting openings.

---

# 207. Parametric Furniture Tests

Example:

```text
Wardrobe width:
1800 → 2400
```

Expected:

```text
2D width updated
3D width updated
panel dimensions updated
BOM stale
cutlist stale
```

---

# 208. Synchronization Tests

```text
Move wall in 2D
→ BIM position updated
→ 3D wall updated
→ room recalculated
```

---

# 209. Manufacturing Traceability Tests

```text
3D furniture
→ component
→ panel
→ cutlist
→ CNC
```

All IDs remain traceable.

---

# 210. Revision Tests

```text
Revision 1 approved
 ↓
Change wall
 ↓
Revision 2
 ↓
Revision 1 unchanged
```

---

# 211. Client Access Tests

Verify client cannot access:

```text
internal cost
supplier price
manufacturing configuration
internal audit
production notes
```

---

# 212. Performance Tests

Test:

```text
1,000 objects
5,000 objects
10,000 objects
25,000 objects
```

Measure:

```text
load
render
selection
navigation
save
regeneration
memory
```

---

# 213. Memory Leak Tests

Repeatedly:

```text
load
unload
reload
```

and monitor:

```text
JS heap
GPU resources
textures
geometries
materials
```

---

# 214. BIM Export Tests

GLB:

```text
geometry
materials
object IDs
```

JSON:

```text
full semantic model
```

IFC, when implemented:

```text
classes
relationships
properties
geometry
```

---

# 215. 3D Rendering Acceptance Tests

```text
[ ] model loads
[ ] camera works
[ ] orbit works
[ ] pan works
[ ] zoom works
[ ] selection works
[ ] object properties work
[ ] materials render
[ ] textures render
[ ] levels work
[ ] rooms work
[ ] clipping works
[ ] views work
[ ] presentation mode works
```

---

# 216. Architectural Acceptance Tests

```text
[ ] wall generated from 2D
[ ] wall thickness correct
[ ] wall height correct
[ ] door opening correct
[ ] window opening correct
[ ] room volume correct
[ ] floor correct
[ ] ceiling correct
[ ] column correct
[ ] beam correct
```

---

# 217. Parametric Acceptance Tests

```text
[ ] furniture template loads
[ ] parameters load
[ ] parameter edit works
[ ] geometry regenerates
[ ] materials update
[ ] BOM updates/invalidation works
[ ] 2D updates
[ ] manufacturing becomes stale
```

---

# 218. BIM Relationship Acceptance Tests

```text
[ ] door hosted by wall
[ ] window hosted by wall
[ ] furniture belongs to room
[ ] room belongs to level
[ ] level belongs to building
[ ] component belongs to furniture
[ ] panel belongs to component
```

---

# 219. Cursor Implementation Sequence

Cursor MUST implement in this order.

## Phase 1 — BIM Foundation

```text
BIM domain model
object IDs
levels
rooms
relationships
properties
materials
revisions
```

## Phase 2 — Three.js Infrastructure

```text
scene manager
camera
controls
renderer
object registry
selection
materials
lighting
```

## Phase 3 — Architectural 3D

```text
walls
doors
windows
floors
ceilings
columns
beams
stairs
```

## Phase 4 — Furniture

```text
furniture templates
parametric generation
cabinets
kitchens
wardrobes
fixtures
appliances
```

## Phase 5 — BIM Intelligence

```text
relationships
quantities
schedules
validation
property sets
filters
```

## Phase 6 — 2D Synchronization

```text
2D ↔ BIM
2D ↔ 3D
elevations
sections
plans
```

## Phase 7 — Manufacturing

```text
component lineage
panel data
BOM
cutlist dependencies
manufacturing invalidation
```

## Phase 8 — Advanced BIM

```text
IFC
advanced sections
design alternatives
collaboration
advanced rendering
```

---

# 220. Cursor Pre-Implementation Analysis

Before modifying the codebase, Cursor MUST inspect:

```text
Three.js setup
existing 3D renderer
existing scene management
existing furniture objects
existing material system
existing 2D CAD model
existing room/wall objects
existing database schema
existing APIs
existing asset library
existing BOM
existing manufacturing model
existing RBAC
existing revision system
existing tests
```

Cursor MUST produce:

```text
CURRENT 3D ARCHITECTURE
CURRENT BIM MODEL
CURRENT THREE.JS SCENE
CURRENT OBJECT TYPES
CURRENT MATERIAL PIPELINE
CURRENT ASSET PIPELINE
CURRENT 2D ↔ 3D LINKS
CURRENT MANUFACTURING LINKS
GAPS
DUPLICATION
TECHNICAL DEBT
PERFORMANCE RISKS
MIGRATION PLAN
```

Do not rewrite functioning subsystems without first mapping them.

---

# 221. Recommended JavaScript Structure

```text
/src/bim/

domain/
  BimModel.js
  BimObject.js
  BimLevel.js
  BimRoom.js
  BimRelationship.js
  BimRevision.js

architecture/
  WallGenerator.js
  DoorGenerator.js
  WindowGenerator.js
  FloorGenerator.js
  CeilingGenerator.js
  ColumnGenerator.js
  BeamGenerator.js
  StairGenerator.js

furniture/
  FurnitureGenerator.js
  FurnitureTemplate.js
  FurnitureInstance.js
  CabinetGenerator.js
  WardrobeGenerator.js
  KitchenGenerator.js

scene/
  SceneManager.js
  SceneBuilder.js
  ObjectRegistry.js
  VisibilityManager.js

camera/
  CameraManager.js
  ViewManager.js
  SectionManager.js

interaction/
  SelectionManager.js
  TransformManager.js
  Snap3DManager.js

materials/
  MaterialManager.js
  TextureManager.js

assets/
  AssetManager.js
  GltfLoaderService.js
  AssetCache.js

validation/
  BimValidator.js
  ClearanceValidator.js
  RelationshipValidator.js

quantities/
  QuantityEngine.js
  ScheduleEngine.js

sync/
  CadBimSyncService.js
  Bim3DSyncService.js
  DependencyManager.js

history/
  CommandManager.js

export/
  GltfExporter.js
  JsonExporter.js
  IfcExporter.js
```

---

# 222. Recommended PHP Structure

```text
src/
  BIM/
    Domain/
    Services/
    Repositories/
    Policies/
    DTO/
    Commands/
    Events/
    Validation/
    Quantity/
    Export/
    Import/
```

Services:

```text
BimModelService
BimObjectService
BimRelationshipService
BimGeometryService
BimQuantityService
BimValidationService
BimRevisionService
BimAssetService
BimFamilyService
BimExportService
BimSyncService
```

---

# 223. Architecture Boundary

Use:

```text
UI
 ↓
3D Interaction Layer
 ↓
Command Layer
 ↓
BIM Domain
 ↓
Geometry / Parametric Engine
 ↓
Persistence/API
```

Three.js must remain below the domain layer.

---

# 224. Anti-Corruption Rule

Do not allow Three.js-specific objects to leak into:

```text
PHP
database
BIM API
business rules
manufacturing engine
```

The BIM layer must be renderer-independent.

---

# 225. Rendering Adapter

Use an adapter concept:

```text
BIM Model
 ↓
ThreeJsRendererAdapter
 ↓
Three.js
```

Future renderer changes should not require rewriting the BIM domain.

---

# 226. Definition of Done

3D/BIM implementation is complete only when:

```text
[ ] BIM domain model implemented
[ ] Building implemented
[ ] Levels implemented
[ ] Rooms implemented
[ ] Walls implemented
[ ] Doors implemented
[ ] Windows implemented
[ ] Floors implemented
[ ] Ceilings implemented
[ ] Columns implemented
[ ] Beams implemented
[ ] Stairs implemented
[ ] Furniture implemented
[ ] Parametric furniture implemented
[ ] Materials implemented
[ ] Assets implemented
[ ] Relationships implemented
[ ] Property sets implemented
[ ] Quantities implemented
[ ] Schedules implemented
[ ] Validation implemented
[ ] Revisions implemented
[ ] 2D synchronization implemented
[ ] 3D scene implemented
[ ] Camera/navigation implemented
[ ] Selection implemented
[ ] Transform tools implemented
[ ] Clipping implemented
[ ] View templates implemented
[ ] Presentation mode implemented
[ ] BOM/BOQ integration implemented
[ ] Manufacturing traceability implemented
[ ] API implemented
[ ] Database implemented
[ ] RBAC enforced
[ ] Tenant isolation enforced
[ ] Asset security implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Browser tests implemented
[ ] Performance tests completed
[ ] Memory leak tests completed
```

---

# 227. Final BIM Architecture

The intended architecture is:

```text
                         PROJECT
                            │
                         BUILDING
                            │
                          LEVEL
                            │
                         BIM MODEL
                            │
       ┌────────────────────┼────────────────────┐
       │                    │                    │
    SPACES             ARCHITECTURE          FURNITURE
       │                    │                    │
    ROOMS          WALL/DOOR/WINDOW       PARAMETRIC UNITS
       │                    │                    │
       └────────────────────┼────────────────────┘
                            │
                      BIM RELATIONSHIPS
                            │
                  ┌─────────┼──────────┐
                  │         │          │
                 2D         3D      SCHEDULES
                  │         │          │
                  └─────────┼──────────┘
                            │
                    QUANTITY ENGINE
                            │
                    ┌───────┴───────┐
                    │               │
                   BOQ             BOM
                    │               │
                    └───────┬───────┘
                            │
                       ENGINEERING
                            │
                      MANUFACTURING
                            │
                 CUTLIST / NESTING / CNC
                            │
                           MES
```

---

# 228. Most Important Implementation Rule

The platform MUST NOT become:

```text
2D Canvas
+
Three.js Viewer
+
Separate Furniture App
+
Separate Manufacturing App
```

Instead it must become:

```text
ONE SHARED SEMANTIC MODEL
```

with multiple representations:

```text
2D
3D
BIM
Elevation
Section
BOM
BOQ
Manufacturing
MES
```

For example:

```text
Wall W123
```

must remain:

```text
W123 in 2D
W123 in BIM
W123 in 3D
W123 in elevation
W123 in section
```

Likewise:

```text
Wardrobe F456
```

must remain:

```text
F456 in 2D
F456 in 3D
F456 in BIM
F456 in BOM
F456 in BOQ
F456 in engineering
F456 in manufacturing
F456 in MES traceability
```

---

# 229. Final Cursor Master Instruction

Treat this document as the **3D/BIM implementation contract**.

Before coding:

1. Analyze the existing codebase.
2. Map current Three.js objects.
3. Map current 2D CAD objects.
4. Identify existing furniture models.
5. Identify material/texture systems.
6. Identify existing BOM/BOQ relationships.
7. Identify manufacturing dependencies.
8. Identify database structures.
9. Identify existing APIs.
10. Identify existing RBAC.
11. Identify existing revision handling.
12. Identify current performance bottlenecks.
13. Produce a migration plan.

Then implement incrementally.

For every new BIM object, Cursor MUST answer:

```text
What is the semantic type?
What parameters define it?
What geometry generates it?
Which level does it belong to?
Which room does it belong to?
Which objects does it host or depend on?
How is it represented in 2D?
How is it represented in 3D?
How is it represented in elevation?
How is it quantified?
What material does it use?
How does it affect BOQ?
How does it affect BOM?
Does it affect manufacturing?
How is it versioned?
How is it validated?
How is it authorized?
How is it audited?
```

If those questions cannot be answered, the BIM feature is not ready for implementation.

---

# 230. Final Product Principle

The final system should behave as:

> **A unified, semantic, parametric interior BIM platform where 2D design, 3D visualization, furniture engineering, quantities, commercial estimation and manufacturing all operate on the same underlying project model.**

The 3D viewport is therefore not the product.

The **BIM model is the product**.

Three.js is the visualization engine.

The semantic model is the source of truth.

And manufacturing/MES consumes that same model through controlled, versioned downstream representations.
