# RBAC & Permission Matrix Specification
## Interior Design, Parametric Furniture, Estimation, Manufacturing & MES Platform

**Document ID:** RBAC-IDFM-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, Frontend Developers, QA, Security, DevOps  
**Backend:** PHP 8.x  
**Database:** MySQL 8.x  
**Frontend:** JavaScript ES6+  
**API:** REST `/api/v1`  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the Role-Based Access Control (RBAC), permission model, role hierarchy, resource-level authorization, workflow permissions, tenant isolation rules and implementation requirements for the complete platform.

The platform covers:

- Platform administration
- Tenant administration
- CRM
- Project management
- Architectural 2D/3D design
- Parametric furniture
- Component Designer
- Material and catalog management
- BOM / BOQ
- Pricing
- Quotations / proposals
- Engineering
- Manufacturing
- Cutlists
- Nesting
- CNC/CAM
- MES
- Production
- QR tracking
- QC
- Packing
- Dispatch
- Installation
- Documents
- AI
- Reporting
- Audit
- White-label administration

RBAC MUST be implemented centrally and MUST NOT depend only on frontend UI visibility.

---

# 2. Core Authorization Principle

The system MUST enforce:

```text
IDENTITY
   ↓
TENANT
   ↓
ROLE
   ↓
PERMISSION
   ↓
RESOURCE ACCESS
   ↓
WORKFLOW STATE
   ↓
BUSINESS RULE
```

Having a permission does NOT automatically mean that a user can modify every record.

Example:

```text
Permission:
manufacturing.release

+
Project access:
YES

+
Manufacturing revision:
READY

+
Engineering validation:
PASSED

+
No blocker:
YES

=
Release allowed
```

---

# 3. Authorization Model

Use a hybrid model:

```text
RBAC
+
Tenant Isolation
+
Resource-Level Access
+
Project Membership
+
Workflow State
+
Ownership
+
Business Rules
```

This is preferable to pure role-based authorization because the platform contains projects, clients, factories and manufacturing records with different access boundaries.

---

# 4. Authorization Dimensions

Every protected action SHOULD evaluate:

### 4.1 Authentication

Is the user authenticated?

### 4.2 Tenant

Does the resource belong to the user's active tenant?

### 4.3 Permission

Does the user's effective permission set contain the required permission?

### 4.4 Resource

Does the user have access to the specific resource?

### 4.5 State

Is the resource in a state where the action is allowed?

### 4.6 Ownership / Scope

Does the user have ownership or assigned scope where required?

### 4.7 Separation of Duties

Is the action restricted because another role must approve it?

---

# 5. Role Hierarchy

The default system roles are:

```text
PLATFORM_SUPER_ADMIN
TENANT_OWNER
TENANT_ADMIN
OPERATIONS_MANAGER
PROJECT_MANAGER
SENIOR_DESIGNER
DESIGNER
DESIGN_REVIEWER
ENGINEER
ESTIMATOR
SALES_MANAGER
SALES_USER
MANUFACTURING_MANAGER
PRODUCTION_MANAGER
PRODUCTION_SUPERVISOR
MACHINE_OPERATOR
QC_MANAGER
QC_INSPECTOR
WAREHOUSE_OPERATOR
PACKING_OPERATOR
DISPATCH_MANAGER
INSTALLATION_MANAGER
INSTALLATION_USER
CLIENT_ADMIN
CLIENT_USER
VIEWER
```

Additional custom roles MAY be created by authorized tenant administrators.

---

# 6. Platform vs Tenant Roles

## Platform Roles

These operate across tenants:

```text
PLATFORM_SUPER_ADMIN
```

## Tenant Roles

These operate only inside a tenant:

```text
TENANT_OWNER
TENANT_ADMIN
OPERATIONS_MANAGER
PROJECT_MANAGER
SENIOR_DESIGNER
DESIGNER
DESIGN_REVIEWER
ENGINEER
ESTIMATOR
SALES_MANAGER
SALES_USER
MANUFACTURING_MANAGER
PRODUCTION_MANAGER
PRODUCTION_SUPERVISOR
MACHINE_OPERATOR
QC_MANAGER
QC_INSPECTOR
WAREHOUSE_OPERATOR
PACKING_OPERATOR
DISPATCH_MANAGER
INSTALLATION_MANAGER
INSTALLATION_USER
CLIENT_ADMIN
CLIENT_USER
VIEWER
```

A tenant user MUST NEVER receive platform-level permissions.

---

# 7. System Role Characteristics

## 7.1 PLATFORM_SUPER_ADMIN

Purpose:

Global platform administration.

Can:

- manage tenants
- suspend tenants
- manage global system configuration
- manage system roles
- manage system permissions
- inspect platform health
- manage global feature flags
- access platform audit
- support tenant troubleshooting

Cannot normally:

- approve a client's commercial quotation on behalf of the tenant
- release manufacturing on behalf of a tenant without explicit support elevation
- impersonate users silently

All support/impersonation actions MUST be audited.

---

# 8. TENANT_OWNER

Purpose:

Highest business role inside a tenant.

Can:

- manage tenant settings
- manage users
- manage roles
- manage branding
- manage domains
- manage catalogs
- manage pricing
- manage projects
- access reports
- approve commercial workflows
- access manufacturing
- access MES
- access audit

Cannot:

- modify system permission definitions
- access another tenant

---

# 9. TENANT_ADMIN

Purpose:

Tenant operational administration.

Can:

- manage users
- manage roles
- configure tenant
- manage branding
- manage catalogs
- access all tenant projects
- access audit
- manage feature flags allowed at tenant level

Should not by default:

- approve their own sensitive commercial transactions where separation of duties is enabled
- release manufacturing without manufacturing permission

---

# 10. OPERATIONS_MANAGER

Purpose:

Cross-functional operational management.

Can:

- access CRM
- access projects
- access design
- access commercial
- access engineering
- access manufacturing
- access MES
- access reports

Can approve operational workflows where configured.

---

# 11. PROJECT_MANAGER

Purpose:

Own project execution.

Can:

- create projects
- manage project team
- manage project lifecycle
- coordinate design
- request engineering
- review commercial status
- monitor manufacturing
- monitor production
- view QC
- view dispatch
- manage project documents

Should not:

- change global pricing
- publish manufacturing templates
- modify released manufacturing data

---

# 12. SENIOR_DESIGNER

Can:

- create/edit designs
- create furniture
- create parametric furniture
- use component library
- create custom furniture designs
- create design revisions
- submit designs for review
- view BOM/BOQ
- request engineering validation

Cannot by default:

- approve own design
- release manufacturing
- change global pricing

---

# 13. DESIGNER

Can:

- view assigned projects
- create/edit assigned design
- create furniture
- use templates
- modify parameters
- generate previews
- create design revisions
- view materials
- view BOM/BOQ

Cannot:

- approve own design
- publish enterprise templates unless granted
- release manufacturing
- change approved quotation

---

# 14. DESIGN_REVIEWER

Can:

- view designs
- review revisions
- comment
- approve/reject design
- request changes
- view design validation

Cannot:

- directly change the designer's approved revision unless explicitly assigned edit permission.

---

# 15. ENGINEER

Can:

- view approved design
- inspect dimensions
- validate manufacturing feasibility
- run engineering validation
- resolve validation findings
- generate manufacturing revision
- inspect panels
- inspect cutlists
- inspect nesting
- inspect CNC outputs

Cannot:

- approve commercial quotation
- change client pricing
- release manufacturing unless separately granted

---

# 16. ESTIMATOR

Can:

- view project
- view furniture
- view BOM
- generate BOQ
- manage pricing calculations
- prepare quotations
- revise quotations before approval
- export commercial documents

Cannot:

- modify engineering design
- release manufacturing
- change published pricing without permission

---

# 17. SALES_MANAGER

Can:

- manage leads
- manage clients
- manage opportunities
- create projects
- view quotations
- approve/send quotations where configured
- view commercial reports

Cannot:

- modify engineering
- release manufacturing

---

# 18. SALES_USER

Can:

- create leads
- manage assigned clients
- manage opportunities
- create project requests
- view approved commercial documents

Cannot:

- approve own quotations unless explicitly configured.

---

# 19. MANUFACTURING_MANAGER

