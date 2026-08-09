# Database Specification Document
## Interior Design, Parametric Furniture, Estimation, Manufacturing & MES Platform

**Document ID:** DBS-IDFM-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Database:** MySQL 8.x / InnoDB / utf8mb4  
**Backend:** PHP 8.x  
**Frontend:** JavaScript ES6+  
**Primary unit:** millimeter (mm)  
**Date:** 2026-08-09

---

## 1. Purpose

This document is the database implementation baseline for the complete platform covering:

- Multi-tenancy
- Authentication and RBAC
- CRM
- Projects, buildings, floors and rooms
- 2D/3D architectural design
- Parametric furniture
- Component Designer
- Materials and catalogs
- BOM/BOQ
- Dual pricing
- Quotations and proposals
- Revisions and approvals
- Engineering validation
- Manufacturing decomposition
- Panels and cutlists
- Nesting
- CNC/CAM
- MES and production
- QR tracking
- QC
- Packing and dispatch
- Documents
- Audit
- Notifications
- Background jobs
- AI processing
- White-label configuration

The schema MUST support a single authoritative project model and versioned derived outputs.

---

# 2. Database Principles

### DBS-001 — Relational Core
Authoritative business entities MUST be relational tables.

### DBS-002 — JSON Usage
JSON MAY be used for:
- geometry
- flexible parameters
- metadata
- rule configuration
- machine-specific configuration
- snapshots

JSON MUST NOT replace relational data that is frequently queried, joined, filtered or authorized.

### DBS-003 — Tenant Isolation
Every tenant-owned table MUST contain `tenant_id`, directly or through a guaranteed parent relationship. Direct tenant scope is preferred for high-risk tables.

### DBS-004 — Historical Reproducibility
Approved quotations and released manufacturing records MUST remain reproducible after current catalog, template or pricing data changes.

### DBS-005 — Revision Safety
Released manufacturing data MUST be immutable through normal application operations.

### DBS-006 — Soft Delete
Critical business entities SHOULD use `deleted_at`; historical records MUST NOT be hard-deleted through normal UI.

### DBS-007 — Server Authority
Tenant filtering, authorization, validation and state transitions MUST be enforced by the backend.

---

# 3. Naming Standards

Tables:
```text
snake_case
plural nouns
```

Examples:
```text
projects
rooms
design_objects
furniture_instances
bom_items
production_jobs
```

Primary keys:
```text
id BIGINT UNSIGNED AUTO_INCREMENT
```

Public identifiers SHOULD also be provided:
```text
public_id CHAR(26) or equivalent
```

Foreign keys:
```text
<entity>_id
```

Timestamps:
```text
created_at
updated_at
```

Audit columns where applicable:
```text
created_by
updated_by
```

---

# 4. Common Data Types

Use:

| Data | Type |
|---|---|
| Internal ID | BIGINT UNSIGNED |
| Public ID | CHAR(26) / ULID / UUID strategy |
| Dimension | DECIMAL(12,3) |
| Quantity | DECIMAL(14,4) |
| Money | DECIMAL(16,4) |
| Percentage | DECIMAL(8,4) |
| Boolean | TINYINT(1) |
| Date/time | DATETIME |
| Flexible data | JSON |

Never use FLOAT/DOUBLE for money.

---

# 5. Migration Structure

Repository:

```text
database/
├── migrations/
├── seeders/
├── fixtures/
├── views/
└── docs/
```

Migrations MUST be sequential and version controlled.

Example:

```text
000001_create_tenants.sql
000002_create_users.sql
000003_create_roles.sql
```

All schema changes MUST occur through migrations.

---

# 6. Tenant / Identity Tables

## 6.1 `tenants`

```text
id
public_id
name
legal_name
slug
status
default_currency
default_unit_system
timezone
locale
logo_file_id
favicon_file_id
created_at
updated_at
```

Unique:
```text
slug
```

Statuses:
```text
ACTIVE
SUSPENDED
ARCHIVED
```

## 6.2 `tenant_settings`

```text
id
tenant_id
setting_key
setting_value_json
created_at
updated_at
```

Unique:
```text
tenant_id + setting_key
```

## 6.3 `tenant_domains`

```text
id
tenant_id
domain
domain_type
verification_status
is_primary
verified_at
created_at
updated_at
```

Unique:
```text
domain
```

## 6.4 `tenant_branding`

```text
id
tenant_id
primary_color
secondary_color
accent_color
email_footer
proposal_footer
quotation_footer
logo_file_id
favicon_file_id
created_at
updated_at
```

Unique:
```text
tenant_id
```

---

# 7. Authentication / RBAC

## 7.1 `users`

```text
id
public_id
tenant_id
first_name
last_name
email
phone
password_hash
status
last_login_at
email_verified_at
created_at
created_by
updated_at
updated_by
deleted_at
```

Unique:
```text
tenant_id + normalized email
```

Statuses:
```text
INVITED
ACTIVE
DISABLED
LOCKED
```

Passwords MUST use secure PHP password hashing. Plaintext passwords are prohibited.

## 7.2 `roles`

```text
id
tenant_id
name
description
is_system_role
status
created_at
updated_at
```

Unique:
```text
tenant_id + name
```

## 7.3 `permissions`

```text
id
module
permission_key
name
description
created_at
```

Unique:
```text
permission_key
```

Examples:
```text
project.view
project.create
project.edit
design.view
design.edit
furniture.create
bom.generate
boq.generate
pricing.edit
quotation.send
manufacturing.validate
manufacturing.release
production.update
qc.update
```

## 7.4 `role_permissions`

```text
role_id
permission_id
created_at
```

PK:
```text
role_id + permission_id
```

## 7.5 `user_roles`

```text
user_id
role_id
created_at
```

PK:
```text
user_id + role_id
```

## 7.6 `user_sessions`

```text
id
user_id
tenant_id
token_hash
expires_at
last_activity_at
ip_address
user_agent
created_at
revoked_at
```

