# UI/UX Screen Specification
## FMOS — End-to-End Interior Design, Furniture Engineering, Manufacturing & MES SaaS Platform

**Document ID:** UIUX-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, Frontend Developers, Backend Developers, UX/UI Designers, QA, Product Owners  
**Frontend:** JavaScript ES6+, HTML5, CSS/Tailwind or existing design system  
**Backend:** PHP 8.x + MySQL 8.x  
**Rendering:** 2D Canvas/SVG + Three.js/WebGL for 3D  
**Architecture:** Responsive web application, desktop-first CAD/MES, mobile/tablet shop-floor workflows  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the screen-level UI/UX requirements for FMOS.

FMOS is an end-to-end platform covering:

```text
CRM
 ↓
Project
 ↓
Architectural Planning
 ↓
2D CAD
 ↓
3D/BIM
 ↓
Interior Design
 ↓
Parametric Furniture
 ↓
Materials & Catalog
 ↓
BOM / BOQ
 ↓
Pricing
 ↓
Client Proposal / Approval
 ↓
Manufacturing
 ↓
Nesting
 ↓
CNC/CAM
 ↓
MES
 ↓
QR / Panel Tracking
 ↓
Quality
 ↓
Packing
 ↓
Dispatch
```

The UI must make this lifecycle feel like one connected application rather than a collection of disconnected modules.

---

# 2. Primary UX Principle

FMOS must be:

```text
Contextual
Visual
Fast
Predictable
Keyboard-friendly
CAD-friendly
Manufacturing-aware
Role-aware
Tenant-aware
AI-assisted
```

The UI must expose complexity progressively.

A designer should not see factory complexity unless needed.

A factory operator should not see unnecessary CAD complexity.

---

# 3. User Experience Architecture

The application should use:

```text
Global Shell
+
Module Navigation
+
Contextual Workspace
+
Right Inspector
+
Bottom/Secondary Panels
+
Command/Search
+
AI Copilot
+
Notifications
```

---

# 4. Primary Application Shell

Desktop layout:

```text
┌─────────────────────────────────────────────────────────────┐
│ LOGO │ Workspace │ Project │ Search │ AI │ Alerts │ User    │
├──────┼──────────────────────────────────────────────────────┤
│      │                                                      │
│ SIDE │                MAIN WORKSPACE                        │
│ NAV  │                                                      │
│      │                                                      │
│      │                                                      │
│      ├──────────────────────────────────────────────────────┤
│      │ Context / Properties / Timeline / Output             │
└──────┴──────────────────────────────────────────────────────┘
```

---

# 5. Global Header

Header must contain:

```text
Tenant Logo
Application Name
Workspace Switcher
Project Context
Global Search
AI Copilot
Notifications
Help
User Menu
```

---

# 6. Tenant Branding

Header should dynamically use:

```text
tenant logo
tenant application name
tenant theme
```

according to White-Label/SaaS specification.

---

# 7. Workspace Switcher

Allow users to switch:

```text
Design
Engineering
Sales
Manufacturing
MES
Administration
```

based on permission.

---

# 8. Project Context

When inside a project show:

```text
Project Name
Customer
Current Floor
Current Room
Revision
Status
```

---

# 9. Global Search

Search across authorized:

```text
customers
projects
rooms
furniture
materials
quotes
production orders
work orders
panels
documents
```

---

# 10. Command Palette

Keyboard shortcut:

```text
Ctrl/Cmd + K
```

Search commands:

```text
Create project
Create room
Add wall
Add furniture
Open catalog
Generate BOM
Generate quote
Open manufacturing
Open MES
Open AI Copilot
```

---

# 11. Global AI Copilot

AI should be available through:

```text
header button
keyboard shortcut
contextual action
```

The AI UI must follow the AI Specification.

---

# 12. Notification Center

Show:

```text
project updates
client approvals
production alerts
quality alerts
AI jobs
file processing
system alerts
```

---

# 13. User Menu

Contains:

```text
Profile
Preferences
Theme
Keyboard Shortcuts
Tenant Settings
Help
Logout
```

Only authorized items should appear.

---

# 14. Main Navigation

Recommended navigation:

```text
Dashboard
CRM
Projects
Design
Catalog
Engineering
Commercial
Manufacturing
MES
Reports
Documents
AI
Administration
```

---

# 15. Navigation Behavior

Navigation must support:

```text
expanded
collapsed
remembered state
```

---

# 16. Breadcrumbs

Every deep screen should show:

```text
Project > Floor > Room > Furniture
```

or equivalent context.

---

# 17. Status Indicators

Use consistent states:

```text
Draft
In Progress
Pending
Approved
Rejected
Blocked
On Hold
Completed
Cancelled
Archived
```

---

# 18. Global UI Components

Build reusable components:

```text
Button
IconButton
Input
Select
MultiSelect
DatePicker
ColorPicker
Slider
Toggle
Tabs
Accordion
Modal
Drawer
Tooltip
Toast
Badge
Avatar
Table
DataGrid
Tree
Card
Timeline
Stepper
ContextMenu
CommandPalette
FileUploader
```

---

# 19. Data Grid

All major business tables must support:

```text
sorting
filtering
search
column selection
pagination
export
saved views
bulk selection
bulk actions
```

---

# 20. Empty States

Every screen must have a meaningful empty state:

```text
No projects yet.
Create your first project.
```

Include primary CTA.

---

# 21. Loading States

Use:

```text
skeleton
progress
spinner
streaming
```

depending on operation.

Never leave a blank screen during processing.

---

# 22. Error States

Errors must provide:

```text
what happened
why where possible
what user can do
retry
```

---

# 23. Unsaved Changes

When navigating away from an editor with unsaved changes:

```text
Save
Discard
Cancel
```

---

# 24. Autosave

Design editors should support configurable autosave.

Show:

```text
Saving...
Saved 10 seconds ago
Offline
Save failed
```

---

# 25. Undo / Redo

CAD/design screens:

```text
Ctrl/Cmd + Z
Ctrl/Cmd + Shift + Z
```

must work consistently.

---

# 26. Keyboard Shortcuts

Provide shortcut help:

```text
?
```

or:

```text
Ctrl/Cmd + /
```

---

# 27. Screen Inventory

The application should implement at least:

```text
01 Login
02 Forgot Password
03 Tenant Onboarding
04 Dashboard
05 CRM
06 Customer
07 Project List
08 Project Overview
09 Project Setup
10 Building Planner
11 Floor Planner
12 Room Designer
13 2D CAD
14 Elevation
15 Section
16 3D/BIM
17 Material Editor
18 Furniture Catalog
19 Furniture Designer
20 Parametric Component Designer
21 Hardware Designer
22 BOM
23 BOQ
24 Pricing
25 Quote
26 Client Presentation
27 Client Approval
28 Manufacturing Overview
29 Production Order
30 Work Order
31 Panel List
32 Nesting
33 Nesting Detail
34 CNC/CAM
35 Machine Queue
36 MES Dashboard
37 Work Center
38 Shop Floor
39 QR Scanner
40 Panel Tracking
41 Panel Timeline
42 Quality
43 Rework
44 Packing
45 Dispatch
46 Reports
47 Documents
48 AI Copilot
49 AI Task Center
50 Knowledge Base
51 Catalog Admin
52 Material Admin
53 Hardware Admin
54 User Admin
55 Role Admin
56 Tenant Settings
57 Branding
58 Domain Management
59 Subscription
60 Usage
61 Integrations
62 API Management
63 Audit Log
64 Platform Admin
```

---

# 28. Login Screen

Route:

```text
/login
```

Elements:

```text
Tenant Logo
Application Name
Email
Password
Remember Me
Login
Forgot Password
SSO
Support
Terms
Privacy
```

---

