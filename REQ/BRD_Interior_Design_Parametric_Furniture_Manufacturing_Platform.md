# Business Requirements Document (BRD)
## End-to-End Interior Design, Parametric Furniture & Manufacturing Execution Platform

**Document Type:** Business Requirements Document (BRD)  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Product Owner, Founder, Business Analysts, Architects, Developers, QA, Cursor/AI Coding Agents  
**Technology Context:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + HTML5 + Three.js  
**Product Domain:** Architecture, Interior Design, Modular Furniture, Estimation, Manufacturing and MES  
**Date:** 2026-08-09

---

# 1. Executive Summary

This document defines the business requirements for an integrated software platform that connects the complete interior design and modular furniture manufacturing lifecycle.

The platform is intended to bridge the traditional gap between:

**Architectural Planning → Interior Design → Furniture Design → Engineering → Estimation → Manufacturing → Factory Execution → Quality → Packing → Dispatch**

The product shall provide a unified digital workspace where project information is created once and reused throughout the lifecycle.

The platform's fundamental business proposition is:

> **Design Once. Engineer Once. Calculate Once. Manufacture Directly.**

A parametric furniture model should automatically drive downstream business and manufacturing outputs including:

- 2D plans
- 3D models
- elevations
- sections
- material requirements
- BOM
- BOQ
- pricing
- cutlists
- edge-banding requirements
- nesting
- CNC/CAM outputs
- panel labels
- production jobs
- factory tracking
- quality control
- packing
- dispatch

---

# 2. Business Problem

Interior design and modular furniture businesses frequently operate through disconnected tools and manual processes.

Typical workflow:

```text
Floor Plan
   ↓
Interior Design Software
   ↓
Manual Furniture Engineering
   ↓
Excel BOQ
   ↓
Manual Cutlist
   ↓
Manual Nesting
   ↓
CNC Software
   ↓
Factory Production
   ↓
Manual Tracking
```

This creates:

- duplicated work
- engineering dependency
- calculation errors
- inconsistent pricing
- material wastage
- incorrect cutlists
- manufacturing delays
- missing panels
- poor traceability
- slow quotation cycles
- difficulty scaling operations
- dependency on highly skilled individuals

The proposed platform addresses these problems through a common digital project model.

---

# 3. Business Opportunity

The platform can serve multiple segments through one technology foundation:

1. Independent interior designers
2. Interior design firms
3. Modular furniture manufacturers
4. Furniture factories
5. Enterprise furniture brands
6. Multi-location interior businesses
7. White-label design/manufacturing businesses

The platform can progressively expand from design software into a broader operating system for interior and furniture businesses.

---

# 4. Product Vision

Create a unified digital operating system for interior design and modular furniture businesses that connects the front-end design experience with back-end commercial and manufacturing execution.

The platform should allow:

```text
Idea
 ↓
Space
 ↓
Design
 ↓
Furniture
 ↓
Engineering
 ↓
Cost
 ↓
Manufacturing
 ↓
Production
 ↓
Delivery
```

without repeatedly recreating the same information.

---

# 5. Product Mission

The platform shall:

- reduce design-to-production time
- reduce engineering dependency
- improve design accuracy
- automate estimation
- reduce material wastage
- standardize manufacturing
- improve production traceability
- improve client presentation
- reduce manual data entry
- provide a scalable SaaS platform for multiple businesses

---

# 6. Strategic Objectives

## Objective 1 — Unified Design

Provide a connected 2D and 3D design environment.

## Objective 2 — Parametric Furniture

Allow furniture to be generated and modified through controlled parameters and rules.

## Objective 3 — Automated Commercial Calculation

Generate BOM, BOQ and pricing automatically.

## Objective 4 — Design-to-Manufacturing

Transform approved furniture designs into manufacturing-ready outputs.

## Objective 5 — Factory Traceability

Track panels and jobs through manufacturing stages.

## Objective 6 — SaaS Scalability

Allow multiple businesses to operate independently on the same platform.

## Objective 7 — Enterprise Extensibility

Support custom catalogs, pricing, templates, components, manufacturing rules and branding.

---

# 7. Business Goals

The platform should enable customers to:

- create projects faster
- produce designs faster
- quote projects faster
- reduce design-to-engineering handoffs
- reduce manufacturing errors
- improve material utilization
- improve production visibility
- standardize furniture manufacturing
- reduce dependency on individual engineers
- scale project volume without linear increases in staff

---

# 8. Target Customers

## 8.1 Independent Interior Designer

Primary needs:

- floor planning
- 3D design
- furniture design
- materials
- quotations
- client presentation

---

## 8.2 Interior Design Company

Primary needs:

- project management
- team collaboration
- standardized design
- catalog management
- pricing
- proposals
- approvals
- production handoff

---

## 8.3 Furniture Manufacturer

Primary needs:

- parametric furniture
- engineering
- BOM
- cutlist
- nesting
- CNC
- production tracking
- quality
- packing

---

## 8.4 Enterprise Furniture Brand

Primary needs:

