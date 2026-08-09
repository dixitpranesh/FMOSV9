# QR / Panel Tracking Specification
## FMOS — Interior Design → Manufacturing → MES Traceability

**Document ID:** QR-PANEL-TRACK-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, ES6 Developers, MES Developers, Manufacturing Engineers, Factory Supervisors, Operators, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Tracking Unit:** Individual Panel Instance  
**Date:** 2026-08-10

---

# 1. Purpose

This specification defines the complete QR/barcode and physical panel tracking system for FMOS.

The objective is to maintain a digital identity for every physical furniture component from engineering through factory execution.

The system must connect:

```text
Project
 ↓
Room
 ↓
Furniture
 ↓
Component
 ↓
Panel Definition
 ↓
Panel Instance
 ↓
Raw Sheet / Remnant
 ↓
Nesting Placement
 ↓
CNC Program
 ↓
Cutting
 ↓
Edge Banding
 ↓
Drilling / Routing
 ↓
Assembly
 ↓
Quality
 ↓
Packing
 ↓
Dispatch
```

The QR/Panel Tracking subsystem is therefore a core MES capability and not merely a label-printing feature.

---

# 2. Core Objective

Every physical panel must have a unique digital identity.

The system must answer:

```text
What is this panel?
Which project does it belong to?
Which room?
Which furniture?
Which component?
Which material?
Which sheet produced it?
Where was it nested?
Which CNC program should process it?
Which operations are complete?
Where is it physically located?
Who handled it?
Which machine processed it?
Did quality pass?
Was it reworked?
Was it scrapped?
Which replacement panel was created?
Which package contains it?
Has it been dispatched?
```

---

# 3. Core Principle

Use:

```text
Panel Definition
```

for the engineering identity and:

```text
Panel Instance
```

for the physical manufacturing identity.

Example:

```text
Panel Definition:
Kitchen_BC03_LeftSide

Panel Instance:
PNL-F01-2026-000123
```

Multiple physical instances may originate from one logical panel definition.

---

# 4. Panel Definition vs Panel Instance

## Panel Definition

Represents the engineered component.

Contains:

```text
logical_panel_id
component_id
dimensions
material
thickness
grain
edge requirements
machining requirements
manufacturing revision
```

## Panel Instance

Represents the actual physical piece.

Contains:

```text
panel_instance_id
logical_panel_id
production_order_id
sheet_id
nesting_placement_id
current_status
current_location
QR identity
```

---

# 5. Panel Identity Hierarchy

```text
Project
  ↓
Building
  ↓
Floor
  ↓
Room
  ↓
Furniture
  ↓
Furniture Module
  ↓
Component
  ↓
Panel Definition
  ↓
Panel Instance
```

---

# 6. Unique Panel ID

Every physical panel must have a globally unique identifier within the FMOS tenant.

Recommended format:

```text
PNL-{FACTORY}-{YEAR}-{SEQUENCE}
```

Example:

```text
PNL-F01-2026-000001
```

The exact format must be configurable.

---

# 7. ID Requirements

Panel IDs must:

```text
be unique
be immutable
not be reused
not contain sensitive customer data
be machine-readable
be human-readable
be searchable
```

---

# 8. ID Generation

IDs must be generated server-side.

Never trust:

```text
browser-generated panel IDs
```

as the authoritative identity.

---

# 9. Sequence Generation

Use a transaction-safe sequence mechanism.

Do not use:

```text
SELECT MAX(id) + 1
```

---

# 10. Tenant Isolation

Panel identity must be unique across:

```text
tenant
```

or globally unique using UUID/ULID.

Recommended internal primary key:

```text
UUID / ULID
```

and separate human-readable:

```text
panel_code
```

---

# 11. Recommended Identity Model

```text
id
= internal UUID/ULID

panel_code
= PNL-F01-2026-000123
```

The internal ID should be immutable.

---

# 12. QR Code Purpose

QR code identifies the panel.

The QR code should resolve to a secure FMOS resource.

Do not store the complete panel data inside the QR code.

---

# 13. QR Payload

Preferred payload:

```text
https://app.example.com/p/pnl/<signed-token>
```

or:

```text
FMOS:PANEL:<signed-token>
```

The token must not expose sensitive information.

---

# 14. QR Security

QR payload should preferably contain:

```text
opaque identifier
```

or:

```text
signed token
```

rather than:

```text
customer name
address
pricing
supplier price
```

---

# 15. QR Token

If signed tokens are used, support:

```text
token_version
panel_id
issued_at
signature
```

---

# 16. QR Token Expiration

Panel identity itself must not expire.

If QR uses expiring access tokens, the printed QR must still be resolvable through a stable panel identifier or controlled token rotation strategy.

---

# 17. QR Error Correction

Use an appropriate QR error correction level.

Recommended default:

```text
M
```

Increase to:

```text
Q/H
```

for harsh factory environments where required.

---

# 18. QR Size

Minimum physical size must be configurable based on:

```text
printer
scanner
distance
surface
environment
```

---

# 19. QR Quiet Zone

Printed QR must maintain the required quiet zone.

Do not place graphics or text inside the QR quiet zone.

---

# 20. QR Print Quality

Label generation must target reliable scanning.

Support:

```text
300 DPI
600 DPI
```

printers where applicable.

---

# 21. QR Verification

Every generated QR must be decoded and validated before mass printing when the printer/scanner workflow supports automated verification.

---

# 22. Barcode Support

In addition to QR, support:

```text
Code 128
```

for factories using laser/barcode scanners.

---

# 23. Dual-Code Label

Recommended label:

```text
QR
+
Code 128
+
Human-readable Panel ID
```

---

# 24. Panel Label

Minimum label information:

```text
Panel ID
Project Code
Room
Furniture
Panel Description
Dimensions
Material
Thickness
Current/Initial Operation
```

Optional:

```text
Grain direction
Edge banding
CNC program
Package
Priority
```

---

# 25. Sensitive Data Rule

Do not print:

```text
customer phone
customer email
customer address
selling price
margin
supplier cost
```

unless explicitly required.

---

# 26. Label Template

Label templates must be configurable by:

```text
tenant
brand
factory
printer
label size
production line
```

---

# 27. White-Label Support

Each tenant may configure:

```text
logo
brand
colors
footer
contact information
```

without modifying tracking logic.

---

# 28. Label Sizes

Support configurable templates such as:

```text
50 × 30 mm
70 × 50 mm
100 × 50 mm
100 × 70 mm
custom
```

