# MES (Manufacturing Execution System) Specification
## Interior Design → Furniture Engineering → CNC → Shop Floor → Quality → Packing → Dispatch

**Document ID:** MES-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, Manufacturing Engineers, Factory Managers, Production Supervisors, Quality Engineers, Warehouse/Dispatch Teams, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Units:** mm, pcs, m², seconds  
**Architecture:** Multi-tenant, multi-factory, role-based, event-driven where practical  
**Date:** 2026-08-10

---

# 1. Purpose

The MES is the operational layer that converts released manufacturing engineering data into controlled shop-floor execution.

The MES must bridge:

```text
DESIGN
  ↓
PARAMETRIC FURNITURE
  ↓
BOM / MANUFACTURING BOM
  ↓
NESTING
  ↓
CNC / CAM
  ↓
MES
  ↓
CUTTING
  ↓
EDGE BANDING
  ↓
DRILLING / ROUTING
  ↓
ASSEMBLY
  ↓
QUALITY
  ↓
PACKING
  ↓
DISPATCH
```

The MES is responsible for:

```text
Production planning
Production orders
Work orders
Job routing
Work centers
Machine allocation
Operator assignment
Material reservation
Panel tracking
QR/barcode tracking
Shop-floor scanning
Operation status
Work-in-progress tracking
Machine status
Downtime
Quality checks
Rework
Scrap
Replacement panels
Assembly tracking
Packing
Dispatch readiness
Production reporting
Traceability
Audit
```

---

# 2. Core Principle

The MES must answer at any moment:

```text
What should be produced?
For which project?
For which room?
For which furniture?
Which panels are required?
Where is each panel?
What operation is pending?
What operation was completed?
Who performed it?
On which machine?
When?
With which material?
Which CNC program was used?
Was quality passed?
Was rework required?
Was anything scrapped?
Is the order ready for packing?
Is it ready for dispatch?
```

---

# 3. MES Scope

## In Scope

```text
Production orders
Work orders
Production routing
Work centers
Machine allocation
Operator allocation
Material reservation
Panel genealogy
QR/barcode tracking
Shop-floor scanning
Operation execution
Machine status
Downtime
Quality
Rework
Scrap
Replacement
Assembly
Packing
Dispatch readiness
Production dashboards
Alerts
Notifications
Audit
```

## Out of Scope

Unless separately implemented:

```text
Accounting
Payroll
CRM
Customer billing
Full ERP procurement
Advanced CAD
Furniture parametric modeling
Nesting optimization
CNC toolpath generation
```

The MES consumes these upstream services.

---

# 4. MES Architecture

```text
                         PROJECT
                            ↓
                    MANUFACTURING REVISION
                            ↓
                       PRODUCTION ORDER
                            ↓
                     PRODUCTION PLAN
                            ↓
                        WORK ORDERS
                            ↓
                         ROUTING
                            ↓
      ┌─────────────────────┼─────────────────────┐
      ↓                     ↓                     ↓
 MATERIAL              WORK CENTERS            PEOPLE
      │                     │                     │
      └─────────────────────┼─────────────────────┘
                            ↓
                      SHOP FLOOR EXECUTION
                            ↓
                ┌───────────┼────────────┐
                ↓           ↓            ↓
             SCANNING    MACHINES      QUALITY
                ↓           ↓            ↓
                └───────────┼────────────┘
                            ↓
                         WIP
                            ↓
                       ASSEMBLY
                            ↓
                         PACKING
                            ↓
                        DISPATCH
```

---

# 5. MES Design Principles

The system must follow:

```text
Traceability first
Revision controlled
Event driven
Station aware
Operator aware
Machine aware
Material aware
Quality controlled
Exception driven
Auditable
```

---

# 6. Manufacturing Genealogy

Every physical component must be traceable:

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
Component
 ↓
Panel
 ↓
Sheet / Remnant
 ↓
Nesting Placement
 ↓
CNC Program
 ↓
Work Order
 ↓
Machine
 ↓
Operator
 ↓
Quality
 ↓
Packing
 ↓
Dispatch
```

---

# 7. Production Order

A Production Order represents an approved manufacturing requirement.

Fields:

```text
production_order_id
tenant_id
factory_id
project_id
manufacturing_revision_id
customer_reference
priority
planned_start
planned_end
status
created_by
approved_by
```

---

# 8. Production Order Status

Support:

```text
DRAFT
PLANNED
APPROVED
RELEASED
IN_PROGRESS
ON_HOLD
PARTIALLY_COMPLETED
COMPLETED
CANCELLED
CLOSED
```

---

# 9. Production Order Creation

Production order may be created from:

```text
Manufacturing Revision
Sales Order
Project
Manual Production Request
```

---

# 10. Release Preconditions

Before production release:

```text
Manufacturing revision valid
BOM valid
Materials available or shortage acknowledged
Nesting available
CNC programs valid where required
Routing valid
Work centers available
```

---

# 11. Production Order Snapshot

On release, store a snapshot/reference of:

```text
manufacturing revision
BOM revision
nesting revision
CNC revision
routing revision
```

Do not allow silent upstream changes to alter an active production order.

---

# 12. Production Revision

A production order must reference exact:

```text
engineering revision
manufacturing revision
nesting revision
CNC revision
```

---

# 13. Work Order

A Work Order represents an executable production task.

Example:

```text
WO-001 Cutting
WO-002 Edge Banding
WO-003 Drilling
WO-004 Assembly
WO-005 QC
WO-006 Packing
```

---

# 14. Work Order Fields

```text
id
production_order_id
operation_id
work_center_id
machine_id
assigned_operator_id
sequence
planned_qty
completed_qty
scrap_qty
rework_qty
status
planned_start
planned_end
actual_start
actual_end
```

---

# 15. Work Order Status

```text
QUEUED
READY
ASSIGNED
IN_PROGRESS
PAUSED
COMPLETED
PARTIAL
BLOCKED
CANCELLED
```

---

# 16. Routing

A routing defines the manufacturing sequence.

Example:

```text
CUT
 ↓
EDGE
 ↓
DRILL
 ↓
ROUTE
 ↓
ASSEMBLY
 ↓
QC
 ↓
PACK
```

---

# 17. Routing Version

Routing must be version controlled.

Fields:

```text
routing_id
version
effective_from
effective_to
status
```

---

# 18. Routing Operation

Each routing operation contains:

```text
sequence
operation_code
work_center
machine requirement
skill requirement
setup time
run time
quality requirement
```

---

# 19. Routing Branches

Support future conditional routing:

```text
IF material requires edge band
    → EDGE BAND

IF CNC required
    → CNC

IF manual assembly
    → ASSEMBLY