- custom product catalogs
- component libraries
- custom manufacturing rules
- multiple factories
- role-based access
- white labeling
- branded client experience

---

# 9. User Personas

| Persona | Business Responsibility |
|---|---|
| Super Admin | Platform management |
| Tenant Admin | Company administration |
| Business Owner | Business oversight |
| Sales Executive | Lead and quotation management |
| Architect | Architectural design |
| Interior Designer | Interior and furniture design |
| Design Engineer | Engineering validation |
| Estimator | BOQ/BOM/pricing |
| Production Manager | Manufacturing planning |
| Factory Supervisor | Factory execution |
| CNC Operator | CNC operations |
| Edge Banding Operator | Edge-banding operations |
| Drilling Operator | Drilling |
| Assembly Operator | Assembly |
| QC Inspector | Quality |
| Packing Operator | Packing |
| Dispatch Manager | Dispatch |
| Installer | Installation |
| Client | Review and approval |

---

# 10. Business Capability Map

The platform shall contain the following major business capabilities:

```text
1. Platform Administration
2. CRM & Sales
3. Project Management
4. Architectural Design
5. 2D Design
6. 3D/BIM
7. Interior Design
8. Parametric Furniture
9. Component Designer
10. Materials & Catalog
11. BOM
12. BOQ
13. Pricing
14. Documentation
15. Manufacturing Engineering
16. Nesting
17. CNC/CAM
18. MES
19. QR Panel Tracking
20. Quality Control
21. Packing & Dispatch
22. AI Services
23. White Label / SaaS
24. Analytics
```

---

# 11. End-to-End Business Process

The target business process is:

```text
Lead
 ↓
Client
 ↓
Project
 ↓
Site / Building
 ↓
Floor
 ↓
Room
 ↓
Architectural Design
 ↓
Interior Design
 ↓
Furniture Design
 ↓
Material Selection
 ↓
Engineering Validation
 ↓
BOM
 ↓
BOQ
 ↓
Pricing
 ↓
Client Proposal
 ↓
Client Approval
 ↓
Manufacturing Engineering
 ↓
Production Release
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
 ↓
Factory Production
 ↓
QC
 ↓
Packing
 ↓
Dispatch
 ↓
Installation
 ↓
Project Completion
```

---

# 12. Business Requirement: Platform Administration

The system shall provide centralized administration.

## Requirements

The platform shall support:

- tenants
- organizations
- users
- roles
- permissions
- subscription configuration
- tenant branding
- custom domains
- system settings
- audit logs

The platform shall support tenant isolation.

---

# 13. Business Requirement: Multi-Tenancy

Each business/customer shall operate within an isolated tenant.

A tenant shall have:

- users
- projects
- clients
- catalogs
- pricing
- templates
- branding
- manufacturing configuration
- production data

Tenant data MUST NOT be accessible by another tenant.

---

# 14. Business Requirement: CRM & Sales

The platform shall allow businesses to manage:

- leads
- prospects
- clients
- contacts
- opportunities
- sales pipeline
- follow-ups
- quotations
- proposals

A sales opportunity should be convertible into a project.

Example:

```text
Lead
 ↓
Qualified
 ↓
Opportunity
 ↓
Proposal
 ↓
Won
 ↓
Project
```

---

# 15. Business Requirement: Project Management

Users shall be able to create and manage projects.

Project information may include:

- project name
- client
- site
- address
- project type
- start date
- expected completion
- assigned team
- status
- budget
- notes
- documents

---

# 16. Building / Floor / Room Structure

Projects shall support:

```text
Project
 └── Building
      └── Floor
           └── Room
```

A project may contain:

- multiple buildings
- multiple floors
- multiple rooms

Each room can contain design objects and furniture.

---

# 17. Architectural Design Requirements

The platform shall support architectural/spatial design elements:

- walls
- partitions
- columns
- beams
- doors
- windows
- openings
- stairs
- floors
- ceilings

Objects shall have dimensional properties.

---

# 18. 2D Design Requirements

The 2D workspace shall support:

- wall creation
- wall editing
- door placement
- window placement
- dimensions
- annotations
- furniture placement
- grid
- snapping
- alignment
- move
- copy
- rotate
- mirror
- trim
- extend
- offset
- zoom
- pan

---

# 19. Precision Requirement

The system shall support precise dimensional design.

The preferred internal unit is:

**millimeter (mm)**

The user may display measurements in other units where configured.

---

# 20. 3D Design Requirements

The system shall provide a 3D representation of the project.

3D should reflect:

- walls
- floors
- ceilings
- doors
- windows
- furniture
- materials
- major fixtures

Changes to parametric objects should be reflected in 3D.

---

# 21. 2D / 3D Synchronization Requirement

2D and 3D must represent the same underlying project objects.

Example:

```text
Designer changes wall length
2500 → 3000 mm
```

The system should update:

- 2D wall
- 3D wall
- room geometry
- dependent furniture constraints
- relevant dimensions
- quantities

---

# 22. Interior Design Requirements

The platform shall support:

- flooring
- wall finishes
- paint
- wallpaper
- tiling
- false ceilings
- decorative elements
- furniture
- lighting objects
- fixtures
- material assignment

---

# 23. Parametric Furniture Requirements

The platform shall provide parametric furniture.

Initial categories:

- wardrobes
- modular kitchen units
- base units
- wall units
- tall units
- islands
- TV units
- storage
- vanities
- bookshelves
- study units
- custom cabinets

---

# 24. Parametric Furniture Business Rule

Furniture must be defined using parameters.

Example:

```text
Wardrobe
Width = 2400
Height = 2400
Depth = 600
```

Changing the dimensions must automatically update dependent geometry and manufacturing information.

---

# 25. Furniture Component Requirements

Furniture may contain:

- side panels
- top
- bottom
- shelves
- partitions
- drawers
- shutters
- back panels
- toe kicks
- hardware
- accessories

---

# 26. Component Designer Requirement

Enterprise users shall eventually be able to define custom components.

A component shall support:

- dimensions
- parameters
- constraints
- formulas
- geometry
- material
- hardware
- manufacturing rules
- pricing rules

---

# 27. Furniture Template Requirement

Furniture templates shall define:

- default parameters
- allowed parameter ranges
- components
- formulas
- material mappings
- hardware mappings
- manufacturing rules

Templates shall be versioned.

---

# 28. Materials & Catalog Requirement

The platform shall provide configurable catalogs.

## Board Catalog

Fields:

- brand
- product code
- material type
- thickness
- sheet length
- sheet width
- finish
- color
- grain direction
- cost
- selling price

## Laminate Catalog

Fields:

- brand
- collection
- code
- name
- finish
- thickness
- image
- cost
- selling price

## Edge Band Catalog

Fields:

- material
- thickness
- width
- color
- cost per meter

## Hardware Catalog

Examples:

- hinges
- drawer channels
- handles
- connectors
- screws
- lifts
- baskets
- mechanisms

---

# 29. Catalog Governance

Catalog changes should support:

- draft
- review
- publish
- deactivate
- version

Published catalog data used in historical quotations/manufacturing must remain reproducible.

---

# 30. BOM Requirement

The system shall automatically generate BOM.

BOM may contain:

- boards
- panels
- laminates
- edge bands
- hardware
- accessories
- profiles
- fasteners

BOM must be derived from the project/furniture model.

---

# 31. BOQ Requirement

The system shall generate commercial BOQ.

BOQ shall support:

- item
- description
- category
- quantity
- unit
- rate
- discount
- tax
- total

BOQ must remain commercially editable where business rules allow.

---

# 32. Dual Pricing Requirement

The platform shall support two major pricing models.

## Raw Material Pricing

Based on:

- board area
- laminate area
- edge-band length
- hardware quantity
- labour
- manufacturing
- installation
- overhead
- margin

## Panel/Unit Pricing

Based on:

- panel rate
- shutter rate
- drawer rate
- cabinet rate
- unit rate
- square-foot rate
- linear-meter rate

---

# 33. Pricing Governance

Each tenant shall be able to configure:

- cost
- markup
- margin
- discounts
- labour rates
- installation rates
- tax
- pricing rules

Pricing changes must not alter previously approved quotations.

---

# 34. Quotation Requirement

The system shall generate client quotations from BOQ/pricing.

Quotation should include:

- company branding
- client details
- project details
- itemized scope
- quantities
- rates
- discounts
- tax
- grand total
- terms
- validity
- approval section

---

# 35. Client Proposal Requirement

The platform shall generate branded proposals.

Possible sections:

- cover page
- client details
- project overview
- floor plans
- 3D views
- furniture views
- materials
- scope
- pricing
- timeline
- terms

---

# 36. Documentation Requirement

The system should automatically generate:

### Architectural

- floor plans
- dimension plans
- furniture plans
- ceiling plans
- electrical plans
- plumbing plans

### Furniture

- elevations
- sections
- top views
- internal views
- details

### Manufacturing

- BOM
- cutlist
- panel drawings
- assembly documentation

---

# 37. Design-to-Manufacturing Requirement

Approved furniture shall be convertible into manufacturing data.

Required flow:

```text
Furniture
 ↓
Engineering Validation
 ↓
Panel Decomposition
 ↓
BOM
 ↓
Cutlist
 ↓
Edge Band
 ↓
Drilling
 ↓
Nesting
 ↓
CNC
 ↓
Production
```

---

# 38. Engineering Validation

Before manufacturing release, the system shall validate:

- dimensions
- component rules
- material availability/configuration
- panel sizes
- edge-banding rules
- hardware
- drilling
- routing
- manufacturing constraints

Validation severity:

- INFO
- WARNING
- ERROR
- BLOCKER

BLOCKER errors shall prevent manufacturing release.

---

# 39. Cutlist Requirement

The system shall generate panel-level cutlists.

Each panel should contain:

- panel ID
- furniture ID
- component ID
- material
- thickness
- length
- width
- quantity
- grain direction
- edge-banding
- drilling
- routing