# 29. Tenant Login Resolution

If custom domain is used:

```text
domain
→ tenant
→ tenant branding
→ tenant login
```

---

# 30. Login Validation

Show inline errors:

```text
Invalid credentials
Account locked
Tenant suspended
SSO required
```

---

# 31. Forgot Password

Route:

```text
/forgot-password
```

Fields:

```text
Email
```

Show security-safe response.

---

# 32. Tenant Onboarding

Route:

```text
/onboarding
```

Steps:

```text
Organization
Branding
Workspace
Factory
Catalog
Users
Preferences
Finish
```

---

# 33. Dashboard

Route:

```text
/dashboard
```

Dashboard changes based on role.

---

# 34. Designer Dashboard

Show:

```text
Recent Projects
Draft Projects
Pending Approvals
Upcoming Deadlines
Recent Designs
AI Suggestions
```

---

# 35. Factory Dashboard

Show:

```text
Production Today
Panels Pending
CNC Queue
Nesting Status
Quality Issues
Delayed Orders
Machine Status
```

---

# 36. Sales Dashboard

Show:

```text
Leads
Opportunities
Quotes
Pending Approvals
Pipeline
Expected Revenue
```

---

# 37. Admin Dashboard

Show:

```text
Users
Usage
Storage
AI Usage
Feature Usage
Subscription
Integrations
System Alerts
```

---

# 38. CRM Customer List

Route:

```text
/crm/customers
```

Columns:

```text
Customer
Contact
Projects
Status
Last Activity
Owner
```

Actions:

```text
Create
View
Edit
Archive
```

---

# 39. Customer Detail

Tabs:

```text
Overview
Contacts
Projects
Quotes
Documents
Activity
Notes
```

---

# 40. Project List

Route:

```text
/projects
```

Views:

```text
grid
table
kanban
```

Filters:

```text
status
owner
customer
date
factory
```

---

# 41. Create Project

Fields:

```text
Project Name
Customer
Project Type
Address
Designer
Factory
Currency
Measurement System
Due Date
```

---

# 42. Project Overview

Show:

```text
Project Header
Progress
Customer
Status
Design Summary
Commercial Summary
Manufacturing Summary
Recent Activity
Documents
```

---

# 43. Project Navigation

Tabs:

```text
Overview
Building
Design
Furniture
Materials
BOM
BOQ
Pricing
Documents
Client
Manufacturing
MES
Activity
```

---

# 44. Project Setup

Configure:

```text
units
scale
levels
floors
default wall thickness
default materials
design standards
manufacturing factory
pricing model
```

---

# 45. Building Planner

Route:

```text
/projects/{id}/building
```

Purpose:

Create:

```text
building
floors
zones
rooms
```

---

# 46. Building Planner Layout

```text
┌──────────┬──────────────────────────┬─────────────┐
│ Elements │       Drawing Area       │ Properties  │
│          │                          │             │
│ Building │                          │ Selected    │
│ Floor    │                          │ Object      │
│ Room     │                          │             │
└──────────┴──────────────────────────┴─────────────┘
```

---

# 47. Floor Planner

Tools:

```text
Wall
Room
Door
Window
Column
Beam
Stair
Opening
Dimension
Text
```

---

# 48. Room Creation

Dialog:

```text
Room Name
Room Type
Length
Width
Height
Floor
```

---

# 49. Room Designer

The Room Designer is the main interior design workspace.

Must support:

```text
2D
3D
Elevation
Material
Furniture
Lighting
Annotations
```

---

# 50. Room Designer Toolbar

Tools:

```text
Select
Pan
Zoom
Wall
Door
Window
Furniture
Material
Dimension
Measure
Annotate
Section
Elevation
3D
```

---

# 51. 2D CAD Workspace

Route:

```text
/design/2d/{roomId}
```

Layout:

```text
Top Toolbar
Left Tool Palette
Canvas
Right Inspector
Bottom Status Bar
```

---

# 52. CAD Canvas

Must support:

```text
grid
snap
zoom
pan
selection
multi-select
dimensions
guides
rulers
coordinates
```

---

# 53. CAD Grid

Configurable:

```text
grid spacing
major grid
minor grid
snap spacing
```

---

# 54. CAD Rulers

Show:

```text
horizontal ruler
vertical ruler
cursor coordinate
```

---

# 55. CAD Selection

Selected object shows:

```text
bounding box
handles
dimensions
rotation
properties
```

---

# 56. CAD Transform

Support:

```text
move
rotate
scale where valid
mirror
duplicate
align
distribute
```

---

# 57. Wall Tool

Wall properties:

```text
length
thickness
height
material
finish
```

---

# 58. Door Tool

Properties:

```text
width
height
frame
swing
swing direction
material
```

---

# 59. Window Tool

Properties:

```text
width
height
sill height
frame
opening
material
```

---

# 60. Structural Elements

Support:

```text
beam
column
slab
opening
```

with dimension-aware properties.

---

# 61. Dimension Tool

Support:

```text
linear
aligned
angular
radial
diameter
```

---

# 62. Annotation Tool

Support:

```text
text
leader
callout
symbol
```

---

# 63. Elevation Screen

Route:

```text
/design/elevation/{roomId}
```

Show:

```text
wall elevation
furniture
dimensions
materials
annotations
```

---

# 64. Section Screen

Show:

```text
cut plane
walls
slabs
ceiling
furniture
dimensions
```

---

# 65. 3D/BIM Workspace

Route:

```text
/design/3d/{roomId}
```

Layout:

```text
Toolbar
Scene Tree
3D Viewport
Inspector
```

---

# 66. 3D Controls

Support:

```text
orbit
pan
zoom
walk
first-person
fit view
top
front
left
right
isometric
```

---

# 67. 3D Scene Tree

Tree:

```text
Building
 ├─ Floor
 │   ├─ Room
 │   │   ├─ Walls
 │   │   ├─ Doors
 │   │   ├─ Windows
 │   │   ├─ Furniture
 │   │   └─ Fixtures
```

---

# 68. 3D Selection

Selecting a 3D object must synchronize:

```text
3D
2D
elevation
properties
```

---

# 69. Real-Time Synchronization UI

When changing an object in 3D:

```text
3D → Model → 2D → Elevation → BOM
```

must refresh according to domain rules.

---

# 70. Furniture Catalog

Route:

```text
/catalog/furniture
```

Views:

```text
cards
grid
list
```

Each card:

```text
preview
name
code
category
dimensions
materials
availability
```

---

# 71. Furniture Search

Search:

```text
name
code
category
dimensions
style
tags
```

---

# 72. Furniture Filters

Support:

```text
category
width
height
depth
style
brand
material
status
```

---

# 73. Furniture Preview

Preview:

```text
2D
3D
dimensions
components
materials
BOM
```

---

# 74. Furniture Placement

From catalog:

```text
Drag into room
```

or:

```text
Add to Room
```

---

# 75. Furniture Inspector

Show:

```text
Dimensions
Construction
Materials
Hardware
Finish
Parameters
BOM
Manufacturing
Pricing
```

---

# 76. Furniture Designer

Route:

```text
/furniture/designer/{id}
```

Workspace:

```text
Component Tree
2D/3D Preview
Parameter Panel
Materials
Hardware
Validation
```

---

# 77. Parametric Component Designer

Support:

```text
carcass
shelf
drawer
shutter
back panel
toe kick
hardware
edge banding
```

---

# 78. Parameter Editor

Fields:

```text
width
height
depth
thickness
offset
gap
count
```

Changes should update geometry live.

---

# 79. Component Tree

Example:

```text
Wardrobe
 ├─ Carcass
 ├─ Left Side
 ├─ Right Side
 ├─ Top
 ├─ Bottom
 ├─ Shelves
 ├─ Drawers
 ├─ Shutters
 └─ Hardware
```