```

---

# 20. Rework Routing

A failed quality check may generate:

```text
rework route
```

without corrupting the original production route.

---

# 21. Work Center

Work center represents a physical production area or process.

Examples:

```text
Cutting
Edge Banding
CNC
Drilling
Assembly
Sanding
QC
Packing
```

---

# 22. Work Center Fields

```text
id
factory_id
code
name
type
capacity
status
location
default_queue_rule
```

---

# 23. Work Center Types

```text
CUTTING
EDGE_BANDING
CNC
DRILLING
ASSEMBLY
QC
PACKING
WAREHOUSE
DISPATCH
OTHER
```

---

# 24. Machine

Machine belongs to a work center.

Fields:

```text
machine_id
work_center_id
machine_profile_id
name
manufacturer
model
status
capacity
```

---

# 25. Machine Status

```text
AVAILABLE
RUNNING
IDLE
SETUP
BLOCKED
MAINTENANCE
BREAKDOWN
OFFLINE
```

---

# 26. Machine Events

Record:

```text
MACHINE_STARTED
MACHINE_STOPPED
MACHINE_IDLE
MACHINE_SETUP
MACHINE_BREAKDOWN
MACHINE_MAINTENANCE
MACHINE_BLOCKED
```

---

# 27. Operator

Operator is an authenticated factory user.

Track:

```text
operator_id
employee_reference
skills
certifications
work_center_authorizations
shift
status
```

---

# 28. Operator Skills

Examples:

```text
CNC
Edge Banding
Assembly
QC
Packing
```

---

# 29. Skill Validation

A work order may require:

```text
skill_code
minimum_skill_level
```

The MES must prevent assignment to an unauthorized operator unless explicit override is allowed.

---

# 30. Shift Management

Support:

```text
shift
start_time
end_time
breaks
work_center
factory
```

---

# 31. Shift Types

```text
DAY
EVENING
NIGHT
CUSTOM
```

---

# 32. Production Calendar

Support:

```text
working days
holidays
planned shutdowns
maintenance windows
```

---

# 33. Capacity

Track:

```text
available hours
planned hours
actual hours
remaining capacity
```

---

# 34. Production Planning

Planning should consider:

```text
due date
priority
material availability
machine availability
operator availability
routing
estimated duration
```

---

# 35. Scheduling

Support scheduling at:

```text
production order level
work order level
work center level
machine level
```

---

# 36. Dispatching

Dispatch queue should show:

```text
job
priority
due date
material readiness
previous operation status
machine readiness
operator readiness
```

---

# 37. Ready-to-Run Rule

A work order is READY only if:

```text
previous required operation completed
material available
machine available
required tools available
required operator available
quality hold absent
```

---

# 38. Material Reservation

MES requests material reservations from Inventory.

It does not directly bypass inventory controls.

---

# 39. Material Reservation

Reserve:

```text
sheet
remnant
hardware
edge band
adhesive
packaging material
```

as required.

---

# 40. Material Issue

When material physically enters production:

```text
ISSUED
```

is recorded.

---

# 41. Material Consumption

Actual consumption must be recorded separately from reservation.

```text
RESERVED
 ↓
ISSUED
 ↓
CONSUMED
```

---

# 42. Material Return

Unused material may be:

```text
RETURNED
```

to inventory.

---

# 43. Scrap Material

Material lost during production is recorded as:

```text
SCRAP
```

with reason.

---

# 44. Panel Identity

Every manufactured panel must have a unique:

```text
panel_instance_id
```

---

# 45. Panel UID

Recommended:

```text
PNL-{factory}-{year}-{sequence}
```

Example:

```text
PNL-F01-26-000123
```

Actual format must be configurable.

---

# 46. Panel QR Code

QR payload should contain a non-sensitive identifier:

```text
panel_instance_id
```

or signed token.

Do not embed confidential customer data in QR codes.

---

# 47. Panel Barcode

Support:

```text
Code128
```

where factory hardware requires barcode instead of QR.

---

# 48. Panel Label

Label may contain:

```text
Panel ID
Project code
Furniture code
Room code
Panel description
Dimensions
Material
Thickness
Grain
Edge information
Operation status
QR/barcode
```

---

# 49. Label Version

Labels must reference:

```text
manufacturing revision
panel revision
```

---

# 50. Label Generation

API:

```http
POST /api/v1/mes/panels/{id}/label
```

---

# 51. Label Reprint

Authorized users may reprint labels.

Every reprint must be audited.

---

# 52. Duplicate Label Protection

The system must detect:

```text
duplicate active label
```

for the same panel.

---

# 53. Panel Lifecycle

Panel lifecycle:

```text
CREATED
 ↓
NESTED
 ↓
RELEASED
 ↓
MATERIAL_ALLOCATED
 ↓
CUT
 ↓
EDGE_BANDED
 ↓
DRILLED
 ↓
ROUTED
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
DISPATCH_READY
 ↓
DISPATCHED
```

---

# 54. Panel Hold

Any panel may be placed:

```text
ON_HOLD
```

with:

```text
reason
created_by
timestamp
```

---

# 55. Panel Release From Hold

Requires authorized user.

---

# 56. Scan Station

Each production station may have:

```text
station_id
station_type
work_center
machine
location
```

---

# 57. Station Types

```text
CUTTING
EDGE
CNC
DRILL
ASSEMBLY
QC
PACKING
DISPATCH
WAREHOUSE
```

---

# 58. Scan Workflow

Example:

```text
Scan Panel
 ↓
Validate Station
 ↓
Validate Current Status
 ↓
Validate Work Order
 ↓
Start Operation
 ↓
Execute
 ↓
