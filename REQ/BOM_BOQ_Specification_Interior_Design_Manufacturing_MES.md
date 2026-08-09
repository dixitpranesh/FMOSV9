# BOM / BOQ Specification
## End-to-End Interior Design, Parametric Furniture, Pricing, Manufacturing & MES Platform

**Document ID:** BOM-BOQ-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, Furniture Engineers, Quantity Surveyors, Estimators, Procurement, Manufacturing Engineers, Finance, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Primary Currency:** Configurable; default INR  
**Primary Units:** mm, m, m², m³, kg, pcs, sheet, roll, set  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the **Bill of Materials (BOM) and Bill of Quantities (BOQ) subsystem**.

The subsystem must convert the authoritative project and engineering model into:

```text
Design
  ↓
BIM / 3D
  ↓
Parametric Furniture
  ↓
Components
  ↓
Materials / Hardware
  ↓
BOM
  ↓
BOQ
  ↓
Cost / Pricing
  ↓
Quotation
  ↓
Procurement
  ↓
Manufacturing
  ↓
MES
```

The BOM/BOQ subsystem is a critical bridge between:

```text
Design
Engineering
Commercial
Procurement
Manufacturing
```

It MUST be derived from structured project/furniture/material data and MUST NOT depend on manually typed line items wherever the underlying design data is available.

---

# 2. Core Principle

The **engineering model is the source of truth**.

BOM and BOQ are derived views.

```text
Parametric Model
       ↓
Component Model
       ↓
Material / Hardware Resolution
       ↓
Quantity Calculation
       ↓
BOM
       ↓
BOQ
       ↓
Pricing
```

Do not make BOM/BOQ the primary source of engineering quantities.

If a designer changes:

```text
Wardrobe width
2400 → 2700 mm
```

the system must be capable of recalculating:

```text
Panel dimensions
Panel quantities
Edge-band lengths
Hardware quantities
Material consumption
BOM
BOQ
Cost
Selling price
```

---

# 3. BOM vs BOQ

The system MUST clearly distinguish:

## BOM — Bill of Materials

Answers:

> What physical components/materials/hardware are required to build this design?

Typical BOM items:

```text
18mm MDF panel
6mm back panel
Laminate
Edge band
Hinge
Drawer runner
Handle
Screw
Cam lock
Dowel
Aluminium profile
Glass
```

## BOQ — Bill of Quantities

Answers:

> What measurable quantities and commercial line items are required for the project?

Typical BOQ:

```text
Wardrobe
MDF board
Laminate
Edge band
Hardware
Fabrication
Installation
Transport
Electrical work
Civil work
```

BOM is primarily engineering/material-oriented.

BOQ is primarily commercial/quantity/pricing-oriented.

---

# 4. BOM/BOQ Architecture

```text
                 PROJECT
                    │
        ┌───────────┴───────────┐
        ↓                       ↓
      ROOMS                   AREAS
        │                       │
        ↓                       ↓
   FURNITURE                  DESIGN
        │
        ↓
PARAMETRIC COMPONENTS
        │
        ↓
 MATERIAL / HARDWARE
        │
        ↓
   QUANTITY ENGINE
        │
   ┌────┴─────┐
   ↓          ↓
  BOM        BOQ
   │          │
   └────┬─────┘
        ↓
      PRICING
        ↓
     QUOTATION
        ↓
 PROCUREMENT / MANUFACTURING
```

---

# 5. BOM Levels

Support:

```text
Project BOM
Room BOM
Furniture BOM
Assembly BOM
Component BOM
Panel BOM
Hardware BOM
Material BOM
```

---

# 6. Multi-Level BOM

Example:

```text
Wardrobe W-001
│
├── Carcass
│   ├── Left Side
│   ├── Right Side
│   ├── Top
│   ├── Bottom
│   └── Partition
│
├── Shutters
│   ├── Shutter 1
│   ├── Shutter 2
│   ├── Shutter 3
│   └── Shutter 4
│
├── Drawers
│   ├── Drawer Box 1
│   ├── Drawer Front 1
│   └── Runner Pair 1
│
└── Hardware
    ├── Hinges
    ├── Handles
    └── Screws
```

---

# 7. BOM Item Types

Minimum types:

```text
ASSEMBLY
SUB_ASSEMBLY
PANEL
MATERIAL
HARDWARE
ACCESSORY
PROFILE
GLASS
STONE
CONSUMABLE
SERVICE
FINISH
```

---

# 8. BOM Item Identity

Each BOM item must have:

```text
id
logical_key
item_code
item_type
description
catalog_product_id
catalog_variant_id
component_id
parent_bom_item_id
quantity
uom
```

---

# 9. BOM Source

Every BOM line must identify its source.

Possible sources:

```text
PARAMETRIC_COMPONENT
CATALOG_PRODUCT
MANUAL
RULE
ASSEMBLY
IMPORT
```

---

# 10. Source Traceability

BOM item should be traceable to:

```text
Project
Room
Furniture
Assembly
Component
Panel
Material
Hardware
Template
Template Version
Generation Run
```

---

# 11. BOM Logical Key

Use stable logical keys where possible.

Example:

```text
F001:CARCASS:LEFT_PANEL
F001:CARCASS:RIGHT_PANEL
F001:SHUTTER:01
F001:HARDWARE:HNG-001
```

This helps revision comparison.

---

# 12. BOM Quantity Types

Support:

```text
COUNT
LENGTH
AREA
VOLUME
WEIGHT
SHEET
ROLL
SET
```

---

# 13. Quantity Precision

Store sufficient precision internally.

Examples:

```text
length = 2.438 m
area = 5.9234 m²
weight = 28.437 kg
```

Display precision may be configurable.

---

# 14. Quantity Calculation

Quantities can be derived from:

```text
component count
panel dimensions
material area
edge length
hardware rules
formula
manual adjustment
```

---

# 15. Quantity Sources

Every quantity must have:

```text
calculation_method
calculation_formula
source_component
source_parameter
```

where applicable.

---

# 16. Quantity Formula Example

For a panel:

```text
area =
length × width
```

For edge band:

```text
length =
sum(selected edge lengths)
```

For hardware:

```text
quantity =
component count × hardware rule quantity
```

---

# 17. BOM Quantity vs Procurement Quantity

Keep separate:

```text
Engineering Quantity
Procurement Quantity
```

Example:

```text
Engineering:
18mm MDF = 7.42 m²

Procurement:
Boards = 3 sheets
```

