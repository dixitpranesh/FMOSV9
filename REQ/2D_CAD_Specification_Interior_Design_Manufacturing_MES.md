# 2D CAD Specification
## Interior Design, Architectural Planning, Parametric Furniture & Manufacturing Platform

**Document ID:** CAD-2D-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript/ES6 Developers, CAD/Geometry Engineers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**Rendering:** HTML5 Canvas 2D / SVG where appropriate  
**API:** REST `/api/v1`  
**Primary Units:** millimetres (mm)  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete functional, technical and implementation requirements for the platform's 2D CAD engine.

The 2D CAD subsystem is the foundation for:

- Architectural floor planning
- Room planning
- Wall creation
- Doors and windows
- Columns and beams
- Flooring and tiling
- False ceilings
- Electrical points
- Plumbing points
- Furniture placement
- Parametric modular furniture
- Elevations
- Section references
- Dimensions
- Annotations
- Material assignment
- BOQ/BOM generation
- 3D synchronization
- Manufacturing downstream processing

The 2D CAD system MUST NOT be implemented as a simple drawing canvas.

It MUST be implemented as a structured, geometric, data-driven CAD model where every visible object has:

```text
Geometry
+
Identity
+
Semantic Type
+
Parameters
+
Relationships
+
Constraints
+
Materials
+
Layer
+
Metadata
+
Revision History
```

---

# 2. Product Objective

The 2D CAD engine should provide a professional interior-design drafting experience while remaining tightly connected to the parametric furniture, 3D and manufacturing engines.

Target workflow:

```text
Project
   ↓
Building
   ↓
Floor
   ↓
Room
   ↓
2D CAD Workspace
   ↓
Walls / Openings / Structure
   ↓
Furniture / Fixtures
   ↓
Dimensions / Annotations
   ↓
Materials
   ↓
Design Validation
   ↓
3D Synchronization
   ↓
BOQ/BOM
   ↓
Manufacturing
```

---

# 3. Design Principles

The CAD engine MUST follow these principles:

1. Geometry is stored in real-world units.
2. Screen pixels are never the source of truth.
3. All objects have stable IDs.
4. All geometry is deterministic.
5. Objects are editable after creation.
6. Objects support parametric modification.
7. CAD changes must be undoable.
8. CAD changes must be persisted.
9. CAD state must be versionable.
10. CAD objects must synchronize with 3D.
11. CAD objects must be usable by downstream manufacturing.
12. CAD data must not depend on canvas rendering state.
13. Rendering and CAD data model MUST be separated.
14. Large drawings MUST remain performant.
15. Invalid geometry MUST be detected before downstream processing.

---

# 4. Coordinate System

## 4.1 World Coordinates

Use:

```text
X = horizontal axis
Y = vertical axis
```

Origin:

```text
(0,0)
```

Project coordinates are stored in millimetres.

Example:

```text
Room:
width = 4200 mm
length = 3600 mm
```

NOT:

```text
width = 420 pixels
```

---

# 5. Coordinate Transformation

The renderer must support:

```text
World Coordinates
        ↓
Camera Transform
        ↓
Screen Coordinates
```

Required functions:

```javascript
worldToScreen(point)
screenToWorld(point)
```

Camera state:

```text
zoom
panX
panY
rotation
```

Default CAD workspace should support:

```text
zoom in
zoom out
pan
fit to drawing
fit to selection
```

---

# 6. Units

Primary internal unit:

```text
millimetres
```

Supported display units:

```text
mm
cm
m
inch
feet + inch
```

User preferences:

```text
decimal precision
unit system
dimension style
scale
```

All calculations MUST use millimetres internally.

---

# 7. Precision

Minimum internal precision:

```text
0.01 mm
```

Display precision configurable:

```text
0
1
2
3
```

Geometry comparison MUST use configurable tolerance.

Default:

```text
EPSILON = 0.01 mm
```

---

# 8. CAD Object Model

Every CAD object MUST contain:

```json
{
  "id": "uuid",
  "project_id": "uuid",
  "floor_id": "uuid",
  "room_id": "uuid",
  "type": "wall",
  "layer_id": "uuid",
  "geometry": {},
  "parameters": {},
  "style": {},
  "constraints": [],
  "relations": [],
  "material_id": null,
  "metadata": {},
  "created_by": "uuid",
  "updated_by": "uuid",
  "created_at": "",
  "updated_at": "",
  "revision": 1
}
```

---

# 9. Supported CAD Object Types

Initial supported object types:

```text
POINT
LINE
POLYLINE
POLYGON
RECTANGLE
CIRCLE
ARC
ELLIPSE
SPLINE
WALL
WALL_SEGMENT
DOOR
WINDOW
COLUMN
BEAM
STAIR
ROOM
FLOOR_AREA
CEILING
FLOORING
TILE_PATTERN
FURNITURE
FURNITURE_INSTANCE
CABINET
KITCHEN_UNIT
WARDROBE
COUNTERTOP
APPLIANCE
SANITARY
ELECTRICAL_POINT
PLUMBING_POINT
ANNOTATION
TEXT
DIMENSION
LEADER
GRID
AXIS
SECTION_MARKER
ELEVATION_MARKER
REFERENCE
IMAGE
PDF_UNDERLAY
```

---

# 10. CAD Layers

Default layers:

```text
ARCHITECTURE
WALLS
OPENINGS
STRUCTURE
DOORS
WINDOWS
FLOORING
CEILING
FURNITURE
MODULAR_FURNITURE
ELECTRICAL
PLUMBING
DIMENSIONS
ANNOTATIONS
REFERENCE
IMAGES
CONSTRUCTION
MANUFACTURING
HIDDEN
```

Each layer:

```text
id
name
code
color
line_type
line_weight
visible
locked
printable
order
```

---

# 11. Layer Requirements

Users can:

```text
create layer
rename layer
delete layer
reorder layer
hide layer
show layer
lock layer
unlock layer
set current layer
```

Locked layer:

```text
visible = YES
editable = NO
```

Hidden layer:

```text
visible = NO
editable = NO
```

Default protected layers MUST NOT be deleted if system objects depend on them.

---

# 12. CAD Workspace

The workspace MUST contain:

```text
Top Toolbar
Left Tool Palette
Canvas
Right Properties Panel
Bottom Status Bar
Layer Panel
Command/Input Bar
Navigation Controls
```

Suggested structure:

```text
+----------------------------------------------------------+
| File | Edit | View | Draw | Modify | Annotate | Design   |
+------+------------------------------------------+--------+
|      |                                          |        |
| Tool |                                          | Props  |
| Bar  |                 CAD CANVAS                | Panel  |
|      |                                          |        |
|      |                                          |        |
+------+------------------------------------------+--------+
| Layer | X | Y | Length | Angle | Scale | Snap | Zoom      |
+----------------------------------------------------------+
```

---

# 13. Canvas Rendering Architecture

Do NOT place all CAD objects into one uncontrolled canvas drawing routine.

Use separate rendering layers:

```text
Background
Grid
Reference Images
CAD Geometry
Furniture
Selection
Constraints
Dimensions
Annotations
Cursor
Snap Indicators
Temporary Geometry
```

Recommended implementation:

```text
HTML Canvas
+
Offscreen Canvas
+
SVG overlay where appropriate
```

The CAD model MUST remain independent from rendering.

---

# 14. Rendering State vs CAD State

Separate:

```text
CAD Document State
```

from:

```text
Renderer State
```

CAD state:

```text
objects
layers
constraints
materials
relationships
```

Renderer state:

```text
zoom
pan
selection
hover
cursor
temporary tools
```

Renderer state MUST NOT be persisted as project geometry.

---

# 15. Selection System

Support:

```text
single click
multi-select
box select
window select
crossing select
select all
invert selection
select by layer
select by type
```

Selection modes:

```text
WINDOW
CROSSING
```

Window selection:

```text
objects completely inside
```

Crossing:

```text
objects inside or intersecting
```

---

# 16. Selection Metadata

Selected objects MUST be available through:

```javascript
selectionManager.getSelectedObjects()
```

Properties panel MUST update based on selection.

For multi-selection:

```text
common properties
mixed values
bulk edit
```

---

# 17. Snapping System

Required snaps:

```text
ENDPOINT
MIDPOINT
CENTER
INTERSECTION
PERPENDICULAR
TANGENT
NEAREST
GRID
ANGLE
WALL_FACE
WALL_CENTER
DOOR_CENTER
WINDOW_CENTER
ROOM_CORNER
FURNITURE_EDGE
```

Snap priority configurable.

---

# 18. Snap Tolerance

Snap tolerance should be screen-space based.

Example:

```text
8–12 px
```

but transformed into world coordinates based on zoom.

Do not use a fixed world-mm tolerance for interactive snapping.

---

# 19. Grid

Support:

```text
major grid
minor grid
snap grid
```

Configurable spacing:

```text
1 mm
5 mm
10 mm
50 mm
100 mm
500 mm
1000 mm
```

Grid rendering MUST adapt to zoom.

---

# 20. Ortho Mode

Support:

```text
0°
90°
180°
270°
```

When active:

```text
line angle snaps to nearest 90°
```

Keyboard shortcut:

```text
F8
```

or configurable.

---

# 21. Polar Tracking

Support configurable angular increments:

```text
5°
10°
15°
22.5°
30°
45°
90°
```

The user should be able to enter:

```text
length + angle
```

during drawing.

---

# 22. Command Input

CAD tools SHOULD support numeric keyboard input.

Example:

```text
Draw Line
Start: 1000,1000
Length: 4200
Angle: 0°
```

Also support:

```text
@4200<0
```

where practical.

---

# 23. Line Tool

Requirements:

```text
click start
move cursor
preview line
click endpoint
```

Support:

```text
numeric length
angle
ortho
polar
snap
```

Properties:

```text
start_x
start_y
end_x
end_y
length
angle
layer
line_type
line_weight
```

---

# 24. Polyline Tool

Support:

```text
multiple segments
straight segments
closing
open/closed state
```

Actions:

```text
add vertex
remove vertex
move vertex
insert vertex
close
break
```

---

# 25. Rectangle Tool

Parameters:

```text
width
height
rotation
corner_radius
```

Modes:

```text
corner-to-corner
center-to-corner
```

---

# 26. Circle Tool

Modes:

```text
center-radius
center-diameter
three-point
```

Properties:

```text
center
radius
diameter
```

---

# 27. Arc Tool

Support:

```text
3-point arc
center-start-end
start-center-end
start-end-radius
```

---

# 28. Polygon Tool

Support:

```text
regular polygon
free polygon
```

Parameters:

```text
number_of_sides
radius
rotation
```

---

# 29. Spline

Support spline only where required.

Spline MUST have:

```text
control points
degree
closed/open
```

Manufacturing-related geometry SHOULD prefer lines/arcs where possible.

---

# 30. Modify Tools

Required:

```text
MOVE
COPY
ROTATE
MIRROR
SCALE
OFFSET
TRIM
EXTEND
FILLET
CHAMFER
STRETCH
ARRAY
ALIGN
JOIN
BREAK
EXPLODE
GROUP
UNGROUP
```

---

# 31. Move

Support:

```text
base point
destination point
numeric offset
X offset
Y offset
```

---

# 32. Copy

Support:

```text
single copy
multiple copies
array copy
```

---

# 33. Rotate

Support:

```text
base point
angle
copy mode
```

---

# 34. Mirror

Support:

```text
two-point mirror axis
copy/no-copy
```

---

# 35. Offset

Required for:

```text
walls
polylines
rooms
flooring boundaries
ceiling boundaries
```

Example:

```text
wall centerline
→ offset
→ inner face
→ outer face
```

---

# 36. Trim / Extend

Must support intersections.

Example:

```text
wall
+
door opening
=
trimmed wall segments
```

Do not simply erase visual pixels.

The underlying geometry must be updated.

---

# 37. Fillet

Support:

```text
line-line
line-arc
arc-arc
```

Parameters:

```text
radius
trim mode
```

---

# 38. Chamfer

Support:

```text
distance-distance
distance-angle
```

---

# 39. Grouping

Objects may be grouped.

Group:

```text
group_id
child_object_ids
transform
```

Moving a group moves children.

---

# 40. Block / Component System

Support reusable 2D blocks:

```text
door
window
sanitary
electrical
furniture symbol
kitchen appliance
```

Block definition:

```text
block_id
name
version
geometry
anchor
parameters
```

Instances reference the block definition.

---

# 41. Architectural Wall System

Walls are semantic CAD objects.

Wall MUST have:

```text
centerline
thickness
height
base_level
top_level
material
finish_inside
finish_outside
```

Optional:

```text
structural
load_bearing
wall_type
```

---

# 42. Wall Geometry

A wall should not be stored only as a rectangle.

Store:

```text
centerline geometry
+
wall thickness
+
joins
+
openings
```

Generated faces:

```text
inside face
outside face
```

---

# 43. Wall Joins

Support:

```text
L JOIN
T JOIN
CROSS JOIN
MITER JOIN
BUTT JOIN
```

Wall intersections MUST automatically update.

---

# 44. Wall Editing

User can:

```text
move wall
stretch wall
change thickness
change height
split wall
merge wall
add opening
remove opening
change material
```

---

# 45. Door System

Door parameters:

```text
width
height
thickness
wall_id
position
offset
swing_direction
swing_angle
hinge_side
frame_width
```

Door must cut the wall geometry.

---

# 46. Door Types

Support:

```text
single swing
double swing
sliding
pocket
folding
bi-fold
```

---

# 47. Window System

Parameters:

```text
width
height
sill_height
head_height
wall_id
position
frame_depth
```

Window must generate wall opening.

---

# 48. Window Types

Support:

```text
fixed
sliding
casement
awning
bay
corner
```

---

# 49. Room System

A room is a semantic object generated from boundary geometry.

Room contains:

```text
room_id
name
floor_id
boundary
area
perimeter
height
room_type
materials
ceiling
```

---

# 50. Automatic Room Detection

System SHOULD detect enclosed wall regions.

Process:

```text
wall network
 ↓
intersection graph
 ↓
closed loops
 ↓
candidate rooms
 ↓
room validation
```

User must be able to manually create or correct rooms.

---