---

# 29. Label Orientation

Support:

```text
portrait
landscape
rotated 90°
rotated 180°
rotated 270°
```

---

# 30. Label Printer Profiles

Store:

```text
printer_id
printer_type
DPI
label_width
label_height
connection
driver/protocol
```

---

# 31. Label Generation

Label generation should produce:

```text
PDF
PNG
printer command format
```

where supported.

---

# 32. Batch Printing

Support printing:

```text
single panel
selected panels
work order
production order
nesting batch
full project
```

---

# 33. Print Queue

Maintain:

```text
QUEUED
PRINTING
PRINTED
FAILED
CANCELLED
```

---

# 34. Print Audit

Every print records:

```text
panel
label version
template
printer
user
timestamp
quantity
```

---

# 35. Reprint

Authorized users may reprint.

Every reprint must record:

```text
reason
user
timestamp
```

---

# 36. Duplicate Active Label Protection

System must detect multiple active labels for the same panel.

Multiple physical labels may exist only when explicitly allowed and must be tracked as:

```text
label instance
```

---

# 37. Label Instance

Recommended table:

```text
mes_panel_labels
```

Fields:

```text
id
panel_id
label_serial
template_id
version
print_count
status
printed_by
printed_at
```

---

# 38. Panel Lifecycle

Minimum:

```text
CREATED
RELEASED
MATERIAL_ALLOCATED
NESTED
CUT_READY
CUT
EDGE_READY
EDGE_BANDED
CNC_READY
CNC_IN_PROGRESS
CNC_COMPLETE
ASSEMBLY_READY
ASSEMBLY_IN_PROGRESS
ASSEMBLED
QC_PENDING
QC_PASSED
QC_FAILED
REWORK
ON_HOLD
SCRAPPED
REPLACED
PACKED
DISPATCH_READY
DISPATCHED
CLOSED
```

---

# 39. State Machine

Panel state changes must be controlled by explicit transitions.

Example:

```text
CREATED
 ↓
RELEASED
 ↓
NESTED
 ↓
CUT_READY
 ↓
CUT
 ↓
EDGE_READY
 ↓
EDGE_BANDED
 ↓
CNC_READY
 ↓
CNC_COMPLETE
 ↓
ASSEMBLY_READY
 ↓
ASSEMBLED
 ↓
QC_PENDING
 ↓
QC_PASSED
 ↓
PACKED
 ↓
DISPATCHED
```

---

# 40. Exception States

Panels may enter:

```text
ON_HOLD
QC_FAILED
REWORK
SCRAPPED
REPLACED
```

These must not destroy the normal production history.

---

# 41. State Transition Validation

Before changing state validate:

```text
current state
required previous operation
operator permission
station capability
production order status
quality hold
material status
```

---

# 42. No Direct State Editing

UI must never allow arbitrary:

```text
panel.status = 'CNC_COMPLETE'
```

without passing through the domain/service transition.

---

# 43. Panel Event Model

Every important state/action creates an immutable event.

Minimum events:

```text
PANEL_CREATED
PANEL_RELEASED
PANEL_LABEL_PRINTED
PANEL_SCANNED
PANEL_MOVED
OPERATION_STARTED
OPERATION_PAUSED
OPERATION_RESUMED
OPERATION_COMPLETED
PANEL_HOLD
PANEL_RELEASED_FROM_HOLD
QUALITY_STARTED
QUALITY_PASSED
QUALITY_FAILED
REWORK_CREATED
SCRAP_RECORDED
REPLACEMENT_CREATED
PACKED
DISPATCHED
```

---

# 44. Event Fields

```text
event_id
tenant_id
factory_id
panel_id
event_type
operator_id
station_id
machine_id
work_order_id
timestamp
device_timestamp
payload_json
```

---

# 45. Event Immutability

Events must never be edited.

Corrections must create:

```text
correction event
```

or:

```text
compensating event
```

---

# 46. Event Sequence

Each panel should maintain an ordered event sequence.

Example:

```text
001 CREATED
002 RELEASED
003 NESTED
004 CUT
005 EDGE_BANDED
006 CNC_COMPLETE
007 QC_PASS
008 PACKED
```

---

# 47. Current State

Current state may be stored for fast lookup:

```text
panel_instances.status
```

but the event history remains the audit source.

---

# 48. Current Location

Every active panel must have:

```text
current_location_id
current_station_id
current_work_center_id
```

where applicable.

---

# 49. Location Hierarchy

```text
Factory
 ↓
Building
 ↓
Zone
 ↓
Work Center
 ↓
Station
 ↓
Rack / Bin / Position
```

---

# 50. Location Types

Support:

```text
WAREHOUSE
CUTTING
EDGE
CNC
DRILLING
ASSEMBLY
QC
PACKING
DISPATCH
HOLD
SCRAP
CUSTOM
```

---

# 51. Location Master

Fields:

```text
id
factory_id
parent_location_id
code
name
type
capacity
status
```

---

# 52. Physical Position

Optional:

```text
rack
shelf
bin
slot
```

---

# 53. Panel Movement

Every physical move may create:

```text
PANEL_MOVED
```

event.

---

# 54. Movement Fields

```text
panel_id
from_location
to_location
operator
timestamp
reason
```

---

# 55. Movement Validation

Prevent movement to:

```text
inactive location
unauthorized factory
wrong work center
```

where configured.

---

# 56. Scan Station

A station represents the place where scanning occurs.

Fields:

```text
station_id
factory_id
work_center_id
location_id
station_type
device_id
status
```

---

# 57. Station Types

```text
CUTTING
EDGE
CNC
DRILLING
ASSEMBLY
QC
PACKING
WAREHOUSE
DISPATCH
```

---

# 58. Device Registration

Factory devices may be registered:

```text
device_id
device_name
device_type
station_id
status
last_seen
```

---

# 59. Scanner Types

Support:

```text
USB scanner
Bluetooth scanner
camera scanner
Android handheld
industrial scanner
tablet camera
```

---

# 60. Scan Input

Frontend should normalize scanner input into:

```text
raw_code
decoded_type
panel_token
device_id
timestamp
```

---

# 61. Scan Resolution

The backend resolves:

```text
QR token
→ panel
```

or:

```text
barcode
→ panel
```

---

# 62. Scan Endpoint

```http
POST /api/v1/mes/panel-tracking/scan
```

Example:

```json
{
  "code": "FMOS:PANEL:...",
  "station_id": "ST-CNC-01",
  "device_id": "DEV-001",
  "action": "START"
}
```