Do not confuse consumption with purchase units.

---

# 18. Waste

Waste must be represented separately.

Example:

```text
Net requirement = 7.42 m²
Waste allowance = 10%
Gross requirement = 8.162 m²
```

Actual nesting waste should supersede estimated waste where nesting has been performed.

---

# 19. Waste Types

Support:

```text
ESTIMATED_WASTE
NESTING_WASTE
CUTTING_WASTE
PROCESS_WASTE
INSTALLATION_WASTE
```

---

# 20. Waste Calculation

```text
gross_quantity =
net_quantity × (1 + waste_percent / 100)
```

But do not apply generic waste automatically to all materials.

---

# 21. Material-Specific Waste

Waste rules can depend on:

```text
material
category
project
factory
manufacturing method
```

---

# 22. BOM Aggregation

Support aggregation across:

```text
same project
same room
same furniture
same material
same SKU
same specification
```

---

# 23. Aggregation Example

Three wardrobes:

```text
Wardrobe A → MDF 18mm = 5.2 m²
Wardrobe B → MDF 18mm = 4.7 m²
Wardrobe C → MDF 18mm = 6.1 m²
```

Project aggregate:

```text
MDF 18mm = 16.0 m²
```

---

# 24. Aggregation Rules

Items should only be aggregated if compatible:

```text
catalog product
variant
thickness
finish
grain
UOM
pricing class
```

Do not merge visually or technically different materials merely because the names look similar.

---

# 25. BOQ Structure

Recommended hierarchy:

```text
Project
 ├── Area
 │    ├── Room
 │    │    ├── Furniture
 │    │    ├── Material
 │    │    ├── Hardware
 │    │    ├── Service
 │    │    └── Installation
```

---

# 26. BOQ Line Types

Support:

```text
MATERIAL
FURNITURE
HARDWARE
LABOUR
FABRICATION
INSTALLATION
TRANSPORT
SERVICE
EQUIPMENT
SUBCONTRACT
OTHER
```

---

# 27. BOQ Line

Fields:

```text
id
boq_id
line_number
item_code
description
category
source_type
source_reference
quantity
uom
unit_rate
gross_amount
discount
tax
net_amount
```

---

# 28. BOQ Source

BOQ line can come from:

```text
BOM
Furniture
Design Element
Service
Manual
Pricing Rule
```

---

# 29. BOQ Commercial Independence

Once a quotation is generated, the quotation must snapshot the BOQ data.

Later design changes must NOT silently change an already-issued quotation.

---

# 30. BOM/BOQ Relationship

Recommended:

```text
BOM
 ↓
BOQ Mapping
 ↓
Commercial Line
```

Example:

```text
BOM:
18mm MDF
Quantity: 7.42 m²

BOQ:
18mm MDF supply
Quantity: 7.42 m²
Rate: ₹X
Amount: ₹Y
```

---

# 31. One-to-Many Mapping

One BOM item may map to:

```text
multiple BOQ lines
```

Example:

```text
Furniture BOM
→ material supply
→ fabrication
→ installation
```

---

# 32. Many-to-One Mapping

Multiple BOM items may aggregate into:

```text
one BOQ line
```

Example:

```text
Multiple cabinet panels
→
"MDF 18mm supply"
```

---

# 33. Commercial Grouping

BOQ may group by:

```text
room
furniture
material category
work package
trade
supplier
```

---

# 34. Work Package

Support:

```text
Carpentry
Kitchen
Wardrobe
Electrical
Civil
Painting
False Ceiling
Glass
Stone
Hardware
Installation
```

---

# 35. Cost Categories

Support:

```text
Material Cost
Hardware Cost
Labour Cost
Fabrication Cost
Machine Cost
Transport Cost
Installation Cost
Overhead
Contingency
Tax
Discount
Margin
```

---

# 36. Cost vs Price

Store separately:

```text
internal_cost
selling_price
```

Do not expose internal cost to unauthorized users.

---

# 37. Unit Rate

BOQ unit rate can be sourced from:

```text
catalog price
supplier price
pricing rule
manual rate
contract rate
customer rate
```

---

# 38. Rate Source

Every commercial line must store:

```text
rate_source_type
rate_source_id
rate_version
effective_date
```

---

# 39. Price Snapshot

At quotation creation:

```text
product
variant
price
currency
tax
discount
rate source
```

must be snapshotted.

---

# 40. Pricing Methods

Support:

```text
PER_PIECE
PER_SHEET
PER_M2
PER_RUNNING_METER
PER_CUBIC_METER
PER_KG
PER_SET
PER_ROOM
PER_FURNITURE
FIXED_AMOUNT
PERCENTAGE
```

---

# 41. Raw Material Pricing

Formula:

```text
Material Cost =
Quantity × Unit Rate
```

---

# 42. Panel-Based Pricing

Formula:

```text
Panel Cost =
Panel Quantity × Panel Rate
```

---

# 43. Furniture-Based Pricing

Formula:

```text
Furniture Price =
Furniture Unit Rate × Furniture Quantity
```

---

# 44. Area-Based Pricing

Example:

```text
Wardrobe:
12.5 sq.ft × ₹X/sq.ft
```

The system must support unit conversion and rounding rules.

---

# 45. Linear Pricing

Example:

```text
Edge band:
28.4 running metres × ₹X/m
```

---

# 46. Labour Pricing

Labour can be:

```text
per hour
per day
per piece
per m²
per furniture
percentage of material
```

---

# 47. Installation Pricing

Support:

```text
per piece
per room
per furniture
per m²
fixed project charge
```

---

# 48. Machine Processing Cost

Optional:

```text
CNC hours × machine rate
Cutting hours × machine rate
Edge-banding length × processing rate
Drilling operations × operation rate
```

---

# 49. Manufacturing Cost

Manufacturing cost may include:

```text
material
hardware
labour
machine
waste
overhead
```

---

# 50. Margin

Margin can be configured as:

```text
percentage
fixed amount
category-specific
customer-specific
project-specific
```

---

# 51. Markup vs Margin

System must distinguish:

```text
Markup %
```

from:

```text
Margin %
```

Do not use them interchangeably.

---

# 52. Discount

Support:

```text
line discount
category discount
BOQ discount
project discount
customer discount
```

---

# 53. Tax

Tax should be applied using configurable tax rules.

Store:

```text
tax_code
tax_rate
taxable_amount
tax_amount
```

---

# 54. Tax Calculation

Example:

```text
taxable_amount = gross - discount

tax = taxable_amount × tax_rate
```

