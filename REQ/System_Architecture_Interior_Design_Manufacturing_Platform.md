# System Architecture & Technical Architecture
## Interior Design + Parametric Furniture + Manufacturing Execution Platform

**Document Type:** System Architecture & Technical Architecture Specification  
**Version:** 1.0  
**Status:** Implementation Baseline for Cursor / Engineering Team  
**Technology Baseline:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + HTML5 + CSS + Three.js  
**Architecture Style:** Modular Monolith with Service-Oriented Domain Boundaries and Future Engine Extraction  
**Primary Deployment:** Linux Web Server / VPS / Cloud  
**Date:** 2026-08-09

---

# 1. Purpose

This document defines the technical architecture for an end-to-end:

**Architectural Design + Interior Design + Parametric Furniture + BOQ/BOM + Pricing + Manufacturing + MES**

platform.

It is intended to be supplied directly to **Cursor / AI coding agents / software development teams** as the technical architecture baseline.

The architecture MUST support:

- Multi-tenant SaaS
- 2D CAD-like design
- 3D spatial visualization
- Parametric furniture
- Component Designer
- Materials/catalogs
- BOM/BOQ
- Dual pricing
- Design-to-manufacturing
- Cutlists
- Nesting
- CNC/CAM adapters
- MES
- QR panel tracking
- Document generation
- Revision control
- Auditability
- Future AI services
- Future extraction of heavy computational engines

---

# 2. Architectural Vision

The system shall be designed around a single principle:

> **One unified parametric project model is the source of truth for design, engineering, commercial calculation and manufacturing.**

The architecture must prevent the following disconnected flow:

```text
2D Design
   ↓
Manual 3D
   ↓
Manual BOM
   ↓
Manual BOQ
   ↓
Manual Cutlist
   ↓
Manual CNC
```

Instead:

```text
                   UNIFIED PROJECT MODEL
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
      DESIGN          COMMERCIAL        MANUFACTURING
        │                  │                  │
    2D / 3D          BOM / BOQ / Price   Cutlist / Nesting
        │                  │                  │
  Drawings/Elevation     Proposal             CNC/CAM
        │                  │                  │
        └──────────────────┼──────────────────┘
                           │
                          MES
                           │
                    Production / QC
                           │
                    Packing / Dispatch
```

---

# 3. Architecture Strategy

## 3.1 Initial Architecture

The first production version should use a:

> **Modular Monolith**

rather than immediately creating microservices.

All core domains may run in one PHP application, but each domain MUST have clear boundaries.

Example:

```text
PHP Application
│
├── Identity
├── Tenant
├── CRM
├── Project
├── Architecture
├── Furniture
├── Catalog
├── Pricing
├── BOM
├── BOQ
├── Manufacturing
├── MES
├── Documents
└── AI Integration
```

The architecture must make future extraction possible.

---

# 4. Future Architecture

When scale or computational requirements justify extraction:

```text
                         Web Browser
                              │
                         API Gateway
                              │
              ┌───────────────┼────────────────┐
              │               │                │
        PHP Application    Geometry API    Manufacturing API
              │               │                │
              │               │          ┌─────┼─────┐
              │               │          │     │     │
              │               │       Nesting CNC   CAM
              │               │
              └───────────────┼────────────────┘
                              │
                            MySQL
                              │
                      Object/File Storage
```

Do NOT implement this distributed architecture prematurely.

Design interfaces so that extraction is possible later.

---

# 5. Technology Stack

## 5.1 Frontend

Required:

- HTML5
- CSS3
- JavaScript ES6+
- ES Modules
- SVG
- Canvas
- Three.js
- Web Workers
- IndexedDB where required

Optional libraries may be introduced only when they solve a clearly documented requirement.

Do not introduce React/Vue/Angular unless explicitly approved.

---

# 6. Backend

Required:

- PHP 8.x
- MySQL 8.x
- REST API
- JSON
- Secure session/token authentication
- PDO or equivalent safe database abstraction

Backend code MUST use strict typing where practical.

---

# 7. Storage

Use separate storage responsibilities.

## MySQL

Store:

- Business entities
- Project metadata
- Parametric data
- Relationships
- User data
- Permissions
- BOM
- BOQ
- Pricing
- Manufacturing metadata
- Production data
- Audit logs

## File/Object Storage

Store:

- Uploaded images
- 3D assets
- PDF files
- Generated drawings
- CNC files
- DXF
- CSV exports
- Generated reports
- QR labels

Large binary files MUST NOT be stored directly in normal MySQL business tables.

---

# 8. High-Level Architecture

```text
┌─────────────────────────────────────────────────────┐
│                    WEB CLIENT                       │
│                                                     │
│  2D Editor │ 3D Viewer │ Furniture │ MES │ Admin  │
└───────────────────────┬─────────────────────────────┘
                        │
                     REST API
                        │
┌───────────────────────▼─────────────────────────────┐
│                APPLICATION LAYER                    │
│                                                     │
│ Controllers / API / Authentication / Validation    │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│                  DOMAIN LAYER                       │
│                                                     │
│ Tenant │ Project │ Design │ Furniture │ Catalog    │
│ Pricing │ BOM │ BOQ │ Manufacturing │ MES         │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│                    ENGINE LAYER                     │
│                                                     │
│ Geometry │ Parametric │ BOM │ Pricing │ Nesting    │
│ Cutlist │ CNC │ Document │ Validation             │
└───────────────────────┬─────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────┐
│                INFRASTRUCTURE                       │
│                                                     │
│ MySQL │ File Storage │ Queue │ Cache │ Logging     │
└─────────────────────────────────────────────────────┘
```

---

# 9. Architectural Layers

The system MUST follow these conceptual layers.

## Layer 1 — Presentation

Responsibilities:

- UI
- user interaction
- rendering
- forms
- workspace
- editor tools
- dashboards

Must NOT contain:

- SQL
- pricing formulas
- manufacturing rules
- authorization logic

---

## Layer 2 — Application

Responsibilities:

- Use-case orchestration
- command handling
- request validation
- transactions
- application-level authorization
- event dispatch

Example:

```text
CreateFurniture
UpdateFurniture
GenerateBOM
GenerateBOQ
ReleaseManufacturing
StartProduction
CompleteQC
```

---

