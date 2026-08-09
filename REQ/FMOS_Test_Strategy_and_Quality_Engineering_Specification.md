# FMOS — Comprehensive Test Strategy & Quality Engineering Specification

**Document ID:** TEST-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Product:** FMOS — End-to-End Interior Design, Furniture Engineering, Manufacturing & MES SaaS Platform  
**Technology:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + Canvas/SVG + Three.js/WebGL  
**Audience:** Cursor, Developers, QA Engineers, Automation Engineers, Product Owners, DevOps, Security, Manufacturing SMEs  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete quality engineering and test strategy for FMOS.

FMOS is not a conventional CRUD SaaS application. It combines:

```text
Multi-Tenant SaaS
CRM
Project Management
2D CAD
3D/BIM
Parametric Geometry
Furniture Engineering
Material Catalog
BOM/BOQ
Pricing
Client Approval
Manufacturing Engineering
Nesting Optimization
CNC/CAM
MES
QR/Panel Traceability
Quality
Packing
Dispatch
AI
White Label
Analytics
```

Testing must therefore validate not only screens and APIs, but also:

```text
Geometry correctness
Parametric rules
Manufacturing correctness
Material calculations
Pricing accuracy
Traceability
Revision integrity
Machine output correctness
Tenant isolation
RBAC
AI safety and determinism boundaries
Performance
Reliability
Offline synchronization
```

---

# 2. Quality Vision

FMOS must provide:

> **Confidence that a design created in FMOS can safely and accurately progress from customer requirement to physical manufactured product without loss of design intent, commercial correctness, manufacturing correctness, traceability, or tenant security.**

The test strategy therefore follows five quality dimensions:

```text
FUNCTIONAL CORRECTNESS
+
DATA INTEGRITY
+
ENGINEERING/MANUFACTURING CORRECTNESS
+
SECURITY/ISOLATION
+
PERFORMANCE/RELIABILITY
```

---

# 3. Quality Principles

## QP-001 — Test Early

Testing starts during:

```text
requirements
architecture
database design
API design
UI design
```

not after coding.

## QP-002 — Automate Repetitive Verification

Automate:

```text
unit
API
regression
database
security
calculation
geometry
manufacturing rules
```

where deterministic.

## QP-003 — Keep Human Judgment for Visual/Domain Validation

Human testing remains important for:

```text
3D visual quality
CAD usability
client presentation
AI usefulness
shop-floor usability
manufacturing SME validation
```

## QP-004 — Backend Is Authoritative

Frontend validation improves UX.

Backend validation determines correctness.

## QP-005 — Tenant Isolation Is Non-Negotiable

Every test involving tenant data must verify isolation.

## QP-006 — Manufacturing Data Must Be Deterministic

The same valid input and rule version should produce reproducible outputs unless an explicitly non-deterministic optimization/AI process is used.

## QP-007 — Revision Integrity

Approved/released historical artifacts must never be silently mutated.

---

# 4. Test Scope

Testing covers:

```text
Authentication
Tenant Management
RBAC
CRM
Projects
Building
2D CAD
3D/BIM
Furniture Engine
Materials
Hardware
BOM
BOQ
Pricing
Quotes
Client Approval
Manufacturing
Nesting
CNC/CAM
MES
QR
Panel Tracking
Quality
Packing
Dispatch
AI
Documents
Reports
White Label
Subscriptions
Integrations
Audit
Offline
Performance
Security
```

---

# 5. Out of Scope Unless Explicitly Added

The following require separate certification/validation if applicable:

```text
physical CNC machine safety certification
electrical safety certification
factory occupational safety certification
regulated industry compliance
third-party machine certification
legal/tax certification
```

FMOS must still validate its software outputs against defined machine/application specifications.

---

# 6. Test Levels

FMOS shall implement:

```text
L0 Static Validation
L1 Unit Testing
L2 Component Testing
L3 Service Testing
L4 API Testing
L5 Database Testing
L6 Integration Testing
L7 Contract Testing
L8 UI Testing
L9 E2E Testing
L10 Visual Testing
L11 Geometry Testing
L12 Manufacturing Algorithm Testing
L13 AI Evaluation
L14 Performance Testing
L15 Security Testing
L16 Reliability/Recovery Testing
L17 UAT
L18 Production Smoke Testing
```

---

# 7. Test Pyramid

Target distribution:

```text
             E2E / UAT
           /            \
       Integration      Visual
       /                     \
    API / Service / Geometry
    /                         \
 Unit / Calculation / Rules
```

Prefer a large deterministic unit/API suite over an unnecessarily large browser suite.

---

# 8. Test Types

## 8.1 Functional Testing

Verify business requirements.

## 8.2 Regression Testing

Verify previously working functionality remains working.

## 8.3 Smoke Testing

Verify build is testable.

## 8.4 Sanity Testing

Verify focused changes.

## 8.5 Exploratory Testing

Discover unexpected behavior.

## 8.6 Usability Testing

Verify workflows are understandable.

## 8.7 Accessibility Testing

Verify keyboard, focus, semantic and contrast requirements.

## 8.8 Compatibility Testing

Verify supported browsers/devices.

## 8.9 Security Testing

Verify authentication, authorization, isolation and secure handling.

## 8.10 Performance Testing

Verify response time, throughput and rendering.

## 8.11 Reliability Testing

Verify recovery from failures.

---

# 9. Test Environment Strategy

Required environments:

```text
LOCAL
DEV
QA
STAGING
UAT
PRODUCTION
```

Recommended:

```text
DEV
→ QA
→ STAGING
→ UAT
→ PROD
```

---

# 10. Environment Rules

## DEV

For rapid development.

May contain synthetic test data.

## QA

Controlled automated/manual testing.

## STAGING

Production-like configuration.

Required for:

```text
performance
security
integration
release candidate
```

## UAT

Business/customer validation.

## PROD

Only production-safe tests.

---

# 11. Test Data Strategy

Test data categories:

```text
Synthetic
Masked Production-like
Boundary
Invalid
Large-volume
Security
Manufacturing
Geometry
AI evaluation
```

Never use real customer confidential information unnecessarily.

---

# 12. Master Test Data Sets

Maintain reusable datasets:

```text
TD-001 Small Project
TD-002 Medium Project
TD-003 Large Project
TD-004 Complex Furniture
TD-005 Manufacturing Order
TD-006 Multi-Tenant
TD-007 Large Catalog
TD-008 Large BOM
TD-009 Large Nesting
TD-010 MES Workload
TD-011 AI Floorplan
TD-012 AI Furniture
TD-013 Failure Scenarios
```

---

# 13. Test Data Reset

Automated tests must be able to:

```text
create
seed
reset
delete
reseed
```

their test data.

Tests must not depend on execution order.

---

# 14. Test Naming Convention

Recommended:

```text
TEST-[MODULE]-[NUMBER]
```

Example:

```text
TEST-CAD-001
TEST-FURN-014
TEST-NEST-008
TEST-MES-021
```

Automation names should map to requirement IDs.

---

# 15. Requirement Traceability

Every requirement must map:

```text
Requirement
 ↓
User Story
 ↓
Test Case
 ↓
Automation
 ↓
Defect
```

Example:

```text
US-E09-003
 ↓
TC-FURN-003
 ↓
test_edit_furniture_dimensions
```

---

# 16. Test Case Template

Every important test case should contain:

```text
Test ID
Requirement ID
Title
Purpose
Preconditions
Test Data
Steps
Expected Result
Actual Result
Priority
Severity
Automation Status
Environment
Evidence
```

---

# 17. Severity

```text
S1 Critical
S2 High
S3 Medium
S4 Low
```

## S1

Examples:

```text
cross-tenant data exposure
incorrect CNC output causing production risk
corrupted project
financial calculation corruption
irrecoverable data loss
```

## S2

Major feature failure without catastrophic impact.

