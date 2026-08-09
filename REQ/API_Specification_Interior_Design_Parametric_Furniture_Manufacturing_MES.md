# API Specification Document
## Interior Design, Parametric Furniture, Estimation, Manufacturing & MES Platform

**Document ID:** API-IDFM-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Backend Developers, Frontend Developers, QA, DevOps  
**Backend:** PHP 8.x  
**Database:** MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API Style:** REST/JSON  
**Base Path:** `/api/v1`  
**Primary Unit:** millimeter (mm)  
**Date:** 2026-08-09

---

# 1. Purpose

This document defines the complete REST API contract for the end-to-end Interior Design, Parametric Furniture, Estimation, Manufacturing and MES platform.

The API MUST support:

- multi-tenancy
- authentication
- RBAC
- CRM
- projects
- architectural design
- 2D/3D design data
- parametric furniture
- component designer
- material catalogs
- BOM/BOQ
- pricing
- quotations
- proposals
- revisions
- approvals
- engineering validation
- manufacturing
- cutlists
- nesting
- CNC/CAM
- MES
- production
- QR tracking
- QC
- packing
- dispatch
- installation
- documents
- notifications
- background jobs
- AI-assisted floor-plan processing
- white-label configuration

The API MUST expose business capabilities, not database tables directly.

---

# 2. API Architecture Principles

## API-001 — REST Resource Orientation

Use nouns:

```text
/projects
/rooms
/furniture
/materials
/quotations
/manufacturing-revisions
/production-jobs
```

Avoid RPC-style endpoints such as:

```text
/getProject
/createProject
/doManufacturing
```

Business commands MAY use action endpoints where a state transition is required:

```text
POST /manufacturing-revisions/{id}/release
POST /production-jobs/{id}/start
POST /production-jobs/{id}/complete
POST /quotations/{id}/approve
```

## API-002 — Versioning

All public APIs MUST begin with:

```text
/api/v1
```

Breaking changes require:

```text
/api/v2
```

## API-003 — JSON

Requests and responses use:

```http
Content-Type: application/json
```

unless the endpoint explicitly handles file upload.

## API-004 — Tenant Isolation

Tenant context MUST come from authenticated identity/session/token.

Never trust:

```json
{
  "tenant_id": 123
}
```

from an untrusted client.

## API-005 — Authorization

Every protected endpoint MUST verify:

1. authentication
2. tenant scope
3. permission
4. resource ownership/access
5. workflow state where applicable

## API-006 — Database Independence

Controllers MUST NOT directly contain SQL.

Recommended:

```text
HTTP Request
 ↓
Router
 ↓
Middleware
 ↓
Controller
 ↓
Application Service
 ↓
Domain Service
 ↓
Repository
 ↓
MySQL
```

---

# 3. Base URL

Development:

```text
http://localhost/api/v1
```

Production:

```text
https://<tenant-domain>/api/v1
```

The exact production hostname is environment-specific.

---

# 4. Authentication

Recommended:

```http
Authorization: Bearer <access_token>
```

For session-cookie implementations, use secure:

```text
HttpOnly
Secure
SameSite
```

cookies.

---

# 5. Authentication Endpoints

## 5.1 Login

```http
POST /auth/login
```

Request:

```json
{
  "email": "designer@example.com",
  "password": "********"
}
```

Response:

```json
{
  "data": {
    "user": {
      "id": "01J...",
      "name": "Designer",
      "email": "designer@example.com"
    },
    "access_token": "TOKEN",
    "expires_in": 3600
  }
}
```

Errors:

```text
AUTH_INVALID_CREDENTIALS
AUTH_ACCOUNT_DISABLED
AUTH_ACCOUNT_LOCKED
```

## 5.2 Logout

```http
POST /auth/logout
```

## 5.3 Current User

```http
GET /auth/me
```

## 5.4 Refresh Token

```http
POST /auth/refresh
```

## 5.5 Change Password

```http
POST /auth/change-password
```

## 5.6 Forgot Password

```http
POST /auth/forgot-password
```

## 5.7 Reset Password

```http
POST /auth/reset-password
```

---

# 6. Standard Headers

Recommended:

```http
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
X-Request-ID: <uuid>
X-Client-Version: <version>
X-Timezone: Asia/Kolkata
```

For idempotent operations:

```http
Idempotency-Key: <unique-key>
```

---

# 7. Standard Response Envelope

Successful response:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Collection:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 125,
    "total_pages": 3
  }
}
```

Error:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "One or more fields are invalid.",
    "details": [
      {
        "field": "width_mm",
        "code": "MIN_VALUE",
        "message": "Width must be at least 300 mm."
      }
    ]
  },
  "meta": {
    "request_id": "..."
  }
}
```

---

# 8. HTTP Status Codes

Use:

```text
200 OK
201 Created
202 Accepted
204 No Content
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
409 Conflict
422 Unprocessable Entity
429 Too Many Requests
500 Internal Server Error
502 Bad Gateway
503 Service Unavailable
```

---

# 9. Standard Error Codes

Authentication:

```text
AUTH_INVALID_CREDENTIALS
AUTH_TOKEN_EXPIRED
AUTH_TOKEN_INVALID
AUTH_ACCOUNT_DISABLED
AUTH_ACCOUNT_LOCKED
```

Authorization:

```text
FORBIDDEN
PERMISSION_DENIED
TENANT_ACCESS_DENIED
RESOURCE_ACCESS_DENIED
```

Validation:

```text
VALIDATION_ERROR
REQUIRED_FIELD
INVALID_TYPE
INVALID_FORMAT
INVALID_VALUE
MIN_VALUE
MAX_VALUE
INVALID_ENUM
```

Resource:

```text
NOT_FOUND
DUPLICATE_RESOURCE
RESOURCE_LOCKED
RESOURCE_ARCHIVED
```

Concurrency:

```text
REVISION_CONFLICT
STALE_DATA
VERSION_CONFLICT
```

Workflow:

```text
INVALID_STATE
INVALID_TRANSITION
APPROVAL_REQUIRED
ENGINEERING_VALIDATION_REQUIRED
MANUFACTURING_RELEASE_REQUIRED
```

Manufacturing:

```text
MANUFACTURING_BLOCKED
MATERIAL_MISSING
PANEL_GENERATION_FAILED
NESTING_FAILED
CNC_GENERATION_FAILED
```

System:

```text
INTERNAL_ERROR
SERVICE_UNAVAILABLE
JOB_FAILED
```

---

# 10. Pagination

Default:

```text
?page=1&per_page=50
```

Maximum:

```text
per_page=200
```