## Layer 3 — Domain

Contains business rules.

Examples:

```text
FurnitureRules
WallRules
MaterialRules
PricingRules
ManufacturingRules
ProductionRules
RevisionRules
```

---

## Layer 4 — Engine

Contains computational logic.

Examples:

```text
GeometryEngine
ParametricEngine
PanelEngine
BOMEngine
PricingEngine
NestingEngine
CNCGenerationEngine
DocumentEngine
```

---

## Layer 5 — Infrastructure

Contains:

- Database
- File storage
- authentication provider
- logging
- cache
- queue
- external services

---

# 10. Recommended Backend Folder Structure

```text
/backend
│
├── public/
│   └── index.php
│
├── config/
│   ├── app.php
│   ├── database.php
│   ├── storage.php
│   └── auth.php
│
├── routes/
│   └── api.php
│
├── controllers/
│
├── requests/
│
├── responses/
│
├── middleware/
│
├── models/
│
├── repositories/
│
├── services/
│
├── domain/
│   ├── Tenant/
│   ├── Identity/
│   ├── CRM/
│   ├── Project/
│   ├── Architecture/
│   ├── Furniture/
│   ├── Catalog/
│   ├── Pricing/
│   ├── BOM/
│   ├── BOQ/
│   ├── Manufacturing/
│   ├── MES/
│   └── Documents/
│
├── engines/
│   ├── Geometry/
│   ├── Parametric/
│   ├── Panel/
│   ├── BOM/
│   ├── Pricing/
│   ├── Nesting/
│   ├── CNC/
│   └── Document/
│
├── events/
│
├── commands/
│
├── validators/
│
├── jobs/
│
├── storage/
│
└── tests/
```

---

# 11. Frontend Architecture

Recommended:

```text
/frontend
│
├── index.html
│
├── assets/
│
├── css/
│
└── js/
    │
    ├── app/
    │   ├── Application.js
    │   ├── Router.js
    │   ├── StateManager.js
    │   ├── EventBus.js
    │   └── CommandManager.js
    │
    ├── api/
    │   ├── ApiClient.js
    │   ├── AuthApi.js
    │   ├── ProjectApi.js
    │   ├── FurnitureApi.js
    │   └── ManufacturingApi.js
    │
    ├── workspace/
    │
    ├── canvas2d/
    │
    ├── viewer3d/
    │
    ├── furniture/
    │
    ├── parametric/
    │
    ├── catalog/
    │
    ├── pricing/
    │
    ├── manufacturing/
    │
    ├── mes/
    │
    ├── documents/
    │
    └── ui/
```

---

# 12. Frontend State Architecture

Do NOT allow arbitrary global variables.

Use a centralized application state.

Conceptually:

```javascript
const state = {
    tenant: {},
    user: {},
    project: {},
    selection: {},
    design: {},
    furniture: {},
    materials: {},
    bom: {},
    boq: {},
    manufacturing: {},
    ui: {}
};
```

State changes must occur through controlled actions/commands.

---

# 13. Event Bus

Implement an application-level event bus.

Example:

```text
FurnitureCreated
FurnitureUpdated
FurnitureDeleted

WallCreated
WallUpdated

MaterialChanged

BOMGenerated
BOQGenerated

DesignApproved
ManufacturingReleased

ProductionStarted
ProductionCompleted
QCApproved
```

Example event flow:

```text
FurnitureUpdated
      ↓
ParametricEngine
      ↓
Geometry Updated
      ↓
BOM Regeneration
      ↓
BOQ Recalculation
      ↓
Manufacturing Impact Analysis
```

---

# 14. Command Architecture

The design workspace should use commands.

Example:

```text
CreateWallCommand
MoveWallCommand
ResizeWallCommand
DeleteWallCommand

CreateFurnitureCommand
MoveFurnitureCommand
ResizeFurnitureCommand

AddComponentCommand
DeleteComponentCommand
ChangeMaterialCommand
```

Each command should conceptually support:

```javascript
execute()
undo()
redo()
```

This creates a robust CAD-style editing model.

---

# 15. Undo / Redo

Undo/redo MUST NOT simply reload the entire project.

Use a command history.

Example:

```text
Action 1: Create Wall
Action 2: Move Wall
Action 3: Add Door
Action 4: Add Wardrobe
Action 5: Change Material
```

Undo:

```text
Action 5 reversed
```

Redo:

```text
Action 5 restored
```

---

# 16. Unified Project Domain Model

Core hierarchy:

```text
Tenant
 └── Organization
      └── Client
      └── Project
           └── Building
                └── Floor
                     └── Room
                          └── Design Objects
                               └── Furniture Instances
```

Furniture:

```text
Furniture Instance
 ├── Template
 ├── Parameters
 ├── Components
 ├── Materials
 ├── Hardware
 ├── Panels
 └── Manufacturing Data
```

---

# 17. Design Object Model

A generic design object should support:

```text
id
project_id
room_id
parent_id
object_type
name
geometry
parameters
materials
metadata
status
revision
created_by
updated_by
created_at
updated_at
```

Geometry and dynamic properties may be stored as JSON.

Example:

```json
{
  "type": "wall",
  "geometry": {
    "start": {"x": 0, "y": 0},
    "end": {"x": 5000, "y": 0}
  },
  "parameters": {
    "thickness": 150,
    "height": 3000
  }
}
```

---

# 18. Database Strategy

Use normalized relational tables for:

- identity
- tenant
- projects
- rooms
- materials
- pricing
- BOM
- BOQ
- manufacturing
- MES

Use JSON for highly dynamic parametric data.

Do not create hundreds of columns for every possible furniture parameter.

---

# 19. Tenant Isolation

Every tenant-owned entity MUST be associated with a tenant.

Example:

```text
tenant_id
```

The application MUST enforce tenant isolation at the repository/service layer.

Never rely solely on frontend filtering.

Example query pattern:

```sql
SELECT *
FROM projects
WHERE tenant_id = :tenant_id
AND id = :project_id;
```

Do not permit a project from another tenant to be retrieved merely because its ID is known.

---

# 20. Identity Architecture

Core entities:

```text
users
roles
permissions
role_permissions
user_roles
sessions
```

Authorization:

```text
Authenticated User
       ↓
Tenant Membership
       ↓
Role
       ↓
Permission
       ↓
Resource-level access
```

---

# 21. API Architecture

Use versioned REST APIs.

Base:

```text
/api/v1/
```

Examples:

```text
/api/v1/auth/login
/api/v1/projects
/api/v1/projects/{id}
/api/v1/rooms
/api/v1/design-objects
/api/v1/furniture
/api/v1/materials
/api/v1/bom
/api/v1/boq
/api/v1/manufacturing
/api/v1/production
```

---

# 22. API Response Standard

Use a consistent JSON structure.

Success:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Error:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid furniture dimensions",
    "details": []
  }
}
```

---

# 23. API Error Codes

Standardize error codes.

Examples:

```text
AUTH_REQUIRED
ACCESS_DENIED
RESOURCE_NOT_FOUND
VALIDATION_ERROR
INVALID_STATE
REVISION_CONFLICT
MANUFACTURING_LOCKED
STALE_DATA
INTERNAL_ERROR
```

---

# 24. Database Transactions

Use transactions for multi-step business operations.

Example:

```text
Release Manufacturing
    ↓
Create Manufacturing Revision
    ↓
Generate BOM Snapshot
    ↓
Generate Cutlist
    ↓
Create Manufacturing Job
    ↓
Set Status = Released
```

These operations should be transactional where possible.

---

# 25. Optimistic Concurrency

Projects may eventually be edited by multiple users.

Use a version field:

```text
version
```

Example:

```text
Client version = 7
Server version = 8
```

The system should detect stale updates rather than silently overwriting another user's changes.

---

# 26. Project Revision Architecture

Every important project state should support revision.

Conceptual:

```text
Project
 ├── Revision 1
 ├── Revision 2
 └── Revision 3
```

Furniture may also have independent revisions.

Manufacturing outputs should reference a specific revision.

---

# 27. Manufacturing Snapshot

When manufacturing is released, create an immutable or controlled snapshot of:

- Furniture geometry
- Parameters
- Materials
- BOM
- Panel data
- Edge banding
- Hardware
- CNC instructions
- Pricing basis where applicable

Production must reference the manufacturing snapshot, not mutable design state.

---

# 28. Design-to-Manufacturing Dependency Graph

The architecture should represent dependencies:

```text
Furniture
   ↓
Components
   ↓
Panels
   ↓
Cutlist
   ↓
Nesting
   ↓
CNC
   ↓
Production
```

When a parent changes, affected downstream objects become stale.

Example:

```text
Furniture Updated
      ↓
BOM = STALE
BOQ = STALE
Cutlist = STALE
Nesting = STALE
CNC = STALE
```

The UI should clearly display this.

---

# 29. Parametric Engine

The Parametric Engine is a core domain engine.

Responsibilities:

- Parameter evaluation
- Constraints
- Formula evaluation
- Component generation
- Dimension validation
- Dependency calculation
- Geometry generation

Example:

```text
Wardrobe Width
      ↓
Internal Width
      ↓
Shelf Width
      ↓
Drawer Width
      ↓
Shutter Width
```

---

# 30. Parametric Rule Model

Rules should not be scattered through frontend code.

Example:

```json
{
  "rule": "shelf_width",
  "formula": "internal_width - (2 * carcass_thickness)",
  "unit": "mm"
}
```

The implementation may use a controlled expression/rule engine.

Never execute arbitrary user-supplied PHP/JavaScript as formulas.

---

# 31. Geometry Engine

Responsibilities:

- Wall geometry
- Furniture geometry
- Panel geometry
- Openings
- Intersections
- Bounding boxes
- Transformations
- Measurements
- Collision checks

The engine should be independent of Three.js.

Three.js is the renderer, not the business geometry engine.

---

# 32. Three.js Architecture

Three.js should consume domain geometry.

Conceptually:

```text
Domain Model
     ↓
Geometry Engine
     ↓
Renderable Geometry
     ↓
Three.js Scene
```

Do NOT make Three.js objects the source of truth.

For example:

```javascript
mesh.position.x
```

must not become the permanent database state.

The database should contain the domain object's geometry/parameters.

---

# 33. 2D Renderer Architecture

2D rendering should consume the same domain objects.

```text
Project Model
     ↓
2D Projection
     ↓
SVG/Canvas Renderer
```

This prevents the 2D and 3D views from becoming separate systems.

---

# 34. 2D/3D Synchronization

When a domain object changes:

```text
Domain Object Changed
       ↓
State Updated
       ↓
2D Renderer Refresh
       ↓
3D Renderer Refresh
```

No manual synchronization between separate databases should exist.

---

# 35. Furniture Engine

Furniture architecture:

```text
Furniture Template
      ↓
Parameters
      ↓
Rules
      ↓
Component Generator
      ↓
Geometry
      ↓
Manufacturing Decomposition
```

A furniture template should define:

- supported parameters
- minimum/maximum values
- default values
- components
- formulas
- materials
- hardware
- manufacturing rules

---

# 36. Component Engine

Components should be reusable.

Examples:

```text
Side Panel
Top Panel
Bottom Panel
Shelf
Drawer
Shutter
Back Panel
Toe Kick
Partition
```

A component may be used across multiple furniture templates.

---

# 37. Material Engine

Materials should be domain entities.

Example:

```text
Material
 ├── Board
 ├── Laminate
 ├── Edge Band
 ├── Hardware
 └── Profile
```

Material data must be independent of the UI.

---

# 38. BOM Engine

BOM generation:

```text
Furniture
 ↓
Components
 ↓
Materials
 ↓
Quantity Calculation
 ↓
BOM
```

BOM generation should be deterministic.

Given the same:

- project revision
- furniture revision
- catalog revision

the same BOM should be generated.

---

# 39. BOQ Engine

BOQ should consume BOM/design information but remain commercially configurable.

```text
BOM
 ↓
Pricing Rules
 ↓
Commercial Mapping
 ↓