## S3

Normal functional defect.

## S4

Minor UI/cosmetic defect.

---

# 18. Priority

```text
P0 Must Pass
P1 Release Critical
P2 Important
P3 Optional
```

---

# 19. Defect Workflow

```text
New
 ↓
Triaged
 ↓
Assigned
 ↓
In Progress
 ↓
Fixed
 ↓
QA Retest
 ↓
Regression
 ↓
Closed
```

Possible:

```text
Rejected
Duplicate
Won't Fix
Deferred
Cannot Reproduce
```

---

# 20. Defect Required Information

Every defect must contain:

```text
Summary
Environment
Build
Requirement
Steps
Expected
Actual
Severity
Priority
Evidence
Logs
Correlation ID
```

---

# 21. Entry Criteria

Testing begins when:

```text
build deployable
database migration available
environment available
requirements identified
test data available
dependencies available
```

---

# 22. Exit Criteria

Release candidate requires:

```text
P0 tests passed
P1 tests passed
no open S1 defects
no unresolved tenant isolation issue
no unresolved financial calculation issue
no unresolved manufacturing correctness issue
no critical data loss issue
acceptable performance
security checks passed
UAT approval where applicable
```

---

# 23. CI Quality Gates

Pull request should run:

```text
lint
static analysis
unit tests
service tests
API tests
database migration validation
security dependency scan
```

---

# 24. Merge Gate

Minimum:

```text
build passes
unit tests pass
API tests pass
no critical static-analysis failure
no critical security vulnerability
```

---

# 25. Release Candidate Gate

Run:

```text
full regression
E2E
visual regression
performance smoke
security regression
manufacturing validation
AI evaluation
```

---

# 26. Production Deployment Gate

Require:

```text
backup
migration validation
smoke tests
rollback plan
monitoring
```

---

# 27. Rollback Testing

Every release with:

```text
database migration
manufacturing algorithm change
pricing change
security change
```

must have a rollback/recovery strategy.

---

# 28. Authentication Test Strategy

Test:

```text
valid login
invalid login
locked user
inactive user
expired password
password reset
session expiry
logout
concurrent sessions
SSO
MFA if enabled
```

---

# 29. Authentication Security

Test:

```text
credential brute force controls
session fixation
session hijacking
CSRF
token expiration
cookie security
password storage
```

---

# 30. Tenant Isolation Test Strategy

This is a P0 test category.

For every tenant-scoped endpoint test:

```text
Tenant A
Tenant B
```

attempt:

```text
read
create
update
delete
search
export
file access
AI retrieval
background job access
```

Expected:

```text
No cross-tenant access.
```

---

# 31. RBAC Test Matrix

Test every critical permission:

```text
View
Create
Edit
Delete
Approve
Export
Execute
Release
Admin
```

against roles.

---

# 32. Horizontal Authorization

User A and User B with same role but different scope must be tested.

Example:

```text
Factory A user
Factory B resource
```

must be denied where factory scope applies.

---

# 33. Vertical Authorization

Lower privilege user must not execute privileged actions.

Example:

```text
Operator
→ cannot release production.
```

---

# 34. API Security Testing

For every protected API:

```text
no token
expired token
invalid token
wrong tenant
wrong role
wrong resource owner
```

must be tested.

---

# 35. Database Testing

Verify:

```text
constraints
foreign keys
unique keys
indexes
defaults
nullability
transactions
cascade rules
soft deletion
tenant keys
```

---

# 36. Migration Testing

Each migration must be tested for:

```text
fresh database
existing database
upgrade
rollback where supported
data preservation
index correctness
```

---

# 37. Data Integrity

Test:

```text
orphan prevention
duplicate prevention
referential integrity
revision integrity
tenant ownership
audit integrity
```

---

# 38. Transaction Testing

Critical operations must be atomic.

Examples:

```text
production release
quote approval
panel generation
package completion
```

If one critical operation fails:

```text
partial state must not remain.
```

---

# 39. API Functional Testing

Each API must test:

```text
valid request
missing fields
invalid types
invalid IDs
unauthorized request
duplicate request
boundary values
concurrent request
```

---

# 40. API Contract Testing

Validate:

```text
request schema
response schema
HTTP status
error format
pagination
sorting
filtering
```

---

# 41. Idempotency Testing

Critical APIs should be tested for duplicate requests.

Examples:

```text
release production
create payment
generate labels
complete operation
```

---

# 42. API Performance

Measure:

```text
p50
p95
p99
throughput
error rate
```

for critical APIs.

---

# 43. Frontend Testing

Test:

```text
components
forms
validation
state
navigation
permissions
loading
empty
error
responsive
```

---

# 44. UI Component Testing

Reusable components require tests for:

```text
default state
interaction
keyboard
error
disabled
loading
accessibility
```

---

# 45. Form Testing

For each form:

```text
valid
invalid
required fields
boundary
server validation
duplicate
cancel
reset
unsaved changes
```

---

# 46. UI Permission Testing

Verify unauthorized actions:

```text
hidden
disabled
or server rejected
```

Frontend behavior must not be treated as security.

---

# 47. Browser E2E Testing

Use E2E for critical workflows:

```text
login
create project
design room
create furniture
generate BOM
quote
approval
manufacturing release
MES scan
quality
packing
```

---

# 48. Recommended E2E Framework

Use an established browser automation framework such as:

```text
Playwright
```

unless the existing repository already has a suitable framework.

Do not introduce a second browser framework unnecessarily.

---

# 49. 2D CAD Test Strategy

2D CAD requires specialized testing.

Test:

```text
geometry creation
coordinates
units
grid
snap
selection
multi-select
transform
dimensions
layers
history
serialization
deserialization
```

---

# 50. CAD Coordinate Testing

Verify:

```text
world coordinates
screen coordinates
zoom transformation
pan transformation
unit conversion
```

---

# 51. CAD Precision Testing

Test:

```text
1 mm
0.1 mm
0.01 mm
```

where supported by application precision.

---

# 52. CAD Snap Testing

Test:

```text
endpoint
midpoint
intersection
grid
object
```

and ensure snapping does not unexpectedly alter geometry.

---

# 53. CAD Undo/Redo Testing

Test sequences:

```text
create
move
rotate
delete
undo
redo
```

and combinations.

---

# 54. CAD Serialization Testing

Given a drawing:

```text
save
reload
```

must preserve supported geometry accurately.

---

# 55. CAD Corruption Testing

Test invalid/corrupt design payloads.

Expected:

```text
safe rejection
clear error
no database corruption
```

---

# 56. 3D/BIM Test Strategy

Test:

```text
scene creation
model loading
object hierarchy
selection
camera
materials
visibility
geometry
2D/3D synchronization
serialization
```

---

# 57. 3D Geometry Validation

For deterministic geometry:

```text
known input
→ expected dimensions
→ expected bounding box
→ expected placement
```

---

# 58. 3D Performance

Measure:

```text
FPS
frame time
memory
asset load
scene load
interaction latency
```

against agreed targets.

---

# 59. Furniture Engine Test Strategy

This is a P0 domain.

Test:

```text
parameter calculation
component generation
constraints
clearances
materials
hardware
edge banding
panel generation
BOM
```

---

# 60. Parametric Boundary Testing

For every furniture parameter:

```text
minimum
minimum-1
normal
maximum
maximum+1
zero
negative
decimal
null
```

---

# 61. Furniture Rule Testing

Example:

```text
Wardrobe width = 2400
```

should produce deterministic component structure according to the configured template.

---

# 62. Furniture Invariant Testing

Test invariants such as:

```text
left side + internal width + right side = external width
```

and configured manufacturing rules.

---

# 63. Component Dependency Testing

If:

```text
carcass width changes
```

then dependent:

```text
shelves
doors
drawers
hardware
```

must update appropriately.

---

# 64. Furniture Regression Dataset