---

# 80. Component Validation Panel

Show:

```text
Valid
Warning
Error
```

with actionable messages.

---

# 81. Material Catalog

Route:

```text
/catalog/materials
```

Cards:

```text
image/swatches
code
name
brand
type
thickness
price
availability
```

---

# 82. Material Detail

Tabs:

```text
Overview
Properties
Pricing
Applications
Suppliers
Inventory
Usage
```

---

# 83. Material Assignment

When assigning material show:

```text
selected component
current material
recommended materials
alternatives
```

---

# 84. Laminate Browser

Support:

```text
visual swatches
brand
catalog
code
finish
color
texture
```

---

# 85. Hardware Catalog

Show:

```text
hinges
drawer systems
handles
connectors
fasteners
lifts
accessories
```

---

# 86. Hardware Detail

Show:

```text
dimensions
compatibility
manufacturer
cost
installation
3D model
```

---

# 87. BOM Screen

Route:

```text
/project/{id}/bom
```

Show hierarchical BOM:

```text
Project
 ├─ Room
 │   ├─ Furniture
 │   │   ├─ Panel
 │   │   ├─ Hardware
 │   │   └─ Accessories
```

---

# 88. BOM Columns

```text
Code
Description
Category
Quantity
Unit
Material
Dimensions
Source
Status
```

---

# 89. BOM Actions

```text
Refresh
Recalculate
Export
Compare Revision
```

---

# 90. BOQ Screen

Show:

```text
Item
Description
Quantity
Unit
Rate
Amount
Tax
Total
```

---

# 91. Pricing Screen

Pricing model selector:

```text
Raw Material
Panel Based
Hybrid
```

---

# 92. Pricing Breakdown

Show:

```text
Materials
Hardware
Edge Banding
Labor
Machine
Nesting/Waste
Overhead
Margin
Tax
Final Price
```

---

# 93. Pricing Explainability

Allow:

```text
Why did price change?
```

which opens AI-assisted explanation grounded in pricing data.

---

# 94. Quote Screen

Sections:

```text
Customer
Project
Scope
Items
Pricing
Taxes
Terms
Validity
Attachments
```

---

# 95. Quote Actions

```text
Save Draft
Preview
Send
Approve
Reject
Revise
Download PDF
```

---

# 96. Client Presentation

Full-screen presentation mode:

```text
Project Cover
Design
3D Views
Materials
Furniture
Pricing
Scope
Terms
Approval
```

---

# 97. Client Approval

Show:

```text
design preview
quote
documents
approval comments
Approve
Request Changes
Reject
```

---

# 98. Manufacturing Overview

Route:

```text
/manufacturing
```

Dashboard:

```text
Orders
WIP
Panels
Nesting
CNC
Quality
Packing
Dispatch
```

---

# 99. Production Order List

Columns:

```text
Order
Project
Customer
Due Date
Priority
Status
Progress
Factory
```

---

# 100. Production Order Detail

Tabs:

```text
Overview
Furniture
Panels
BOM
Nesting
CNC
Work Orders
Quality
Packing
Timeline
```

---

# 101. Production Order Timeline

Visual timeline:

```text
Released
 ↓
Cutting
 ↓
Edge Banding
 ↓
Drilling
 ↓
Assembly
 ↓
QC
 ↓
Packing
 ↓
Dispatch
```

---

# 102. Work Order Screen

Show:

```text
Work Order
Operation
Machine
Operator
Queue Position
Required Materials
Panels
Status
```

---

# 103. Work Order Actions

Depending on permission:

```text
Start
Pause
Complete
Hold
Rework
Cancel
```

---

# 104. Panel List

Route:

```text
/manufacturing/panels
```

Filters:

```text
production order
operation
status
machine
material
location
```

---

# 105. Panel Detail

Show:

```text
Panel Code
QR
Barcode
Dimensions
Material
Edge Banding
Operations
Current Status
Current Location
Production Order
```

---

# 106. Panel Timeline

Timeline:

```text
Created
Nested
Cut
Edge Banded
Drilled
QC
Packed
Dispatched
```

---

# 107. Nesting Dashboard

Show:

```text
Nesting Jobs
Sheets
Utilization
Waste
Panels
Status
```

---

# 108. Nesting Workspace

Layout:

```text
Panel List
Sheet Preview
Nesting Result
Statistics
Actions
```

---

# 109. Nesting Sheet View

Show:

```text
sheet boundary
panel rectangles
grain direction
cut paths
labels
waste
```

---

# 110. Nesting Statistics

Display:

```text
Sheet Area
Used Area
Waste Area
Utilization %
Number of Panels
```

---

# 111. CNC/CAM Screen

Show:

```text
CNC Programs
Machine
Program Version
Operations
Status
```

---

# 112. CNC Program Detail

Show:

```text
Program metadata
Machine
Tool list
Operations
Panels
Warnings
Validation
```

---

# 113. CNC Validation Panel

Show:

```text
Geometry Valid
Machine Compatible
Tool Compatible
Dimensions Valid
```

---

# 114. Machine Queue

Factory operator view:

```text
Now Running
Next
Queued
Blocked
Completed
```

---

# 115. MES Dashboard

Route:

```text
/mes
```

Show:

```text
Production Today
Orders
WIP
Machine Utilization
Downtime
Quality
On-Time %
```

---

# 116. MES Work Center Screen

Show:

```text
work center
machines
current job
queue
operator
status
```

---

# 117. Shop-Floor Screen

Designed for large displays.

Show:

```text
current operation
current job
next job
machine status
alerts
```

Minimal navigation.

---

# 118. QR Scanner Screen

Route:

```text
/mes/scan
```

Optimized for:

```text
mobile
tablet
handheld scanner
```

---

# 119. QR Scanner UI

Large:

```text
Scan QR
```

Support:

```text
camera
hardware scanner
manual code entry
```

---

# 120. Scan Result

Show:

```text
Panel
Current Status
Expected Operation
Current Location
Next Action
```

---

# 121. Wrong Scan

If panel is scanned at wrong station:

```text
BLOCKED
```

Show:

```text
Expected:
CNC Drilling

Current:
Edge Banding
```

---

# 122. Panel Action

Operator can:

```text
Start
Complete
Move
Hold
Rework
Scrap
```

only if permitted.

---

# 123. Panel Tracking

Show physical journey:

```text
Cutting
 ↓
Edge Banding
 ↓
Drilling
 ↓
QC
 ↓
Assembly
 ↓
Packing
```

---

# 124. Quality Dashboard

Show:

```text
Inspections
Pass %
Fail %
Rework
Scrap
Top Defects
```

---

# 125. Quality Inspection

Show:

```text
Panel
Work Order
Checklist
Measurements
Photos
Comments
Result
```

---

# 126. Rework Screen

Show:

```text
Defect
Root Cause
Action
Assigned To
Due Date
Status
```

---

# 127. Packing Screen

Show:

```text
Order
Furniture
Panels
Packages
Missing Components
Verification
```

---

# 128. Packing Verification

Support:

```text
scan panel
scan package
verify contents
complete package
```

---

# 129. Dispatch Screen

Show:

```text
Customer
Order
Packages
Address
Documents
Dispatch Status
```

---

# 130. Dispatch Verification

Require:

```text
package verification
document verification
quantity verification
```

---

# 131. Reports Dashboard

Categories:

```text
Sales
Design
Commercial
Manufacturing
MES
Quality
Inventory
AI
SaaS
```

---

# 132. Report Builder

Support:

```text
filters
columns
grouping
charts
saved reports
export
```

---

# 133. Documents Screen

Show:

```text
folders
documents
versions
tags
permissions
```

---

# 134. Document Preview

Support:

```text
PDF
images
text
CAD exports
```

