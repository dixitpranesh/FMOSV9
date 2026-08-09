# Material & Catalog Specification
## Interior Design, Parametric Furniture, Manufacturing & MES Platform

**Document ID:** MAT-CAT-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP/JavaScript Developers, CAD/BIM Engineers, Furniture Engineers, Procurement, Catalog Administrators, Manufacturing Engineers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Unit:** millimetres (mm), square metres (m²), linear metres (m), cubic metres (m³)  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the **Material & Catalog subsystem** of an end-to-end interior design, furniture engineering, manufacturing and MES platform.

The subsystem must provide a centralized, versioned, tenant-aware catalog for:

- Boards
- MDF
- HDF
- Plywood
- Particle board
- Laminates
- Veneers
- Acrylic
- PET
- PVC
- Glass
- Stone
- Quartz
- Solid surfaces
- Metals
- Aluminium profiles
- Edge bands
- Hardware
- Adhesives
- Fasteners
- Furniture accessories
- Appliances
- Sanitary products
- Lighting
- Tiles
- Flooring
- Paints
- Fabrics
- Decorative materials
- Profiles
- Manufacturing consumables
- 3D assets
- Textures
- Product variants
- Supplier products
- Pricing
- Stock/availability metadata

The catalog is not merely an e-commerce product list.

It is a **design-engineering-manufacturing master-data system**.

---

# 2. Core Product Principle

A catalog item must be capable of driving:

```text
Design
  ↓
BIM / 3D
  ↓
Parametric Furniture
  ↓
BOM
  ↓
BOQ
  ↓
Pricing
  ↓
Engineering
  ↓
Cutlist
  ↓
Nesting
  ↓
CNC / Machine Operations
  ↓
MES
```

Therefore catalog data MUST distinguish between:

```text
Commercial Product
Material Definition
Manufacturing Specification
Visual Representation
Pricing
Supplier Data
Inventory Metadata
```

Do not store all of these as unstructured JSON only.

---

# 3. Material vs Catalog Product

The system must distinguish:

## Material

A physical material specification.

Example:

```text
18 mm MDF
Density: 720 kg/m³
Sheet Size: 2440 × 1220 mm
```

## Catalog Product

A commercially identifiable product.

Example:

```text
Greenlam Laminate
Code: GL-1234
Finish: Matte
Color: Walnut
```

## SKU

A purchasable/stockable variant.

Example:

```text
GL-1234-2440-1220
```

## Supplier Product

Supplier-specific commercial representation.

Example:

```text
Supplier A
SKU: SUP-456
Rate: ₹1250
```

---

# 4. Catalog Hierarchy

Recommended:

```text
Tenant
 └── Catalog
      ├── Category
      │    └── Subcategory
      │         └── Product
      │              └── Variant
      │                   └── SKU
      │                        └── Supplier Mapping
      └── Material Library
```

---

# 5. Catalog Categories

Minimum categories:

```text
BOARD
LAMINATE
VENEER
EDGE_BAND
HARDWARE
FASTENER
ADHESIVE
GLASS
STONE
QUARTZ
SOLID_SURFACE
METAL
ALUMINIUM
PROFILE
TILE
FLOORING
PAINT
FABRIC
LIGHTING
APPLIANCE
SANITARY
ACCESSORY
CONSUMABLE
3D_ASSET
TEXTURE
```

The system must support tenant-defined categories.

---

# 6. Category Definition

Category fields:

```text
id
tenant_id
parent_id
name
code
description
icon
sort_order
status
attribute_schema
created_by
updated_by
created_at
updated_at
```

---

# 7. Category Attribute Schema

Each category may define attributes.

Example:

```text
BOARD
 ├── thickness
 ├── length
 ├── width
 ├── density
 ├── core_type
 ├── moisture_resistance
 ├── fire_rating
 ├── grain
 └── finish
```

---

# 8. Product Types

Each category can define product types.

Example:

```text
BOARD
 ├── MDF
 ├── HDF
 ├── PLYWOOD
 ├── PARTICLE_BOARD
 └── BLOCKBOARD
```

---

# 9. Product Master

Product master:

```text
id
tenant_id
category_id
subcategory_id
brand_id
manufacturer_id
product_code
name
description
status
product_type
default_unit
metadata
created_by
updated_by
created_at
updated_at
```

---

# 10. Product Status

Support:

```text
DRAFT
ACTIVE
INACTIVE
DISCONTINUED
ARCHIVED
PENDING_APPROVAL
```

---

# 11. Product Code

Every catalog product should have:

```text
internal_code
manufacturer_code
supplier_code
```

They must not be treated as interchangeable.

---

# 12. SKU

A product can have multiple SKUs.

Example:

```text
Product:
18mm MDF White

Variants:
2440 × 1220
2750 × 1830
```

Each variant can have a separate SKU.

---

# 13. Variant Model

Variant fields:

```text
id
product_id
sku
variant_name
dimensions
thickness
finish
color
pack_size
unit
weight
status
```

---

# 14. Brand Master

Brand:

```text
id
name
code
logo
website
description
status
```

Examples may include:

```text
Greenlam
Merino
CenturyPly
Greenply
Hettich
Blum
Häfele
```

The catalog must not hard-code brands.

---

# 15. Manufacturer Master

Manufacturer fields:

```text
id
name
code
country
website
contact
status
```

---

# 16. Supplier Master

Supplier:

```text
id
tenant_id
name
code
GST/tax identifier where legally required
contact
address
currency
payment_terms
status
```

---

# 17. Supplier Product Mapping

Mapping:

```text
catalog_product_id
supplier_id
supplier_sku
supplier_name
purchase_unit
purchase_price
currency
minimum_order_quantity
lead_time
availability
```

---

# 18. Material Definition

Material fields:

```text
id
product_id
material_type
base_material
density
thickness
length
width
grain_direction
surface_finish
physical_properties
manufacturing_properties
```

---

# 19. Board Materials

Board-specific fields:

```text
board_type
core_material
thickness
sheet_length
sheet_width
density
weight
moisture_resistance
fire_rating
screw_holding
machinability
```

---

# 20. Board Types

Support:

```text
MDF
HDF
HMR
Particle Board
Plywood
Marine Plywood
Blockboard
OSB
Solid Wood Panel
Other
```

---

# 21. Sheet Dimensions

Store standard sheet sizes:

```text
length
width
thickness
```

Example:

```text
2440 × 1220 × 18 mm
```

Do not encode dimensions only inside product names.

---

# 22. Multiple Sheet Sizes

A product may support:

```text
2440 × 1220
2750 × 1830
3050 × 1220
```