Can:

- view approved projects
- view engineering outputs
- generate manufacturing
- manage manufacturing revisions
- generate cutlists
- run nesting
- configure machine mappings
- generate CNC
- release manufacturing
- manage production planning

Cannot:

- modify the approved design directly after release.

---

# 20. PRODUCTION_MANAGER

Can:

- manage production jobs
- schedule production
- assign workstations
- manage production stages
- monitor production
- place jobs on hold
- manage rework
- view QC
- view panels
- view manufacturing documents

---

# 21. PRODUCTION_SUPERVISOR

Can:

- start/complete production operations
- assign operators
- scan panels
- manage stage transitions
- create rework events
- view manufacturing instructions

Cannot:

- change engineering design
- change pricing
- release manufacturing

---

# 22. MACHINE_OPERATOR

Can:

- view assigned jobs
- scan panels
- start/complete allowed machine operations
- view machine instructions
- report machine issues
- report defects

Cannot:

- modify design
- modify pricing
- release manufacturing

---

# 23. QC_MANAGER

Can:

- configure QC checklists
- create inspections
- approve/reject QC
- manage defects
- manage rework
- view production

---

# 24. QC_INSPECTOR

Can:

- perform assigned inspections
- record measurements
- pass/fail inspections
- create defects
- request rework

Cannot:

- alter manufacturing source data.

---

# 25. WAREHOUSE_OPERATOR

Can:

- view required materials
- receive material
- update warehouse status where inventory module exists
- view panels/packages
- scan QR/barcodes

---

# 26. PACKING_OPERATOR

Can:

- view production-ready panels
- create packages
- scan panels
- add/remove package items before package closure
- print package labels

Cannot:

- modify manufacturing design.

---

# 27. DISPATCH_MANAGER

Can:

- create dispatch
- assign packages
- update dispatch status
- record carrier/vehicle
- mark delivery

Cannot:

- change manufacturing source.

---

# 28. INSTALLATION_MANAGER

Can:

- create installation jobs
- assign installers
- schedule installation
- monitor completion
- close installation

---

# 29. INSTALLATION_USER

Can:

- view assigned installation jobs
- scan package/panel
- start installation
- report issues
- complete assigned installation tasks

---

# 30. CLIENT_ADMIN

Can:

- access their tenant/client portal
- view assigned projects
- view design presentations
- comment
- approve/reject requested client workflows
- view quotations
- download documents

Cannot:

- access internal manufacturing
- access internal cost pricing
- access internal production details unless explicitly enabled.

---

# 31. CLIENT_USER

Can:

- view assigned projects
- view client presentation
- comment
- view permitted documents

Cannot:

- access internal design engineering
- access cost data
- access manufacturing
- access internal audit

---

# 32. VIEWER

Read-only access to explicitly assigned resources.

Cannot:

- create
- edit
- delete
- approve
- release
- publish
- execute production operations

---

# 33. Permission Naming Convention

Permissions MUST follow:

```text
<module>.<action>
```

Examples:

```text
project.view
project.create
project.update
project.delete
project.approve
```

For sensitive commands:

```text
manufacturing.release
quotation.approve
pricing.publish
template.publish
```

---

# 34. Standard CRUD Actions

Supported base actions:

```text
view
list
create
update
delete
archive
restore
export
```

Workflow actions:

```text
submit
review
approve
reject
publish
release
cancel
hold
resume
complete
```

---

# 35. Platform Permission Matrix

Legend:

```text
✓ = Allowed
○ = Conditional / assigned scope
— = Not allowed
```

| Permission | Platform Admin | Tenant Owner | Tenant Admin |
|---|---:|---:|---:|
| tenant.view | ✓ | ✓ | ✓ |
| tenant.update | ✓ | ✓ | ✓ |
| tenant.suspend | ✓ | — | — |
| tenant.branding | ✓ | ✓ | ✓ |
| tenant.domain.manage | ✓ | ✓ | ✓ |
| tenant.feature.manage | ✓ | ✓ | ○ |
| user.view | ✓ | ✓ | ✓ |
| user.create | ✓ | ✓ | ✓ |
| user.update | ✓ | ✓ | ✓ |
| user.disable | ✓ | ✓ | ✓ |
| role.view | ✓ | ✓ | ✓ |
| role.create | ✓ | ✓ | ✓ |
| role.update | ✓ | ✓ | ✓ |
| role.delete | ✓ | ✓ | ○ |
| permission.view | ✓ | ✓ | ✓ |
| permission.manage | ✓ | — | — |
| audit.view | ✓ | ✓ | ✓ |

---

# 36. CRM Permission Matrix

| Permission | Owner | Admin | PM | Sales Mgr | Sales User | Designer |
|---|---:|---:|---:|---:|---:|---:|
| lead.view | ✓ | ✓ | ✓ | ✓ | ○ | — |
| lead.create | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| lead.update | ✓ | ✓ | ✓ | ✓ | ○ | — |
| lead.delete | ✓ | ✓ | — | ✓ | ○ | — |
| lead.convert | ✓ | ✓ | ✓ | ✓ | ○ | — |
| client.view | ✓ | ✓ | ✓ | ✓ | ○ | ○ |
| client.create | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| client.update | ✓ | ✓ | ○ | ✓ | ○ | — |
| opportunity.view | ✓ | ✓ | ✓ | ✓ | ○ | — |
| opportunity.create | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| opportunity.update | ✓ | ✓ | ○ | ✓ | ○ | — |
| opportunity.convert | ✓ | ✓ | ✓ | ✓ | ○ | — |

---

# 37. Project Permission Matrix

| Permission | Owner | Admin | PM | Senior Designer | Designer | Reviewer | Engineer | Client |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| project.view | ✓ | ✓ | ✓ | ○ | ○ | ○ | ○ | ○ |
| project.list | ✓ | ✓ | ✓ | ○ | ○ | ○ | ○ | ○ |
| project.create | ✓ | ✓ | ✓ | ✓ | ○ | — | — | — |
| project.update | ✓ | ✓ | ✓ | ○ | ○ | — | ○ | — |
| project.delete | ✓ | ✓ | ✓ | — | — | — | — | — |
| project.archive | ✓ | ✓ | ✓ | — | — | — | — | — |
| project.team.manage | ✓ | ✓ | ✓ | — | — | — | — | — |
| project.approve | ✓ | ○ | ✓ | — | — | ✓ | — | ○ |
| project.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |

---

# 38. Design Permission Matrix

| Permission | Owner | Admin | PM | Senior Designer | Designer | Reviewer | Engineer | Client |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| design.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| design.create | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — |
| design.update | ✓ | ✓ | ○ | ✓ | ✓ | — | ○ | — |
| design.delete | ✓ | ✓ | ○ | ✓ | ○ | — | — | — |
| design.comment | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| design.submit_review | ✓ | ✓ | ✓ | ✓ | ✓ | — | — | — |
| design.review | ✓ | ✓ | ✓ | ✓ | — | ✓ | ○ | — |
| design.approve | ✓ | ○ | ✓ | — | — | ✓ | — | ○ |
| design.reject | ✓ | ✓ | ✓ | ✓ | — | ✓ | ○ | ○ |
| design.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |

---

# 39. Architectural Object Permission Matrix

| Permission | Designer | Senior Designer | Reviewer | Engineer | PM |
|---|---:|---:|---:|---:|---:|
| design_object.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| design_object.create | ✓ | ✓ | — | — | ○ |
| design_object.update | ✓ | ✓ | — | ○ | ○ |
| design_object.delete | ○ | ✓ | — | — | — |
| design_object.bulk_update | ✓ | ✓ | — | ○ | — |
| design_object.revert | ○ | ✓ | — | — | ○ |
| design_snapshot.create | ✓ | ✓ | ✓ | ✓ | ✓ |
| design_snapshot.restore | ○ | ✓ | — | — | ✓ |

---

# 40. Furniture Permission Matrix