Complete
```

---

# 59. Scan Start

API:

```http
POST /api/v1/mes/scans/start
```

Payload:

```json
{
  "panel_id": "PNL-001",
  "station_id": "CNC-01",
  "operator_id": "OP-12"
}
```

---

# 60. Scan Complete

```http
POST /api/v1/mes/scans/complete
```

---

# 61. Scan Validation

System checks:

```text
panel exists
panel belongs to tenant
panel belongs to factory
panel belongs to active production order
station supports operation
operator authorized
previous operation complete
panel not on hold
```

---

# 62. Invalid Scan

Return:

```text
code
message
current status
expected operation
next allowed action
```

---

# 63. Offline Scanning

Future-capable architecture should support offline scanning.

Store locally:

```text
scan_id
device_id
timestamp
panel_id
station_id
operator_id
event
```

Sync when network returns.

---

# 64. Offline Conflict

If a scan conflicts with server state:

```text
CONFLICT
```

must be created rather than silently overwriting the server state.

---

# 65. Production Events

MES should use immutable production events:

```text
PANEL_CREATED
PANEL_SCANNED
OPERATION_STARTED
OPERATION_COMPLETED
PANEL_MOVED
PANEL_HOLD
PANEL_RELEASED
PANEL_SCRAPPED
PANEL_REWORKED
QUALITY_PASSED
QUALITY_FAILED
PACKED
DISPATCHED
```

---

# 66. Event Structure

Every event:

```text
event_id
event_type
tenant_id
factory_id
entity_type
entity_id
operator_id
station_id
timestamp
payload
```

---

# 67. Event Immutability

Production events must not be edited.

Corrections create compensating events.

---

# 68. Event Timestamp

Store:

```text
server_timestamp
device_timestamp
```

when mobile/offline scanning is supported.

---

# 69. WIP

Track work-in-progress by:

```text
production order
work order
panel
work center
station
```

---

# 70. WIP State

Example:

```text
CUTTING = 120
EDGE = 85
CNC = 72
ASSEMBLY = 30
QC = 12
PACKING = 5
```

---

# 71. WIP Location

Each panel should have:

```text
current_station
current_work_center
current_status
```

---

# 72. Panel Movement

Track movement:

```text
FROM
TO
operator
timestamp
reason
```

---

# 73. WIP Queue

Each work center should show:

```text
waiting
ready
in progress
blocked
completed
```

---

# 74. Production Execution

Operators must be able to:

```text
Start
Pause
Resume
Complete
Reject
Scrap
Rework
Hold
```

according to permissions.

---

# 75. Operation Start

Record:

```text
operator
machine
station
start time
work order
panel
```

---

# 76. Operation Pause

Record:

```text
reason
timestamp
operator
```

---

# 77. Operation Resume

Record:

```text
timestamp
operator
```

---

# 78. Operation Complete

Record:

```text
completed quantity
accepted quantity
scrap quantity
rework quantity
actual duration
operator
machine
```

---

# 79. Partial Completion

Support partial completion.

Example:

```text
100 panels planned
80 completed
20 remaining
```

---

# 80. Quantity Tracking

Track:

```text
planned_qty
started_qty
completed_qty
accepted_qty
rework_qty
scrap_qty
remaining_qty
```

---

# 81. Panel-Level vs Quantity-Level

Panel-level traceability is mandatory for manufactured furniture components.

Bulk quantity tracking may be used for consumables.

---

# 82. Machine Integration

MES should accept machine events where integration exists:

```text
PROGRAM_LOADED
PROGRAM_STARTED
PROGRAM_COMPLETED
MACHINE_STOPPED
MACHINE_ALARM
```

---

# 83. CNC Integration

CNC program must be linked to:

```text
work order
panel
nesting revision
machine
```

---

# 84. CNC Program Validation

MES must require:

```text
CNC program status = RELEASED
```

before execution where configured.

---

# 85. Wrong Program Protection

If machine/operator scans a panel and selects an incompatible CNC program:

```text
BLOCK
```

with explanation.

---

# 86. Machine Assignment

A work order may specify:

```text
preferred machine
allowed machine group
```

---

# 87. Machine Substitution

If configured, MES may substitute another compatible machine.

Compatibility requires:

```text
material
dimensions
operation
tool capability
axis capability
machine limits
```

---

# 88. Machine Capability Matrix

Store:

```text
machine
operation
material
thickness range
tool requirement
capacity
```

---

# 89. Downtime

Track machine downtime:

```text
start
end
duration
machine
reason
operator
```

---

# 90. Downtime Categories

```text
BREAKDOWN
SETUP
TOOL_CHANGE
MATERIAL_SHORTAGE
QUALITY_HOLD
NO_OPERATOR
NO_JOB
MAINTENANCE
POWER_FAILURE
OTHER
```

---

# 91. Downtime Reason Master

Reasons must be configurable.

---

# 92. Downtime Approval

Some downtime categories may require supervisor approval.

---

# 93. Availability

Calculate:

```text
planned production time
- downtime
```

---

# 94. Performance

Track:

```text
planned cycle time
actual cycle time
```

---

# 95. Quality

MES must support quality gates at each applicable stage.

Example:

```text
CUT → dimensional QC
EDGE → edge QC
CNC → hole/feature QC
ASSEMBLY → fit/visual QC
PACK → completeness QC
```

---

# 96. Quality Plan

Quality plan contains:

```text
inspection stage
check item
measurement type
target
tolerance
sampling
pass criteria
```

---

# 97. Quality Check Types

Support:

```text
PASS_FAIL
NUMERIC
TEXT
PHOTO
CHECKLIST
DIMENSION
WEIGHT
COUNT
```

---

# 98. Dimensional Inspection

Example:

```text
Target = 600 mm
Tolerance = ±0.5 mm
Measured = 600.2 mm
Result = PASS
```

---

# 99. Inspection Record

Fields:

```text
inspection_id
panel_id
work_order_id
quality_plan_id
operator
inspector
measurement
result
timestamp
```

---

# 100. Sampling

Support:

```text
100%
FIRST_PIECE
RANDOM
LOT
AQL-compatible future model
```

---

# 101. First Piece Approval

For configured processes:

```text
first piece must pass
```

before batch continuation.

---

# 102. Quality Failure

If failed:

```text
panel = QUALITY_HOLD
```

and workflow must determine:

```text
REWORK
SCRAP
REPLACE
ENGINEERING_REVIEW
```

---

# 103. Rework

Create a rework order linked to:

```text
original work order
original panel
failure reason
rework route
```

---

# 104. Rework Traceability

Do not replace the original production history.

Track:

```text
original
→ failed
→ rework
→ reinspection
```

---

# 105. Scrap

Scrap event must include:

```text
panel
reason
quantity
material
operator
station
timestamp
```

---

# 106. Scrap Reasons

Examples:

```text
DIMENSION_ERROR
MACHINE_ERROR
MATERIAL_DEFECT
OPERATOR_ERROR
DESIGN_ERROR
DAMAGE
QUALITY_FAILURE
LOST_PANEL
OTHER
```

---

# 107. Scrap Authorization

Configurable threshold:

```text
operator may scrap
supervisor approval required
```

---

# 108. Replacement Panel

If a panel is scrapped:

```text
replacement panel
```

may be generated from the same engineering component.

---

# 109. Replacement Identity

Replacement must receive a new:

```text
panel_instance_id
```

but retain:

```text
replacement_for_panel_id
```

---

# 110. Replacement Revision

If replacement uses same engineering revision:

```text
same manufacturing revision
```

If engineering changes:

```text
new manufacturing revision
```

must be created.

---

# 111. Shortage

If a replacement requires material not available:

```text
MATERIAL_SHORTAGE
```

must be raised.

---

# 112. Exception Management

MES must create exceptions for:

```text
material shortage
machine breakdown
quality failure
missing panel
wrong panel
wrong program
late production
operator shortage
tool shortage
```

---

# 113. Exception Severity

```text
INFO
WARNING
HIGH
CRITICAL
```

---

# 114. Exception Assignment

Exceptions may be assigned to:

```text
operator
supervisor
production manager
quality manager
engineering
```

---

# 115. Exception Lifecycle

```text
OPEN
ACKNOWLEDGED
IN_PROGRESS
RESOLVED
CLOSED
```

---

# 116. Production Holds

Production order may be held because of:

```text
engineering issue
material issue
customer change
quality issue
machine issue
```

---

# 117. Hold Propagation

If a project revision invalidates released manufacturing data:

```text
affected work orders
```

must be marked:

```text
BLOCKED
```

where configured.

---

# 118. Engineering Change

MES must consume engineering change events.

Example:

```text
Manufacturing Revision R4
→ R5
```

---

# 119. ECO Impact

Engineering change should identify:

```text
affected panels
affected work orders
affected CNC programs
affected stock
```

---

# 120. Change Freeze

Released production should be protected from silent engineering changes.

---

# 121. Approval Workflow

Production release may require:

```text
Production Manager
Manufacturing Engineer
Quality
```

according to configuration.

---

# 122. Production Dashboard

Dashboard must show:

```text
Orders
Due today
Late
In progress
Blocked
Completed
WIP
Scrap
Rework
Quality failures
Machine downtime
```

---

# 123. Shop Floor Dashboard

Show:

```text
Current station
Current operator
Current job
Next job
Queue
Material readiness
Quality holds
```

---

# 124. Supervisor Dashboard

Show:

```text
work center utilization
operator productivity
machine utilization
downtime
WIP
rework
scrap
on-time production
```

---

# 125. Factory Dashboard

Show:

```text
OEE
throughput
cycle time
downtime
quality
scrap
rework
order completion
```

---

# 126. OEE

Support:

```text
Availability
Performance
Quality
OEE
```

Formula:

```text
OEE = Availability × Performance × Quality
```

---

# 127. Availability

Example:

```text
Availability =
Run Time / Planned Production Time
```

---

# 128. Performance

Example:

```text
Performance =
Ideal Cycle Time × Total Count / Run Time
```

---

# 129. Quality

Example:

```text
Quality =
Good Count / Total Count
```

---

# 130. Production KPI

Track:

```text
throughput
cycle time
lead time
on-time completion
first-pass yield
scrap rate
rework rate
```

---

# 131. Operator KPI

Optional metrics:

```text
completed operations
cycle time
quality pass rate
rework rate
```

Avoid using metrics as punitive automated employment decisions.

---

# 132. Machine KPI

Track:

```text
runtime
idle time
downtime
setup time
cycle count
alarm count
```

---

# 133. Material KPI

Track:

```text
planned consumption
actual consumption
scrap
waste
variance
```

---

# 134. Traceability Search

Users must be able to search by:

```text
Project
Production Order
Panel ID
Furniture ID
Room
QR code
CNC Program
Machine
Operator
Work Order
Date
```

---

# 135. Panel Genealogy View

For a panel show:

```text
Engineering
Material
Nesting
CNC
Production
Quality
Rework
Packing
Dispatch
```

as a timeline.

---

# 136. Production Timeline

Example:

```text
08:32 CUT START
08:34 CUT COMPLETE
09:01 EDGE START
09:02 EDGE COMPLETE
09:15 CNC START
09:18 CNC COMPLETE
10:10 QC PASS
11:20 PACKED
```

---

# 137. Work Order Timeline

Show:

```text
planned
assigned
started
paused
resumed
completed
```

---

# 138. Audit Timeline

Every important event must be visible to authorized users.

---

# 139. Assembly

Assembly work orders may be:

```text
panel assembly
furniture assembly
room assembly
```

---

# 140. Assembly Kit

Generate assembly kit containing:

```text
panels
hardware
accessories
instructions
```

---

# 141. Assembly Checklist

Support:

```text
component present
hardware present
dimensions
alignment
visual inspection
functional check
```

---

# 142. Hardware Tracking

Track:

```text
hardware SKU
quantity planned
quantity issued
quantity consumed
quantity returned
```

---

# 143. Missing Hardware

Create:

```text
MATERIAL_SHORTAGE
```

exception.

---

# 144. Packing

Packing starts only when:

```text
all required components complete
QC passed
hardware complete
```

unless partial packing is allowed.

---

# 145. Packing Unit

A packing unit may represent:

```text
box
carton
bundle
pallet
crate
```

---

# 146. Packing Record

Fields:

```text
package_id
production_order_id
contents
weight
dimensions
operator
timestamp
```

---

# 147. Package QR Code

Generate QR for:

```text
package_id
```

---

# 148. Package Contents

Track:

```text
panel IDs
furniture IDs
hardware kits
documents
```

---

# 149. Packing Checklist

Support:

```text
all panels present
hardware present
labels present
instructions present
protection applied
package sealed
```

---

# 150. Dispatch Readiness

Order is DISPATCH_READY only when:

```text
all required packages complete
quality passed
packing complete
dispatch documents ready
```

---

# 151. Dispatch

Record:

```text
dispatch_id
production_order_id
packages
vehicle
carrier
driver/reference
dispatch timestamp
```

---

# 152. Dispatch Status

```text
READY
STAGED
LOADED
DISPATCHED
DELIVERED
CANCELLED
```

---

# 153. Partial Dispatch

Support partial dispatch.

Track exactly which:

```text
packages
furniture
rooms
```

were dispatched.

---

# 154. Production Order Closure

Order closes only when:

```text
all required production complete
all exceptions resolved
quality complete
packing complete
dispatch complete
```

or authorized partial closure.

---

# 155. Warehouse Integration

MES should integrate with inventory/warehouse for:

```text
material issue
material return
finished goods
packing
dispatch
```

---

# 156. Inventory Boundary

Inventory remains source of truth for:

```text
stock quantity
stock location
material transactions
```

MES owns:

```text
production consumption context
```

---

# 157. Notifications

Support notifications for:

```text
material shortage
machine breakdown
quality failure
late order
blocked work order
rework
production completion
dispatch readiness
```

---

# 158. Notification Channels

Architecture should support:

```text
in-app
email
SMS/WhatsApp through external provider
```

Provider integration is outside the core MES.

---

# 159. Alerts

Alert example:

```text
Production Order PO-102 is at risk of missing due date.
Reason:
CNC machine unavailable for 4 hours.
```

---

# 160. Escalation

Configurable escalation:

```text
operator
→ supervisor
→ production manager
```

---

# 161. API Architecture

All APIs:

```text
/api/v1/mes/
```

---

# 162. Production Order APIs

```http
POST /api/v1/mes/production-orders
GET /api/v1/mes/production-orders
GET /api/v1/mes/production-orders/{id}
PATCH /api/v1/mes/production-orders/{id}
POST /api/v1/mes/production-orders/{id}/approve
POST /api/v1/mes/production-orders/{id}/release
POST /api/v1/mes/production-orders/{id}/hold
POST /api/v1/mes/production-orders/{id}/resume
POST /api/v1/mes/production-orders/{id}/close
```

---

# 163. Work Order APIs

```http
POST /api/v1/mes/work-orders
GET /api/v1/mes/work-orders
GET /api/v1/mes/work-orders/{id}
POST /api/v1/mes/work-orders/{id}/assign
POST /api/v1/mes/work-orders/{id}/start
POST /api/v1/mes/work-orders/{id}/pause
POST /api/v1/mes/work-orders/{id}/resume
POST /api/v1/mes/work-orders/{id}/complete
POST /api/v1/mes/work-orders/{id}/rework
POST /api/v1/mes/work-orders/{id}/scrap
```

---

# 164. Scan APIs

```http
POST /api/v1/mes/scans/start
POST /api/v1/mes/scans/complete
POST /api/v1/mes/scans/move
POST /api/v1/mes/scans/hold
POST /api/v1/mes/scans/release
```

---

# 165. Panel APIs

```http
GET /api/v1/mes/panels/{id}
GET /api/v1/mes/panels/{id}/timeline
GET /api/v1/mes/panels/{id}/genealogy
GET /api/v1/mes/panels/{id}/status
GET /api/v1/mes/panels/{id}/label
```

---

# 166. Quality APIs

```http
GET /api/v1/mes/quality/plans
POST /api/v1/mes/quality/inspections
GET /api/v1/mes/quality/inspections/{id}
POST /api/v1/mes/quality/inspections/{id}/pass
POST /api/v1/mes/quality/inspections/{id}/fail
```

---

# 167. Downtime APIs

```http
POST /api/v1/mes/downtime
GET /api/v1/mes/downtime
PATCH /api/v1/mes/downtime/{id}
```

---

# 168. Machine APIs

```http
GET /api/v1/mes/machines
GET /api/v1/mes/machines/{id}
POST /api/v1/mes/machines/{id}/status
```

---

# 169. Packing APIs

```http
POST /api/v1/mes/packages
GET /api/v1/mes/packages/{id}
POST /api/v1/mes/packages/{id}/complete
```

---

# 170. Dispatch APIs

```http
POST /api/v1/mes/dispatch
GET /api/v1/mes/dispatch
GET /api/v1/mes/dispatch/{id}
POST /api/v1/mes/dispatch/{id}/complete
```

---

# 171. Dashboard APIs

```http
GET /api/v1/mes/dashboard/factory
GET /api/v1/mes/dashboard/production
GET /api/v1/mes/dashboard/work-center
GET /api/v1/mes/dashboard/operator
GET /api/v1/mes/dashboard/machine
```

---

# 172. Exception APIs

```http
GET /api/v1/mes/exceptions
POST /api/v1/mes/exceptions/{id}/assign
POST /api/v1/mes/exceptions/{id}/acknowledge
POST /api/v1/mes/exceptions/{id}/resolve
POST /api/v1/mes/exceptions/{id}/close
```

---

# 173. Database Tables

Minimum:

```text
mes_production_orders
mes_production_order_items
mes_work_orders
mes_work_order_operations
mes_routings
mes_routing_versions
mes_routing_operations
mes_work_centers
mes_machines
mes_machine_events
mes_shifts
mes_shift_assignments
mes_operator_skills
mes_material_reservations
mes_material_issues
mes_material_consumption
mes_material_returns
mes_panel_instances
mes_panel_events
mes_panel_locations
mes_scan_stations
mes_scan_events
mes_quality_plans
mes_quality_checks
mes_quality_inspections
mes_rework_orders
mes_scrap_records
mes_replacement_panels
mes_exceptions
mes_downtime_records
mes_assembly_orders
mes_assembly_items
mes_packages
mes_package_items
mes_dispatch_orders
mes_dispatch_items
mes_production_events
mes_audit_logs
```

---

# 174. Production Order Table

Fields:

```text
id
tenant_id
factory_id
project_id
manufacturing_revision_id
bom_revision_id
nesting_revision_id
priority
planned_start
planned_end
status
created_by
approved_by
released_at
completed_at
```

---

# 175. Work Order Table

Fields:

```text
id
production_order_id
routing_operation_id
work_center_id
machine_id
operator_id
sequence
planned_qty
started_qty
completed_qty
accepted_qty
rework_qty
scrap_qty
status
planned_start
planned_end
actual_start
actual_end
```

---

# 176. Panel Instance Table

Fields:

```text
id
tenant_id
factory_id
production_order_id
component_id
logical_panel_id
replacement_for_panel_id
manufacturing_revision_id
nesting_placement_id
status
current_station_id
current_work_center_id
label_version
created_at
```

---

# 177. Panel Event Table

Fields:

```text
id
panel_id
event_type
operator_id
station_id
machine_id
work_order_id
timestamp
payload_json
```

---

# 178. Scan Event Table

Fields:

```text
id
scan_id
panel_id
station_id
operator_id
device_id
event_type
device_timestamp
server_timestamp
sync_status
```

---

# 179. Quality Plan Table

Fields:

```text
id
factory_id
operation_code
version
status
```

---

# 180. Quality Check Table

Fields:

```text
id
quality_plan_id
check_code
check_type
target
min_value
max_value
required
```

---

# 181. Inspection Table

Fields:

```text
id
work_order_id
panel_id
quality_plan_id
inspector_id
result
notes
photo_file_id
created_at
```

---

# 182. Scrap Table

Fields:

```text
id
panel_id
work_order_id
reason_code
quantity
material_id
approved_by
created_by
created_at
```

---

# 183. Rework Table

Fields:

```text
id
original_work_order_id
panel_id
reason_code
rework_routing_id
status
created_at
completed_at
```

---

# 184. Replacement Panel Table

Fields:

```text
id
original_panel_id
replacement_panel_id
reason
created_by
created_at
```

---

# 185. Machine Event Table

Fields:

```text
id
machine_id
event_type
reason_code
started_at
ended_at
duration
operator_id
payload_json
```

---

# 186. Exception Table

Fields:

```text
id
tenant_id
factory_id
production_order_id
work_order_id
panel_id
type
severity
status
assigned_to
message
created_at
resolved_at
```

---

# 187. Package Table

Fields:

```text
id
production_order_id
package_number
package_type
weight
length
width
height
status
created_by
created_at
```

---

# 188. Package Item Table

Fields:

```text
id
package_id
panel_id
furniture_id
hardware_kit_id
quantity
```

---

# 189. Dispatch Table

Fields:

```text
id
production_order_id
status
carrier
vehicle_reference
driver_reference
dispatch_date
created_by
```

---

# 190. Event Store

If an event architecture is implemented, maintain:

```text
mes_production_events
```

as append-only.

---

# 191. Frontend Architecture

Recommended:

```text
/src/mes/