Each is a variant/SKU.

---

# 23. Density

Board material MAY specify:

```text
density kg/m³
```

Used for:

```text
weight
logistics
structural calculations
pricing
```

---

# 24. Weight Calculation

If density exists:

```text
weight =
length × width × thickness × density
```

with correct unit conversion.

---

# 25. Moisture Properties

Support:

```text
standard
moisture resistant
water resistant
marine
exterior
```

Use configurable classifications.

---

# 26. Fire Properties

Support:

```text
fire rating
reaction to fire
flame spread
```

Do not assume a product is fire-rated without explicit catalog data.

---

# 27. Laminate Catalog

Laminate product fields:

```text
brand
collection
catalog
code
name
color
pattern
finish
surface_texture
thickness
sheet_size
design_direction
```

---

# 28. Laminate Classification

Support:

```text
Decorative Laminate
Compact Laminate
Back Laminate
High Pressure Laminate
Digital Laminate
Other
```

---

# 29. Laminate Finish

Examples:

```text
Matt
Gloss
Super Matt
Woodgrain
Stone
Fabric
Metallic
Textured
High Gloss
Soft Touch
```

The list must be configurable.

---

# 30. Laminate Color

Store:

```text
color_name
hex_color
rgb
lab
color_family
```

Hex color is only an approximation for digital rendering.

---

# 31. Laminate Pattern

Support:

```text
plain
wood
stone
marble
fabric
metal
abstract
floral
custom
```

---

# 32. Laminate Texture

Store:

```text
texture_asset_id
normal_map_id
roughness_map_id
metalness_map_id
```

---

# 33. Laminate Grain Direction

Support:

```text
NONE
HORIZONTAL
VERTICAL
FIXED
ROTATABLE
```

This affects:

```text
visualization
panel orientation
nesting
cutlist
```

---

# 34. Laminate Sheet Orientation

A laminate product can define:

```text
preferred_orientation
allowed_rotation
```

---

# 35. Veneer

Veneer fields:

```text
species
grade
thickness
sheet_size
grain
finish
backing
```

---

# 36. Edge Band Catalog

Edge band fields:

```text
material
width
thickness
length_per_roll
color
finish
grain
matching_product_id
```

---

# 37. Edge Band Compatibility

Catalog can define:

```text
compatible_board_materials
compatible_thicknesses
compatible_laminates
```

---

# 38. Hardware Catalog

Hardware categories:

```text
HINGE
DRAWER_RUNNER
HANDLE
KNOB
CAM_LOCK
DOWEL
CONFIRMAT
SCREW
CONNECTOR
SHELF_PIN
LIFT_UP
SLIDING_SYSTEM
BASKET
ACCESSORY
PROFILE
```

---

# 39. Hardware Product

Hardware fields:

```text
brand
product_code
name
type
dimensions
finish
load_capacity
opening_angle
compatible_thickness
installation_method
```

---

# 40. Hardware Technical Data

Support:

```text
drilling_pattern
hole_diameter
hole_depth
offset
clearance
mounting_type
```

This information is consumed by the Parametric Furniture Engine.

---

# 41. Hardware Compatibility

Compatibility rules may specify:

```text
panel thickness
door thickness
door width
door height
load
application
```

---

# 42. Hardware Auto-Selection

Furniture rules may request:

```text
HINGE
```

and catalog selection can choose:

```text
compatible hinge SKU
```

based on:

```text
dimensions
weight
opening
brand preference
tenant rules
```

---

# 43. Hardware Alternatives

A product can have:

```text
preferred product
approved alternatives
deprecated alternatives
```

---

# 44. Material Compatibility Matrix

Create a compatibility engine.

Example:

```text
18mm MDF
 ↔
2mm Edge Band = Compatible

18mm MDF
 ↔
6mm Edge Band = Invalid
```

---

# 45. Compatibility Rules

Compatibility may include:

```text
material ↔ edge band
material ↔ hardware
material ↔ adhesive
material ↔ finish
material ↔ machining
material ↔ machine
```

---

# 46. Manufacturing Compatibility

Catalog products can define:

```text
cuttable
drillable
routable
edge_bandable
CNC_supported
laser_supported
```

---

# 47. Machine Constraints

Material may define:

```text
maximum_cut_length
maximum_cut_width
minimum_panel_size
minimum_thickness
maximum_thickness
```

---

# 48. CNC Compatibility

Product can specify supported operations:

```text
CUT
DRILL
ROUTE
GROOVE
POCKET
HINGE_BORE
```

---

# 49. Material Properties for Nesting

Store:

```text
sheet_length
sheet_width
grain
rotation_allowed
minimum_trim
minimum_part_size
kerf
```

---

# 50. Nesting Rules

Catalog may specify:

```text
grain mandatory
grain optional
rotation allowed
rotation forbidden
```

---

# 51. Material Waste Factor

Optional catalog default:

```text
waste_factor
```

Used only for estimation.

Actual nesting waste must come from the nesting engine.

---

# 52. Material Pricing

Material price records:

```text
product_id
variant_id
supplier_id
price
currency
unit
effective_from
effective_to
minimum_quantity
price_type
```

---

# 53. Pricing Units

Support:

```text
per sheet
per m²
per running metre
per piece
per kg
per litre
per roll
per box
```

---

# 54. Effective Pricing

Price must be date-versioned.

Example:

```text
₹1800/sheet
valid:
2026-08-01 → 2026-08-31
```

---

# 55. Price History

Never overwrite historical pricing used in an approved quotation.

Store:

```text
old price
new price
effective date
source
approved by
```

---

# 56. Customer Pricing

Optional:

```text
customer-specific rate
customer group rate
project-specific rate
```

---

# 57. Factory Pricing

Factories may maintain their own:

```text
purchase rate
processing rate
internal cost
```

These must be permission-controlled.

---

# 58. Selling Price

Catalog can define:

```text
base selling price
markup
margin
discount
```

But project quotations should snapshot the price used at the time of quotation.

---

# 59. Currency

Support:

```text
INR
USD
EUR
GBP
AED
```

with extensible currency master.

---

# 60. Tax

Support configurable:

```text
tax category
tax rate
tax-inclusive
tax-exclusive
```

Do not hard-code tax rates into material classes.

---

# 61. Unit of Measure

Create UOM master:

```text
MM
CM
M
SQ_MM
SQ_CM
SQ_M
CU_MM
CU_M
KG
G
L
PCS
SHEET
ROLL
BOX
SET
```

---

# 62. Unit Conversion

Store conversion rules.

Example:

```text
1 m = 1000 mm
1 m² = 1,000,000 mm²
```