# 51. Room Properties

Calculate:

```text
area
perimeter
wall length
ceiling area
floor area
```

Optional:

```text
wall finish area
skirting length
false ceiling area
```

---

# 52. Room Naming

Default:

```text
Room 01
Room 02
```

User can rename:

```text
Living Room
Master Bedroom
Kitchen
Utility
Toilet
```

---

# 53. Floor Plan Hierarchy

```text
Project
 └── Building
      └── Floor
           ├── Room
           │    ├── Walls
           │    ├── Doors
           │    ├── Windows
           │    ├── Furniture
           │    └── Fixtures
           └── Shared Objects
```

---

# 54. Structural Objects

Support:

```text
column
beam
slab boundary
stair
shaft
```

Column parameters:

```text
shape
width
depth
diameter
rotation
height
```

Supported shapes:

```text
rectangular
square
circular
custom polygon
```

---

# 55. Furniture Placement

Furniture MUST be semantic objects.

Example:

```json
{
  "type": "furniture_instance",
  "template_id": "wardrobe-01",
  "x": 2500,
  "y": 1200,
  "rotation": 0,
  "width": 1800,
  "depth": 600,
  "height": 2400
}
```

---

# 56. Parametric Furniture in 2D

Furniture instance parameters MAY include:

```text
width
depth
height
carcass_thickness
shutter_thickness
toe_kick
shelf_count
drawer_count
partition_count
```

Changing a parameter MUST regenerate the 2D representation.

---

# 57. Furniture Anchor System

Every furniture object MUST have an anchor:

```text
CENTER
LEFT
RIGHT
TOP
BOTTOM
CUSTOM
```

Support wall-aware placement.

Example:

```text
wardrobe
→ select wall
→ align back edge
→ offset 50 mm
```

---

# 58. Collision Detection

The CAD engine SHOULD detect:

```text
furniture-furniture overlap
furniture-wall overlap
door-furniture conflict
window-furniture conflict
circulation conflict
```

Severity:

```text
INFO
WARNING
ERROR
```

---

# 59. Clearance Rules

Configurable clearances:

```text
door clearance
walkway clearance
cabinet clearance
appliance clearance
window clearance
service clearance
```

Example:

```text
minimum walkway = 900 mm
```

Values MUST be configurable by project/tenant.

---

# 60. Kitchen Planning

Support semantic kitchen objects:

```text
base cabinet
wall cabinet
tall unit
corner unit
sink unit
hob unit
oven unit
refrigerator
dishwasher
countertop
```

2D placement must connect to parametric furniture templates.

---

# 61. Wardrobe Planning

Support:

```text
wardrobe carcass
shutter
drawer
shelf
hanging section
loft
side panel
back panel
```

2D view should represent:

```text
plan
front
internal elevation
```

where applicable.

---

# 62. Flooring

Flooring area must be represented as a semantic object.

Parameters:

```text
material
pattern
tile_width
tile_height
joint_width
rotation
offset_x
offset_y
```

---

# 63. Tiling Patterns

Support:

```text
grid
brick
running bond
stack bond
herringbone
diagonal
custom
```

The tile pattern must be clipped to room/floor boundary.

---

# 64. False Ceiling

Support:

```text
ceiling boundary
levels
drops
bulkheads
coves
profiles
lights
```

Each ceiling object MUST contain elevation information.

---

# 65. Electrical Objects

Support:

```text
switch
socket
light
fan
AC point
data point
TV point
```

Properties:

```text
height
type
circuit
symbol
orientation
```

---

# 66. Plumbing Objects

Support:

```text
water point
drain
sink
basin
shower
toilet
geyser
```

---

# 67. Dimensions

Required dimension types:

```text
linear
aligned
horizontal
vertical
angular
radius
diameter
arc length
```

---

# 68. Associative Dimensions

Dimensions MUST reference geometry.

Example:

```text
dimension
 ↓
wall edge A
 ↓
wall edge B
```

If wall moves:

```text
dimension updates automatically
```

Do not store only static text.

---

# 69. Dimension Properties

```text
id
type
references
offset
text_position
text_override
precision
unit
style
```

Manual text override MUST be flagged:

```text
is_override = true
```

---

# 70. Dimension Styles

Configurable:

```text
arrow type
arrow size
text size
text font
extension line
dimension line
precision
unit
color
```

Tenant/project-level styles SHOULD be supported.

---

# 71. Text

Support:

```text
single-line
multi-line
rich text
```

Properties:

```text
position
rotation
font
size
alignment
color
width
```

---

# 72. Annotation

Support:

```text
leader
callout
note
room label
material tag
detail marker
section marker
elevation marker
```

---

# 73. Drawing Scale

Support standard scales:

```text
1:1
1:5
1:10
1:20
1:25
1:50
1:100
1:200
```

Scale affects presentation/printing, not stored geometry.

---

# 74. Paper Space / Print Layout

The system SHOULD support layouts:

```text
A4
A3
A2
A1
A0
custom
```

Orientation:

```text
portrait
landscape
```

Title block:

```text
project
client
drawing name
drawing number
revision
date
designer
company
scale
```

---

# 75. Viewports

A print layout MAY contain multiple viewports:

```text
floor plan
furniture plan
ceiling plan
electrical plan
dimension plan
```

Each viewport has:

```text
camera
scale
layer visibility
crop
```

---

# 76. Reference Images

Support image underlay:

```text
PNG
JPG
WEBP
```

Properties:

```text
position
scale
rotation
opacity
locked
```

---

# 77. PDF Underlay

Support PDF pages as reference.

Required:

```text
upload
page selection
scale
position
rotation
opacity
lock
hide
```

PDF underlay MUST NOT be treated as editable CAD geometry unless processed by an AI/vectorization workflow.

---

# 78. Image-to-2D / AI Integration

The CAD system SHOULD expose an integration point:

```text
AI Floorplan Analysis
```

Input:

```text
image/PDF
```

Output:

```text
walls
doors
windows
rooms
dimensions
structural objects
```

AI-generated geometry MUST be marked:

```text
source = AI
confidence
review_status = PENDING
```

AI output MUST require human validation before being considered authoritative.

---

# 79. Geometry Validation

Validation engine MUST detect:

```text
open wall joins
self-intersections
zero-length lines
duplicate geometry
invalid polygons
overlapping walls
unclosed rooms
invalid door placement
invalid window placement
furniture outside room
```

---

# 80. Validation Severity

```text
INFO
WARNING
ERROR
BLOCKER
```

Manufacturing generation MUST be blocked by relevant BLOCKER errors.

---

# 81. Constraints

Initial constraints:

```text
horizontal
vertical
parallel
perpendicular
collinear
coincident
equal_length
fixed_length
fixed_angle
distance
radius
```

---

# 82. Constraint Architecture

Constraint:

```json
{
  "id": "uuid",
  "type": "distance",
  "entities": ["A", "B"],
  "value": 900,
  "driving": true,
  "status": "valid"
}
```

---

# 83. Parametric Constraints

A driving dimension can modify geometry.

Example:

```text
wall length = 4200
```

Change:

```text
4200 → 4500
```

Geometry updates.

---

# 84. Constraint Conflicts

When constraints conflict:

```text
constraint status = CONFLICT
```

The system MUST NOT silently corrupt geometry.

Display:

```text
constraint conflict
affected objects
resolution options
```

---

# 85. Dependency Graph

CAD relationships should be represented as a graph.

Example:

```text
Wall
 ↓
Door Opening
 ↓
Room Boundary
 ↓
Floor Area
 ↓
Flooring
 ↓
BOQ
```

Furniture:

```text
Furniture Template
 ↓
Furniture Instance
 ↓
Panel Geometry
 ↓
BOM
 ↓
Cutlist
```

---

# 86. Associativity

If a referenced object changes:

```text
dependent objects become:
CURRENT
or
STALE
```

Examples:

```text
wall changed
→ room boundary stale

furniture dimensions changed
→ BOM stale

furniture geometry changed
→ cutlist stale
```

---

# 87. Revision System

CAD documents MUST support revisions.

Revision:

```text
revision_number
created_by
created_at
change_summary
parent_revision_id
```

---

# 88. Autosave

Autosave SHOULD occur:

```text
every 5–15 seconds
```

and/or after meaningful commands.

Autosave MUST use incremental changes where practical.

---

# 89. Undo/Redo

Every user command MUST be undoable where applicable.

Use command pattern:

```javascript
command.execute()
command.undo()
```

History:

```text
undoStack
redoStack
```

---

# 90. CAD Command Architecture

Recommended:

```text
CommandManager
ToolManager
SelectionManager
SnapManager
ConstraintManager
GeometryEngine
HistoryManager
DocumentManager
Renderer
```

---

# 91. Tool Architecture

Each tool SHOULD implement:

```javascript
activate()
deactivate()
onMouseDown()
onMouseMove()
onMouseUp()
onKeyDown()
cancel()
commit()
```

Example:

```text
LineTool
WallTool
DoorTool
WindowTool
MoveTool
RotateTool
DimensionTool
```

---

# 92. Geometry Engine

Do not put geometry calculations directly into UI event handlers.

Create:

```text
GeometryService
```

Responsibilities:

```text
distance
angle
intersection
projection
offset
trim
extend
fillet
chamfer
polygon area
polygon perimeter
point-in-polygon
segment intersection
bounding box
transform
```

---

# 93. Bounding Boxes

Every CAD object MUST expose:

```javascript
getBoundingBox()
```

Used for:

```text
selection
zoom extents
collision
spatial indexing
render optimization
```

---

# 94. Spatial Index

For large drawings, implement spatial indexing.

Recommended:

```text
R-tree
or
quadtree
```

Used for:

```text
hit testing
selection
snapping
collision detection
visibility
```

---

# 95. Hit Testing

Mouse selection MUST use geometry-aware hit testing.

Examples:

```text
line distance from cursor
polygon containment
circle radius
wall face
dimension text
```

Do not use only bounding boxes for final selection.

---

# 96. Performance Requirements

Target:

```text
5,000 objects:
smooth editing

10,000 objects:
usable interactive performance

25,000+ objects:
progressive rendering / spatial indexing
```

Avoid re-rendering the entire document for every mouse movement.

---

# 97. Rendering Optimization

Use:

```text
dirty rectangles
layer caching
offscreen rendering
spatial culling
requestAnimationFrame
```

Selection overlays MAY be rendered separately.

---

# 98. Persistence Model

Do not store the CAD document as a single opaque canvas screenshot.

Persist structured objects.

Recommended:

```text
cad_documents
cad_layers
cad_objects
cad_object_vertices
cad_constraints
cad_relations
cad_dimensions
cad_snapshots
cad_commands
```

---

# 99. CAD Document Table

Minimum:

```text
id
tenant_id
project_id
floor_id
room_id
name
unit
version
status
created_by
updated_by
created_at
updated_at
```

---

# 100. CAD Object Table

Minimum:

```text
id
tenant_id
cad_document_id
layer_id
object_type
parent_id
geometry_type
geometry_json
parameters_json
style_json
metadata_json
version
status
created_by
updated_by
created_at
updated_at
```

---

# 101. Vertex Storage

For performance, frequently edited geometry MAY use normalized vertex tables.

```text
cad_object_vertices
```

Fields:

```text
id
object_id
sequence
x
y
bulge
```

Use `bulge` where required for arc segments in polylines.

---

# 102. JSON vs Relational Geometry

Recommended:

Use relational fields for:

```text
identity
tenant
project
layer
type
version
status
```

Use JSON for:

```text
complex geometry
parameters
style
metadata
```

Use normalized tables where:

```text
query frequency
large vertex counts
spatial processing
```

justify it.

---

# 103. API Requirements

Required endpoints:

```http
GET    /api/v1/cad/documents
POST   /api/v1/cad/documents
GET    /api/v1/cad/documents/{id}
PATCH  /api/v1/cad/documents/{id}
DELETE /api/v1/cad/documents/{id}
```

---

# 104. CAD Object APIs

```http
GET    /cad/documents/{id}/objects
POST   /cad/documents/{id}/objects
GET    /cad/objects/{id}
PATCH  /cad/objects/{id}
DELETE /cad/objects/{id}
```

---

# 105. Bulk CAD Operations

CAD clients MUST support efficient batch updates.

```http
POST /api/v1/cad/documents/{id}/commands
```

Example:

```json
{
  "commands": [
    {
      "type": "MOVE",
      "object_ids": ["A", "B"],
      "delta": {
        "x": 100,
        "y": 0
      }
    }
  ]
}
```

---

# 106. Save API

```http
POST /api/v1/cad/documents/{id}/save
```

Request:

```json
{
  "base_version": 42,
  "commands": [],
  "client_session_id": "uuid"
}
```

---

# 107. Optimistic Concurrency

If:

```text
client base_version != server version
```

return:

```http
409 Conflict
```

Response:

```json
{
  "error": {
    "code": "CAD_VERSION_CONFLICT",
    "server_version": 43,
    "client_version": 42
  }
}
```

---

# 108. CAD Snapshot API

```http
POST /cad/documents/{id}/snapshots
GET  /cad/documents/{id}/snapshots
GET  /cad/documents/{id}/snapshots/{snapshotId}
POST /cad/documents/{id}/snapshots/{snapshotId}/restore
```

---

# 109. Validation API

```http
POST /cad/documents/{id}/validate
GET  /cad/documents/{id}/validation-results
```

Response categories:

```text
geometry
constraints
architecture
clearance
manufacturing
associativity
```

---

# 110. Export APIs

Support:

```http
GET /cad/documents/{id}/export/dxf
GET /cad/documents/{id}/export/svg
GET /cad/documents/{id}/export/pdf
GET /cad/documents/{id}/export/json
```

Optional:

```text
DWG
```

should be treated as a separate integration due to proprietary format requirements.

---

# 111. DXF Export

Minimum entities:

```text
LINE
LWPOLYLINE
CIRCLE
ARC
TEXT
MTEXT
DIMENSION
INSERT
```

Layers MUST map to CAD layers.

---

# 112. SVG Export

SVG export MUST preserve:

```text
scale
line weights
layers
text
dimensions
```

---

# 113. PDF Export

PDF export MUST support:

```text
paper size
orientation
scale
title block
line weights
layer visibility
```

---

# 114. Printing

Print preview MUST display:

```text
actual page boundary
drawing scale
margins
title block
clipping
```

---

# 115. Import Requirements

Support:

```text
SVG
DXF
PDF underlay
PNG
JPG
WEBP
```

DXF import MUST map supported entities into the internal geometry model.

Unsupported entities MUST be reported, not silently discarded.

---

# 116. Import Validation

After import:

```text
Imported Objects
Unsupported Objects
Invalid Geometry
Unit Detected
Scale
Layer Mapping
Warnings
```

User must confirm before committing imported geometry.

---

# 117. DXF Unit Handling

Detect:

```text
mm
cm
m
inch
feet
```

If ambiguous:

```text
ask user to confirm
```

Never silently assume incorrect scale.

---

# 118. Selection Properties Panel

When one object is selected, show:

```text
Object type
Layer
Position
Dimensions
Rotation
Material
Parameters
Constraints
Metadata
```

When multiple objects selected:

```text
count
common layer
common properties
bulk transform
```

---

# 119. Object Property Editing

Changing a property through the panel MUST use the same command/history system as canvas manipulation.

Example:

```text
Width 4200 → 4500
```

must create an undoable command.

---

# 120. Keyboard Shortcuts

Default:

```text
Esc        Cancel
Delete     Delete
Ctrl+Z     Undo
Ctrl+Y     Redo
Ctrl+C     Copy
Ctrl+V     Paste
Ctrl+X     Cut
Ctrl+A     Select All
Ctrl+S     Save
F8         Ortho
F3         Snap
F7         Grid
```

Allow customization later.

---

# 121. Mouse Controls

Default:

```text
Left click = select/confirm
Middle drag = pan
Wheel = zoom
Right click = context menu / cancel
```

---

# 122. Context Menu

For selected objects:

```text
Move
Copy
Rotate
Mirror
Offset
Trim
Properties
Layer
Material
Group
Hide
Lock
Delete
```

Object-specific actions MUST appear contextually.

---

# 123. Right-Side Properties

The property panel MUST be data-driven.

Avoid writing a separate hard-coded form for every object.

Recommended:

```javascript
PropertySchemaRegistry
```

Example:

```json
{
  "wall": [
    "thickness",
    "height",
    "material",
    "finish_inside",
    "finish_outside"
  ]
}
```

---

# 124. Material Association

CAD objects can reference:

```text
material_id
```

Materials include:

```text
board
laminate
tile
paint
stone
glass
metal
wood
```

Changing material should update:

```text
2D appearance
3D material
BOQ
pricing
```

where applicable.

---

# 125. Color Coding

Use configurable visual categories.

Example:

```text
walls
doors
windows
furniture
electrical
plumbing
dimensions
```

Tenant can customize colors.

---

# 126. Line Types

Support:

```text
CONTINUOUS
DASHED
CENTER
HIDDEN
PHANTOM
DOT
```

---

# 127. Line Weight

Support configurable line weights:

```text
0.05
0.10
0.15
0.20
0.25
0.35
0.50
0.70
1.00 mm
```

---

# 128. Design Modes

Provide predefined views:

```text
Architectural Plan
Furniture Plan
Electrical Plan
Plumbing Plan
Ceiling Plan
Flooring Plan
Dimension Plan
Manufacturing Plan
Presentation Plan
```

Each mode controls:

```text
layer visibility
object visibility
annotation style
```

---

# 129. View Filters

Support:

```text
show all
architecture
furniture
MEP
manufacturing
dimensions
annotations
```

---

# 130. Room-Based Editing

User can double-click a room.

System enters:

```text
ROOM EDIT MODE
```

Only relevant room objects become editable/highlighted.

---

# 131. Wall-Based Editing

Selecting a wall should show:

```text
length
thickness
height
material
inside finish
outside finish
connected walls
openings
```

---

# 132. Door-Based Editing

Selecting door:

```text
width
height
frame
swing
hinge
offset
wall
```

---

# 133. Window-Based Editing

Selecting window:

```text
width
height
sill
head
frame
wall
offset
```

---

# 134. Furniture-Based Editing

Selecting furniture:

```text
template
width
depth
height
rotation
material
parameters
anchor
wall alignment
```

---

# 135. Elevation Generation

The 2D engine SHOULD generate elevations from semantic objects.

Example:

```text
Floor plan
 ↓
Select wall
 ↓
Generate elevation
 ↓
Doors/windows/furniture projected
```

Elevation should be associative where possible.

---

# 136. Section Generation

Support section markers.

Section can reference:

```text
cut plane
direction
depth
view scale
```

The section engine may initially provide a simplified 2D projection and later integrate deeply with 3D.

---

# 137. 2D ↔ 3D Synchronization

2D remains the authoritative architectural layout for plan-level geometry.

3D references:

```text
wall
door
window
room
floor
ceiling
furniture
```

Changes in 2D:

```text
update 3D
```

Changes in 3D:

```text
update corresponding semantic parameters
```

---

# 138. Synchronization Conflicts

If both views have changed the same object:

```text
SYNC_CONFLICT
```

Display:

```text
2D version
3D version
last modified by
timestamp
```

User chooses:

```text
keep 2D
keep 3D
merge
```

---

# 139. Manufacturing Dependency

2D furniture changes MUST invalidate downstream artifacts where applicable.

Example:

```text
Furniture width changed
 ↓
Furniture geometry changed
 ↓
BOM stale
 ↓
Cutlist stale
 ↓
Nesting stale
 ↓
CNC stale
```

System MUST clearly display:

```text
STALE
```

status.

---

# 140. CAD Status Model

Document:

```text
DRAFT
IN_REVIEW
APPROVED
LOCKED
ARCHIVED
```

Object:

```text
ACTIVE
HIDDEN
LOCKED
DELETED
SUPERSEDED
```

---

# 141. Locking

Objects can be locked.

Locked objects:

```text
selectable = YES
editable = NO
```

Optional:

```text
selectable = NO
```

---

# 142. Soft Delete

CAD objects MUST use soft deletion where revision history is important.

Example:

```text
status = DELETED
deleted_at
deleted_by
```

Do not physically delete objects that are referenced by historical revisions.

---

# 143. Audit Trail

Record:

```text
object created
object updated
object deleted
object restored
layer changed
material changed
constraint changed
revision created
snapshot restored
import performed
export performed
```

---

# 144. CAD Event Model

Events SHOULD include:

```text
CAD_OBJECT_CREATED
CAD_OBJECT_UPDATED
CAD_OBJECT_DELETED
CAD_OBJECT_MOVED
CAD_OBJECT_RESIZED
CAD_WALL_CHANGED
CAD_OPENING_CHANGED
CAD_FURNITURE_CHANGED
CAD_CONSTRAINT_CHANGED
CAD_REVISION_CREATED
CAD_VALIDATION_COMPLETED
CAD_EXPORT_CREATED
```

These events may drive downstream services.

---

# 145. Event-Driven Downstream Processing

Example:

```text
CAD_FURNITURE_CHANGED
        ↓
Parametric Engine
        ↓
BOM Regeneration
        ↓
Pricing Recalculation
        ↓
Manufacturing Invalidation
```