BOQ
```

---

# 40. Pricing Engine

Pricing should support:

```text
Raw Material Pricing
Panel Pricing
Unit Pricing
Labour
Installation
Overhead
Markup
Discount
Tax
```

Pricing rules should be tenant-configurable.

Do not hard-code currency or rates into furniture templates.

---

# 41. Manufacturing Engine

Manufacturing engine responsibilities:

- Panel decomposition
- Cutlist
- Edge banding
- Hardware
- Drilling
- Routing
- Grain direction
- Panel validation
- Manufacturing constraints

The engine should not depend on UI components.

---

# 42. Panel Model

A panel should contain:

```text
id
furniture_id
component_id
material_id
thickness
length
width
quantity
grain_direction
edge_top
edge_bottom
edge_left
edge_right
drilling_data
routing_data
status
```

---

# 43. Nesting Engine

Nesting engine input:

```text
Panel List
Sheet List
Machine Constraints
Cutting Rules
```

Output:

```text
Nesting Job
 ├── Sheet 1
 │    ├── Panel A
 │    └── Panel B
 ├── Sheet 2
 │    ├── Panel C
 │    └── Panel D
 └── Waste Data
```

The nesting engine should be replaceable.

Create an interface such as:

```text
NestingEngineInterface
```

so future algorithms can be introduced without changing the application layer.

---

# 44. CNC Architecture

Create:

```text
CNCGeneratorInterface
```

Potential implementations:

```text
DXFGenerator
CSVGenerator
BiesseAdapter
HomagAdapter
KDTAdapter
GenericCNCAdapter
```

Core manufacturing data must remain manufacturer-neutral.

---

# 45. MES Architecture

MES should consume released manufacturing jobs.

MES must NOT directly modify design geometry.

Flow:

```text
Design
 ↓
Engineering
 ↓
Manufacturing Release
 ↓
Manufacturing Job
 ↓
MES
```

---

# 46. Production State Machine

Production jobs should use controlled states.

Example:

```text
PLANNED
 ↓
READY
 ↓
CUTTING
 ↓
EDGE_BANDING
 ↓
DRILLING
 ↓
ROUTING
 ↓
ASSEMBLY
 ↓
QC
 ↓
PACKING
 ↓
DISPATCHED
 ↓
COMPLETED
```

Invalid transitions should be rejected.

---

# 47. QR Tracking Architecture

QR code contains a secure identifier.

Example:

```text
panel/{public_identifier}
```

Scanning should resolve the panel through an authenticated API.

Do not put sensitive business data directly into QR codes.

---

# 48. Document Generation Architecture

Document generation should be isolated.

```text
Project Data
     ↓
Document Template
     ↓
Renderer
     ↓
PDF/File
```

Templates should be tenant-aware.

Possible outputs:

- Proposal
- Quotation
- Floor plan
- Elevation
- BOM
- Cutlist
- Production report
- Panel label

---

# 49. File Storage Architecture

Create an abstraction:

```text
StorageInterface
```

Possible implementations:

```text
LocalStorage
ObjectStorage
S3CompatibleStorage
```

This allows migration from local server storage to cloud storage later.

---

# 50. Background Jobs

Long-running tasks should NOT block normal HTTP requests.

Potential background jobs:

- BOM generation
- Large nesting calculations
- CNC generation
- PDF generation
- Large imports
- Image processing
- AI floorplan processing
- catalog import

Initial implementation may use a database-backed job queue or equivalent.

---

# 51. Web Workers

Browser-side heavy calculations should use Web Workers where appropriate.

Examples:

- geometry calculations
- large scene processing
- client-side nesting previews
- large catalog filtering
- image preprocessing

The UI thread should remain responsive.

---

# 52. Caching

Potential cache layers:

```text
Browser cache
IndexedDB
Application cache
Database query cache
```

Cache:

- material catalogs
- furniture templates
- permissions
- tenant configuration

Do not cache mutable manufacturing state without proper invalidation.

---

# 53. Search Architecture

For initial scale, MySQL indexing/search is sufficient.

Create indexes for:

- tenant_id
- project_id
- client_id
- room_id
- furniture_id
- material_id
- status
- created_at
- updated_at

A dedicated search engine may be introduced later.

---

# 54. Database Indexing Principles

Every frequently filtered foreign key should have an index.

Composite indexes should be used for common tenant-scoped queries.

Example:

```text
INDEX(tenant_id, project_id)
INDEX(tenant_id, status)
INDEX(tenant_id, created_at)
```

---

# 55. Audit Log Architecture

Create an append-only audit log.

Example:

```text
audit_logs
- id
- tenant_id
- user_id
- entity_type
- entity_id
- action
- before_data
- after_data
- timestamp
```

Sensitive values should not be logged unnecessarily.

---

# 56. Security Architecture

Required:

- Password hashing
- Secure sessions
- CSRF protection
- Input validation
- Output encoding
- SQL parameterization
- Authorization middleware
- Tenant isolation
- File upload validation
- MIME validation
- File size limits
- Rate limiting for sensitive endpoints
- Secure secrets configuration

Never place secrets in frontend JavaScript.

---

# 57. API Authentication

Authentication architecture should support:

- Login
- Logout
- Session/token validation
- Password reset
- Session expiration
- Permission evaluation

The frontend must never determine whether a user is authorized to perform a business action.

The backend must enforce it.

---

# 58. File Security

Uploaded:

- CAD files
- images
- PDFs
- 3D assets
- CNC files

must be validated.

Do not execute uploaded files.

Use generated storage names rather than trusting original filenames.

---

# 59. Logging

Application logs should include:

```text
timestamp
request ID
tenant ID
user ID where available
module
operation
severity
message
exception
```

Avoid logging passwords, tokens or sensitive client data.

---

# 60. Observability

At minimum track:

- API errors
- slow requests
- background job failures
- database failures
- manufacturing generation failures
- file generation failures

Future:

- metrics
- tracing
- centralized log aggregation

---

# 61. Configuration Management

Configuration should be environment-based.

Examples:

```text
APP_ENV
APP_URL
DB_HOST
DB_NAME
DB_USER
DB_PASSWORD
STORAGE_PATH
QUEUE_DRIVER
MAIL_HOST
```

Secrets must not be committed to Git.

Provide:

```text
.env.example
```

but never commit real credentials.

---

# 62. Database Migration Strategy

Use versioned migrations.

Example:

```text
001_create_users
002_create_tenants
003_create_projects
004_create_rooms
005_create_materials
...
```

Database structure must not depend on manually editing production tables.

---

# 63. Seed Data

Create seed data for:

- system roles
- permissions
- basic material categories
- furniture categories
- sample templates
- default statuses

Production seed data must be clearly separated from demo/test data.

---

# 64. Testing Architecture

At minimum:

## Unit Tests

For:

- pricing
- formulas
- parametric calculations
- panel calculations
- BOM
- BOQ
- nesting
- state transitions

## Integration Tests

For:

- API
- database
- authentication
- tenant isolation

## UI Tests

For:

- project creation
- drawing
- furniture creation
- material changes
- manufacturing generation

---

# 65. Critical Calculation Tests

The following must have deterministic automated tests:

```text
Wardrobe dimension calculations
Shelf dimensions
Panel dimensions
Edge banding
BOM quantities
Pricing
Tax
Nesting calculations
Manufacturing state transitions
```

Do not rely only on manual testing for manufacturing calculations.

---

# 66. Geometry Testing

Geometry tests should validate:

- dimensions
- bounding boxes
- intersections
- wall thickness
- openings
- panel sizes
- transformations

Use tolerances for floating-point comparisons.

---

# 67. API Versioning

Use:

```text
/api/v1
```

Do not break existing APIs without a versioning strategy.

Future:

```text
/api/v2
```

may coexist during migration.

---

# 68. Backward Compatibility

Catalog and parametric templates may evolve.

Old projects must continue to render correctly.

A project should reference the relevant:

- template version
- material revision
- pricing version
- manufacturing rules version

---

# 69. Template Versioning

Furniture templates should be versioned.

Example:

```text
Wardrobe Template
v1
v2
v3
```

An existing project using v1 should not unexpectedly change because the global template was modified.

---

# 70. Material Versioning

Material properties affecting manufacturing/pricing should be versioned or snapshotted.

Example:

```text
MDF-18
Cost = ₹X
Sheet = 2440 × 1220
```

If the supplier later changes pricing, historical quotations must remain reproducible.

---

# 71. Pricing Versioning

Every quotation should reference a pricing snapshot/version.

Historical quotation:

```text
Quotation Q001
Pricing Version = PV-2026-08
```

must remain reproducible even if current rates change.

---

# 72. Manufacturing Reproducibility

A manufacturing job should be reproducible from:

```text
Project Revision
+
Furniture Revision
+
Template Version
+
Material Version
+
Manufacturing Rules Version
```

This is a critical enterprise requirement.

---

# 73. Data Ownership

Recommended ownership:

```text
Tenant owns:
Projects
Clients
Catalog configuration
Pricing
Branding