Maintain golden furniture models:

```text
kitchen base cabinet
wall cabinet
tall unit
wardrobe
drawer unit
loft cabinet
TV unit
storage unit
custom cabinet
```

---

# 65. Golden Model Testing

For each golden model compare:

```text
component count
dimensions
materials
BOM
panel count
hardware count
```

against expected results.

---

# 66. Material Engine Testing

Test:

```text
material assignment
thickness
area
linear consumption
waste
edge banding
hardware
price
```

---

# 67. Material Unit Testing

Verify:

```text
mm
cm
m
sq mm
sq m
linear mm
linear m
piece
```

conversions.

---

# 68. BOM Test Strategy

Test:

```text
generation
aggregation
deduplication
hierarchy
revision
quantity
units
source traceability
```

---

# 69. BOM Golden Test

Given known furniture:

```text
Expected:
8 panels
12 hinges
2 handles
...
```

Actual must match approved golden result.

---

# 70. BOM Change Test

Change:

```text
cabinet width
```

and verify only expected BOM components change.

---

# 71. BOQ Test Strategy

Verify:

```text
quantity
unit
rate
amount
tax
total
```

with known datasets.

---

# 72. Pricing Calculation Testing

Pricing must be tested with independent expected calculations.

Do not use the same implementation logic to calculate expected values.

---

# 73. Pricing Boundary Testing

Test:

```text
zero quantity
negative quantity
very large quantity
decimal quantity
zero price
discount 0%
discount 100%
tax 0%
high tax
margin
```

according to business rules.

---

# 74. Pricing Precision

Verify decimal rounding at:

```text
line level
subtotal
tax
grand total
```

according to configured policy.

---

# 75. Pricing Regression

Maintain golden quote datasets.

Any pricing-engine change must compare against approved expected outputs.

---

# 76. Quote Testing

Test:

```text
create
edit
revision
send
approve
reject
expire
PDF
branding
```

---

# 77. Client Approval Testing

Verify:

```text
correct revision
correct customer
correct project
timestamp
user
decision
comments
```

---

# 78. Revision Testing

For every versioned object test:

```text
create revision
edit revision
approve revision
clone revision
compare
restore where allowed
```

---

# 79. Revision Immutability

After approval/release:

```text
historical revision must not silently change.
```

---

# 80. Change Impact Testing

Change a design component and verify downstream impact:

```text
BOM
BOQ
pricing
panels
nesting
CNC
production
```

---

# 81. Manufacturing Engine Testing

Manufacturing engine is P0.

Test:

```text
panel generation
edge banding
operations
hardware drilling
routing
grooving
labels
BOM
traceability
```

---

# 82. Panel Generation Golden Tests

Known furniture inputs must produce approved panel sets.

Compare:

```text
panel ID
width
height
thickness
material
grain
edges
operations
source component
```

---

# 83. Edge Banding Testing

Test each edge combination:

```text
none
left
right
top
bottom
all
mixed
```

---

# 84. Drilling Rule Testing

Verify:

```text
hole positions
diameters
depth
spacing
hardware pattern
```

against known rules.

---

# 85. Manufacturing Constraint Testing

Invalid configurations must be detected:

```text
minimum panel width
minimum panel height
unsupported thickness
machine limit
tool limit
```

---

# 86. Nesting Test Strategy

Nesting is algorithmically sensitive.

Test:

```text
panel placement
rotation
grain
kerf
trim
sheet dimensions
utilization
waste
determinism
```

---

# 87. Nesting Golden Dataset

Maintain datasets with expected:

```text
sheet count
utilization range
waste range
panel placement validity
```

---

# 88. Nesting Geometry Validation

Every generated placement must satisfy:

```text
inside sheet
no panel overlap
required edge clearance
grain rules
machine constraints
```

---

# 89. Nesting Determinism

For deterministic mode:

```text
same input
+
same algorithm version
+
same parameters
=
same result
```

unless documented otherwise.

---

# 90. Nesting Optimization Testing

Do not test only one "best" layout.

Test:

```text
validity
utilization
constraints
performance
```

---

# 91. CNC/CAM Testing

CNC is P0.

Test:

```text
geometry
operations
tool selection
machine profile
post processor
DXF
G-code
```

---

# 92. CNC File Structural Testing

Generated files must satisfy configured:

```text
syntax
headers
coordinates
operations
tool calls
machine conventions
```

---

# 93. CNC Golden Files

Maintain version-controlled golden output files.

Compare normalized output where timestamps/non-functional metadata differ.

---

# 94. CNC Geometry Safety

Validate:

```text
coordinates
bounds
depth
tool diameter
operation order
```

---

# 95. CNC Regression

Every post-processor change must execute:

```text
golden files
multiple materials
multiple panel types
multiple operations
```

---

# 96. MES Test Strategy

Test:

```text
orders
work orders
queues
state transitions
stations
operators
machine status
downtime
quality
```

---

# 97. MES State Machine Testing

Valid transitions:

```text
Queued → Running
Running → Paused
Paused → Running
Running → Completed
Running → Rework
```

Invalid transitions must be rejected.

---

# 98. MES Concurrency

Test two operators attempting to:

```text
start same job
complete same panel
move same panel
```

Only valid state transition should succeed.

---

# 99. MES Race Conditions

Use concurrent API tests.

Verify no:

```text
duplicate completion
double consumption
duplicate scan
```

---

# 100. QR Testing

Test:

```text
valid QR
unknown QR
expired/invalid QR
duplicate QR
wrong station
wrong order
manual code
camera scan
hardware scanner
```

---

# 101. QR Uniqueness

Every panel QR must resolve uniquely to its intended panel.

---

# 102. Panel Traceability Testing

Verify genealogy:

```text
Project
→ Furniture
→ Component
→ Panel
→ Nesting
→ CNC
→ Work Order
→ Scan Events
→ Quality
→ Package
→ Dispatch
```

---

# 103. Quality Testing

Test:

```text
inspection
checklist
pass
fail
defect
severity
photo
rework
scrap
reinspection
```

---

# 104. Rework Testing

Verify:

```text
failed panel
→ rework
→ operation
→ inspection
→ pass
```

History must remain intact.

---

# 105. Packing Testing

Test:

```text
expected components
missing components
duplicate scan
wrong panel
package close
reopen if permitted
```

---

# 106. Dispatch Testing

Verify dispatch cannot complete when mandatory packing verification fails.

---

# 107. AI Test Strategy

AI must be tested differently from deterministic code.

Test:

```text
functional correctness
grounding
authorization
hallucination
prompt injection
data leakage
structured output
tool invocation
failure handling
latency
cost
```

---

# 108. AI Deterministic Boundary

AI may propose.

Deterministic engines must validate:

```text
dimensions
manufacturing rules
pricing
permissions
```

---

# 109. AI Golden Evaluation Set

Maintain representative prompts:

```text
furniture design
material recommendation
cost optimization
floorplan analysis
manufacturing explanation
MES delay explanation
```

---

# 110. AI Structured Output Testing

If AI returns JSON:

```text
schema valid
required fields
types
ranges
enum values
```

must be validated.

---

# 111. AI Hallucination Testing

Ask questions where the correct answer is:

```text
Not available in provided data.
```

AI should not invent facts.

---

# 112. AI Authorization Testing

Attempt:

```text
"Show me another tenant's project."
```

Expected:

```text
denied/no access
```

---

# 113. AI Prompt Injection Testing

Project/catalog documents may contain malicious instructions.

Test that untrusted content cannot override system authorization.

---

# 114. AI Tool Security

AI tool calls must be permission-checked independently.

AI cannot obtain privileges simply because the model requested them.

---

# 115. AI Change Approval

Test:

```text
AI proposes change
→ user rejects
→ no change committed
```

and:

```text
AI proposes change
→ user approves
→ deterministic engine validates
→ change committed
```