Do not make the CAD canvas directly generate CNC files.

---

# 146. Manufacturing Plan View

Provide a specialized 2D view for:

```text
panel layouts
cutting plans
drilling locations
hardware positions
edge bands
```

This should be generated from manufacturing geometry rather than manually drawn.

---

# 147. Panel Geometry

Panel objects may contain:

```text
width
height
thickness
grain_direction
edge_band_left
edge_band_right
edge_band_top
edge_band_bottom
drill_points
grooves
cutouts
```

This is a downstream manufacturing object, not ordinary architectural geometry.

---

# 148. Furniture Engineering View

Provide a view showing:

```text
carcass
partitions
shelves
shutters
drawers
hardware
panel labels
dimensions
```

This view can use the same 2D engine but a different semantic layer configuration.

---

# 149. CAD JSON Export

Export must contain:

```text
document metadata
layers
objects
geometry
parameters
constraints
relations
materials
dimensions
annotations
revision
```

It MUST be possible to reconstruct the document from exported JSON.

---

# 150. Import/Export Round Trip

Acceptance:

```text
Create CAD
 ↓
Export JSON
 ↓
Delete local copy
 ↓
Import JSON
 ↓
Geometry matches
 ↓
Relationships match
 ↓
Dimensions match
 ↓
Materials match
```

Tolerance:

```text
≤ 0.01 mm
```

for numerical geometry where practical.

---

# 151. Database Indexing

Required indexes:

```text
tenant_id
project_id
floor_id
room_id
cad_document_id
layer_id
object_type
parent_id
status
updated_at
```

Spatial data may later use MySQL spatial indexes if appropriate.

---

# 152. MySQL Spatial Consideration

Where supported and beneficial, use:

```text
POINT
LINESTRING
POLYGON
```

for spatial querying.

However, application-level CAD geometry remains authoritative because CAD objects require:

```text
arcs
bulges
constraints
parametric metadata
```

---

# 153. Security

CAD APIs MUST enforce:

```text
authentication
tenant isolation
RBAC
project access
resource authorization
file access authorization
```

Uploaded reference files MUST NOT be publicly accessible by predictable URLs.

---

# 154. File Security

For images/PDF/DXF:

```text
validate MIME type
validate file extension
validate size
virus scan where available
store outside public web root
generate secure download URLs
```

---

# 155. CAD Autosave Security

Autosave requests MUST verify:

```text
user
tenant
document
project membership
document version
```

Never accept arbitrary:

```text
tenant_id
user_id
project_id
```

from the browser as authoritative.

---

# 156. API Validation

Every CAD API request MUST validate:

```text
object type
geometry structure
numeric values
tenant
project
layer
references
constraints
```

Reject:

```text
NaN
Infinity
invalid JSON
negative dimensions where invalid
zero-size geometry where prohibited
unknown object types
invalid references
```

---

# 157. Large Document Loading

For large projects:

```text
load document metadata
 ↓
load visible layers
 ↓
load objects within viewport
 ↓
lazy-load remaining objects
```

Optional:

```text
LOD
```

for complex objects.

---

# 158. Collaboration Readiness

The architecture SHOULD support future collaborative editing.

Prepare for:

```text
document version
command IDs
client session IDs
user IDs
timestamps
conflict detection
```

Real-time collaboration via WebSocket can be added later.

---

# 159. Offline / Network Resilience

CAD editor SHOULD maintain a local command queue.

If connection drops:

```text
continue editing
queue commands
sync when online
```

Conflicts MUST be detected rather than silently overwriting server state.

---

# 160. Telemetry

Measure:

```text
CAD load time
render time
save time
object count
command latency
validation time
export time
memory usage
```

Do not collect sensitive project content unnecessarily.

---

# 161. Error Handling

Errors should be categorized:

```text
CAD_GEOMETRY_ERROR
CAD_CONSTRAINT_ERROR
CAD_VERSION_CONFLICT
CAD_PERMISSION_DENIED
CAD_INVALID_OBJECT
CAD_IMPORT_ERROR
CAD_EXPORT_ERROR
CAD_SYNC_ERROR
CAD_VALIDATION_ERROR
```

---

# 162. User-Friendly Error Messages

Example:

Bad:

```text
Constraint solver exception.
```

Better:

```text
This wall cannot be moved to 4500 mm because it conflicts
with a fixed 3000 mm room boundary.
```

---

# 163. CAD Toolbar Requirements

Toolbar categories:

```text
Select
Draw
Architecture
Furniture
Modify
Annotate
Measure
Constraints
Layers
View
Import
Export
```

---

# 164. Draw Tools

Minimum:

```text
Line
Polyline
Rectangle
Circle
Arc
Polygon
Spline
Point
```

---

# 165. Architecture Tools

Minimum:

```text
Wall
Door
Window
Room
Column
Beam
Stair
Opening
Floor Area
Ceiling
```

---

# 166. Furniture Tools

Minimum:

```text
Furniture Library
Place Furniture
Cabinet
Kitchen Unit
Wardrobe
Countertop
Appliance
Fixture
```

---

# 167. Modify Tools

Minimum:

```text
Move
Copy
Rotate
Mirror
Scale
Offset
Trim
Extend
Fillet
Chamfer
Stretch
Array
Align
Join
Break
Explode
```

---

# 168. Annotation Tools

Minimum:

```text
Dimension
Text
Leader
Room Tag
Material Tag
Section
Elevation
Detail Marker
```

---

# 169. Measure Tools

Support:

```text
distance
area
perimeter
angle
radius
```

Measurements can be temporary or persistent annotations.

---

# 170. CAD Search

Support searching:

```text
object ID
room name
furniture name
material
layer
type
```

Example:

```text
Search: "Kitchen"
```

returns:

```text
Kitchen Room
Kitchen Cabinets
Kitchen Countertop
Kitchen Appliances
```

---

# 171. Object Tree

Provide a CAD object tree:

```text
Floor
 ├── Rooms
 │    ├── Living Room
 │    └── Kitchen
 ├── Walls
 ├── Doors
 ├── Windows
 ├── Furniture
 └── Annotations
```

Selecting an item in the tree selects it on canvas.

---

# 172. Properties Synchronization

Canvas selection:

```text
→ properties panel updates
```

Properties panel update:

```text
→ CAD model changes
→ renderer updates
→ history command created
→ downstream dependency status evaluated
```

---

# 173. Multi-Selection Transform

Support:

```text
move
rotate
scale
align
distribute
```

Alignment:

```text
left
center
right
top
middle
bottom
```

Distribution:

```text
horizontal
vertical
```

---

# 174. Copy/Paste

Clipboard should store structured CAD data:

```json
{
  "source_document": "uuid",
  "objects": [],
  "layers": [],
  "materials": [],
  "dependencies": []
}
```

Pasting must generate new object IDs.

---

# 175. External Clipboard

Do not trust pasted external JSON.

Validate:

```text
schema
geometry
references
```

---

# 176. Template Library

Users can save selected geometry as reusable 2D templates.

Examples:

```text
standard bathroom
kitchen layout
wardrobe elevation
TV unit
bedroom furniture group
```

---

# 177. Template Versioning

Templates MUST support:

```text
version
status
published_by
published_at
```

Changing a template MUST NOT silently alter existing project instances.

---

# 178. Parametric Template Instances

Existing instances reference:

```text
template_id
template_version
parameters
```

Updating template version should require explicit migration.

---

# 179. Design Validation Dashboard

Display:

```text
Geometry Errors
Constraint Errors
Clearance Warnings
Missing Materials
Unclosed Rooms
Invalid Openings
Stale Dependencies
```

Each result should link to the relevant object.

---

# 180. Validation Navigation

Clicking an error:

```text
select object
zoom to object
open properties
highlight issue
```

---

# 181. CAD Workflow States

Recommended:

```text
DRAFT
DESIGNING
IN_REVIEW
CHANGES_REQUIRED
APPROVED
LOCKED
SUPERSEDED
ARCHIVED
```

---

# 182. Approval Rules

A CAD document can enter:

```text
APPROVED
```

only if:

```text
no BLOCKER validation errors
required dimensions complete
required materials assigned
room boundaries valid
```

Tenant may configure additional requirements.

---

# 183. Lock Rules

When approved:

```text
editing = restricted
```

To modify:

```text
create new revision
```

This preserves historical design intent.

---

# 184. Revision Comparison

Support:

```text
revision A
vs
revision B
```

Show:

```text
added objects
removed objects
moved objects
resized objects
material changes
parameter changes
```

Visual diff:

```text
added
removed
modified
```

---

# 185. Design Freeze

A project may freeze:

```text
architectural plan
furniture plan
materials
```

After freeze:

```text
changes require new revision
```

---

# 186. Dependency Status

Every downstream artifact SHOULD expose:

```text
CURRENT
STALE
INVALID
NOT_GENERATED
```

Example:

```text
CAD = CURRENT
BOM = CURRENT
BOQ = CURRENT
CUTLIST = STALE
NESTING = STALE
CNC = STALE
```

---

# 187. Performance Acceptance Criteria

Target:

```text
initial CAD load < 3 sec
for standard project up to 5,000 objects
```

Interactive:

```text
pan/zoom target ≥ 45 FPS
```

on supported desktop hardware.

Saving:

```text
normal incremental save < 1 sec
```

Large saves MAY be asynchronous.

---

# 188. Geometry Accuracy Acceptance Criteria

For standard geometry:

```text
line endpoint accuracy ≤ 0.01 mm
dimension calculation accuracy ≤ 0.01 mm
area calculation tolerance ≤ 0.01%
```

---

# 189. Wall Acceptance Tests

```text
[ ] create wall
[ ] specify thickness
[ ] specify height
[ ] join wall
[ ] split wall
[ ] move wall
[ ] stretch wall
[ ] add door
[ ] add window
[ ] change material
[ ] room recalculates
[ ] 3D updates
```

---

# 190. Furniture Acceptance Tests

```text
[ ] place furniture
[ ] rotate
[ ] resize
[ ] change parameters
[ ] align to wall
[ ] detect collision
[ ] generate BOM
[ ] invalidate manufacturing
[ ] synchronize to 3D
```

---

# 191. Dimension Acceptance Tests

```text
[ ] create dimension
[ ] associate with wall
[ ] move wall
[ ] dimension updates
[ ] change units
[ ] change precision
[ ] override text
[ ] restore calculated value
```

---

# 192. Undo/Redo Acceptance Tests

```text
[ ] create object
[ ] undo
[ ] redo
[ ] modify property
[ ] undo
[ ] redo
[ ] multi-object operation
[ ] undo entire command
```

---

# 193. Save/Load Acceptance Tests

```text
[ ] create drawing
[ ] save
[ ] reload
[ ] geometry identical
[ ] layers identical
[ ] dimensions identical
[ ] constraints identical
[ ] materials identical
[ ] furniture parameters identical
```

---

# 194. Concurrency Acceptance Tests

```text
User A loads revision 10
User B saves revision 11
User A attempts save
→ 409 conflict
```

No silent overwrite.

---

# 195. Tenant Isolation Acceptance Tests

```text
Tenant A CAD document
→ cannot be accessed by Tenant B

Tenant A reference image
→ cannot be downloaded by Tenant B

Tenant A furniture template
→ cannot be modified by Tenant B
```

---

# 196. Manufacturing Integration Acceptance

Example:

```text
Create wardrobe
 ↓
Change width 1800 → 2000
 ↓
2D updates
 ↓
3D updates
 ↓
BOM becomes stale
 ↓
Cutlist becomes stale
 ↓
Nesting becomes stale
 ↓
CNC becomes stale
```

After regeneration:

```text
BOM CURRENT
CUTLIST CURRENT
NESTING CURRENT
CNC CURRENT
```

---

# 197. Cursor Implementation Sequence

Cursor MUST implement in this order:

## Phase 1 — Foundation

```text
CAD document model
Coordinate system
Canvas
Camera
Layers
Selection
Basic rendering
Persistence
```

## Phase 2 — Basic Geometry

```text
Line
Polyline
Rectangle
Circle
Arc
Polygon
Move
Copy
Rotate
Mirror
Delete
Undo/Redo
```

## Phase 3 — CAD Interaction

```text
Snap
Grid
Ortho
Polar
Numeric input
Hit testing
Spatial indexing
```

## Phase 4 — Architecture

```text
Walls
Wall joins
Rooms
Doors
Windows
Columns
Beams
Stairs
```

## Phase 5 — Annotation

```text
Dimensions
Text
Leaders
Tags
Sections
Elevations
```

## Phase 6 — Furniture

```text
Furniture library
Furniture placement
Parametric furniture
Kitchen
Wardrobe
Cabinets
Appliances
```

## Phase 7 — Constraints

```text
constraints
dependency graph
parametric updates
validation
```

## Phase 8 — Import/Export

```text
DXF
SVG
PDF
images
JSON
```

## Phase 9 — 3D Integration

```text
2D → 3D synchronization
3D → 2D parameter updates
conflict handling
```

## Phase 10 — Manufacturing

```text
engineering views
panel geometry
BOM dependencies
cutlist dependencies
manufacturing invalidation
```

---

# 198. Cursor Pre-Implementation Analysis

Before implementing, Cursor MUST inspect the existing codebase and produce:

```text
CURRENT CAD ARCHITECTURE
CURRENT CANVAS IMPLEMENTATION
CURRENT GEOMETRY MODEL
CURRENT OBJECT MODEL
CURRENT DATABASE TABLES
CURRENT APIs
CURRENT RENDERING PIPELINE
CURRENT 3D IMPLEMENTATION
CURRENT FURNITURE IMPLEMENTATION
CURRENT MATERIAL IMPLEMENTATION
CURRENT UNDO/REDO
CURRENT SAVE/AUTOSAVE
CURRENT TEST COVERAGE
```

Then identify:

```text
GAPS
DUPLICATES
TECHNICAL DEBT
PERFORMANCE RISKS
DATA MODEL RISKS
GEOMETRY RISKS
SECURITY RISKS
3D SYNC RISKS
MANUFACTURING DEPENDENCY RISKS
```

Do NOT rewrite the application blindly.

---

# 199. Cursor Architecture Rules