domain/
  ProductionOrder.js
  WorkOrder.js
  Panel.js
  Station.js
  Machine.js
  QualityInspection.js
  Package.js

production/
  ProductionDashboard.js
  ProductionPlanner.js
  WorkOrderBoard.js
  DispatchQueue.js

shopfloor/
  Scanner.js
  StationView.js
  OperatorView.js
  PanelTimeline.js

quality/
  QualityPlan.js
  InspectionForm.js
  QualityDashboard.js

machine/
  MachineDashboard.js
  Downtime.js

packing/
  PackingStation.js
  PackageBuilder.js

dispatch/
  DispatchBoard.js

reports/
  ProductionReport.js
  WipReport.js
  QualityReport.js
  ScrapReport.js
  OeeReport.js
```

---

# 192. PHP Architecture

Recommended:

```text
src/
  MES/
    Domain/
    Production/
    WorkOrders/
    Routing/
    Scheduling/
    WorkCenters/
    Machines/
    Operators/
    Panels/
    Scanning/
    Quality/
    Rework/
    Scrap/
    Assembly/
    Packing/
    Dispatch/
    Events/
    Exceptions/
    Reporting/
    Repositories/
    Policies/
    DTO/
```

Core services:

```text
ProductionOrderService
WorkOrderService
RoutingService
SchedulingService
DispatchingService
PanelTrackingService
ScanService
ProductionEventService
QualityService
ReworkService
ScrapService
MachineEventService
DowntimeService
PackingService
DispatchService
ExceptionService
OeeService
```

---

# 193. Production State Machine

Production orders should use explicit state transitions.

Example:

```text
DRAFT
 ↓