---

# 40. Nesting Requirement

The system shall optimize board-sheet usage.

Inputs:

- panel list
- sheet sizes
- grain direction
- cutting kerf
- spacing
- rotation rules

Outputs:

- sheets required
- panel placement
- used area
- waste area
- waste percentage
- cutting layout

---

# 41. CNC/CAM Requirement

The platform shall support machine output through adapters.

Potential formats:

- DXF
- CSV
- G-code
- machine-specific formats

Potential machine ecosystems:

- Biesse
- Homag
- KDT
- generic CNC

Machine-specific logic must remain isolated from the core manufacturing model.

---

# 42. MES Requirement

The platform shall provide a Manufacturing Execution System.

Stages:

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
QC
 ↓
Packing
 ↓
Dispatch
```

Each job shall have status and traceability.

---

# 43. Production Job Requirement

Production jobs shall contain:

- job ID
- project
- client
- furniture
- panels
- operations
- assigned operator
- status
- timestamps
- notes
- QC information

---

# 44. QR Panel Tracking Requirement

Each panel shall have a unique identifier.

QR/barcode labels should provide:

- project
- furniture
- panel
- material
- dimensions
- operation status

Scanning should allow factory staff to update production status.

---

# 45. Production Traceability

The system should answer:

- Where is this panel?
- Which project does it belong to?
- Which furniture does it belong to?
- Which stage is it in?
- Who processed it?
- When was it processed?
- Did it pass QC?
- Has it been packed?
- Has it been dispatched?

---

# 46. Quality Control Requirement

QC shall support:

- inspection checklist
- pass/fail
- defect category
- comments
- photographs where applicable
- rework status
- inspector
- timestamp

A failed QC item should create a controlled rework flow.

---

# 47. Packing Requirement

Packing shall support:

- furniture/package grouping
- panel grouping
- package ID
- QR/barcode
- packing status
- packing checklist
- package dimensions
- package notes

---

# 48. Dispatch Requirement

Dispatch should support:

- dispatch record
- project
- packages
- vehicle/courier
- dispatch date
- delivery status
- proof of delivery where applicable

---

# 49. Manufacturing Release Requirement

Manufacturing release must be controlled.

Lifecycle:

```text
Draft
 ↓
Engineering
 ↓
Approved
 ↓
Production Ready
 ↓
Released
```

After release, changes must create a new revision.

---

# 50. Revision Requirement

The platform shall support:

- project revision
- furniture revision
- design revision
- manufacturing revision
- approval history
- revision comparison
- restoration

---

# 51. Manufacturing Snapshot Requirement

When production is released, the system shall preserve a manufacturing snapshot containing the relevant:

- design data
- furniture parameters
- component definitions
- materials
- BOM
- panels
- cutlist
- nesting
- CNC outputs
- manufacturing rules

This prevents later design changes from silently changing factory instructions.

---

# 52. AI Floorplan Requirement

The platform should support future AI-assisted conversion of:

```text
Floorplan Image
 ↓
Wall Detection
 ↓
Room Detection
 ↓
Door/Window Detection
 ↓
Editable 2D Model
 ↓
3D Model
```

AI output must be editable and user-verifiable.

AI output must not directly enter manufacturing without validation.

---

# 53. White-Label Requirement

The platform shall support businesses operating under their own brand.

Tenant configuration shall include:

- company name
- logo
- favicon
- brand colors
- email branding
- PDF branding
- quotation templates
- proposal templates
- terms
- tax information

---

# 54. Custom Domain Requirement

The architecture shall support tenant-specific domains.

Example:

```text
design.customer.com
```

The tenant should receive the branded application experience through the custom domain.

---

# 55. RBAC Business Requirement

The platform shall support role-based permissions.

Example permissions:

```text
project.view
project.create
project.edit

design.view
design.create
design.edit

furniture.create
furniture.edit
furniture.approve

bom.view
bom.generate

boq.view
boq.edit

pricing.view
pricing.edit

manufacturing.view
manufacturing.generate
manufacturing.release

production.view
production.update

qc.view
qc.update
```

---

# 56. Business Workflow: Designer

Designer workflow:

```text
Login
 ↓
Select Project
 ↓
Select Floor
 ↓
Select Room
 ↓
Open 2D Workspace
 ↓
Create/Edit Room
 ↓
Add Furniture
 ↓
Configure Furniture
 ↓
Select Materials
 ↓
View 3D
 ↓
Generate Presentation
 ↓
Submit for Review
```

---

# 57. Business Workflow: Estimator

Estimator workflow:

```text
Open Project
 ↓
Review Design
 ↓
Generate BOM
 ↓
Generate BOQ
 ↓
Apply Pricing Rules
 ↓
Review Margin
 ↓
Generate Quotation
 ↓
Submit for Approval
```

---

# 58. Business Workflow: Engineer

Engineer workflow:

```text
Open Approved Design
 ↓
Validate Furniture
 ↓
Review Dimensions
 ↓
Review Materials
 ↓
