# Product Vision & Scope
## Interior Design + Parametric Furniture + Manufacturing Execution Platform

**Document Type:** Product Vision & Scope / Implementation Baseline  
**Version:** 1.0  
**Status:** Implementation Baseline for Cursor / Engineering Team  
**Technology Baseline:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + HTML5 + CSS + Three.js  
**Primary Product Domain:** Interior Design, Architectural Spatial Planning, Modular Furniture Design, Estimation, Manufacturing and MES  
**Date:** 2026-08-09

---

# 1. Purpose of This Document

This document defines the product vision, business scope, product boundaries, major capabilities, architectural principles, implementation priorities, and acceptance expectations for an end-to-end interior design and furniture manufacturing platform.

This document is intended to be supplied to **Cursor / AI coding agents / software development teams** as the product-level source of truth before implementation begins.

The implementation team MUST use this document to:

1. Understand the intended product.
2. Establish the correct module boundaries.
3. Avoid building disconnected features.
4. Preserve a single source of truth between design and manufacturing.
5. Design the application for multi-tenant SaaS from the beginning.
6. Build extensible parametric furniture and manufacturing capabilities.
7. Prevent premature implementation of out-of-scope capabilities.
8. Prepare the codebase for future AI, BIM, CNC and MES integrations.

This document is a **product scope document**, not a detailed screen-by-screen SRS. Detailed functional requirements should be created from this baseline.

---

# 2. Product Vision

## 2.1 Vision Statement

Build a unified digital platform that connects:

**Architectural Design → Interior Design → Parametric Furniture → BOQ/BOM → Pricing → Manufacturing Engineering → Nesting → CNC/CAM → Factory Production → Quality → Packing → Dispatch**

using one unified, version-controlled, parametric project model.

The platform should eliminate repetitive data entry and disconnected workflows between designers, engineers, estimators and factories.

---

# 3. Product Mission

The mission of the platform is to enable interior businesses and furniture manufacturers to move from:

> **Design → Manual Engineering → Manual Estimation → Manual Cutlist → Manual Production Planning**

to:

> **Design Once → Generate Everything**

A furniture object created by a designer should become the source for downstream:

- 2D representation
- 3D representation
- Elevations
- Sections
- Dimensions
- BOM
- BOQ
- Pricing
- Cutlist
- Edge-banding requirements
- Hardware requirements
- Nesting
- CNC/CAM output
- Panel labels
- Production jobs
- Production tracking
- Packing
- Dispatch

---

# 4. Product Positioning

The product should be positioned as a:

## "Design-to-Manufacturing Operating System for Interior and Modular Furniture Businesses."

It is NOT only:

- an interior design tool,
- a 3D visualization tool,
- a CAD application,
- a quotation application,
- an ERP,
- or a factory tracking application.

It combines these capabilities through a common parametric project model.

---

# 5. Target Customers

## 5.1 Primary Customer Segments

### Segment A — Interior Designers

Needs:

- Floor planning
- Room design
- Furniture design
- Material selection
- Client presentation
- BOQ
- Pricing
- Proposal generation

### Segment B — Interior Design Companies

Needs:

- Multi-project management
- Multiple designers
- Standardized catalogs
- Templates
- Pricing rules
- Client approvals
- Centralized project data
- Design-to-production workflows

### Segment C — Modular Furniture Manufacturers

Needs:

- Parametric furniture
- Engineering automation
- Cutlists
- BOM
- Nesting
- CNC output
- Production planning
- QR tracking
- QC
- Packing
- Dispatch

### Segment D — Enterprise Furniture Brands

Needs:

- White-label platform
- Custom domain
- Product catalog
- Custom parametric components
- Enterprise pricing
- Factory integrations
- Role-based access
- Multi-location manufacturing

---

# 6. Primary User Personas

The initial product should support these personas.