Raw tokens MUST NOT be stored.

---

# 8. CRM

## 8.1 `leads`

```text
id
public_id
tenant_id
name
company_name
email
phone
source
status
owner_user_id
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

Statuses:
```text
NEW
CONTACTED
QUALIFIED
LOST
CONVERTED
```

## 8.2 `clients`

```text
id
public_id
tenant_id
name
company_name
email
phone
address_line1
address_line2
city
state
postal_code
country
tax_identifier
status
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

## 8.3 `client_contacts`

```text
id
tenant_id
client_id
name
email
phone
designation
is_primary
status
created_at
updated_at
```

## 8.4 `opportunities`

```text
id
public_id
tenant_id
client_id
title
stage
estimated_value
probability
expected_close_date
owner_user_id
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

Stages:
```text
QUALIFIED
DISCOVERY
DESIGN
PROPOSAL
NEGOTIATION
WON
LOST
```

---

# 9. Project Hierarchy

## 9.1 `projects`

```text
id
public_id
tenant_id
client_id
opportunity_id
project_number
name
project_type
status
address_line1
address_line2
city
state
postal_code
country
start_date
expected_completion_date
completed_at
owner_user_id
notes
current_revision_id
created_at
created_by
updated_at
updated_by
deleted_at
```

Unique:
```text
tenant_id + project_number
```

Statuses:
```text
DRAFT
DESIGN
INTERNAL_REVIEW
CLIENT_REVIEW
CLIENT_APPROVED
ENGINEERING
PRODUCTION_READY
MANUFACTURING_RELEASED
IN_PRODUCTION
COMPLETED
ARCHIVED
```

## 9.2 `project_users`

```text
project_id
user_id
project_role
created_at
```

## 9.3 `buildings`

```text
id
public_id
tenant_id
project_id
name
building_number
status
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

## 9.4 `floors`