---

# 63. Scan Actions

Support:

```text
LOOKUP
START
COMPLETE
MOVE
HOLD
RELEASE
QUALITY
REWORK
SCRAP
PACK
DISPATCH
```

---

# 64. Scan Lookup

`LOOKUP` must not change panel state.

It returns:

```text
identity
current status
current location
current operation
next operation
work order
CNC program
quality state
```

---

# 65. Scan Start

Start requires:

```text
valid panel
valid station
valid operation
authorized operator
no blocking hold
```

---

# 66. Scan Complete

Complete requires:

```text
operation started
operator authorized
station valid
required quality data completed
```

where configured.

---

# 67. Wrong Station

If panel is scanned at the wrong station:

```text
BLOCK
```

and return:

```text
expected station
expected operation
current panel state
```

---

# 68. Wrong Operation

If operator tries:

```text
PACK
```

while panel is:

```text
CUT_READY
```

return:

```text
PANEL_NOT_READY_FOR_OPERATION
```

---

# 69. Wrong Production Order

A panel must not be executed against a work order from another production order unless explicitly configured.

---

# 70. Wrong Factory

Panel from Factory A must not be processed by Factory B unless inter-factory transfer is explicitly supported.

---

# 71. Inter-Factory Transfer

Future support:

```text
TRANSFER_REQUESTED
TRANSFERRED
RECEIVED
```

with complete audit.

---

# 72. Duplicate Scan Protection

Repeated scans must be handled idempotently.

Example:

```text
START
START again
```

must not create two operation starts.

---

# 73. Idempotency Key

Support:

```http
Idempotency-Key: <client-event-id>
```

---

# 74. Offline Scan

The architecture must support offline operation for factory environments with unreliable network.

Offline event:

```text
client_event_id
device_id
panel_id/token
action
device_timestamp
```

---

# 75. Offline Queue

Store pending events locally:

```text
PENDING
SYNCING
SYNCED
FAILED
CONFLICT
```

---

# 76. Offline Security

Do not store long-lived privileged credentials in plain local storage.

Use:

```text
short-lived tokens
device registration
secure storage
```

as supported by the platform.

---

# 77. Offline Conflict

If server state changed after an offline event:

```text
CONFLICT
```

must be returned.

Do not silently overwrite server state.

---

# 78. Conflict UI

Show:

```text
Panel
Device action
Server state
Conflict reason
Supervisor action
```

---

# 79. Panel Search

Search by:

```text
panel_code
internal_id
QR
barcode
project
room
furniture
component
production_order
work_order
CNC program
```

---

# 80. Panel Lookup Response

Return:

```json
{
  "panel": {
    "panel_code": "PNL-F01-2026-000123",
    "status": "CNC_COMPLETE",
    "project": "P001",
    "room": "Kitchen",
    "furniture": "Base Cabinet 03",
    "material": "MDF",
    "thickness": 18
  },
  "current_operation": "QC",
  "current_location": "QC-01",
  "next_operation": "PACK"
}
```

---

# 81. Panel Detail Screen

Show:

```text
Panel identity
Dimensions
Material
Edges
Grain
Furniture
Room
Project
Current status
Current location
Current work order
CNC program
Quality
Timeline
```

---

# 82. Panel Timeline

Display:

```text
08:30 CREATED
08:40 NESTED
09:10 CUT
09:30 EDGE
10:05 CNC
10:30 QC PASS
11:00 PACKED
```

---

# 83. Panel Genealogy Screen

Show upstream:

```text
Project
Room
Furniture
Component
Manufacturing Revision
```

and downstream:

```text
Sheet
Nesting
CNC
Machine
Production
QC
Package
Dispatch
```

---

# 84. Panel Production History

Show:

```text
operation
operator
machine
station
start
finish
duration
result
```

---

# 85. Panel Quality History

Show:

```text
inspection
measurement
result
inspector
timestamp
failure reason
rework
```

---

# 86. Panel Rework History

Show:

```text
original operation
failure
rework order
rework operation
reinspection
result
```

---

# 87. Panel Scrap History

Show:

```text
scrap reason
operator
supervisor approval
material
quantity
replacement
```

---

# 88. Replacement Panel

A replacement panel receives:

```text
new panel_instance_id
```

and stores:

```text
replacement_for_panel_id
replacement_reason
```

---

# 89. Replacement Chain

Support:

```text
PNL-001
 ↓ SCRAPPED
PNL-101 replacement
 ↓ SCRAPPED
PNL-201 replacement
```

The complete chain must remain visible.

---

# 90. No Identity Reuse

A scrapped panel ID must never be assigned to another physical panel.

---

# 91. Panel Splitting

If a manufacturing process creates multiple physical pieces from one source:

```text
parent_panel
→ child_panel_1
→ child_panel_2
```

support genealogy.

---

# 92. Panel Merging

If multiple physical components are assembled into one physical unit:

```text
panel_1
panel_2
panel_3
→ assembly_unit
```

preserve component genealogy.

---

# 93. Assembly Unit

Create:

```text
assembly_unit_id
```

for furniture assemblies.

---

# 94. Assembly Genealogy

```text
Assembly Unit
 ↓
Panel 1
Panel 2
Panel 3
Hardware Kit
```

---

# 95. Package Genealogy

```text
Package
 ↓
Assembly
 ↓
Panels
 ↓
Hardware
```

---

# 96. Dispatch Genealogy

```text
Dispatch
 ↓
Packages
 ↓
Assemblies
 ↓
Panels
```

---

# 97. Label Content by Stage

Label may change visually as production progresses, but the QR identity must remain stable.

Recommended:

```text
Engineering label
Production label
QC label
Packing label
```

where required.

---

# 98. Label Reconciliation

If a label becomes unreadable:

```text
scan panel using alternate identifier
```

or:

```text
manual search
```

then print replacement label.

---

# 99. Label Damage Workflow

Support:

```text
LABEL_DAMAGED
LABEL_REPRINTED
LABEL_REATTACHED
```

---

# 100. Label Replacement

Reprinted label must preserve:

```text
same panel identity
```

but create:

```text
new label instance
```

---

# 101. Panel Marking

If QR labels cannot survive machining, support:

```text
temporary label
permanent engraving/marking
secondary tracking
```

according to factory capability.

---

# 102. Machining Label Strategy

The system must define whether QR label is:

```text
applied before cutting
after cutting
after edge banding
after CNC
```

based on the factory workflow.

---