PLANNED
 ↓
APPROVED
 ↓
RELEASED
 ↓
IN_PROGRESS
 ↓
COMPLETED
 ↓
CLOSED
```

Invalid transitions must be rejected.

---

# 194. Work Order State Machine

```text
QUEUED
 ↓
READY
 ↓
ASSIGNED
 ↓
IN_PROGRESS
 ↓
COMPLETED
```

Alternative:

```text
IN_PROGRESS
 ↓
PAUSED
 ↓
IN_PROGRESS
```

or:

```text
IN_PROGRESS
 ↓
BLOCKED
 ↓
READY
```

---

# 195. Panel State Machine

```text
CREATED
 ↓
RELEASED
 ↓
CUT
 ↓
EDGE_BANDED
 ↓
DRILLED
 ↓
ASSEMBLY_READY
 ↓
ASSEMBLED
 ↓
QC_PASSED
 ↓
PACKED
 ↓
DISPATCHED
```

Exceptions:

```text
ON_HOLD
REWORK
SCRAPPED
REPLACED
```

---

# 196. State Transition Rules

Every state transition must verify:

```text
authorization
current state
required predecessor
required quality gate
material readiness
```

---

# 197. No Silent State Changes

All state changes must create:

```text
production event
```

---

# 198. Production Event Example

```json
{
  "event_type": "OPERATION_COMPLETED",
  "panel_id": "PNL-001",
  "work_order_id": "WO-100",
  "operator_id": "OP-12",
  "station_id": "CNC-01",
  "timestamp": "2026-08-10T10:30:00+05:30",
  "payload": {
    "accepted": 1,
    "scrap": 0,
    "rework": 0
  }
}
```

---

# 199. Shop Floor UI

The shop-floor UI should be:

```text
large touch targets
minimal navigation
high contrast
fast scanning
few clicks
clear status
```

---

# 200. Operator Home Screen

Show:

```text
Current station
Operator
Current job
Queue
Next panel
Alerts
```

---

# 201. Scan Screen

Primary action:

```text
SCAN QR / BARCODE
```

Then display:

```text
Panel
Furniture
Room
Operation
Machine
Expected action
```

---

# 202. Start Operation Screen

Show:

```text
Panel
Dimensions
Material
Operation
CNC Program
Tool
Machine
```

---

# 203. Completion Screen

Ask:

```text
Accepted?
Rework?
Scrap?
Quantity?
Quality required?
```

---

# 204. Quality Screen

Show:

```text
target
tolerance
measured
result
```

---

# 205. Supervisor Screen

Show:

```text
queues
blocked jobs
machine states
quality failures
late orders
exceptions
```

---

# 206. Production Board

Use columns:

```text
READY
IN PROGRESS
PAUSED
BLOCKED
QC
COMPLETED
```

---

# 207. Search and Filters

Support:

```text
project
order
room
furniture
panel
machine
operator
station
status
priority
date
```

---

# 208. Reporting

Minimum reports:

```text
Production Summary
WIP
Panel Traceability
Work Order Status
Machine Utilization
Downtime
OEE
Quality
Scrap
Rework
Material Consumption
On-Time Delivery
Packing
Dispatch
```

---

# 209. Production Summary

Show:

```text
orders planned
orders released
orders completed
orders late
panels planned
panels completed
```

---

# 210. WIP Report

Show:

```text
work center
queued
in progress
blocked
completed
aging
```

---

# 211. Aging

Calculate time a work order has remained:

```text
READY
BLOCKED
IN_PROGRESS
```

---

# 212. Quality Report

Show:

```text
inspection count
pass
fail
first-pass yield
rework
scrap
```

---

# 213. Scrap Report

Show:

```text
scrap quantity
scrap rate
reason
machine
operator
material
project
```

---

# 214. Rework Report

Show:

```text
rework count
reason
operation
time lost
material lost
```

---

# 215. Material Consumption Report

Compare:

```text
planned
issued
consumed
scrapped
returned
```

---

# 216. OEE Report

By:

```text
machine
work center
shift
day
week
month
```

---

# 217. On-Time Production

Calculate:

```text
completed_by_due_date
/
total_completed
```

---

# 218. Alerts

Dashboard must highlight:

```text
late
blocked
critical quality
machine breakdown
material shortage
```

---

# 219. Search Genealogy

Example:

```text
Search Panel PNL-001
```

Result:

```text
Project: P-100
Room: Kitchen
Furniture: Base Cabinet 03
Material: 18mm MDF
Sheet: S-220
Nesting: N-88
CNC: CNC-201-v3
Cut: 08:30
Edge: 09:10
Drill: 09:45
QC: PASS 10:20
Pack: BOX-12
```

---

# 220. Security

MES controls production-critical operations.

Require:

```text
authentication
authorization
tenant isolation
factory isolation
audit
state validation
```

---

# 221. RBAC Permissions

Minimum:

```text
mes.view
mes.create_order
mes.approve_order
mes.release_order
mes.plan
mes.schedule
mes.assign
mes.execute
mes.scan
mes.hold
mes.rework
mes.scrap
mes.quality
mes.manage_routing
mes.manage_work_centers
mes.manage_machines
mes.manage_shifts
mes.manage_operators
mes.pack
mes.dispatch
mes.reports
mes.admin
```

---

# 222. Operator Permissions

Typical:

```text
scan
start
pause
complete
quality entry
hold request
```

Scrap/rework may require elevated permissions.

---

# 223. Supervisor Permissions

Typical:

```text
assign
override
approve scrap
approve rework
release hold
change priority
```

---

# 224. Quality Permissions

Typical:

```text
inspect
pass
fail
hold
release quality hold
```

---

# 225. Production Manager Permissions

Typical:

```text
plan
release
schedule
change priority
approve exceptions
close orders
```

---

# 226. Tenant Isolation

Every MES query must derive:

```text
tenant_id
```

from authenticated server context.

Never trust browser-supplied tenant IDs.

---

# 227. Factory Isolation

Users should only see:

```text
authorized factories
```

and their production data.

---

# 228. Audit

Audit:

```text
order creation
release
assignment
scan
state transition
quality
scrap
rework
machine status
downtime
packing
dispatch
```

---

# 229. File Attachments

Quality may attach:

```text
photos
documents
inspection evidence
```

through centralized file storage.

---

# 230. Photo Evidence

Quality failure may require:

```text
photo_required = true
```

before submission.

---

# 231. Production Notes

Operators can record:

```text
operator_note
```

but notes do not replace structured events.

---

# 232. Exception Notes

Exceptions require:

```text
resolution_note
```

before closure.

---

# 233. Data Retention

Production and traceability records should be retained according to:

```text
tenant policy
factory policy
legal/business requirements
```

---

# 234. Backup

MES database must support:

```text
daily backup
point-in-time recovery
```

according to infrastructure capability.

---

# 235. Concurrency

Prevent:

```text
two operators starting same exclusive operation
```

through transactional locking/state validation.

---

# 236. Duplicate Scan Protection

Repeated identical scans within a configurable short interval should be detected.

Do not blindly create duplicate events.

---

# 237. Idempotency

Scan and production commands should support:

```text
Idempotency-Key
```

---

# 238. Offline Synchronization

Future-capable API should include:

```text
device_id
client_event_id
client_timestamp
server_timestamp
sync_status
```

---

# 239. Conflict Resolution

Conflict policy:

```text
server authoritative
```

unless a controlled reconciliation workflow exists.

---

# 240. Performance Targets

Recommended:

```text
QR scan response < 500 ms
Panel lookup < 500 ms
Start operation < 1 sec
Complete operation < 1 sec
Dashboard < 2 sec
```

Large reports may be asynchronous.

---

# 241. Large Factory Scaling

Architecture must support:

```text
multiple factories
multiple work centers
multiple machines
thousands of panels/day
```

without redesigning the data model.

---

# 242. Queue Architecture

Large operations should support background jobs for:

```text
production planning
bulk panel creation
label generation
report generation
OEE calculation
dispatch documents
```

---

# 243. Caching

Cache:

```text
machine master
work center master
routing
quality plans
operator skills
```

---

# 244. Real-Time Updates

Architecture should support future:

```text
WebSocket
Server-Sent Events
polling
```

for:

```text
machine state
WIP
production events
alerts
```

---

# 245. Event Bus

Future-capable event bus can publish:

```text
production.order.released
work_order.started
work_order.completed
panel.completed
quality.failed
machine.breakdown
package.completed
dispatch.completed
```

---

# 246. Integration With Manufacturing Engine

Manufacturing provides:

```text
BOM
panel list
routing requirements
manufacturing revision
```

MES creates:

```text
production order
work orders
panel instances
```

---

# 247. Integration With Nesting

Nesting provides:

```text
nesting revision
sheet assignment
panel placement
```

MES tracks:

```text
sheet
panel
production job
```

---

# 248. Integration With CNC/CAM

CNC provides:

```text
CNC program
machine
tool
operation
program revision
```

MES tracks:

```text
program execution
machine status
operator
start
complete
failure
```

---

# 249. Integration With Inventory

Inventory provides:

```text
stock
reservation
issue
return
consumption
```

MES provides:

```text
production context
```

---

# 250. Integration With Quality

Quality provides:

```text
inspection plans
inspection results
```

MES controls:

```text
quality gates
panel holds
release
```

---

# 251. Integration With Packing

MES provides:

```text
completed furniture/components
```

Packing creates:

```text
packages
package contents
package labels
```

---

# 252. Integration With Dispatch

Dispatch consumes:

```text
dispatch-ready packages
```

and returns:

```text
loaded
dispatched
delivered
```

status.

---

# 253. Integration With Pricing

MES may provide actual:

```text
material consumption
labor duration
machine time
scrap
```

to costing.

---

# 254. Cost Capture

Optional MES cost records:

```text
labor_time
machine_time
material_consumption
scrap_cost
rework_cost
```

---

# 255. Actual vs Planned

Every production stage should compare:

```text
planned
actual
variance
```

---

# 256. Time Variance

```text
actual_duration
-
planned_duration
```

---

# 257. Quantity Variance

```text
planned_quantity
-
accepted_quantity
```

---

# 258. Material Variance

```text
planned_consumption
-
actual_consumption
```

---

# 259. Production Analytics

Support drill-down:

```text
Factory
 ↓