```text
id
public_id
tenant_id
building_id
name
floor_number
elevation_mm
height_mm
status
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

## 9.5 `rooms`

```text
id
public_id
tenant_id
floor_id
name
room_type
width_mm
depth_mm
height_mm
status
geometry_json
notes
created_at
created_by
updated_at
updated_by
deleted_at
```

Room types:
```text
LIVING_ROOM
BEDROOM
KITCHEN
DINING
BATHROOM
STUDY
OFFICE
UTILITY
OTHER
```

---

# 10. Design / Architectural Model

## 10.1 `project_revisions`

```text
id
public_id
tenant_id
project_id
revision_number
revision_label
revision_type
status
parent_revision_id
reason
created_by
created_at
approved_by
approved_at
```

Unique:
```text
project_id + revision_number
```

## 10.2 `design_layers`

```text
id
tenant_id
project_id
name
layer_key
color
sort_order
is_visible
is_locked
created_at
updated_at
```

Unique:
```text
project_id + layer_key
```

## 10.3 `design_objects`

This is the authoritative architectural/design object table.

```text
id
public_id
tenant_id
project_id
project_revision_id
building_id
floor_id
room_id
parent_object_id
object_type
name
layer_id
geometry_json
parameters_json
transform_json
material_assignments_json
metadata_json
status
version
created_at
created_by
updated_at
updated_by
deleted_at
```

Object types:
```text
WALL
DOOR
WINDOW
COLUMN
BEAM
FLOOR
CEILING
FURNITURE
FIXTURE
ANNOTATION
DIMENSION
```

## 10.4 `design_object_relations`

```text
id
tenant_id
source_object_id
target_object_id
relation_type
metadata_json
created_at
```

Relations:
```text
HOSTED_ON
CONNECTED_TO
CONTAINS
OPENING_IN
REFERENCE_TO
ALIGNED_WITH
```

## 10.5 `design_snapshots`

```text
id
tenant_id
project_id
project_revision_id
snapshot_type
snapshot_json
created_by
created_at
```

Snapshot types:
```text
AUTOSAVE
MANUAL
APPROVAL
RELEASE
```

---

# 11. Furniture Template / Parametric Engine

## 11.1 `furniture_templates`

```text
id
public_id
tenant_id
category
template_code
name
description
status
current_version_id
created_at
created_by
updated_at
updated_by
deleted_at
```

Unique:
```text
tenant_id + template_code
```

Categories:
```text
WARDROBE
KITCHEN_BASE
KITCHEN_WALL
KITCHEN_TALL
KITCHEN_ISLAND
TV_UNIT
VANITY
STORAGE
BOOKSHELF
STUDY
CUSTOM_CABINET
```

## 11.2 `furniture_template_versions`

```text
id
tenant_id
furniture_template_id
version_number
version_label
status
description
published_at
published_by
created_at
created_by
```

Statuses:
```text
DRAFT
IN_REVIEW
PUBLISHED
ARCHIVED
```

Unique:
```text
furniture_template_id + version_number
```

## 11.3 `furniture_template_parameters`

```text
id
tenant_id
template_version_id
parameter_key
display_name
data_type
unit
default_value_json
minimum_value_json
maximum_value_json
is_required
sort_order
help_text
validation_config_json
created_at
updated_at
```

Types:
```text
NUMBER
INTEGER
BOOLEAN
TEXT
ENUM
MATERIAL
HARDWARE
```

## 11.4 `furniture_template_components`

```text
id
tenant_id
template_version_id
component_key
name
component_type
parent_component_id
sort_order
parameters_json
geometry_rule_json
material_rule_json
manufacturing_rule_json
status
created_at
updated_at
```

## 11.5 `furniture_template_rules`

```text
id
tenant_id
template_version_id
rule_key
name
rule_type
expression
priority
condition_json
output_config_json
status
created_at
updated_at
```

Rule types:
```text
DIMENSION
VISIBILITY
COMPONENT
MATERIAL
HARDWARE
MANUFACTURING
VALIDATION
PRICING
```

Expressions MUST be interpreted by a safe expression engine. Never use `eval()` or equivalent arbitrary code execution.

---

# 12. Furniture Instances

## 12.1 `furniture_instances`

```text
id
public_id
tenant_id
project_id
project_revision_id
room_id
template_id
template_version_id
furniture_code
name
status
position_json
rotation_json
scale_json
parameter_values_json
material_overrides_json
metadata_json
current_revision_id
created_at
created_by
updated_at
updated_by
deleted_at
```

## 12.2 `furniture_revisions`

```text
id
tenant_id
furniture_id
revision_number
revision_label
template_version_id
parameter_values_json
component_snapshot_json
geometry_snapshot_json
material_snapshot_json
status
reason
created_by
created_at
approved_by
approved_at
```

Unique:
```text
furniture_id + revision_number
```

## 12.3 `furniture_components`

```text
id
public_id
tenant_id
furniture_id
furniture_revision_id
template_component_id
parent_component_id
component_key
component_type
name
parameters_json
geometry_json
material_id
hardware_id
manufacturing_config_json
status
created_at
updated_at
```

## 12.4 `furniture_hardware`

```text
id
tenant_id
furniture_id
furniture_revision_id
hardware_id
quantity
source_component_id
rule_source
metadata_json
created_at
```

---

# 13. Materials / Catalog

## 13.1 `material_categories`

```text
id
tenant_id NULL
code
name
material_type
status
created_at
updated_at
```

Material types:
```text
BOARD
LAMINATE
EDGE_BAND
HARDWARE
PROFILE
ACCESSORY
```

## 13.2 `materials`

```text
id
public_id
tenant_id
material_category_id
brand
code
name
description
unit
status
cost
selling_price
currency
attributes_json
image_file_id
created_at
created_by
updated_at
updated_by
deleted_at
```

Unique:
```text
tenant_id + code
```

## 13.3 `material_versions`

```text
id
tenant_id
material_id
version_number
code
name
thickness_mm
dimensions_json
cost
selling_price
attributes_json
status
created_at
created_by
```

## 13.4 `boards`

```text
id
material_id
thickness_mm
sheet_length_mm
sheet_width_mm
material_type
finish
color
grain_direction
density_kg_m3
kerf_allowance_mm
status
created_at
updated_at
```

## 13.5 `laminates`

```text
id
material_id
collection
finish
thickness_mm
sheet_length_mm
sheet_width_mm
image_file_id
status
created_at
updated_at
```

## 13.6 `edge_bands`

```text
id
material_id
thickness_mm
width_mm
color
cost_per_meter
selling_price_per_meter
status
created_at
updated_at
```

## 13.7 `hardware`

```text
id
public_id
tenant_id
category
brand
code
name
unit
cost
selling_price
currency
attributes_json
status
created_at
created_by
updated_at
updated_by
deleted_at
```

## 13.8 `hardware_versions`

```text
id
tenant_id
hardware_id
version_number
code
name
cost
selling_price
attributes_json
status
created_at
created_by
```

---

# 14. Catalog Imports

## 14.1 `catalog_imports`

```text
id
public_id
tenant_id
import_type
file_id
status
total_rows
valid_rows
invalid_rows
started_at
completed_at
created_by
created_at
error_summary_json
```

Statuses:
```text
UPLOADED
PARSING
VALIDATING
READY
IMPORTING
COMPLETED
FAILED
```

## 14.2 `catalog_import_rows`

```text
id
catalog_import_id
row_number
raw_data_json
mapped_data_json
validation_status
validation_errors_json
created_at
```

---

# 15. BOM / BOQ

## 15.1 `bom_headers`

```text
id
public_id
tenant_id
project_id
project_revision_id
furniture_id
furniture_revision_id
bom_number
version
status
generated_at
generated_by
source_hash
```

Statuses:
```text
DRAFT
GENERATED
STALE
APPROVED
LOCKED
```

## 15.2 `bom_items`

```text
id
tenant_id
bom_id
item_type
material_id
hardware_id
component_id
source_object_id
source_component_id
item_code
description
quantity
unit
dimensions_json
cost_rate
cost_total
metadata_json
```

Every derived BOM item SHOULD retain source lineage.

## 15.3 `boq_headers`

```text
id
public_id
tenant_id
project_id
project_revision_id
boq_number
version
status
generated_at
generated_by
source_hash
```

## 15.4 `boq_items`

```text
id
tenant_id
boq_id
source_bom_item_id
source_furniture_id
item_code
description
category
quantity
unit
rate
discount_percent
tax_percent
subtotal
tax_amount
total
metadata_json
```

---

# 16. Pricing

## 16.1 `pricing_rule_sets`

```text
id
tenant_id
name
description
status
created_at
created_by
updated_at
updated_by
```

## 16.2 `pricing_rules`

```text
id
tenant_id
pricing_rule_set_id
rule_key
rule_type
basis
unit
rate
formula_config_json
priority
conditions_json
status
created_at
updated_at
```

Rule types:
```text
MATERIAL
PANEL
UNIT
LABOUR
MANUFACTURING
INSTALLATION
OVERHEAD
MARKUP
DISCOUNT
TAX
```

Bases:
```text
SQFT
SQM
LM
PANEL
UNIT
FIXED
PERCENTAGE
```

## 16.3 `pricing_versions`

```text
id
tenant_id
pricing_rule_set_id
version_number
status
snapshot_json
created_by
created_at
published_by
published_at
```

## 16.4 `pricing_calculations`

```text
id
tenant_id
project_id
project_revision_id
pricing_version_id
source_bom_id
source_boq_id
currency
subtotal
discount_total
tax_total
grand_total
calculation_snapshot_json
created_by
created_at
```

## 16.5 `pricing_calculation_lines`

```text
id
pricing_calculation_id
source_type
source_id
category
description
quantity
unit
rate
amount
calculation_json
```

---

# 17. Quotations / Proposals

## 17.1 `quotations`

```text
id
public_id
tenant_id
project_id
client_id
boq_id
pricing_calculation_id
quotation_number
version
status
issue_date
valid_until
currency
subtotal
discount_total
tax_total
grand_total
terms_text
notes
approved_at
approved_by
created_at
created_by
updated_at
updated_by
```

Statuses:
```text
DRAFT
INTERNAL_REVIEW
SENT
CLIENT_REVIEW
APPROVED
REJECTED
EXPIRED
CANCELLED
```

## 17.2 `quotation_items`

```text
id
tenant_id
quotation_id
source_boq_item_id
item_code
description
quantity
unit
rate
discount_percent
tax_percent
subtotal
tax_amount
total
snapshot_json
```

Approved quotations MUST use snapshot data.

## 17.3 `proposals`

```text
id
public_id
tenant_id
project_id
client_id
quotation_id
template_id
version
status
title
content_json
file_id
created_by
created_at
updated_at
```

---

# 18. Documents / Files

## 18.1 `files`

```text
id
public_id
tenant_id
storage_provider
storage_key
original_filename
mime_type
extension
size_bytes
checksum_sha256
status
created_by
created_at
deleted_at
```

Uploaded files MUST be validated and must not be executable.

## 18.2 `document_templates`

```text
id
tenant_id NULL
template_type
name
version
content_json
status
created_at
created_by
updated_at
```

## 18.3 `documents`

```text
id
public_id
tenant_id
entity_type
entity_id
document_type
document_number
version
status
file_id
revision_reference_json
created_by
created_at
```

Document types:
```text
FLOOR_PLAN
ELEVATION
SECTION
BOM
BOQ
QUOTATION
PROPOSAL
CUTLIST
NESTING
CNC
MANUFACTURING
QC
PACKING
DISPATCH
```

---

# 19. Engineering

## 19.1 `engineering_validations`

```text
id
tenant_id
project_id
project_revision_id
manufacturing_revision_id
status
started_at
completed_at
summary_json
created_by
```

Statuses:
```text
RUNNING
PASSED
PASSED_WITH_WARNINGS
FAILED
```

## 19.2 `engineering_validation_results`

```text
id
tenant_id
engineering_validation_id
severity
rule_code
message
entity_type
entity_id
field_name
details_json
resolved_at
resolved_by
created_at
```

Severity:
```text
INFO
WARNING
ERROR
BLOCKER
```

---

# 20. Manufacturing Revisions

## 20.1 `manufacturing_revisions`

```text
id
public_id
tenant_id
project_id
project_revision_id
revision_number
revision_label
status
design_hash
engineering_validation_hash
template_versions_snapshot_json
material_versions_snapshot_json
pricing_version_id
created_by
created_at
released_by
released_at
release_notes
```

Statuses:
```text
DRAFT
VALIDATING
READY
RELEASED
SUPERSEDED
CANCELLED
```

## 20.2 `manufacturing_snapshots`

```text
id
tenant_id
manufacturing_revision_id
snapshot_type
snapshot_json
checksum_sha256
created_at
created_by
```

Snapshot types:
```text
DESIGN
BOM
BOQ
PANELS
NESTING
CNC
RELEASE
```

---

# 21. Manufacturing Decomposition

## 21.1 `manufacturing_components`

```text
id
public_id
tenant_id
manufacturing_revision_id
furniture_id
furniture_revision_id
source_component_id
component_code
component_type
name
dimensions_json
material_id
quantity
manufacturing_config_json
created_at
```

## 21.2 `panels`

```text
id
public_id
tenant_id
manufacturing_revision_id
manufacturing_component_id
furniture_id
room_id
panel_code
panel_type
material_id
thickness_mm
length_mm
width_mm
quantity
grain_direction
grain_locked
edge_top_json
edge_bottom_json
edge_left_json
edge_right_json
drilling_json
routing_json
grooving_json
status
production_status
created_at
updated_at
```

Panel types:
```text
CARCASS
SHELF
SHUTTER
BACK
DRAWER
PARTITION
OTHER
```

## 21.3 `panel_edge_bands`

```text
id
tenant_id
panel_id
edge_position
edge_band_id
length_mm
width_mm
thickness_mm
operation
status
created_at
```

## 21.4 `panel_operations`

```text
id
tenant_id
panel_id
operation_type
sequence_no
x_mm
y_mm
z_mm
width_mm
height_mm
depth_mm
tool_code
parameters_json
status
created_at
```

Operations:
```text
DRILL
ROUTE
GROOVE
CUT
POCKET
```

---

# 22. Cutlists

## 22.1 `cutlists`

```text
id
public_id
tenant_id
manufacturing_revision_id
version
status
source_hash
generated_at
generated_by
```

## 22.2 `cutlist_items`

```text
id
tenant_id
cutlist_id
panel_id
material_id
material_code
thickness_mm
length_mm
width_mm
quantity
grain_direction
edge_data_json
metadata_json
```

---

# 23. Nesting

## 23.1 `nesting_jobs`

```text
id
public_id
tenant_id
manufacturing_revision_id
material_id
status
algorithm
kerf_mm
spacing_mm
allow_rotation
grain_constraint
started_at
completed_at
created_by
error_json
```

## 23.2 `nesting_sheets`

```text
id
tenant_id
nesting_job_id
sheet_number
material_id
length_mm
width_mm
thickness_mm
used_area_mm2
waste_area_mm2
utilization_percent
layout_json
created_at
```

## 23.3 `nesting_placements`

```text
id
tenant_id
nesting_sheet_id
panel_id
x_mm
y_mm
rotation_deg
length_mm
width_mm
grain_orientation
created_at
```

---

# 24. CNC / CAM

## 24.1 `cnc_machines`

```text
id
public_id
tenant_id
name
manufacturer
model
adapter_type
unit_system
work_area_length_mm
work_area_width_mm
max_thickness_mm
configuration_json
status
created_at
updated_at
```

## 24.2 `cnc_jobs`

```text
id
public_id
tenant_id
manufacturing_revision_id
machine_id
status
source_hash
output_file_id
output_format
started_at
completed_at
created_by
error_json
```

## 24.3 `cnc_operations`

```text
id
tenant_id
cnc_job_id
panel_id
operation_type
sequence_no
operation_data_json
created_at
```

Machine-specific logic belongs in code adapters, not the database.

Potential adapters:
```text
GENERIC_CSV
DXF
BIESSE
HOMAG
KDT
GENERIC_CNC
```

---

# 25. Manufacturing Release

## 25.1 `manufacturing_releases`

```text
id
public_id
tenant_id
manufacturing_revision_id
release_number
status
released_by
released_at
release_checklist_json
notes
created_at
```

Release status:
```text
RELEASED
SUPERSEDED
CANCELLED
```

Released records MUST be immutable through normal UI.

---

# 26. MES / Production

## 26.1 `production_jobs`

```text
id
public_id
tenant_id
project_id
manufacturing_revision_id
job_number
priority
status
assigned_team
scheduled_date
started_at
completed_at
created_by
created_at
updated_at
```

Statuses:
```text
PLANNED
READY
CUTTING
EDGE_BANDING
DRILLING
ROUTING
ASSEMBLY
QC
PACKING
DISPATCHED
COMPLETED
ON_HOLD
CANCELLED
```

## 26.2 `production_operations`

```text
id
tenant_id
production_job_id
stage
sequence_no
status
started_at
completed_at
assigned_user_id
workstation_id
notes
created_at
updated_at
```

## 26.3 `production_events`

```text
id
tenant_id
production_job_id
panel_id
stage
event_type
user_id
workstation_id
event_data_json
created_at
```

Events:
```text
STARTED
COMPLETED
PAUSED
REWORK
HOLD
FAILED
SCANNED
```

Production events MUST be append-only.

---

# 27. Workstations

## 27.1 `workstations`

```text
id
public_id
tenant_id
name
station_type
location
status
configuration_json
created_at
updated_at
```

Types:
```text
CUTTING
EDGE_BANDING
DRILLING
ROUTING
ASSEMBLY
QC
PACKING
DISPATCH
```

---

# 28. QR Tracking

## 28.1 `qr_codes`

```text
id
public_id
tenant_id
entity_type
entity_id
token_hash
status
created_at
revoked_at
```

QR payload MUST use an unpredictable public reference/token.

## 28.2 `qr_scan_events`

```text
id
tenant_id
qr_code_id
entity_type
entity_id
user_id
workstation_id
action
scan_result
metadata_json
created_at
```

---

# 29. QC

## 29.1 `qc_checklist_templates`

```text
id
tenant_id
name
entity_type
checklist_json
status
created_at
updated_at
```

## 29.2 `qc_inspections`

```text
id
public_id
tenant_id
production_job_id
panel_id
inspection_type
status
inspector_user_id
started_at
completed_at
notes
created_at
```

Statuses:
```text
PENDING
PASS
FAIL
REWORK
HOLD
```

## 29.3 `qc_results`

```text
id
tenant_id
inspection_id
check_code
check_name
result
expected_value
actual_value
notes
created_at
```

## 29.4 `qc_defects`

```text
id
tenant_id
inspection_id
panel_id
defect_type
severity
description
rework_required
status
resolved_at
resolved_by
created_at
```

---

# 30. Packing / Dispatch / Installation

## 30.1 `packages`

```text
id
public_id
tenant_id
project_id
package_number
package_type
status
length_mm
width_mm
height_mm
weight_kg
label_file_id
created_by
created_at
updated_at
```

## 30.2 `package_items`

```text
id
tenant_id
package_id
panel_id
hardware_id
bom_item_id
quantity
created_at
```

## 30.3 `dispatches`

```text
id
public_id
tenant_id
project_id
dispatch_number
status
carrier
vehicle_number
driver_name
driver_phone
dispatch_date
delivery_date
destination_json
notes
created_by
created_at
updated_at
```

Statuses:
```text
READY
DISPATCHED
IN_TRANSIT
DELIVERED
ISSUE
CANCELLED
```

## 30.4 `dispatch_packages`

```text
dispatch_id
package_id
created_at
```

PK:
```text
dispatch_id + package_id
```

## 30.5 `installation_jobs`

```text
id
public_id
tenant_id
project_id
installer_name
installer_phone
scheduled_date
started_at
completed_at
status
notes
created_at
updated_at
```

---

# 31. Documents / Collaboration

## 31.1 `notifications`

```text
id
tenant_id
user_id
notification_type
title
message
entity_type
entity_id
read_at
created_at
```

## 31.2 `comments`

```text
id
tenant_id
entity_type
entity_id
user_id
parent_comment_id
comment_text
status
created_at
updated_at
deleted_at
```

## 31.3 `approval_requests`

```text
id
public_id
tenant_id
entity_type
entity_id
approval_type
requested_by
assigned_to
status
requested_at
completed_at
completed_by
comments
revision_reference_json
```

Statuses:
```text
PENDING
APPROVED
REJECTED
CANCELLED
```

---

# 32. Background Jobs

## 32.1 `jobs`

```text
id
public_id
tenant_id
job_type
entity_type
entity_id
status
priority
payload_json
progress_percent
started_at
completed_at
error_json
attempt_count
max_attempts
created_by
created_at
updated_at
```

Statuses:
```text
QUEUED
RUNNING
COMPLETED
FAILED
CANCELLED
```

## 32.2 `job_events`

```text
id
job_id
event_type
message
progress_percent
metadata_json
created_at
```

Long operations such as nesting, CNC, large BOM generation, PDF generation and imports SHOULD use background jobs.

---

# 33. AI

## 33.1 `ai_jobs`

```text
id
public_id
tenant_id
project_id
room_id
job_type
input_file_id
status
provider
model
request_json
response_json
confidence_json
created_by
created_at
completed_at
error_json
```

Types:
```text
FLOORPLAN_RECOGNITION
IMAGE_TO_3D
DESIGN_ASSISTANCE
```

## 33.2 `ai_proposed_objects`

```text
id
tenant_id
ai_job_id
object_type
proposed_geometry_json
proposed_parameters_json
confidence
status
accepted_object_id
reviewed_by
reviewed_at
created_at
```

Statuses:
```text
PROPOSED
ACCEPTED
REJECTED
EDITED
```

AI output MUST never bypass normal engineering validation.

---

# 34. Audit / Activity

## 34.1 `audit_logs`

```text
id
tenant_id
user_id
entity_type
entity_id
action
before_json
after_json
request_id
ip_address
user_agent
created_at
```

Audit logs SHOULD be append-only.

## 34.2 `activity_logs`

```text
id
tenant_id
user_id
entity_type
entity_id
activity_type
message
metadata_json
created_at
```

Audit and user-facing activity are separate concepts.

---

# 35. Feature Flags

## 35.1 `feature_flags`

```text
id
tenant_id NULL
flag_key
enabled
configuration_json
created_at
updated_at
```

Examples:
```text
enable_advanced_nesting
enable_cnc_biesse
enable_ai_floorplan
enable_component_designer
enable_mes
```

---

# 36. Numbering

## 36.1 `numbering_sequences`

```text
id
tenant_id
sequence_type
prefix
current_number
padding_length
reset_period
updated_at
```

Types:
```text
PROJECT
QUOTATION
MANUFACTURING
PANEL
PACKAGE
DISPATCH
```

Allocation MUST be transaction-safe.

---

# 37. Critical Relationships

The following relationship chain MUST remain intact:

```text
Tenant
 ↓