Review Hardware
 ↓
Generate Manufacturing Data
 ↓
Resolve Errors
 ↓
Approve Engineering
 ↓
Release Manufacturing
```

---

# 59. Business Workflow: Factory

Factory workflow:

```text
View Production Jobs
 ↓
Start Cutting
 ↓
Scan Panel
 ↓
Complete Cutting
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

# 60. Business Workflow: Client

Client workflow:

```text
Receive Proposal
 ↓
Review Design
 ↓
Review Materials
 ↓
Review Price
 ↓
Approve / Request Changes
```

Client-facing capabilities may initially be limited to presentation and approval.

---

# 61. Business Rules — General

1. Every tenant-owned record must belong to a tenant.
2. A furniture object must belong to a project/room.
3. Manufacturing output must reference a defined design/furniture revision.
4. Released manufacturing data must not be silently overwritten.
5. Critical calculation results must be reproducible.
6. Pricing must be versioned/snapshotted for approved quotations.
7. Material changes must identify downstream impact.
8. Manufacturing blockers must prevent release.
9. Factory users must operate on released manufacturing information.
10. Historical records must remain traceable.

---

# 62. Business Rules — Parametric Furniture

1. Parameters must have valid ranges.
2. Invalid dimensions must be rejected.
3. Dependent dimensions must recalculate automatically.
4. Components must be generated according to template rules.
5. Material selection must affect downstream calculations.
6. Hardware must be calculated according to rules.
7. Panel sizes must be validated against manufacturing constraints.
8. Changes must mark dependent outputs as stale.

---

# 63. Business Rules — Pricing

1. Current pricing must not modify historical approved quotations.
2. Pricing rules must be tenant-configurable.
3. Discounts must be permission-controlled.
4. Margin should be visible to authorized users.
5. Tax must be configurable.
6. Unit types must be defined consistently.
7. Pricing calculations must be deterministic.

---

# 64. Business Rules — Manufacturing

1. Manufacturing cannot be released with unresolved blockers.
2. Manufacturing release creates a controlled revision/snapshot.
3. Production uses released manufacturing data.
4. Design changes after release require a new revision.
5. CNC output must reference a manufacturing revision.
6. Panels must have unique identifiers.
7. Production status changes must be audited.
8. QC failures must support rework.

---

# 65. Business Rules — MES

1. Production stages must follow allowed state transitions.
2. A panel cannot be marked complete without required information.
3. QC failure prevents final completion until resolved.
4. Packing should reference completed/QC-approved items where configured.
5. Dispatch should reference packages.
6. Production history must remain auditable.

---

# 66. Reporting Requirements

The platform should eventually provide:

## Project Reports

- project status
- project value
- completion
- pending approvals

## Sales Reports

- leads
- opportunities
- conversion
- quotation value

## Design Reports

- projects by designer
- revisions
- approval cycle time

## Manufacturing Reports

- jobs
- panels
- waste
- material consumption
- production status

## Factory Reports

- operator productivity
- stage throughput
- QC defects
- rework
- dispatch

---

# 67. Business KPIs

Potential KPIs include:

### Design

- design turnaround time
- revision count
- approval time

### Commercial

- quotation turnaround time
- quote-to-order conversion
- gross margin

### Manufacturing

- material utilization
- waste %
- panels produced
- production cycle time
- rework rate

### Operations

- on-time delivery
- QC pass rate
- missing panel rate

---

# 68. Product Success Metrics

The initial product should target measurable improvement in:

1. Design-to-quotation time
2. Engineering time per furniture unit
3. Cutlist preparation time
4. Manufacturing error rate
5. Material waste
6. Production traceability
7. Client approval cycle
8. Project throughput per employee

Actual targets should be established during commercial validation.

---

# 69. MVP Scope

## P0 — Required for End-to-End MVP

### Platform

- authentication
- tenant
- users
- roles
- permissions

### Projects

- clients
- projects
- buildings
- floors
- rooms

### Design

- basic 2D walls
- doors
- windows
- dimensions
- basic 3D

### Furniture

- wardrobe
- kitchen base unit
- kitchen wall unit
- tall unit
- TV unit

### Catalog

- boards
- laminates
- edge bands
- hardware

### Commercial

- BOM
- BOQ
- dual pricing
- quotation

### Manufacturing

- panel generation
- cutlist
- edge-banding
- basic nesting
- production data

### Traceability

- panel IDs
- QR labels
- basic production tracking

---

# 70. P1 Scope

- advanced furniture templates
- component designer
- advanced nesting
- CNC adapters
- engineering validation
- production dashboards
- QC
- packing
- dispatch
- client approvals
- branded proposals
- white label

---

# 71. P2 Scope

- advanced CNC integration
- multiple factories
- advanced MES
- production analytics
- advanced collaboration
- advanced BIM interoperability
- inventory integrations
- supplier integrations

---

# 72. P3 Scope

- AI floorplan recognition
- image-to-3D
- AI design assistant
- AI-generated furniture suggestions
- predictive production analytics
- intelligent optimization