Response:

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 1000,
    "total_pages": 20
  }
}
```

Large manufacturing datasets SHOULD support cursor pagination in addition to page pagination.

---

# 11. Filtering

Example:

```http
GET /projects?status=DESIGN&client_id=01J...
```

Multiple values:

```http
GET /projects?status=DESIGN,ENGINEERING
```

Backend MUST validate allowed filter fields.

Do not allow arbitrary SQL expressions through query parameters.

---

# 12. Sorting

Example:

```http
GET /projects?sort=-created_at
```

Allowed sort fields MUST be explicitly whitelisted.

---

# 13. Search

Example:

```http
GET /materials?search=green
```

Search MUST be scoped to tenant.

---

# 14. Idempotency

Idempotency SHOULD be required for:

- quotation sending
- quotation approval
- manufacturing release
- production transition
- dispatch creation
- payment/commercial integrations
- file processing
- long-running job creation

Example:

```http
Idempotency-Key: 01JABC...
```

Repeated requests with same key MUST not create duplicate transactions.

---

# 15. API Resource Naming

Primary resources:

```text
/auth
/users
/roles
/permissions
/tenants
/leads
/clients
/opportunities
/projects
/buildings
/floors
/rooms
/design-objects
/design-layers
/project-revisions
/furniture-templates
/furniture-template-versions
/furniture-instances
/furniture-revisions
/furniture-components
/material-categories
/materials
/boards
/laminates
/edge-bands
/hardware
/catalog-imports
/pricing-rule-sets
/pricing-rules
/pricing-versions
/boms
/boqs
/pricing-calculations
/quotations
/proposals
/engineering-validations
/manufacturing-revisions
/manufacturing-components
/panels
/cutlists
/nesting-jobs
/cnc-machines
/cnc-jobs
/manufacturing-releases
/production-jobs
/workstations
/qr-codes
/qr-scans
/qc-inspections
/packages
/dispatches
/installation-jobs
/files
/documents
/notifications
/jobs
/ai-jobs
/approvals
/comments
/audit-logs
```

---

# 16. Tenant API

## GET `/tenant`

Return current tenant.

## PATCH `/tenant`

Update tenant profile.

Permission:

```text
tenant.manage
```

## GET `/tenant/settings`

## PATCH `/tenant/settings`

## GET `/tenant/branding`

## PATCH `/tenant/branding`

## GET `/tenant/domains`

## POST `/tenant/domains`

## DELETE `/tenant/domains/{id}`

Custom domain verification MUST be handled asynchronously if DNS verification is required.

---

# 17. User API

## GET `/users`

Filters:

```text
status
role
search
```

## POST `/users`

Request:

```json
{
  "first_name": "John",
  "last_name": "Designer",
  "email": "john@example.com",
  "phone": "+91...",
  "role_ids": ["01J..."]
}
```

## GET `/users/{id}`

## PATCH `/users/{id}`

## POST `/users/{id}/disable`

## POST `/users/{id}/enable`

## POST `/users/{id}/roles`

## DELETE `/users/{id}/roles/{role_id}`

---

# 18. Role API

## GET `/roles`

## POST `/roles`

```json
{
  "name": "Senior Designer",
  "description": "..."
}
```

## GET `/roles/{id}`

## PATCH `/roles/{id}`

## DELETE `/roles/{id}`

System roles MUST NOT be deleted.

## GET `/roles/{id}/permissions`

## PUT `/roles/{id}/permissions`

---

# 19. Permission API

## GET `/permissions`

Permissions are primarily system-managed.

Normal tenants SHOULD not create arbitrary permission keys.

---

# 20. CRM API

## Leads

```http
GET    /leads
POST   /leads
GET    /leads/{id}
PATCH  /leads/{id}
DELETE /leads/{id}
POST   /leads/{id}/convert
```

Conversion SHOULD optionally create:

```text
client
opportunity
project
```

## Clients

```http
GET    /clients
POST   /clients
GET    /clients/{id}
PATCH  /clients/{id}
DELETE /clients/{id}
GET    /clients/{id}/contacts
POST   /clients/{id}/contacts
PATCH  /clients/{id}/contacts/{contactId}
DELETE /clients/{id}/contacts/{contactId}
```

## Opportunities

```http
GET    /opportunities
POST   /opportunities
GET    /opportunities/{id}
PATCH  /opportunities/{id}
POST   /opportunities/{id}/convert
```

---

# 21. Project API

## GET `/projects`

Supported filters:

```text
status
client_id
owner_user_id
project_type
search
created_from
created_to
```

## POST `/projects`

Request:

```json
{
  "name": "Villa Interior",
  "client_id": "01J...",
  "project_type": "RESIDENTIAL",
  "address": {
    "line1": "...",
    "city": "...",
    "state": "...",
    "postal_code": "...",
    "country": "IN"
  }
}
```

Creates:

```text
project
initial project revision
```

inside a transaction.

## GET `/projects/{id}`

## PATCH `/projects/{id}`

## DELETE `/projects/{id}`

Deletion is soft-delete only where allowed.

## GET `/projects/{id}/summary`

Return:

```text
project status
rooms
furniture count
BOM status
BOQ status
quotation status
manufacturing status
production status
```

---

# 22. Project Team API

```http
GET    /projects/{id}/users
POST   /projects/{id}/users
DELETE /projects/{id}/users/{userId}
```

---

# 23. Building API

```http
GET    /projects/{projectId}/buildings
POST   /projects/{projectId}/buildings
GET    /buildings/{id}
PATCH  /buildings/{id}
DELETE /buildings/{id}
```

---

# 24. Floor API

```http
GET    /buildings/{buildingId}/floors
POST   /buildings/{buildingId}/floors
GET    /floors/{id}
PATCH  /floors/{id}
DELETE /floors/{id}
```

---

# 25. Room API

```http
GET    /floors/{floorId}/rooms
POST   /floors/{floorId}/rooms
GET    /rooms/{id}
PATCH  /rooms/{id}
DELETE /rooms/{id}
```

Room creation request:

```json
{
  "name": "Master Bedroom",
  "room_type": "BEDROOM",
  "width_mm": 4500,
  "depth_mm": 5000,
  "height_mm": 3000
}
```

---

# 26. Project Revision API

```http
GET  /projects/{projectId}/revisions
POST /projects/{projectId}/revisions
GET  /project-revisions/{id}
POST /project-revisions/{id}/duplicate
POST /project-revisions/{id}/approve
```

Creating a revision MUST NOT overwrite the previous revision.

---

# 27. Design Layer API

```http
GET    /projects/{projectId}/layers
POST   /projects/{projectId}/layers
GET    /design-layers/{id}
PATCH  /design-layers/{id}
DELETE /design-layers/{id}
```

---

# 28. Design Object API

```http
GET    /projects/{projectId}/design-objects
POST   /projects/{projectId}/design-objects
GET    /design-objects/{id}
PATCH  /design-objects/{id}
DELETE /design-objects/{id}
```

Request example:

```json
{
  "project_revision_id": "01J...",
  "room_id": "01J...",
  "object_type": "WALL",
  "geometry": {
    "start": {"x": 0, "y": 0},
    "end": {"x": 4500, "y": 0},
    "thickness_mm": 150,
    "height_mm": 3000
  }
}
```

The API MUST validate geometry.

---

# 29. Bulk Design Object API

For CAD-style interactions:

```http
POST /projects/{projectId}/design-objects/bulk
```

Request:

```json
{
  "revision_id": "01J...",
  "operations": [
    {
      "operation": "CREATE",
      "client_id": "local-1",
      "object": {}
    },
    {
      "operation": "UPDATE",
      "id": "01J...",
      "version": 4,
      "patch": {}
    }
  ]
}
```

Response MUST return per-operation success/failure.

This endpoint is important for reducing network calls during canvas editing.

---

# 30. Design Object Relations

```http
GET  /design-objects/{id}/relations
POST /design-objects/{id}/relations
DELETE /design-objects/{id}/relations/{relationId}
```

---

# 31. Design Snapshot API

```http
POST /projects/{projectId}/snapshots
GET  /projects/{projectId}/snapshots
GET  /design-snapshots/{id}
```

Autosave snapshots SHOULD use a background job or throttled request strategy.

---

# 32. Furniture Template API

```http
GET    /furniture-templates
POST   /furniture-templates
GET    /furniture-templates/{id}
PATCH  /furniture-templates/{id}
DELETE /furniture-templates/{id}
```

Create:

```json
{
  "category": "WARDROBE",
  "template_code": "WRD-3D-01",
  "name": "3 Door Wardrobe"
}
```

---

# 33. Furniture Template Version API

```http
GET  /furniture-templates/{id}/versions
POST /furniture-templates/{id}/versions
GET  /furniture-template-versions/{id}
PATCH /furniture-template-versions/{id}
POST /furniture-template-versions/{id}/publish
POST /furniture-template-versions/{id}/archive
```

Publishing creates an immutable usable template version.

---

# 34. Template Parameter API

```http
GET    /furniture-template-versions/{id}/parameters
POST   /furniture-template-versions/{id}/parameters
PATCH  /furniture-template-parameters/{id}
DELETE /furniture-template-parameters/{id}
```

---

# 35. Template Component API

```http
GET    /furniture-template-versions/{id}/components
POST   /furniture-template-versions/{id}/components
GET    /furniture-template-components/{id}
PATCH  /furniture-template-components/{id}
DELETE /furniture-template-components/{id}
```

---

# 36. Template Rule API

```http
GET    /furniture-template-versions/{id}/rules
POST   /furniture-template-versions/{id}/rules
PATCH  /furniture-template-rules/{id}
DELETE /furniture-template-rules/{id}
POST   /furniture-template-rules/{id}/validate
```

Rule validation MUST happen server-side.

---

# 37. Furniture Instance API

```http
GET    /rooms/{roomId}/furniture
POST   /rooms/{roomId}/furniture
GET    /furniture/{id}
PATCH  /furniture/{id}
DELETE /furniture/{id}
```

Create:

```json
{
  "template_id": "01J...",
  "template_version_id": "01J...",
  "name": "Master Wardrobe",
  "position": {
    "x": 1000,
    "y": 500,
    "z": 0
  },
  "parameters": {
    "width_mm": 2400,
    "height_mm": 2400,
    "depth_mm": 600,
    "shutter_count": 3
  }
}
```

---

# 38. Furniture Validation API

```http
POST /furniture/{id}/validate
```

Response:

```json
{
  "valid": false,
  "errors": [
    {
      "code": "MAX_WIDTH_EXCEEDED",
      "message": "Maximum module width is 1200 mm."
    }
  ],
  "warnings": []
}
```

---

# 39. Furniture Regeneration API

```http
POST /furniture/{id}/regenerate
```

Used after parameter changes.

Response MAY be:

```http
202 Accepted
```

for complex geometry/manufacturing calculations.

---

# 40. Furniture Revision API

```http
GET  /furniture/{id}/revisions
POST /furniture/{id}/revisions
GET  /furniture-revisions/{id}
POST /furniture-revisions/{id}/approve
```

A revision MUST capture:

```text
template version
parameters
components
geometry
materials
```

---

# 41. Furniture Component API

```http
GET    /furniture/{id}/components
GET    /furniture-components/{id}
PATCH  /furniture-components/{id}
```

Direct edits MUST respect template rules and revision status.

---

# 42. Material API

```http
GET    /materials
POST   /materials
GET    /materials/{id}
PATCH  /materials/{id}
DELETE /materials/{id}
```

Filters:

```text
material_type
brand
category
status
search
thickness
```

---

# 43. Board API

```http
GET    /boards
POST   /boards
GET    /boards/{id}
PATCH  /boards/{id}
DELETE /boards/{id}
```

---

# 44. Laminate API

```http
GET    /laminates
POST   /laminates
GET    /laminates/{id}
PATCH  /laminates/{id}
DELETE /laminates/{id}
```

---

# 45. Edge Band API

```http
GET    /edge-bands
POST   /edge-bands
GET    /edge-bands/{id}
PATCH  /edge-bands/{id}
DELETE /edge-bands/{id}
```

---

# 46. Hardware API

```http
GET    /hardware
POST   /hardware
GET    /hardware/{id}
PATCH  /hardware/{id}
DELETE /hardware/{id}
```

---

# 47. Catalog Import API

## Upload

```http
POST /catalog-imports
```

Multipart:

```text
file
import_type
```

Response:

```json
{
  "data": {
    "id": "01J...",
    "status": "UPLOADED"
  }
}
```

## Validate

```http
POST /catalog-imports/{id}/validate
```

## Preview

```http
GET /catalog-imports/{id}/preview
```

## Commit

```http
POST /catalog-imports/{id}/commit
```

Large imports SHOULD return `202 Accepted` and create a background job.

---

# 48. Pricing Rule API

```http
GET    /pricing-rule-sets
POST   /pricing-rule-sets
GET    /pricing-rule-sets/{id}
PATCH  /pricing-rule-sets/{id}
```

Rules:

```http
GET    /pricing-rule-sets/{id}/rules
POST   /pricing-rule-sets/{id}/rules
PATCH  /pricing-rules/{id}
DELETE /pricing-rules/{id}
```

---

# 49. Pricing Version API

```http
GET  /pricing-rule-sets/{id}/versions
POST /pricing-rule-sets/{id}/versions
POST /pricing-versions/{id}/publish
```

Published pricing versions MUST be immutable.

---

# 50. BOM API

## Generate

```http
POST /projects/{projectId}/boms/generate
```

Request:

```json
{
  "project_revision_id": "01J...",
  "furniture_ids": ["01J...", "01J..."]
}
```

For complex projects:

```http
202 Accepted
```

with:

```json
{
  "data": {
    "job_id": "01J..."
  }
}
```

## List

```http
GET /projects/{projectId}/boms
```

## Get

```http
GET /boms/{id}
```

## Items

```http
GET /boms/{id}/items
```

## Regenerate

```http
POST /boms/{id}/regenerate
```

## Approve

```http
POST /boms/{id}/approve
```

---

# 51. BOQ API

```http
POST /projects/{projectId}/boqs/generate
GET  /projects/{projectId}/boqs
GET  /boqs/{id}
GET  /boqs/{id}/items
POST /boqs/{id}/regenerate
POST /boqs/{id}/approve
```

BOQ MUST reference BOM and project revision.

---

# 52. Pricing Calculation API

```http
POST /projects/{projectId}/pricing-calculations
GET  /projects/{projectId}/pricing-calculations
GET  /pricing-calculations/{id}
```

Request:

```json
{
  "boq_id": "01J...",
  "pricing_version_id": "01J..."
}
```

Response:

```json
{
  "data": {
    "subtotal": 850000,
    "discount_total": 25000,
    "tax_total": 148500,
    "grand_total": 973500,
    "currency": "INR"
  }
}
```

---

# 53. Dual Pricing Engine API

The API MUST support:

## Raw Material Pricing

```http
POST /pricing-calculations/raw-material
```

Inputs may include:

```text
board area
edge band length
hardware quantity
laminate area
labour
overhead
markup
tax
```

## Panel Pricing

```http
POST /pricing-calculations/panel-based
```

Inputs:

```text
panel count
panel type
unit rates
hardware
labour
installation
markup
tax
```

The calculation response MUST expose line-level calculation data.

---

# 54. Quotation API

```http
GET    /quotations
POST   /quotations
GET    /quotations/{id}
PATCH  /quotations/{id}
DELETE /quotations/{id}
```

Create:

```json
{
  "project_id": "01J...",
  "boq_id": "01J...",
  "pricing_calculation_id": "01J...",
  "valid_until": "2026-09-15"
}
```

---

# 55. Quotation Workflow

```http
POST /quotations/{id}/submit-review
POST /quotations/{id}/send
POST /quotations/{id}/approve
POST /quotations/{id}/reject
POST /quotations/{id}/cancel
POST /quotations/{id}/create-version
```

Approval MUST preserve snapshot data.

---

# 56. Client Proposal API

```http
POST /projects/{projectId}/proposals
GET  /projects/{projectId}/proposals
GET  /proposals/{id}
PATCH /proposals/{id}
POST /proposals/{id}/render
POST /proposals/{id}/send
```

Rendering can be asynchronous.

---

# 57. Approval API

Generic approval endpoint:

```http
GET  /approvals
POST /approvals
GET  /approvals/{id}
POST /approvals/{id}/approve
POST /approvals/{id}/reject
POST /approvals/{id}/cancel
```

Approval types:

```text
INTERNAL_DESIGN
CLIENT
ENGINEERING
MANUFACTURING
QC
```

---

# 58. Engineering Validation API

```http
POST /projects/{projectId}/engineering-validations
GET  /projects/{projectId}/engineering-validations
GET  /engineering-validations/{id}
POST /engineering-validations/{id}/run
GET  /engineering-validations/{id}/results
POST /engineering-validations/{id}/results/{resultId}/resolve
```

Validation MUST check:

- dimensions
- material availability
- component completeness
- hardware completeness
- manufacturing constraints
- unsupported geometry
- panel dimensions
- grain constraints
- edge-band requirements
- CNC operation validity

---

# 59. Manufacturing Revision API

```http
GET  /projects/{projectId}/manufacturing-revisions
POST /projects/{projectId}/manufacturing-revisions
GET  /manufacturing-revisions/{id}
POST /manufacturing-revisions/{id}/generate
POST /manufacturing-revisions/{id}/validate
POST /manufacturing-revisions/{id}/release
POST /manufacturing-revisions/{id}/supersede
```

Generate flow:

```text
Project Revision
 ↓