---

# 116. AI Failure

If AI provider fails:

```text
core FMOS continues operating
request is preserved
user receives retry option
```

---

# 117. Image-to-3D Testing

Test:

```text
valid floorplan
low-resolution floorplan
rotated floorplan
skewed image
noisy image
missing dimensions
complex floorplan
```

---

# 118. AI Detection Evaluation

Measure:

```text
wall detection precision
door detection precision
window detection precision
room detection
dimension accuracy
```

using an approved evaluation dataset.

---

# 119. White-Label Testing

Test each tenant branding surface:

```text
login
app shell
client portal
proposal
PDF
email
labels
```

---

# 120. White-Label Isolation

Tenant A branding must never appear in Tenant B surfaces.

---

# 121. Custom Domain Testing

Test:

```text
unverified
verified
duplicate
expired
SSL unavailable
wrong tenant
```

---

# 122. Email Testing

Verify:

```text
sender
branding
template
links
tenant
recipient
```

---

# 123. Document Testing

Test generated:

```text
drawings
quotes
BOM
BOQ
labels
reports
```

for:

```text
content
format
branding
revision
pagination
```

---

# 124. PDF Testing

Verify:

```text
file opens
correct pages
correct data
fonts
images
tables
print layout
```

---

# 125. Import Testing

Test:

```text
valid file
empty file
large file
invalid format
missing columns
duplicate rows
invalid values
partial failure
```

---

# 126. Import Transaction Testing

If configured as atomic:

```text
any critical validation failure
→ no partial commit.
```

If partial import is supported:

```text
successful rows committed
failed rows reported
```

---

# 127. Export Testing

Verify:

```text
correct rows
correct columns
correct encoding
correct units
correct tenant scope
```

---

# 128. Search Testing

Test:

```text
exact
partial
case-insensitive
code
name
special characters
empty search
large result set
permission filtering
```

---

# 129. Notification Testing

Verify:

```text
trigger
recipient
content
deep link
read/unread
preferences
```

---

# 130. Audit Testing

Critical actions must generate audit events.

Test:

```text
create
update
delete
approve
release
scrap
dispatch
role change
tenant change
```

---

# 131. Audit Immutability

Normal users must not alter audit history.

---

# 132. Accessibility Test Strategy

Target:

```text
WCAG 2.1 AA
```

where applicable.

Test:

```text
keyboard
focus
labels
ARIA
contrast
screen reader
zoom
```

---

# 133. Accessibility Automated Testing

Use tools such as:

```text
axe
Lighthouse
```

where appropriate.

---

# 134. Keyboard Testing

Test:

```text
Tab
Shift+Tab
Enter
Space
Escape
Arrow keys
shortcuts
```

---

# 135. Responsive Testing

Test:

```text
desktop
laptop
tablet
mobile
shop-floor handheld
```

---

# 136. Browser Compatibility

Test supported versions of:

```text
Chrome
Edge
Firefox
Safari
```

---

# 137. Mobile MES Testing

Test:

```text
camera
QR
touch
offline
sync
screen rotation
```

---

# 138. Performance Test Strategy

Measure:

```text
page load
API response
database query
CAD interaction
3D FPS
AI latency
file upload
nesting runtime
CNC generation
MES throughput
```

---

# 139. Performance Targets

Initial engineering targets should be agreed and baselined.

Recommended starting goals:

```text
Standard API p95 < 500 ms
Simple UI interaction < 100 ms perceived
Standard page load < 2 sec where infrastructure permits
Critical API error rate < 1%
Normal 3D scene target ≈ 60 FPS
```

Targets must be adjusted using production telemetry.

---

# 140. Load Testing

Load test:

```text
concurrent users
API requests
project creation
catalog search
BOM generation
MES scans
```

---

# 141. Stress Testing

Increase load until controlled degradation occurs.

Identify:

```text
CPU bottleneck
memory bottleneck
database bottleneck
queue bottleneck
network bottleneck
```

---

# 142. Soak Testing

Run representative workload for extended duration to identify:

```text
memory leaks
queue growth
connection leaks
performance degradation
```

---

# 143. Database Performance

Identify slow queries.

Test:

```text
indexes
joins
large BOM
large catalog
large project
MES event history
audit history
```

---

# 144. Large Dataset Testing

Minimum test datasets should include:

```text
10,000+ catalog items
100,000+ panels
large project
large audit history
large MES event history
```

Exact production scale must be finalized from capacity planning.

---

# 145. Caching Testing

Verify cache:

```text
correctness
tenant isolation
invalidation
expiration
stale data prevention
```

---

# 146. File Storage Testing

Test:

```text
upload
download
authorization
tenant isolation
large file
invalid file
duplicate
delete
restore where supported
```

---

# 147. Background Job Testing

Test:

```text
success
failure
retry
timeout
duplicate execution
dead-letter
cancellation
```

---

# 148. Queue Idempotency

A retried job must not create duplicate:

```text
panels
labels
CNC programs
documents
notifications
```

---

# 149. Reliability Testing

Test failures of:

```text
database
cache
queue
storage
email
AI provider
external API
network
```

---

# 150. Recovery Testing

Verify:

```text
service restart
database reconnect
queue recovery
job retry
session recovery
offline synchronization
```

---

# 151. Backup Testing

Backups must be:

```text
created
verified
restorable
tested periodically
```

A backup that has never been restored is not considered validated.

---

# 152. Disaster Recovery

Define and test:

```text
RPO
RTO
backup frequency
restore procedure
failover
```

---

# 153. Security Test Strategy

Security testing must cover:

```text
OWASP Top 10
authentication
authorization
tenant isolation
input validation
file upload
SQL injection
XSS
CSRF
SSRF
IDOR
session security
secrets
API abuse
```

---

# 154. SQL Injection

All API/database paths must be tested using malicious SQL payloads.

Use parameterized queries.

---

# 155. XSS

Test:

```text
project names
customer names
comments
catalog names
AI content
documents
```

for stored/reflected XSS.

---

# 156. IDOR Testing

Change resource IDs in requests.

Expected:

```text
access denied
```

when resource is outside scope.

---

# 157. File Upload Security

Test:

```text
extension spoofing
MIME spoofing
oversized file
malicious filename
path traversal
script upload
```

---

# 158. Secrets Testing

Scan source code and build artifacts for:

```text
API keys
passwords
tokens
private keys
database credentials
```

---

# 159. Dependency Security

Run:

```text
PHP dependency audit
npm dependency audit
container/image scanning if used
```

---

# 160. Static Analysis

Use appropriate tools for:

```text
PHP
JavaScript
SQL
configuration
```

---

# 161. Penetration Testing

Before production launch and periodically thereafter:

```text
external penetration test
authenticated application test
API test
tenant isolation test
```

---

# 162. CAD Security

CAD import must be treated as untrusted input.

Test:

```text
malformed geometry
huge geometry
recursive data
invalid objects
```

---

# 163. Manufacturing Security

Manufacturing release APIs require elevated authorization.

---

# 164. CNC Security

Only authorized users can:

```text
generate
approve
release
download
```

CNC outputs.

---

# 165. MES Security

Operators can only execute operations within authorized scope.

---

# 166. QR Security

QR endpoints must not expose sensitive information without authorization.

---

# 167. Financial Security

Pricing/cost/margin data must be protected by RBAC.

---

# 168. Concurrency Testing

Test concurrent:

```text
editing
approval
production release
panel scan
packing
dispatch
```

---

# 169. Optimistic Locking

Where required, test:

```text
User A edits
User B edits
User A saves
User B saves
```

Expected conflict handling.

---

# 170. Race Condition Testing

Particularly test:

```text
same panel scanned twice
same production order released twice
same quote approved twice
same package completed twice
```

---

# 171. Time/Timezone Testing

Test:

```text
IST
UTC
other tenant timezone
DST-aware regions
date boundaries
month/year boundaries
```

---

# 172. Currency Testing

Test:

```text
INR
USD
EUR
configured tenant currencies
```

where supported.

---

# 173. Unit System Testing

Test:

```text
mm
cm
m
inch
feet
```

where supported.

---

# 174. Unit Conversion Golden Tests

Use independent expected conversion values.

---

# 175. Localization Testing

Verify:

```text
labels
dates
numbers
currency
units
emails
PDFs
```

---

# 176. Data Migration Testing

For existing FMOS data:

```text
backup
migration
validation
record count
checksums/sample comparison
application verification
```

---

# 177. Regression Suite Structure

Recommended:

```text
tests/
  unit/
  api/
  integration/
  e2e/
  cad/
  furniture/
  manufacturing/
  nesting/
  cnc/
  mes/
  ai/
  security/
  performance/
  visual/
  fixtures/
```

---

# 178. Test Automation Architecture

Recommended:

```text
tests/
  fixtures/
  factories/
  helpers/
  assertions/
  mocks/
  golden/
  snapshots/
```

---

# 179. Test Database

Automated tests should use isolated databases/schema/containers.

---

# 180. Test Factories

Create reusable factories:

```text
TenantFactory
UserFactory
ProjectFactory
RoomFactory
FurnitureFactory
MaterialFactory
BOMFactory
QuoteFactory
ProductionOrderFactory
PanelFactory
WorkOrderFactory
```

---

# 181. API Test Fixtures

Maintain:

```text
valid payload
invalid payload
boundary payload
unauthorized payload
cross-tenant payload
```

---

# 182. Golden Artifacts

Version-control non-sensitive golden outputs:

```text
CAD JSON
furniture JSON
BOM JSON
nesting layout
DXF
CNC output
label layout
```

---

# 183. Snapshot Testing

Use snapshots for:

```text
structured geometry
BOM
API responses where stable
UI components
```

Avoid brittle snapshots containing volatile data.

---

# 184. Visual Regression

Critical screens:

```text
Login
Dashboard
2D CAD
3D
Furniture Designer
BOM
Quote
Manufacturing
Nesting
MES
QR
Client Portal
```

---

# 185. Visual Regression Rules

Ignore dynamic:

```text
timestamps
random IDs
live metrics
```

where necessary.

---

# 186. 3D Visual Regression

Use deterministic camera/scene setup for baseline comparisons.

---

# 187. CAD Visual Regression

Use deterministic:

```text
canvas size
zoom
camera
grid
font
```

for stable comparison.

---

# 188. Browser Console Testing

Release tests must verify:

```text
no unexpected console errors
no unhandled promise rejection
no critical network failures
```

---

# 189. API Logging Test

Logs must contain enough information to troubleshoot without leaking:

```text
password
token
secret
sensitive customer data
```

---

# 190. Observability Testing

Verify:

```text
metrics
logs
traces
correlation IDs
alerts
```

for critical workflows.

---

# 191. Production Smoke Test

After deployment:

```text
login
dashboard
create/read safe test object where permitted
API health
database health
queue health
storage
```

must be checked.

---

# 192. Manufacturing Production Smoke

Where safe:

```text
machine profile
test program validation
non-production output
```

must be verified before actual release.

---

# 193. MES Production Smoke

Verify:

```text
login
queue
scan
status
```

using an approved test/staging workflow.

Never manipulate live production records casually.

---

# 194. Test Automation Priority

Automate first:

```text
P0 business calculations
RBAC
tenant isolation
furniture rules
BOM
pricing
manufacturing
nesting validity
CNC validation
MES state transitions
QR
revision
```

---

# 195. Manual Testing Priority

Retain manual/exploratory testing for:

```text
UX
CAD usability
3D visual quality
client presentation
AI usefulness
shop-floor ergonomics
```

---

# 196. Exploratory Testing Charters

Create charters such as:

```text
CAD-EXP-001
Can a new designer create a room without guidance?

FURN-EXP-001
Can a designer modify a complex wardrobe safely?

MES-EXP-001
Can an operator recover from a wrong scan?

CLIENT-EXP-001
Can a client approve a design without training?
```

---

# 197. Usability Testing

Observe:

```text
task completion
time
errors
help requests
confusion
```

---

# 198. Key UX Tasks

Test users can complete:

```text
create project
create room
design cabinet
generate quote
approve
release production
scan panel
complete quality
pack
```

---

# 199. Shop-Floor Usability

Test in realistic conditions:

```text
gloves if relevant
bright lighting
noisy environment
mobile device
barcode scanner
limited attention
```

---

# 200. CAD Usability

Test:

```text
mouse
trackpad
keyboard
shortcut discoverability
numeric entry
```

---

# 201. AI Usability

Test:

```text
prompt clarity
result comprehension
trust
approval flow
error recovery
```

---

# 202. Accessibility Manual Testing

Use:

```text
keyboard-only
screen reader
200% zoom
high contrast
```

where applicable.

---

# 203. Release Regression Suites

## Smoke

Fast:

```text
login
health
dashboard
core API
```

## Core Regression

```text
project
CAD
furniture
BOM
pricing
quote
```

## Manufacturing Regression

```text
panels
nesting
CNC
MES
QR
quality
```

## Full Regression

All critical suites.

---

# 204. CI Pipeline

Recommended:

```text
Commit
 ↓
Lint
 ↓
Static Analysis
 ↓
Unit Tests
 ↓
API Tests
 ↓
Build
 ↓
Integration Tests
 ↓
Security Scan
 ↓
Deploy QA
 ↓
E2E
 ↓
Visual Regression
 ↓
Performance Smoke
 ↓
Release Candidate
```

---

# 205. Nightly Test Suite

Run:

```text
full API regression
full E2E regression
database tests
security regression
golden manufacturing outputs
AI evaluation
```

---

# 206. Scheduled Performance

Run regular:

```text
load tests
soak tests
large dataset tests
```

---

# 207. Flaky Test Management

Flaky tests must not simply be ignored.

Track:

```text
test
frequency
failure rate
root cause
owner
```

---

# 208. Flaky Test Rule

A test may be quarantined only when:

```text
defect ticket exists
owner assigned
fix planned
```

---

# 209. Test Coverage

Track coverage by:

```text
code
requirements
stories
critical workflows
risk
```

Code coverage alone is insufficient.

---

# 210. Recommended Coverage Targets

Initial targets:

```text
Critical business logic: ≥90%
General backend logic: ≥80%
Frontend business components: ≥70%
Critical API endpoints: 100%
Critical state transitions: 100%
Financial calculations: 100%
Manufacturing rules: ≥90%
```

Targets may be refined after baseline measurement.

---

# 211. Mutation Testing

Consider mutation testing for:

```text
pricing
manufacturing
nesting constraints
RBAC
state transitions
```

to verify test effectiveness.

---

# 212. Property-Based Testing

Recommended for:

```text
geometry
unit conversion
pricing
panel generation
nesting constraints
```

---

# 213. Geometry Property Tests

Properties:

```text
width >= minimum
height >= minimum
no invalid negative dimensions
```

---

# 214. Nesting Property Tests

Every generated layout must satisfy:

```text
panel inside sheet
no overlap
grain constraint
kerf constraint
trim constraint
```

---

# 215. Manufacturing Property Tests

Every generated panel must satisfy configured manufacturing invariants.

---

# 216. Pricing Property Tests

Verify:

```text
zero quantity => zero line amount
negative quantity => rejected
```

and configured financial invariants.

---

# 217. Security Regression

Every security fix must create a permanent regression test.

---

# 218. Defect Escape Tracking

Track:

```text
escaped defect
module
severity
root cause
test gap
prevention action
```

---

# 219. Root Cause Categories

Use:

```text
Requirement
Design
Implementation
Test Gap
Environment
Data
Integration
Deployment
Third Party
```