# 103. Label Survival Rule

The tracking strategy must ensure that panel identity survives every operation.

---

# 104. Temporary-to-Permanent Identity

If the physical panel cannot retain the original label:

```text
temporary identifier
→ operation
→ permanent identifier
```

must maintain a traceable mapping.

---

# 105. Label Placement

Label template may define:

```text
position
orientation
safe margin
```

---

# 106. CNC-Safe Label Placement

Label placement must avoid:

```text
machining zone
edge banding zone
drilling zone
cut contour
```

where applicable.

---

# 107. Label Placement Metadata

Store:

```text
label_x
label_y
label_rotation
```

if label position is engineered.

---

# 108. Grain Awareness

Panel label should optionally show:

```text
grain direction
```

with a visual arrow.

---

# 109. Edge Awareness

Panel label may show:

```text
front edge
back edge
left edge
right edge
```

for operator orientation.

---

# 110. Orientation Marker

Provide visual orientation:

```text
↑ FRONT
```

or configurable equivalent.

---

# 111. Panel Image

Label may optionally include:

```text
2D panel thumbnail
```

for operator recognition.

---

# 112. Panel Drawing

Panel detail screen should optionally show:

```text
2D machining drawing
```

including:

```text
holes
grooves
edges
orientation
dimensions
```

---

# 113. CNC Program Link

Panel tracking must link:

```text
panel
→ CNC program
```

including:

```text
program number
version
machine
post processor
```

---

# 114. Multiple CNC Programs

A panel may have multiple CNC programs if:

```text
multiple setups
multiple machines
multiple faces
```

are required.

---

# 115. CNC Execution Event

Store:

```text
program_id
machine_id
operator_id
start
finish
result
alarm
```

---

# 116. Machine Alarm

If machine reports an alarm:

```text
CNC_ALARM
```

event should be associated with:

```text
panel
work order
machine
program
```

when identifiable.

---

# 117. Manual CNC Completion

If automatic machine feedback is unavailable, authorized operator may record:

```text
MANUAL_CNC_COMPLETE
```

with audit.

---

# 118. Manual Completion Restrictions

Manual completion should require:

```text
reason
operator
timestamp
```

and optionally supervisor approval.

---

# 119. Panel Location Scan

Support scan action:

```text
MOVE
```

to record:

```text
current physical location
```

---

# 120. Rack Tracking

Racks may have their own QR codes.

Example:

```text
RACK-CNC-05
```

Scanning:

```text
panel + rack
```

moves the panel to that rack.

---

# 121. Bin Tracking

Support:

```text
BIN-001
SHELF-002
ZONE-A
```

---

# 122. Batch Tracking

Operators may scan multiple panels as a batch.

Batch tracking must still retain individual panel identity.

---

# 123. Batch ID

Support:

```text
production_batch_id
```

---

# 124. Batch Scan

Example:

```text
Batch:
BATCH-001

Panels:
PNL-001
PNL-002
PNL-003
...
```

---

# 125. Batch Completion

Completing a batch must not falsely mark unscanned panels as completed.

---

# 126. Bundle Tracking

Support bundling related panels:

```text
BUNDLE-001
```

with:

```text
panel IDs
```

---

# 127. Bundle QR

Bundle QR must resolve to:

```text
bundle
```

and list its panel contents.

---

# 128. Bundle Split

Support:

```text
remove panel from bundle
```

with audit.

---

# 129. Bundle Merge

Support:

```text
merge bundles
```

with genealogy.

---

# 130. Packing Verification

When packing:

```text
scan package
scan panel
```

must verify panel belongs to package/order.

---

# 131. Wrong Package

If panel does not belong:

```text
BLOCK
```

and show expected package/order.

---

# 132. Package Completion

Package cannot be completed if required components are missing unless partial packing is explicitly enabled.

---

# 133. Dispatch Verification

At dispatch:

```text
scan package
```

must validate:

```text
package complete
QC passed
dispatch-ready
```

---

# 134. Dispatch Scan

Record:

```text
package
dispatch
operator
timestamp
```

---

# 135. Customer/Installer Tracking

Future support may expose a limited tracking view to installation teams.

Never expose factory-sensitive information.

---

# 136. Installation Handoff

Optional future flow:

```text
DISPATCHED
 ↓
RECEIVED
 ↓
INSTALLATION
 ↓
INSTALLED
```

---

# 137. Installation QR

The same package/panel identity may be used for installation tracking.

---

# 138. Panel Tracking Database

Minimum tables:

```text
mes_panel_instances
mes_panel_labels
mes_panel_events
mes_panel_locations
mes_panel_movements
mes_scan_stations
mes_scan_devices
mes_scan_events
mes_panel_batches
mes_panel_batch_items
mes_panel_bundles
mes_panel_bundle_items
mes_panel_replacements
mes_panel_genealogy
```

---

# 139. Panel Instances Table

Fields:

```text
id
tenant_id
factory_id
panel_code
logical_panel_id
component_id
furniture_id
room_id
project_id
production_order_id
manufacturing_revision_id
nesting_revision_id
sheet_id
nesting_placement_id
status
current_location_id
current_station_id
current_work_center_id
replacement_for_panel_id
created_at
updated_at
```

---

# 140. Panel Labels Table

Fields:

```text
id
panel_id
label_serial
qr_token_version
template_id
template_version
printer_id
print_reason
status
printed_by
printed_at
```

---

# 141. Panel Events Table

Fields:

```text
id
panel_id
event_type
event_sequence
operator_id
station_id
device_id
machine_id
work_order_id
from_status
to_status
from_location_id
to_location_id
payload_json
device_timestamp
server_timestamp
created_at
```

---

# 142. Panel Locations Table

Fields:

```text
id
factory_id
parent_location_id
location_code
location_name
location_type
capacity
status
```

---

# 143. Panel Movement Table

Fields:

```text
id
panel_id
from_location_id
to_location_id
operator_id
station_id
reason
event_id
created_at
```

---

# 144. Scan Event Table

Fields:

```text
id
client_event_id
panel_id
station_id
device_id
operator_id
action
raw_code
decoded_type
device_timestamp
server_timestamp
sync_status
result
error_code
payload_json
```

---

# 145. Panel Replacement Table

Fields:

```text
id
original_panel_id
replacement_panel_id
reason_code
authorized_by
created_by
created_at
```

---

# 146. Panel Genealogy Table

Fields:

```text
id
parent_panel_id
child_panel_id
relationship_type
created_by
created_at
```

Relationships:

```text
REPLACEMENT
SPLIT
MERGE
DERIVED
```

---

# 147. Bundle Table

Fields:

```text
id
factory_id
bundle_code
status
created_by
created_at
```

---

# 148. Bundle Item Table

Fields:

```text
id
bundle_id
panel_id
added_at
removed_at
```

---

# 149. Indexing

Required indexes:

```text
panel_code
project_id
room_id
furniture_id
production_order_id
status
current_location_id
current_station_id
qr token hash
barcode
created_at
```

---

# 150. QR Token Storage

If signed/opaque tokens are used, store only what is necessary.

Where feasible:

```text
token_hash
```

rather than plaintext reusable secrets.

---

# 151. API Architecture

Base:

```http
/api/v1/mes/panel-tracking/
```

---

# 152. Panel Creation API

```http
POST /api/v1/mes/panel-tracking/panels
```

Created normally from Manufacturing/MES integration.

---

# 153. Panel Lookup API

```http
GET /api/v1/mes/panel-tracking/panels/{id}
```

---

# 154. Panel Code Lookup

```http
GET /api/v1/mes/panel-tracking/panels/by-code/{panel_code}
```

---

# 155. QR Resolve API

```http
POST /api/v1/mes/panel-tracking/resolve
```

Request:

```json
{
  "code": "FMOS:PANEL:..."
}
```

---

# 156. Scan API

```http
POST /api/v1/mes/panel-tracking/scan
```

---

# 157. Movement API

```http
POST /api/v1/mes/panel-tracking/panels/{id}/move
```

---

# 158. Hold API

```http
POST /api/v1/mes/panel-tracking/panels/{id}/hold
```

---

# 159. Release Hold API

```http
POST /api/v1/mes/panel-tracking/panels/{id}/release-hold
```

---

# 160. Label API

```http
POST /api/v1/mes/panel-tracking/panels/{id}/label
GET /api/v1/mes/panel-tracking/panels/{id}/label
POST /api/v1/mes/panel-tracking/panels/{id}/label/reprint
```

---

# 161. Timeline API

```http
GET /api/v1/mes/panel-tracking/panels/{id}/timeline
```

---

# 162. Genealogy API

```http
GET /api/v1/mes/panel-tracking/panels/{id}/genealogy
```

---

# 163. Location API

```http
GET /api/v1/mes/panel-tracking/locations
POST /api/v1/mes/panel-tracking/panels/{id}/move
```

---

# 164. Batch API

```http
POST /api/v1/mes/panel-tracking/batches
GET /api/v1/mes/panel-tracking/batches/{id}
POST /api/v1/mes/panel-tracking/batches/{id}/scan
```

---

# 165. Bundle API

```http
POST /api/v1/mes/panel-tracking/bundles
GET /api/v1/mes/panel-tracking/bundles/{id}
POST /api/v1/mes/panel-tracking/bundles/{id}/add
POST /api/v1/mes/panel-tracking/bundles/{id}/remove
```

---

# 166. Replacement API

```http
POST /api/v1/mes/panel-tracking/panels/{id}/replacement
GET /api/v1/mes/panel-tracking/panels/{id}/replacements
```

---

# 167. Scan Station API

```http
GET /api/v1/mes/panel-tracking/stations
POST /api/v1/mes/panel-tracking/stations
PATCH /api/v1/mes/panel-tracking/stations/{id}
```

---

# 168. Device API

```http
POST /api/v1/mes/panel-tracking/devices/register
POST /api/v1/mes/panel-tracking/devices/{id}/heartbeat
```

---

# 169. Label Template API

```http
GET /api/v1/mes/panel-tracking/label-templates
POST /api/v1/mes/panel-tracking/label-templates
PATCH /api/v1/mes/panel-tracking/label-templates/{id}
```

---

# 170. Scan Response

Successful response:

```json
{
  "success": true,
  "data": {
    "panel": {},
    "action": "START",
    "allowed": true,
    "next_state": "CNC_IN_PROGRESS"
  }
}
```

---

# 171. Scan Failure Response

```json
{
  "success": false,
  "error": {
    "code": "PANEL_WRONG_STATION",
    "message": "Panel is not ready for this station.",
    "details": {
      "current_station": "EDGE-01",
      "requested_station": "PACK-01"
    }
  }
}
```

---

# 172. Frontend Architecture

Recommended:

```text
/src/panel-tracking/

domain/
  Panel.js
  PanelDefinition.js
  PanelEvent.js
  PanelLocation.js
  PanelLabel.js
  PanelGenealogy.js

scanner/
  Scanner.js
  QrDecoder.js
  BarcodeDecoder.js
  ScanQueue.js
  ScanValidator.js

tracking/
  PanelTracker.js
  PanelTimeline.js
  PanelGenealogyView.js
  LocationTracker.js

labels/
  LabelRenderer.js
  LabelTemplate.js
  PrintQueue.js

shopfloor/
  StationScanner.js
  OperatorPanel.js
  ScanResult.js

offline/
  OfflineEventStore.js
  SyncManager.js
  ConflictResolver.js
```

---

# 173. PHP Architecture

Recommended:

```text
src/
  PanelTracking/
    Domain/
    Identity/
    QR/
    Barcode/
    Labels/
    Scanning/
    Locations/
    Movement/
    Genealogy/
    Batches/
    Bundles/
    Replacements/
    Events/
    Offline/
    Repositories/
    Policies/
    DTO/
```

Core services:

```text
PanelIdentityService
PanelCreationService
QrTokenService
BarcodeService
PanelScanService
PanelStateService
PanelMovementService
PanelGenealogyService
PanelLabelService
PanelReplacementService
PanelBatchService
PanelBundleService
PanelEventService
```

---

# 174. Panel State Service

All transitions must pass through:

```text
PanelStateService
```

rather than direct database updates.

---

# 175. Scan Service

Responsibilities:

```text
decode
resolve
authorize
validate
execute action
create event
update state
return next action
```

---

# 176. QR Service

Responsibilities:

```text
generate token
encode token
validate token
resolve panel
handle token version
```

---

# 177. Label Service

Responsibilities:

```text
load template
resolve panel data
generate QR
generate barcode
render label
create print job
audit print
```

---

# 178. Location Service

Responsibilities:

```text
validate location
move panel
record movement
update current location
```

---

# 179. Genealogy Service

Responsibilities:

```text
replacement
split
merge
derived relationships
assembly relationships
```

---

# 180. Offline Sync Service

Responsibilities:

```text
receive client events
deduplicate
validate ordering
resolve conflicts
persist authoritative event
return sync result
```

---

# 181. Scan UI

Primary workflow:

```text
SCAN
 ↓
IDENTIFY
 ↓
SHOW STATUS
 ↓
SHOW EXPECTED OPERATION
 ↓
CONFIRM / EXECUTE
 ↓
SHOW RESULT
```

---

# 182. Scan Result UI

Success:

```text
✓ Panel accepted
CNC operation started
Machine: CNC-01
Program: CNC-1003-v4
```

Failure:

```text
✕ Panel cannot be processed here
Expected station: EDGE-01
Current station: CNC-01
```

---

# 183. Operator UI Requirements

Keep actions limited.

Primary actions:

```text
SCAN
START
COMPLETE
HOLD
REWORK
SCRAP
MOVE
```

Only show actions allowed by state and role.

---

# 184. Supervisor Override

Overrides require:

```text
reason
supervisor identity
timestamp
original state
new state
```

---

# 185. Override Audit

Never hide that an override occurred.

---

# 186. Physical Environment

The tracking system must account for:

```text
dust
sawdust
laminate reflection
scratched labels
low lighting
glare
operator gloves
distance
network interruptions
```

---

# 187. Scanner Usability

Scanning should ideally require:

```text
one scan
minimal typing
immediate feedback
```

---

# 188. Scan Feedback

Support:

```text
sound
visual
vibration
```

depending on device.

---

# 189. Scan Timeout

If scanner input is incomplete or invalid, reset after configurable timeout.

---

# 190. Camera Scanner

If camera scanning is supported:

```text
QR detection
barcode detection
focus
torch
```

should be available where device permits.

---

# 191. Manual Entry

Allow manual:

```text
panel code
```

when QR is damaged.

Manual lookup must still require authorization.

---

# 192. Partial Code Search

Optional:

```text
search last 6 digits
```

but require user confirmation before executing a production action.

---

# 193. Panel Orientation

Panel details should show:

```text
width
height
thickness
grain
edges
front/back
```

---

# 194. Panel Thumbnail

Use generated 2D thumbnail where available.

---

# 195. Panel Manufacturing Drawing

Optional link:

```text
View Manufacturing Drawing
```

---

# 196. Panel CNC Drawing

Optional:

```text
View CNC Preview
```

---

# 197. Panel Material

Display:

```text
material
brand
catalog
code
finish
thickness
```

where authorized.

---

# 198. Panel Edge Information

Show:

```text
L
R
T
B
```

edge treatments.

Example:

```text
L = 1mm ABS
R = none
T = 2mm ABS
B = none
```

---

# 199. Grain Direction

Display:

```text
→
```

or configured orientation marker.

---

# 200. Panel Production Instructions

Show only the instructions relevant to the current operation.

Example:

```text
CNC:
Run program CNC-1003-v4
Machine CNC-01
```

---

# 201. Safety Instructions

MES must not replace machine safety procedures.

Where configured, show:

```text
PPE required
machine safety check
fixture check
```

---

# 202. Safety Acknowledgement

For selected operations, operator may be required to acknowledge:

```text
machine ready
fixture secured
material verified
```

---

# 203. Material Verification

Before first operation, optionally require scan:

```text
material/sheet QR
+
panel QR
```

to verify correct material.

---

# 204. Sheet Tracking

Raw sheets may also have QR/barcode identities:

```text
SHT-F01-2026-000001
```

---

# 205. Sheet-to-Panel Relationship

Store:

```text
sheet_id
panel_id
nesting_placement_id
```

---

# 206. Remnant Tracking

Remnants may receive:

```text
REM-F01-2026-000001
```

and can later become source material for panels.

---

# 207. Remnant Genealogy

```text
Original Sheet
 ↓
Nesting
 ↓
Used Panels
 ↓
Remaining Remnant
 ↓
Future Nesting
 ↓
New Panels
```

---

# 208. Sheet Verification

Before cutting, operator may scan:

```text
sheet QR
```

and system verifies:

```text
correct material
correct thickness
correct nesting job
```

---

# 209. Wrong Sheet

If incorrect sheet is scanned:

```text
BLOCK
```

---

# 210. Panel-to-Sheet Verification

System can verify:

```text
panel belongs to sheet
```

before production.

---

# 211. Nesting Verification

Panel scan can display:

```text
sheet
position
rotation
nesting revision
```

---

# 212. Cut Station Workflow

```text
Scan Sheet
 ↓
Verify Nesting
 ↓
Start Cut Job
 ↓
Generate / print labels
 ↓
Cut panels
 ↓
Scan/confirm panels
 ↓
Move to Edge/CNC
```

---

# 213. Post-Cut Labeling

If labels are applied after cutting:

```text
cut panel
→ identify placement
→ print label
→ attach label
→ scan label
```

---

# 214. Panel Association

System must prevent a label being attached to the wrong panel.

Recommended verification:

```text
operator scans intended panel
+
scans printed label
```

or uses a controlled label assignment workflow.

---

# 215. Label Assignment

Label assignment must create:

```text
PANEL_LABEL_ASSIGNED
```

event.

---

# 216. Label Misassignment

If label is assigned incorrectly:

```text
LABEL_MISASSIGNED
```

must be corrected through authorized workflow.

Do not silently update the panel code.

---

# 217. Label Reconciliation

Provide a supervisor workflow:

```text
Old Label
→ Actual Panel
→ Correct Label
→ Audit
```

---

# 218. Tracking Integrity

The system must detect impossible sequences.

Example:

```text
Panel says CNC_COMPLETE
but no CNC_STARTED event exists
```

This becomes:

```text
TRACEABILITY_INCONSISTENCY
```

---

# 219. Integrity Checks

Scheduled integrity jobs may detect:

```text
missing events
invalid transitions
duplicate labels
duplicate panel IDs
orphaned panels
orphaned packages
missing sheet relationships
```

---

# 220. Traceability Score

Optional dashboard metric:

```text
traceability completeness %
```

Example:

```text
98.7% of panels have complete genealogy
```

---

# 221. Panel Aging

Track time since:

```text
created
released
cut
last movement
current operation start
```

---

# 222. Stale Panel Alert

Alert if panel remains at a station beyond configurable threshold.

---

# 223. Lost Panel Detection

If a panel has no location/event update for configurable duration:

```text
PANEL_MISSING_RISK
```

---

# 224. Missing Panel Workflow

Status:

```text
MISSING
```