| Persona | Primary Responsibility |
|---|---|
| Platform Super Admin | SaaS platform administration |
| Tenant Admin | Company configuration |
| Business Owner | Business/project oversight |
| Sales Executive | Leads, clients, quotations |
| Architect | Architectural planning |
| Interior Designer | Interior design and furniture |
| Design Engineer | Engineering validation |
| Estimator | BOQ/BOM/pricing |
| Production Manager | Manufacturing planning |
| Factory Supervisor | Production execution |
| CNC Operator | CNC operations |
| Edge Banding Operator | Edge-banding stage |
| Drilling Operator | Drilling stage |
| QC Inspector | Quality control |
| Packing Operator | Packing |
| Dispatch Manager | Dispatch |
| Installer | Installation |
| Client | Review, approval and presentation |

---

# 7. Core Product Principle

## 7.1 Single Source of Truth

The most important architectural/product principle is:

> **The parametric project model is the source of truth.**

The system must avoid maintaining independent versions of:

- 2D drawing
- 3D model
- BOM
- BOQ
- cutlist
- CNC
- pricing

Instead, these should be generated from the project model.

---

# 8. Unified Project Model

Conceptually:

```text
Tenant
 └── Organization
      └── Project
           └── Building
                └── Floor
                     └── Room
                          ├── Walls
                          ├── Doors
                          ├── Windows
                          ├── Columns
                          ├── Beams
                          ├── Ceiling
                          ├── Flooring
                          │
                          └── Furniture
                               ├── Wardrobe
                               ├── Kitchen
                               ├── TV Unit
                               ├── Base Unit
                               ├── Wall Unit
                               └── Custom Furniture
```

Every design object should have:

- unique ID
- object type
- parent object
- geometry
- parameters
- materials
- metadata
- manufacturing attributes
- pricing attributes
- revision
- audit information

---

# 9. Product Capability Map

The product is divided into the following major capability areas.

## 9.1 Platform Administration

Includes:

- Tenant management
- Organization management
- Users
- Roles
- Permissions
- Subscription configuration
- Branding
- Custom domains
- System configuration
- Audit logs

---

## 9.2 CRM & Sales

Includes:

- Leads
- Prospects
- Clients
- Contacts
- Opportunities
- Sales pipeline
- Project conversion
- Quotations
- Proposal management
- Follow-ups

---

## 9.3 Project Management

Includes:

- Projects
- Sites
- Buildings
- Floors
- Rooms
- Project members
- Project status
- Milestones
- Approvals
- Revisions
- Documents

---

# 10. Architectural & Spatial Design Scope

## 10.1 2D Floor Planning

The system should provide a CAD-like 2D workspace supporting:

- Walls
- Partitions
- Doors
- Windows
- Columns
- Beams
- Stairs
- Openings
- Furniture
- Fixtures
- Dimensions
- Text
- Labels
- Annotations

---

## 10.2 Precision Drafting

Support:

- Grid
- Snap
- Object snap
- Angle snap
- Orthogonal drawing
- Alignment
- Offset
- Trim
- Extend
- Move
- Copy
- Rotate
- Mirror
- Delete
- Undo
- Redo

---

## 10.3 Parametric Architectural Objects

Objects should not be represented merely as visual pixels.

A wall should contain:

```text
Wall
- Start point
- End point
- Thickness
- Height
- Base elevation
- Material
- Interior finish
- Exterior finish
- Openings
```

Doors and windows should similarly contain dimensional and material properties.

---

# 11. 3D/BIM Scope

The platform should provide a unified 3D environment.

## 11.1 3D Requirements

Support:

- Walls
- Floors
- Ceilings
- Doors
- Windows
- Columns
- Beams
- Furniture
- Materials
- Lighting
- Cameras
- Basic rendering

Three.js should be used as the initial 3D rendering framework.

---

# 12. 2D/3D Synchronization

A critical requirement:

> Changes in one representation must update the other representation.

Example:

```text
2D Wall Width
2500 → 3000
```

must update:

- 3D wall
- room dimensions
- furniture constraints
- elevations
- quantities

Similarly, modifying a parametric furniture object in 3D must update its corresponding 2D representation.

---

# 13. BIM Direction

The first implementation does not need to become a complete industry BIM replacement.

The system should instead establish a BIM-like object model where objects have:

- geometry
- identity
- relationships
- properties
- materials
- metadata
- quantities

Future IFC/BIM interoperability should remain possible.

---

# 14. Interior Design Scope

Support:

- Room styling
- Wall finishes
- Flooring
- Ceiling design
- False ceiling
- Tiling
- Paint
- Wallpaper
- Decorative elements
- Furniture placement
- Material assignment
- Lighting objects
- Fixtures

---

# 15. Modular Furniture Scope

This is a core product capability.

Initial furniture categories:

1. Wardrobe
2. Kitchen base unit
3. Kitchen wall unit
4. Kitchen tall unit
5. Kitchen island
6. TV unit
7. Storage unit
8. Vanity
9. Bookshelf
10. Study unit
11. Custom cabinet

The architecture must allow additional categories without rewriting the core engine.

---

# 16. Parametric Furniture Principle

Furniture must be generated from parameters rather than static geometry.

Example:

```text
Wardrobe

Width = 2400 mm
Height = 2400 mm
Depth = 600 mm
Carcass Thickness = 18 mm
Back Thickness = 6 mm
Shutter Thickness = 18 mm
```

Changing width should automatically recalculate dependent components.

---

# 17. Component Designer Scope

The platform should eventually allow enterprise users to create custom parametric components.

A component should support:

- Parameters
- Constraints
- Rules
- Geometry
- Materials
- Hardware
- Manufacturing attributes
- Pricing attributes

Example:

```text
Custom Cabinet

Parameters:
- Width
- Height
- Depth
- Shelf Count
- Drawer Count
- Carcass Thickness

Rules:
- Shelf width calculation
- Panel thickness rules
- Hardware selection
- Minimum/maximum dimensions
```

---

# 18. Catalog Scope

The platform should include catalogs for:

## Boards

- MDF
- HDF
- Plywood
- Particle board
- Blockboard

Properties:

- Brand
- Product code
- Thickness
- Length
- Width
- Finish
- Color
- Grain direction
- Cost
- Selling price
- Available sheet sizes

## Laminates

- Brand
- Collection
- Code
- Name
- Finish
- Thickness
- Image
- Cost
- Selling price

## Edge Banding

- Material
- Thickness
- Width
- Color
- Cost per meter

## Hardware

- Hinges
- Drawer channels
- Handles
- Connectors
- Screws
- Lifts
- Baskets
- Mechanisms

---

# 19. BOM Scope

The platform must generate a live BOM.

BOM categories may include:

- Panels
- Boards
- Laminates
- Edge banding
- Hardware
- Accessories
- Profiles
- Fasteners

BOM should be generated from the project model.

---

# 20. BOQ Scope

BOQ is the commercial representation of the project.

It should support:

- Item
- Description
- Quantity
- Unit
- Rate
- Discount
- Tax
- Total
- Margin

BOQ should be linked to the project design but remain commercially configurable.

---

# 21. Pricing Engine Scope

Support two pricing models.

## Model A — Raw Material Pricing

Calculate based on:

- Board area
- Laminate area
- Edge-band length
- Hardware units
- Labour
- Manufacturing cost
- Installation
- Overhead
- Margin

## Model B — Panel / Unit Pricing

Examples:

```text
18mm MDF Panel = ₹X
Drawer = ₹X
Shutter = ₹X / sq.ft
Cabinet = ₹X / unit
```

The pricing engine should allow tenant-specific pricing rules.

---

# 22. Design-to-Manufacturing Scope

The platform should transform furniture designs into manufacturing-ready information.

Pipeline:

```text
Furniture Model
      ↓
Engineering Validation
      ↓
Panel Decomposition
      ↓
BOM
      ↓
Cutlist
      ↓
Edge Banding
      ↓
Hardware
      ↓
Drilling
      ↓
Nesting
      ↓
CNC/CAM
      ↓
Production
```

---

# 23. Cutlist Scope

A cutlist item should contain at minimum:

```text
Part ID
Project ID
Furniture ID
Room ID
Material
Thickness
Length
Width
Quantity
Grain Direction
Edge Banding
Drilling
Routing
```

---

# 24. Nesting Scope

The system should support sheet optimization.

Input:

- Available board sheet sizes
- Panel list
- Grain direction
- Cutting kerf
- Minimum spacing
- Rotation rules

Output:

- Sheet count
- Panel placement
- Used area
- Waste area
- Waste percentage
- Cutting layout

The initial implementation may use heuristic optimization.

Advanced optimization can be introduced later.

---

# 25. CNC/CAM Scope

The architecture must support machine-specific output adapters.

Potential targets:

- Biesse
- Homag
- KDT
- Generic CNC
- Standard DXF
- CSV
- G-code where applicable

The platform should NOT hard-code one manufacturer's format into the core manufacturing engine.

Instead:

```text
Internal Manufacturing Model
          ↓
Machine Adapter
          ↓
Machine-specific Output
```

---

# 26. MES Scope

The Manufacturing Execution System should track production after engineering release.

Production stages:

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

Each production job should have:

- Job ID
- Project
- Customer
- Furniture
- Panels
- Operations
- Status
- Assigned operator
- Start time
- Completion time
- QC status
- Notes

---

# 27. QR / Barcode Panel Tracking

Every manufacturable panel should be identifiable.

Example:

```text
Project: PRJ-001
Furniture: WARD-003
Panel: PNL-014
```

The system should generate QR/barcode labels containing the panel identifier.

Scanning should allow factory users to:

- View panel details
- View dimensions
- View material
- View furniture
- View operation sequence
- Start operation
- Complete operation
- Report issue
- Send to QC

---

# 28. Manufacturing Release Control

A design must have controlled lifecycle states.

Recommended lifecycle:

```text
Draft
 ↓
Engineering
 ↓
Design Review
 ↓
Client Approval
 ↓
Production Ready
 ↓
Released
 ↓
In Production
 ↓
Completed
```

Once released to production, changes must not silently overwrite the released manufacturing revision.

A new revision must be created.

---

# 29. Revision Management

Support:

- Project revision
- Design revision
- Furniture revision
- Manufacturing revision
- Approval history
- Change history
- Restore revision

Example:

```text
Revision 1
   ↓
Approved
   ↓
Released
   ↓
Design Change
   ↓
Revision 2
```

---

# 30. Documentation Scope

Automatically generate:

## Architectural

- Floor plans
- Dimension plans
- Furniture plans
- Ceiling plans
- Electrical plans
- Plumbing plans

## Furniture

- Front elevation
- Side elevation
- Top view
- Sections
- Internal views
- Detail drawings

## Manufacturing

- Cutlist
- BOM
- Panel drawings
- Assembly information
- CNC files

---

# 31. Client Presentation Scope

The platform should support branded presentation generation.

Presentation may contain:

- Company logo
- Client information
- Project information
- Floor plans
- 3D views
- Furniture views
- Materials
- BOQ
- Price summary
- Terms
- Approval section

Output:

- PDF
- Digital presentation

---

# 32. AI Scope

AI is a strategic capability but should NOT be tightly coupled into the initial core architecture.

Potential AI capabilities:

## Floorplan Image → Editable Model

```text
Floorplan Image
 ↓
Wall Detection
 ↓
Room Detection
 ↓
Door/Window Detection
 ↓
Vectorization
 ↓
Editable 2D Model
 ↓
3D Generation
```

AI-generated geometry must remain editable and user-verifiable.

---

# 33. White Label / SaaS Scope

The platform must be multi-tenant.

Each tenant should have:

- Tenant ID
- Users
- Projects
- Catalog
- Pricing rules
- Branding
- Templates
- Email settings
- Domain settings

---

# 34. Custom Domain

Future target:

```text
design.customer.com
```

instead of:

```text
app.platform.com
```

Custom-domain architecture must be considered during tenant design.

Do not implement domain routing in a way that prevents future multi-domain support.

---

# 35. Tenant Branding

Tenant-specific configuration:

- Company name
- Logo
- Favicon
- Brand colors
- Email branding
- PDF branding
- Proposal templates
- Quotation templates
- Terms and conditions
- GST/tax details
- Currency
- Contact details

---

# 36. RBAC Scope

Permissions must be granular.

Examples:

```text
project.view
project.create
project.edit
project.delete

design.view
design.create
design.edit

furniture.view
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

dispatch.view
dispatch.update
```

Do not implement authorization using only hard-coded role checks.