The engine must never use ambiguous unit conversions.

---

# 63. Catalog Search

Search must support:

```text
name
code
brand
manufacturer
category
subcategory
color
finish
material
supplier SKU
```

---

# 64. Catalog Filters

Support:

```text
category
brand
collection
material
thickness
width
length
finish
color
price
availability
supplier
status
```

---

# 65. Advanced Search

Support:

```text
exact code
partial code
fuzzy name
multi-filter
range filter
```

---

# 66. Catalog Sorting

Support:

```text
relevance
name
brand
price
recently updated
popularity
```

---

# 67. Catalog Views

Provide:

```text
Grid
List
Table
Technical
Visual
```

---

# 68. Product Detail

Product page must display:

```text
Product Name
Brand
Code
Images
Swatches
Technical Specifications
Dimensions
Material
Finish
Available Variants
Pricing
Suppliers
3D Asset
Texture
Compatibility
Manufacturing Properties
Documents
```

---

# 69. Product Thumbnail

Each visual product should support:

```text
thumbnail
primary image
gallery
swatch
```

---

# 70. Image Assets

Image asset fields:

```text
id
product_id
file
type
width
height
mime
sort_order
is_primary
```

---

# 71. Image Types

Support:

```text
PRIMARY
THUMBNAIL
SWATCH
DETAIL
LIFESTYLE
TECHNICAL
INSTALLATION
```

---

# 72. Texture Assets

Texture fields:

```text
base_color
normal
roughness
metalness
height
alpha
```

---

# 73. Real-World Texture Mapping

Store:

```text
physical_width
physical_height
repeat_x
repeat_y
rotation
grain_direction
```

---

# 74. Texture Color Management

The renderer must support consistent color space handling.

Catalog RGB/HEX values should not be treated as physically accurate material appearance.

---

# 75. 3D Product Assets

Catalog products may reference:

```text
GLB
GLTF
```

Asset metadata:

```text
dimensions
origin
scale
orientation
lod
polycount
```

---

# 76. 3D Asset Validation

Validate:

```text
file
format
dimensions
materials
textures
polycount
origin
scale
```

---

# 77. Product Documentation

Support documents:

```text
datasheet
installation guide
technical drawing
warranty
catalog PDF
safety document
certification
```

---

# 78. Document Metadata

Store:

```text
document_id
product_id
type
file
version
effective_date
expiry_date
language
```

---

# 79. Certifications

Support:

```text
certificate_type
certificate_number
issuer
issue_date
expiry_date
document
```

---

# 80. Compliance Data

Material/product may contain:

```text
fire rating
VOC
formaldehyde class
water resistance
load rating
certification
```

Do not infer compliance without source data.

---

# 81. Brand Catalog Import

System must support bulk import through:

```text
CSV
XLSX
JSON
API
PDF extraction pipeline
```

---

# 82. Import Staging

Never directly insert external catalog data into production tables.

Pipeline:

```text
Upload
 ↓
Staging
 ↓
Parse
 ↓
Normalize
 ↓
Validate
 ↓
Duplicate Detection
 ↓
Mapping
 ↓
Approval
 ↓
Publish
```

---

# 83. Import Staging Tables

Recommended:

```text
catalog_import_jobs
catalog_import_rows
catalog_import_errors
catalog_import_mappings
```

---

# 84. Import Job

Store:

```text
job_id
tenant_id
source
file
format
status
total_rows
processed_rows
successful_rows
failed_rows
started_at
completed_at
```

---

# 85. Import Validation

Validate:

```text
required fields
code uniqueness
UOM
dimensions
numeric values
brand mapping
category mapping
image references
```

---

# 86. Duplicate Detection

Possible duplicate keys:

```text
brand + manufacturer_code
supplier + supplier_sku
tenant + internal_code
```

---

# 87. Duplicate Handling

Options:

```text
SKIP
UPDATE
CREATE_NEW
MERGE
REVIEW
```

Do not silently overwrite catalog records.

---

# 88. Catalog Merge

Merge must preserve:

```text
existing product ID
historical usage
price history
references
```

---

# 89. PDF Catalog Extraction

The system may support extraction from manufacturer catalogs.

Pipeline:

```text
PDF
 ↓
Page Extraction
 ↓
OCR if required
 ↓
Product Code Detection
 ↓
Product Name Detection
 ↓
Specification Extraction
 ↓
Image Extraction
 ↓
Human Review
 ↓
Publish
```

---

# 90. OCR

OCR results must be marked:

```text
machine_extracted
```

until verified.

---

# 91. Extraction Confidence

Store:

```text
confidence_score
```

per extracted field where feasible.

---

# 92. Human Review

Low-confidence records must enter:

```text
REVIEW_REQUIRED
```

---

# 93. Catalog Source

Every product should have:

```text
source_type
source_reference
source_document
source_url
source_date
```

Examples:

```text
MANUFACTURER
SUPPLIER
INTERNAL
IMPORTED
USER_CREATED
AI_GENERATED
```

---

# 94. Source Traceability

Catalog data should be traceable back to:

```text
source file
page
supplier
manufacturer
import job
```

---

# 95. AI Catalog Extraction

AI can assist in extracting:

```text
product code
name
category
finish
dimensions
color
technical specifications
```

AI output must be treated as:

```text
DRAFT
```

until validated.

---

# 96. AI Catalog Normalization

AI may suggest:

```text
category mapping
attribute mapping
duplicate matches
color family
finish classification
```

Human approval remains available.

---

# 97. Catalog Governance

Roles may include:

```text
Catalog Administrator
Material Engineer
Procurement Manager
Factory Engineer
Designer
Viewer
```

---

# 98. Catalog Permissions

Minimum permissions:

```text
catalog.view
catalog.create
catalog.edit
catalog.delete
catalog.import
catalog.approve
catalog.publish
catalog.archive
catalog.price.view
catalog.price.edit
catalog.supplier.manage
catalog.asset.manage
```

---

# 99. Price Permissions

Separate:

```text
catalog.price.view
catalog.cost.view
catalog.margin.view
catalog.price.edit
```

---

# 100. Client Catalog Access

Client users should see only:

```text
published products
client-visible attributes
client-visible pricing
```

They must not see:

```text
supplier cost
internal margin
procurement notes
internal machine constraints
```

---

# 101. Catalog Lifecycle

Product lifecycle:

```text
DRAFT
REVIEW
APPROVED
PUBLISHED
DEPRECATED
ARCHIVED
```

---

# 102. Publishing Rules

A product cannot be published if required fields are missing.

Example:

```text
Board:
thickness required
sheet size required
material type required
```

---