Platform owns:
System definitions
System permissions
Core engines
System templates
```

Tenant-specific extensions should never modify global system definitions directly.

---

# 74. API Domain Boundaries

Recommended API domains:

```text
/auth
/tenants
/users
/roles
/clients
/projects
/buildings
/floors
/rooms
/design
/furniture
/components
/materials
/bom
/boq
/pricing
/manufacturing
/nesting
/cnc
/production
/qc
/packing
/dispatch
/documents
```

---

# 75. Example End-to-End API Flow

## Create Furniture

```text
POST /api/v1/furniture
```

Then:

```text
POST /api/v1/furniture/{id}/validate
```

Then:

```text
POST /api/v1/furniture/{id}/generate-bom
```

Then:

```text
POST /api/v1/furniture/{id}/generate-boq
```

Then:

```text
POST /api/v1/furniture/{id}/manufacturing-preview
```

Then:

```text
POST /api/v1/manufacturing/jobs
```

Then:

```text
POST /api/v1/manufacturing/jobs/{id}/release
```

Then:

```text
GET /api/v1/production/jobs
```

---

# 76. Manufacturing Release API Rule

Release endpoint MUST verify:

- Design approved
- Required dimensions valid
- Required materials available
- Furniture rules valid
- BOM generated
- Cutlist valid
- Manufacturing constraints valid
- No unresolved blocking errors

Only then:

```text
status = RELEASED
```

---

# 77. Validation Levels

Use:

```text
INFO
WARNING
ERROR
BLOCKER
```

Example:

```text
WARNING:
Shelf is close to maximum span.

ERROR:
Material not assigned.

BLOCKER:
Panel dimension exceeds available sheet size.
```

Manufacturing release must reject unresolved BLOCKER errors.

---

# 78. Domain Error Model

Errors should be structured.

Example:

```json
{
  "code": "PANEL_EXCEEDS_SHEET",
  "severity": "BLOCKER",
  "entity": "PANEL-001",
  "message": "Panel width exceeds available sheet width"
}
```

This allows the UI to display meaningful engineering errors.

---

# 79. Multi-User Collaboration Direction

Initial version may use optimistic concurrency rather than full real-time collaboration.

Future capability:

```text
Designer A
       │
Designer B
       │
       ▼
Shared Project State
```

Architecture should not prevent future WebSocket/event-based collaboration.

---

# 80. Performance Targets

Initial target:

- Normal API response: under 500 ms where practical
- Simple UI interactions: under 100 ms perceived response
- No long-running synchronous request for heavy jobs
- 2D editor remains responsive for typical projects
- 3D scene should degrade gracefully with complexity
- Heavy computations should use background jobs/Web Workers

These are engineering targets, not guarantees for every workload.

---

# 81. Scalability

The architecture should initially support:

- multiple tenants
- thousands of projects
- large material catalogs
- hundreds of design objects per room
- hundreds/thousands of panels per manufacturing job

Scale horizontally later by separating:

- web servers
- job workers
- file storage
- computational engines

---

# 82. Deployment Architecture — Initial

Recommended:

```text
Internet
   ↓
HTTPS
   ↓
Nginx/Apache
   ↓
PHP Application
   │
   ├── MySQL
   ├── File Storage
   └── Queue Worker
```

Frontend static assets may be served by the same web server initially.

---

# 83. Deployment Architecture — Future

```text
                  Load Balancer
                       │
             ┌─────────┴─────────┐
             │                   │
          Web Node 1          Web Node 2
             │                   │
             └─────────┬─────────┘
                       │
                Application Layer
                       │
         ┌─────────────┼─────────────┐
         │             │             │
       MySQL         Cache         Queue
                                     │
                         ┌───────────┼───────────┐
                         │           │           │
                     Worker 1    Worker 2    Worker 3