---

# 73. Explicitly Out of MVP Scope

The MVP should not attempt to become:

- a full Revit replacement
- a structural engineering application
- a full accounting ERP
- a payroll system
- a complete HR system
- a full procurement platform
- a supplier marketplace
- an autonomous factory system
- a complete robotics platform
- a full AR/VR platform

These may be future integrations or products.

---

# 74. Business Acceptance Criteria — Platform

The platform is acceptable when:

- users can securely log in
- tenant data is isolated
- roles control access
- users can create projects
- projects can contain buildings/floors/rooms
- users can create basic design objects
- users can save and reopen projects
- audit information is available for critical actions

---

# 75. Business Acceptance Criteria — Design

The design capability is acceptable when:

- walls can be created
- walls can be dimensioned
- doors/windows can be placed
- design can be saved
- design can be viewed in 3D
- selected objects can be edited
- basic undo/redo works
- 2D and 3D represent the same underlying objects

---

# 76. Business Acceptance Criteria — Furniture

The furniture capability is acceptable when:

- user can create a parametric furniture object
- user can change dimensions
- dependent components recalculate
- materials can be assigned
- furniture is visible in 2D
- furniture is visible in 3D
- furniture produces manufacturing-relevant components

---

# 77. Business Acceptance Criteria — Commercial

The commercial capability is acceptable when:

- BOM can be generated
- BOQ can be generated
- pricing can be calculated
- raw-material pricing works
- panel/unit pricing works
- quotation can be generated
- approved quotations remain reproducible

---

# 78. Business Acceptance Criteria — Manufacturing

The manufacturing capability is acceptable when:

- furniture produces panel data
- cutlist is generated
- edge banding is calculated
- manufacturing validation works
- nesting can be generated
- panel IDs exist
- production data can be created

---

# 79. Business Acceptance Criteria — MES

The MES capability is acceptable when:

- production jobs can be created
- panel status can be updated
- QR labels can be generated
- factory staff can scan/identify panels
- production stages are tracked
- QC can be recorded
- packing can be recorded
- dispatch can be recorded

---

# 80. Business Acceptance Criteria — Revision

The revision capability is acceptable when:

- design changes create traceable revisions
- manufacturing references a specific revision
- released manufacturing data is protected
- old revisions remain viewable
- historical quotations remain reproducible

---

# 81. Non-Functional Business Requirements

The system should provide:

- reliable data storage
- secure access
- reasonable performance
- responsive design workspace
- scalable tenant architecture
- auditability
- recoverability
- maintainability
- extensibility

---

# 82. Data Ownership

Tenant owns:

- projects
- clients
- designs
- furniture
- catalog extensions
- pricing
- quotations
- manufacturing jobs
- production data

Platform owns:

- system configuration
- system permissions
- platform-level templates
- core engines

---

# 83. Business Data Security

The platform must protect:

- customer information
- project designs
- pricing
- manufacturing data
- CNC files
- production information
- user information

Tenant data must never leak across organizations.

---

# 84. Audit Requirements

Critical actions must be recorded:

- login/security events where appropriate
- price changes
- quotation approvals
- design approvals
- manufacturing release
- revision creation
- production status changes
- QC decisions
- dispatch

---

# 85. Integration Requirements

The platform should be designed to integrate with:

- CNC systems
- storage providers
- email systems
- AI services
- accounting systems
- ERP systems
- supplier catalogs
- payment systems
- identity providers

Integrations should use adapters/interfaces.

---

# 86. Localization

The architecture should allow future:

- multiple languages
- multiple currencies
- multiple tax models
- regional formats

Text displayed in the UI should not be hard-coded into business logic.

---

# 87. Internationalization

Potential configuration:

```text
Language
Currency
Measurement System
Date Format
Number Format
Tax Rules
```

India can be the initial market configuration.

---

# 88. Regulatory / Commercial Considerations

The platform should support configurable:

- GST/tax fields
- company registration details
- quotation terms
- invoice-related data where integrated
- customer information

The product should not hard-code India-specific commercial rules into the core domain.

---

# 89. Business Continuity

Customers should be able to recover project data after:

- server failure
- accidental deletion
- software error
- user error

Backup and restore capabilities are therefore required for production environments.

---

# 90. User Experience Principle

The application should feel like one connected workspace.

Users should not have to repeatedly:

- export data
- import data
- re-enter dimensions
- recreate BOM
- recreate pricing
- recreate manufacturing information

---

# 91. Design-to-Manufacturing UX Principle

When a designer changes:

```text
Wardrobe width
2400 → 2700
```

the application should clearly communicate:

```text
Design Updated
BOM affected
BOQ affected
Cutlist affected
Nesting affected
CNC affected
```

If the design has already been released, the user should be prompted to create a new manufacturing revision rather than silently modifying production data.

---

# 92. Business Transparency

The platform should show users why calculations were produced.

For example:

```text
Wardrobe Price

Board:
12.4 sq.ft × ₹X

Laminate:
10.8 sq.ft × ₹Y

Edge Band:
18.2 m × ₹Z

Hardware:
₹X

Labour:
₹X

Margin:
₹X

Total:
₹X
```

The exact presentation may evolve, but calculations should be explainable to authorized users.

---

# 93. Enterprise Configuration

Enterprise customers should be able to configure:

- furniture templates
- component libraries
- materials
- hardware
- pricing rules
- manufacturing rules
- machine outputs
- quotation templates
- proposal templates
- branding
- roles
- workflows

---

# 94. Extensibility Requirement

Adding a new furniture category should NOT require rewriting:

- BOM engine
- pricing engine
- manufacturing engine
- 2D renderer
- 3D renderer

A new furniture template should plug into existing engines.

---

# 95. Extensibility Example

Adding:

```text
New Furniture:
Study Table
```

should involve:

```text
Study Table Template
+
Parameters
+
Components
+
Rules
+
Materials
+
Manufacturing Rules
```

rather than creating a separate application.

---

# 96. Core Business Architecture

The business architecture is:

```text
PROJECT
   │
   ├── SPACE
   │    ├── Building
   │    ├── Floor
   │    └── Room
   │
   ├── DESIGN
   │    ├── Architecture
   │    ├── Interior
   │    └── Furniture
   │
   ├── COMMERCIAL
   │    ├── BOM
   │    ├── BOQ
   │    ├── Pricing
   │    └── Proposal
   │
   └── MANUFACTURING
        ├── Engineering
        ├── Cutlist
        ├── Nesting
        ├── CNC
        └── MES
```

---

# 97. Product North Star

The platform should ultimately allow a business to manage the full lifecycle of a project through one digital system:

```text
                     ONE PROJECT
                          │
                 ONE DIGITAL MODEL
                          │
          ┌───────────────┼────────────────┐
          │               │                │
        DESIGN        COMMERCIAL      MANUFACTURING
          │               │                │
       2D / 3D        BOQ / Price       BOM / Cutlist
          │               │                │
      Drawings        Proposal           Nesting
          │               │                │
          └───────────────┼────────────────┘
                          │
                         MES
                          │
                Production / QC / Packing
                          │
                       Dispatch
```

---

# 98. Key Business Differentiators

The platform's strategic differentiation should be:

### 1. Unified Design-to-Manufacturing

The design becomes the manufacturing source.

### 2. Parametric Intelligence

Furniture automatically adapts to dimensions and rules.

### 3. Automated Commercial Calculation

Design automatically drives BOM, BOQ and pricing.

### 4. Manufacturing Automation

Furniture directly produces cutlists, nesting and CNC outputs.

### 5. Factory Traceability

QR-based tracking connects individual panels to production.

### 6. Enterprise Customization

Businesses can define their own components, materials, pricing and workflows.

### 7. White Label

Businesses can offer the platform under their own brand.

---

# 99. Business Risks

Potential risks include:

## Risk 1 — Overly Broad Scope

Mitigation:

Use phased delivery and MVP priorities.

## Risk 2 — Geometry Complexity

Mitigation:

Build a controlled parametric engine before advanced CAD features.

## Risk 3 — Manufacturing Accuracy

Mitigation:

Use deterministic calculations and automated tests.

## Risk 4 — CNC Compatibility

Mitigation:

Use machine adapters.

## Risk 5 — Data Synchronization

Mitigation:

Use one unified project model.

## Risk 6 — Historical Data Integrity

Mitigation:

Use revisioning and manufacturing snapshots.

## Risk 7 — Performance

Mitigation:

Use Web Workers and background processing.

## Risk 8 — Tenant Data Leakage

Mitigation:

Enforce tenant isolation at backend/data layer.

---

# 100. Business Assumptions

The initial product assumes:

- customers have internet access
- projects are primarily created in browser
- manufacturing machines accept compatible generated files
- users provide/maintain material catalogs
- tenant-specific pricing is configurable
- manufacturing rules vary by customer/factory
- advanced CNC integration will require machine-specific validation

---

# 101. Dependencies

Major dependencies:

- browser rendering capabilities
- Three.js
- MySQL
- PHP runtime
- file storage
- PDF generation
- CNC format specifications
- machine-specific testing
- material catalogs
- customer-specific manufacturing rules

---

# 102. Business Constraints

The product must:

- remain commercially understandable
- avoid requiring highly specialized technical knowledge for basic users
- maintain manufacturing accuracy
- support gradual adoption
- allow customers to use only relevant modules
- preserve historical project information

---

# 103. Recommended Module Packaging

Commercially, the platform may eventually be packaged as:

```text
Design
+
Furniture
+
Commercial
+
Manufacturing
+
MES
+
Enterprise
```

Customers may purchase additional modules depending on business needs.

---

# 104. MVP Commercial Journey

The MVP should demonstrate:

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
BOQ
 ↓
Price
 ↓
Quotation
 ↓
Cutlist
 ↓
Nesting
 ↓