# 103. Product Versioning

Product specifications can change.

Examples:

```text
Finish changed
Sheet size changed
Technical specification changed
```

Version these changes.

---

# 104. Catalog Snapshot

Projects must be able to snapshot catalog data.

When a project is approved, store:

```text
product version
price version
material version
```

---

# 105. Historical Accuracy

Changing the current product must not alter:

```text
old quotations
old BOMs
old manufacturing releases
old invoices
```

---

# 106. Material Assignment

Parametric furniture can reference:

```text
material_product_id
material_variant_id
```

not only free-text names.

---

# 107. Material Assignment Precedence

Recommended:

```text
Project Override
 ↓
Furniture Instance Override
 ↓
Furniture Template Rule
 ↓
Category Default
 ↓
Tenant Default
```

---

# 108. Material Substitution

Support approved substitutions.

Example:

```text
Primary:
18mm MDF Brand A

Approved Alternative:
18mm MDF Brand B
```

---

# 109. Substitution Rules

Substitution must verify:

```text
thickness
dimensions
density
machinability
finish
grain
cost
manufacturing compatibility
```

---

# 110. Substitution Approval

A substitution can be:

```text
AUTO_ALLOWED
REQUIRES_APPROVAL
FORBIDDEN
```

---

# 111. Material Equivalence

Do not define products as equivalent based only on:

```text
same thickness
```

Equivalence may require:

```text
material type
performance
dimensions
finish
manufacturing
approved supplier
```

---

# 112. Catalog Collections

Support collections:

```text
Wood Collection
Stone Collection
2026 Laminate Collection
Premium Hardware
Kitchen Essentials
```

---

# 113. Catalog Tags

Tags:

```text
premium
budget
new
popular
sustainable
water-resistant
fire-rated
```

---

# 114. Product Attributes

Support extensible custom attributes.

Example:

```text
attribute_name
value
unit
data_type
```

But important engineering attributes must remain strongly typed columns where required for performance and validation.

---

# 115. Attribute Data Types

```text
TEXT
NUMBER
BOOLEAN
ENUM
MATERIAL
DIMENSION
COLOR
DATE
REFERENCE
```

---

# 116. Search Index

For large catalogs, create a searchable representation.

Search fields:

```text
name
code
brand
collection
category
tags
technical fields
```

---

# 117. Catalog API

```http
GET    /api/v1/catalog/products
POST   /api/v1/catalog/products
GET    /api/v1/catalog/products/{id}
PATCH  /api/v1/catalog/products/{id}
DELETE /api/v1/catalog/products/{id}
```

---

# 118. Categories API

```http
GET  /api/v1/catalog/categories
POST /api/v1/catalog/categories
PATCH /api/v1/catalog/categories/{id}
```

---

# 119. Brands API

```http
GET  /api/v1/catalog/brands
POST /api/v1/catalog/brands
PATCH /api/v1/catalog/brands/{id}
```

---

# 120. Suppliers API

```http
GET  /api/v1/catalog/suppliers
POST /api/v1/catalog/suppliers
PATCH /api/v1/catalog/suppliers/{id}
```

---

# 121. Variants API

```http
GET  /api/v1/catalog/products/{id}/variants
POST /api/v1/catalog/products/{id}/variants
PATCH /api/v1/catalog/variants/{id}
```

---

# 122. Material API

```http
GET  /api/v1/catalog/materials
POST /api/v1/catalog/materials
GET  /api/v1/catalog/materials/{id}
PATCH /api/v1/catalog/materials/{id}
```

---

# 123. Compatibility API

```http
POST /api/v1/catalog/compatibility/check
GET  /api/v1/catalog/products/{id}/compatible-products
```

Example request:

```json
{
  "product_id": "MDF-18-001",
  "target_type": "EDGE_BAND",
  "target_product_id": "EB-002"
}
```

---

# 124. Pricing API

```http
GET  /api/v1/catalog/products/{id}/prices
POST /api/v1/catalog/products/{id}/prices
PATCH /api/v1/catalog/prices/{id}
```

---

# 125. Supplier Mapping API

```http
GET  /api/v1/catalog/products/{id}/suppliers
POST /api/v1/catalog/products/{id}/suppliers
PATCH /api/v1/catalog/supplier-mappings/{id}
```

---

# 126. Asset API

```http
GET  /api/v1/catalog/products/{id}/assets
POST /api/v1/catalog/products/{id}/assets
DELETE /api/v1/catalog/assets/{id}
```

---

# 127. Import API

```http
POST /api/v1/catalog/imports
GET  /api/v1/catalog/imports/{id}
GET  /api/v1/catalog/imports/{id}/errors
POST /api/v1/catalog/imports/{id}/validate
POST /api/v1/catalog/imports/{id}/approve
POST /api/v1/catalog/imports/{id}/publish
```

---

# 128. Export API

```http
GET /api/v1/catalog/export/products
GET /api/v1/catalog/export/materials
GET /api/v1/catalog/export/hardware
```

---

# 129. Database Tables

Minimum tables:

```text
catalog_categories
catalog_brands
catalog_manufacturers
catalog_suppliers
catalog_products
catalog_product_versions
catalog_product_variants
catalog_materials
catalog_material_properties
catalog_attributes
catalog_attribute_values
catalog_product_attributes
catalog_product_assets
catalog_textures
catalog_documents
catalog_certifications
catalog_supplier_products
catalog_prices
catalog_price_history
catalog_compatibility_rules
catalog_substitutions
catalog_collections
catalog_tags
catalog_product_tags
catalog_import_jobs
catalog_import_rows
catalog_import_errors
catalog_import_mappings
catalog_audit_logs
catalog_uom
catalog_currency
```

---

# 130. Product Table

Recommended fields:

```text
id
tenant_id
category_id
subcategory_id
brand_id
manufacturer_id
internal_code
product_code
name
description
status
default_uom
source_type
source_reference
metadata_json
created_by
updated_by
created_at
updated_at
```

---

# 131. Product Version Table

Fields:

```text
id
product_id
version
status
specification_json
effective_from
effective_to
created_by
approved_by
published_at
```

---

# 132. Variant Table

Fields:

```text
id
product_id
sku
variant_name
length
width
height
thickness
finish
color
weight
pack_quantity
uom
status
```

---

# 133. Material Table

Fields:

```text
id
product_id
material_type
base_material
density
grain_direction
machinability
moisture_class
fire_class
properties_json
```

---

# 134. Price Table

Fields:

```text
id
product_id
variant_id
supplier_id
price
currency
uom
price_type
minimum_quantity
effective_from
effective_to
status
```

---