```

---

# 84. Disaster Recovery Direction

Future production environment should support:

- database backups
- file backups
- backup verification
- point-in-time recovery where available
- restore testing
- retention policy

A backup that has never been restored/tested should not be considered a verified backup.

---

# 85. Data Export

The platform should support export of tenant-owned business data.

Examples:

- Projects
- BOM
- BOQ
- Materials
- Furniture definitions
- Manufacturing data
- Production records

Avoid vendor lock-in.

---

# 86. Import Architecture

Future import support:

- CSV
- Excel
- DXF
- Images
- 3D formats
- Supplier catalogs

All imports should run through validation pipelines.

Example:

```text
Upload
 ↓
Parse
 ↓
Validate
 ↓
Preview
 ↓
Confirm
 ↓
Import
 ↓
Audit
```

---

# 87. Catalog Import Engine

Catalog imports may contain:

- board catalogs
- laminate catalogs
- hardware
- pricing
- images

Imports must not overwrite production data blindly.

Use:

```text
Import
 ↓
Validation
 ↓
Staging
 ↓
Approval
 ↓
Publish
```

---

# 88. AI Integration Architecture

AI services should be accessed through an abstraction.

Example:

```text
AIServiceInterface
```

Potential implementations:

```text
FloorplanRecognitionService
ImageTo3DService
DesignAssistantService
```

The core PHP application should not contain provider-specific AI logic everywhere.

---

# 89. AI Floorplan Pipeline

Future architecture:

```text
Image Upload
      ↓
Storage
      ↓
AI Processing Job
      ↓
Detection Result
      ↓
Geometry Validation
      ↓
Human Review
      ↓
Parametric Objects
      ↓
Project Model
```

AI output should NEVER directly become production-ready manufacturing data without validation.

---

# 90. External Integration Architecture

External systems should be accessed through adapters.

Examples:

```text
EmailProviderInterface
StorageInterface
CNCGeneratorInterface
AIProviderInterface
PaymentProviderInterface
```

This prevents vendor lock-in.

---

# 91. Dependency Injection

Services should receive dependencies rather than creating everything internally.

Avoid:

```php
$db = new Database();
```

inside every service.

Prefer a controlled dependency/container pattern appropriate to the project size.

---

# 92. Repository Pattern

Database access should be isolated.

Example:

```text
ProjectRepository
FurnitureRepository
MaterialRepository
BOMRepository
ManufacturingRepository
ProductionRepository
```

Controllers should not contain raw SQL.

---

# 93. Service Pattern

Business operations should be exposed through services.

Examples:

```text
ProjectService
FurnitureService
PricingService
BOMService
ManufacturingService
ProductionService
```

---

# 94. Controller Responsibilities

Controllers should:

1. Receive request.
2. Authenticate.
3. Authorize.
4. Validate request.
5. Call application service.
6. Return standardized response.

Controllers should NOT implement business calculations.

---

# 95. Frontend API Client

Create a common API client.

Example responsibilities:

- Base URL
- Authentication
- headers
- JSON serialization
- errors
- retries where appropriate
- request ID
- session handling

Feature modules should use the client rather than direct `fetch()` calls everywhere.

---

# 96. UI Component Architecture

Reusable UI components:

```text
Modal
Dialog
Dropdown
Tabs
DataTable
PropertyPanel
Toolbar
ContextMenu
Toast
Form
TreeView
CommandPalette
```

CAD-specific:

```text
Canvas
Ruler
Grid
SnapController
SelectionController
TransformController
```

---

# 97. Selection Model

The design workspace should have a central selection model.

Example:

```text
selectedObjectIds[]
activeObjectId
selectionMode
```

2D and 3D views should share selection state.

Selecting an object in 2D should highlight it in 3D and vice versa.

---

# 98. Coordinate System

Define a single canonical coordinate system.

Recommended:

```text
Unit: millimeter
```

Use a documented world coordinate system.

Example:

```text
X = width
Y = depth
Z = height
```

Three.js coordinate conversion should happen at the renderer boundary if required.

Never mix units without explicit conversion.

---

# 99. Units

Internal geometry calculations should use a canonical unit.

Recommended:

```text
millimeter
```

Display may support:

- mm
- cm
- meter
- feet/inches where required

Conversion must be centralized.

---

# 100. Precision

Define precision rules.

Example:

```text
Internal geometry:
floating-point

Displayed dimensions:
1 mm precision by default

Pricing:
2 decimal places

Area:
configured precision
```

Avoid repeated rounding during calculations.

Round only at presentation/output boundaries unless a manufacturing rule explicitly requires rounding.

---

# 101. Geometry Serialization

Do not serialize Three.js scene objects directly.

Store domain geometry.

Example:

```json
{
  "type": "panel",
  "position": {
    "x": 100,
    "y": 200,
    "z": 300
  },
  "size": {
    "length": 2400,
    "width": 600,
    "thickness": 18
  },
  "rotation": {
    "x": 0,
    "y": 0,
    "z": 0
  }
}
```

---

# 102. Object IDs

Use stable unique IDs.

Recommended:

```text
Project: PRJ-XXXX
Room: ROOM-XXXX
Furniture: FUR-XXXX
Component: CMP-XXXX
Panel: PNL-XXXX
Production Job: JOB-XXXX
```

Database primary keys may be numeric/UUID, while business IDs can be human-readable.

---

# 103. Business ID vs Database ID

Do not expose internal numeric IDs as business identifiers where avoidable.

Example:

```text
database id = 92831