| Permission | Designer | Senior Designer | Reviewer | Engineer | Manufacturing Manager |
|---|---:|---:|---:|---:|---:|
| furniture.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| furniture.create | ✓ | ✓ | — | — | ○ |
| furniture.update | ✓ | ✓ | — | ○ | ○ |
| furniture.delete | ○ | ✓ | — | — | — |
| furniture.validate | ✓ | ✓ | — | ✓ | ✓ |
| furniture.regenerate | ✓ | ✓ | — | ✓ | ✓ |
| furniture.revision.create | ✓ | ✓ | — | ✓ | ✓ |
| furniture.revision.approve | — | ✓ | ✓ | — | — |
| furniture.export | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# 41. Component Designer Permission Matrix

| Permission | Designer | Senior Designer | Engineer | Manufacturing Manager | Admin |
|---|---:|---:|---:|---:|---:|
| template.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| template.create | — | ✓ | ○ | ✓ | ✓ |
| template.update | ○ | ✓ | ○ | ✓ | ✓ |
| template.delete | — | ✓ | — | ✓ | ✓ |
| template.version.create | — | ✓ | ✓ | ✓ | ✓ |
| template.validate | ✓ | ✓ | ✓ | ✓ | ✓ |
| template.preview | ✓ | ✓ | ✓ | ✓ | ✓ |
| template.publish | — | ○ | ✓ | ✓ | ✓ |
| template.archive | — | ✓ | — | ✓ | ✓ |
| template.import | — | ✓ | ○ | ✓ | ✓ |

Publishing SHOULD require a reviewer/authorized role where enterprise governance is enabled.

---

# 42. Catalog Permission Matrix

| Permission | Admin | Owner | Designer | Engineer | Estimator | Manufacturing Manager |
|---|---:|---:|---:|---:|---:|---:|
| material.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| material.create | ✓ | ✓ | — | ✓ | — | ✓ |
| material.update | ✓ | ✓ | — | ○ | — | ✓ |
| material.delete | ✓ | ✓ | — | — | — | ○ |
| board.manage | ✓ | ✓ | — | ✓ | — | ✓ |
| laminate.manage | ✓ | ✓ | — | ✓ | — | ✓ |
| edge_band.manage | ✓ | ✓ | — | ✓ | — | ✓ |
| hardware.manage | ✓ | ✓ | — | ✓ | — | ✓ |
| catalog.import | ✓ | ✓ | — | ✓ | — | ✓ |
| catalog.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# 43. Pricing Permission Matrix

| Permission | Owner | Admin | Estimator | Sales Manager | Sales User | Designer | Manufacturing Manager |
|---|---:|---:|---:|---:|---:|---:|---:|
| pricing.view | ✓ | ✓ | ✓ | ✓ | ○ | ✓ | ✓ |
| pricing.calculate | ✓ | ✓ | ✓ | ○ | ○ | ○ | ✓ |
| pricing.rule.view | ✓ | ✓ | ✓ | ○ | — | — | ✓ |
| pricing.rule.create | ✓ | ✓ | ✓ | — | — | — | ✓ |
| pricing.rule.update | ✓ | ✓ | ✓ | — | — | — | ✓ |
| pricing.publish | ✓ | ○ | ✓ | — | — | — | ○ |
| pricing.export | ✓ | ✓ | ✓ | ✓ | ○ | — | ✓ |

---

# 44. BOM Permission Matrix

| Permission | Designer | Senior Designer | Engineer | Estimator | Manufacturing Manager | PM |
|---|---:|---:|---:|---:|---:|---:|
| bom.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| bom.generate | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| bom.regenerate | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| bom.approve | — | ✓ | ✓ | ✓ | ✓ | ✓ |
| bom.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| bom.lock | — | ✓ | ✓ | ✓ | ✓ | ○ |

---

# 45. BOQ Permission Matrix

| Permission | Estimator | Sales Manager | PM | Designer | Admin | Manufacturing Manager |
|---|---:|---:|---:|---:|---:|---:|
| boq.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| boq.generate | ✓ | ✓ | ✓ | ○ | ✓ | ✓ |
| boq.update | ✓ | ✓ | ○ | — | ✓ | ○ |
| boq.approve | ✓ | ✓ | ✓ | — | ✓ | — |
| boq.export | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# 46. Quotation Permission Matrix

| Permission | Estimator | Sales User | Sales Manager | PM | Owner | Client |
|---|---:|---:|---:|---:|---:|---:|
| quotation.view | ✓ | ○ | ✓ | ✓ | ✓ | ○ |
| quotation.create | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| quotation.update | ✓ | ○ | ✓ | ○ | ✓ | — |
| quotation.delete | ✓ | ○ | ✓ | — | ✓ | — |
| quotation.submit_review | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| quotation.send | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| quotation.approve | — | — | ✓ | ○ | ✓ | ○ |
| quotation.reject | — | — | ✓ | ✓ | ✓ | ○ |
| quotation.cancel | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| quotation.export | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |

---

# 47. Separation of Duties — Commercial

Default recommended policy:

```text
Estimator
    ↓
creates quotation

Sales Manager
    ↓
reviews quotation

Client
    ↓
approves quotation
```

The creator of a quotation SHOULD NOT be allowed to approve the same quotation unless a tenant explicitly disables separation-of-duties controls.

---

# 48. Engineering Permission Matrix

| Permission | Designer | Reviewer | Engineer | PM | Manufacturing Manager |
|---|---:|---:|---:|---:|---:|
| engineering.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| engineering.validate | — | ○ | ✓ | — | ✓ |
| engineering.run_validation | — | ○ | ✓ | — | ✓ |
| engineering.resolve_issue | — | — | ✓ | — | ✓ |
| engineering.approve | — | ✓ | ✓ | ✓ | ○ |
| engineering.export | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# 49. Manufacturing Permission Matrix

| Permission | Engineer | Manufacturing Manager | Production Manager | PM | Admin |
|---|---:|---:|---:|---:|---:|
| manufacturing.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| manufacturing.create_revision | ✓ | ✓ | ○ | — | ✓ |
| manufacturing.generate | ✓ | ✓ | ✓ | — | ✓ |
| manufacturing.validate | ✓ | ✓ | ✓ | — | ✓ |
| manufacturing.modify_draft | ✓ | ✓ | ○ | — | ✓ |
| manufacturing.release | — | ✓ | ○ | — | ○ |
| manufacturing.supersede | — | ✓ | ○ | — | ✓ |
| manufacturing.export | ✓ | ✓ | ✓ | ✓ | ✓ |

---

# 50. Manufacturing Release Rule

The `manufacturing.release` permission alone is insufficient.

The API MUST additionally check:

```text
project.status = PRODUCTION_READY
AND
engineering.status = PASSED
AND
blocker_count = 0
AND
manufacturing.status = READY
AND
required BOM = current
AND
required cutlist = current
AND
required nesting = current
AND
required CNC = current
```

Only then:

```text
RELEASED
```

---

# 51. Released Data Protection

Once:

```text
manufacturing_revision.status = RELEASED
```

the following permissions MUST NOT allow direct mutation:

```text
manufacturing.update
panel.update
cutlist.update
nesting.update
cnc.update
```

Instead:

```text
Create New Manufacturing Revision
```

is required.

---

# 52. MES Permission Matrix

| Permission | Production Manager | Supervisor | Machine Operator | QC Manager | QC Inspector | Warehouse | Packing | Dispatch |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| mes.dashboard.view | ✓ | ✓ | ○ | ✓ | ✓ | ○ | ○ | ✓ |
| production.view | ✓ | ✓ | ○ | ✓ | ✓ | ○ | ✓ | ○ |
| production.create | ✓ | ✓ | — | — | — | — | — | — |
| production.schedule | ✓ | ✓ | — | — | — | — | — | — |
| production.start | ✓ | ✓ | ✓ | — | — | — | — | — |
| production.pause | ✓ | ✓ | ✓ | — | — | — | — | — |
| production.resume | ✓ | ✓ | ✓ | — | — | — | — | — |
| production.hold | ✓ | ✓ | ✓ | ○ | — | — | — | — |
| production.complete | ✓ | ✓ | ✓ | — | — | — | — | — |
| production.rework | ✓ | ✓ | ○ | ✓ | ✓ | — | — | — |

---

# 53. Production State Permission

### Machine Operator

Allowed:

```text
READY → CUTTING
CUTTING → EDGE_BANDING
EDGE_BANDING → DRILLING
DRILLING → ROUTING
```