# 135. Compatibility Rule Table

Fields:

```text
id
tenant_id
source_product_id
target_product_id
rule_type
conditions_json
result
priority
status
```

---

# 136. Asset Table

Fields:

```text
id
product_id
asset_type
file_path
mime_type
file_size
width
height
metadata_json
is_primary
status
```

---

# 137. Texture Table

Fields:

```text
id
product_id
base_color_asset_id
normal_asset_id
roughness_asset_id
metalness_asset_id
physical_width
physical_height
rotation
grain_direction
```

---

# 138. Catalog Audit

Audit:

```text
product_created
product_updated
price_changed
material_changed
asset_added
supplier_mapping_changed
product_published
product_archived
import_completed
compatibility_changed
```

---

# 139. Audit Fields

```text
user_id
tenant_id
entity_type
entity_id
action
old_value
new_value
timestamp
source
```

---

# 140. Tenant Isolation

Every catalog query MUST enforce:

```sql
tenant_id = authenticated_tenant_id
```

Never trust:

```text
tenant_id
```

from the browser.

---

# 141. Global vs Tenant Catalog

Support:

```text
SYSTEM
TENANT
FACTORY
PROJECT
```

catalog scopes.

---

# 142. Catalog Visibility

Products may be:

```text
PRIVATE
TENANT_VISIBLE
FACTORY_VISIBLE
PROJECT_VISIBLE
PUBLIC_CATALOG
```

---

# 143. Catalog Sharing

A tenant may clone/import a system product.

The clone should preserve:

```text
source_product_id
source_version
```

but become tenant-controlled.

---

# 144. Catalog Dependency

Furniture templates may depend on catalog products.

Store dependency references:

```text
template → material
template → hardware
template → edge band
template → asset
```

---

# 145. Dependency Integrity

If a product is archived:

```text
existing projects remain valid
new design usage may be blocked
```

---

# 146. Archived Product

Archived product must remain readable in:

```text
old projects
old BOMs
old BOQs
old manufacturing records
```

but cannot normally be selected for new designs.

---

# 147. Product Replacement

Provide:

```text
Replace Product
```

workflow:

```text
Old Product
 ↓
Candidate Alternatives
 ↓
Compatibility Check
 ↓
Impact Analysis
 ↓
Approval
 ↓
Replace
```

---

# 148. Impact Analysis

Before replacing a material show:

```text
Projects affected
Furniture templates affected
Furniture instances affected
BOMs affected
Pricing affected
Manufacturing rules affected
```

---

# 149. Catalog-to-Furniture Integration

Furniture Engine must request catalog data through a service interface.

Example:

```javascript
materialCatalog.getMaterial(materialId)
```

Do not duplicate the entire catalog inside furniture templates.

---

# 150. Catalog-to-BIM Integration

BIM material assignment should reference:

```text
catalog_product_id
catalog_product_version
```

---

# 151. Catalog-to-3D Integration

Catalog product may provide:

```text
base color
PBR textures
GLB asset
dimensions
```

3D renderer uses these for visualization.

---

# 152. Catalog-to-Manufacturing Integration

Manufacturing uses:

```text
material type
thickness
sheet size
grain
machining compatibility
edge band compatibility
machine compatibility
```

---

# 153. Catalog-to-Pricing Integration

Pricing engine uses:

```text
product
variant
supplier
price version
UOM
quantity
currency
```

---

# 154. Catalog-to-MES Integration

MES may use:

```text
material SKU
supplier SKU
batch/lot
purchase source
production material
```

---

# 155. Batch / Lot Tracking

For manufacturing materials, optional:

```text
batch_number
lot_number
received_date
expiry_date
supplier
```

---

# 156. Inventory Integration Readiness

Do not implement full inventory inside catalog unless required.

Catalog should expose:

```text
availability
stock_status
warehouse
lead_time
```

through integration interfaces.

---

# 157. Stock Status

```text
IN_STOCK
LOW_STOCK
OUT_OF_STOCK
ON_ORDER
DISCONTINUED
UNKNOWN
```

---

# 158. Procurement Integration

Supplier product records may contain:

```text
MOQ
lead time
purchase UOM
purchase price
supplier SKU
```

---

# 159. Material Approval

Material approval workflow:

```text
Designer Request
 ↓
Material Engineer Review
 ↓
Procurement Review
 ↓
Approved
 ↓
Published
```

Tenant may simplify the workflow.

---

# 160. Material Samples

Support sample records:

```text
sample_id
product_id
physical_location
sample_code
status
```

Optional future feature.

---

# 161. Visual Catalog

Visual catalog should support:

```text
swatches
room application
3D preview
texture preview
```

---

# 162. Product Swatches

Swatch should be linked to product variant.

Do not rely only on product images for color selection.

---

# 163. Material Preview

User can apply material to:

```text
wall
floor
ceiling
furniture panel
shutter
countertop
```

and preview in 3D.

---

# 164. Material Replacement Preview

Allow:

```text
Current Material
→
Candidate Material
```

preview without immediately changing the project.

---

# 165. Material Comparison

Support side-by-side comparison:

```text
Product A
Product B
Product C
```

Compare:

```text
price
finish
dimensions
technical properties
availability
visual
```

---

# 166. Material Recommendation

Future recommendation engine may consider:

```text
room
style
budget
existing materials
availability
manufacturing compatibility
```

Recommendations must remain suggestions.

---

# 167. Catalog Data Quality Score

Each product can have:

```text
data_quality_score
```

based on:

```text
required fields
images
technical specs
pricing
supplier
3D asset
texture
verification
```

---

# 168. Data Quality Status

```text
INCOMPLETE
REVIEW
VERIFIED
HIGH_QUALITY
```

---

# 169. Product Completeness Rules

Example for a board:

```text
[required] material type
[required] thickness
[required] sheet size
[required] UOM
[recommended] density
[recommended] texture
[recommended] 3D
```

---

# 170. Catalog Data Validation

Validation engine must detect:

```text
duplicate code
invalid dimension
invalid UOM
missing category
missing brand
invalid price
invalid currency
broken image
broken texture
invalid material compatibility
```

---

# 171. Data Normalization

Normalize:

```text
units
brand names
finish names
color names
material categories
codes
```

---

# 172. Canonical Names

Example:

Instead of:

```text
18MM MDF
18 mm MDF
MDF 18MM
MDF-18
```

maintain:

```text
Canonical Product
```

with aliases.

---

# 173. Product Aliases

Store:

```text
alias
source
language
```

Used for search/import matching.

---

# 174. Multilingual Catalog

Support:

```text
name
description
finish
color
category
```

translations.