Work Center
 ↓
Machine
 ↓
Shift
 ↓
Operator
 ↓
Work Order
 ↓
Panel
```

---

# 260. Dashboard Filters

Support:

```text
factory
work center
machine
shift
date
project
customer
status
```

---

# 261. Production Calendar

Show:

```text
planned orders
production dates
machine capacity
maintenance
holidays
```

---

# 262. Bottleneck Detection

Identify:

```text
work center with highest queue
machine with highest utilization
operation with highest cycle variance
```

---

# 263. Bottleneck Alert

Example:

```text
CNC Work Center is at 96% planned utilization.
Order PO-1002 is at risk.
```

---

# 264. Late Order Detection

Flag if:

```text
estimated completion > due date
```

---

# 265. Production Risk

Risk score may consider:

```text
material readiness
machine availability
queue
quality failure
rework
due date
```

---

# 266. Production Forecast

Estimate:

```text
completion date
```

based on:

```text
remaining work
capacity
current queue
```

---

# 267. Production Completion

Production order completion must validate:

```text
required quantity
required quality
required operations
```

---

# 268. Partial Completion

Support:

```text
PARTIALLY_COMPLETED
```

where customer/project rules allow.

---

# 269. Cancellation

Cancelled orders retain history.

Never hard-delete production events.

---

# 270. Soft Delete

Master records may use:

```text
status = INACTIVE
```

rather than destructive delete.

---

# 271. API Error Contract

Standard error:

```json
{
  "success": false,
  "error": {
    "code": "MES_INVALID_STATE",
    "message": "Panel cannot start CNC because cutting is incomplete.",
    "details": {}
  }
}
```

---

# 272. API Success Contract

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

---

# 273. Pagination

All list APIs support:

```text
page
limit
sort
filter
```

---

# 274. Filtering

Support:

```text
status
factory
work_center
machine
operator
date range
priority
project
```

---

# 275. API Authorization

Every endpoint must validate:

```text
authentication
tenant
factory
RBAC
resource ownership
state
```

---

# 276. Database Indexes

Important indexes:

```text
tenant_id
factory_id
production_order_id
work_order_id
panel_id
status
machine_id
work_center_id
operator_id
created_at
event_type
```

---

# 277. Database Transactions

Transactions required for:

```text
start operation
complete operation
scrap
rework
material issue
package completion
dispatch
```

---

# 278. Atomic State Changes

State transition and production event creation should occur atomically where possible.

---

# 279. Data Integrity

Foreign keys should be used for critical relationships.

---

# 280. Production Event Ordering

Events should include:

```text
sequence
timestamp
```

to support deterministic reconstruction.

---

# 281. Event Reconstruction

The system should be able to reconstruct current panel state from:

```text
latest authoritative state
+
event history
```

---

# 282. Event Retention

Production events should be retained according to factory policy.

---

# 283. Testing — Production

Test:

```text
create order
approve
release
start
pause
resume
complete
close
```

---

# 284. Testing — Panel

Test:

```text
scan
move
start
complete
hold
release
rework
scrap
replace
```

---

# 285. Testing — Quality

Test:

```text
pass
fail
hold
rework
reinspection
```

---

# 286. Testing — Inventory

Test:

```text
reserve
issue
consume
return
scrap
```

---

# 287. Testing — Concurrency

Test:

```text
two operators scan same panel
two users assign same work order
two users complete same operation
```

Only one valid transition should win.

---

# 288. Testing — Security

Test:

```text
tenant isolation
factory isolation
RBAC
resource authorization
```

---

# 289. Testing — Offline

If implemented:

```text
offline event
sync
duplicate event
conflict
retry
```

---

# 290. Testing — Integration

End-to-end test:

```text
Design
→ Manufacturing
→ Nesting
→ CNC
→ MES
→ Quality
→ Packing
→ Dispatch
```

---

# 291. Acceptance Scenario

Example:

```text
Project: P001
Furniture: Kitchen
Panels: 100
```

Production flow:

```text
Production Order Released
        ↓