Project
 ↓
Project Revision
 ↓
Room
 ↓
Furniture Instance
 ↓
Furniture Revision
 ↓
Template Version
 ↓
Furniture Component
 ↓
Manufacturing Component
 ↓
Panel
 ↓
Cutlist
 ↓
Nesting Placement
 ↓
CNC Operation
 ↓
Production Event
 ↓
QC Inspection
 ↓
Package
 ↓
Dispatch
```

Commercial lineage:

```text
Furniture
 ↓
BOM
 ↓
BOQ
 ↓
Pricing Calculation
 ↓
Quotation
 ↓
Quotation Item
```

Every derived artifact MUST preserve source references.

---

# 38. Foreign Key Strategy

Use foreign keys for critical relationships.

Recommended behavior:

```text
ON DELETE RESTRICT
```

for:
- manufacturing history
- approved quotations
- production history
- audit
- released revisions

Safe cascading MAY be used for draft-only dependent records.

Never cascade-delete manufacturing history when a project is deleted.

---

# 39. Indexing Strategy

Common indexes:

```text
(tenant_id, status)
(tenant_id, project_id)
(tenant_id, created_at)
(tenant_id, code)
(tenant_id, public_id)
```

Important examples:

```text
projects: tenant_id + status
rooms: tenant_id + floor_id
design_objects: tenant_id + room_id
furniture_instances: tenant_id + project_id
bom_headers: tenant_id + project_revision_id
panels: tenant_id + manufacturing_revision_id
production_jobs: tenant_id + status
notifications: tenant_id + user_id + read_at
audit_logs: tenant_id + entity_type + entity_id
```

Indexes MUST be based on actual query patterns. Do not index every column.

---

# 40. Tenant Security

Every protected repository/query MUST apply tenant scope.

Example:

```sql
SELECT *
FROM projects
WHERE id = :id
  AND tenant_id = :tenant_id;