Language-independent product code remains canonical.

---

# 175. Localization

Catalog UI can support:

```text
English
Hindi
Kannada
other tenant languages
```

---

# 176. Regional Catalog

Tenant may define region:

```text
India
UAE
UK
US
```

Products may differ by region.

---

# 177. Regional Availability

Product variant can specify:

```text
country
region
availability
currency
supplier
```

---

# 178. Sustainability Metadata

Optional fields:

```text
recycled_content
FSC
PEFC
low_VOC
formaldehyde_class
environmental_certification
```

Only use verified source data.

---

# 179. Sustainability Filters

Users may filter:

```text
FSC
low VOC
recycled
water resistant
```

---

# 180. Product Recommendation Constraints

Recommendations must respect:

```text
manufacturing compatibility
project availability
budget
design intent
```

Do not recommend an aesthetically suitable but unmanufacturable material.

---

# 181. Catalog Security

Validate uploaded:

```text
images
PDFs
GLB
GLTF
textures
CSV
XLSX
JSON
```

for:

```text
file type
file size
malicious content
path traversal
```

---

# 182. File Storage

Store files outside executable PHP directories.

Use:

```text
private storage
signed URLs
authenticated downloads
```

---

# 183. Import Security

Imported spreadsheets and JSON must be treated as untrusted input.

Validate:

```text
formula injection
malicious paths
oversized values
unexpected types
```

---

# 184. API Security

All catalog APIs require:

```text
authentication
tenant authorization
RBAC
input validation
rate limiting where appropriate
audit logging
```

---

# 185. API Response Format

Example:

```json
{
  "data": {
    "id": "MAT-001",
    "name": "18mm MDF",
    "category": "BOARD",
    "variant": {
      "length": 2440,
      "width": 1220,
      "thickness": 18
    }
  },
  "meta": {
    "version": 3
  }
}
```

---

# 186. API Error Codes

Use structured errors:

```text
CATALOG_PRODUCT_NOT_FOUND
CATALOG_PRODUCT_INACTIVE
CATALOG_DUPLICATE_CODE
CATALOG_INVALID_VARIANT
CATALOG_INVALID_PRICE
CATALOG_COMPATIBILITY_ERROR
CATALOG_IMPORT_ERROR
CATALOG_PERMISSION_DENIED
CATALOG_VERSION_CONFLICT
CATALOG_ASSET_ERROR
```

---

# 187. Concurrency

Product edits must use optimistic concurrency.

Request:

```json
{
  "base_version": 5,
  "changes": {}
}
```

Conflict:

```http
409 Conflict
```

---

# 188. Catalog Change Events

Events:

```text
PRODUCT_CREATED
PRODUCT_UPDATED
PRODUCT_PUBLISHED
PRODUCT_ARCHIVED
PRICE_CHANGED
MATERIAL_CHANGED
ASSET_CHANGED
SUPPLIER_CHANGED
COMPATIBILITY_CHANGED
```

---

# 189. Downstream Impact Events

Example:

```text
MATERIAL_CHANGED
 ↓
Affected Furniture Templates
 ↓
Affected Furniture Instances
 ↓
BOM/BOQ impact
 ↓
Manufacturing impact
```

---

# 190. Stale Data Handling

When a catalog product changes:

```text
Current project
```

may continue using its snapshot.

New designs use the latest version.

---

# 191. Project Catalog Snapshot

Project should be able to record:

```text
catalog product
version
price version
```

when used.

---

# 192. Quotation Snapshot

Quotation must snapshot:

```text
product
product version
price
currency
tax
UOM
```

---

# 193. Manufacturing Snapshot

Manufacturing release must snapshot:

```text
material
variant
thickness
sheet size
grain
machine compatibility
```

---

# 194. Catalog Performance

Target:

```text
100,000+ products
1,000,000+ variants
```

without requiring full-table scans for normal search.

---

# 195. Database Indexing

Index:

```text
tenant_id
category_id
brand_id
manufacturer_id
product_code
sku
status
name
```

Composite indexes should be added based on actual query patterns.

---

# 196. Search Performance

Target:

```text
catalog search < 300 ms
```

for normal indexed searches.

---

# 197. Asset CDN

For large deployments, support:

```text
CDN
image resizing
thumbnail generation
texture delivery
3D asset delivery
```

---

# 198. Thumbnail Generation

Automatically generate:

```text
small
medium
large
```

product images.

---

# 199. Texture Optimization

Generate web-ready texture variants:

```text
512
1024
2048
```

based on product type and use case.

---

# 200. 3D Asset Optimization

Support:

```text
GLB compression
mesh simplification
LOD
texture compression
```

---

# 201. Catalog Administration UI

Main screens:

```text
Catalog Dashboard
Category Manager
Brand Manager
Product Manager
Material Manager
Hardware Manager
Supplier Manager
Pricing Manager
Asset Manager
Import Center
Compatibility Manager
Collections
Approval Queue
Audit Log
```

---

# 202. Product Editor UI

Sections:

```text
Basic Information
Classification
Technical Specifications
Dimensions
Material
Finish
Visual Assets
3D Asset
Pricing
Suppliers
Compatibility
Manufacturing
Documents
Certifications
Version History
```

---

# 203. Material Editor UI

For board:

```text
Material Type
Thickness
Sheet Size
Density
Grain
Moisture Class
Fire Class
Machining
Nesting
Pricing
Texture
```

---

# 204. Hardware Editor UI

Fields:

```text
Brand
Code
Type
Dimensions
Finish
Load
Compatible Thickness
Drilling Pattern
Installation
Pricing
3D Asset
```

---

# 205. Import Center UI

Display:

```text
Import File
Source
Detected Rows
Valid
Warnings
Errors
Duplicates
Mapped Fields
Preview
Approve
Publish
```

---

# 206. Mapping UI

User can map:

```text
CSV Column
→
Catalog Field
```

Example:

```text
"Laminate Code"
→
product_code
```

---

# 207. Import Preview

Before commit:

```text
New Products
Updates
Duplicates
Errors
Warnings
```

---

# 208. Catalog Approval Queue

Show:

```text
Product
Source
Quality Score
Errors
Warnings
Submitted By
Date
```

Actions:

```text
Approve
Reject
Request Changes
```

---

# 209. Product Comparison UI

Compare:

```text
technical
visual
price
availability
manufacturing compatibility
```

---

# 210. Material Selection UI

Furniture designer should provide:

```text
Search
Filters
Visual Swatches
Recent
Favorites
Approved
Compatible
```

---

# 211. Compatible Material Filtering

When selecting material for a furniture component:

```text
show only compatible materials
```

