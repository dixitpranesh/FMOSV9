# Pricing Engine Specification
## End-to-End Interior Design, Furniture Engineering, Manufacturing & MES Platform

**Document ID:** PRC-ENG-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, JavaScript ES6 Developers, Product Engineers, Estimators, Finance, Procurement, Manufacturing Engineers, QA  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Default Currency:** INR  
**Primary Units:** mm, m, m², m³, kg, pcs, sheet, roll, set  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the complete requirements for the **Pricing Engine**.

The Pricing Engine converts engineering and commercial inputs into:

```text
Material Cost
+ Hardware Cost
+ Consumables
+ Fabrication
+ Machine Processing
+ Labour
+ Installation
+ Transport
+ Subcontracting
+ Overhead
+ Contingency
+ Taxes
+ Discounts
+ Markup / Margin
= Selling Price
```

The engine must support:

```text
Interior Design
Modular Furniture
Kitchens
Wardrobes
Storage
Custom Furniture
Civil / Electrical / False Ceiling / Flooring / Painting
Manufacturing
Installation
Procurement
Quotations
```

---

# 2. Core Principle

The Pricing Engine is a **calculation service**, not a database of manually typed prices.

It must consume authoritative data from:

```text
Catalog
Furniture Engine
BOM
BOQ
Manufacturing
Supplier Pricing
Labour Rates
Machine Rates
Commercial Rules
Tax Rules
Customer Rules
Project Rules
```

and produce deterministic pricing outputs.

---

# 3. Source of Truth

Pricing must use:

```text
Catalog → Product / Material master
BOM → Engineering quantities
BOQ → Commercial lines
Pricing Engine → Rate and calculation logic
Quotation → Immutable commercial snapshot
```

Do not make quotation prices the source of truth for future calculations.

---

# 4. Pricing Architecture

```text
                    DESIGN / BIM
                         │
                         ↓
                PARAMETRIC FURNITURE
                         │
                         ↓
                       BOM
                         │
                         ↓
                       BOQ
                         │
             ┌───────────┼────────────┐
             ↓           ↓            ↓
         MATERIAL      LABOUR       MACHINE
          RATES         RATES         RATES
             │           │            │
             └───────────┼────────────┘
                         ↓
                  PRICING ENGINE
                         │
             ┌───────────┼────────────┐
             ↓           ↓            ↓
          COSTING     DISCOUNT       TAX
             │           │            │
             └───────────┼────────────┘
                         ↓
                    MARKUP/MARGIN
                         ↓
                   SELLING PRICE
                         ↓
                    QUOTATION
```

---

# 5. Pricing Modes

The engine must support:

```text
RAW_MATERIAL
PANEL_BASED
AREA_BASED
LINEAR_METER
UNIT_BASED
FURNITURE_BASED
ROOM_BASED
PROJECT_FIXED
LABOUR_BASED
MACHINE_BASED
FORMULA_BASED
PERCENTAGE_BASED
HYBRID
```

---

# 6. Raw Material Pricing

Example:

```text
MDF required = 12.4 m²
Rate = ₹850/m²

Cost = 12.4 × 850
```

---

# 7. Sheet-Based Pricing

Example:

```text
MDF:
3 sheets
₹2,400/sheet

Cost = ₹7,200
```

The engine must distinguish:

```text
engineering consumption
purchase quantity
pricing quantity
```

---

# 8. Panel-Based Pricing

Support:

```text
panel_count × panel_rate
```

Example:

```text
18 panels × ₹450
```

---

# 9. Furniture-Based Pricing

Support:

```text
furniture_quantity × furniture_rate
```

Example:

```text
Wardrobe × 2
₹85,000/unit
```

---

# 10. Area-Based Pricing

Support:

```text
area × rate_per_m²
```

Examples:

```text
False ceiling
Flooring
Wall paneling
Painting
Countertop
```

---

# 11. Linear Pricing

Support:

```text
linear_quantity × rate_per_m
```

Examples:

```text
Edge band
Skirting
Profiles
Cornice
Channels
```

---

# 12. Piece-Based Pricing

Support:

```text
quantity × rate_per_piece
```

Examples:

```text
Handles
Hinges
Lights
Sockets
Accessories
```

---

# 13. Volume-Based Pricing

Support:

```text
volume × rate_per_m³
```

Useful for:

```text
solid wood
stone
bulk materials
```

---

# 14. Weight-Based Pricing

Support:

```text
weight × rate_per_kg
```

Useful for:

```text
metal
aluminium
steel
```

---

# 15. Labour Pricing

Support:

```text
hours × hourly_rate
```

and:

```text
days × daily_rate
```

and:

```text
quantity × labour_rate
```

---

# 16. Installation Pricing

Support:

```text
per piece
per furniture
per room
per m²
per running metre
fixed charge
percentage
```

---

# 17. Machine Pricing

Support:

```text
machine_hours × machine_rate
```

and:

```text
operations × operation_rate
```

and:

```text
linear_length × processing_rate
```

Examples:

```text
CNC cutting
Edge banding
Drilling
Routing
Grooving
```

---

# 18. Manufacturing Pricing

Manufacturing cost may be:

```text
Material
+ Hardware
+ Consumables
+ Labour
+ Machine
+ Factory Overhead
+ Waste
```

---

# 19. Cost Layers

Pricing must preserve separate layers:

```text
Direct Material Cost
Direct Hardware Cost
Direct Labour Cost
Direct Machine Cost
Manufacturing Overhead
Installation Cost
Transport Cost
Corporate Overhead
Contingency
Total Cost
Markup
Discount
Tax
Selling Price
```

---

# 20. Cost vs Price

The engine must never confuse:

```text
COST
```

with:

```text
SELLING PRICE
```

Example:

```text
Total Cost = ₹60,000
Markup = 25%
Selling Price = ₹75,000
```

---

# 21. Markup

Markup calculation:

```text
Selling Price =
Cost × (1 + Markup% / 100)
```

Example:

```text
Cost = 100,000
Markup = 20%

Price = 120,000
```

---

# 22. Margin

Margin calculation:

```text
Margin% =
(Selling Price - Cost) / Selling Price × 100
```

---

# 23. Margin-Based Price

Required price for target margin:

```text
Selling Price =
Cost / (1 - Target Margin%)
```

Example:

```text
Cost = ₹80,000
Target Margin = 20%

Price = ₹100,000
```

Do not treat margin and markup as the same calculation.

---