only when assigned to the relevant workstation/job.

### Supervisor

Can override or correct stage transitions where authorized.

### Production Manager

Can manage exceptional transitions and rework.

All overrides MUST be audited.

---

# 54. QR Permission Matrix

| Permission | Supervisor | Machine Operator | QC | Warehouse | Packing | Dispatch | Installation |
|---|---:|---:|---:|---:|---:|---:|---:|
| qr.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| qr.generate | ✓ | — | — | — | ✓ | — | — |
| qr.scan | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| qr.revoke | ✓ | — | — | — | ✓ | — | — |
| qr.transition | ✓ | ✓ | ✓ | ○ | ✓ | ○ | ○ |

---

# 55. QR Security

QR scanning MUST NOT rely solely on panel ID.

Use:

```text
secure QR token
+
tenant
+
panel
+
current manufacturing revision
+
production state
+
workstation
+
permission
```

---

# 56. QC Permission Matrix

| Permission | QC Manager | QC Inspector | Production Manager | Engineer |
|---|---:|---:|---:|---:|
| qc.view | ✓ | ✓ | ✓ | ✓ |
| qc.create | ✓ | ✓ | ○ | — |
| qc.start | ✓ | ✓ | ○ | — |
| qc.record_result | ✓ | ✓ | — | ○ |
| qc.pass | ✓ | ✓ | — | ○ |
| qc.fail | ✓ | ✓ | ○ | ✓ |
| qc.create_defect | ✓ | ✓ | ✓ | ✓ |
| qc.resolve_defect | ✓ | ✓ | ✓ | ✓ |
| qc.configure | ✓ | — | — | — |

---

# 57. QC Approval Separation

Recommended:

```text
Production Operator
      ↓
Production complete
      ↓
QC Inspector
      ↓
PASS / FAIL
      ↓
QC Manager for high-risk jobs
```

The same user SHOULD NOT both produce and approve a high-risk QC inspection.

---

# 58. Packing Permission Matrix

| Permission | Packing Operator | Production Supervisor | Warehouse | Dispatch Manager |
|---|---:|---:|---:|---:|
| package.view | ✓ | ✓ | ✓ | ✓ |
| package.create | ✓ | ✓ | ✓ | ✓ |
| package.update | ✓ | ✓ | ✓ | ✓ |
| package.add_item | ✓ | ✓ | ✓ | ✓ |
| package.remove_item | ✓ | ✓ | ✓ | ✓ |
| package.close | ✓ | ✓ | ✓ | ✓ |
| package.print_label | ✓ | ✓ | ✓ | ✓ |

After:

```text
package.status = CLOSED
```

contents SHOULD become immutable.

---

# 59. Dispatch Permission Matrix

| Permission | Dispatch Manager | Packing Operator | PM | Admin |
|---|---:|---:|---:|---:|
| dispatch.view | ✓ | ○ | ✓ | ✓ |
| dispatch.create | ✓ | ○ | ✓ | ✓ |
| dispatch.update | ✓ | — | ○ | ✓ |
| dispatch.dispatch | ✓ | — | ✓ | ✓ |
| dispatch.deliver | ✓ | — | ✓ | ✓ |
| dispatch.cancel | ✓ | — | ✓ | ✓ |

---

# 60. Installation Permission Matrix

| Permission | Installation Manager | Installation User | PM | Client |
|---|---:|---:|---:|---:|
| installation.view | ✓ | ○ | ✓ | ○ |
| installation.create | ✓ | — | ✓ | — |
| installation.assign | ✓ | — | ✓ | — |
| installation.start | ✓ | ✓ | ✓ | — |
| installation.complete | ✓ | ✓ | ✓ | ○ |
| installation.issue | ✓ | ✓ | ✓ | ○ |

---

# 61. Document Permission Matrix

| Permission | Admin | PM | Designer | Engineer | Estimator | Production | Client |
|---|---:|---:|---:|---:|---:|---:|---:|
| document.view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| document.create | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| document.render | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| document.download | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ○ |
| document.archive | ✓ | ✓ | ○ | ○ | ○ | ○ | — |

---

# 62. AI Permission Matrix

| Permission | Admin | PM | Designer | Senior Designer | Engineer |
|---|---:|---:|---:|---:|---:|
| ai.floorplan.analyze | ✓ | ✓ | ✓ | ✓ | ✓ |
| ai.image_to_3d | ✓ | ✓ | ✓ | ✓ | ✓ |
| ai.proposal.view | ✓ | ✓ | ✓ | ✓ | ✓ |
| ai.proposal.accept | — | ○ | ✓ | ✓ | ✓ |
| ai.proposal.reject | — | ✓ | ✓ | ✓ | ✓ |
| ai.configuration | ✓ | — | — | — | — |

AI-generated output MUST NOT automatically receive production approval.

---

# 63. Reporting Permission Matrix

| Permission | Owner | Admin | PM | Sales | Designer | Engineer | Manufacturing | Production | Client |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| report.dashboard | ✓ | ✓ | ✓ | ✓ | ○ | ✓ | ✓ | ✓ | ○ |
| report.financial | ✓ | ✓ | ✓ | ✓ | — | — | ○ | — | — |
| report.production | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | ✓ | — |
| report.qc | ✓ | ✓ | ✓ | — | — | ✓ | ✓ | ✓ | — |
| report.export | ✓ | ✓ | ✓ | ✓ | ○ | ✓ | ✓ | ✓ | ○ |

---

# 64. Audit Permission Matrix

| Permission | Platform Admin | Tenant Owner | Tenant Admin | PM | Other |
|---|---:|---:|---:|---:|---:|
| audit.view | ✓ | ✓ | ✓ | ○ | — |
| audit.export | ✓ | ✓ | ✓ | ○ | — |
| audit.delete | — | — | — | — | — |

Audit logs MUST be append-only.

---

# 65. Permission Catalog

The initial permission catalog SHOULD include:

## Platform

```text
platform.view
platform.manage
platform.audit
platform.feature.manage
```

## Tenant

```text
tenant.view
tenant.update
tenant.suspend
tenant.branding.view
tenant.branding.manage
tenant.domain.view
tenant.domain.manage
tenant.feature.view
tenant.feature.manage
```

## User

```text
user.view
user.create
user.update
user.disable
user.enable
user.delete
user.role.assign
user.role.remove
```

## Role

```text
role.view
role.create
role.update
role.delete
role.permission.assign
role.permission.remove
```

## Permission

```text
permission.view
permission.manage
```

## CRM

```text
lead.view
lead.create
lead.update
lead.delete
lead.convert

client.view
client.create
client.update
client.delete

client_contact.view
client_contact.create
client_contact.update
client_contact.delete

opportunity.view
opportunity.create
opportunity.update
opportunity.delete
opportunity.convert
```

## Project

```text
project.view
project.list
project.create
project.update
project.delete
project.archive
project.restore
project.team.view
project.team.manage
project.approve
project.export
```

## Design

```text
design.view
design.create
design.update
design.delete
design.comment
design.submit_review
design.review
design.approve
design.reject
design.export

design_object.view
design_object.create
design_object.update
design_object.delete
design_object.bulk_update

design_layer.view
design_layer.create
design_layer.update
design_layer.delete

design_snapshot.create
design_snapshot.view
design_snapshot.restore
```

## Furniture

```text
furniture.view
furniture.create
furniture.update
furniture.delete
furniture.validate
furniture.regenerate
furniture.export

furniture_revision.view
furniture_revision.create
furniture_revision.update
furniture_revision.approve
```

## Templates

```text
template.view
template.create
template.update
template.delete
template.version.create
template.validate
template.preview
template.publish
template.archive
template.import
template.export
```

## Catalog

```text
material.view
material.create
material.update
material.delete
material.archive

board.view
board.manage

laminate.view
laminate.manage

edge_band.view
edge_band.manage

hardware.view
hardware.create
hardware.update
hardware.delete
hardware.archive

catalog.import
catalog.validate
catalog.commit
catalog.export
```

## Commercial