Actual tax rules must be configurable.

---

# 55. Commercial Rounding

Support:

```text
quantity precision
unit rate precision
amount precision
tax rounding
invoice rounding
```

Never round internal geometry-derived quantities prematurely.

---

# 56. BOM Rounding

Example:

```text
Engineering:
7.4286 m²

Display:
7.43 m²
```

But calculations should retain full precision.

---

# 57. BOQ Rounding

Commercial quantities may use configurable rounding:

```text
7.4286 → 7.43
```

or:

```text
7.4286 → 7.5
```

depending on item/UOM rules.

---

# 58. Sheet Procurement

If:

```text
required area = 8.4 m²
sheet area = 2.9768 m²
```

the system can calculate:

```text
minimum sheets = ceil(8.4 / 2.9768)
```

But final sheet quantity should come from nesting when available.

---

# 59. Nesting-Aware Procurement

Preferred flow:

```text
Net Panels
 ↓
Nesting
 ↓
Actual Boards Used
 ↓
Actual Waste
 ↓
Procurement Quantity
```

---

# 60. BOM Status

```text
NOT_GENERATED
CURRENT
STALE
INVALID
RELEASED
SUPERSEDED
```

---

# 61. BOQ Status

```text
DRAFT
CURRENT
PRICED
REVIEW
APPROVED
QUOTED
SUPERSEDED
```

---

# 62. Stale Logic

If source design changes:

```text
BOM → STALE
BOQ → STALE
```

if quantities or commercial assumptions are affected.

---

# 63. Price-Only Change

If only price changes:

```text
BOM → CURRENT
BOQ → STALE
```

---

# 64. Material Specification Change

If material thickness changes:

```text
BOM → STALE
BOQ → STALE
Manufacturing → STALE
```

---

# 65. Design Change Impact

System should calculate:

```text
affected BOM lines
affected BOQ lines
affected prices
affected procurement
affected manufacturing
```

---

# 66. BOM Revision

Each BOM revision stores:

```text
revision_number
source_generation_run
source_design_revision
template_version
catalog_version
created_by
created_at
change_summary
```

---

# 67. BOQ Revision

Each BOQ revision stores:

```text
revision_number
source_bom_revision
pricing_version
tax_version
commercial_rules_version
created_by
created_at
```

---

# 68. Immutable Released Data

Once:

```text
BOM released for manufacturing
```

it must not be overwritten.

Create a new revision.

---

# 69. Quotation Snapshot

Quotation must store a complete snapshot of:

```text
BOQ lines
quantities
rates
discounts
tax
totals
currency
terms
```

---

# 70. Procurement Snapshot

Purchase order should reference:

```text
approved BOQ/BOM revision
```

and snapshot:

```text
product
variant
quantity
UOM
rate
supplier
```

---

# 71. Manufacturing Snapshot

Manufacturing release should reference:

```text
BOM revision
cutlist revision
material version
hardware version
```

---

# 72. BOM Explosion

Support:

```text
explode full BOM
```

Example:

```text
Wardrobe
→ Carcass
→ Panels
→ Hardware
→ Edge band
→ Fasteners
```

---

# 73. BOM Roll-Up

Support:

```text
roll-up identical items
```

Example:

```text
Hinge = 24 pcs
```

instead of:

```text
Wardrobe A = 8
Wardrobe B = 10
Wardrobe C = 6
```

when project-level aggregation is requested.

---

# 74. BOM Views

Provide:

```text
Detailed BOM
Rolled-Up BOM
Manufacturing BOM
Procurement BOM
Cost BOM
```

---

# 75. BOQ Views

Provide:

```text
Detailed BOQ
Room BOQ
Furniture BOQ
Material BOQ
Trade BOQ
Quotation BOQ
Procurement BOQ
```

---

# 76. BOM Grouping

Group by:

```text
Project
Room
Furniture
Assembly
Material
Supplier
```

---

# 77. BOQ Grouping

Group by:

```text
Room
Work Package
Trade
Furniture
Material
Supplier
```

---

# 78. Procurement BOM

Procurement BOM should optimize:

```text
supplier
SKU
purchase UOM
MOQ
pack size
lead time
```

---

# 79. Procurement Quantity

Example:

```text
Required = 17 hinges
Pack size = 10
Procurement = 20
```

Store both:

```text
required_quantity = 17
purchase_quantity = 20
```

---

# 80. MOQ

Supplier may specify:

```text
minimum_order_quantity
```

The procurement engine can calculate the required order quantity.

---

# 81. Pack Size

Support:

```text
pack_size
pack_uom
```

Example:

```text
1 box = 50 screws
```

---

# 82. Procurement Rounding

```text
purchase_qty =
ceil(required_qty / pack_size) × pack_size
```

---

# 83. BOM Unit Conversion

Example:

```text
Edge band:
engineering = 12,450 mm
BOM = 12.45 m
Procurement = 1 roll
```

---

# 84. BOM Material Consumption

Support:

```text
net
gross
waste
procurement
```

Example:

```text
Net = 20.2 m
Waste = 2.0 m
Gross = 22.2 m
Purchase = 1 roll
```

---

# 85. BOM by Room

Example:

```text
Kitchen
 ├── Base cabinets
 ├── Wall cabinets
 ├── Tall units
 ├── Countertop
 └── Hardware
```

---

# 86. BOQ by Room

Example:

```text
Kitchen
 ├── Cabinetry
 ├── Countertop
 ├── Hardware
 ├── Installation
 └── Electrical coordination
```

---

# 87. BOQ by Work Package

Example:

```text
CARPENTRY
GLASS
STONE
ELECTRICAL
PAINTING
CIVIL
INSTALLATION
```

---

# 88. Furniture-Level BOQ

Each furniture object should be capable of generating a commercial line.

Example:

```text
Wardrobe 2400 × 600 × 2400
Qty = 1
Rate = ₹X
Amount = ₹Y
```

---

# 89. Composite Furniture Pricing

Furniture price may be:

```text
Material
+
Hardware
+
Fabrication
+
Labour
+
Installation
+
Margin
```

---

# 90. Cost Breakdown

Authorized users can view:

```text
Material = ₹X
Hardware = ₹Y
Labour = ₹Z
Machine = ₹A
Overhead = ₹B
Total Cost = ₹C
Margin = ₹D
Selling Price = ₹E
```

---

# 91. Cost Roll-Up

Project cost:

```text
Room costs
 ↓
Area costs
 ↓
Project cost
```