# 24. Pricing Waterfall

Recommended order:

```text
Base Cost
 ↓
Cost Adjustments
 ↓
Overhead
 ↓
Contingency
 ↓
Cost
 ↓
Markup / Target Margin
 ↓
Gross Selling Price
 ↓
Discount
 ↓
Tax
 ↓
Final Price
```

The exact tenant commercial policy must be configurable.

---

# 25. Alternative Pricing Waterfall

Some businesses apply discount before margin.

Support configurable pricing sequences:

```text
COST
→ MARGIN
→ DISCOUNT
→ TAX
```

or:

```text
COST
→ DISCOUNT
→ MARGIN
→ TAX
```

The sequence must be explicitly configured, never assumed.

---

# 26. Pricing Pipeline

```text
INPUT
 ↓
NORMALIZE
 ↓
VALIDATE
 ↓
RESOLVE RATES
 ↓
CALCULATE DIRECT COST
 ↓
CALCULATE INDIRECT COST
 ↓
APPLY OVERHEAD
 ↓
APPLY CONTINGENCY
 ↓
CALCULATE TARGET PRICE
 ↓
APPLY DISCOUNTS
 ↓
CALCULATE TAX
 ↓
ROUND
 ↓
VALIDATE
 ↓
OUTPUT
```

---

# 27. Rate Sources

A rate may come from:

```text
CATALOG
SUPPLIER
FACTORY
TENANT
CUSTOMER
PROJECT
CONTRACT
PRICING_TEMPLATE
MANUAL
IMPORT
```

---

# 28. Rate Precedence

Recommended default:

```text
Project Override
 ↓
Customer Contract
 ↓
Factory Rate
 ↓
Tenant Rate
 ↓
Supplier Rate
 ↓
Catalog Default
 ↓
System Default
```

Make precedence configurable.

---

# 29. Rate Resolution

Rate resolver must return:

```text
selected_rate
currency
uom
source_type
source_id
version
effective_from
effective_to
resolution_reason
```

---

# 30. Rate Validity

Rates must support:

```text
effective_from
effective_to
```

Do not use future or expired rates accidentally.

---

# 31. Price History

Never overwrite historical prices used in:

```text
approved quotations
purchase orders
manufacturing releases
invoices
```

Create new versions.

---

# 32. Price Version

Each rate must have:

```text
price_version
```

Example:

```text
MDF-18
v1 ₹800/m²
v2 ₹850/m²
```

---

# 33. Currency

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

# 34. Exchange Rates

If multi-currency pricing is enabled:

```text
base_currency
source_currency
exchange_rate
effective_date
source
```

must be stored.

---

# 35. Exchange Rate Snapshot

Quotation must store the exchange rate used.

Future FX changes must not modify old quotations.

---

# 36. Currency Conversion

Example:

```text
USD cost × FX rate = INR cost
```

Do not round before the final calculation unless configured.

---

# 37. Unit of Measure

Pricing must support:

```text
PCS
SET
SHEET
M
M²
M³
KG
HOUR
DAY
ROOM
PROJECT
```

---

# 38. Unit Conversion

The pricing engine must use a centralized UOM service.

Example:

```text
1000 mm = 1 m
1,000,000 mm² = 1 m²
```

Do not duplicate conversion formulas across pricing rules.

---

# 39. Pricing Quantity

Pricing quantity can differ from engineering quantity.

Example:

```text
Engineering = 17 hinges
Purchase = 20 hinges
Pricing = 20 hinges
```

The pricing source must explicitly identify which quantity is being priced.

---

# 40. Quantity Selection

Possible quantity bases:

```text
ENGINEERING_QTY
GROSS_QTY
PROCUREMENT_QTY
NESTED_QTY
MANUAL_QTY
```

---

# 41. Sheet Pricing Quantity

Support:

```text
NESTING_RESULT
```

as the preferred source when available.

Fallback:

```text
PROCUREMENT_QTY
```

---

# 42. Waste Pricing

Waste may be:

```text
included in material rate
```

or:

```text
calculated separately
```

Do not apply both.

---

# 43. Waste Pricing Models

Support:

```text
PERCENTAGE
ACTUAL_NESTING
FIXED_QUANTITY
RATE_INCLUDED
```

---

# 44. Material Cost Formula

```text
material_cost =
pricing_quantity × resolved_material_rate
```

---

# 45. Hardware Cost Formula

```text
hardware_cost =
procurement_quantity × resolved_hardware_rate
```

---

# 46. Labour Cost Formula

```text
labour_cost =
labour_quantity × labour_rate
```

---

# 47. Machine Cost Formula

```text
machine_cost =
machine_quantity × machine_rate
```

---

# 48. Processing Cost

Support operation-based pricing:

```text
cut_count × cut_rate
drill_count × drill_rate
groove_length × groove_rate
edge_length × edge_rate
```

---

# 49. Operation Rate

Manufacturing operation master:

```text
operation_code
operation_name
machine_type
uom
rate
currency
effective_dates
```

---

# 50. Machine Rate

Machine rate may include:

```text
depreciation
power
maintenance
operator
consumables
```

or may be configured as a simple hourly rate.

---

# 51. Factory Overhead

Support:

```text
percentage of direct cost
fixed amount
per hour
per unit
```

---

# 52. Corporate Overhead

Support:

```text
percentage
fixed project amount
```

---

# 53. Installation Overhead

Support:

```text
percentage of furniture value
per room
per unit
fixed charge
```

---

# 54. Transport

Support:

```text
fixed project
per km
per trip
percentage
weight-based
volume-based
```

---

# 55. Distance-Based Transport

Optional:

```text
distance × rate_per_km
```

Inputs may come from project logistics.

---

# 56. Subcontractor Pricing

Support:

```text
subcontract cost
subcontract margin
```

Supplier/subcontractor-specific rates must be versioned.

---

# 57. Contingency

Support:

```text
percentage
fixed amount
```

Example:

```text
Direct Cost = ₹500,000
Contingency = 3%
= ₹15,000
```

---

# 58. Contingency Policy

Contingency may be applied:

```text
project level
category level
risk level
```

---

# 59. Risk-Based Pricing

Optional rules:

```text
LOW_RISK = 1%
MEDIUM_RISK = 3%
HIGH_RISK = 7%
```

The risk classification must be explicit.

---

# 60. Discount Types

Support:

```text
PERCENTAGE
FIXED_AMOUNT
PER_UNIT
CATEGORY
CUSTOMER
PROJECT
PROMOTIONAL
```