Engineering Validation
 ↓
Manufacturing Decomposition
 ↓
Panels
 ↓
Cutlist
 ↓
Nesting
 ↓
CNC
```

---

# 60. Manufacturing Component API

```http
GET /manufacturing-revisions/{id}/components
GET /manufacturing-components/{id}
```

These are generated records and normally not manually editable.

---

# 61. Panel API

```http
GET /manufacturing-revisions/{id}/panels
GET /panels/{id}
PATCH /panels/{id}
```

Panel PATCH MUST be restricted to draft manufacturing revisions.

Released panels MUST be immutable.

---

# 62. Panel Operations API

```http
GET  /panels/{id}/operations
POST /panels/{id}/operations
PATCH /panel-operations/{id}
DELETE /panel-operations/{id}
```

Operations MUST be validated against panel geometry.

---

# 63. Cutlist API

```http
POST /manufacturing-revisions/{id}/cutlists/generate
GET  /manufacturing-revisions/{id}/cutlists
GET  /cutlists/{id}
GET  /cutlists/{id}/items
POST /cutlists/{id}/export
```

Export formats:

```text
CSV
XLSX
JSON
```

CSV profiles MAY include:

```text
GENERIC
BIESSE
KDT
HOMAG
```

---

# 64. Nesting API

```http
POST /manufacturing-revisions/{id}/nesting-jobs
GET  /manufacturing-revisions/{id}/nesting-jobs
GET  /nesting-jobs/{id}
POST /nesting-jobs/{id}/run
POST /nesting-jobs/{id}/cancel
GET  /nesting-jobs/{id}/sheets
GET  /nesting-sheets/{id}/placements
```

Request:

```json
{
  "material_id": "01J...",
  "algorithm": "MAX_RECTANGLE",
  "kerf_mm": 4,
  "spacing_mm": 8,
  "allow_rotation": true,
  "grain_constraint": "STRICT"
}
```

Response for long-running nesting:

```http
202 Accepted
```

---

# 65. CNC Machine API

```http
GET    /cnc-machines
POST   /cnc-machines
GET    /cnc-machines/{id}
PATCH  /cnc-machines/{id}
DELETE /cnc-machines/{id}
```

---

# 66. CNC Job API

```http
POST /manufacturing-revisions/{id}/cnc-jobs
GET  /manufacturing-revisions/{id}/cnc-jobs
GET  /cnc-jobs/{id}
POST /cnc-jobs/{id}/generate
POST /cnc-jobs/{id}/cancel
GET  /cnc-jobs/{id}/download
```

Supported output examples:

```text
DXF
CSV
G-code
machine-specific formats
```

The actual adapter determines output format.

---

# 67. Manufacturing Release API

```http
POST /manufacturing-revisions/{id}/release
GET  /manufacturing-releases/{id}
```

Release MUST verify:

```text
project approved
engineering validation passed
no blocker errors
BOM valid
materials valid
panels valid
cutlist valid
required CNC outputs valid
```

If any blocker exists:

```http
422
```

with:

```text
MANUFACTURING_BLOCKED
```

---

# 68. MES Dashboard API

```http
GET /mes/dashboard
GET /mes/production-summary
GET /mes/workstation-summary
GET /mes/queue
GET /mes/alerts
```

Response should support:

```text
jobs by status
panels by stage
delayed jobs
QC failures
rework
material shortage
machine failures
```

---

# 69. Production Job API

```http
GET    /production-jobs
POST   /production-jobs
GET    /production-jobs/{id}
PATCH  /production-jobs/{id}
DELETE /production-jobs/{id}
```

Production jobs MUST reference a released manufacturing revision.

---

# 70. Production State Transition API

```http
POST /production-jobs/{id}/start
POST /production-jobs/{id}/pause
POST /production-jobs/{id}/resume
POST /production-jobs/{id}/hold
POST /production-jobs/{id}/complete
POST /production-jobs/{id}/cancel
```

Allowed transitions MUST be server-controlled.

---

# 71. Production Operations API

```http
GET  /production-jobs/{id}/operations
POST /production-jobs/{id}/operations/{operationId}/start
POST /production-jobs/{id}/operations/{operationId}/complete
POST /production-jobs/{id}/operations/{operationId}/hold
```

---

# 72. Production Events API

Normally read-only:

```http
GET /production-jobs/{id}/events
GET /panels/{id}/production-events
```

Events are generated by state transitions/scans.

---

# 73. Workstation API

```http
GET    /workstations
POST   /workstations
GET    /workstations/{id}
PATCH  /workstations/{id}
DELETE /workstations/{id}
GET    /workstations/{id}/queue
```

---

# 74. QR Code API

```http
POST /panels/{id}/qr-code
GET  /panels/{id}/qr-code
POST /qr-codes/{id}/revoke
```

QR generation SHOULD return a printable asset or document reference.

---

# 75. QR Scan API

Mobile/factory scanning endpoint:

```http
POST /qr-scans
```

Request:

```json
{
  "token": "QR_TOKEN",
  "action": "SCAN",
  "workstation_id": "01J..."
}
```

Response:

```json
{
  "data": {
    "panel": {
      "id": "01J...",
      "panel_code": "PNL-000123",
      "status": "EDGE_BANDING"
    },
    "allowed_actions": [
      "START",
      "COMPLETE",
      "HOLD"
    ]
  }
}
```

Scan endpoint MUST validate:

- QR validity
- tenant
- panel state
- workstation
- user permission
- allowed transition

---

# 76. QC API

```http
GET  /qc-inspections
POST /qc-inspections
GET  /qc-inspections/{id}
POST /qc-inspections/{id}/start
POST /qc-inspections/{id}/complete
POST /qc-inspections/{id}/fail
POST /qc-inspections/{id}/rework
```

---

# 77. QC Result API

```http
GET  /qc-inspections/{id}/results
POST /qc-inspections/{id}/results
PATCH /qc-results/{id}
```

---

# 78. QC Defect API

```http
GET  /qc-inspections/{id}/defects
POST /qc-inspections/{id}/defects
PATCH /qc-defects/{id}
POST /qc-defects/{id}/resolve
```

---

# 79. Package API

```http
GET  /packages
POST /packages
GET  /packages/{id}
PATCH /packages/{id}
POST /packages/{id}/close
GET  /packages/{id}/items
POST /packages/{id}/items
DELETE /packages/{id}/items/{itemId}
```

Package closure SHOULD freeze its contents.

---

# 80. Dispatch API

```http
GET  /dispatches
POST /dispatches
GET  /dispatches/{id}
PATCH /dispatches/{id}
POST /dispatches/{id}/dispatch
POST /dispatches/{id}/deliver
```

Dispatch MUST validate all package statuses.

---

# 81. Installation API

```http
GET  /installation-jobs
POST /installation-jobs
GET  /installation-jobs/{id}
PATCH /installation-jobs/{id}
POST /installation-jobs/{id}/start
POST /installation-jobs/{id}/complete
```

---

# 82. File API

## Upload

```http
POST /files
```

Multipart form:

```text
file
entity_type
entity_id
```

Return:

```json
{
  "data": {
    "id": "01J...",
    "filename": "floor-plan.pdf",
    "mime_type": "application/pdf",
    "size_bytes": 234567
  }
}
```

## Download

```http
GET /files/{id}/download
```

Authorization MUST be checked before issuing a download.

---

# 83. Document API

```http
GET  /documents
POST /documents
GET  /documents/{id}
POST /documents/{id}/render
GET  /documents/{id}/download
POST /documents/{id}/archive
```

---

# 84. Background Job API

```http
GET /jobs
GET /jobs/{id}
POST /jobs/{id}/cancel
GET /jobs/{id}/events
```

Long-running API responses SHOULD return:

```http
202 Accepted
```

Example:

```json
{
  "data": {
    "job_id": "01J...",
    "status": "QUEUED"
  }
}
```

---

# 85. Job Polling

Client can poll:

```http
GET /jobs/{id}
```

Response:

```json
{
  "data": {
    "status": "RUNNING",
    "progress_percent": 62
  }
}
```

---

# 86. AI Floor Plan API

## Upload/Analyze

```http
POST /ai-jobs/floorplan-recognition
```

Request:

```text
image/pdf file
```

Response:

```http
202 Accepted
```

## Get result

```http
GET /ai-jobs/{id}
```

## Proposed objects

```http
GET /ai-jobs/{id}/proposed-objects
```

## Accept

```http
POST /ai-proposed-objects/{id}/accept
```

## Reject

```http
POST /ai-proposed-objects/{id}/reject
```

Accepted AI objects MUST still go through normal design/engineering validation.

---

# 87. AI Image-to-3D API

```http
POST /ai-jobs/image-to-3d
GET  /ai-jobs/{id}
GET  /ai-jobs/{id}/result
POST /ai-jobs/{id}/accept
```

AI-generated geometry MUST be marked as AI-derived.

---

# 88. Notifications API

```http
GET   /notifications
POST  /notifications/{id}/read
POST  /notifications/read-all
DELETE /notifications/{id}
```

---

# 89. Comments API

```http
GET    /comments?entity_type=PROJECT&entity_id=...
POST   /comments
GET    /comments/{id}
PATCH  /comments/{id}
DELETE /comments/{id}
```

---

# 90. Audit API

Administrators:

```http
GET /audit-logs
GET /audit-logs/{id}
```

Supported filters:

```text
entity_type
entity_id
user_id
action
date_from
date_to
```

Audit records are read-only.

---

# 91. API State Machines

## Project

```text
DRAFT
 ↓