---

# 92. Project BOQ Total

Display:

```text
Subtotal
Discount
Taxable Amount
Tax
Grand Total
```

---

# 93. Margin Analysis

Authorized users can view:

```text
Cost
Selling Price
Gross Profit
Margin %
```

---

# 94. Budget Comparison

Support:

```text
Budget
Actual
Forecast
Variance
```

---

# 95. BOQ Budget

Each project may have:

```text
budget_amount
```

Compare:

```text
quoted amount
vs
budget
```

---

# 96. Estimate vs Actual

Future integration:

```text
BOQ Estimate
 ↓
Purchase Orders
 ↓
Goods Receipt
 ↓
Production
 ↓
Actual Cost
```

---

# 97. Cost Variance

Calculate:

```text
actual_cost - estimated_cost
```

---

# 98. Quantity Variance

Calculate:

```text
actual_quantity - estimated_quantity
```

---

# 99. BOM/BOQ Analytics

Support:

```text
material consumption
material cost
hardware consumption
room cost
furniture cost
waste
margin
```

---

# 100. High-Cost Items

Identify:

```text
top material costs
top furniture costs
top hardware costs
```

---

# 101. Waste Analytics

Track:

```text
estimated waste
actual nesting waste
actual production waste
```

---

# 102. BOM/BOQ Data Model

Minimum tables:

```text
bom_headers
bom_revisions
bom_items
bom_item_links
bom_item_quantities
bom_item_costs
bom_item_sources
bom_aggregations
bom_waste_rules
bom_snapshots

boq_headers
boq_revisions
boq_items
boq_item_sources
boq_item_pricing
boq_discounts
boq_taxes
boq_snapshots

pricing_rules
pricing_rule_versions
price_snapshots

quantity_rules
quantity_rule_versions
```

---

# 103. BOM Header

Fields:

```text
id
tenant_id
project_id
scope_type
scope_id
status
current_revision
created_by
updated_by
created_at
updated_at
```

---

# 104. BOM Revision

Fields:

```text
id
bom_id
revision_number
source_design_revision
source_generation_run
template_version
catalog_version
status
change_summary
created_by
created_at
released_at
```

---

# 105. BOM Item

Fields:

```text
id
bom_revision_id
parent_item_id
logical_key
item_code
item_type
description
catalog_product_id
catalog_variant_id
component_id
quantity
uom
quantity_type
source_type
source_id
sort_order
```

---

# 106. BOM Quantity Detail

Fields:

```text
id
bom_item_id
net_quantity
waste_quantity
gross_quantity
procurement_quantity
procurement_uom
conversion_factor
calculation_method
formula
```

---

# 107. BOM Cost Detail

Fields:

```text
id
bom_item_id
unit_cost
extended_cost
currency
price_source
price_version
effective_date
```

---

# 108. BOQ Header

Fields:

```text
id
tenant_id
project_id
status
current_revision
currency
pricing_model
created_by
created_at
updated_at
```

---

# 109. BOQ Revision

Fields:

```text
id
boq_id
revision_number
source_bom_revision
pricing_version
tax_version
commercial_rules_version
status
change_summary
created_by
created_at
approved_at
```

---

# 110. BOQ Item

Fields:

```text
id
boq_revision_id
line_number
parent_line_id
item_code
description
category
work_package
source_type
source_id
quantity
uom
unit_rate
gross_amount
discount_amount
taxable_amount
tax_amount
net_amount
currency
```

---

# 111. BOQ Pricing Detail

Fields:

```text
id
boq_item_id
rate
currency
rate_uom
rate_source_type
rate_source_id
rate_version
pricing_formula
```

---

# 112. BOQ Tax Detail

Fields:

```text
id
boq_item_id
tax_code
tax_rate
taxable_amount
tax_amount
```

---

# 113. BOQ Snapshot

Store immutable:

```text
product name
product code
description
quantity
UOM
rate
discount
tax
amount
currency
```

---

# 114. BOM Snapshot

Manufacturing BOM snapshot should store:

```text
item code
description
material
dimensions
quantity
UOM
hardware
revision
```

---

# 115. Pricing Rules

Pricing rules must support:

```text
IF
THEN
FORMULA
```

Examples:

```text
IF category = WARDROBE
THEN fabrication_rate = X
```

---

# 116. Pricing Rule Priority

```text
Project
 ↓
Customer
 ↓
Factory
 ↓
Tenant
 ↓
System
```

---

# 117. Rate Resolution

Rate resolution should return:

```text
selected rate
source
version
reason
effective date
```

---

# 118. Price Resolution Example

```text
Project Override?
 → yes → use it

Customer Rate?
 → yes → use it

Factory Rate?
 → yes → use it

Tenant Rate?
 → yes → use it

Catalog Rate
 → fallback
```

---

# 119. Manual BOQ Items

Users must be able to add manual lines for:

```text
civil work
transport
special installation
site work
custom service
```

Manual lines MUST be marked:

```text
source_type = MANUAL
```

---

# 120. Manual Quantity

Manual quantity must require:

```text
quantity
UOM
description
```

---

# 121. Manual Pricing

Manual lines may use:

```text
manual rate
```

but should record:

```text
entered_by
entered_at
reason
```

where tenant policy requires it.

---

# 122. BOQ Templates

Support reusable commercial templates:

```text
Standard Wardrobe Pricing
Standard Kitchen Pricing
Installation Charges
Transport Charges
```

---

# 123. BOQ Formula Templates

Example:

```text
Installation = 8% of furniture selling price
```

Such rules must be configurable and versioned.

---

# 124. Pricing Dependencies

Example:

```text
Furniture Price
 ↓
Installation = 8%
 ↓
Transport = 2%
```

The system must detect circular pricing dependencies.

---

# 125. Circular Pricing

Reject:

```text
A depends on B
B depends on A
```

with a clear validation error.

---

# 126. Commercial Approval

BOQ approval workflow may be:

```text
Estimator
 ↓
Sales
 ↓
Commercial Manager
 ↓
Approved
```

Tenant-specific RBAC controls actual steps.

---

# 127. Quotation Integration

Quotation should consume:

```text
approved BOQ revision
```

not an arbitrary current BOQ.

---

# 128. Quotation Change

If design changes after quotation:

```text
new BOQ revision
new quotation revision
```

must be created.

---

# 129. Purchase Order Integration

PO can consume:

```text
procurement BOM
```

and:

```text
approved supplier
```

---

# 130. Manufacturing Integration