---

# 61. Discount Scope

Discount may apply to:

```text
line
category
room
furniture
BOQ
project
```

---

# 62. Discount Priority

Configurable precedence:

```text
Project Discount
Customer Discount
Category Discount
Line Discount
```

The engine must prevent accidental double discounting.

---

# 63. Discount Caps

Support:

```text
maximum discount percentage
maximum discount amount
```

---

# 64. Approval Threshold

If discount exceeds threshold:

```text
approval required
```

Example:

```text
> 15% → Commercial Manager approval
```

---

# 65. Promotional Pricing

Optional:

```text
promotion_code
valid_from
valid_to
eligibility
discount
```

---

# 66. Customer-Specific Pricing

Support:

```text
customer price list
customer discount
customer contract
customer-specific material rates
```

---

# 67. Project-Specific Pricing

Support:

```text
project rate
project discount
project margin
```

---

# 68. Contract Pricing

For enterprise customers:

```text
contract_id
contract_rate
validity
scope
```

---

# 69. Price Lists

Support multiple price lists:

```text
Retail
Dealer
Corporate
Premium
Factory
Export
```

---

# 70. Price List Versioning

Each price list must support:

```text
version
effective date
expiry
status
```

---

# 71. Price List Assignment

Price list may be assigned to:

```text
tenant
customer
customer group
project
```

---

# 72. Pricing Rule Hierarchy

Recommended:

```text
Project
Customer
Factory
Tenant
System
```

for rules as well as rates where appropriate.

---

# 73. Formula Engine

Pricing rules may contain formulas.

Example:

```text
installation =
furniture_selling_price × 0.08
```

---

# 74. Formula Variables

Supported variables may include:

```text
quantity
area
length
volume
weight
material_cost
hardware_cost
labour_cost
machine_cost
fabrication_cost
subtotal
selling_price
```

---

# 75. Formula Safety

Do NOT execute arbitrary PHP or JavaScript from pricing rules.

Use a restricted expression parser.

---

# 76. Formula Parser

Supported operations:

```text
+
-
*
/
%
()
MIN()
MAX()
ROUND()
CEIL()
FLOOR()
IF()
```

---

# 77. Formula Validation

Before publishing a pricing rule:

```text
syntax check
variable validation
unit validation
circular dependency check
division-by-zero check
```

---

# 78. Pricing Dependency Graph

Example:

```text
Material Cost
   ↓
Direct Cost
   ↓
Overhead
   ↓
Total Cost
   ↓
Margin
   ↓
Selling Price
   ↓
Installation %
```

The engine must detect cycles.

---

# 79. Circular Dependency

Reject:

```text
A → B
B → C
C → A
```

with a clear validation error.

---

# 80. Rule Versioning

Pricing rules must be versioned.

Store:

```text
rule_id
version
status
effective_from
effective_to
created_by
approved_by
```

---

# 81. Rule Lifecycle

```text
DRAFT
REVIEW
APPROVED
PUBLISHED
DEPRECATED
ARCHIVED
```

---

# 82. Rate Lifecycle

```text
DRAFT
APPROVED
ACTIVE
EXPIRED
ARCHIVED
```

---

# 83. Pricing Template

Support reusable templates:

```text
Standard Wardrobe
Premium Wardrobe
Modular Kitchen
Budget Kitchen
Office Workstation
```

---

# 84. Pricing Template Structure

```text
Template
 ├── Cost Components
 ├── Rates
 ├── Rules
 ├── Markup
 ├── Discount
 ├── Tax
 └── Approval Rules
```

---

# 85. Furniture Pricing Template

Example:

```text
Wardrobe
Material = BOM Cost
Hardware = BOM Cost
Fabrication = 15%
Installation = 8%
Overhead = 5%
Margin = 20%
```

---

# 86. Panel Pricing Template

Example:

```text
Panel
Material = ₹X
Cutting = ₹Y
Edge Band = ₹Z
Drilling = ₹A
Finishing = ₹B
```

---

# 87. Kitchen Pricing

Support kitchen-specific factors:

```text
base units
wall units
tall units
shutters
drawers
hardware
countertop
backsplash
installation
```

---

# 88. Wardrobe Pricing

Support:

```text
carcass
shutters
drawers
internal accessories
hardware
mirror/glass
lighting
installation
```

---

# 89. Custom Furniture Pricing

Support arbitrary BOM-driven pricing.

Do not require every furniture type to have hard-coded pricing logic.

---

# 90. Service Pricing

Support:

```text
site measurement
design fee
consultation
installation
transport
supervision
```

---

# 91. Design Fee

Can be:

```text
fixed
per room
per m²
percentage of project
```

---

# 92. Minimum Order Value

Support:

```text
minimum_project_value
minimum_category_value
```

---

# 93. Small Order Surcharge

Optional:

```text
if project value < threshold:
    surcharge = fixed/percentage
```

---

# 94. Rush Order Pricing

Optional:

```text
rush_multiplier
```

based on:

```text
production lead time
installation deadline
```

---

# 95. Complexity Pricing

Optional complexity multiplier:

```text
standard = 1.0
complex = 1.1
high_complexity = 1.25
```

Must be explicit and auditable.

---

# 96. Location Pricing

Rates may vary by:

```text
country
state
city
factory
service region
```

---

# 97. Customer Segment Pricing

Support:

```text
Retail
Designer
Builder
Developer
Enterprise
Dealer
```

---

# 98. Supplier Selection

Pricing can select supplier based on:

```text
preferred supplier
lowest approved cost
availability
lead time
customer preference
```

---

# 99. Supplier Rate Comparison

For a product:

```text
Supplier A = ₹800
Supplier B = ₹760
Supplier C = ₹825
```

Engine can recommend the lowest approved rate, subject to business rules.

---

# 100. Supplier Cost vs Selling Rate

Keep separate:

```text
purchase_rate
internal_cost_rate
selling_rate
```

---

# 101. Cost Allocation

Shared costs can be allocated using:

```text
value
quantity
area
labour hours
revenue
```

---

# 102. Project Overhead Allocation

Example:

```text
Project Overhead = ₹50,000

Allocate by:
Furniture selling value
```

---

# 103. Category Overhead

Example:

```text
Carpentry = 5%
Glass = 8%
Stone = 6%
```

---

# 104. Labour Rate Card

Labour master:

```text
role
skill
rate
UOM
location
effective date
```