```

A client-supplied `tenant_id` MUST NOT be trusted.

IDOR testing is mandatory.

---

# 41. Transactions

Database transactions are mandatory for:

- project creation + initial revision
- furniture creation
- BOM generation
- BOQ generation
- quotation creation
- manufacturing release
- production state transition
- QC completion
- package closure
- dispatch creation
- sequence allocation

---

# 42. Manufacturing Release Transaction

Transaction sequence:

```text
BEGIN
 ↓
Lock/read current revision
 ↓
Verify approvals
 ↓
Verify engineering validation
 ↓
Verify no BLOCKER errors
 ↓
Create manufacturing snapshot
 ↓
Create release record
 ↓
Update manufacturing revision
 ↓
COMMIT
```

Failure MUST rollback all steps.

---

# 43. Production Transition Transaction

```text
BEGIN
 ↓
Read current state
 ↓
Validate transition
 ↓
Validate permission
 ↓
Update current status
 ↓
Insert production event
 ↓
Update panel state where applicable
 ↓
COMMIT
```

---

# 44. Optimistic Locking

Critical mutable tables SHOULD contain:

```text
version INT NOT NULL DEFAULT 1
```

Update pattern:

```sql
UPDATE entity
SET version = version + 1
WHERE id = :id
AND version = :expected_version;
```

If affected rows = 0:

```text
REVISION_CONFLICT
```

---

# 45. Stale Derived Data

Derived tables SHOULD include:

```text
source_hash
source_revision_id
status
```

Example:

```text
BOM status = GENERATED
```

When furniture changes:

```text
BOM status = STALE
BOQ status = STALE
Cutlist status = STALE
Nesting status = STALE
CNC status = STALE
```

Released manufacturing records remain protected; the new design creates a new revision.

---

# 46. Source Hash

Use SHA-256 over canonicalized relevant source data.

Relevant inputs can include:

```text
project revision
furniture revision
template version
material version
manufacturing rules
```

Do not hash non-deterministic JSON ordering.

---

# 47. Historical Snapshot Rules

For approved/released artifacts preserve:

```text
template version
material version
pricing version
design revision
furniture revision
manufacturing revision
calculation snapshot
```

Never assume current master data represents historical data.

---

# 48. Material / Hardware Versioning

Current catalog data can change.

Historical records MUST reference a version or snapshot.

Example:

```text
Material M001
Version 1 → cost ₹100
Version 2 → cost ₹120
```

A quotation using Version 1 MUST remain based on ₹100.

---

# 49. Quotation History

When a quotation is approved:

- quotation header becomes immutable
- quotation items retain snapshot values
- pricing calculation snapshot remains
- source revision remains referenced

Any commercial change creates a new quotation version.

---

# 50. Manufacturing History

When manufacturing is released:

- manufacturing revision becomes immutable
- panels are linked to the release
- cutlist is linked
- nesting is linked
- CNC output is linked
- template/material versions are preserved

A later design change MUST create a new manufacturing revision.

---

# 51. Production Event Immutability

`production_events` MUST be append-only.

If a mistake occurs:

```text
CORRECTION
```

should be represented as a new event rather than editing historical events.

---

# 52. Audit Immutability

Audit records MUST NOT be editable from normal application paths.

---

# 53. Database Security

Required:

- parameterized SQL
- least-privilege DB account
- no root runtime account
- encrypted connections in production where supported
- no credentials in source control
- safe backups
- tenant isolation
- authorization checks
- input validation

Environment variables:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

---

# 54. File Storage

Binary files should be stored in filesystem/object storage.

Database stores metadata:

```text
storage_provider
storage_key
mime_type
size
checksum
```

Do not store large images/PDF/CNC files directly in normal relational columns.

---

# 55. Data Retention

Retention should be configurable for:

- audit logs
- production events
- documents
- notifications
- background jobs
- AI results

Manufacturing traceability should be retained according to business/legal requirements.

---

# 56. Backup

Production MUST have:

- automated database backups
- off-server backup
- retention policy
- restore testing

A backup is not considered valid until restoration has been tested.

---

# 57. Performance

The database implementation MUST support:

- server-side pagination
- indexed tenant queries
- batched reads
- avoidance of N+1 queries
- query monitoring
- slow query logging
- appropriate connection pooling/configuration

Use `EXPLAIN` for expensive queries.

---

# 58. Reporting

Initial reports may query transactional tables.

Recommended views:

```text
v_project_summary
v_material_consumption
v_manufacturing_summary
v_production_dashboard
v_qc_summary
```

Heavy analytics can later move to summary tables/data warehouse without changing core transactional relationships.

---

# 59. Seed Data

Seed:

- system permissions
- default roles
- material categories
- status values
- production stages
- document types
- feature flags
- default tenant configuration

---

# 60. Demo Fixture

Development MUST provide a repeatable fixture containing:

```text
1 Tenant
1 Admin
1 Designer
1 Engineer
1 Estimator
1 Production User
1 Client
1 Project
1 Building
1 Floor
2 Rooms
1 Wardrobe
1 Kitchen Base Unit
5 Boards
3 Laminates
3 Edge Bands
10 Hardware Items
1 BOM
1 BOQ
1 Quotation
1 Manufacturing Revision
1 Production Job
```

This fixture becomes the baseline for integration tests.

---

# 61. Database Test Requirements

Tests MUST cover:

### Tenant
- Tenant A cannot access Tenant B
- cross-tenant foreign references rejected

### RBAC
- unauthorized user cannot modify protected entities

### Revisions
- new furniture revision does not overwrite old revision
- old BOM remains available

### Pricing
- approved quotation remains unchanged after rate changes

### Manufacturing
- release blocked by BLOCKER
- released revision cannot be modified

### Production
- invalid state transition rejected
- event inserted atomically with state change

### QR
- invalid QR cannot resolve private data

### Transactions
- failed release leaves no partial records

---

# 62. Database Documentation

Repository MUST contain:

```text
/docs/database/
  database-overview.md
  erd.md
  table-catalog.md
  naming-conventions.md
  migration-guide.md
  indexing-guide.md
  data-retention.md
  backup-restore.md