Use:

```text
User → Role → Permission
```

---

# 37. Non-Functional Product Goals

## Performance

The application should be designed for:

- large projects
- multiple rooms
- hundreds of design objects
- large furniture assemblies
- thousands of BOM items
- large material catalogs

Heavy calculations should not freeze the browser.

Use Web Workers where appropriate.

---

# 38. Offline / Local Resilience

The design workspace should eventually support local caching.

Potential technology:

```text
IndexedDB
```

The browser should be able to temporarily preserve unsaved work.

The initial implementation should at minimum prevent accidental loss during normal browser failures.

---

# 39. Auditability

Important business actions must be auditable.

Track:

- User
- Timestamp
- Action
- Entity
- Previous value
- New value
- IP/session metadata where appropriate

Especially:

- Price changes
- Design approvals
- Manufacturing release
- Revision creation
- Production status changes

---

# 40. Security Scope

The platform should include:

- Secure authentication
- Password hashing
- Session management
- CSRF protection
- SQL injection protection
- XSS protection
- Input validation
- Authorization checks
- File upload validation
- Tenant isolation
- Audit logs

Never trust client-side authorization.

---

# 41. Technology Constraints

Initial technology:

```text
Backend:
PHP 8.x

Database:
MySQL 8.x

Frontend:
HTML5
CSS
JavaScript ES6+

3D:
Three.js

API:
REST

Storage:
Filesystem/Object storage

Authentication:
Secure PHP session or token architecture
```

The implementation should avoid introducing a frontend framework unless explicitly approved.

---

# 42. Code Architecture Principle

Avoid:

```text
one giant app.js
one giant PHP file
database queries inside UI code
business logic inside HTML
manufacturing rules inside rendering code
```

Use modular separation:

```text
UI
 ↓
Application State
 ↓
Domain Services
 ↓
API
 ↓
Database
```

---

# 43. Domain Separation

The following domains should remain logically independent:

```text
Authentication
Tenant
CRM
Projects
Architecture
Furniture
Materials
Pricing
BOM
BOQ
Manufacturing
MES
Documents
AI
```

They may initially exist in one deployable application but should have clean boundaries.

---

# 44. Event-Based Synchronization

The system should support domain events.

Examples:

```text
FurnitureCreated
FurnitureUpdated
FurnitureDeleted
MaterialChanged
ProjectApproved
DesignReleased
ManufacturingGenerated
ProductionStarted
ProductionCompleted
QCApproved
```

Example:

```text
FurnitureUpdated
       ↓
Recalculate Geometry
       ↓
Recalculate BOM
       ↓
Recalculate BOQ
       ↓
Recalculate Pricing
       ↓
Invalidate Manufacturing Output
```

---

# 45. Critical Business Rule

When design changes after manufacturing output is generated:

The system MUST identify affected downstream artifacts.

Example:

```text
Wardrobe Width
2400 → 2700
```

System should identify:

```text
3D = affected
2D = affected
Elevation = affected
BOM = affected
BOQ = affected
Cutlist = affected
Nesting = affected
CNC = affected
Production release = affected
```

The application must never silently leave stale manufacturing data.

---

# 46. Product Lifecycle

Recommended project lifecycle:

```text
Lead
 ↓
Opportunity
 ↓
Project Created
 ↓
Site Survey
 ↓
Architectural Design
 ↓
Interior Design
 ↓
Furniture Design
 ↓
Estimation
 ↓
Client Proposal
 ↓
Client Approval
 ↓
Engineering
 ↓
Manufacturing Planning
 ↓
Production Release
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

# 47. Scope Priority

## P0 — Foundation / Must Have

- Multi-tenancy foundation
- Authentication
- RBAC
- Project management
- Building/floor/room
- 2D workspace
- Basic 3D
- Parametric furniture foundation
- Materials
- BOM
- BOQ
- Pricing
- Cutlist
- Revision system

## P1 — Core Commercial Product

- Advanced furniture
- Nesting
- Manufacturing jobs
- QR tracking
- Documentation
- Client proposals
- Production workflow
- White labeling

## P2 — Advanced Manufacturing

- Machine-specific CNC adapters
- Advanced nesting
- Drilling optimization
- Routing
- Production analytics
- Factory dashboards

## P3 — Advanced Intelligence

- AI floorplan recognition
- Image-to-3D
- AI design assistant
- Automated design suggestions
- Predictive manufacturing insights

---

# 48. Explicitly Out of Initial MVP Scope

Unless separately approved, the first implementation should NOT attempt to fully implement:

- Full Revit replacement
- Full architectural BIM certification
- Structural engineering calculations
- Structural load analysis
- Advanced photorealistic rendering
- AI-generated entire homes without user control
- Every CNC manufacturer
- Complete accounting ERP
- Payroll
- HR
- Procurement ERP
- Inventory ERP
- Supplier marketplace
- Full mobile application
- Advanced AR/VR
- Autonomous factory robotics

These may become future products/modules.

---

# 49. MVP Success Definition

The MVP is successful if a user can complete this journey:

```text
Create Tenant
      ↓