Examples:

```text
Carpenter
CNC Operator
Installer
Designer
Supervisor
Electrician
Painter
```

---

# 105. Labour Rate Resolution

Resolve:

```text
project location
skill
role
factory
effective date
```

---

# 106. Machine Rate Card

Machine master:

```text
machine
machine_type
hourly_rate
setup_rate
operation_rate
location
effective date
```

---

# 107. Setup Cost

Support:

```text
machine setup
tool setup
job setup
```

---

# 108. Minimum Machine Charge

Optional:

```text
minimum_machine_charge
```

---

# 109. Consumables

Support:

```text
adhesive
sanding material
drill bits
CNC consumables
packaging
cleaning
```

Pricing methods:

```text
per unit
per hour
percentage
fixed
```

---

# 110. Packaging

Support:

```text
carton
foam
bubble wrap
corner protection
stretch film
pallet
```

---

# 111. Packaging Pricing

Support:

```text
per furniture
per panel
per package
percentage of material
```

---

# 112. Logistics

Support:

```text
loading
transport
unloading
site delivery
```

---

# 113. Installation Team Pricing

Support:

```text
team/day
installer/hour
per furniture
per room
```

---

# 114. Site Conditions

Optional pricing factors:

```text
floor level
lift availability
parking
site access
working hours
distance
```

These must be explicit project inputs.

---

# 115. Site Surcharge

Example:

```text
No lift + upper floor
→ configurable surcharge
```

---

# 116. Pricing Scenario Engine

Support multiple scenarios:

```text
BASE
BUDGET
PREMIUM
OPTIMISTIC
CONSERVATIVE
CUSTOM
```

---

# 117. Scenario Comparison

Compare:

```text
cost
selling price
margin
material selection
supplier
```

---

# 118. What-If Pricing

Users should be able to change:

```text
material
quantity
rate
margin
discount
```

and preview the impact without modifying the approved quotation.

---

# 119. Price Simulation

Simulation must be:

```text
temporary
non-destructive
```

---

# 120. Target Budget

Given:

```text
target budget
```

engine may calculate:

```text
maximum allowable cost
```

based on target margin.

---

# 121. Budget-Constrained Recommendations

Optional future capability:

```text
Current price > budget
 ↓
Suggest material alternatives
 ↓
Recalculate
```

Recommendations must respect engineering compatibility.

---

# 122. Price Optimization

Optional future capability:

```text
optimize supplier
optimize material
optimize panel pricing
```

subject to constraints.

---

# 123. Pricing Auditability

Every calculated amount must be explainable.

Example:

```text
MDF Cost
Quantity: 8.42 m²
Rate: ₹850/m²
Source: Supplier Price List v4
Effective: 2026-08-01
Formula: quantity × rate
Amount: ₹7,157
```

---

# 124. Explain Price API

Provide:

```http
GET /api/v1/pricing/lines/{id}/explanation
```

Response should show:

```text
inputs
rate source
formula
adjustments
discount
tax
final result
```

---

# 125. Pricing Trace

Store:

```text
calculation_id
rule_version
rate_version
source_revision
inputs_hash
output_hash
```

---

# 126. Deterministic Pricing

Given identical:

```text
BOM revision
catalog version
rate versions
pricing rule version
tax version
currency rate
```

the engine must return the same result.

---

# 127. Calculation Hash

Generate:

```text
pricing_input_hash
pricing_output_hash
```

for audit and caching.

---

# 128. Pricing Snapshot

At quotation approval, snapshot:

```text
all input rates
rules
quantities
discounts
tax
FX
formulas
```

---

# 129. Immutable Quote Pricing

Once a quote is approved:

```text
do not recalculate it automatically
```

A new revision is required.

---

# 130. Repricing

Support:

```text
Reprice Draft BOQ
```

but never silently reprice:

```text
approved quotation
accepted quotation
invoice
```

---

# 131. Repricing Reasons

Track:

```text
material price update
supplier change
customer request
design revision
commercial revision
tax update
```

---

# 132. Pricing Freeze

Support:

```text
PRICE_FROZEN
```

for approved commercial documents.

---

# 133. Pricing Validity

Quotation may have:

```text
valid_until
```

After expiry:

```text
revalidation required
```

not automatic price change.

---

# 134. Price Escalation Clause

Optional contract rule:

```text
if material price increases > threshold
→ commercial review
```

---

# 135. Price Escalation Tracking

Track:

```text
original rate
current rate
difference
percentage change
impact
```

---

# 136. Cost Variance

Support:

```text
estimated cost
actual purchase cost
actual production cost
actual installation cost
```

---

# 137. Margin Variance

Calculate:

```text
planned margin
actual margin
variance
```

---

# 138. Pricing Analytics

KPIs:

```text
Average Margin %
Average Discount %
Material Cost %
Labour Cost %
Manufacturing Cost %
Installation Cost %
Quote Value
Win/Loss Pricing
```

---

# 139. Price Waterfall Report

Example:

```text
Material Cost          ₹500,000
Hardware Cost           ₹80,000
Labour                  ₹60,000
Machine                 ₹30,000
Overhead                ₹40,000
Contingency             ₹15,000
--------------------------------
Total Cost             ₹725,000

Target Margin 20%
--------------------------------
Gross Selling Price    ₹906,250

Discount 5%             ₹45,313
--------------------------------
Taxable Value           ₹860,937

Tax                    ₹154,968
--------------------------------
Final Price            ₹1,015,905
```

Exact tax and rounding rules are configurable.

---

# 140. Margin Protection

Support minimum margin:

```text
minimum_margin_percent
```

If a discount causes margin below threshold:

```text
BLOCK
```

or:

```text
REQUIRES_APPROVAL
```

---

# 141. Discount Impact

Before applying discount show:

```text
Current Price
Discount
New Price
Current Margin
New Margin
Minimum Allowed Margin
```

---

# 142. Approval Rules

Approval may depend on:

```text
quote value
discount
margin
customer
project
risk
```

---

# 143. Approval Matrix

Example:

```text
Discount <= 5% → Sales
5%-10% → Sales Manager
10%-15% → Commercial Manager
>15% → Director
```

Values are configurable.

---

# 144. Pricing Roles

Minimum:

```text
Estimator
Sales
Commercial Manager
Finance
Pricing Administrator
Factory Manager
Designer
Viewer
```

---

# 145. Pricing Permissions

Minimum permissions:

```text
pricing.view
pricing.cost.view
pricing.margin.view
pricing.rate.view
pricing.rate.create
pricing.rate.edit
pricing.rule.view
pricing.rule.create
pricing.rule.edit
pricing.simulate
pricing.approve
pricing.freeze
pricing.reprice
pricing.export
```

---

# 146. Cost Visibility

Designer:

```text
may see selling price
```

Estimator:

```text
may see cost
```

Finance:

```text
may see cost/margin
```

Client:

```text
selling price only
```

Actual permissions must be RBAC-driven.

---

# 147. Tenant Isolation

All pricing data must be tenant-scoped.

Server must enforce:

```text
authenticated tenant
```

and must never trust a client-supplied tenant ID.

---

# 148. Pricing Database Tables

Minimum:

```text
pricing_rate_cards
pricing_rates
pricing_rate_versions
pricing_price_lists
pricing_price_list_items
pricing_rules
pricing_rule_versions
pricing_templates
pricing_template_items
pricing_labour_rates
pricing_machine_rates
pricing_operation_rates
pricing_overheads
pricing_discounts
pricing_discount_rules
pricing_tax_rules
pricing_exchange_rates
pricing_scenarios
pricing_calculations
pricing_calculation_inputs
pricing_calculation_outputs
pricing_snapshots
pricing_approvals
pricing_audit_logs
```

---

# 149. Rate Card Table

Fields:

```text
id
tenant_id
name
type
currency
scope
status
effective_from
effective_to
version
created_by
approved_by
```

---

# 150. Rate Table

Fields:

```text
id
rate_card_id
catalog_product_id
catalog_variant_id
supplier_id
uom
rate
minimum_quantity
currency
effective_from
effective_to
status
```

---

# 151. Pricing Rule Table

Fields:

```text
id
tenant_id
name
code
description
rule_type
formula
scope
priority
status
```

---

# 152. Pricing Rule Version

Fields:

```text
id
pricing_rule_id
version
formula
variables_json
effective_from
effective_to
status
created_by
approved_by
```

---

# 153. Pricing Template Table

Fields:

```text
id
tenant_id
name
code
product_type
currency
status
version
```

---

# 154. Pricing Template Item

Fields:

```text
id
template_id
component_type
cost_basis
rate_source
formula
percentage
priority
```

---

# 155. Labour Rate Table

Fields:

```text
id
tenant_id
role
skill
location
uom
rate
currency
effective_from
effective_to
status
```

---

# 156. Machine Rate Table

Fields:

```text
id
tenant_id
machine_id
machine_type
uom
rate
currency
setup_rate
minimum_charge
effective_from
effective_to
status
```

---

# 157. Discount Rule Table

Fields:

```text
id
tenant_id
scope
rule_type
value
maximum_discount
minimum_order_value
customer_group
effective_from
effective_to
status
```

---

# 158. Tax Rule Table

Fields:

```text
id
tenant_id
tax_code
name
rate
calculation_method
effective_from
effective_to
status
```

---

# 159. Exchange Rate Table

Fields:

```text
id
base_currency
source_currency
rate
effective_at
source
```

---

# 160. Calculation Table

Fields:

```text
id
tenant_id
project_id
boq_id
boq_revision_id
calculation_type
status
input_hash
output_hash
rule_version
created_by
created_at
```

---

# 161. Calculation Input

Store:

```text
input_name
input_type
value
uom
source_type
source_id
version
```

---

# 162. Calculation Output

Store:

```text
cost
selling_price
discount
tax
margin
currency
result_json
```

---

# 163. Pricing Snapshot Table

Store immutable:

```text
quotation_id
boq_revision_id
catalog_versions
rate_versions
rule_versions
tax_versions
fx_versions
calculation_result
created_at
```

---

# 164. Approval Table

Store:

```text
entity_type
entity_id
approval_level
approver_id
decision
comments
timestamp
```

---

# 165. Audit Table

Record:

```text
rate_created
rate_changed
rule_created
rule_published
discount_applied
margin_override
pricing_recalculated
pricing_approved
pricing_frozen
```

---

# 166. API — Rate Cards

```http
GET    /api/v1/pricing/rate-cards
POST   /api/v1/pricing/rate-cards
GET    /api/v1/pricing/rate-cards/{id}
PATCH  /api/v1/pricing/rate-cards/{id}
POST   /api/v1/pricing/rate-cards/{id}/publish
```

---

# 167. API — Rates

```http
GET    /api/v1/pricing/rates
POST   /api/v1/pricing/rates
PATCH  /api/v1/pricing/rates/{id}
GET    /api/v1/pricing/rates/{id}/history
```

---

# 168. API — Pricing Rules

```http
GET    /api/v1/pricing/rules
POST   /api/v1/pricing/rules
GET    /api/v1/pricing/rules/{id}
PATCH  /api/v1/pricing/rules/{id}
POST   /api/v1/pricing/rules/{id}/validate
POST   /api/v1/pricing/rules/{id}/publish
```

---

# 169. API — Pricing Templates

```http
GET    /api/v1/pricing/templates
POST   /api/v1/pricing/templates
GET    /api/v1/pricing/templates/{id}
PATCH  /api/v1/pricing/templates/{id}
POST   /api/v1/pricing/templates/{id}/publish
```

---

# 170. API — Calculate

```http
POST /api/v1/pricing/calculate
```

Example:

```json
{
  "project_id": "P001",
  "boq_revision_id": "BOQ-R3",
  "pricing_mode": "HYBRID",
  "currency": "INR"
}
```

---

# 171. API — Preview

```http
POST /api/v1/pricing/preview
```

Preview must be non-destructive.

---

# 172. API — Reprice

```http
POST /api/v1/pricing/reprice
```

Request:

```json
{
  "boq_revision_id": "BOQ-R3",
  "rate_card_version": 7,
  "rule_version": 4
}
```

---

# 173. API — Explanation

```http
GET /api/v1/pricing/calculations/{id}/explanation
```

---

# 174. API — Margin

```http
POST /api/v1/pricing/margin/check
```

Returns:

```json
{
  "cost": 725000,
  "selling_price": 906250,
  "margin_percent": 20,
  "minimum_margin_percent": 18,
  "status": "PASS"
}
```

---

# 175. API — Discount

```http
POST /api/v1/pricing/discount/check
POST /api/v1/pricing/discount/apply
```

---

# 176. API — Scenario