Optionally allow:

```text
show incompatible
```

with reason.

---

# 212. Compatibility Reason

Example:

```text
Not compatible:
Selected board thickness = 25 mm.
Hardware supports maximum = 18 mm.
```

---

# 213. Favorites

Users may favorite:

```text
products
materials
hardware
```

---

# 214. Recently Used

Track:

```text
recent material selections
recent hardware
recent products
```

This is a user preference feature, not authoritative catalog data.

---

# 215. Catalog Recommendations

Recommended products can consider:

```text
recent
approved
popular
compatible
budget
```

---

# 216. No Silent Product Replacement

If an old product is discontinued:

```text
do not silently replace it
```

Show:

```text
Product discontinued.
Approved alternatives available.
```

---

# 217. Product Deletion

Products with references SHOULD NOT be physically deleted.

Use:

```text
ARCHIVED
```

instead.

---

# 218. Referential Integrity

Before archiving:

```text
check active references
```

Show:

```text
affected templates
projects
quotes
manufacturing jobs
```

---

# 219. Catalog Dependency Graph

Maintain relationships:

```text
Product
 ↓
Material
 ↓
Furniture Template
 ↓
Furniture Instance
 ↓
BOM
 ↓
Manufacturing
```

This supports impact analysis.

---

# 220. Example Material Flow

```text
18mm MDF
   ↓
Material Catalog
   ↓
Furniture Template
   ↓
Wardrobe Instance
   ↓
Side Panel
   ↓
BOM
   ↓
Cutlist
   ↓
Nesting
   ↓
Production
```

---

# 221. Example Laminate Flow

```text
Laminate Product
   ↓
Shutter Material Assignment
   ↓
3D Texture
   ↓
Shutter BOM
   ↓
Pricing
   ↓
Manufacturing Finish
```

---

# 222. Example Hardware Flow

```text
Hinge SKU
   ↓
Furniture Rule
   ↓
Hinge Count
   ↓
Hinge Placement
   ↓
Drilling Operations
   ↓
Hardware BOM
   ↓
Assembly
```

---

# 223. Catalog-to-Parametric Engine Contract

Furniture engine requests:

```text
material
hardware
edge band
profile
```

Catalog returns:

```text
identity
technical data
compatibility
pricing
visual assets
manufacturing properties
```

---

# 224. Catalog Must Not Own Furniture Logic

Catalog says:

```text
This hinge supports 18mm–22mm panels.
```

Furniture engine decides:

```text
This wardrobe needs this hinge.
```

Keep domain responsibilities separate.

---

# 225. Catalog Must Not Own Nesting Logic

Catalog says:

```text
Sheet = 2440 × 1220
Grain = Fixed
Rotation = Forbidden
```

Nesting engine decides:

```text
How panels are arranged.
```

---

# 226. Catalog Must Not Own CNC Logic

Catalog says:

```text
Material supports drilling/routing.
```

Machine profile decides:

```text
Which machine and tool path.
```

---

# 227. Data Ownership

```text
Catalog
→ Product master data

Furniture Engine
→ Parametric furniture definition

BIM
→ Project object relationships

Pricing
→ Commercial calculations

Manufacturing
→ Production operations

MES
→ Production execution
```

---

# 228. Testing Strategy

## Unit Tests

Test:

```text
product validation
variant validation
UOM conversion
price selection
compatibility
material resolution
supplier mapping
```

---

# 229. Import Tests

Test:

```text
CSV
XLSX
JSON
PDF extraction
duplicate detection
mapping
validation
```

---

# 230. Catalog-to-Furniture Tests

Verify:

```text
select material
→ furniture updates
→ 3D updates
→ BOM updates
→ manufacturing remains valid
```

---

# 231. Material Compatibility Tests

Example:

```text
18mm board + compatible edge band
→ PASS

18mm board + invalid hardware
→ FAIL
```

---

# 232. Pricing Tests

Verify:

```text
current price
historical price
supplier price
project price
quotation snapshot
```

---

# 233. Version Tests

```text
Product v1
 ↓
Project uses v1
 ↓
Product v2 published
 ↓
Existing project remains v1
 ↓
New project uses v2
```

---

# 234. Archive Tests

Archived product:

```text
cannot be selected for new furniture
```

but:

```text
old project still renders
old BOM remains valid
old manufacturing release remains valid
```

---

# 235. Security Tests

Verify:

```text
Tenant A cannot read Tenant B products
Designer cannot edit catalog without permission
Client cannot view supplier price
Archived product cannot be modified
Supplier cannot see internal margin
```

---

# 236. Performance Tests

Test:

```text
100k products
500k variants
1M variants
large asset libraries
large import files
```

Measure:

```text
search
filter
load
import
publish
price resolution
compatibility
```

---

# 237. Cursor Pre-Implementation Analysis

Before changing the codebase, Cursor MUST inspect:

```text
existing material tables
existing laminate database
existing hardware database
existing product tables
existing asset storage
existing image/texture system
existing pricing engine
existing BOM
existing furniture engine
existing 3D renderer
existing 2D CAD
existing manufacturing system
existing MES
existing RBAC
existing tenant model
existing import scripts
```

Cursor must produce:

```text
CURRENT CATALOG ARCHITECTURE
CURRENT MATERIAL MODEL
CURRENT LAMINATE MODEL
CURRENT HARDWARE MODEL
CURRENT ASSET MODEL
CURRENT PRICING MODEL
CURRENT IMPORT PIPELINE
DUPLICATIONS
DATA QUALITY ISSUES
MIGRATION RISKS
TARGET ARCHITECTURE
MIGRATION PLAN
```

Do not rewrite existing catalog/import functionality before mapping it.

---

# 238. Recommended JavaScript Structure

```text
/src/catalog/

domain/
  CatalogProduct.js
  ProductVariant.js
  Material.js
  Hardware.js
  Supplier.js
  Brand.js

search/
  CatalogSearch.js
  CatalogFilters.js

materials/
  MaterialResolver.js
  CompatibilityEngine.js

pricing/
  CatalogPriceResolver.js

assets/
  CatalogAssetManager.js
  TextureManager.js
  GltfAssetManager.js

import/
  ImportManager.js
  CsvImporter.js
  XlsxImporter.js
  JsonImporter.js
  PdfExtractionAdapter.js

validation/
  CatalogValidator.js
  ProductValidator.js
  CompatibilityValidator.js

state/
  CatalogCache.js
  RecentProducts.js
  Favorites.js
```

---

# 239. Recommended PHP Structure