```text
bom.view
bom.generate
bom.regenerate
bom.approve
bom.lock
bom.export

boq.view
boq.generate
boq.update
boq.approve
boq.export

pricing.view
pricing.calculate
pricing.rule.view
pricing.rule.create
pricing.rule.update
pricing.rule.delete
pricing.publish
pricing.export

quotation.view
quotation.create
quotation.update
quotation.delete
quotation.submit_review
quotation.send
quotation.approve
quotation.reject
quotation.cancel
quotation.export

proposal.view
proposal.create
proposal.update
proposal.render
proposal.send
proposal.export
```

## Engineering

```text
engineering.view
engineering.validate
engineering.run_validation
engineering.resolve_issue
engineering.approve
engineering.reject
engineering.export
```

## Manufacturing

```text
manufacturing.view
manufacturing.create_revision
manufacturing.generate
manufacturing.validate
manufacturing.modify_draft
manufacturing.release
manufacturing.supersede
manufacturing.export

panel.view
panel.update
panel.export

cutlist.view
cutlist.generate
cutlist.export

nesting.view
nesting.create
nesting.run
nesting.cancel
nesting.export

cnc_machine.view
cnc_machine.create
cnc_machine.update
cnc_machine.delete

cnc.view
cnc.generate
cnc.cancel
cnc.export
```

## MES

```text
mes.dashboard.view
mes.production.view
mes.production.create
production.view
production.create
production.update
production.schedule
production.start
production.pause
production.resume
production.hold
production.complete
production.cancel
production.rework
production.event.view

workstation.view
workstation.create
workstation.update
workstation.delete
workstation.queue.view
```

## QR

```text
qr.view
qr.generate
qr.scan
qr.revoke
qr.transition
```

## QC

```text
qc.view
qc.create
qc.start
qc.record_result
qc.pass
qc.fail
qc.create_defect
qc.resolve_defect
qc.configure
```

## Packaging

```text
package.view
package.create
package.update
package.add_item
package.remove_item
package.close
package.print_label
```

## Dispatch

```text
dispatch.view
dispatch.create
dispatch.update
dispatch.dispatch
dispatch.deliver
dispatch.cancel
```

## Installation

```text
installation.view
installation.create
installation.update
installation.assign
installation.start
installation.complete
installation.issue
```

## Files/Documents

```text
file.upload
file.view
file.download
file.delete

document.view
document.create
document.update
document.render
document.download
document.archive
```

## AI

```text
ai.floorplan.analyze
ai.image_to_3d
ai.proposal.view
ai.proposal.accept
ai.proposal.reject
ai.configuration
```

## Jobs

```text
job.view
job.cancel
job.retry
```

## Notifications

```text
notification.view
notification.manage
```

## Audit

```text
audit.view
audit.export
```

---

# 66. Default Role-to-Permission Matrix

The following is the baseline assignment.

## PLATFORM_SUPER_ADMIN

```text
ALL_PLATFORM_PERMISSIONS
ALL_TENANT_ADMINISTRATION_PERMISSIONS
AUDIT
SUPPORT
```

Platform admin MUST still respect tenant boundaries for ordinary business operations unless explicit support elevation is used.

---

# 67. TENANT_OWNER

```text
ALL_TENANT_PERMISSIONS
```

Except:

```text
permission.manage
platform.manage
platform.feature.manage
```

---

# 68. TENANT_ADMIN

```text
tenant.view
tenant.update
tenant.branding.view
tenant.branding.manage
tenant.domain.view
tenant.domain.manage

user.*
role.*
permission.view

catalog.*
pricing.*
project.view
project.list
project.create
project.update
project.archive
project.export

audit.view
audit.export
report.dashboard
report.export
```

Sensitive actions such as manufacturing release SHOULD be assigned separately.

---

# 69. OPERATIONS_MANAGER

```text
crm.*
project.*
design.view
design.comment
furniture.view
furniture.validate

bom.*
boq.*
pricing.view
pricing.calculate
quotation.view
quotation.create
quotation.submit_review
quotation.send

engineering.*
manufacturing.view
manufacturing.validate
manufacturing.export

mes.dashboard.view
mes.production.view
production.view

qc.view
package.view
dispatch.view
installation.view

report.*
```

---

# 70. PROJECT_MANAGER

```text
lead.view
client.view
opportunity.view

project.*
design.view
design.comment
design.submit_review
design.approve
design.reject
design.export

furniture.view
bom.view
bom.generate
bom.export
boq.view
boq.generate
boq.export

pricing.view
pricing.calculate

quotation.view
quotation.create
quotation.submit_review
quotation.send
quotation.export

engineering.view
engineering.approve

manufacturing.view
manufacturing.export

mes.dashboard.view
mes.production.view
production.view
qc.view
package.view
dispatch.view
installation.*
document.*
report.dashboard
report.export
```

---

# 71. SENIOR_DESIGNER

```text
project.view
project.list
project.create
project.update
project.export

design.*
design_object.*
design_layer.*
design_snapshot.*

furniture.*
furniture_revision.*

template.view
template.create
template.update
template.version.create
template.validate
template.preview
template.export

material.view
board.view
laminate.view
edge_band.view
hardware.view

bom.*
boq.view
boq.generate
boq.export

pricing.view
pricing.calculate

quotation.view
proposal.view
proposal.create
proposal.update
proposal.render
proposal.export

engineering.view
document.*
ai.floorplan.analyze
ai.image_to_3d
ai.proposal.view
ai.proposal.accept
ai.proposal.reject
```

---

# 72. DESIGNER

```text
project.view
project.list

design.view
design.create
design.update
design.comment
design.submit_review
design.export

design_object.view
design_object.create
design_object.update
design_object.bulk_update

design_layer.view

design_snapshot.create
design_snapshot.view

furniture.view
furniture.create
furniture.update
furniture.validate
furniture.regenerate
furniture.export

furniture_revision.view
furniture_revision.create

template.view
template.preview
template.validate

material.view
board.view
laminate.view
edge_band.view
hardware.view

bom.view
bom.generate
bom.export

boq.view
boq.generate
boq.export

pricing.view
pricing.calculate

document.view
document.create
document.render
document.download

ai.floorplan.analyze
ai.image_to_3d
ai.proposal.view
ai.proposal.accept
ai.proposal.reject
```

---

# 73. DESIGN_REVIEWER

```text
project.view
design.view
design.comment
design.review
design.approve
design.reject
design.export

design_snapshot.view

furniture.view
furniture.validate

bom.view
boq.view
pricing.view

document.view
document.download
```

---

# 74. ENGINEER

```text
project.view
design.view
design_object.view
design.export

furniture.view
furniture.validate
furniture.regenerate

template.view
template.validate
template.preview

material.view
board.view
laminate.view
edge_band.view
hardware.view

bom.view
bom.generate
bom.regenerate
bom.approve
bom.export

boq.view
boq.export

engineering.*
manufacturing.view
manufacturing.create_revision
manufacturing.generate
manufacturing.validate
manufacturing.modify_draft
manufacturing.export

panel.view
panel.update
panel.export

cutlist.view
cutlist.generate
cutlist.export

nesting.view
nesting.create
nesting.run
nesting.export

cnc.view
cnc.generate
cnc.export

document.view
document.create
document.render
document.download
```

---

# 75. ESTIMATOR

```text
project.view
design.view
furniture.view

bom.view
bom.generate
bom.export

boq.*
pricing.*

quotation.*
proposal.view
proposal.create
proposal.update
proposal.render
proposal.send
proposal.export

document.view
document.create
document.render
document.download

report.financial
report.export
```

Default:

```text
quotation.approve = NO
pricing.publish = YES/conditional
```

---

# 76. SALES_MANAGER

```text
lead.*
client.*
opportunity.*

project.view
project.create
project.update

quotation.view
quotation.create
quotation.update
quotation.submit_review
quotation.send
quotation.approve
quotation.reject
quotation.cancel
quotation.export

boq.view
pricing.view
pricing.calculate

proposal.*
document.view
document.download

report.dashboard
report.financial
report.export
```

---

# 77. SALES_USER

```text
lead.view
lead.create
lead.update
lead.convert

client.view
client.create
client.update

opportunity.view
opportunity.create
opportunity.update
opportunity.convert

project.view
project.create

quotation.view
quotation.create
quotation.update
quotation.submit_review
quotation.send
quotation.export

proposal.view
proposal.create
proposal.render
proposal.send
proposal.export

document.view
document.download
```