Create Client
      ↓
Create Project
      ↓
Create Building/Floor/Room
      ↓
Draw Room in 2D
      ↓
See Room in 3D
      ↓
Add Parametric Wardrobe
      ↓
Configure Dimensions
      ↓
Configure Materials
      ↓
Generate BOM
      ↓
Generate BOQ
      ↓
Calculate Price
      ↓
Generate Cutlist
      ↓
Generate Basic Nesting
      ↓
Generate Production Data
      ↓
Generate Panel Labels
```

This is the minimum end-to-end value chain.

---

# 50. Definition of Done for Product Architecture

Before feature implementation proceeds beyond foundation, the codebase should have:

- Modular directory structure
- Tenant-aware data model
- Authentication
- RBAC
- API layer
- Database migration strategy
- Central state management
- Event bus
- Command/undo-redo architecture
- Project object model
- Parametric object model
- Version/revision mechanism
- Audit logging foundation
- File storage abstraction
- Error handling
- Logging
- Validation framework
- Automated test structure

---

# 51. Cursor Implementation Instructions

Cursor MUST treat this document as the product-level source of truth.

Before writing implementation code:

1. Inspect the existing repository.
2. Identify current architecture.
3. Identify reusable modules.
4. Identify technical debt.
5. Identify conflicts with this product vision.
6. Do NOT immediately rewrite the entire application.
7. Produce an architecture gap report first.
8. Propose a phased implementation plan.
9. Identify dependencies between modules.
10. Identify database changes.
11. Identify APIs required.
12. Identify frontend modules required.
13. Identify domain services required.

After architecture approval, implementation should proceed module by module.

---

# 52. Cursor Must Not

Cursor MUST NOT:

- Create duplicate business logic.
- Hard-code material rules into UI.
- Hard-code pricing into furniture components.
- Hard-code machine-specific CNC logic into the core engine.
- Store all application state in global variables.
- Create one giant JavaScript file.
- Put SQL queries directly inside presentation code.
- Trust browser permissions for authorization.
- Modify production data without revision control.
- silently regenerate released manufacturing data.
- introduce unnecessary frameworks.
- replace working functionality without evidence.
- remove existing functionality without explicit approval.

---

# 53. Required Engineering Pattern

Use the following conceptual flow:

```text
USER ACTION
    ↓
UI COMPONENT
    ↓
COMMAND
    ↓
DOMAIN SERVICE
    ↓
DOMAIN MODEL
    ↓
STATE UPDATE
    ↓
EVENT
    ↓
DEPENDENT SERVICES
    ↓
PERSISTENCE
```

Example:

```text
Designer changes wardrobe width
            ↓
ResizeFurnitureCommand
            ↓
FurnitureService
            ↓
ParametricRulesEngine
            ↓
PanelGenerator
            ↓
BOMGenerator
            ↓
PricingEngine
            ↓
ManufacturingImpactAnalyzer
            ↓
UI Refresh
```

---

# 54. Future Scalability Direction

The first deployment can remain a modular PHP application.

Long-term architecture may evolve toward:

```text
                 Web Application
                       │
                PHP Application
                       │
        ┌──────────────┼──────────────┐
        │              │              │
      MySQL       File Storage     Cache/Queue
        │
        └──────────────┬──────────────┘
                       │
                 Engine Services
                       │
        ┌──────────────┼──────────────┐
        │              │              │
 Geometry Engine   Manufacturing   AI Engine
                   Engine
                       │
              CNC / Nesting Services