business id = WARD-00045
```

This improves readability and future integrations.

---

# 104. API Idempotency

Operations that may be retried should be idempotent.

Especially:

- Generate BOM
- Generate BOQ
- Generate manufacturing job
- Release manufacturing
- Generate CNC
- Create production job

Use idempotency keys where appropriate.

---

# 105. Job Processing

Heavy tasks should return a job reference.

Example:

```text
POST /manufacturing/generate
```

Response:

```json
{
  "success": true,
  "job_id": "MFGJOB-001",
  "status": "QUEUED"
}
```

Client can query:

```text
GET /manufacturing/jobs/MFGJOB-001
```

---

# 106. Notifications

Architecture should support notifications for:

- manufacturing generation complete
- CNC generation complete
- job failure
- approval requested
- QC failed
- production completed

Initial implementation may use in-app notifications and email later.

---

# 107. Feature Flags

Use feature flags for risky/experimental functionality.

Examples:

```text
enable_ai_floorplan
enable_cnc_biesse
enable_advanced_nesting
enable_realtime_collaboration
```

This allows controlled rollout.

---

# 108. Tenant Feature Configuration

Tenant-level feature configuration may eventually include:

```text
2D Design
3D Design
Furniture Designer
Pricing
Manufacturing
MES
AI
CNC
```

Do not hard-code subscription/feature checks throughout the UI.

Centralize feature authorization.

---

# 109. Subscription Architecture Direction

Future plans may include:

```text
Free
Professional
Business
Enterprise
```

Potential limits:

- users
- projects
- storage
- manufacturing jobs
- AI usage
- catalogs

Subscription logic should remain separate from core domain rules.

---

# 110. White-Label Architecture

Tenant configuration should be resolved at request time:

```text
Domain
 ↓
Tenant Resolver
 ↓
Tenant Configuration
 ↓
Branding
 ↓
Application
```

This should allow:

```text
customer-a.com
customer-b.com
platform.com
```

to use the same application.

---

# 111. Email Architecture

Create:

```text
EmailService
EmailTemplateRepository
```

Templates should support:

- tenant branding
- variables
- subject
- HTML
- text fallback

Do not hard-code email HTML inside controllers.

---

# 112. Document Template Architecture

Templates should be data-driven.

Example:

```text
Proposal Template
 ├── Header
 ├── Client
 ├── Project
 ├── Design
 ├── Materials
 ├── BOQ
 ├── Price
 └── Terms
```

Tenant-specific templates should be versioned.

---

# 113. Audit + Revision Relationship

Audit log answers:

> Who changed what?

Revision answers:

> What version existed?

Both must exist.

Do not attempt to replace revision management with audit logs.

---

# 114. Data Lifecycle

Typical data lifecycle:

```text
Draft
 ↓
Validated
 ↓
Approved
 ↓
Released
 ↓
Archived
```

Deletion should be controlled for business-critical records.

Use soft delete where appropriate.

---

# 115. Soft Delete

Potential entities:

- users
- materials
- furniture templates
- projects
- clients

Use:

```text
deleted_at
```

where historical references must remain valid.

Do not physically delete data referenced by released manufacturing records.

---

# 116. Referential Integrity

Use foreign keys for critical relational data.

Examples:

```text
project → tenant
room → project
furniture → room
panel → furniture
production_job → manufacturing_job
```

---

# 117. Manufacturing Data Retention

Released manufacturing records should remain available for historical traceability.

Even if a furniture template is later deleted/deactivated, historical manufacturing jobs must remain readable.

---

# 118. Security Boundary for Manufacturing

Production users should not be able to alter design parameters unless their permissions explicitly allow it.

Factory users should generally operate on released manufacturing data.

---

# 119. Design Approval Boundary

Client approval and internal engineering approval should be separate states where required.

Example:

```text
Designer Complete
 ↓
Internal Review
 ↓
Client Approval
 ↓
Engineering Validation
 ↓
Manufacturing Release
```

---

# 120. API Authorization Examples

A designer may:

```text
design.view
design.edit
furniture.create
furniture.edit
```

but not:

```text
manufacturing.release
```

A production manager may:

```text
manufacturing.view
manufacturing.release
production.update
```

but not necessarily:

```text
pricing.edit
```

---

# 121. Data Access Rules

Authorization should consider:

```text
Tenant
Organization
Project
Role
Permission
Resource ownership/membership
```

Example:

A user may have `project.view` but only for projects where they are a member.

---

# 122. Architecture Documentation Requirements

The repository should contain:

```text
/docs
    /architecture
    /api
    /database
    /domain
    /manufacturing
    /frontend
    /deployment