---

# 135. AI Copilot Screen

Route:

```text
/ai
```

Layout:

```text
Conversation
Context
Sources
Actions
```

---

# 136. Context Indicator

AI UI should show:

```text
Context:
Project ABC
Room: Kitchen
Selected: Base Cabinet B03
```

---

# 137. AI Tool Activity

Display user-friendly activity:

```text
Checking furniture dimensions...
Searching catalog...
Validating manufacturing rules...
```

---

# 138. AI Sources

Show:

```text
Catalog
Project Data
SOP
Manufacturing Rule
```

with clickable references where possible.

---

# 139. AI Action Review

For changes:

```text
Proposed Changes
```

with:

```text
Apply
Edit
Reject
```

---

# 140. AI Task Center

Show asynchronous AI jobs:

```text
Floorplan processing
Document processing
Catalog classification
3D generation
Report generation
```

---

# 141. AI Job Detail

Show:

```text
status
progress
input
output
errors
result
```

---

# 142. Knowledge Base

Route:

```text
/knowledge
```

Show:

```text
Documents
Categories
Versions
Status
Search
```

---

# 143. Knowledge Document Detail

Show:

```text
metadata
version
approval
chunks
usage
```

---

# 144. Catalog Administration

Platform/Tenant Admin:

```text
Furniture
Materials
Laminates
Hardware
Suppliers
```

---

# 145. Material Admin

Actions:

```text
Create
Import
Edit
Archive
Bulk Import
Export
```

---

# 146. Hardware Admin

Support:

```text
manufacturer
code
dimensions
compatibility
price
3D model
```

---

# 147. User Administration

Route:

```text
/admin/users
```

Columns:

```text
Name
Email
Role
Factory
Status
Last Login
```

---

# 148. User Detail

Tabs:

```text
Profile
Roles
Factories
Projects
Activity
Security
```

---

# 149. Role Administration

Show:

```text
Role
Scope
Users
Permissions
Status
```

---

# 150. Permission Matrix

Matrix:

```text
Module × Action
```

Actions:

```text
View
Create
Edit
Delete
Approve
Export
Execute
Admin
```

---

# 151. Tenant Settings

Sections:

```text
Organization
Branding
Localization
Documents
Email
Notifications
Security
AI
Manufacturing
MES
API
```

---

# 152. Branding Screen

Preview:

```text
Login
Header
Client Portal
Document
Email
```

Allow:

```text
logo upload
colors
application name
favicon
```

---

# 153. Domain Management

Show:

```text
Domain
Type
Verification
SSL
Status
Primary
```

Actions:

```text
Add
Verify
Set Primary
Remove
```

---

# 154. Subscription Screen

Show:

```text
Current Plan
Usage
Limits
Billing
Features
Upgrade
Cancel
```

---

# 155. Usage Screen

Show:

```text
Users
Projects
Storage
AI
API
Manufacturing
```

with progress indicators.

---

# 156. Integrations Screen

Cards:

```text
Email
Storage
Accounting
CRM
CNC
ERP
SSO
AI Providers
```

---

# 157. Integration Detail

Show:

```text
Status
Configuration
Credentials
Last Sync
Logs
Test Connection
```

Secrets must be masked.

---

# 158. API Management

Show:

```text
API Keys
OAuth Apps
Webhooks
Usage
Logs
```

---

# 159. API Key Creation

Fields:

```text
Name
Scopes
Expiration
```

Show secret only once.

---

# 160. Webhook Management

Show:

```text
Endpoint
Events
Status
Last Delivery
Failure Rate
```

---

# 161. Audit Log

Filters:

```text
user
action
resource
date
IP
```

---

# 162. Platform Admin Tenant List

Show:

```text
Tenant
Plan
Status
Users
Usage
Created
Last Activity
```

---

# 163. Platform Tenant Detail

Tabs:

```text
Overview
Users
Subscription
Usage
Domains
Branding
Features
Health
Audit
Support
```

---

# 164. Support Impersonation UI

Require:

```text
reason
duration
confirmation
```

Then show prominent banner.

---

# 165. Global Design System

Create design tokens:

```text
spacing
radius
typography
shadows
colors
z-index
motion
```

---

# 166. Typography

Use a readable modern UI font.

Support:

```text
regular
medium
semibold
bold
```

Avoid excessive font weights.

---

# 167. Color System

Semantic colors:

```text
Primary
Success
Warning
Danger
Info
Neutral
```

Tenant colors must map into controlled tokens.

---

# 168. Accessibility

Target:

```text
WCAG 2.1 AA
```

where practical.

Support:

```text
keyboard navigation
focus states
ARIA labels
contrast
screen reader semantics
```

---

# 169. CAD Accessibility

CAD canvas must support:

```text
keyboard shortcuts
numeric property editing
```

because pure mouse operation is insufficient.

---

# 170. Responsive Strategy

Desktop-first for:

```text
CAD
3D
BIM
Furniture Designer
Nesting
CNC
MES dashboards
```

Tablet/mobile-first for:

```text
QR Scanner
Panel Tracking
Quality
Shop Floor
Packing
Dispatch
```

---

# 171. Mobile Navigation

Use:

```text
bottom navigation
large actions
camera scanner
```

for shop-floor workflows.

---

# 172. Shop-Floor Touch Targets

Minimum recommended touch target:

```text
44 × 44 px
```

---

# 173. Shop-Floor Theme

High visibility:

```text
large typography
high contrast
minimal controls
clear status colors
```

---

# 174. Offline UX

For QR/MES mobile workflows show:

```text
Online
Offline
Syncing
Sync Failed
```

---

# 175. Offline Queue

Show:

```text
Pending Scans: 4
```

with retry.

---

# 176. AI Streaming UI

AI responses may stream progressively.

Show:

```text
Thinking/working indicator
partial response
tool activity
```

Do not expose hidden chain-of-thought.

---

# 177. AI Error UI

Show:

```text
AI temporarily unavailable.
Retry
```

and preserve user's message.

---

# 178. AI Approval UX

Never make high-impact AI changes happen silently.

Show:

```text
Proposed
Validated
Ready to Apply
```

---

# 179. 2D/3D Synchronization UX

If object selected in 2D:

```text
3D object highlights
```

If selected in 3D:

```text
2D object highlights
```

---

# 180. View Synchronization

Support:

```text
2D
3D
Elevation
Section
```

linked selection.

---

# 181. Split View

Allow:

```text
2D | 3D
```

or:

```text
Elevation | 3D
```

for advanced users.

---

# 182. Property Inspector

Inspector sections:

```text
Identity
Geometry
Materials
Construction
Manufacturing
Pricing
Metadata
```

Collapse/expand sections.

---

# 183. Inspector Editing

Numeric values should support:

```text
keyboard input
unit display
validation
reset
```

---

# 184. Unit Display

Example:

```text
Width: 2400 mm
```

or:

```text
Width: 7' 10.5"
```

based on project settings.

---

# 185. Dimension Validation

Invalid value should show:

```text
red field
error message
rule reference where useful
```

---

# 186. Context Menus

Right-click selected CAD/furniture object:

```text
Edit
Duplicate
Move
Rotate
Mirror
Material
Properties
Generate BOM
Delete
AI Assist
```

---

# 187. Drag and Drop

Support drag/drop:

```text
catalog → room
material → furniture
hardware → component
```

where meaningful.

---

# 188. Command Search

Commands should be searchable by natural language where AI is enabled.

Example:

```text
"Add a 600mm base cabinet"
```

---

# 189. Selection Summary

Multi-select should show:

```text
3 objects selected
```

with supported batch operations.

---

# 190. Bulk Edit

Support:

```text
material
finish
status
owner
```

where safe.

---

# 191. Revision Selector

Show:

```text
Current Revision: R08
```