```text
src/
  Catalog/
    Domain/
    Services/
    Repositories/
    Validators/
    Import/
    Pricing/
    Compatibility/
    Assets/
    Events/
    DTO/
    Policies/
```

Services:

```text
CatalogProductService
CatalogMaterialService
CatalogHardwareService
CatalogSupplierService
CatalogPricingService
CatalogCompatibilityService
CatalogImportService
CatalogAssetService
CatalogVersionService
CatalogPublishService
CatalogImpactService
```

---

# 240. Implementation Sequence

## Phase 1 — Master Data Foundation

```text
UOM
Currency
Category
Brand
Manufacturer
Supplier
```

## Phase 2 — Product Catalog

```text
Products
Variants
Attributes
Versions
```

## Phase 3 — Materials

```text
Boards
Laminates
Veneers
Edge Bands
Finishes
```

## Phase 4 — Hardware

```text
Hinges
Runners
Handles
Connectors
Accessories
```

## Phase 5 — Assets

```text
Images
Swatches
Textures
GLB/GLTF
Documents
```

## Phase 6 — Pricing

```text
Supplier Prices
Price History
Project Prices
```

## Phase 7 — Compatibility

```text
Material Compatibility
Hardware Compatibility
Manufacturing Compatibility
Substitution
```

## Phase 8 — Import

```text
CSV
XLSX
JSON
PDF/OCR
```

## Phase 9 — Furniture Integration

```text
Material Resolver
Hardware Resolver
Pricing Resolver
```

## Phase 10 — Manufacturing Integration

```text
Sheet Specs
Grain
Nesting Constraints
Machine Compatibility
```

## Phase 11 — Governance

```text
Approval
Publishing
Audit
Versioning
Impact Analysis
```

---

# 241. Definition of Done

The Material & Catalog subsystem is complete only when:

```text
[ ] Category master implemented
[ ] Brand master implemented
[ ] Manufacturer master implemented
[ ] Supplier master implemented
[ ] Product master implemented
[ ] Product variants implemented
[ ] Product versioning implemented
[ ] Material master implemented
[ ] Board catalog implemented
[ ] Laminate catalog implemented
[ ] Veneer catalog implemented
[ ] Edge band catalog implemented
[ ] Hardware catalog implemented
[ ] Supplier mappings implemented
[ ] Pricing implemented
[ ] Price history implemented
[ ] UOM implemented
[ ] Currency implemented
[ ] Compatibility engine implemented
[ ] Substitution engine implemented
[ ] Image assets implemented
[ ] Texture assets implemented
[ ] 3D assets implemented
[ ] Product documents implemented
[ ] Certifications implemented
[ ] Catalog import staging implemented
[ ] CSV import implemented
[ ] XLSX import implemented
[ ] JSON import implemented
[ ] PDF extraction pipeline interface implemented
[ ] Duplicate detection implemented
[ ] Import validation implemented
[ ] Product approval implemented
[ ] Product publishing implemented
[ ] Product archival implemented
[ ] Catalog search implemented
[ ] Catalog filters implemented
[ ] Material preview implemented
[ ] Product comparison implemented
[ ] Furniture integration implemented
[ ] BIM integration implemented
[ ] Pricing integration implemented
[ ] Manufacturing integration implemented
[ ] MES metadata integration implemented
[ ] RBAC implemented
[ ] Tenant isolation implemented
[ ] Audit implemented
[ ] Version snapshots implemented
[ ] Impact analysis implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Import tests implemented
[ ] Security tests implemented
[ ] Performance tests completed
```

---

# 242. Final Architecture

The intended system is:

```text
                         CATALOG PLATFORM
                                │
        ┌───────────────────────┼────────────────────────┐
        │                       │                        │
   MASTER DATA             PRODUCTS                 ASSETS
        │                       │                        │
 Category / Brand          Materials                 Images
 Supplier / UOM            Hardware                  Textures
 Currency                  Accessories               3D Models
        │                       │                        │
        └───────────────────────┼────────────────────────┘
                                │
                         VERSIONED CATALOG
                                │
                ┌───────────────┼────────────────┐
                ↓               ↓                ↓
             DESIGN         FURNITURE          BIM
                │               │                │
                └───────────────┼────────────────┘
                                ↓
                            PRICING
                                ↓
                              BOM
                                ↓
                         MANUFACTURING
                                ↓
                        CUTLIST / NESTING
                                ↓
                              CNC
                                ↓
                              MES
```

---

# 243. Most Important Implementation Rule

Do not build the catalog as:

```text
Product Name
+
Price
+
Image
```

It must be an **engineering-grade master data platform**.

For example, an MDF product must be capable of answering:

```text
What is it?
Who manufactures it?
What are its dimensions?
What thicknesses exist?
What is its density?
What is its grain behavior?
What machines can process it?
What operations are supported?
What edge bands are compatible?
What hardware can be used with it?
What is its supplier SKU?
What is its current price?
What was its historical price?
What is its 3D appearance?
What texture should Three.js use?
What projects use it?
What furniture templates use it?
What BOMs use it?
What manufacturing jobs use it?
Which version was used?
```

---

# 244. Final Cursor Instruction

Treat this document as the **Material & Catalog implementation contract**.

Before implementing anything:

1. Analyze the existing repository.
2. Identify existing material/catalog tables.
3. Identify existing laminate records/importers.
4. Identify existing hardware data.
5. Identify existing pricing.
6. Identify existing image/texture assets.
7. Identify existing 3D assets.
8. Identify existing furniture references.
9. Identify existing BOM/BOQ references.
10. Identify existing manufacturing references.
11. Identify existing supplier data.
12. Identify existing RBAC and tenant model.
13. Identify existing imports.
14. Identify duplicate master-data structures.
15. Identify migration risks.
16. Propose the target data model.
17. Produce a migration plan.
18. Implement incrementally.

Do not destroy existing production catalog data.

Do not silently rename or replace product IDs.

Do not silently replace materials used by existing projects.

All historical project, quotation and manufacturing records must remain reproducible.

---

# 245. Final Product Principle

The catalog is the **digital material and product truth layer** of the platform.

A single catalog item should be able to flow through:

```text
CATALOG
   ↓
DESIGN
   ↓
BIM
   ↓
PARAMETRIC FURNITURE
   ↓
3D VISUALIZATION
   ↓
BOM / BOQ
   ↓
PRICING
   ↓
ENGINEERING
   ↓
CUTLIST
   ↓
NESTING
   ↓
CNC
   ↓
PRODUCTION
   ↓
MES
```

Therefore:

> **Material and catalog data must be structured, versioned, traceable, manufacturable, visually representable and commercially usable—not merely stored as product names and images.**