Manufacturing should consume:

```text
released BOM
```

and:

```text
released cutlist
```

---

# 131. MES Integration

MES can reference:

```text
BOM item
component
panel
production job
```

---

# 132. Traceability

A production panel should be traceable:

```text
Panel
 ↓
BOM Item
 ↓
Furniture
 ↓
Room
 ↓
Project
```

---

# 133. QR Traceability

QR payload can reference:

```text
panel_id
production_job_id
bom_revision
```

Do not expose sensitive cost data in QR payload.

---

# 134. Export Formats

BOM/BOQ exports:

```text
CSV
XLSX
PDF
JSON
```

Optional:

```text
XML
```

---

# 135. BOM CSV

Columns:

```text
Project
Room
Furniture
Assembly
Item Code
Description
Material
Dimensions
Quantity
UOM
Source
```

---

# 136. BOQ CSV

Columns:

```text
Line
Room
Work Package
Item Code
Description
Quantity
UOM
Rate
Discount
Tax
Amount
```

---

# 137. Excel Export

Excel should support:

```text
BOM Summary
BOM Detail
BOQ Detail
Cost Summary
Room Summary
Furniture Summary
```

---

# 138. PDF Export

PDF should support:

```text
company branding
project information
revision
prepared by
approved by
date
tables
totals
terms
```

---

# 139. Client BOQ

Client-facing BOQ must hide:

```text
supplier cost
internal cost
internal margin
procurement notes
manufacturing notes
```

unless explicitly authorized.

---

# 140. Internal BOQ

Internal BOQ may show:

```text
cost
selling price
margin
supplier
```

based on permissions.

---

# 141. BOM Print

Manufacturing BOM may show:

```text
Panel
Dimensions
Material
Grain
Edges
Quantity
Hardware
Revision
```

---

# 142. Procurement BOM Print

Procurement view:

```text
Product
SKU
Supplier
Required Qty
Purchase Qty
UOM
Price
Lead Time
```

---

# 143. BOQ Summary

Dashboard:

```text
Total Project Value
Material Cost
Hardware Cost
Labour
Installation
Tax
Discount
Margin
```

---

# 144. Room Summary

Example:

```text
Master Bedroom
Furniture = ₹X
Materials = ₹Y
Installation = ₹Z
Total = ₹A
```

---

# 145. Furniture Summary

Example:

```text
Wardrobe W001
Material Cost = ₹X
Hardware = ₹Y
Fabrication = ₹Z
Selling Price = ₹A
```

---

# 146. Change Impact

When design changes, show:

```text
BOM:
12 items affected

BOQ:
8 lines affected

Cost:
+₹X

Selling Price:
+₹Y

Manufacturing:
Cutlist stale
```

---

# 147. User Confirmation

For significant changes after approval:

```text
Design changed.
This will invalidate BOM, BOQ and manufacturing outputs.
Continue?
```

---

# 148. Regeneration

Support:

```text
Regenerate BOM
Regenerate BOQ
Regenerate Pricing
```

with separate actions.

---

# 149. Partial Regeneration

If only price changed:

```text
do not regenerate geometry-derived BOM unnecessarily
```

---

# 150. Calculation Engine

Recommended services:

```text
QuantityEngine
BomEngine
BoqEngine
PricingEngine
TaxEngine
AggregationEngine
WasteEngine
SnapshotEngine
ImpactEngine
```

---

# 151. Quantity Engine

Responsible for:

```text
component quantities
panel areas
linear quantities
volumes
hardware counts
```

---

# 152. BOM Engine

Responsible for:

```text
BOM generation
hierarchy
aggregation
traceability
revision
```

---

# 153. BOQ Engine

Responsible for:

```text
commercial line generation
grouping
pricing
discount
tax
totals
```

---

# 154. Pricing Engine

Responsible for:

```text
rate resolution
raw material pricing
panel pricing
furniture pricing
labour
installation
margin
```

---

# 155. Tax Engine

Responsible for:

```text
tax code
rate
taxable amount
tax
rounding
```

---

# 156. Snapshot Engine

Responsible for:

```text
quotation snapshots
procurement snapshots
manufacturing snapshots
historical reproduction
```

---

# 157. Impact Engine

Responsible for:

```text
source change
affected BOM
affected BOQ
affected pricing
affected manufacturing
```

---

# 158. API — BOM

```http
GET  /api/v1/bom
POST /api/v1/bom/generate
GET  /api/v1/bom/{id}
GET  /api/v1/bom/{id}/revisions
GET  /api/v1/bom/{id}/items
POST /api/v1/bom/{id}/release
POST /api/v1/bom/{id}/supersede
```

---

# 159. API — BOQ

```http
GET  /api/v1/boq
POST /api/v1/boq/generate
GET  /api/v1/boq/{id}
GET  /api/v1/boq/{id}/revisions
GET  /api/v1/boq/{id}/items
POST /api/v1/boq/{id}/price
POST /api/v1/boq/{id}/approve
POST /api/v1/boq/{id}/supersede
```

---

# 160. API — BOM Item

```http
GET   /api/v1/bom/{id}/items
PATCH /api/v1/bom/items/{itemId}
```

Manual changes should be restricted according to policy.

---

# 161. API — BOQ Item

```http
POST   /api/v1/boq/{id}/items
PATCH  /api/v1/boq/items/{itemId}
DELETE /api/v1/boq/items/{itemId}
```

---

# 162. API — Recalculation

```http
POST /api/v1/bom/{id}/recalculate
POST /api/v1/boq/{id}/recalculate
POST /api/v1/boq/{id}/reprice
```

---

# 163. API — Impact

```http
GET /api/v1/projects/{projectId}/bom-boq/impact
POST /api/v1/projects/{projectId}/bom-boq/analyze-impact
```

---

# 164. API — Export

```http
GET /api/v1/bom/{id}/export/csv
GET /api/v1/bom/{id}/export/xlsx
GET /api/v1/bom/{id}/export/pdf

GET /api/v1/boq/{id}/export/csv
GET /api/v1/boq/{id}/export/xlsx
GET /api/v1/boq/{id}/export/pdf
```

---

# 165. API — Snapshots

```http
POST /api/v1/boq/{id}/snapshot
GET  /api/v1/boq/{id}/snapshots
POST /api/v1/bom/{id}/snapshot
GET  /api/v1/bom/{id}/snapshots
```

---

# 166. Command Model

Complex operations should support:

```http
POST /api/v1/bom/{id}/commands
POST /api/v1/boq/{id}/commands
```