100 Panel Instances Created
        ↓
Cutting
        ↓
100 Cut
        ↓
Edge Banding
        ↓
100 Completed
        ↓
CNC
        ↓
100 Completed
        ↓
QC
        ↓
98 Pass
2 Fail
        ↓
2 Rework
        ↓
2 Pass
        ↓
Assembly
        ↓
Packing
        ↓
Dispatch Ready
```

MES must preserve the complete genealogy.

---

# 292. Acceptance — QR Tracking

Scan:

```text
PNL-001
```

must return:

```text
Project
Room
Furniture
Material
Dimensions
Current Operation
Next Operation
CNC Program
Quality Status
```

---

# 293. Acceptance — Wrong Station

If a panel requiring:

```text
EDGE
```

is scanned at:

```text
PACKING
```

the system must:

```text
BLOCK
```

and explain why.

---

# 294. Acceptance — Wrong CNC

If panel expects:

```text
CNC Program v3
```

and operator selects:

```text
v2
```

the system must block execution.

---

# 295. Acceptance — Quality Failure

If QC fails:

```text
panel = QUALITY_HOLD
```

and cannot proceed to packing until released.

---

# 296. Acceptance — Scrap

When a panel is scrapped:

```text
old panel = SCRAPPED
new replacement panel = CREATED
replacement_for_panel_id = old panel
```

---

# 297. Acceptance — Revision

If manufacturing revision changes:

```text
old production data remains immutable
affected work orders are identified
affected CNC programs are identified
affected panels are identified
```

---

# 298. Acceptance — Traceability

Given:

```text
Project
```

user must be able to find:

```text
Production Order
→ Work Orders
→ Panels
→ CNC
→ Machines
→ Operators
→ Quality
→ Packages
→ Dispatch
```

---

# 299. Acceptance — Performance

The system must support:

```text
10,000+ panel instances
1,000+ work orders
multiple work centers
multiple machines
multiple concurrent operators
```

without architectural redesign.

---

# 300. Cursor Pre-Implementation Analysis

Before coding, Cursor MUST inspect the existing codebase for:

```text
Project model
Furniture model
BOM
Manufacturing Engine
Nesting Engine
CNC/CAM
Material/Inventory
Users/RBAC
File storage
Notifications
Revision system
Existing production workflows
```

Cursor must produce:

```text
CURRENT MES CAPABILITIES
CURRENT PRODUCTION DATA MODEL
CURRENT PANEL TRACKING
CURRENT CNC INTEGRATION
CURRENT INVENTORY INTEGRATION
CURRENT QUALITY
CURRENT USER/RBAC
CURRENT DATABASE
CURRENT API
CURRENT UI
DUPLICATE LOGIC
MISSING CAPABILITIES
DATA MIGRATION REQUIREMENTS
INTEGRATION GAPS
TARGET MES ARCHITECTURE
```

Do not build duplicate:

```text
production order
work order
panel tracking
inventory
CNC
quality
```

modules if equivalent functionality already exists.

---

# 301. Cursor Implementation Sequence

## Phase 1 — Core MES Domain

```text
Production Order
Work Order
Routing
Work Center
Machine
Operator
```

## Phase 2 — Panel Traceability

```text
Panel Instance
Panel Lifecycle
Panel Events
QR/Barcode
Station
Scanning
```

## Phase 3 — Production Execution

```text
Start
Pause
Resume
Complete
Hold
Rework
Scrap
```

## Phase 4 — Material Integration

```text
Reservation
Issue
Consumption
Return
Scrap
```

## Phase 5 — CNC Integration

```text
Program
Machine
Execution
Machine Events
```

## Phase 6 — Quality

```text
Quality Plan
Inspection
Pass/Fail
Hold
Rework
```

## Phase 7 — Shop Floor

```text
Station UI
Operator UI
Supervisor UI
WIP
Queues
```

## Phase 8 — Packing

```text
Package
Package Contents
Labels
Completion
```

## Phase 9 — Dispatch

```text
Dispatch
Partial Dispatch
Vehicle
Carrier
Delivery
```

## Phase 10 — Analytics

```text
OEE
Downtime
Quality
Scrap
Rework
Production Variance
```

## Phase 11 — Governance

```text
RBAC
Audit
Revision
Exceptions
```

## Phase 12 — Scale

```text
Async
Real-time
Offline
Caching
Event Bus
```

---

# 302. Recommended MES Service Flow

```text
ProductionOrderService
        ↓