Production
```

This is the primary product demonstration path.

---

# 105. Definition of Business Success

The platform should demonstrate that a customer can take a representative interior project from initial design through manufacturing without manually recreating core information at every stage.

The system succeeds when the majority of downstream project information is **generated from the unified digital model rather than manually recreated**.

---

# 106. Cursor / Engineering Instructions

This BRD is the business-level source of truth.

Cursor MUST:

1. Read this BRD together with the Product Vision & Scope and System Architecture documents.
2. Map every business requirement to one or more technical modules.
3. Identify requirements already implemented in the repository.
4. Identify missing capabilities.
5. Identify conflicts with the current codebase.
6. Create a requirements traceability matrix.
7. Do not implement large features without first identifying dependencies.
8. Do not remove existing business functionality without explicit approval.
9. Preserve historical data.
10. Add automated tests for calculation-heavy business rules.

---

# 107. Cursor Requirement Traceability

Cursor should maintain a matrix:

| BRD ID | Requirement | Module | Technical Component | API | DB | UI | Test | Status |
|---|---|---|---|---|---|---|---|---|
| BRD-001 | Tenant isolation | Tenant | TenantService | /tenants | tenants | Admin | Yes | Planned |
| BRD-002 | Project management | Project | ProjectService | /projects | projects | Project | Yes | Planned |
| BRD-003 | Parametric furniture | Furniture | ParametricEngine | /furniture | furniture | Designer | Yes | Planned |
| BRD-004 | BOM | BOM | BOMEngine | /bom | bom | BOM | Yes | Planned |
| BRD-005 | Cutlist | Manufacturing | PanelEngine | /manufacturing | panels | Manufacturing | Yes | Planned |

The actual matrix should be maintained as implementation progresses.

---

# 108. BRD-to-Implementation Rules

Every implementation item must trace back to:

```text
Business Requirement
 ↓
Functional Requirement
 ↓
Technical Design
 ↓
Implementation
 ↓
Test
 ↓
Acceptance
```

No major feature should exist only because it "seems useful."

---

# 109. Requirement IDs

The detailed SRS/FSD should assign IDs derived from this BRD.

Examples:

```text
BRD-PLATFORM-001
BRD-PROJECT-001
BRD-DESIGN-001
BRD-FURNITURE-001
BRD-CATALOG-001
BRD-BOM-001
BRD-BOQ-001
BRD-PRICING-001
BRD-MFG-001
BRD-MES-001
BRD-AI-001
BRD-SAAS-001
```

---

# 110. Recommended Next Documentation Sequence

After this BRD, the recommended documentation sequence is:

```text
01 Product Vision & Scope
02 System Architecture
03 Business Requirements Document (THIS DOCUMENT)
04 Software Requirements Specification (SRS)
05 Functional Specification Document (FSD)
06 Database Specification
07 API Specification
08 RBAC Matrix
09 2D CAD Functional Specification
10 3D/BIM Functional Specification
11 Parametric Furniture Engine Specification
12 Component Designer Specification
13 Catalog Specification
14 BOM/BOQ/Pricing Specification
15 Manufacturing Engine Specification
16 Nesting Specification
17 CNC/CAM Specification
18 MES Specification
19 QR Tracking Specification
20 UI/UX Screen-by-Screen Specification
21 User Stories + Acceptance Criteria
22 Test Strategy
23 Deployment & DevOps
```

---

# 111. Final Business Principle

The platform must not be treated as separate software products stitched together.

It is one connected business system.

The primary business architecture is:

```text
                 CUSTOMER / PROJECT
                        │
                        ▼
                 SPATIAL DESIGN
                        │
                        ▼
                PARAMETRIC FURNITURE
                        │
                        ▼
                   ENGINEERING
                        │
              ┌─────────┴─────────┐
              ▼                   ▼
             BOQ                 BOM
              │                   │
              ▼                   ▼
           PRICING            MANUFACTURING
              │                   │
              ▼             ┌─────┼─────┐
          QUOTATION          ▼     ▼     ▼
                         CUTLIST NESTING CNC
                                  │
                                  ▼
                                 MES
                                  │
                           FACTORY EXECUTION
                                  │
                         QC → PACK → DISPATCH
```

---

# 112. Final Business Requirement

The most important requirement of the entire platform is:

> **Any significant information entered once by the user should be reused automatically wherever it is required downstream.**

For example:

```text
Wardrobe Width = 2400 mm
```

must become one authoritative value that can influence:

- 2D design
- 3D model
- elevation
- panel dimensions
- BOM
- BOQ
- pricing
- cutlist
- nesting
- CNC
- production

A change to that value must propagate through the relevant dependency chain and clearly identify any downstream information that needs revalidation.

This is the fundamental business requirement that differentiates the platform from disconnected interior design, quotation and manufacturing tools.

---

# 113. Final Acceptance Statement

The BRD shall be considered fulfilled when the product architecture and implementation plan demonstrate a credible path to:

**Design → Parametric Engineering → Commercial Calculation → Manufacturing → Factory Execution**

using a unified, version-controlled project model with secure multi-tenant access and traceable downstream outputs.