Use this conceptual separation:

```text
UI
 ↓
Tool Controller
 ↓
Command Manager
 ↓
CAD Domain Model
 ↓
Geometry Engine
 ↓
Constraint Engine
 ↓
Dependency Engine
 ↓
Persistence
 ↓
API
```

Rendering:

```text
CAD Domain Model
 ↓
Render State
 ↓
Canvas/SVG Renderer
```

---

# 200. Recommended JavaScript Modules

```text
/src/cad/

document/
  CadDocument.js
  CadObject.js
  CadLayer.js
  CadRevision.js

geometry/
  Point.js
  Line.js
  Polyline.js
  Circle.js
  Arc.js
  Polygon.js
  GeometryService.js
  IntersectionService.js
  TransformService.js

tools/
  SelectTool.js
  LineTool.js
  WallTool.js
  DoorTool.js
  WindowTool.js
  FurnitureTool.js
  MoveTool.js
  RotateTool.js
  DimensionTool.js

interaction/
  SelectionManager.js
  SnapManager.js
  InputManager.js
  Camera.js

constraints/
  ConstraintManager.js
  ConstraintSolver.js

history/
  CommandManager.js
  commands/

rendering/
  CadRenderer.js
  GridRenderer.js
  SelectionRenderer.js
  DimensionRenderer.js

validation/
  CadValidator.js
  ClearanceValidator.js
  GeometryValidator.js

sync/
  CadSyncService.js
  DependencyManager.js

io/
  DxfImporter.js
  DxfExporter.js
  SvgExporter.js
  PdfExporter.js
```

---

# 201. PHP Backend Structure

Recommended:

```text
src/
  CAD/
    Domain/
    Services/
    Geometry/
    Validation/
    Repositories/
    Policies/
    DTO/
    Commands/
    Events/
```

Example services:

```text
CadDocumentService
CadObjectService
CadGeometryService
CadValidationService
CadRevisionService
CadExportService
CadImportService
CadDependencyService
```

---

# 202. Database/API Boundary

Do not expose raw database structure directly to JavaScript.

Use:

```text
API DTO
 ↓
Domain Service
 ↓
Repository
 ↓
MySQL
```

---

# 203. Testing Strategy

## Unit Tests

Test:

```text
geometry
intersections
offsets
trim
extend
dimensions
area
perimeter
transforms
constraints
```

## Integration Tests

Test:

```text
CAD API
database persistence
revisioning
authorization
imports
exports
3D synchronization
manufacturing invalidation
```

## Browser Tests

Test:

```text
drawing
selection
snap
move
resize
dimensions
save
undo
redo
```

---

# 204. Geometry Test Examples

### Distance

```text
A=(0,0)
B=(3000,0)
Expected=3000
```

### Rectangle

```text
width=4000
height=3000
area=12,000,000 mm²
```

### Room

```text
4000 × 3000
area = 12 m²
```

---

# 205. Regression Testing

Every geometry bug MUST receive:

```text
regression test
```

before fixing.

Do not rely only on manual testing for geometry correctness.

---

# 206. Browser Compatibility

Target:

```text
Chrome
Edge
Firefox
Safari
```

Desktop-first.

Minimum supported viewport:

```text
1280 × 720
```

Recommended:

```text
1920 × 1080
```

---

# 207. Accessibility

CAD canvas controls SHOULD support:

```text
keyboard shortcuts
focusable toolbar
ARIA labels
high-contrast UI
tooltips
```

Canvas itself cannot expose every geometry operation through standard accessibility, so critical properties/actions must also be available through accessible panels.

---

# 208. Localization

All user-facing text MUST use localization keys.

Do not hard-code text in CAD JavaScript.

Example:

```javascript
t('cad.tools.wall')
```

Support future:

```text
English
Hindi
Kannada
Korean
```

---

# 209. Telemetry / Logging

Log technical events:

```text
CAD_OPEN
CAD_SAVE
CAD_EXPORT
CAD_IMPORT
CAD_VALIDATION
CAD_ERROR
CAD_SYNC_CONFLICT
```

Do not log complete project geometry in normal application logs.

---

# 210. Final Definition of Done

2D CAD is considered implementation-ready only when:

```text
[ ] Real-world coordinate system implemented
[ ] CAD document model implemented
[ ] Layer system implemented
[ ] Canvas renderer implemented
[ ] Selection implemented
[ ] Snap implemented
[ ] Grid implemented
[ ] Ortho implemented
[ ] Numeric input implemented
[ ] Basic geometry implemented
[ ] Modify tools implemented
[ ] Wall system implemented
[ ] Room system implemented
[ ] Door/window system implemented
[ ] Structural objects implemented
[ ] Furniture placement implemented
[ ] Parametric furniture integration implemented
[ ] Dimensions implemented
[ ] Annotations implemented
[ ] Constraints implemented
[ ] Validation implemented
[ ] Undo/redo implemented
[ ] Autosave implemented
[ ] Revisioning implemented
[ ] Import implemented
[ ] Export implemented
[ ] API implemented
[ ] Database implemented
[ ] Tenant isolation implemented
[ ] RBAC enforced
[ ] 2D/3D synchronization implemented
[ ] Dependency invalidation implemented
[ ] Manufacturing dependency model implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Browser tests implemented
[ ] Performance testing completed
[ ] Security testing completed
```

---

# 211. Final CAD Architecture Principle

The most important implementation rule is:

> **The canvas is a visualization of the CAD model; it is not the CAD model itself.**

The authoritative architecture MUST be:

```text
                    CAD DOCUMENT
                         │
          ┌──────────────┼───────────────┐
          │              │               │
       OBJECTS         LAYERS        CONSTRAINTS
          │              │               │
          └──────────────┼───────────────┘
                         │
                  DEPENDENCY GRAPH
                         │
          ┌──────────────┼──────────────┐
          │              │              │
          2D             3D          MATERIALS
          │              │              │
          └──────────────┼──────────────┘
                         │
                    BOM / BOQ
                         │
                   ENGINEERING
                         │
                  MANUFACTURING
                         │
              CUTLIST / NESTING / CNC
                         │
                       MES
```

The system should therefore behave like a **domain-specific interior CAD platform**, not a drawing application.

---

# 212. Cursor Master Instruction

Treat this document as the **2D CAD implementation contract**.

Before writing implementation code:

1. Analyze the existing repository.
2. Identify current CAD/Canvas/Three.js implementations.
3. Map existing objects to this specification.
4. Reuse working components where architecturally sound.
5. Do not duplicate geometry engines.
6. Do not introduce a second source of truth.
7. Create a migration plan for incompatible data structures.
8. Implement the CAD domain model before expanding UI tools.
9. Add automated geometry tests before complex parametric features.
10. Preserve backward compatibility where possible.

Every CAD feature must answer:

```text
What is the domain object?
What geometry does it own?
What parameters control it?
What objects does it depend on?
What objects depend on it?
How is it persisted?
How is it rendered?
How is it edited?
How is it versioned?
How is it validated?
How does it synchronize to 3D?
How does it affect BOM/BOQ?
How does it affect manufacturing?
How is it authorized?
How is it audited?
```

If those questions cannot be answered, the feature is not ready for implementation.