may be introduced if factory policy requires it.

Workflow:

```text
SEARCH
→ LOCATE
or
→ REPLACEMENT
```

---

# 225. Search Assistance

Show last known:

```text
location
operator
station
timestamp
```

---

# 226. Last Known Location

Must always be retrievable from:

```text
panel.current_location_id
```

and event history.

---

# 227. Panel Location Map

Future UI may visually display:

```text
Factory
 → Zone
 → Work Center
 → Rack
```

and panel positions.

---

# 228. Mobile UI

Mobile/handheld interface should support:

```text
scan
lookup
start
complete
move
hold
quality
```

---

# 229. Responsive UI

Desktop UI should support:

```text
supervisor
manager
planner
quality
```

Mobile UI should prioritize:

```text
operator
scanner
shop-floor
```

---

# 230. Notifications

Notify relevant users for:

```text
missing panel
wrong scan
quality failure
rework
critical hold
replacement
```

---

# 231. Panel Dashboard

Show:

```text
total panels
in production
completed
on hold
rework
scrapped
missing
packed
dispatched
```

---

# 232. Tracking Dashboard

Show:

```text
panels by station
panels by status
panels by location
aging
exceptions
```

---

# 233. Traceability Report

Export:

```text
CSV
PDF
```

where required.

---

# 234. Panel List Export

Fields:

```text
panel code
project
room
furniture
status
location
material
operation
operator
```

---

# 235. Security

Panel tracking APIs must enforce:

```text
authentication
tenant isolation
factory authorization
RBAC
resource-level authorization
```

---

# 236. Permissions

Minimum:

```text
panel.view
panel.create
panel.scan
panel.move
panel.start
panel.complete
panel.hold
panel.release_hold
panel.rework
panel.scrap
panel.replace
panel.print_label
panel.reprint_label
panel.manage_locations
panel.manage_stations
panel.manage_templates
panel.view_genealogy
panel.admin
```

---

# 237. Operator Permissions

Typical:

```text
view
scan
start
complete
move
hold_request
```

---

# 238. Supervisor Permissions

Typical:

```text
override
release_hold
approve_scrap
approve_rework
reprint
reconcile
```

---

# 239. Quality Permissions

Typical:

```text
inspect
pass
fail
hold
release_quality_hold
```

---

# 240. Tenant Isolation

Every database query must derive tenant context from authenticated server-side context.

---

# 241. Factory Isolation

A user must not resolve or manipulate a panel from an unauthorized factory.

---

# 242. QR Enumeration Protection

Do not expose sequential internal IDs through unauthenticated QR resolution.

Use:

```text
opaque token
```

or:

```text
signed identifier
```

---

# 243. Rate Limiting

QR resolution endpoints should be rate-limited.

---

# 244. Token Revocation

If tokens can be compromised, support:

```text
token_version
revoked_at
```

or equivalent.

---

# 245. Audit Logging

Audit:

```text
QR generated
label printed
label reprinted
scan
manual lookup
movement
override
state change
replacement
scrap
reconciliation
```

---

# 246. Privacy

Do not expose customer-sensitive data through QR resolution unless authenticated and authorized.

---

# 247. Performance

Recommended targets:

```text
QR resolve < 300 ms
panel lookup < 500 ms
scan action < 500 ms
state transition < 1 sec
label generation < 2 sec
```

Large batch printing may be asynchronous.

---

# 248. Concurrency

Prevent:

```text
two operators starting same panel operation
two stations claiming same panel
simultaneous conflicting moves
```

Use:

```text
database transaction
row locking
state validation
idempotency
```

---

# 249. Offline Performance

Offline scan must provide immediate local feedback where sufficient cached data exists.

---

# 250. Offline Cache

Cache only necessary:

```text
assigned work orders
panel identities
station configuration
allowed operations
```

Do not cache unnecessary sensitive information.

---

# 251. Sync Retry

Use:

```text
exponential backoff
```

with a maximum retry policy.

---

# 252. Sync Idempotency

Each offline action must have:

```text
client_event_id
```

and server must deduplicate.

---

# 253. Testing — QR

Test:

```text
generate QR
decode QR
resolve token
invalid token
expired/revoked token
wrong tenant
wrong factory
```

---

# 254. Testing — Barcode

Test:

```text
Code 128
invalid barcode
manual entry
duplicate barcode
```

---

# 255. Testing — Panel Lifecycle

Test:

```text
created
released
nested
cut
edge
CNC
assembly
QC
pack
dispatch
```

---

# 256. Testing — Invalid Transitions

Verify:

```text
cannot pack before QC
cannot CNC before required previous operations
cannot dispatch before packing
cannot process scrapped panel
```

---

# 257. Testing — Duplicate Scan

Verify:

```text
same scan twice
```

does not duplicate state transition.

---

# 258. Testing — Concurrency

Verify:

```text
two operators scan simultaneously
```

only one valid operation starts.

---

# 259. Testing — Offline

Verify:

```text
offline scan
sync
duplicate
conflict
retry
```

---

# 260. Testing — Replacement

Verify:

```text
original scrap
replacement creation
replacement genealogy
replacement tracking
```

---

# 261. Testing — Label

Verify:

```text
correct panel
correct QR
correct barcode
correct revision
correct template
correct print audit
```

---

# 262. Testing — Wrong Label

Verify:

```text
label cannot silently be assigned to another panel
```

---

# 263. Testing — Genealogy

Verify:

```text
project
→ furniture
→ panel
→ sheet
→ nesting
→ CNC
→ operation
→ QC
→ package
→ dispatch
```

can be traversed.

---

# 264. Testing — Security

Verify:

```text
tenant A cannot access tenant B panel
factory A cannot operate factory B panel
unauthorized operator cannot scrap
unauthorized user cannot override
```

---

# 265. Testing — Scale

Test:

```text
100,000+ panels
millions of events
large event history
high-frequency scanning
```

using realistic database indexing and partitioning strategies where required.

---

# 266. Cursor Pre-Implementation Analysis

Before implementing, Cursor MUST inspect the existing codebase for:

```text
Panel models
Furniture models
BOM
Manufacturing Engine
Nesting Engine
CNC/CAM
MES
Inventory
Project/Room
Assembly
Quality
Packing
Dispatch
RBAC
File storage
QR libraries
Barcode libraries
Existing label printing
```

Cursor must produce:

```text
CURRENT PANEL MODEL
CURRENT PANEL IDENTIFIERS
CURRENT QR/BARCODE
CURRENT LABEL SYSTEM
CURRENT PRODUCTION STATES
CURRENT LOCATION MODEL
CURRENT SCANNING
CURRENT CNC LINK
CURRENT INVENTORY LINK
CURRENT QUALITY LINK
CURRENT DATABASE
CURRENT API
CURRENT UI
DUPLICATE LOGIC
MISSING CAPABILITIES
MIGRATION PLAN
TARGET ARCHITECTURE
```

Do not duplicate existing panel identity logic.

---

# 267. Cursor Implementation Sequence

## Phase 1 — Identity

```text
Panel Definition
Panel Instance
Panel Code
UUID/ULID
```

## Phase 2 — QR/Barcode

```text
QR Token
Barcode
Resolve
Security
```

## Phase 3 — Labels

```text
Templates
QR
Barcode
Print
Reprint
Audit
```

## Phase 4 — State Machine

```text
Panel states
Transitions
Validation
```

## Phase 5 — Events

```text
Panel events
Audit
Timeline
```

## Phase 6 — Location

```text
Factory
Zone
Work Center
Station
Rack
Bin
Movement
```

## Phase 7 — Scanner

```text
Scan
Start
Complete
Move
Hold
Quality
```

## Phase 8 — Offline

```text
Local queue
Sync
Deduplication
Conflict
```

## Phase 9 — Genealogy

```text
Replacement
Split
Merge
Assembly
Package
Dispatch
```

## Phase 10 — Integrations

```text
Manufacturing
Nesting
CNC
Inventory
Quality
Packing
Dispatch
```

## Phase 11 — Analytics

```text
Tracking dashboard
Aging
Missing panels
Traceability
```

---

# 268. Recommended Service Flow

```text
PanelCreationService
        ↓
PanelIdentityService
        ↓
QrTokenService
        ↓
LabelService
        ↓
PanelStateService
        ↓
PanelScanService
        ↓
PanelMovementService
        ↓
PanelEventService
        ↓
GenealogyService
        ↓
MES Integrations
```

---

# 269. End-to-End Example

Example furniture:

```text
Kitchen Base Cabinet B03
```

Panel:

```text
600 × 720 × 18 mm MDF
```

System creates:

```text
Panel Definition:
B03-LEFT-SIDE
```

Physical instance:

```text
PNL-F01-2026-000123
```

QR:

```text
FMOS:PANEL:<opaque-token>
```

Then:

```text
NESTED
 ↓
Sheet SHT-001
 ↓
Cut
 ↓
Label printed
 ↓
EDGE
 ↓
CNC
 ↓
QC
 ↓
ASSEMBLY
 ↓
PACK-001
 ↓
DISPATCH-001
```

Every step generates an immutable event.

---

# 270. End-to-End Scan Example

Operator scans:

```text
PNL-F01-2026-000123
```

System responds:

```text
Panel:
Base Cabinet B03 Left Side

Material:
18mm MDF

Current:
EDGE_READY

Station:
EDGE-02

Next:
EDGE_BANDING
```

Operator presses:

```text
START
```

System creates:

```text
OPERATION_STARTED
```

Operator completes:

```text
COMPLETE
```

System creates:

```text
OPERATION_COMPLETED
```

Panel moves:

```text
EDGE_BANDED
```

and next operation becomes:

```text
CNC_READY
```

---

# 271. Final Definition of Done

```text
[ ] Panel Definition implemented
[ ] Panel Instance implemented
[ ] Unique Panel ID implemented
[ ] UUID/ULID internal identity implemented
[ ] QR identity implemented
[ ] Secure QR token implemented
[ ] Barcode support implemented
[ ] QR resolve implemented
[ ] Label templates implemented
[ ] QR labels implemented
[ ] Barcode labels implemented
[ ] Batch printing implemented
[ ] Print audit implemented
[ ] Reprint workflow implemented
[ ] Panel state machine implemented
[ ] State transition validation implemented
[ ] Panel event system implemented
[ ] Event immutability implemented
[ ] Location hierarchy implemented
[ ] Panel movement implemented
[ ] Station management implemented
[ ] Device registration implemented
[ ] Scan workflow implemented
[ ] Start operation implemented
[ ] Complete operation implemented
[ ] Hold implemented
[ ] Release hold implemented
[ ] Quality integration implemented
[ ] Rework integration implemented
[ ] Scrap integration implemented
[ ] Replacement genealogy implemented
[ ] Split/merge genealogy architecture implemented
[ ] Assembly genealogy implemented
[ ] Package genealogy implemented
[ ] Dispatch genealogy implemented
[ ] Sheet tracking implemented
[ ] Remnant tracking integration implemented
[ ] Nesting integration implemented
[ ] CNC integration implemented
[ ] Inventory integration implemented
[ ] Offline architecture implemented
[ ] Idempotency implemented
[ ] Conflict handling implemented
[ ] Tenant isolation implemented
[ ] Factory isolation implemented
[ ] RBAC implemented
[ ] Audit logging implemented
[ ] Panel timeline implemented
[ ] Panel genealogy implemented
[ ] Tracking dashboard implemented
[ ] Missing-panel detection implemented
[ ] Traceability integrity checks implemented
[ ] API tests implemented
[ ] State machine tests implemented
[ ] QR tests implemented
[ ] Barcode tests implemented
[ ] Concurrency tests implemented
[ ] Security tests implemented
[ ] Integration tests implemented
```

---

# 272. Final Architecture Principle

The QR/Panel Tracking subsystem must follow this fundamental model:

```text
                 DIGITAL IDENTITY
                       │
                       ↓
                PANEL INSTANCE
                       │
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
      QR CODE       BARCODE        PANEL CODE
        │              │              │
        └──────────────┼──────────────┘
                       ↓
                  SCAN SERVICE
                       ↓
                STATE VALIDATION
                       ↓
                PRODUCTION EVENT
                       ↓
             CURRENT STATE + LOCATION
                       ↓
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
      CNC             QC           PACKING
        │              │              │
        └──────────────┼──────────────┘
                       ↓
                    GENEALOGY
                       ↓
                    DISPATCH
```

The key rule is:

> **The QR code identifies the physical panel; the panel event history records what happened to it; the state machine determines what it is allowed to do next; and genealogy connects that physical panel back to the original design and forward to the finished shipment.**

The QR system must therefore remain a **stable identity layer**, while production status, location, operations, quality and logistics remain dynamic data associated with that identity.

This separation is mandatory for reliable factory-scale traceability.