DESIGN
 ↓
INTERNAL_REVIEW
 ↓
CLIENT_REVIEW
 ↓
CLIENT_APPROVED
 ↓
ENGINEERING
 ↓
PRODUCTION_READY
 ↓
MANUFACTURING_RELEASED
 ↓
IN_PRODUCTION
 ↓
COMPLETED
```

## Quotation

```text
DRAFT
 ↓
INTERNAL_REVIEW
 ↓
SENT
 ↓
CLIENT_REVIEW
 ↓
APPROVED
```

## Manufacturing

```text
DRAFT
 ↓
VALIDATING
 ↓
READY
 ↓
RELEASED
 ↓
SUPERSEDED
```

## Production

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

---

# 92. State Transition Rules

Every state transition endpoint MUST:

1. authenticate
2. authorize
3. load current state
4. verify allowed transition
5. verify required dependencies
6. execute transaction
7. write audit/event
8. return updated state

Invalid transitions MUST return:

```http
409 Conflict
```

with:

```text
INVALID_TRANSITION
```

---

# 93. Revision Conflict Handling

Every PATCH to version-controlled records SHOULD accept:

```json
{
  "version": 4
}
```

If database version differs:

```http
409 Conflict
```

Response:

```json
{
  "error": {
    "code": "REVISION_CONFLICT",
    "message": "The record has changed since it was loaded.",
    "current_version": 5
  }
}
```

---

# 94. API Patch Strategy

For normal business entities:

```http
PATCH /projects/{id}
```

For highly interactive design objects, support JSON Merge Patch or controlled field patching.

Do not allow arbitrary database-column updates.

---

# 95. API Validation

Backend MUST validate:

- required fields
- data types
- enums
- numeric ranges
- dimensions
- references
- tenant ownership
- state
- permissions
- revision status

Example furniture validation:

```text
width > 0
height > 0
depth > 0
width <= template max
material exists
template version published
```

---

# 96. Geometry API Rules

Geometry API MUST validate:

- coordinate types
- units
- finite numeric values
- valid polygon/path structures
- non-zero dimensions where required
- supported object type
- parent relationships

Never trust browser-generated geometry.

---

# 97. Manufacturing API Rules

Manufacturing APIs MUST reject:

- missing materials
- invalid panel dimensions
- missing edge-band data
- invalid drilling
- unsupported operations
- stale design
- stale BOM
- stale BOQ where required
- unresolved blocker validations

---

# 98. API Security

Mandatory:

- prepared/parameterized SQL
- input validation
- authorization
- tenant isolation
- rate limiting
- secure headers
- CSRF protection for cookie-based auth
- XSS-safe output handling
- file upload validation
- audit logging for critical operations

Never log:

```text
password
access token
refresh token
secret keys
```

---

# 99. Rate Limiting

Recommended baseline:

```text
Login: 10 requests/minute/IP
General API: 120 requests/minute/user
Heavy generation: 20 requests/minute/user
File upload: 20 requests/minute/user
QR scan: 120 requests/minute/device
```

Exact limits may be configurable.

---

# 100. Request Size Limits

Configure limits for:

```text
JSON request
file upload
bulk design operations
catalog import
AI input
```

Large datasets SHOULD use asynchronous jobs.

---

# 101. Bulk API Limits

Recommended:

```text
design object bulk: <= 500 operations/request
catalog import: asynchronous
panel update: <= 500/request
```

Limits MUST be configurable.

---

# 102. API Caching

Cache only safe, non-user-specific data such as:

- published catalog lookups
- material metadata
- permission definitions
- document templates

Do not cache authorization-sensitive project data without tenant/user-safe cache keys.

---

# 103. ETag / Conditional Requests

GET endpoints for large resources SHOULD support:

```http
ETag
If-None-Match
```

Especially:

```text
design snapshots
published templates
catalog data
documents
```

---

# 104. API Response Optimization

Do not return entire project trees by default.

Example:

```http
GET /projects/{id}
```

should return project summary.

Use:

```http
GET /projects/{id}/design-tree
```

for the full design hierarchy.

---

# 105. Project Workspace API

Provide optimized endpoint:

```http
GET /projects/{id}/workspace
```

Response SHOULD contain:

```text
project metadata
current revision
buildings
floors
rooms
layers
design objects
furniture instances
catalog references
permissions
```

Use pagination or chunking where required.

---

# 106. Workspace Save API

```http
POST /projects/{id}/workspace/changes
```

Request:

```json
{
  "revision_id": "01J...",
  "base_version": 12,
  "operations": [
    {
      "operation": "CREATE",
      "entity": "DESIGN_OBJECT",
      "client_id": "tmp-1",
      "data": {}
    },
    {
      "operation": "UPDATE",
      "entity": "FURNITURE",
      "id": "01J...",
      "version": 3,
      "data": {}
    }
  ]
}
```

Response:

```json
{
  "data": {
    "revision_version": 13,
    "created": [],
    "updated": [],
    "deleted": [],
    "conflicts": []
  }
}
```

This endpoint is intended for CAD-like interactive workflows.

---

# 107. Autosave API

```http
POST /projects/{id}/autosave
```

Autosave MUST NOT create a business revision on every keystroke.

Recommended:

```text
debounce
snapshot
queue
persist
```

---

# 108. Undo/Redo API

Undo/redo should primarily be handled client-side for immediate UI responsiveness.

Server-side snapshots SHOULD provide recovery.

Optional:

```http
POST /projects/{id}/snapshots
GET /projects/{id}/snapshots
POST /projects/{id}/snapshots/{snapshotId}/restore
```

Restore MUST create a new revision or controlled workspace state rather than destroy history.

---

# 109. Catalog Search API

Optimized:

```http
GET /catalog/search?q=...
```

Response categories:

```text
materials
laminates
boards
hardware
templates
```

The endpoint MUST remain tenant-scoped.

---

# 110. Furniture Browser API

```http
GET /furniture-library
```

Filters:

```text
category
brand
published
search
dimensions
```

Only published templates should be returned to normal designers unless permission allows drafts.

---

# 111. Component Designer API

```http
POST /furniture-template-versions/{id}/test-build
POST /furniture-template-versions/{id}/preview
POST /furniture-template-versions/{id}/validate
```

`test-build` should generate a temporary parametric instance without publishing the template.

---

# 112. Parametric Evaluation API

```http
POST /furniture-template-versions/{id}/evaluate
```

Request:

```json
{
  "parameters": {
    "width_mm": 2400,
    "height_mm": 2400,
    "depth_mm": 600
  }
}
```

Response:

```json
{
  "data": {
    "valid": true,
    "components": [],
    "geometry": {},
    "manufacturing": {},
    "warnings": []
  }
}
```

This endpoint is important for preview before saving.

---

# 113. BOM Lineage API

```http
GET /boms/{id}/lineage
```

Response SHOULD show:

```text
BOM
 ↓