```

At minimum:

```text
architecture-overview.md
domain-model.md
database-schema.md
api-overview.md
manufacturing-engine.md
frontend-architecture.md
deployment.md
```

---

# 123. Code Documentation

Document:

- complex geometry formulas
- manufacturing rules
- nesting algorithm
- CNC transformations
- pricing formulas
- revision logic
- state transitions

Do not document obvious code excessively.

Document business rules and non-obvious decisions.

---

# 124. Architecture Decision Records

Create ADRs for major decisions.

Example:

```text
ADR-001 Modular Monolith
ADR-002 Three.js Rendering
ADR-003 MySQL + JSON Parametric Data
ADR-004 Manufacturing Snapshot
ADR-005 Nesting Interface
ADR-006 CNC Adapter Architecture
ADR-007 Multi-Tenant Strategy
```

---

# 125. Git Architecture

Recommended:

```text
main
develop
feature/*
bugfix/*
release/*
```

Every major module should be committed independently.

Commit messages should identify intent.

---

# 126. CI/CD Direction

Pipeline:

```text
Git Push
 ↓
Lint
 ↓
Unit Tests
 ↓
Integration Tests
 ↓
Build/Asset Validation
 ↓
Security Checks
 ↓
Deploy to Staging
 ↓
Smoke Tests
 ↓
Production Approval
```

---

# 127. Environment Separation

At minimum:

```text
Development
Staging
Production
```

Never use production database credentials in development.

---

# 128. Staging Requirements

Staging should include:

- separate database
- separate file storage
- test email configuration
- test tenant
- demo materials
- demo projects
- representative manufacturing data

---

# 129. Backup Architecture

At minimum:

```text
Daily DB backup
Regular file backup
Retention policy
Restore test
```

Production backup credentials must be isolated.

---

# 130. Initial Architecture Deliverables Cursor Must Produce

Before implementing major features, Cursor should create:

1. Repository architecture map
2. Domain architecture map
3. Database ERD
4. API map
5. Frontend module map
6. Dependency map
7. Event map
8. Manufacturing data flow
9. Revision flow
10. Deployment architecture
11. Security boundary map
12. Test architecture

---

# 131. Cursor Initial Analysis Instructions

When this document is provided to Cursor, the first task MUST be analysis, not coding.

Cursor should:

### Step 1
Inspect repository structure.

### Step 2
Identify:

- PHP version
- MySQL usage
- JS architecture
- existing Three.js
- existing APIs
- existing database tables
- authentication
- RBAC
- current design engine
- current furniture engine
- current manufacturing engine

### Step 3
Produce:

```text
CURRENT ARCHITECTURE
TARGET ARCHITECTURE
GAP ANALYSIS
MIGRATION PLAN
RISKS
DEPENDENCIES
```

### Step 4
Do not delete or rewrite existing code without approval.

---

# 132. Cursor Implementation Rules

Cursor MUST:

- preserve existing working features
- follow domain boundaries
- use reusable services
- avoid duplicate logic
- add tests for business calculations
- maintain tenant isolation
- use migrations
- use parameterized SQL
- implement backend authorization
- document architectural changes
- maintain backward compatibility where practical

---

# 133. Cursor Prohibited Patterns

Cursor MUST NOT:

- create giant `app.js`
- create giant controller files
- put SQL inside frontend
- put business rules inside HTML
- make Three.js the source of truth
- duplicate pricing formulas
- duplicate BOM logic
- duplicate manufacturing calculations
- hard-code tenant-specific values
- hard-code machine-specific CNC logic
- silently change released manufacturing data
- bypass authorization
- store secrets in source code
- introduce unnecessary dependencies
- delete existing functionality without approval

---

# 134. Architecture Acceptance Criteria

The architecture is acceptable only if:

- [ ] Modules have clear boundaries
- [ ] Tenant isolation is enforced
- [ ] Authentication is centralized
- [ ] RBAC is centralized
- [ ] API versioning exists
- [ ] Database migrations exist
- [ ] Frontend is modular
- [ ] 2D and 3D consume common domain data
- [ ] Three.js is not the source of truth
- [ ] Parametric rules are centralized
- [ ] BOM is generated from the model
- [ ] BOQ is generated from commercial rules
- [ ] Manufacturing data is derived from the model
- [ ] Manufacturing release creates a snapshot
- [ ] Revision management exists
- [ ] Heavy operations support asynchronous processing
- [ ] CNC adapters are isolated
- [ ] Nesting engine is replaceable
- [ ] Audit logs exist
- [ ] Tests exist for critical calculations
- [ ] Environment configuration is separated
- [ ] Production secrets are protected

---

# 135. Recommended Initial Build Order

## Phase 1 — Foundation

```text
Configuration
Database
Migrations
Authentication
Tenant
RBAC
API
Logging
Audit
```

## Phase 2 — Project Model

```text
Clients
Projects
Buildings
Floors
Rooms
Design Objects
Revision
```

## Phase 3 — Design Core

```text
2D Model
2D Renderer
3D Model
Three.js Renderer
Selection
Commands
Undo/Redo
```

## Phase 4 — Parametric Furniture

```text
Templates
Parameters
Rules
Components
Geometry
Materials
```

## Phase 5 — Commercial

```text
BOM
BOQ
Pricing
Quotation
```

## Phase 6 — Manufacturing

```text
Panel Engine
Cutlist
Edge Band
Hardware
Validation
Nesting
```

## Phase 7 — CNC

```text
CNC Interface
DXF
CSV
Machine Adapters
```

## Phase 8 — MES

```text
Manufacturing Jobs
Production
QR
QC
Packing
Dispatch
```

## Phase 9 — Advanced

```text
AI
White Label
Custom Domains
Advanced Collaboration
Analytics
```

---

# 136. Recommended First Technical Milestone

Do not begin by building the complete 2D editor.

First establish:

```text
Tenant
 ↓
Project
 ↓
Room
 ↓
Design Object
 ↓
Furniture Instance
 ↓
Parametric Parameters
 ↓
Revision
```

Then prove that one object can flow through:

```text
Parameter
 ↓
Geometry
 ↓
2D
 ↓
3D
 ↓
BOM
 ↓
BOQ
 ↓
Panel
```

If this vertical slice works correctly, the rest of the platform can be built on top of it.

---

# 137. Critical Vertical Slice

The first technical proof-of-concept should be a:

## Parametric Wardrobe

Input:

```text
Width
Height
Depth
Carcass Thickness
Back Thickness
Shelf Count
Shutter Count
Material
```

System generates:

```text
2D Wardrobe
3D Wardrobe
Components
Panels
BOM
BOQ
Price
Cutlist
```

This should become the architectural reference implementation for all future furniture types.

---

# 138. Why This Vertical Slice Matters

If the wardrobe cannot reliably flow from:

```text
Parameter
→ Geometry
→ BOM
→ BOQ
→ Manufacturing
```

then adding more furniture categories will only multiply technical debt.

The first parametric furniture implementation should therefore be treated as an **architecture validation exercise**, not merely a feature.

---

# 139. Final Architecture Principle

The platform must be built around this hierarchy:

```text
USER
 ↓
WORKSPACE
 ↓
APPLICATION COMMAND
 ↓
DOMAIN MODEL
 ↓
PARAMETRIC / BUSINESS ENGINE
 ↓
EVENT
 ↓
DEPENDENT DOMAIN SERVICES
 ↓
PERSISTENCE
 ↓
DERIVED OUTPUTS
```

Not:

```text
USER
 ↓
UI
 ↓
SQL
```

---

# 140. Final Technical North Star

The final system should conceptually behave like this:

```text
                     PROJECT MODEL
                          │
             ┌────────────┼────────────┐
             │            │            │
        ARCHITECTURE   FURNITURE    MATERIALS
             │            │            │
             └────────────┼────────────┘
                          │
                   PARAMETRIC ENGINE
                          │
             ┌────────────┼────────────┐
             │            │            │
          GEOMETRY       BOM        PRICING
             │            │            │
          2D / 3D        BOQ          │
             │            │            │
          DRAWINGS    QUOTATION        │
             │            └──────┬─────┘
             │                   │
             └─────────────┬─────┘
                           │
                    MANUFACTURING
                           │
                 ┌─────────┼─────────┐
                 │         │         │
              CUTLIST   NESTING     CNC
                 │         │         │
                 └─────────┼─────────┘
                           │
                          MES
                           │
              Production / QC / Packing
                           │
                       Dispatch
```

The architecture's ultimate objective is therefore:

> **Maintain one authoritative digital representation of the project and derive every design, commercial, engineering and manufacturing output from that model.**

This is the core architectural moat of the platform.