Allow:

```text
compare
restore
create revision
```

subject to permissions.

---

# 192. Revision Compare

Visual diff:

```text
Added
Removed
Changed
```

---

# 193. Activity Timeline

Every project should show:

```text
who
did what
when
```

---

# 194. Comments

Support contextual comments on:

```text
project
room
furniture
drawing
quote
production order
panel
```

---

# 195. Mentions

Support:

```text
@designer
@engineer
@factory-manager
```

where user directory allows.

---

# 196. Notifications

Notification should deep-link to relevant object.

---

# 197. Search Result UX

Grouped results:

```text
Projects
Furniture
Materials
Orders
Panels
Documents
```

---

# 198. Global Quick Create

Header button:

```text
+
```

Actions:

```text
Customer
Project
Room
Furniture
Quote
Production Order
```

based on permissions.

---

# 199. Project Wizard

For new projects:

```text
Customer
Project Details
Building
Rooms
Design
Commercial
Factory
```

allow skip where permitted.

---

# 200. Factory Wizard

Configure:

```text
Factory
Work Centers
Machines
Materials
Operations
Production Rules
```

---

# 201. Machine Setup

Show:

```text
Machine Name
Type
Manufacturer
Model
Working Area
Supported Materials
Tools
Status
```

---

# 202. Work Center Setup

Show:

```text
Work Center
Operations
Machines
Operators
Capacity
Schedule
```

---

# 203. MES Station UI

Station screen should show only relevant:

```text
current operation
scan
instructions
status
next action
```

---

# 204. Operator Instructions

Display:

```text
visual instructions
dimensions
material
edge banding
drilling
special notes
```

---

# 205. Panel Label Preview

Show:

```text
QR
Barcode
Panel Code
Project
Furniture
Dimensions
Material
Operation
```

---

# 206. Label Printing

Options:

```text
Print
Reprint
Preview
Printer
Quantity
```

---

# 207. Quality Photo Capture

Mobile quality screen:

```text
Take Photo
Attach Photo
Mark Defect
Comment
Submit
```

---

# 208. Defect Annotation

Allow photo annotation:

```text
circle
arrow
text
```

---

# 209. Packing Scanner

Show:

```text
Scan Package
Scan Panel
Match
Confirm
```

---

# 210. Dispatch Mobile

Show:

```text
Order
Customer
Package Count
Verification
Signature
Dispatch
```

---

# 211. Dashboard Widgets

Widgets should support:

```text
move
resize
hide
save layout
```

where applicable.

---

# 212. Role-Based Dashboard

Dashboard widget visibility must be permission-based.

---

# 213. Tenant Dashboard Branding

Tenant dashboard should use tenant-specific:

```text
logo
colors
application name
```

---

# 214. Dark Mode

Support:

```text
light
dark
system
```

but CAD canvas may use a specialized canvas theme.

---

# 215. CAD Dark Mode

Provide CAD-specific:

```text
dark canvas
light canvas
high contrast
```

---

# 216. 3D Display Modes

Support:

```text
shaded
textured
wireframe
hidden line
realistic
```

subject to performance.

---

# 217. 3D Performance UI

Show warning if scene becomes heavy:

```text
High scene complexity
```

Allow:

```text
Simplify View
Hide Furniture
Hide Details
```

---

# 218. Large Project Handling

Support:

```text
lazy loading
level of detail
virtualized lists
```

---

# 219. Data Tables Performance

Large datasets must use:

```text
server pagination
virtual scrolling
```

where appropriate.

---

# 220. Loading UX for CAD

Do not block entire application while:

```text
BOM
nesting
AI
CNC
```

jobs run.

---

# 221. Background Job Notification

When job completes:

```text
toast
notification
activity
```

with deep link.

---

# 222. Modal Rules

Use modal for:

```text
short confirmation
small form
critical action
```

Use full-page/drawer for complex forms.

---

# 223. Drawer Rules

Use right drawer for:

```text
properties
quick edit
AI context
details
```

---

# 224. Full Page Editor

Use full-page for:

```text
2D CAD
3D
Furniture Designer
Nesting
CNC
```

---

# 225. Confirmation Dialog

Must show:

```text
action
impact
affected objects
```

before destructive operations.

---

# 226. Destructive Actions

Require confirmation for:

```text
delete
scrap
cancel production
remove material
remove furniture
delete tenant
```

---

# 227. Undoable Actions

Prefer undo over confirmation when safe.

---

# 228. Toast Notifications

Use for:

```text
saved
updated
copied
export started
export completed
```

Do not use toast for critical information that disappears quickly.

---

# 229. Progress Indicators

For long operations show:

```text
progress %
current stage
cancel if supported
```

---

# 230. File Upload UX

Show:

```text
drag/drop
file picker
progress
validation
preview
retry
```

---

# 231. File Import Wizard

For catalog imports:

```text
Upload
Map Columns
Validate
Preview
Import
Summary
```

---

# 232. Import Validation

Show:

```text
Valid rows
Warnings
Errors
Duplicates
```

---

# 233. Bulk Import Result

Provide:

```text
Download error report
```

---

# 234. Data Export

Export modal:

```text
format
columns
filters
scope
```

---

# 235. Saved Views

Users can save:

```text
filters
columns
sort
grouping
```

for major data grids.

---

# 236. Role-Specific UI

Do not merely hide unauthorized buttons after rendering.

Backend authorization remains authoritative.

---

# 237. Permission-Aware Components

Frontend should use:

```text
can("project.edit")
can("manufacturing.release")
can("mes.scan")
```

to conditionally render actions.

---

# 238. White-Label UI

Tenant-specific branding must not alter functional information architecture.

---

# 239. AI UI

AI features should enhance existing screens rather than create a separate application silo.

Example:

```text
Furniture Inspector
   ↓
AI Assist
```

instead of requiring users to leave the Furniture Designer.

---

# 240. AI Contextual Actions

Examples:

```text
Optimize
Explain
Suggest Alternative
Check Manufacturing
Reduce Cost
Find Similar
```

---

# 241. Manufacturing AI Actions

On production order:

```text
Explain Delay
Find Bottleneck
Suggest Priority
Check Material Risk
```

---

# 242. Panel AI Actions

On panel:

```text
Explain Status
Trace Genealogy
Explain Rework
Find Related Panels
```

---

# 243. Client AI Restrictions

Client-facing AI should only use:

```text
shared project data
approved documents
approved design data
```

---

# 244. Client Presentation UX

Client view must remove:

```text
internal costs
manufacturing rules
internal notes
factory information
```

unless explicitly shared.

---

# 245. Internal vs Client Data

UI must distinguish:

```text
Internal
Client Visible
```

for relevant documents/comments.

---

# 246. Project Status UX

Use progress:

```text
Design
Engineering
Approval
Production
Quality
Packing
Dispatch
```

---

# 247. Project Health

Display:

```text
On Track
At Risk
Delayed
Blocked
```

---

# 248. Risk Panel

Show:

```text
Late approval
Material shortage
Engineering issue
Production delay
Quality issue
```

---

# 249. Commercial Health

Show:

```text
Quoted
Approved
Cost
Margin
Payment
```

according to permissions.

---

# 250. Manufacturing Health

Show:

```text
Panels
WIP
Machine
Quality
Delivery
```

---

# 251. Screen-to-Screen Navigation

Each object must provide related navigation.

Example:

```text
Furniture
→ BOM
→ Pricing
→ Panels
→ Production
→ QR
```

---

# 252. Deep Links

Every major resource should have a stable route.

Example:

```text
/projects/123
/projects/123/rooms/45
/furniture/789
/panels/ABC123
/production-orders/PO-1002
```

---

# 253. Browser Navigation

Support:

```text
Back
Forward
Refresh
Direct URL
```