Example:

```json
{
  "commands": [
    {
      "type": "ADD_MANUAL_ITEM",
      "description": "Site transport",
      "quantity": 1,
      "uom": "SET"
    }
  ]
}
```

---

# 167. Optimistic Concurrency

Requests should include:

```text
base_revision
```

Conflict:

```http
409 Conflict
```

Never silently overwrite approved commercial data.

---

# 168. JSON BOM Example

```json
{
  "bom_id": "BOM-001",
  "revision": 4,
  "status": "CURRENT",
  "items": [
    {
      "logical_key": "F001:CARCASS:LEFT_PANEL",
      "item_code": "MDF-18-001",
      "description": "18mm MDF Panel",
      "quantity": 1,
      "uom": "PCS",
      "source_type": "PARAMETRIC_COMPONENT"
    }
  ]
}
```

---

# 169. JSON BOQ Example

```json
{
  "boq_id": "BOQ-001",
  "revision": 3,
  "currency": "INR",
  "items": [
    {
      "line_number": 10,
      "description": "18mm MDF Supply",
      "quantity": 7.42,
      "uom": "SQ_M",
      "unit_rate": 850,
      "gross_amount": 6307
    }
  ]
}
```

---

# 170. Client-Side Architecture

Recommended:

```text
/src/bom/

domain/
  Bom.js
  BomItem.js
  BomRevision.js

quantity/
  QuantityEngine.js
  UnitConverter.js
  WasteEngine.js

generation/
  BomGenerator.js
  BoqGenerator.js

pricing/
  PricingEngine.js
  RateResolver.js
  TaxEngine.js

aggregation/
  BomAggregator.js
  BoqAggregator.js

impact/
  BomImpactAnalyzer.js
  BoqImpactAnalyzer.js

snapshots/
  SnapshotManager.js

exports/
  CsvExporter.js
  ExcelExporter.js
  PdfExporter.js

validation/
  BomValidator.js
  BoqValidator.js
```

---

# 171. PHP Backend Architecture

Recommended:

```text
src/
  Bom/
    Domain/
    Services/
    Repositories/
    Generators/
    Quantity/
    Aggregation/
    Pricing/
    Snapshots/
    Validation/
    Policies/
    DTO/
  Boq/
    Domain/
    Services/
    Repositories/
    Pricing/
    Tax/
    Snapshots/
    Validation/
```

---

# 172. Database Relationships

```text
Project
  ↓
BOM
  ↓
BOM Revision
  ↓
BOM Item
  ↓
Component / Catalog Product

BOM
  ↓
BOQ
  ↓
BOQ Revision
  ↓
BOQ Item
  ↓
Pricing / Tax
```

---

# 173. Catalog Integration

BOM items should reference:

```text
catalog_product_id
catalog_variant_id
catalog_product_version
```

Do not duplicate all catalog master data into BOM.

---

# 174. Historical Snapshot

However, for reproducibility, BOM snapshots should store critical descriptive data:

```text
product_code
name
description
material
thickness
dimensions
```

---

# 175. Furniture Integration

Furniture engine should expose:

```text
components
panels
hardware
materials
quantities
```

to BOM generation.

---

# 176. BIM Integration

BOM should be able to trace quantities to:

```text
BIM object
BIM component
room
level
```

---

# 177. Manufacturing Integration

BOM should provide:

```text
panel quantities
material specifications
hardware
edge band
machining references
```

to manufacturing.

---

# 178. Cutlist Integration

Cutlist should reference:

```text
BOM item
component
panel
revision
```

---

# 179. Nesting Integration

Nesting should receive:

```text
panel requirement
material
sheet size
grain
rotation
quantity
```

and return:

```text
boards consumed
actual waste
utilization
```

---

# 180. CNC Integration

CNC outputs should reference:

```text
BOM revision
panel ID
operation
machine profile
```

---

# 181. MES Integration

MES should reference:

```text
BOM revision
production job
panel
hardware
assembly
```

---

# 182. BOM Release Workflow

Recommended:

```text
DRAFT
 ↓
GENERATED
 ↓
VALIDATED
 ↓
ENGINEERING_REVIEW
 ↓
APPROVED
 ↓
RELEASED
 ↓
SUPERSEDED
```

---

# 183. BOQ Workflow

Recommended:

```text
DRAFT
 ↓
GENERATED
 ↓
PRICED
 ↓
COMMERCIAL_REVIEW
 ↓
APPROVED
 ↓
QUOTED
 ↓
ACCEPTED
 ↓
SUPERSEDED
```

---

# 184. Validation States

Validation result:

```text
PASS
WARNING
ERROR
BLOCKER
```

---

# 185. BOM Validation

Check:

```text
missing catalog product
missing UOM
invalid quantity
missing material
invalid hardware
duplicate logical key
stale source model
```

---

# 186. BOQ Validation

Check:

```text
missing rate
invalid UOM
invalid quantity
missing tax
invalid currency
stale BOM
unapproved manual line
```

---

# 187. Commercial Blockers

Examples:

```text
No selling rate
No tax classification
Invalid currency
Missing required pricing
```

---

# 188. Engineering Blockers

Examples:

```text
Unknown material
Invalid component
Unresolved quantity
Invalid dimensions
```

---

# 189. Audit Trail

Record:

```text
BOM generated
BOM regenerated
BOM edited
BOM released
BOQ generated
BOQ repriced
BOQ edited
BOQ approved
BOQ quoted
Revision created
Manual line added
Price changed
```

---

# 190. Audit Fields

```text
user_id
tenant_id
entity_type
entity_id
action
old_value
new_value
timestamp
reason
```

---

# 191. Security

Internal financial fields:

```text
supplier_cost
internal_cost
margin
markup
factory_rate
```

must be protected.

---

# 192. RBAC Permissions

Minimum:

```text
bom.view
bom.generate
bom.edit
bom.release
bom.approve
bom.export

boq.view
boq.generate
boq.edit
boq.price
boq.approve
boq.quote
boq.export

pricing.cost.view
pricing.margin.view
pricing.edit
```

---

# 193. Tenant Isolation

All BOM/BOQ records must include or derive:

```text
tenant_id
```

Authorization must be server-side.

---

# 194. Project Isolation

A user authorized for Project A must not access:

```text
Project B BOM
Project B BOQ
Project B costs
```

unless explicitly authorized.

---

# 195. Client Security

Client users should see only:

```text
client-facing BOQ
selling price
approved descriptions
```