```http
POST /api/v1/pricing/scenarios
GET  /api/v1/pricing/scenarios/{id}
POST /api/v1/pricing/scenarios/{id}/calculate
```

---

# 177. API — Approvals

```http
POST /api/v1/pricing/approvals/{id}/approve
POST /api/v1/pricing/approvals/{id}/reject
```

---

# 178. API — Snapshot

```http
POST /api/v1/pricing/snapshots
GET  /api/v1/pricing/snapshots/{id}
```

---

# 179. API Response

Example:

```json
{
  "data": {
    "cost": 725000,
    "markup_percent": 25,
    "selling_price_before_discount": 906250,
    "discount": 45313,
    "taxable_value": 860937,
    "tax": 154968,
    "grand_total": 1015905,
    "margin_percent": 20
  },
  "meta": {
    "currency": "INR",
    "calculation_version": 12
  }
}
```

---

# 180. API Errors

Use structured errors:

```text
PRICING_RATE_NOT_FOUND
PRICING_RULE_INVALID
PRICING_RULE_CIRCULAR_DEPENDENCY
PRICING_CURRENCY_ERROR
PRICING_UOM_ERROR
PRICING_MARGIN_BELOW_MINIMUM
PRICING_DISCOUNT_REQUIRES_APPROVAL
PRICING_QUOTE_FROZEN
PRICING_STALE_SOURCE
PRICING_VERSION_CONFLICT
PRICING_PERMISSION_DENIED
```

---

# 181. Frontend Architecture

Recommended:

```text
/src/pricing/

domain/
  PricingCalculation.js
  PricingLine.js
  Rate.js
  PricingRule.js
  PricingTemplate.js

engine/
  PricingEngine.js
  CostEngine.js
  SellingPriceEngine.js
  MarginEngine.js
  DiscountEngine.js
  TaxEngine.js

rates/
  RateResolver.js
  RateCardManager.js

rules/
  RuleParser.js
  RuleValidator.js
  RuleDependencyGraph.js

scenarios/
  ScenarioEngine.js

explanation/
  PricingExplanation.js

approval/
  PricingApproval.js

state/
  PricingStore.js
```

---

# 182. PHP Architecture

Recommended:

```text
src/
  Pricing/
    Domain/
    Services/
    Repositories/
    RateResolution/
    Rules/
    Calculation/
    Discount/
    Tax/
    Margin/
    Scenarios/
    Snapshots/
    Approvals/
    Validation/
    DTO/
    Policies/
```

Services:

```text
PricingEngine
CostEngine
RateResolver
PricingRuleEngine
DiscountEngine
TaxEngine
MarginEngine
PricingScenarioService
PricingSnapshotService
PricingApprovalService
PricingExplanationService
```

---

# 183. Rule Engine Design

Pricing rules should be declarative.

Example:

```json
{
  "code": "WARDROBE_INSTALLATION",
  "formula": "selling_price * 0.08",
  "variables": ["selling_price"],
  "uom": "INR"
}
```

---

# 184. Rule Evaluation Context

Context may contain:

```text
project
customer
room
furniture
BOM
BOQ
catalog
supplier
location
currency
date
```

---

# 185. Context Security

The rule engine must expose only approved variables.

Do not expose:

```text
database connection
filesystem
PHP execution
arbitrary HTTP
```

to formulas.

---

# 186. Rule Unit Safety

Formula engine should know:

```text
₹ × %
m² × ₹/m²
pcs × ₹/pcs
hours × ₹/hour
```

and reject incompatible units.

---

# 187. Example Unit Error

Invalid:

```text
5 m² + ₹800
```

Return:

```text
PRICING_UNIT_MISMATCH
```

---

# 188. Rounding Policy

Support:

```text
ROUND_HALF_UP
ROUND_HALF_DOWN
ROUND_UP
ROUND_DOWN
BANKERS
```

per tenant/business policy.

---

# 189. Internal Precision

Store calculations with high precision.

Suggested:

```text
DECIMAL(18,6)
```

or greater where necessary.

Do not use binary floating-point for financial persistence.

---

# 190. Database Money Fields

Use MySQL:

```sql
DECIMAL(18,4)
```

or an appropriate higher precision.

Never use:

```text
FLOAT
DOUBLE
```

for persisted monetary values.

---

# 191. Quantity Fields

Use appropriate:

```text
DECIMAL(18,6)
```

for engineering quantities.

---

# 192. Tax Precision

Maintain full precision until the configured tax rounding stage.

---

# 193. Financial Audit

Every approved calculation must be reproducible from:

```text
source revision
rate versions
rule versions
tax versions
FX version
```

---

# 194. Price Lock

Support:

```text
lock_price
lock_reason
locked_by
locked_at
```

---

# 195. Price Override

Authorized users can override:

```text
unit rate
discount
margin
final price
```

but every override must capture:

```text
original value
new value
reason
user
timestamp
```

---

# 196. Override Controls

Tenant can configure:

```text
allowed roles
maximum override %
approval required
```

---

# 197. Final Price Override

If a user directly changes final selling price, engine must recalculate:

```text
actual margin
actual markup
discount equivalent
```

and display the impact.

---

# 198. Minimum Margin Enforcement

If:

```text
actual margin < minimum margin
```

then:

```text
BLOCK
```

or:

```text
REQUIRES_APPROVAL
```

according to configuration.

---

# 199. Negative Margin

Negative margin must never be silently accepted.

Show:

```text
WARNING/BLOCKER
```

---

# 200. Zero/Negative Rates

Validate:

```text
rate > 0
```

unless explicitly allowed for:

```text
free item
promotional item
credit
```

---

# 201. Free Items

Support:

```text
rate = 0
```

with explicit reason:

```text
FREE_PROMOTION
COMPLIMENTARY
BUNDLED
```

---

# 202. Bundle Pricing

Support:

```text
bundle product
bundle rate
bundle components
```

Example:

```text
Kitchen Package
→ Cabinets
→ Hardware
→ Installation
```

---

# 203. Bundle Allocation

If required, allocate bundle price across components using:

```text
proportional cost
fixed allocation
manual allocation
```

---

# 204. Promotional Bundle

Support:

```text
normal value
bundle value
discount
```

---

# 205. Price Comparison

Compare:

```text
current
previous
supplier
scenario
```

---

# 206. Scenario Example

```text
STANDARD:
Cost ₹600k
Price ₹750k
Margin 20%

PREMIUM:
Cost ₹700k
Price ₹900k
Margin 22.2%
```