```

For every table document:

```text
Purpose
Columns
Data types
Primary key
Foreign keys
Indexes
Unique constraints
Tenant scope
Lifecycle
Revision behavior
Retention
```

---

# 63. Recommended Migration Order

```text
001 tenants
002 tenant_settings
003 tenant_domains
004 tenant_branding

005 users
006 roles
007 permissions
008 role_permissions
009 user_roles
010 user_sessions

011 leads
012 clients
013 client_contacts
014 opportunities

015 projects
016 project_users
017 buildings
018 floors
019 rooms
020 project_revisions

021 design_layers
022 design_objects
023 design_object_relations
024 design_snapshots

025 furniture_templates
026 furniture_template_versions
027 furniture_template_parameters
028 furniture_template_components
029 furniture_template_rules

030 furniture_instances
031 furniture_revisions
032 furniture_components
033 furniture_hardware

034 material_categories
035 materials
036 material_versions
037 boards
038 laminates
039 edge_bands
040 hardware
041 hardware_versions
042 catalog_imports
043 catalog_import_rows

044 pricing_rule_sets
045 pricing_rules
046 pricing_versions
047 bom_headers
048 bom_items
049 boq_headers
050 boq_items
051 pricing_calculations
052 pricing_calculation_lines
053 quotations
054 quotation_items
055 proposals