Furniture
 ↓
Furniture Revision
 ↓
Component
 ↓
Material
```

---

# 114. Manufacturing Lineage API

```http
GET /manufacturing-revisions/{id}/lineage
```

Return:

```text
Project
Project Revision
Furniture
Furniture Revision
Template Version
Material Version
BOM
BOQ
Engineering Validation
Panels
Cutlist
Nesting
CNC
```

This endpoint is critical for traceability.

---

# 115. Stale Data API

```http
GET /projects/{id}/stale-artifacts
POST /projects/{id}/refresh-derived-data
```

Return:

```json
{
  "data": {
    "artifacts": [
      {
        "type": "BOM",
        "id": "01J...",
        "status": "STALE",
        "reason": "Furniture revision changed"
      }
    ]
  }
}
```

---

# 116. Export API

Generic export:

```http
POST /exports
GET  /exports/{id}
GET  /exports/{id}/download
```

Export types:

```text
BOM_CSV
BOQ_XLSX
CUTLIST_CSV
CUTLIST_XLSX
NESTING_PDF
NESTING_DXF
CNC_FILE
PROJECT_PDF
PROPOSAL_PDF
```

Large exports SHOULD be asynchronous.

---

# 117. API Job Pattern

For long operations:

```text
POST /operation
 ↓