---

# 207. Pricing Dashboard

Show:

```text
Project Value
Total Cost
Gross Margin
Discount
Tax
Net Value
```

---

# 208. Cost Breakdown Chart

Categories:

```text
Materials
Hardware
Labour
Machine
Overhead
Installation
Transport
```

---

# 209. Margin Dashboard

Show:

```text
Target Margin
Current Margin
Minimum Margin
Margin After Discount
```

---

# 210. Pricing Waterfall UI

Show:

```text
Cost
 ↓
Overhead
 ↓
Margin
 ↓
Gross Price
 ↓
Discount
 ↓
Tax
 ↓
Final Price
```

---

# 211. Pricing Explanation UI

For every line:

```text
Why is this ₹X?
```

should be answerable.

Example:

```text
MDF:
Quantity: 8.42 m²
Rate: ₹850/m²
Source: Factory Rate Card v7
Cost: ₹7,157
```

---

# 212. Price Simulation UI

Provide sliders/inputs for:

```text
Material Rate
Markup
Margin
Discount
```

and instantly show:

```text
Selling Price
Margin
Profit
```

Simulation must not alter saved data until explicitly applied.

---

# 213. Apply Simulation

When user chooses:

```text
Apply Scenario
```

create a new pricing revision rather than overwriting an approved revision.

---

# 214. Reporting

Required reports:

```text
Project Pricing
Price Waterfall
Cost Breakdown
Margin Analysis
Rate Card
Material Cost
Supplier Cost Comparison
Discount Report
Pricing Variance
Actual vs Estimated
```

---

# 215. Export Formats

Support:

```text
CSV
XLSX
PDF
JSON
```

---

# 216. Client Pricing Export

Must show:

```text
Description
Quantity
UOM
Rate
Amount
Discount
Tax
Grand Total
```

and hide internal cost.

---

# 217. Internal Pricing Export

May show:

```text
Cost
Selling Price
Margin
Supplier
Rate Source
```

subject to RBAC.

---

# 218. Procurement Export

Show:

```text
Supplier
SKU
Quantity
Purchase Rate
Amount
Lead Time
```

---

# 219. Manufacturing Cost Export

Show:

```text
Material
Hardware
Machine
Labour
Processing
Cost
```

---

# 220. Pricing Change Impact

When a catalog rate changes:

```text
Current Draft BOQ
→ identify affected lines
→ calculate new value
→ show variance
```

Do not automatically change approved quotes.

---

# 221. Design Change Impact

When design changes:

```text
Design Revision
 ↓
BOM Revision
 ↓
BOQ Revision
 ↓
Pricing Recalculation
```

---

# 222. Pricing Staleness

Pricing becomes:

```text
STALE
```

if any required source version changes:

```text
BOM
Catalog
Rate Card
Pricing Rules
Tax
FX
```

---

# 223. Stale Pricing UI

Display:

```text
Pricing is based on an older BOM/rate version.
Recalculate?
```

---

# 224. Approval State

Pricing statuses:

```text
DRAFT
CALCULATED
REVIEW
APPROVAL_REQUIRED
APPROVED
FROZEN
SUPERSEDED
```

---

# 225. Quote Integration

Quotation should reference:

```text
approved pricing snapshot
```

not a live calculation.

---

# 226. Invoice Integration

Invoice should reference:

```text
accepted quotation
```

and not recalculate historical pricing.

---

# 227. Purchase Order Integration

PO uses:

```text
procurement quantity
supplier rate
approved supplier
```

not customer selling price.

---

# 228. Manufacturing Integration

Manufacturing may use internal cost for analytics but should use engineering quantities and approved manufacturing rates.

---

# 229. MES Integration

MES may report:

```text
actual production cost
actual labour
actual machine time
actual material consumption
```

back to the cost analytics layer.

---

# 230. Actual Cost

Actual cost must remain separate from estimated cost:

```text
estimated_cost
actual_cost
variance
```

---

# 231. Actual Labour

MES/production may return:

```text
actual hours
```

Pricing analytics can calculate:

```text
actual labour cost
```

---

# 232. Actual Machine Cost

MES can return:

```text
actual machine time
```

for actual manufacturing cost.

---

# 233. Actual Material Cost

Procurement can return:

```text
actual purchase price
```

and inventory can return:

```text
actual consumption
```

---

# 234. Profitability

After project completion:

```text
Revenue
- Actual Cost
= Actual Gross Profit
```

---

# 235. Estimated vs Actual Report

Show:

```text
Estimated Cost
Actual Cost
Variance
Estimated Margin
Actual Margin
```

---

# 236. Performance Requirements

Target:

```text
Standard BOQ pricing < 2 seconds
5,000 lines < 5 seconds
10,000+ lines → asynchronous
```

Heavy simulations may run asynchronously.

---

# 237. Incremental Pricing

When only one furniture item changes:

```text
reprice affected subtree
```

instead of recalculating unrelated project lines.

---

# 238. Caching

Cache stable:

```text
rate resolution
rule compilation
tax resolution
FX resolution
pricing template
```

---

# 239. Cache Invalidation

Invalidate on:

```text
rate version
rule version
tax version
FX version
BOM revision
catalog version
```

---

# 240. Deterministic Calculation

Same:

```text
inputs
versions
rules
```

must produce the same output.

---

# 241. Pricing Regression Tests

Every pricing defect must become a permanent regression test.

---

# 242. Unit Tests

Minimum:

```text
markup
margin
discount
tax
UOM conversion
currency conversion
rate resolution
formula evaluation
overhead
waste
labour
machine
installation
```

---

# 243. Integration Tests

Test:

```text
Catalog
→ BOM
→ Pricing
→ BOQ
→ Quotation
```

---

# 244. Furniture Pricing Tests

Test:

```text
Kitchen
Wardrobe
Storage
Custom Furniture
```

with:

```text
material
hardware
labour
installation
```

---

# 245. Manufacturing Pricing Tests

Test:

```text
cutting
edge banding
drilling
routing
machine setup
machine hours
```

---

# 246. Margin Tests

Verify:

```text
target margin
actual margin
discount impact
minimum margin
approval thresholds
```

---

# 247. Version Tests

Verify:

```text
Rate v1
→ Quote v1

Rate v2
→ New draft calculation

Quote v1 unchanged
```

---

# 248. Security Tests

Verify:

```text
Tenant A cannot read Tenant B rates
Designer cannot see internal costs
Client cannot see supplier rates
Unauthorized user cannot modify pricing rules
Approved quote cannot be overwritten
```

---

# 249. Formula Security Tests

Verify pricing formulas cannot:

```text
execute PHP
execute JS
access filesystem
access database
make network requests
```

---

# 250. Concurrency Tests

Two users editing the same pricing revision must produce:

```text
version conflict
```

rather than silent overwrite.

---

# 251. Pricing Engine Acceptance Criteria

The engine is acceptable when:

```text
[ ] It can price BOM-driven materials
[ ] It can price hardware
[ ] It can price labour
[ ] It can price manufacturing operations
[ ] It can price installation
[ ] It can price transport
[ ] It supports overhead
[ ] It supports contingency
[ ] It supports markup
[ ] It supports target margin
[ ] It supports discount
[ ] It supports tax
[ ] It supports multi-currency
[ ] It supports rate cards
[ ] It supports price history
[ ] It supports effective dates
[ ] It supports pricing templates
[ ] It supports formula rules
[ ] It prevents circular formulas
[ ] It supports scenarios
[ ] It supports simulations
[ ] It supports price overrides
[ ] It supports approval thresholds
[ ] It supports minimum margin
[ ] It provides pricing explanations
[ ] It provides immutable snapshots
[ ] It supports quotation integration
[ ] It supports procurement integration
[ ] It supports manufacturing integration
[ ] It supports actual-vs-estimated analytics
[ ] It supports RBAC
[ ] It supports tenant isolation
[ ] It provides audit trails
[ ] It passes unit tests
[ ] It passes integration tests
[ ] It passes security tests
[ ] It passes regression tests
[ ] It meets performance targets
```

---

# 252. Cursor Pre-Implementation Analysis

Before coding, Cursor MUST inspect:

```text
existing catalog
existing material prices
existing BOM
existing BOQ
existing quotation
existing customer pricing
existing supplier pricing
existing labour rates
existing manufacturing rates
existing tax logic
existing currency logic
existing discounts
existing margins
existing invoice logic
existing RBAC
existing tenant model
```

Cursor must produce:

```text
CURRENT PRICING ARCHITECTURE
CURRENT RATE SOURCES
CURRENT FORMULAS
CURRENT COST MODEL
CURRENT QUOTATION FLOW
CURRENT TAX MODEL
CURRENT DISCOUNT MODEL
DUPLICATE LOGIC
DATA QUALITY ISSUES
MIGRATION RISKS
TARGET PRICING ARCHITECTURE
MIGRATION PLAN
```

Do not create duplicate pricing logic if equivalent logic already exists.

---

# 253. Cursor Implementation Sequence

## Phase 1 — Foundations

```text
UOM
Currency
Money
Precision
Rounding
```

## Phase 2 — Rate Resolution

```text
Rate Cards
Supplier Rates
Catalog Rates
Labour Rates
Machine Rates
```

## Phase 3 — Calculation

```text
Cost Engine
Quantity Resolver
Formula Engine
```

## Phase 4 — Commercial

```text
Overhead
Contingency
Markup
Margin
Discount
Tax
```

## Phase 5 — Templates

```text
Pricing Templates
Pricing Rules
Rule Versions
```

## Phase 6 — Scenarios

```text
Simulation
What-if
Budget
Alternative Materials
```

## Phase 7 — Governance

```text
Approvals
Overrides
Locks
Snapshots
Audit
```

## Phase 8 — Integration

```text
BOM
BOQ
Quotation
Procurement
Manufacturing
MES
```

## Phase 9 — Analytics

```text
Cost
Margin
Variance
Actual vs Estimated
```

## Phase 10 — Optimization

```text
Caching
Incremental Repricing
Async Calculations
Performance
```

---

# 254. Recommended Pricing Engine Service Flow

```text
PricingRequest
       ↓
InputValidator
       ↓
SourceVersionResolver
       ↓
QuantityResolver
       ↓
RateResolver
       ↓
CostEngine
       ↓
OverheadEngine
       ↓
ContingencyEngine
       ↓
MarginEngine
       ↓
DiscountEngine
       ↓
TaxEngine
       ↓
RoundingEngine
       ↓
MarginValidator
       ↓
PricingSnapshot
       ↓
PricingResponse
```

---

# 255. Final Architecture

```text
                         PRICING ENGINE
                              │
        ┌─────────────────────┼─────────────────────┐
        ↓                     ↓                     ↓
      CATALOG                BOM                   BOQ
        │                     │                     │
        ↓                     ↓                     ↓
     MATERIAL               QTY                COMMERCIAL
      RATES               REQUIREMENT             LINES
        │                     │                     │
        └─────────────────────┼─────────────────────┘
                              ↓
                       RATE RESOLUTION
                              ↓
                       COST CALCULATION
                              ↓
        ┌─────────────────────┼─────────────────────┐
        ↓                     ↓                     ↓
     MATERIAL              LABOUR                MACHINE
        ↓                     ↓                     ↓
        └─────────────────────┼─────────────────────┘
                              ↓
                          OVERHEAD
                              ↓
                         CONTINGENCY
                              ↓
                         TOTAL COST
                              ↓
                    MARKUP / TARGET MARGIN
                              ↓
                     GROSS SELLING PRICE
                              ↓
                           DISCOUNT
                              ↓
                         TAX ENGINE
                              ↓
                         FINAL PRICE
                              ↓
                         QUOTATION
                              ↓
                   PURCHASE / PRODUCTION
                              ↓
                      ACTUAL COST DATA
                              ↓
                       PROFITABILITY
```

---

# 256. Final Product Principle

The Pricing Engine must answer, for every commercial amount:

```text
What quantity was used?
Where did that quantity come from?
Which rate was used?
Who supplied the rate?
Which rate-card version was used?
Which pricing rule was used?
Which currency was used?
Which tax rule was used?
Which discount was applied?
What was the resulting margin?
Who approved it?
Can the exact price be reproduced later?
```

The fundamental implementation rule is:

> **Pricing must be deterministic, versioned, explainable, auditable and traceable back to the engineering BOM and catalog data.**

A designer changing a cabinet dimension should not require a person to manually recalculate a spreadsheet. The system must propagate the engineering change through:

```text
Design
 ↓
Furniture
 ↓
BOM
 ↓
BOQ
 ↓
Pricing
 ↓
Quotation Impact
 ↓
Manufacturing Impact
```

without losing historical revisions.