---

# 220. Quality Metrics Dashboard

Track:

```text
Pass Rate
Fail Rate
Automation %
Flaky %
Defect Density
Escaped Defects
S1/S2 Count
MTTR
Coverage
Build Health
```

---

# 221. Manufacturing Quality Metrics

Track:

```text
Panel Defect Rate
Rework Rate
Scrap Rate
Nesting Waste
CNC Failure Rate
MES Transition Errors
```

---

# 222. Product Quality Metrics

Track:

```text
Design-to-Production Success
Quote Calculation Errors
Revision Conflicts
Client Approval Errors
Traceability Completeness
```

---

# 223. AI Quality Metrics

Track:

```text
Grounded Response Rate
Structured Output Validity
Tool Success Rate
Hallucination Rate
User Acceptance Rate
AI Failure Rate
Latency
Cost
```

---

# 224. Test Reporting

Every CI run should publish:

```text
total
passed
failed
skipped
duration
coverage
failed tests
artifacts
```

---

# 225. Test Evidence

Critical failures should retain:

```text
screenshots
video
request
response
logs
database evidence where safe
CNC/golden artifact
```

---

# 226. Test Artifact Retention

Define retention according to environment.

Do not retain sensitive customer data unnecessarily.

---

# 227. UAT Strategy

UAT participants:

```text
designer
design lead
estimator
factory manager
CNC engineer
operator
quality inspector
business owner
client representative
```

where applicable.

---

# 228. UAT Scenario

Primary UAT:

```text
Customer
→ Project
→ Design
→ Furniture
→ Quote
→ Approval
→ Production
→ MES
→ QR
→ Quality
→ Packing
```

---

# 229. UAT Acceptance

Business owner signs off when:

```text
critical workflows complete
business rules correct
outputs acceptable
no blocking defects
```

---

# 230. Manufacturing SME Sign-Off

Required for:

```text
panel dimensions
edge banding
hardware drilling
nesting
CNC
MES states
```

---

# 231. Financial SME Sign-Off

Required for:

```text
BOQ
pricing
tax
discount
margin
rounding
```

---

# 232. Security Sign-Off

Required before production for:

```text
tenant isolation
RBAC
authentication
API security
file security
secrets
```

---

# 233. Performance Sign-Off

Required for agreed production workload.

---

# 234. Release Candidate Checklist

```text
[ ] Build successful
[ ] Database migration tested
[ ] Unit tests passed
[ ] API tests passed
[ ] Integration tests passed
[ ] E2E tests passed
[ ] Security tests passed
[ ] Tenant isolation passed
[ ] RBAC passed
[ ] CAD regression passed
[ ] 3D regression passed
[ ] Furniture golden tests passed
[ ] BOM tests passed
[ ] Pricing golden tests passed
[ ] Manufacturing tests passed
[ ] Nesting tests passed
[ ] CNC golden tests passed
[ ] MES state tests passed
[ ] QR tests passed
[ ] Quality tests passed
[ ] AI evaluation passed
[ ] White-label tests passed
[ ] Performance passed
[ ] Accessibility passed
[ ] Visual regression passed
[ ] No open S1
[ ] No unacceptable S2
[ ] UAT sign-off
[ ] Rollback plan
[ ] Monitoring active
```

---

# 235. Production Deployment Checklist

```text
[ ] Backup verified
[ ] Migration tested
[ ] Release artifact verified
[ ] Environment configuration verified
[ ] Secrets verified
[ ] Health checks verified
[ ] Queue verified
[ ] Storage verified
[ ] Monitoring verified
[ ] Smoke tests executed
[ ] Rollback procedure ready
```

---

# 236. Cursor Implementation Instructions

Before modifying the codebase, Cursor MUST:

```text
1. Inspect existing application.
2. Identify current test framework(s).
3. Identify existing unit tests.
4. Identify existing API tests.
5. Identify existing E2E framework.
6. Identify test database strategy.
7. Identify CI pipeline.
8. Identify existing fixtures/factories.
9. Identify existing mocking framework.
10. Identify existing CAD tests.
11. Identify existing Three.js tests.
12. Identify existing manufacturing tests.
13. Identify existing PHP tests.
14. Identify existing JavaScript tests.
15. Identify gaps against this Test Strategy.
```

Cursor MUST NOT introduce duplicate testing frameworks unless justified.

---

# 237. Cursor Test Gap Report

Before implementation, Cursor should produce:

```text
CURRENT TEST FRAMEWORK
CURRENT TEST COVERAGE
CURRENT AUTOMATION
CURRENT CI
CURRENT TEST DATA
CURRENT DEFECTS
CURRENT GAPS
REQUIRED NEW TESTS
RECOMMENDED PRIORITY
```

---

# 238. Cursor Test Implementation Sequence

Implement tests in this order:

```text
1. Existing functionality baseline
2. Unit tests
3. Critical API tests
4. Security/RBAC tests
5. Database integrity
6. Furniture engine
7. BOM/Pricing
8. Manufacturing
9. Nesting
10. CNC
11. MES
12. QR
13. AI
14. UI components
15. Critical E2E
16. Visual
17. Performance
```

---

# 239. Cursor Rule — Do Not Test Implementation Details Unnecessarily

Tests should verify behavior/contracts.

Avoid brittle tests that fail because an internal function/class was refactored while external behavior remains correct.

---

# 240. Cursor Rule — Preserve Golden Manufacturing Outputs

Do not casually update golden files because implementation changed.

When golden output changes:

```text
identify reason
verify business impact
obtain domain approval
document version change
```

---

# 241. Cursor Rule — Independent Expected Values

For financial/manufacturing tests:

```text
Do not calculate expected value using the same production function.
```

Use:

```text
known constants
independent calculation
approved fixtures
```

---

# 242. Cursor Rule — Security Tests Are Mandatory

Every new protected endpoint must include:

```text
authorized
unauthorized
cross-tenant
wrong-scope
```

tests as applicable.

---

# 243. Cursor Rule — New Bug Requires Regression Test

Every resolved defect must include a regression test whenever technically practical.

---

# 244. Cursor Rule — New Story Requires Acceptance Tests

Every new User Story must map to at least one automated or documented acceptance test.

---

# 245. Cursor Rule — Manufacturing Changes Require Expanded Regression

Any change to:

```text
furniture rules
panel generation
edge banding
hardware drilling
nesting
CNC
```

must run the manufacturing regression suite.

---

# 246. Cursor Rule — Pricing Changes Require Golden Regression

Any pricing calculation change must run the pricing golden dataset.

---

# 247. Cursor Rule — Schema Changes Require Migration Tests

Any database schema change must include:

```text
fresh database
upgrade database
data preservation
```

tests as applicable.

---

# 248. Cursor Rule — UI Changes Require Visual Review

Critical screens must undergo visual regression or documented visual review.

---

# 249. Cursor Rule — API Changes Require Contract Review

Changing an API response/request requires:

```text
consumer impact analysis
contract update
tests
documentation
```

---

# 250. Cursor Rule — Do Not Disable Tests to Make Builds Green

A failing test must be:

```text
fixed
corrected with justification
or formally quarantined
```

Never silently deleted or skipped.

---

# 251. Core End-to-End Test Pack

Maintain these permanent E2E tests:

```text
E2E-001 Tenant onboarding
E2E-002 User/RBAC
E2E-003 Customer/project
E2E-004 Building/floor/room
E2E-005 2D CAD
E2E-006 2D→3D
E2E-007 Furniture placement
E2E-008 Parametric furniture
E2E-009 Materials
E2E-010 BOM
E2E-011 BOQ
E2E-012 Pricing
E2E-013 Quote
E2E-014 Client approval
E2E-015 Manufacturing release
E2E-016 Panel generation
E2E-017 Nesting
E2E-018 CNC
E2E-019 MES
E2E-020 QR tracking
E2E-021 Quality
E2E-022 Rework
E2E-023 Packing
E2E-024 Dispatch
E2E-025 Revision impact
E2E-026 AI furniture
E2E-027 AI floorplan
E2E-028 White label
E2E-029 Tenant isolation
E2E-030 Offline QR
```