056 manufacturing_revisions
057 manufacturing_snapshots
058 engineering_validations
059 engineering_validation_results
060 manufacturing_components
061 panels
062 panel_edge_bands
063 panel_operations
064 cutlists
065 cutlist_items
066 nesting_jobs
067 nesting_sheets
068 nesting_placements
069 cnc_machines
070 cnc_jobs
071 cnc_operations
072 manufacturing_releases

073 production_jobs
074 production_operations
075 production_events
076 workstations
077 qr_codes
078 qr_scan_events

079 qc_checklist_templates
080 qc_inspections
081 qc_results
082 qc_defects

083 packages
084 package_items
085 dispatches
086 dispatch_packages
087 installation_jobs

088 files
089 document_templates
090 documents
091 notifications
092 jobs
093 job_events
094 ai_jobs
095 ai_proposed_objects
096 approval_requests
097 comments
098 audit_logs
099 activity_logs
100 feature_flags
101 numbering_sequences
```

Exact numbering may be adjusted to match the migration framework.

---

# 64. MVP Database Priority

## P0

```text
tenants
users
roles
permissions
role_permissions
user_roles

clients
projects
buildings
floors
rooms
project_revisions

design_layers
design_objects

furniture_templates
furniture_template_versions
furniture_template_parameters
furniture_template_components
furniture_template_rules
furniture_instances
furniture_revisions
furniture_components