not:

```text
internal BOM
supplier cost
internal margin
manufacturing notes
```

---

# 196. Performance

Target:

```text
Project with 5,000 BOM lines
→ generate < 5 seconds
```

Typical project:

```text
< 2 seconds
```

for standard BOM generation, excluding heavy asynchronous work.

---

# 197. Large Project Processing

For:

```text
10,000+ lines
```

support asynchronous generation.

UI:

```text
Generating BOM...
Progress: 62%
```

---

# 198. Incremental Recalculation

When one furniture item changes:

```text
recalculate affected BOM subtree
```

instead of rebuilding the entire project where possible.

---

# 199. Caching

Cache:

```text
quantity calculations
catalog price resolution
BOM generation
aggregation
```

using stable source/version hashes.

---

# 200. Cache Invalidation

Invalidate on:

```text
design revision
catalog version
pricing version
tax version
rule version
```

---

# 201. Deterministic Generation

Given identical:

```text
design revision
catalog versions
pricing versions
rules
engine version
```

the same BOM must be generated.

---

# 202. Generation Hash

Store:

```text
source_hash
bom_hash
boq_hash
```

to identify identical outputs.

---

# 203. Regression Testing

Every BOM/BOQ bug must produce an automated regression test.

---

# 204. Unit Tests

Required:

```text
quantity calculations
unit conversion
area
length
volume
waste
aggregation
pricing
discount
tax
rounding
```

---

# 205. Integration Tests

Test:

```text
Furniture
→ BOM
→ BOQ
→ Pricing
→ Quotation
```

---

# 206. Manufacturing Test

Test:

```text
Furniture
→ BOM
→ Cutlist
→ Nesting
→ CNC
```

and verify traceability.

---

# 207. Version Test

```text
Design v1
→ BOM v1

Design v2
→ BOM v2

BOM v1 remains unchanged
```

---

# 208. Pricing Version Test

```text
Catalog price v1
→ BOQ v1

Catalog price v2
→ new BOQ pricing

old quotation remains unchanged
```

---

# 209. Aggregation Test

Three furniture items using the same material must aggregate correctly while preserving individual source references.

---

# 210. Waste Test

Verify:

```text
net
waste
gross
procurement
```

separately.

---

# 211. Procurement Test

Verify:

```text
required = 17
pack = 10
purchase = 20
```

---

# 212. Tax Test

Verify:

```text
gross
discount
taxable
tax
grand total
```

with configurable rounding.

---

# 213. Security Test

Verify:

```text
Tenant A cannot read Tenant B BOM
Client cannot see internal cost
Designer cannot approve if unauthorized
Released BOM cannot be overwritten
```

---

# 214. UI — BOM Screen

Recommended:

```text
+----------------------------------------------------------------+
| BOM Header                                                     |
| Project | Room | Revision | Status | Generate | Release       |
+----------------------------------------------------------------+
| Filters                                                        |
| Room | Furniture | Material | Supplier | Type                  |
+----------------------------------------------------------------+
| Tree / Table                                                   |
| Item | Description | Qty | UOM | Material | Source | Cost      |
+----------------------------------------------------------------+
| Summary                                                        |
| Materials | Hardware | Panels | Total Cost                    |
+----------------------------------------------------------------+
```

---

# 215. BOM Tree View

Support:

```text
Project
 ├── Room
 │    ├── Furniture
 │    │    ├── Assembly
 │    │    ├── Panel
 │    │    └── Hardware
```

---

# 216. BOQ Screen

```text
+----------------------------------------------------------------+
| BOQ Header                                                     |
| Project | Revision | Currency | Status | Reprice | Approve    |
+----------------------------------------------------------------+
| Line | Description | Qty | UOM | Rate | Discount | Tax | Total |
+----------------------------------------------------------------+
| Subtotal | Discount | Tax | Grand Total | Margin              |
+----------------------------------------------------------------+
```

---

# 217. BOM Detail Drawer

Selecting an item should show:

```text
Source Furniture
Source Component
Material
Dimensions
Quantity Formula
Catalog Product
Catalog Version
Revision
```

---

# 218. BOQ Detail Drawer

Show:

```text
Source BOM
Pricing Source
Rate Version
Formula
Discount
Tax
Commercial Notes
```

---

# 219. Change Impact UI

When a source model changes:

```text
┌─────────────────────────────────────┐
│ Change Impact                       │
├─────────────────────────────────────┤
│ BOM: 14 lines affected              │
│ BOQ: 8 lines affected               │
│ Cost: +₹24,500                      │
│ Manufacturing: STALE                 │
│ Quotation: NEW REVISION REQUIRED    │
└─────────────────────────────────────┘
```

---

# 220. Export UI

Actions:

```text
Export CSV
Export Excel
Export PDF
Print
```

Options:

```text
Detailed
Summary
Room-wise
Furniture-wise
Procurement
Manufacturing
Client
Internal
```

---

# 221. Manual BOQ Editor

Allow authorized users to:

```text
add line
edit quantity
edit rate
add discount
add tax
add notes
```

Every manual modification must be marked and audited.

---

# 222. Locked Generated Lines

Tenant policy can lock:

```text
engineering-generated quantity
```

so commercial users can change:

```text
rate
```

but not:

```text
engineering quantity
```

---

# 223. Separation of Engineering and Commercial Edits

Strongly recommended:

```text
Engineering Quantity
        │
        ↓
Commercial Quantity Override
```

Commercial overrides must not modify engineering source data.

---

# 224. Quantity Override

If allowed:

```text
original_quantity
override_quantity
override_reason
override_by
override_at
```

---

# 225. BOQ Notes

Support:

```text
internal_note
client_note
manufacturing_note
procurement_note
```

Visibility must be permission-controlled.

---

# 226. BOQ Terms

Quotation may reference:

```text
payment terms
delivery terms
installation terms
warranty
validity
```

These are quotation-level data, not BOM data.

---

# 227. Analytics

Dashboard KPIs:

```text
Total Material Cost
Total Hardware Cost
Total Manufacturing Cost
Total Installation Cost
Total Selling Price
Gross Margin
Waste %
```

---

# 228. Material Consumption Report

Show:

```text
Material
Quantity
Rooms
Furniture
Cost
Supplier
```

---

# 229. Hardware Consumption Report

Show:

```text
Hardware SKU
Quantity
Furniture
Supplier
Cost
```

---

# 230. Procurement Report

Show:

```text
Supplier
SKU
Product
Required Qty
Purchase Qty
UOM
Rate
Amount
Lead Time
```