Default:

```text
quotation.approve = NO
```

---

# 78. MANUFACTURING_MANAGER

```text
project.view
design.view
furniture.view

material.view
board.view
laminate.view
edge_band.view
hardware.view

bom.view
bom.generate
bom.regenerate
bom.approve
bom.export

boq.view
boq.export

engineering.view
engineering.validate
engineering.run_validation

manufacturing.*

panel.view
panel.update
panel.export

cutlist.*
nesting.*
cnc_machine.*
cnc.*

mes.dashboard.view
mes.production.view
production.view
production.create
production.schedule

workstation.view
workstation.queue.view

document.*
report.production
report.export
```

Includes:

```text
manufacturing.release
```

subject to workflow and separation-of-duties rules.

---

# 79. PRODUCTION_MANAGER

```text
project.view
manufacturing.view
manufacturing.export

panel.view
cutlist.view
cutlist.export
nesting.view
cnc.view

mes.dashboard.view
mes.production.view

production.*
workstation.*

qr.view
qr.scan
qr.transition

qc.view
qc.create
qc.create_defect

package.view
package.create
package.update
package.add_item
package.remove_item
package.close
package.print_label

dispatch.view
installation.view

report.production
report.qc
report.export
```

---

# 80. PRODUCTION_SUPERVISOR

```text
manufacturing.view
panel.view
cutlist.view
cnc.view

mes.dashboard.view
mes.production.view

production.view
production.start
production.pause
production.resume
production.hold
production.complete
production.rework
production.event.view

workstation.view
workstation.queue.view

qr.view
qr.scan
qr.transition

qc.view
qc.create
qc.create_defect

package.view
package.create
package.update
package.add_item
package.remove_item
package.print_label
```

---

# 81. MACHINE_OPERATOR

```text
manufacturing.view
panel.view
cutlist.view
cnc.view

production.view
production.start
production.pause
production.resume
production.hold
production.complete
production.event.view

workstation.view
workstation.queue.view

qr.view
qr.scan
qr.transition

qc.view
qc.create_defect
```

All production permissions are assignment-scoped.

---

# 82. QC_MANAGER

```text
project.view
manufacturing.view
panel.view
production.view

qc.*

qr.view
qr.scan

document.view
document.create
document.render
document.download

report.qc
report.production
report.export
```

---

# 83. QC_INSPECTOR

```text
project.view
manufacturing.view
panel.view
production.view

qc.view
qc.create
qc.start
qc.record_result
qc.pass
qc.fail
qc.create_defect
qc.resolve_defect

qr.view
qr.scan

document.view
document.download
```

---

# 84. WAREHOUSE_OPERATOR

```text
project.view
manufacturing.view
panel.view
cutlist.view

qr.view
qr.scan

package.view
package.add_item
package.remove_item
```

---

# 85. PACKING_OPERATOR

```text
project.view
manufacturing.view
panel.view
production.view
qc.view

qr.view
qr.scan

package.*
```

Package delete/archive permissions should be restricted after closure.

---

# 86. DISPATCH_MANAGER

```text
project.view
package.view
package.print_label

dispatch.*
installation.view

qr.view
qr.scan

document.view
document.download

report.production
report.export
```

---

# 87. INSTALLATION_MANAGER

```text
project.view
package.view
dispatch.view

installation.*
qr.view
qr.scan

document.view
document.download
```

---

# 88. INSTALLATION_USER

```text
project.view
package.view
dispatch.view

installation.view
installation.start
installation.complete
installation.issue

qr.view
qr.scan

document.view
document.download
```

Scope is assigned installations only.

---

# 89. CLIENT_ADMIN

```text
project.view
project.list

design.view
design.comment
design.approve
design.reject
design.export

furniture.view

quotation.view
quotation.approve
quotation.reject
quotation.export

proposal.view
proposal.export

document.view
document.download

installation.view
```

Client users MUST NOT receive:

```text
pricing.rule.*
manufacturing.*
engineering.internal.*
audit.*
production.internal.*
```

unless explicitly configured.

---

# 90. CLIENT_USER

```text
project.view
design.view
design.comment
design.export

furniture.view

quotation.view
quotation.export

proposal.view
proposal.export

document.view
document.download

installation.view
```

---

# 91. VIEWER

```text
project.view
design.view
furniture.view
material.view
bom.view
boq.view
quotation.view
proposal.view
document.view
document.download
```

Only assigned resources.

---

# 92. Resource Scope Model

Permissions MUST support scopes:

```text
GLOBAL
TENANT
PROJECT
OWNED
ASSIGNED
TEAM
CLIENT
FACTORY
WORKSTATION
SELF
```

Example:

```text
production.start
scope = ASSIGNED
```

means a machine operator can start only jobs assigned to them or their workstation.

---

# 93. Project Membership

A user MAY have project-level access even if their global role does not grant unrestricted access.

Table:

```text
project_users
```

Fields:

```text
project_id
user_id
project_role
scope
created_at
```

Project roles:

```text
OWNER
MANAGER
DESIGNER
REVIEWER
ENGINEER
ESTIMATOR
VIEWER
CLIENT
```

---

# 94. Effective Permission Calculation

Effective permissions:

```text
User
 ↓
Global Role Permissions
 +
Project Role Permissions
 +
Explicit Grants
 -
Explicit Denials
 ↓
Effective Permissions
```

Recommended default:

```text
DENY BY DEFAULT
```

Explicit deny SHOULD override allow for custom roles where the platform supports deny policies.

---

# 95. Permission Evaluation

Backend helper:

```php
$authorization->can(
    user: $user,
    permission: 'manufacturing.release',
    resource: $manufacturingRevision
);
```

Should evaluate:

```text
authenticated
tenant
permission
resource
project membership
state
ownership
business rules
```

---

# 96. Policy Architecture

Recommended:

```text
src/
  Authorization/
    PermissionRegistry.php
    PermissionChecker.php
    RoleService.php
    Policy/
      ProjectPolicy.php
      DesignPolicy.php
      FurniturePolicy.php
      QuotationPolicy.php
      ManufacturingPolicy.php
      ProductionPolicy.php
      QcPolicy.php
      PackagePolicy.php
      DispatchPolicy.php
```

---

# 97. Middleware Architecture

Recommended:

```text
AuthenticateMiddleware
TenantContextMiddleware
RateLimitMiddleware
PermissionMiddleware
```

Route example:

```text
POST /manufacturing-revisions/{id}/release

Authenticate
 ↓
TenantContext
 ↓
Permission(manufacturing.release)
 ↓
ManufacturingPolicy
 ↓
ManufacturingService
```

---

# 98. Frontend Authorization

Frontend MAY hide controls based on permissions.

Example:

```javascript
if (auth.can('manufacturing.release')) {
    showReleaseButton();
}
```

BUT:

> Frontend permission checks are UX controls, not security controls.

Backend MUST always enforce permissions.

---

# 99. Permission API

## Get current user permissions

```http
GET /api/v1/auth/me/permissions
```

Response:

```json
{
  "data": {
    "roles": [
      "SENIOR_DESIGNER"
    ],
    "permissions": [
      "project.view",
      "design.view",
      "design.create",
      "furniture.create"
    ]
  }
}
```

## Check permission

Optional:

```http
POST /api/v1/auth/check-permission
```

Request:

```json
{
  "permission": "manufacturing.release",
  "resource_type": "manufacturing_revision",
  "resource_id": "01J..."
}
```

This endpoint MUST NOT be used as the only authorization mechanism.

---

# 100. Role API

```http
GET    /roles
POST   /roles
GET    /roles/{id}
PATCH  /roles/{id}
DELETE /roles/{id}

GET    /roles/{id}/permissions
PUT    /roles/{id}/permissions
```

---

# 101. Role Assignment API

```http
POST   /users/{id}/roles
DELETE /users/{id}/roles/{roleId}

POST   /projects/{projectId}/users
PATCH  /projects/{projectId}/users/{userId}
DELETE /projects/{projectId}/users/{userId}
```

Role assignment MUST validate that the assigning user has:

```text
user.role.assign
```

and is allowed to assign that specific role.

---

# 102. Privilege Escalation Prevention

A user MUST NOT be able to assign:

```text
a role with more privileges than they possess
```

unless they have:

```text
role.administer
```

or equivalent tenant-owner/system-admin authority.

Example:

```text
Designer
```

cannot assign:

```text
Tenant Admin
```

to another user.

---

# 103. Sensitive Permissions

The following MUST be treated as high-risk:

```text
user.role.assign
role.permission.assign
pricing.publish
quotation.approve
manufacturing.release
manufacturing.supersede
production.override
qc.pass
dispatch.dispatch
tenant.domain.manage
tenant.suspend
audit.export
```

These actions MUST be audited.

---

# 104. Optional Approval Controls

Tenant settings MAY enable:

```text
require_quotation_approval
require_design_approval
require_engineering_approval
require_manufacturing_approval
require_qc_manager_approval
require_dispatch_approval
```

When enabled, the corresponding workflow MUST enforce approval.

---

# 105. Separation of Duties

Recommended rules:

### Design

```text
Designer creates
Reviewer approves
```

### Quotation

```text
Estimator prepares
Sales Manager approves
Client accepts
```

### Manufacturing

```text
Engineer validates
Manufacturing Manager releases
```

### Production/QC

```text
Production executes
QC validates
```

### Dispatch

```text
Packing closes
Dispatch Manager dispatches
```

The same-user restriction MAY be configurable by tenant but SHOULD default to enabled for high-risk operations.

---

# 106. Impersonation / Support Access

If implemented:

```text
POST /support/impersonate
```

MUST require:

```text
platform.support.impersonate
```

Every action performed during impersonation MUST record:

```text
actual_user_id
impersonated_user_id
reason
started_at
ended_at
request_id
```

No silent impersonation.

---

# 107. API Authorization Error Rules

Unauthenticated:

```http
401
```

Authenticated but unauthorized:

```http
403
```

Resource outside tenant:

Prefer:

```http
404
```

to avoid revealing existence.

Invalid workflow:

```http
409
```

Validation/business rule failure:

```http
422
```

---

# 108. Tenant Isolation Rule

Every repository query MUST include tenant scope.

Example:

```sql
SELECT *
FROM projects
WHERE id = :id
AND tenant_id = :tenant_id;
```

Never:

```sql
SELECT *
FROM projects
WHERE id = :id;
```

for tenant-owned resources.

---

# 109. Resource Access Example

Designer requests:

```text
GET /projects/P1
```

System checks:

```text
User authenticated?
YES

Tenant matches?
YES

project.view?
YES

Project membership?
YES

→ ALLOW
```

Another tenant:

```text
Tenant mismatch
→ 404
```

---

# 110. Manufacturing Release Example

User:

```text
MANUFACTURING_MANAGER
```

Permission:

```text
manufacturing.release = YES
```

But:

```text
engineering = FAILED
```

Result:

```text
DENY
MANUFACTURING_BLOCKED
```

This is a business authorization failure, not merely a permission failure.

---

# 111. Production Assignment Example

Machine Operator has:

```text
production.start
```

Job:

```text
assigned_workstation = CUT-02
```

Operator:

```text
workstation = EDGE-01
```

Result:

```text
DENY
RESOURCE_SCOPE_DENIED
```

---

# 112. Client Data Isolation

Client portal APIs MUST never expose internal:

```text
supplier cost
internal margin
internal markup
employee information
manufacturing rules
machine configuration
internal QC notes
internal audit logs
```

unless explicitly configured.

Client quotation responses SHOULD expose only:

```text
selling price
discount
tax
grand total
approved scope
```

---

# 113. Cost Data Protection

Permissions:

```text
pricing.view
pricing.rule.view
pricing.calculate
```

MUST be separated.

A user may be allowed to see:

```text
selling price
```

without being allowed to see:

```text
raw material cost
supplier rate
margin
markup rule
```

Introduce data-level masking where required.

---

# 114. Field-Level Authorization

For sensitive resources, permissions SHOULD support fields.

Example quotation:

```text
Client:
  visible:
    item
    quantity
    selling_price
    tax
    total

Estimator:
  visible:
    item
    quantity
    cost
    selling_price
    margin
    markup
```

Backend MUST apply masking, not just frontend hiding.

---

# 115. Role Customization

Tenant administrators MAY create custom roles.

Example:

```text
Interior Consultant
```

with:

```text
project.view
design.view
design.comment
furniture.view
proposal.view
```

Custom role creation MUST use existing permission keys.

Users MUST NOT create arbitrary executable permissions.

---

# 116. Role Versioning

When a role's permissions change, audit:

```text
role_id
changed_by
old_permissions
new_permissions
timestamp
```

Optionally maintain:

```text
role_versions
```

for enterprise environments.

---

# 117. Permission Caching

Permission sets MAY be cached.

Cache key:

```text
tenant_id:user_id:role_version
```

Invalidate cache when:

```text
role changes
permission assignment changes
user role changes
user disabled
tenant suspended
```

Never allow stale privileges after revocation for longer than the configured security window.

---

# 118. Session Revocation

When:

```text
user disabled
role removed
tenant suspended
```

existing sessions SHOULD be invalidated or privilege cache immediately invalidated.

High-risk environments SHOULD revoke active tokens.

---

# 119. Audit Requirements

Audit these events:

```text
login
logout
failed_login
user_created
user_disabled
role_created
role_updated
role_deleted
role_assigned
role_removed
permission_changed
tenant_setting_changed
pricing_published
quotation_approved
manufacturing_released
manufacturing_superseded
production_override
qc_pass
qc_fail
dispatch
impersonation
```

---

# 120. RBAC Database Requirements

Minimum tables:

```text
users
roles
permissions
role_permissions
user_roles
project_users
audit_logs
```

Recommended additional:

```text
role_versions
permission_groups
user_permission_overrides
authorization_policies
```

---

# 121. Permission Groups

Group permissions for administration:

```text
DESIGN
FURNITURE
CATALOG
COMMERCIAL
ENGINEERING
MANUFACTURING
MES
QC
LOGISTICS
ADMINISTRATION
REPORTING
```

Groups are for UI organization and administration only; authorization uses individual permission keys.

---

# 122. Explicit Overrides

Optional table:

```text
user_permission_overrides
```

Fields:

```text
id
tenant_id
user_id
permission_id
effect
scope
resource_type
resource_id
expires_at
created_by
created_at
```

Effects:

```text
ALLOW
DENY
```

Use sparingly.

Default recommendation:

```text
Roles > explicit user overrides
```

because excessive overrides make authorization difficult to audit.

---

# 123. Temporary Permissions

Temporary access MAY have:

```text
expires_at
```

Example:

```text
Engineer temporarily receives manufacturing.release
until 2026-08-15
```

Expired permissions MUST automatically become ineffective.

---

# 124. API Route Permission Registry

Maintain a centralized registry:

```php
[
    'GET /projects' => 'project.list',
    'POST /projects' => 'project.create',
    'PATCH /projects/{id}' => 'project.update',
    'POST /manufacturing-revisions/{id}/release' => 'manufacturing.release',
    'POST /production-jobs/{id}/start' => 'production.start',
]
```

This makes the permission surface auditable.

---

# 125. UI Permission Registry

Frontend SHOULD consume permission metadata:

```json
{
  "manufacturing.release": {
    "label": "Release Manufacturing",
    "danger_level": "HIGH"
  }
}
```

Do not duplicate the complete authorization logic in JavaScript.

---

# 126. Permission Matrix Export

Admin UI SHOULD support:

```text
View role
View permissions
Compare roles
Export permission matrix CSV
Export permission matrix JSON
```

Example:

```http
GET /roles/matrix
```

---

# 127. Role Comparison

Optional:

```http
GET /roles/compare?role_ids=A,B,C
```

Return:

```text
permission
role A
role B
role C
```

This helps tenant administrators understand privilege differences.

---

# 128. Access Review

Enterprise tenants SHOULD have:

```http
GET /security/access-review
```

Show:

```text
users
roles
high-risk permissions
inactive users
expired grants
temporary access
users with manufacturing release
users with pricing publish
users with tenant admin
```

---

# 129. Security Alerts

Generate alerts for:

```text
new tenant admin
manufacturing.release assigned
pricing.publish assigned
permission.manage assigned
large role change
impersonation
multiple failed logins
```