---

# 252. Core Golden Test Pack

Maintain golden cases for:

```text
GT-001 Base Cabinet
GT-002 Wall Cabinet
GT-003 Tall Cabinet
GT-004 Wardrobe
GT-005 Drawer Unit
GT-006 Kitchen
GT-007 TV Unit
GT-008 Storage Unit
GT-009 Complex Custom Furniture
```

For each, verify:

```text
geometry
components
materials
BOM
pricing
panels
nesting
CNC
```

where applicable.

---

# 253. Manufacturing Golden Test Pack

Maintain:

```text
MGT-001 Panel generation
MGT-002 Edge banding
MGT-003 Drilling
MGT-004 Routing
MGT-005 Nesting
MGT-006 DXF
MGT-007 G-code
MGT-008 Label
```

---

# 254. Security Golden Test Pack

Maintain permanent tests for:

```text
SEC-G-001 Cross tenant read
SEC-G-002 Cross tenant update
SEC-G-003 Cross tenant delete
SEC-G-004 Cross tenant search
SEC-G-005 Cross tenant file
SEC-G-006 Cross tenant AI
SEC-G-007 RBAC escalation
SEC-G-008 IDOR
SEC-G-009 SQL injection
SEC-G-010 XSS
```

---

# 255. Quality Gate Matrix

| Area | P0 Required | Automated | Manual |
|---|---:|---:|---:|
| Authentication | Yes | Yes | Yes |
| Tenant Isolation | Yes | Yes | Yes |
| RBAC | Yes | Yes | Yes |
| Project | Yes | Yes | Yes |
| 2D CAD | Yes | Yes | Yes |
| 3D/BIM | Yes | Yes | Yes |
| Furniture Engine | Yes | Yes | Yes |
| Materials | Yes | Yes | Yes |
| BOM | Yes | Yes | Yes |
| Pricing | Yes | Yes | SME |
| Quote | Yes | Yes | Yes |
| Manufacturing | Yes | Yes | SME |
| Nesting | Yes | Yes | SME |
| CNC | Yes | Yes | SME |
| MES | Yes | Yes | SME |
| QR | Yes | Yes | Yes |
| Quality | Yes | Yes | SME |
| AI | Yes | Yes | Yes |
| White Label | Yes | Yes | Yes |
| Performance | Yes | Yes | Yes |
| Accessibility | Yes | Partial | Yes |
| Usability | No | Partial | Yes |

---

# 256. Risk-Based Testing

Highest risk:

```text
1. Tenant isolation
2. RBAC
3. Manufacturing correctness
4. CNC output
5. Pricing
6. Data integrity
7. Revision management
8. MES state transitions
9. Panel traceability
10. AI authorization
```

These receive the deepest testing.

---

# 257. Test Risk Matrix

## Critical + High Probability

Immediate automation.

## Critical + Low Probability

Targeted negative tests and security review.

## Low Impact + High Probability

Automate if inexpensive.

## Low Impact + Low Probability

Manual/exploratory as appropriate.

---

# 258. Release Blocking Defects

Release MUST be blocked for:

```text
cross-tenant data leak
authentication bypass
RBAC bypass
data corruption
incorrect financial totals
incorrect released manufacturing geometry
unsafe/invalid CNC output
irrecoverable MES corruption
loss of panel traceability
critical production workflow failure
```

---

# 259. Non-Blocking Defects

Examples:

```text
minor visual issue
non-critical tooltip
minor spacing
cosmetic alignment
```

provided no accessibility/security/business impact.

---

# 260. Production Monitoring Validation

After release monitor:

```text
API errors
PHP errors
JS errors
database errors
queue failures
AI failures
MES transition failures
CNC generation failures
QR failures
latency
resource utilization
```

---

# 261. Post-Release Validation

Within agreed deployment window:

```text
smoke
critical API
login
tenant
dashboard
core workflow health
queue
storage
```

---

# 262. Canary Release

Where infrastructure supports it:

```text
small tenant subset
→ monitor
→ expand
```

especially for:

```text
manufacturing engine
pricing
AI
major database changes
```

---

# 263. Feature Flags

High-risk features should support controlled rollout:

```text
AI
new nesting algorithm
new pricing engine
new CNC postprocessor
new MES workflow
```

---

# 264. A/B Testing

Use only where appropriate.

Do not A/B test safety-critical manufacturing behavior without explicit controls.

---

# 265. Compliance of Test Artifacts

Test artifacts must not accidentally expose:

```text
customer PII
credentials
tokens
private business data
```

---

# 266. Test Data Privacy

Mask or synthesize:

```text
customer contact information
financial data
addresses
internal credentials
```

where possible.

---

# 267. Performance Baselines

Each major algorithm requires a baseline:

```text
furniture generation
BOM generation
nesting
CNC
large project load
```

Track regression against baseline.

---

# 268. Algorithm Versioning

Record algorithm version for:

```text
furniture engine
nesting
pricing
CNC
AI workflows
```

where output needs traceability.

---

# 269. Reproducibility

A manufacturing defect should be reproducible from:

```text
project revision
furniture revision
material revision
rule-set version
algorithm version
nesting parameters
machine profile
```

---

# 270. Testability Requirement

Every major engine must provide deterministic test interfaces.

Examples:

```text
FurnitureEngine.generate()
BOMEngine.generate()
PricingEngine.calculate()
ManufacturingEngine.generatePanels()
NestingEngine.optimize()
CNCGenerator.generate()
```

---

# 271. Pure Calculation Functions

Where possible isolate:

```text
unit conversions
pricing
dimensions
material consumption
panel calculations
```

as testable pure functions.

---

# 272. Mocking External Services

Mock:

```text
email
AI providers
storage
payment
external ERP
SSO
```

for deterministic tests.

Use real integration tests separately.

---

# 273. Contract Test Environments

External integrations should have:

```text
mock
sandbox
real staging
```

as appropriate.

---

# 274. Test Timeouts

Avoid excessive global test timeouts.

Set operation-specific timeouts for:

```text
AI
file processing
nesting
CNC
imports
```

---

# 275. Test Parallelization

Parallelize isolated tests.

Do not parallelize tests that incorrectly share mutable state.

---

# 276. Database Test Isolation

Each test should either:

```text
transaction rollback
isolated schema
isolated database
```

depending on architecture.

---

# 277. E2E Test Isolation

Each E2E suite should have its own test tenant/project unless a deliberate shared fixture is required.

---

# 278. Screenshot Standards

Critical UI failures should include:

```text
viewport
screen
state
browser
build
```

---

# 279. Video Standards

Failed E2E tests should retain video/traces where framework supports it.

---

# 280. API Failure Evidence

Capture:

```text
request method
path
sanitized request
status
sanitized response
correlation ID
```

---

# 281. Database Failure Evidence

Do not expose production database credentials.

Use safe diagnostic queries in controlled environments.

---

# 282. Test Review

Critical automated tests should be peer reviewed.

---

# 283. Test Code Quality

Test code must follow:

```text
same coding standards
clear naming
small scope
independent setup
deterministic assertions
```

---

# 284. Test Maintainability

Avoid:

```text
magic waits
arbitrary sleeps
brittle selectors
hard-coded volatile IDs
```

Prefer:

```text
stable selectors
explicit waits
API fixtures
data-testid
semantic locators
```

---

# 285. UI Selector Standard

Where appropriate use:

```html
data-testid="project-create-button"
```

for stable automation.

Do not rely on CSS class names that exist only for styling.

---

# 286. CAD Automation