without losing valid state unnecessarily.

---

# 254. URL State

Filters/tabs/search may be encoded in URL where useful.

---

# 255. Autosave Conflict

If same object changes elsewhere:

```text
Conflict detected
```

show:

```text
Your changes
Other changes
Merge/Reload
```

---

# 256. Collaborative UX

Future support:

```text
other users viewing
other users editing
presence
```

architecture should not prevent collaboration.

---

# 257. Performance Targets

Initial UI target:

```text
application shell < 2 sec where infrastructure permits
standard page interaction < 100 ms perceived response
```

CAD/3D performance must target smooth interaction.

---

# 258. CAD Performance

Target:

```text
60 FPS where practical
```

for normal room scenes.

---

# 259. 3D Performance

Use:

```text
LOD
instancing
lazy loading
asset caching
geometry reuse
```

---

# 260. Accessibility and Keyboard

All non-canvas UI must be fully keyboard navigable.

---

# 261. Browser Support

Target current:

```text
Chrome
Edge
Firefox
Safari
```

versions supported by project policy.

---

# 262. Mobile Browser

QR/MES workflows must support modern mobile browsers.

---

# 263. Security UX

Never display:

```text
API secrets
passwords
private keys
```

in plain text after creation.

---

# 264. Session Expiration

Show:

```text
Session expiring
Continue
Logout
```

where applicable.

---

# 265. Unauthorized UI

Show:

```text
Access denied
```

without leaking resource information.

---

# 266. 404 UI

Tenant-branded 404 where appropriate.

---

# 267. 500 UI

Friendly:

```text
Something went wrong.
Try again.
Reference ID: ...
```

---

# 268. Offline UI

For supported shop-floor workflows:

```text
Offline Mode
```

must be obvious but not alarming.

---

# 269. Sync Conflict UI

Show:

```text
4 scans waiting
1 conflict
```

with resolution action.

---

# 270. Notification Drawer

Sections:

```text
All
Mentions
Approvals
Production
System
```

---

# 271. Help Center

Support:

```text
Documentation
Keyboard Shortcuts
Videos
Contact Support
```

---

# 272. Contextual Help

Each complex module should have:

```text
?
```

linking to relevant help.

---

# 273. Tooltips

Use for:

```text
icons
unfamiliar controls
technical fields
```

Avoid tooltip-only critical instructions.

---

# 274. Onboarding Tours

Provide optional tours for:

```text
2D CAD
3D
Furniture Designer
Manufacturing
MES
AI
```

---

# 275. First-Time Empty State

Guide user with:

```text
1. Create Project
2. Create Room
3. Start Design
```

---

# 276. Contextual Quick Actions

On empty project:

```text
Create Building
Import Floorplan
Use AI
```

---

# 277. Import Floorplan UI

Flow:

```text
Upload
 ↓
Analyze
 ↓
Review Detections
 ↓
Correct
 ↓
Create CAD
 ↓
Create 3D
```

---

# 278. AI Detection Review

Show overlays:

```text
Walls
Doors
Windows
Dimensions
Rooms
```

with confidence.

---

# 279. AI Review Controls

Allow:

```text
Accept
Reject
Edit
Merge
Split
```

---

# 280. Design Proposal UI

AI-generated design proposal should show:

```text
visual
parameters
materials
estimated price
manufacturing status
```

---

# 281. AI Design Comparison

Compare:

```text
Option A
Option B
Option C
```

with:

```text
cost
storage
materials
manufacturability
```

---

# 282. AI Action Confirmation

High-impact changes:

```text
Apply to Design
```

must be explicit.

---

# 283. Manufacturing Release UI

Before release:

```text
BOM valid
Pricing valid
Nesting valid
CNC valid
Materials available
```

show validation checklist.

---

# 284. Production Release Dialog

Show:

```text
Orders
Panels
Materials
Warnings
Approvals
```

and:

```text
Release Production
```

---

# 285. CNC Release Dialog

Show:

```text
Machine
Program
Version
Validation
Tooling
Warnings
```

---

# 286. Quality Release UI

Show:

```text
Inspection status
Open defects
Rework
Approval
```

---

# 287. Packing Release UI

Show:

```text
all panels verified
all components verified
quality complete
```

---

# 288. Dispatch Release UI

Show:

```text
package count
documents
address
verification
```

---

# 289. Reports UI

Charts must support:

```text
hover
filter
drill-down
export
```

---

# 290. Dashboard Drilldown

Clicking:

```text
Delayed Orders
```

opens filtered production order list.

---

# 291. Manufacturing Heatmap

Optional visualization:

```text
work center utilization
machine bottleneck
```

---

# 292. Panel Heatmap

Optional:

```text
panel status by order
```

---

# 293. Design Analytics

Show:

```text
projects
rooms
furniture count
design completion
approval cycle
```

---

# 294. Sales Analytics

Show:

```text
pipeline
quote conversion
average project value
```

---

# 295. Factory Analytics

Show:

```text
throughput
utilization
waste
quality
on-time delivery
```

---

# 296. AI Analytics

Show:

```text
AI requests
accepted suggestions
rejected suggestions
cost
latency
```

---

# 297. SaaS Analytics

Platform Admin:

```text
tenants
active users
feature adoption
storage
AI usage
subscription
```

---

# 298. Design Review Mode

Full-screen:

```text
3D
Materials
Annotations
Comments
Approval
```

---

# 299. Client Approval UX

Large:

```text
Approve
Request Changes
```

with comments.

---

# 300. Approval Audit

Show:

```text
who
when
what version
decision
comment
```

---

# 301. Document Approval

Same approval pattern for:

```text
quote
BOQ
design
manufacturing package
```

---

# 302. Version Lock

Approved design should be visibly marked:

```text
APPROVED — REV R08
```

---

# 303. Manufacturing Revision Lock

Released production package must identify:

```text
design revision
BOM revision
nesting revision
CNC revision
```

---

# 304. Cross-Module Consistency

UI must show revision relationships.

Example:

```text
Design R08
BOM R08
Nesting R08
CNC R08
```

---

# 305. Change Impact UI

When design changes after manufacturing release:

```text
Change detected
```

show impact:

```text
BOM affected
Nesting affected
CNC affected
Production affected
```

---

# 306. Change Impact Actions

```text
Review
Regenerate
Create New Revision
Cancel
```

---

# 307. Manufacturing Regeneration UI

Show affected:

```text
panels
nesting
CNC
labels
```

---

# 308. Panel Reprint UI

Show:

```text
reason
operator
label count
```

and audit.

---

# 309. Audit UI

Timeline format:

```text
10:21 — Designer changed width
10:22 — BOM regenerated
10:24 — Nesting regenerated
10:25 — CNC regenerated
```

---

# 310. UI State Architecture

Use explicit state domains:

```text
authState
tenantState
projectState
designState
catalogState
manufacturingState
mesState
aiState
notificationState
```

---

# 311. State Isolation

Tenant/project state must be reset when context changes.

---

# 312. API Error Mapping

Frontend should map API errors to:

```text
validation
authorization
conflict
not found
server
network
```

---

# 313. Optimistic UI

Allowed only for safe actions:

```text
favorite
view preference
non-critical UI state
```

Avoid optimistic state for:

```text
production
pricing
CNC
quality
```

---

# 314. Confirmation for Critical Actions

Use explicit confirmation for:

```text
release
scrap
delete
approve
dispatch
```

---

# 315. Screen-Level Telemetry

Track UI events:

```text
screen_view
button_click
action_success
action_failure
```

without collecting unnecessary personal data.

---

# 316. UX Analytics

Track product behavior to improve UX:

```text
time to first design
project completion
CAD usage
catalog usage
quote conversion
manufacturing release
MES scan success
```

---

# 317. UI Testing