202
 ↓
job_id
 ↓
GET /jobs/{id}
 ↓
COMPLETED
 ↓
download/result
```

Operations:

```text
catalog import
BOM generation
BOQ generation
PDF rendering
nesting
CNC generation
AI processing
large exports
```

---

# 118. Webhook/Event Preparation

The initial application MAY use internal events.

Recommended event names:

```text
project.updated
furniture.updated
bom.generated
boq.generated
quotation.approved
manufacturing.released
production.started
production.completed
qc.failed
package.closed
dispatch.completed
```

External webhooks can be added later.

---

# 119. API Audit Requirements

Audit critical commands:

```text
user role changes
project approval
quotation approval
quotation send
manufacturing release
production state changes
QC decisions
dispatch
catalog bulk updates
template publishing
pricing publishing
```

Audit must record:

```text
who
what
when
entity
before
after
request_id
IP
```

---

# 120. API Documentation

The project MUST maintain OpenAPI documentation:

```text
/docs/api/openapi.yaml
```

Every production endpoint MUST be documented.

Documentation MUST include:

```text
method
path
summary
authentication
permission
parameters
request schema
response schema
errors
examples
```

---

# 121. OpenAPI Requirement

Generate or maintain:

```text
OpenAPI 3.1
```

The OpenAPI definition SHOULD be the source for:

- API documentation
- frontend client generation
- contract testing
- QA test cases

---

# 122. PHP API Folder Structure

Recommended:

```text
src/
├── Controllers/
├── Middleware/
├── Services/
├── Domain/
├── Repositories/
├── Validators/
├── DTO/
├── Policies/
├── Jobs/
├── Events/
└── Infrastructure/
```

Example:

```text
ProjectController
ProjectService
ProjectRepository
ProjectValidator
ProjectPolicy
```

---

# 123. Router Structure

Recommended:

```text
routes/
├── auth.php
├── users.php
├── crm.php
├── projects.php
├── design.php
├── furniture.php
├── catalog.php
├── commercial.php
├── manufacturing.php
├── mes.php
├── documents.php
├── jobs.php
└── ai.php
```

---

# 124. Controller Rules

Controllers MUST:

- parse request
- authenticate through middleware
- validate DTO
- call service
- return standardized response

Controllers MUST NOT:

- contain SQL
- implement nesting algorithms
- calculate manufacturing geometry
- calculate complex pricing
- directly mutate multiple tables without service transaction

---

# 125. Service Layer

Services handle business workflows.

Examples:

```text
ProjectService
FurnitureService
ParametricEvaluationService
BomGenerationService
BoqGenerationService
PricingService
QuotationService
EngineeringValidationService
ManufacturingService
NestingService
CncGenerationService
ProductionService
QcService
DispatchService
AiService
```

---

# 126. Repository Layer

Repositories handle persistence.

Examples:

```text
ProjectRepository
DesignObjectRepository
FurnitureRepository
MaterialRepository
BomRepository
BoqRepository
QuotationRepository
ManufacturingRepository
PanelRepository
ProductionRepository
```

Repositories MUST use parameterized queries.

---

# 127. Policy Layer

Policies MUST implement authorization.

Examples:

```text
ProjectPolicy
FurniturePolicy
QuotationPolicy
ManufacturingPolicy
ProductionPolicy
QcPolicy
```

Example:

```text
canViewProject()
canEditProject()
canApproveProject()
canReleaseManufacturing()
```

---

# 128. DTO Requirement

Use request/response DTOs where practical.

Example:

```text
CreateProjectRequest
UpdateProjectRequest
CreateFurnitureRequest
GenerateBomRequest
ReleaseManufacturingRequest
ProductionTransitionRequest
```

Do not pass raw `$_POST` arrays through the entire application.

---

# 129. API Validation Architecture

Recommended:

```text
Request
 ↓