CAD tests may use:

```text
engine-level geometry tests
integration tests
limited browser interaction tests
```

Do not force every geometry test through the browser.

---

# 287. 3D Automation

Prefer:

```text
scene/model tests
geometry assertions
asset loading tests
```

plus selected visual regression.

---

# 288. MES Automation

Prefer API/state-machine tests for exhaustive transition coverage.

Use E2E for representative operator journeys.

---

# 289. Manufacturing Automation

Prefer deterministic engine tests.

Use E2E for integration.

---

# 290. AI Automation

Use:

```text
schema validation
evaluation datasets
policy tests
retrieval authorization
tool authorization
```

rather than asserting exact natural-language wording.

---

# 291. AI Evaluation Criteria

Score:

```text
correctness
grounding
safety
authorization
structured output
usefulness
```

---

# 292. AI Regression Thresholds

Define acceptable thresholds before release.

A model/provider change cannot be released solely because generic benchmark quality improved.

FMOS-specific evaluation must pass.

---

# 293. Prompt Version Testing

Version and test important prompts.

---

# 294. AI Provider Failover

If supported, test:

```text
provider unavailable
timeout
rate limit
invalid response
```

---

# 295. AI Cost Guardrails

Test:

```text
maximum tokens
maximum calls
tenant quota
rate limits
```

---

# 296. AI Data Retention

Verify configured retention behavior.

---

# 297. Offline Testing

Test:

```text
offline
online transition
duplicate sync
conflicting sync
partial sync
failed sync
retry
```

---

# 298. Offline Security

Offline local data must not expose unauthorized tenant data.

---

# 299. Mobile Scanner Testing

Test camera:

```text
permission denied
permission granted
poor lighting
multiple QR codes
invalid QR
```

---

# 300. Shop-Floor Device Testing

Test representative:

```text
phone
tablet
handheld scanner
industrial browser
```

---

# 301. Accessibility of Shop-Floor

Use:

```text
large controls
high contrast
status labels
```

and avoid relying only on color.

---

# 302. Internationalization Testing

Test long strings to ensure UI does not break.

---

# 303. Font Testing

Verify:

```text
catalog
CAD
3D labels
PDF
QR labels
```

under supported fonts.

---

# 304. Data Export Integrity

Compare exported data against source records.

---

# 305. Import/Export Round Trip

Where supported:

```text
Export
→ Import
→ Compare
```

must preserve supported data.

---

# 306. Audit Correlation

Critical workflows should have correlation IDs connecting:

```text
UI action
API
service
background job
database event
audit
```

where applicable.

---

# 307. Test Environment Health

Before test suite:

```text
database reachable
API reachable
queue reachable
storage reachable
test identity provider reachable
```

---

# 308. Failed Environment Rule

Do not classify environment outages as product defects without evidence.

---

# 309. Test Suite Categorization

Use tags:

```text
@smoke
@regression
@security
@cad
@3d
@furniture
@bom
@pricing
@manufacturing
@nesting
@cnc
@mes
@qr
@ai
@performance
@e2e
@visual
@uat
```

---

# 310. Test Execution Commands

Cursor should document project-specific commands such as:

```text
npm test
npm run test:e2e
npm run test:visual
phpunit
```

but MUST first inspect the repository and use existing scripts rather than inventing commands.

---

# 311. PHP Test Requirements

If PHPUnit or another existing framework is present:

```text
reuse it
```

Test:

```text
services
repositories
controllers
validation
authorization
calculations
```

---

# 312. JavaScript Test Requirements

Use the existing test framework where present.

Test:

```text
components
utilities
state
CAD logic
3D logic
API clients
validation
```

---

# 313. MySQL Test Requirements

Test:

```text
schema
constraints
indexes
queries
transactions
migrations
```

---

# 314. Integration Test Requirements

Test actual interactions:

```text
PHP
MySQL
JS/API
storage
queue
```

in controlled environments.

---

# 315. E2E Environment Requirement

E2E should run against a production-like application stack.

---

# 316. Test Report Format

Every release should produce:

```text
Executive Summary
Build
Environment
Test Scope
Pass/Fail
Defects
Risk
Coverage
Performance
Security
Manufacturing Validation
AI Evaluation
Release Recommendation
```

---

# 317. Release Recommendation

Possible:

```text
GO
GO WITH KNOWN RISKS
NO-GO
```

---

# 318. No-Go Conditions

Automatic NO-GO for:

```text
tenant isolation failure
auth bypass
critical data corruption
incorrect financial calculation
critical manufacturing output error
critical CNC validation failure
critical traceability failure
unrecoverable production workflow
```

---

# 319. Known Risk Acceptance

Only authorized product/business owner can accept a known risk.

Acceptance must be recorded.

---

# 320. Test Strategy Definition of Done

The FMOS test strategy implementation is complete when:

```text
[ ] Test framework identified
[ ] Unit testing operational
[ ] API testing operational
[ ] Database testing operational
[ ] Integration testing operational
[ ] E2E operational
[ ] Visual regression operational
[ ] Security regression operational
[ ] Tenant isolation automated
[ ] RBAC automated
[ ] CAD regression operational
[ ] 3D regression operational
[ ] Furniture golden suite operational
[ ] BOM golden suite operational
[ ] Pricing golden suite operational
[ ] Manufacturing golden suite operational
[ ] Nesting validation operational
[ ] CNC golden suite operational
[ ] MES state suite operational
[ ] QR suite operational
[ ] AI evaluation suite operational
[ ] Performance baseline established
[ ] Accessibility baseline established
[ ] CI gates configured
[ ] Release gates configured
[ ] Test reporting configured
[ ] Defect workflow configured
[ ] Traceability established
[ ] Production smoke suite established
```

---

# 321. Final Quality Principle

FMOS must not be considered "tested" simply because:

```text
the UI works
```

The platform is tested only when the complete chain is verified:

```text
CUSTOMER
   ↓
PROJECT
   ↓
SPACE
   ↓
2D DESIGN
   ↓
3D/BIM
   ↓
PARAMETRIC FURNITURE
   ↓
MATERIALS
   ↓
BOM
   ↓
BOQ
   ↓
PRICING
   ↓
QUOTE
   ↓
CLIENT APPROVAL
   ↓
MANUFACTURING ENGINEERING
   ↓
PANELS
   ↓
NESTING
   ↓
CNC/CAM
   ↓
MES
   ↓
QR TRACEABILITY
   ↓
QUALITY
   ↓
REWORK
   ↓
PACKING
   ↓
DISPATCH
```

The ultimate acceptance criterion is:

> **A valid approved design must be transformed into correct, traceable and manufacturable production output without loss of design intent, commercial integrity, security, revision history or physical-panel traceability.**

---

# 322. Cursor Master Instruction

When implementing FMOS, Cursor MUST treat this document as a quality gate rather than optional testing guidance.

For every feature:

```text
Requirement
 ↓
Implementation
 ↓
Unit Test
 ↓
API Test
 ↓
Integration Test
 ↓
UI Test
 ↓
E2E Test where required
 ↓
Security Test
 ↓
Regression
 ↓
Acceptance
```

For every manufacturing feature:

```text
Input
 ↓
Deterministic Rule
 ↓
Expected Output
 ↓
Golden Test
 ↓
Regression
 ↓
Domain Validation
```

For every AI feature:

```text
Prompt/Input
 ↓
Authorization
 ↓
AI Processing
 ↓
Structured Validation
 ↓
Deterministic Validation
 ↓
User Approval
 ↓
Commit
 ↓
Audit
```

For every tenant feature:

```text
Tenant Context
 ↓
Authentication
 ↓
Authorization
 ↓
Data Access
 ↓
Tenant Isolation
 ↓
Audit
```

**Do not declare a feature complete until its implementation, acceptance criteria, automated tests, security controls and regression coverage are complete.**