Every major screen must have:

```text
unit tests
component tests
integration tests
E2E tests
```

as appropriate.

---

# 318. Visual Regression

Critical screens:

```text
login
dashboard
2D CAD
3D
furniture designer
quote
manufacturing
MES
QR scanner
client portal
```

should have visual regression coverage.

---

# 319. Accessibility Testing

Test:

```text
keyboard
screen reader
contrast
focus
responsive layout
```

---

# 320. Responsive Testing

Test:

```text
desktop
laptop
tablet
mobile
shop-floor handheld
```

for relevant workflows.

---

# 321. Browser Testing

Test supported browsers defined by project policy.

---

# 322. Performance Testing

Measure:

```text
first load
navigation
CAD interaction
3D rendering
large tables
search
AI response
file upload
MES scan
```

---

# 323. Screen Implementation Contract

Every screen implementation must define:

```text
route
permission
data source
API endpoints
components
states
loading
empty
error
success
responsive behavior
keyboard shortcuts
analytics
```

---

# 324. Screen Documentation Template

Cursor should create a component/spec record:

```text
Screen Name:
Route:
Purpose:
Roles:
Permissions:
Primary Actions:
Secondary Actions:
Data:
API:
States:
Validation:
Error Handling:
Responsive:
Accessibility:
Telemetry:
```

---

# 325. Cursor Pre-Implementation Analysis

Before implementing UI, Cursor MUST inspect:

```text
existing HTML
existing CSS
existing JS
existing components
existing Tailwind/design system
existing 2D canvas
existing Three.js
existing APIs
existing routes
existing RBAC
existing project context
existing catalog
existing manufacturing
existing MES
existing AI
```

Cursor must produce:

```text
CURRENT SCREEN INVENTORY
CURRENT ROUTES
CURRENT COMPONENT INVENTORY
CURRENT DESIGN SYSTEM
CURRENT DUPLICATES
CURRENT UX GAPS
CURRENT API/UI MISMATCHES
CURRENT RESPONSIVE GAPS
CURRENT ACCESSIBILITY GAPS
CURRENT CAD UI
CURRENT 3D UI
CURRENT MES UI
TARGET SCREEN ARCHITECTURE
MIGRATION PLAN
```

---

# 326. Cursor UI Implementation Rules

Cursor MUST:

```text
reuse existing components
reuse existing API clients
reuse existing authentication
reuse existing RBAC
reuse existing design tokens
reuse existing CAD engines
reuse existing 3D engine
```

before creating duplicates.

---

# 327. Frontend Architecture

Recommended:

```text
src/
  app/
  components/
    ui/
    layout/
    forms/
    data/
    feedback/
  modules/
    crm/
    projects/
    building/
    cad2d/
    bim3d/
    furniture/
    catalog/
    bom/
    boq/
    pricing/
    quotes/
    manufacturing/
    nesting/
    cnc/
    mes/
    qr/
    quality/
    packing/
    dispatch/
    reports/
    documents/
    ai/
    admin/
  services/
  state/
  utils/
  permissions/
  routing/
```

---

# 328. CAD Component Architecture

```text
cad2d/
  CanvasManager.js
  ToolManager.js
  SelectionManager.js
  SnapManager.js
  GridManager.js
  DimensionManager.js
  LayerManager.js
  HistoryManager.js
  PropertyInspector.js
```

---

# 329. 3D Component Architecture

```text
bim3d/
  SceneManager.js
  CameraManager.js
  SelectionManager.js
  ModelLoader.js
  MaterialManager.js
  LightingManager.js
  SceneTree.js
  ViewControls.js
```

---

# 330. MES Component Architecture

```text
mes/
  Dashboard.js
  WorkCenter.js
  MachineQueue.js
  ShopFloor.js
  Scanner.js
  PanelTimeline.js
  Quality.js
  Packing.js
  Dispatch.js
```

---

# 331. UI API Boundary

Components should call:

```text
service/API layer
```

rather than embedding raw fetch calls everywhere.

---

# 332. Form Architecture

Forms must support:

```text
validation
dirty state
submit state
server errors
reset
autosave where required
```

---

# 333. Form Validation

Client-side validation improves UX.

Server-side validation remains authoritative.

---

# 334. Table Architecture

Data grids should support server-side:

```text
search
filter
sort
pagination
```

for large datasets.

---

# 335. Modal Accessibility

Every modal must support:

```text
focus trap
Escape
keyboard navigation
accessible title
```

---

# 336. Toast Accessibility

Important status messages should use appropriate ARIA live regions.

---

# 337. Color Accessibility

Do not communicate status using color alone.

Use:

```text
icon
label
color
```

---

# 338. Icon Rules

Use consistent icon library.

Icons must have tooltips/accessible labels when meaning is not obvious.

---

# 339. Form Field Rules

Every field needs:

```text
label
help where required
validation
error
```

---

# 340. Numeric Input

Dimension inputs should support:

```text
units
decimal
keyboard
min/max
increment
```

---

# 341. CAD Numeric Input

Typing:

```text
2400
```

should interpret according to project units.

---

# 342. Search Debouncing

Search fields should debounce network requests.

---

# 343. Autosuggest

Catalog search should support:

```text
code
name
semantic search
recent items
```

---

# 344. Drag Feedback

During drag/drop show:

```text
valid placement
invalid placement
snap position
```

---

# 345. Placement Validation

Furniture placement should visually indicate:

```text
valid
warning
collision
```

---

# 346. Selection Highlight

Use consistent highlight across:

```text
2D
3D
elevation
tree
inspector
```

---

# 347. Manufacturing Status Colors

Recommended semantics:

```text
Queued = neutral
Running = primary
Completed = success
Blocked = danger
Warning = warning
```

---

# 348. MES Status Visibility

Operator must always know:

```text
What am I doing?
Which panel?
Which operation?
What next?
```

---

# 349. Scanner Error

Use large, clear message:

```text
WRONG STATION
```

and explain expected operation.

---

# 350. Scanner Success

Show:

```text
PANEL ACCEPTED
```

with next action.

---

# 351. Scanner Audio/Haptic

Where browser/device permits, support:

```text
success sound
error sound
vibration
```

configurable.

---

# 352. Camera Permission

Scanner must clearly explain camera permission requirements.

---

# 353. Manual Scan Fallback

Always provide manual entry for supported workflows.

---

# 354. Panel Traceability UI

Panel detail should provide:

```text
status
location
operation
genealogy
events
photos
documents
```

---

# 355. Genealogy View

Visual:

```text
Sheet
 ↓
Nesting
 ↓
Panel
 ↓
Furniture
 ↓
Order
 ↓
Package
 ↓
Dispatch
```

---

# 356. Project Health View

Use a compact visual summary:

```text
Design      ✓
Approval    ✓
Engineering ⚠
Production  —
Quality     —
Dispatch    —
```

---

# 357. Change Impact Visualization

Use graph/tree:

```text
Furniture
 ├─ BOM
 ├─ Panels
 ├─ Nesting
 └─ CNC
```

---

# 358. Revision Comparison UI

Support:

```text
side-by-side
overlay
change list
```

---

# 359. Design Review Comments

Comments should anchor to:

```text
object
position
revision
```

where supported.

---

# 360. Client Review

Client should be able to:

```text
rotate 3D
zoom
view materials
comment
approve
```

without exposing internal tools.

---

# 361. Proposal Preview

Use polished presentation layout:

```text
cover
project summary
design
materials
scope
commercials
terms
approval
```

---

# 362. Quote Preview

Must be print/PDF friendly.

---

# 363. PDF Preview

Provide:

```text
zoom
page navigation
download
print
```

---

# 364. Print Styles

Critical documents must have dedicated print CSS.

---

# 365. Label Print Layout

Labels must have configurable:

```text
size
fields
QR
barcode
logo
font
```

---

# 366. Factory Label Preview

Show actual print representation before printing.

---

# 367. Production Board

Kanban:

```text
Queued
Cutting
Edge Banding
Drilling
QC
Packing
Completed
```

---

# 368. Production Board Interaction

Allow authorized users to:

```text
move
prioritize
open
inspect
```

but state transitions must be server validated.

---

# 369. Machine Board

Cards:

```text
Machine
Status
Current Job
Operator
Utilization
```

---

# 370. Downtime UI

Show:

```text
reason
duration
start
end
impact
```

---

# 371. Quality Dashboard

Use:

```text
Pass Rate
Defect Rate
Rework Rate
Scrap Rate
```

with drill-down.

---

# 372. Quality Defect UI

Defect categories:

```text
dimension
edge
surface
drilling
material
assembly
other
```

---

# 373. Packing Dashboard

Show:

```text
Ready
In Progress
Missing
Completed
```

---

# 374. Dispatch Dashboard

Show:

```text
Ready
Scheduled
Dispatched
Delivered
```

---

# 375. SaaS Admin UI

Platform-level navigation:

```text
Tenants
Plans
Subscriptions
Usage
Domains
Feature Flags
Support
Audit
System Health
```

---

# 376. Tenant Branding Preview

Preview multiple surfaces:

```text
Login
App
Client Portal
Document
Email
Label
```

---

# 377. Tenant Feature Management

Feature grid:

```text
Feature
Plan
Tenant Override
Status
```

---

# 378. Subscription UI

Show usage against limits:

```text
Users 12 / 25
Storage 80 / 100 GB
AI 42k / 100k requests
```

---

# 379. Upgrade UX

Clearly show:

```text
Current
New Plan
Additional Features
Price
Impact
```

---

# 380. Tenant Domain UX

Show DNS instructions:

```text
Record Type
Host
Value
Status
```

with copy button.

---

# 381. Domain Verification

Provide:

```text
Verify Now
Retry
Refresh
```

---

# 382. SSO Setup UI

Wizard:

```text
Provider
Metadata
Claims
Test Login
Enable
```

---

# 383. Integration Test UI

Show:

```text
Testing...
Connected
Failed
```

with diagnostic message.

---

# 384. API Documentation

Tenant API screen should link to:

```text
API docs
Authentication
Examples
Webhooks
```

---

# 385. Audit UI

Allow filtering and drill-down into event details.

---

# 386. Error Diagnostics

Authorized admins may see:

```text
correlation ID
technical status
```

not raw secrets.

---

# 387. UI Internationalization

All user-facing strings must use localization keys.

Never hard-code UI text directly into components.

---

# 388. Localization Key Example

```text
project.create.title
project.create.customer
manufacturing.release.button
```

---

# 389. RTL Readiness

Architecture should not prevent future RTL support.

---

# 390. Number Formatting

Use locale-aware formatting.

---

# 391. Date Formatting

Use tenant/project timezone and locale.

---

# 392. Currency Formatting

Use configured currency and locale.

---

# 393. Accessibility of CAD Alternatives

Important CAD actions should also be accessible through:

```text
toolbar
menus
property editor
keyboard
```

not only canvas gestures.

---

# 394. UI Security Rule

Never rely on hidden UI elements as security.

The API/RBAC layer remains authoritative.

---

# 395. UI Data Privacy

Do not display internal:

```text
cost
margin
supplier rate
factory data
```

to unauthorized/client users.

---

# 396. Client Data Boundary

Client portal APIs should return only client-approved fields.

---

# 397. Performance Budget

Avoid loading:

```text
3D assets
large catalogs
MES datasets
```

until needed.

---

# 398. Lazy Loading

Modules should load on demand where practical:

```text
CAD
3D
Nesting
MES
AI
```

---

# 399. Asset Caching

Cache:

```text
3D models
material thumbnails
catalog images
icons
```

with tenant-safe cache keys.

---

# 400. UI Definition of Done

```text
[ ] Global shell implemented
[ ] Tenant branding implemented
[ ] Navigation implemented
[ ] Command palette implemented
[ ] Notifications implemented
[ ] Search implemented
[ ] Role-aware navigation implemented
[ ] Dashboard implemented
[ ] CRM screens implemented
[ ] Customer screens implemented
[ ] Project screens implemented
[ ] Building Planner implemented
[ ] Floor Planner implemented
[ ] Room Designer implemented
[ ] 2D CAD workspace implemented
[ ] Elevation implemented
[ ] Section implemented
[ ] 3D/BIM workspace implemented
[ ] Furniture Catalog implemented
[ ] Furniture Designer implemented
[ ] Parametric Component Designer implemented
[ ] Material Catalog implemented
[ ] Hardware Catalog implemented
[ ] BOM implemented
[ ] BOQ implemented
[ ] Pricing implemented
[ ] Quote implemented
[ ] Client Presentation implemented
[ ] Client Approval implemented
[ ] Manufacturing Dashboard implemented
[ ] Production Orders implemented
[ ] Work Orders implemented
[ ] Panel Tracking implemented
[ ] Nesting implemented
[ ] CNC/CAM implemented
[ ] Machine Queue implemented
[ ] MES Dashboard implemented
[ ] Shop Floor implemented
[ ] QR Scanner implemented
[ ] Quality implemented
[ ] Rework implemented
[ ] Packing implemented
[ ] Dispatch implemented
[ ] Reports implemented
[ ] Documents implemented
[ ] AI Copilot implemented
[ ] AI Task Center implemented
[ ] Knowledge Base implemented
[ ] Catalog Administration implemented
[ ] User Administration implemented
[ ] Role Administration implemented
[ ] Tenant Settings implemented
[ ] Branding implemented
[ ] Domain Management implemented
[ ] Subscription implemented
[ ] Usage implemented
[ ] Integrations implemented
[ ] API Management implemented
[ ] Audit Log implemented
[ ] Platform Admin implemented
[ ] Responsive layouts implemented
[ ] Mobile shop-floor workflows implemented
[ ] Accessibility implemented
[ ] Keyboard navigation implemented
[ ] Loading states implemented
[ ] Empty states implemented
[ ] Error states implemented
[ ] Unsaved-change protection implemented
[ ] Autosave implemented where required
[ ] Undo/redo implemented
[ ] Revision UX implemented
[ ] Change impact UX implemented
[ ] Visual regression tests implemented
[ ] E2E tests implemented
[ ] Performance tests implemented
[ ] Security/permission UI tested
```

---

# 401. Final UX Principle

FMOS should feel like:

```text
ONE CONNECTED OPERATING SYSTEM
```

not:

```text
CAD APP
+
ERP
+
MES
+
AI
+
CRM
```

The user journey should feel like:

```text
Customer
   ↓
Project
   ↓
Room
   ↓
Design
   ↓
Furniture
   ↓
Materials
   ↓
BOM
   ↓
Price
   ↓
Approval
   ↓
Manufacturing
   ↓
Nesting
   ↓
CNC
   ↓
Panel
   ↓
Quality
   ↓
Packing
   ↓
Dispatch
```

At every stage, the user should be able to navigate directly to the next logical stage.

The UI must preserve the underlying object relationships so that:

```text
Room
 → Furniture
 → Components
 → Materials
 → BOM
 → BOQ
 → Price
 → Panels
 → Nesting
 → CNC
 → MES
 → QR
 → Quality
 → Package
 → Dispatch
```

remain connected throughout the entire product lifecycle.

**This screen specification is the UI/UX implementation contract for Cursor. Cursor must first analyze the existing codebase, reuse existing components and engines where possible, then implement the screens incrementally without creating duplicate application shells, CAD engines, API clients, RBAC systems, or state-management systems.**