Authentication
 ↓
Tenant Context
 ↓
DTO Validation
 ↓
Policy
 ↓
Service
 ↓
Domain Validation
 ↓
Repository
```

---

# 130. API Transaction Boundary

Transactions MUST be owned by service/workflow layer.

Example:

```php
$transaction->begin();

try {
    $manufacturingService->validate(...);
    $manufacturingService->snapshot(...);
    $manufacturingService->release(...);

    $transaction->commit();
} catch (Throwable $e) {
    $transaction->rollback();
    throw $e;
}
```

Exact PHP implementation can vary.

---

# 131. API Testing Strategy

Minimum tests:

### Unit
- validators
- pricing
- parameter rules
- state machines
- permission policies

### Integration
- API + MySQL
- tenant isolation
- revision conflicts
- transactions

### Contract
- OpenAPI request/response

### End-to-end
- project to manufacturing
- QR production tracking

---

# 132. API Test Examples

## Tenant Isolation

```text
Create project as Tenant A
Request as Tenant B
Expected: 404/403
```

## Revision Conflict

```text
Client A loads version 5
Client B updates to version 6
Client A updates with version 5
Expected: 409 REVISION_CONFLICT
```

## Manufacturing Release

```text
Engineering validation = FAILED
POST /manufacturing-revisions/{id}/release
Expected: 422 MANUFACTURING_BLOCKED
```

## Production

```text
Production = READY
POST /production-jobs/{id}/complete
Expected: 409 INVALID_TRANSITION
```

---

# 133. API Observability

Every request SHOULD have:

```text
request_id
tenant_id
user_id
route
HTTP method
status
duration
```

Do not log sensitive payloads.

---

# 134. Error Logging

Internal logs MAY contain detailed exception information.

API clients SHOULD receive safe messages.

Never expose:

```text
SQL statements
stack traces
database passwords
filesystem paths
internal secrets
```

in production responses.

---

# 135. API Performance Targets

Initial targets:

```text
Simple GET: <300ms typical
Standard CRUD: <500ms typical
Complex calculation: asynchronous
Nesting: asynchronous
CNC generation: asynchronous
AI processing: asynchronous
Large exports: asynchronous
```

Targets are operational goals, not correctness guarantees.

---

# 136. CAD Interaction Performance

For interactive 2D/3D editing:

- do not call API on every mousemove
- debounce persistence
- batch changes
- use optimistic UI
- use bulk change endpoint
- save snapshots periodically
- use version conflict detection

---

# 137. API Compatibility

API responses MUST remain backward compatible within v1 where practical.

Do not rename/remove fields unexpectedly.

For breaking changes:

```text
v2
```

or versioned representation.

---

# 138. API Deprecation

Deprecated endpoints MUST return headers where appropriate:

```http
Deprecation: true
Sunset: <date>
```

Documentation MUST indicate replacement endpoint.

---

# 139. API Security Checklist

Before production:

```text
[ ] Authentication
[ ] RBAC
[ ] Tenant isolation
[ ] IDOR protection
[ ] SQL injection protection
[ ] XSS protection
[ ] CSRF protection where applicable
[ ] Rate limiting
[ ] File upload validation
[ ] Secure token handling
[ ] Request size limits
[ ] Audit logging
[ ] Error sanitization
[ ] HTTPS
[ ] Security headers
[ ] Dependency scanning
[ ] API penetration testing
```

---

# 140. Cursor Pre-Implementation Analysis

Before writing API code, Cursor MUST inspect:

```text
existing PHP routes
existing controllers
existing SQL
existing models
existing repositories
existing authentication
existing frontend API calls
existing database schema
existing middleware
existing tests
```

Then produce:

```text
CURRENT API INVENTORY
DUPLICATES
MISSING ENDPOINTS
BROKEN CONTRACTS
SECURITY GAPS
TENANT ISOLATION GAPS
DATABASE/API MISMATCHES
MIGRATION PLAN
```

Do not blindly replace existing APIs.

---

# 141. Cursor Implementation Rules

For every endpoint define:

```text
Method
Path
Authentication
Permission
Request
Validation
Service
Transaction
Database operations
Response
Errors
Audit event
Tests
```

Example:

```text
POST /manufacturing-revisions/{id}/release