---

# 130. RBAC Testing Requirements

## Authentication

```text
[ ] unauthenticated API blocked
[ ] expired token blocked
[ ] disabled user blocked
```

## Tenant Isolation

```text
[ ] Tenant A cannot access Tenant B project
[ ] Tenant A cannot access Tenant B materials
[ ] Tenant A cannot access Tenant B manufacturing
[ ] Tenant A cannot access Tenant B audit
```

## Role

```text
[ ] Designer cannot release manufacturing
[ ] Machine Operator cannot change pricing
[ ] Client cannot view internal costs
[ ] Viewer cannot edit
```

## Workflow

```text
[ ] Release blocked without engineering approval
[ ] QC cannot approve unreleased manufacturing
[ ] Dispatch blocked if package incomplete
```

---

# 131. Automated RBAC Test Matrix

The test suite MUST include at minimum:

```text
Platform Admin × all platform permissions
Tenant Owner × all tenant permissions
Tenant Admin × administration permissions
Designer × design permissions
Engineer × engineering permissions
Estimator × commercial permissions
Manufacturing Manager × manufacturing permissions
Production Manager × MES permissions
Machine Operator × production permissions
QC Inspector × QC permissions
Packing Operator × package permissions
Dispatch Manager × dispatch permissions
Client User × client permissions
Viewer × read-only permissions
```

For every role:

```text
positive tests
negative tests
cross-tenant tests
resource-scope tests
workflow-state tests
```

---

# 132. Permission Matrix Acceptance Criteria

The implementation is accepted only when:

1. Every protected API route has a defined permission.
2. Every permission belongs to a documented module.
3. Every system role has a documented permission set.
4. Tenant isolation is enforced server-side.
5. Project-level access is enforced.
6. High-risk actions are audited.
7. Released manufacturing data is protected.
8. Client users cannot access internal costs.
9. Permission changes invalidate cached privileges.
10. Disabled users cannot access APIs.
11. Role assignment cannot escalate privileges.
12. Custom roles cannot invent arbitrary permission keys.
13. All authorization failures return safe errors.
14. Authorization is tested independently of frontend visibility.

---

# 133. Definition of Done

RBAC is complete only when:

```text
[ ] Permission registry created
[ ] Default roles created
[ ] Role-permission mappings seeded
[ ] Tenant roles implemented
[ ] Platform roles implemented
[ ] Project membership implemented
[ ] Permission middleware implemented
[ ] Policy layer implemented
[ ] Resource-level authorization implemented
[ ] Workflow authorization implemented
[ ] Field masking implemented where required
[ ] Audit logging implemented
[ ] Permission caching implemented
[ ] Cache invalidation implemented
[ ] Role assignment API implemented
[ ] Permission API implemented
[ ] Admin permission matrix UI/API implemented
[ ] OpenAPI documented
[ ] Unit tests implemented
[ ] Integration tests implemented
[ ] Cross-tenant tests implemented
[ ] Privilege escalation tests implemented
[ ] Security review completed
```

---

# 134. Cursor Pre-Implementation Analysis

Before changing the existing RBAC implementation, Cursor MUST inspect:

```text
existing users table
existing roles
existing permissions
existing role mappings
existing middleware
existing controllers
existing API routes
existing frontend guards
existing project access rules
existing authentication
existing audit logs
existing tests
```

Cursor MUST first produce:

```text
CURRENT RBAC INVENTORY
CURRENT ROLES
CURRENT PERMISSIONS
CURRENT ROUTE-PERMISSION MAP
CURRENT GAPS
DUPLICATE PERMISSIONS
OVER-PERMISSIONED ROLES
UNDER-PERMISSIONED ROLES
TENANT-ISOLATION RISKS
PRIVILEGE-ESCALATION RISKS
MIGRATION PLAN
```

Do NOT immediately replace the existing authorization system.

---

# 135. Cursor Implementation Rules

For every endpoint:

```text
Authentication
      ↓
Tenant Context
      ↓
Permission
      ↓
Resource Policy
      ↓
Workflow State
      ↓
Business Rule
      ↓
Service
```

For every sensitive action:

```text
Authorization
+
Transaction
+
Audit
```

For every tenant-owned resource:

```text
tenant_id
+
resource access
```

For every project resource:

```text
tenant access
+
project membership
+
permission
```

---

# 136. Cursor Prohibited Patterns

Cursor MUST NOT:

- rely only on frontend permissions
- trust client-supplied tenant_id
- expose cross-tenant records
- allow role escalation
- give all admins all permissions without documenting it
- hard-code authorization separately in every controller
- silently bypass policies
- allow direct modification of released manufacturing data
- expose internal costs to clients
- allow machine operators to change design/pricing
- allow users to assign roles above their authority
- store permissions only in JavaScript
- skip audit for high-risk operations
- use hidden URL parameters as authorization
- rely on UI button hiding for security

---

# 137. Recommended Authorization Service

Conceptual interface:

```php
interface AuthorizationService
{
    public function can(
        User $user,
        string $permission,
        mixed $resource = null
    ): bool;

    public function authorize(
        User $user,
        string $permission,
        mixed $resource = null
    ): void;

    public function permissions(User $user): array;
}
```

`authorize()` MUST throw a safe authorization exception when access is denied.

---

# 138. Recommended Policy Interface

```php
interface Policy
{
    public function view(User $user, $resource): bool;

    public function create(User $user, $context = null): bool;

    public function update(User $user, $resource): bool;

    public function delete(User $user, $resource): bool;
}
```

Domain-specific policies SHOULD expose additional actions:

```text
approve
release
publish
complete
cancel
```

---

# 139. Final RBAC Model

The intended authorization architecture is:

```text
                    PLATFORM
                       │
                PLATFORM ADMIN
                       │
                    TENANT
                       │
              ┌────────┴────────┐
              │                 │
          TENANT OWNER      TENANT ADMIN
              │
      ┌───────┼────────┬────────────┐
      │       │        │            │
   DESIGN   SALES   ENGINEERING   MANUFACTURING
      │       │        │            │
 DESIGNER  ESTIMATOR  ENGINEER   MFG MANAGER
      │       │                       │
 REVIEWER   SALES MGR             PRODUCTION
                                      │
                             ┌────────┼─────────┐
                             │        │         │
                         SUPERVISOR OPERATOR   QC
                             │                  │
                          PACKING             QC INSPECTOR
                             │
                         DISPATCH
                             │
                       INSTALLATION
```

---

# 140. Final Authorization Principle

The platform MUST NOT implement authorization as:

```text
ROLE = ADMIN
```

alone.

It MUST evaluate:

```text
WHO
 ↓
WHICH TENANT
 ↓
WHICH ROLE
 ↓
WHICH PERMISSION
 ↓
WHICH PROJECT
 ↓
WHICH RESOURCE
 ↓
WHICH REVISION
 ↓
WHICH WORKFLOW STATE
 ↓
WHICH BUSINESS RULE
```

This is essential because the platform crosses from creative design into commercially sensitive information and finally into real factory execution.

A designer may modify a cabinet.

An estimator may calculate its price.

An engineer may validate its manufacturability.

A manufacturing manager may release it.

A machine operator may manufacture it.

A QC inspector may approve it.

A dispatch manager may ship it.

A client may approve the commercial proposal.

**None of these roles should automatically inherit the privileges of the others.**

The RBAC system must preserve that separation while still allowing configurable enterprise workflows.

---

# 141. Final Cursor Instruction

Treat this document as the **RBAC and Permission Matrix implementation contract**.

Do not implement permissions as UI-only flags.

The final system MUST provide:

```text
CENTRAL PERMISSION REGISTRY
        ↓
ROLE DEFINITIONS
        ↓
USER ROLE ASSIGNMENT
        ↓
PROJECT MEMBERSHIP
        ↓
RESOURCE POLICIES
        ↓
WORKFLOW POLICIES
        ↓
FIELD-LEVEL DATA PROTECTION
        ↓
AUDIT
        ↓
AUTOMATED SECURITY TESTS
```

The security objective is:

> **Every user should have exactly the access required to perform their job—no more, no less—and every sensitive action must be traceable, tenant-isolated and governed by the state of the business object.**