PlanningService
        ↓
WorkOrderService
        ↓
RoutingService
        ↓
DispatchingService
        ↓
PanelTrackingService
        ↓
ScanService
        ↓
ProductionExecutionService
        ↓
QualityService
        ↓
Rework/ScrapService
        ↓
PackingService
        ↓
DispatchService
```

Cross-cutting:

```text
EventService
AuditService
AuthorizationService
NotificationService
InventoryService
```

---

# 303. Final Architecture

```text
                        FMOS
                         │
       ┌─────────────────┼──────────────────┐
       ↓                 ↓                  ↓
    DESIGN          MANUFACTURING        INVENTORY
       │                 │                  │
       └─────────────────┼──────────────────┘
                         ↓
                      NESTING
                         ↓
                       CNC/CAM
                         ↓
                  ┌───────────────┐
                  │      MES      │
                  └───────────────┘
                         │
        ┌────────────────┼─────────────────┐
        ↓                ↓                 ↓
   PRODUCTION         QUALITY           MATERIAL
        │                │                 │
        ↓                ↓                 ↓
   WORK ORDERS       INSPECTION        CONSUMPTION
        │
        ↓
     SHOP FLOOR
        │
   ┌────┼────┬────┬──────┐
   ↓    ↓    ↓    ↓      ↓
 CUT  EDGE CNC ASSEMBLY  QC
                         │
                         ↓
                      PACKING
                         │
                         ↓
                      DISPATCH
```

---

# 304. Final Product Principle

The MES is the **operational nervous system** of the manufacturing side of the platform.

It must connect:

```text
Digital Design
      ↓
Digital Manufacturing Definition
      ↓
Digital Production Instruction
      ↓
Physical Panel
      ↓
Physical Process
      ↓
Quality Result
      ↓
Finished Product
      ↓
Package
      ↓
Dispatch
```

The most important rule is:

> **Every physical component must remain digitally traceable throughout its entire manufacturing lifecycle.**

From:

```text
Project
 ↓
Room
 ↓
Furniture
 ↓
Component
 ↓
Panel
 ↓
Material
 ↓
Nesting
 ↓
CNC
 ↓
Machine
 ↓
Operator
 ↓
Operation
 ↓
Quality
 ↓
Rework / Scrap
 ↓
Assembly
 ↓
Package
 ↓
Dispatch
```

The MES should therefore not be implemented as merely a **production dashboard**.

It must be implemented as a **transactional, revision-controlled, traceable shop-floor execution system** connecting the digital engineering model to physical factory execution.