```

The architecture should therefore avoid decisions that make future service extraction impossible.

---

# 55. Product Differentiation

The product's primary differentiation should be:

## 1. Design-to-Manufacturing Continuity

No manual re-engineering between design and factory.

## 2. Parametric Furniture Intelligence

Furniture is generated from rules and dimensions.

## 3. Unified Commercial Model

Design automatically drives BOM, BOQ and pricing.

## 4. Manufacturing Automation

Furniture directly drives cutlists, nesting and CNC.

## 5. Factory Traceability

QR-based panel tracking connects engineering to production.

## 6. Enterprise White Label

Businesses can operate the platform under their own brand/domain.

---

# 56. Product North Star

The product should ultimately enable:

```text
ONE PROJECT
     ↓
ONE DIGITAL MODEL
     ↓
EVERYTHING GENERATED
```

Specifically:

```text
                 DIGITAL PROJECT MODEL
                         │
       ┌─────────────────┼─────────────────┐
       │                 │                 │
      DESIGN          COMMERCIAL       MANUFACTURING
       │                 │                 │
   2D / 3D          BOQ / Pricing      BOM / Cutlist
       │                 │                 │
  Elevations        Proposal           Nesting
       │                 │                 │
  Drawings          Invoice-ready       CNC/CAM
       │                 │                 │
       └─────────────────┼─────────────────┘
                         │
                       MES
                         │
              Production / QC / Packing
                         │
                      Dispatch
```

---

# 57. Final Product Principle

Every future feature should be evaluated against this question:

> **Does this feature strengthen the connection between design, commercial estimation, engineering, manufacturing and project execution?**

If yes, it belongs naturally in the platform.

If it creates a disconnected data silo, it should be reconsidered.

---

# 58. Immediate Next Implementation Documents

After this Product Vision & Scope, the recommended implementation sequence is:

1. **System Architecture & Technical Architecture**
2. **Business Requirements Document (BRD)**
3. **Software Requirements Specification (SRS)**
4. **Functional Specification Document (FSD)**
5. **Database Architecture & ERD**
6. **API Specification**
7. **RBAC & Permission Matrix**
8. **2D CAD Workspace Specification**
9. **3D/BIM Workspace Specification**
10. **Parametric Furniture Engine Specification**
11. **Material & Catalog Specification**
12. **BOM/BOQ/Pricing Engine Specification**
13. **Manufacturing Engine Specification**
14. **Nesting Engine Specification**
15. **CNC/CAM Adapter Specification**
16. **MES Specification**
17. **QR Panel Tracking Specification**
18. **UI/UX Screen-by-Screen Specification**
19. **User Stories & Acceptance Criteria**
20. **Test Strategy**
21. **Deployment & DevOps Specification**

---

# 59. Product Scope Approval Checklist

Before moving into detailed implementation, confirm:

- [ ] Product vision approved
- [ ] Target customers approved
- [ ] User personas approved
- [ ] Product modules approved
- [ ] MVP scope approved
- [ ] Out-of-scope items approved
- [ ] Unified project model approved
- [ ] Parametric furniture strategy approved
- [ ] Manufacturing strategy approved
- [ ] SaaS/tenant architecture approved
- [ ] Revision strategy approved
- [ ] Technology stack approved
- [ ] Future AI strategy acknowledged
- [ ] Future CNC adapter strategy acknowledged

---

# 60. Final Statement

This platform should not be implemented as a collection of unrelated screens.

It should be implemented as a **unified parametric design and manufacturing system** where:

**Spatial Design → Furniture Design → Engineering → Commercial Calculation → Manufacturing → Factory Execution**

are connected through a common project model.

The success of the product depends less on the number of screens and more on the integrity of this underlying model.

**Primary engineering objective:**

> Build the data model, parametric engine and domain architecture correctly first; then expose those capabilities through the 2D, 3D, commercial and MES interfaces.