Auth: required
Permission: manufacturing.release

Validation:
- revision exists
- tenant matches
- revision status = READY
- engineering validation passed
- no blockers
- required outputs generated

Transaction:
- create release
- snapshot
- update revision
- audit

Response:
201/200
```

---

# 142. Cursor Prohibited API Patterns

Cursor MUST NOT:

- trust tenant_id from client
- expose database IDs unnecessarily
- return raw SQL errors
- put SQL in controllers
- put complex business logic in routes
- skip permission checks
- mutate released manufacturing records
- modify approved quotations in place
- expose passwords/tokens in logs
- perform long-running work synchronously when it can exceed API timeout
- use arbitrary query parameters as SQL
- allow arbitrary column updates
- bypass revision checks
- create duplicate jobs on retried requests

---

# 143. API Implementation Priority

## P0 — Foundation

```text
auth
users
roles
permissions
tenant
```

## P0 — Core Design

```text
clients
projects
buildings
floors
rooms
project revisions
design layers
design objects
```

## P0 — Furniture

```text
furniture templates
template versions
parameters
components
rules
furniture instances
furniture revisions
```

## P0 — Commercial

```text
materials
boards
laminates
edge bands
hardware
BOM
BOQ
pricing
quotations
```

## P1 — Manufacturing

```text
engineering validation
manufacturing revisions
panels
cutlists
nesting
CNC
manufacturing release
```

## P1 — MES

```text
production
workstations
QR
QC
packing
dispatch
installation
```

## P2

```text
AI
advanced analytics
white-label
external integrations
webhooks
```

---

# 144. End-to-End API Flow

The API MUST support this complete workflow:

```text
LOGIN
 ↓
CREATE CLIENT
 ↓
CREATE PROJECT
 ↓
CREATE BUILDING
 ↓
CREATE FLOOR
 ↓
CREATE ROOM
 ↓
CREATE DESIGN REVISION
 ↓
CREATE WALLS / DOORS / WINDOWS
 ↓
ADD FURNITURE TEMPLATE
 ↓
CREATE FURNITURE INSTANCE
 ↓
SET PARAMETERS
 ↓
VALIDATE FURNITURE
 ↓
GENERATE BOM
 ↓
GENERATE BOQ
 ↓
CALCULATE PRICE
 ↓
CREATE QUOTATION
 ↓
CLIENT APPROVAL
 ↓
ENGINEERING VALIDATION
 ↓
CREATE MANUFACTURING REVISION
 ↓
GENERATE PANELS
 ↓
GENERATE CUTLIST
 ↓
RUN NESTING
 ↓
GENERATE CNC
 ↓
RELEASE MANUFACTURING
 ↓
CREATE PRODUCTION JOB
 ↓
CUTTING
 ↓
EDGE BANDING
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
DISPATCH
 ↓
INSTALLATION
 ↓
PROJECT COMPLETION
```

---

# 145. Example End-to-End Requests

## Create Project

```http
POST /api/v1/projects
Authorization: Bearer TOKEN
Content-Type: application/json
```

```json
{
  "name": "Villa Interior",
  "client_id": "01JCLIENT",
  "project_type": "RESIDENTIAL"
}
```

## Create Furniture

```http
POST /api/v1/rooms/01JROOM/furniture
```

```json
{
  "template_id": "01JTEMPLATE",
  "template_version_id": "01JVERSION",
  "name": "Master Wardrobe",
  "parameters": {
    "width_mm": 2400,
    "height_mm": 2400,
    "depth_mm": 600
  }
}
```

## Validate

```http
POST /api/v1/furniture/01JFURNITURE/validate
```

## Generate BOM

```http
POST /api/v1/projects/01JPROJECT/boms/generate
```

```json
{
  "project_revision_id": "01JREVISION"
}
```

## Generate Manufacturing

```http
POST /api/v1/projects/01JPROJECT/manufacturing-revisions
```

```json
{
  "project_revision_id": "01JREVISION"
}
```

## Release

```http
POST /api/v1/manufacturing-revisions/01JMFG/release
Idempotency-Key: 01JIDEMPOTENCY
```

## Start Production

```http
POST /api/v1/production-jobs/01JJOB/start
```

## Scan Panel

```http
POST /api/v1/qr-scans
```

```json
{
  "token": "QR_TOKEN",
  "action": "START",
  "workstation_id": "01JSTATION"
}
```

---

# 146. API Completion Criteria

An API module is complete only when:

- endpoint implemented
- authentication applied
- permission applied
- tenant scope enforced
- DTO/request validation implemented
- service implemented
- repository implemented
- transaction implemented where required
- standardized response implemented
- errors documented
- audit implemented where required
- OpenAPI documented
- unit tests added
- integration tests added
- security tests added
- frontend integration verified

---

# 147. Final Architecture Principle

The API must preserve this chain:

```text
ONE AUTHORITATIVE DESIGN
          ↓
VERSIONED DESIGN REVISION
          ↓
PARAMETRIC FURNITURE
          ↓
BOM / BOQ / PRICING
          ↓
ENGINEERING VALIDATION
          ↓
MANUFACTURING REVISION
          ↓
PANELS / CUTLIST / NESTING / CNC
          ↓
MES / PRODUCTION
          ↓
QR TRACEABILITY
          ↓
QC / PACKING / DISPATCH
```

Every API operation that changes authoritative data must preserve versioning, lineage, tenant isolation and auditability.

---

# 148. Final Cursor Instruction

Treat this document as the **API implementation contract**.

Before implementing any endpoint, Cursor MUST answer:

```text
Who can call it?
Which tenant does it belong to?
Which resource does it operate on?
Which revision does it operate on?
What validation is required?
What state must the resource be in?
What database transaction is required?
What dependent records become stale?
What audit event is required?
What response does the frontend need?
What happens if the request is retried?
What happens if two users update simultaneously?
```

The API is successful when the frontend can perform the complete journey:

> **Design → Parametric Furniture → BOM → BOQ → Pricing → Quotation → Engineering → Manufacturing → Cutlist → Nesting → CNC → MES → QR Tracking → QC → Packing → Dispatch → Installation**

without bypassing the business rules or the authoritative versioned data model.