material_categories
materials
boards
laminates
edge_bands
hardware

bom_headers
bom_items
boq_headers
boq_items
pricing_rule_sets
pricing_rules
pricing_versions
pricing_calculations
pricing_calculation_lines
quotations
quotation_items

manufacturing_revisions
engineering_validations
engineering_validation_results
manufacturing_components
panels
panel_edge_bands
cutlists
cutlist_items
nesting_jobs
nesting_sheets
nesting_placements
```

## P1

```text
cnc_machines
cnc_jobs
cnc_operations
manufacturing_releases

production_jobs
production_operations
production_events
workstations
qr_codes
qr_scan_events

qc_checklist_templates
qc_inspections
qc_results
qc_defects

packages
package_items
dispatches
dispatch_packages
installation_jobs
```

## P2/P3

```text
AI
advanced analytics
advanced collaboration
external machine integrations
advanced client portal
```

---

# 65. Cursor Database Analysis Requirement

Before modifying the existing database, Cursor MUST inspect:

```text
existing schema
existing migrations
existing SQL
existing models
existing repositories
existing API queries
existing foreign keys
existing indexes
existing seeders
existing fixtures
```

Cursor MUST first produce:

```text
CURRENT SCHEMA
TARGET SCHEMA
DUPLICATES
MISSING TABLES
MISSING RELATIONSHIPS
MISSING INDEXES
TENANT-ISOLATION RISKS
HISTORICAL-DATA RISKS
MIGRATION PLAN
```

Do not immediately rewrite the schema.

---

# 66. Cursor Implementation Rules

For every new table:

```text
Purpose
 ↓
Columns
 ↓
Primary Key
 ↓
Foreign Keys
 ↓
Unique Constraints
 ↓
Indexes
 ↓
Tenant Scope
 ↓
Lifecycle
 ↓
Revision Behavior
 ↓
Migration
 ↓
Seed Data
 ↓
Tests
```

For every derived table:

```text
Source
Source Revision
Source Hash
Status
Generated At
Generated By
```

For every released artifact:

```text
Immutable Snapshot
+
Revision Reference
+
Audit Trail
```

---

# 67. Cursor Prohibited Database Patterns

Cursor MUST NOT:

- store passwords in plaintext
- use FLOAT/DOUBLE for money
- put the entire application into one JSON table
- use JSON for frequently queried relational data
- create cross-tenant references
- delete released manufacturing history
- modify approved quotations in place
- use root DB credentials
- commit DB credentials
- bypass migrations
- create unbounded queries
- use unnecessary `SELECT *`
- blindly add indexes
- rely only on client-supplied tenant IDs
- use complex business workflows inside triggers without approval
- silently destroy existing production data

---

# 68. Definition of Database Done

A database feature is complete only when:

- schema implemented
- migration implemented
- primary keys defined
- foreign keys reviewed
- tenant isolation enforced
- indexes implemented
- unique constraints implemented
- repository implemented
- validation implemented
- transaction boundaries implemented
- tests added
- seed/fixture updated
- ERD updated
- table documentation updated

---

# 69. Final Architectural Data Model

The database must support:

```text
                 PROJECT
                    │
             PROJECT REVISION
                    │
              ┌─────┴─────┐
              │           │
            ROOMS     DESIGN OBJECTS
              │
       FURNITURE INSTANCE
              │
       FURNITURE REVISION
              │
       TEMPLATE VERSION
              │
       FURNITURE COMPONENT
              │
     MANUFACTURING COMPONENT
              │
            PANELS
              │
       ┌──────┼─────────┐
       │      │         │
    CUTLIST NESTING    CNC
       │      │         │
       └──────┴─────────┘
              │
        PRODUCTION JOB
              │
      PRODUCTION EVENTS
              │
              QC
              │
           PACKAGES
              │
          DISPATCH
```

Commercial:

```text
FURNITURE
    ↓
   BOM
    ↓
   BOQ
    ↓
PRICING CALCULATION
    ↓
 QUOTATION
```

---

# 70. Final Database Principle

The platform must not become a collection of unrelated copies of data.

The intended model is:

```text
ONE AUTHORITATIVE PROJECT MODEL
             ↓
     VERSIONED DESIGN MODEL
             ↓
     PARAMETRIC FURNITURE
             ↓
       DERIVED COMMERCIAL
             ↓
      DERIVED MANUFACTURING
             ↓
       FACTORY TRACEABILITY
```

A change such as:

```text
Wardrobe Width
2400 → 2700
```

must be able to identify:

```text
Design        → changed
3D            → changed
BOM           → stale
BOQ           → stale
Pricing       → stale
Cutlist       → stale
Nesting       → stale
CNC           → stale
Released MFG  → protected
```

The database is successful when it preserves this lineage and history without duplicating uncontrolled sources of truth.

---

# 71. Final Cursor Instruction

Treat this document as the **Database Specification baseline**.

Do not implement tables in isolation.

For every entity, ask:

```text
Who owns it?
Which tenant owns it?
What is its lifecycle?
What is its source?
What revision does it belong to?
What depends on it?
What happens if it changes?
What must remain immutable?
What must be auditable?
How is it queried?
How is it authorized?
How is it tested?
```

The database must enable the product's central promise:

> **Design once, derive everything, preserve every important revision, trace every manufacturing artifact back to its source, and prevent historical production/commercial data from being silently changed.**