---

# 231. Manufacturing BOM Report

Show:

```text
Furniture
Component
Panel
Material
Dimensions
Quantity
Edge Band
Operations
Revision
```

---

# 232. Client BOQ Report

Show:

```text
Description
Room
Quantity
UOM
Rate
Amount
Tax
Total
```

Hide internal information.

---

# 233. Cursor Pre-Implementation Requirement

Before coding, Cursor MUST analyze:

```text
existing furniture engine
existing material catalog
existing BOM
existing BOQ
existing pricing
existing quotation
existing database
existing manufacturing
existing cutlist
existing nesting
existing MES
existing RBAC
existing revision system
existing export functionality
```

Produce:

```text
CURRENT BOM ARCHITECTURE
CURRENT BOQ ARCHITECTURE
CURRENT PRICING FLOW
CURRENT DATA SOURCES
DUPLICATE LOGIC
GAPS
MIGRATION RISKS
TARGET MODEL
IMPLEMENTATION PLAN
```

Do not blindly create duplicate BOM/BOQ tables if equivalent structures already exist.

---

# 234. Cursor Implementation Order

## Phase 1

```text
Quantity Engine
UOM Engine
Calculation Rules
```

## Phase 2

```text
BOM Domain
BOM Generator
BOM Hierarchy
BOM Aggregation
```

## Phase 3

```text
BOQ Domain
BOQ Generator
Commercial Grouping
```

## Phase 4

```text
Pricing Engine
Rate Resolution
Discount
Tax
Margin
```

## Phase 5

```text
Revision
Snapshot
Impact Analysis
```

## Phase 6

```text
Procurement
```

## Phase 7

```text
Manufacturing
Cutlist
Nesting
CNC
```

## Phase 8

```text
Quotation
```

## Phase 9

```text
Exports
Reports
Analytics
```

## Phase 10

```text
Performance
Security
Regression
```

---

# 235. Critical Implementation Rules

Cursor MUST follow these rules:

1. Do not manually hard-code BOM quantities where parametric data exists.
2. Do not mix BOM and BOQ responsibilities.
3. Do not use BOM as the engineering source of truth.
4. Do not use BOQ as the pricing source of truth.
5. Do not overwrite released revisions.
6. Do not overwrite historical quotation snapshots.
7. Do not silently change old prices.
8. Do not silently substitute materials.
9. Do not expose internal cost to unauthorized users.
10. Do not silently aggregate incompatible materials.
11. Do not round engineering quantities prematurely.
12. Do not calculate procurement sheets using area alone when nesting data is available.
13. Preserve traceability from BOQ → BOM → Furniture → Component → Project.
14. Preserve traceability from Manufacturing → BOM → Panel → Furniture.
15. All formulas must be deterministic and versioned.

---

# 236. Definition of Done

The BOM/BOQ subsystem is complete when:

```text
[ ] Quantity Engine implemented
[ ] UOM Engine implemented
[ ] BOM hierarchy implemented
[ ] Multi-level BOM implemented
[ ] BOM generation implemented
[ ] BOM aggregation implemented
[ ] BOM roll-up implemented
[ ] BOM revisioning implemented
[ ] BOM snapshot implemented
[ ] BOQ hierarchy implemented
[ ] BOQ generation implemented
[ ] BOQ grouping implemented
[ ] BOQ revisioning implemented
[ ] Pricing Engine implemented
[ ] Rate resolution implemented
[ ] Raw material pricing implemented
[ ] Panel pricing implemented
[ ] Furniture pricing implemented
[ ] Labour pricing implemented
[ ] Installation pricing implemented
[ ] Machine processing pricing implemented
[ ] Discount implemented
[ ] Tax engine implemented
[ ] Margin/markup implemented
[ ] Waste engine implemented
[ ] Procurement quantity implemented
[ ] Pack/MOQ handling implemented
[ ] Supplier pricing implemented
[ ] Price history implemented
[ ] Commercial snapshot implemented
[ ] Manufacturing snapshot implemented
[ ] Impact analysis implemented
[ ] Stale status implemented
[ ] Manual BOQ lines implemented
[ ] Approval workflow implemented
[ ] RBAC implemented
[ ] Tenant isolation implemented
[ ] Audit trail implemented
[ ] CSV export implemented
[ ] XLSX export implemented
[ ] PDF export implemented
[ ] Client BOQ implemented
[ ] Internal BOQ implemented
[ ] Procurement BOM implemented
[ ] Manufacturing BOM implemented
[ ] Analytics implemented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Security tests implemented
[ ] Regression tests implemented
[ ] Performance tests completed
```

---

# 237. Final End-to-End Data Flow

The final implementation should support:

```text
                   DESIGN / BIM
                        │
                        ↓
              PARAMETRIC FURNITURE
                        │
                        ↓
                  COMPONENT GRAPH
                        │
            ┌───────────┼───────────┐
            ↓           ↓           ↓
         PANELS      HARDWARE     MATERIALS
            │           │           │
            └───────────┼───────────┘
                        ↓
                 QUANTITY ENGINE
                        │
                        ↓
                       BOM
                        │
              ┌─────────┴─────────┐
              ↓                   ↓
        PROCUREMENT BOM        BOQ
                                  │
                    ┌─────────────┼──────────────┐
                    ↓             ↓              ↓
                 PRICING       DISCOUNT         TAX
                    │             │              │
                    └─────────────┼──────────────┘
                                  ↓
                            SELLING PRICE
                                  │
                                  ↓
                              QUOTATION
                                  │
                 ┌────────────────┼────────────────┐
                 ↓                ↓                ↓
             PROCUREMENT     MANUFACTURING       MES
                                  │
                              CUTLIST
                                  ↓
                               NESTING
                                  ↓
                                CNC
                                  ↓
                             PRODUCTION
```

---

# 238. Final Product Principle

The BOM/BOQ subsystem must make it possible to answer, for every project:

```text
What are we building?
What components are required?
What materials are required?
How much material is required?
How much waste is expected?
What hardware is required?
Which supplier supplies it?
What will it cost?
What will we sell it for?
What is the margin?
What needs to be procured?
What needs to be manufactured?
Which manufacturing revision was used?
Which design component generated this line?
Which room and furniture does it belong to?
```

The fundamental rule is:

> **BOM represents the engineering and physical requirements of the design. BOQ represents the measurable and commercial requirements of the project. Both must remain traceable to the same authoritative parametric design model.**

