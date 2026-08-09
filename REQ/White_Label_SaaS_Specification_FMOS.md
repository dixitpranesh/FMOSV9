# White-Label / SaaS Specification
## FMOS — Multi-Tenant Interior Design, Furniture Manufacturing & MES Platform

**Document ID:** SAAS-WL-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Audience:** Cursor, PHP Developers, ES6 Developers, DevOps, Product, Security, SaaS Operations  
**Backend:** PHP 8.x + MySQL 8.x  
**Frontend:** JavaScript ES6+  
**Architecture:** Multi-tenant SaaS with optional dedicated/enterprise deployment  
**Primary Principle:** One product platform, isolated tenant data, configurable tenant experience  
**Date:** 2026-08-10

---

# 1. Purpose

This specification defines the complete White-Label and SaaS architecture for FMOS.

FMOS must support multiple independent businesses using the same application platform while allowing each business to present the platform as its own branded solution.

The platform must support:

```text
Multi-Tenant SaaS
+
White Label
+
Custom Domain
+
Tenant Branding
+
Tenant Configuration
+
Tenant-Specific Catalog
+
Tenant-Specific Pricing
+
Tenant-Specific Manufacturing Rules
+
Tenant-Specific Users/Roles
+
Tenant-Specific Documents
+
Tenant-Specific Emails
+
Tenant-Specific Client Portals
+
Optional Dedicated Deployment
```

---

# 2. Product Vision

FMOS should be capable of being sold as:

```text
FMOS SaaS
```

or:

```text
Customer-Branded Interior Design Platform
```

or:

```text
Customer-Branded Furniture Manufacturing Platform
```

or:

```text
Customer-Branded Design-to-Manufacturing ERP/MES
```

Example:

```text
app.fmos.com
```

Tenant:

```text
ABC Interiors
```

may use:

```text
design.abcinteriors.com
```

with:

```text
ABC Interiors logo
ABC Interiors colors
ABC Interiors email domain
ABC Interiors proposal templates
ABC Interiors catalogs
ABC Interiors pricing
ABC Interiors manufacturing workflows
```

without modifying the FMOS core application.

---

# 3. SaaS Architecture Principle

The platform must separate:

```text
PLATFORM
```

from:

```text
TENANT
```

and:

```text
TENANT DATA
```

Core architecture:

```text
                    FMOS PLATFORM
                         │
          ┌──────────────┼──────────────┐
          ↓              ↓              ↓
      Tenant A        Tenant B        Tenant C
          │              │              │
       Users          Users          Users
       Projects       Projects       Projects
       Catalog        Catalog        Catalog
       Pricing        Pricing        Pricing
       Factory        Factory        Factory
```

---

# 4. Tenant Definition

A tenant represents an independent business/customer organization using FMOS.

Example:

```text
tenant_id = TEN-001
tenant_name = ABC Interiors Pvt Ltd
```

---

# 5. Tenant Isolation

Tenant isolation is mandatory.

Every tenant-owned record must contain:

```text
tenant_id
```

unless the record is explicitly platform-global.

---

# 6. Tenant Security Rule

Never trust:

```text
tenant_id
```

provided by browser requests.

Tenant context must be derived server-side from:

```text
authenticated user
session/token
domain
platform context
```

---

# 7. Tenant Context

Every authenticated request must resolve:

```text
tenant
user
role
permissions
factory
project
```

as applicable.

---

# 8. Tenant Context Service

Implement:

```text
TenantContextService
```

responsible for:

```text
resolve tenant
validate access
expose current tenant
enforce isolation
```

---

# 9. Tenant Resolver

Tenant may be resolved from:

```text
custom domain
subdomain
authenticated tenant mapping
```

Example:

```text
design.abcinteriors.com
```

maps to:

```text
TEN-001
```

---

# 10. Platform Domain

Primary FMOS domain:

```text
app.fmos.com
```

or configured production domain.

---

# 11. Tenant Subdomain

Support:

```text
abcinteriors.fmos.com
```

---

# 12. Custom Domain

Support:

```text
design.abcinteriors.com
```

or:

```text
app.abcinteriors.com
```

---

# 13. Domain Mapping

Minimum table:

```text
tenant_domains
```

Fields:

```text
id
tenant_id
domain
domain_type
verification_status
ssl_status
is_primary
created_at
verified_at
```

---

# 14. Domain Types

Support:

```text
PLATFORM_SUBDOMAIN
CUSTOM_DOMAIN
CUSTOM_SUBDOMAIN
```

---

# 15. Domain Status

```text
PENDING
VERIFYING
VERIFIED
ACTIVE
SUSPENDED
REVOKED
```

---

# 16. Custom Domain Verification

Tenant must prove domain ownership.

Recommended methods:

```text
DNS TXT
DNS CNAME
```

---

# 17. Domain Verification

Example:

```text
TXT:
fmos-verification=<token>
```

The platform validates ownership before activating the domain.

---

# 18. Domain Security

Do not activate a custom domain merely because a user typed it into the UI.

---

# 19. Domain Collision

A domain cannot belong to two tenants.

---

# 20. Domain Normalization

Normalize:

```text
lowercase
remove protocol
remove trailing slash
punycode where applicable
```

before comparison.

---

# 21. SSL/TLS

All tenant domains must use HTTPS in production.

---

# 22. SSL Status

Track:

```text
NOT_CONFIGURED
PENDING
ACTIVE
EXPIRING
EXPIRED
ERROR
```

---

# 23. Tenant Branding

Tenant branding must be configurable without code changes.

Configurable:

```text
logo
favicon
primary color
secondary color
accent color
background color
text color
font
login image
login message
email footer
document footer
```

---

# 24. Brand Configuration Table

```text
tenant_branding
```

Fields:

```text
id
tenant_id
logo_url
favicon_url
primary_color
secondary_color
accent_color
font_family
login_image_url
email_footer
document_footer
created_at
updated_at
```

---

# 25. Logo Requirements

Support:

```text
SVG
PNG
JPG
WEBP
```

with configurable size limits.

---

# 26. Favicon

Support:

```text
ICO
PNG
SVG
```

where supported by browsers.

---

# 27. Brand Color Validation

Reject invalid values.

Example:

```text
#RRGGBB
```

or approved design-token formats.

---

# 28. Design Tokens

Frontend should consume tenant branding through CSS variables:

```css
--brand-primary
--brand-secondary
--brand-accent
--brand-background
--brand-text
```

---

# 29. No Hard-Coded Branding

Do not hard-code:

```text
FMOS logo
FMOS colors
FMOS company name
```

into tenant-facing screens.

Platform admin may retain FMOS branding in platform-only areas.

---

# 30. Tenant Application Name

Tenant may configure:

```text
application_name
```

Example:

```text
ABC Design Studio
```

---

# 31. Tenant Product Name

Optional:

```text
product_name
```

Example:

```text
ABC Studio Pro
```

---

# 32. White-Label Mode

Tenant configuration:

```text
white_label_enabled
```

When enabled:

```text
FMOS branding
```

is hidden from normal tenant users except legally/technically required platform references.

---

# 33. Powered-By Configuration

Support:

```text
SHOW_POWERED_BY
HIDE_POWERED_BY
```

subject to commercial plan.

---

# 34. SaaS Plan Model

Support:

```text
plans
```

Example:

```text
STARTER
PROFESSIONAL
BUSINESS
ENTERPRISE
```

Plans must be configurable.

---

# 35. Plan Entitlements

Plans may control:

```text
users
projects
storage
factories
AI usage
catalog size
domains
client portals
manufacturing
MES
CNC integrations
API access
```

---

# 36. Entitlement Architecture

Implement:

```text
EntitlementService
```

Do not scatter plan checks across controllers.

---

# 37. Feature Flags vs Entitlements

Separate:

```text
Feature Flag
```

from:

```text
Commercial Entitlement
```

Example:

```text
feature enabled internally
```

does not mean:

```text
tenant is entitled to use it
```

---

# 38. Tenant Feature Flags

Support:

```text
tenant_features
```

Examples:

```text
CAD_2D
BIM_3D
FURNITURE_ENGINE
AI
BOM
PRICING
MANUFACTURING
NESTING
CNC
MES
QR_TRACKING
CLIENT_PORTAL
API
```

---

# 39. Feature Flag States

```text
ENABLED
DISABLED
BETA
READ_ONLY
```

---

# 40. Tenant Subscription

Table:

```text
tenant_subscriptions
```

Fields:

```text
id
tenant_id
plan_id
status
start_date
end_date
trial_end_date
billing_cycle
external_subscription_id
created_at
updated_at
```

---

# 41. Subscription Status

```text
TRIAL
ACTIVE
PAST_DUE
SUSPENDED
CANCELLED
EXPIRED
```

---

# 42. Subscription Suspension

When suspended:

```text
do not delete data
```

Instead restrict configured capabilities.

---

# 43. Grace Period

Support configurable grace period.

Example:

```text
7 days
```

---

# 44. Read-Only Suspension

Optional state:

```text
READ_ONLY
```

Users may view/export permitted data but cannot create new transactions.

---

# 45. Tenant Lifecycle

```text
PROVISIONING
TRIAL
ACTIVE
SUSPENDED
READ_ONLY
DEACTIVATED
DELETED
```

---

# 46. Tenant Creation

Tenant creation should provision:

```text
tenant
admin user
branding
default roles
default permissions
default settings
default workflows
default document templates
default notification templates
```

---

# 47. Tenant Provisioning

Implement:

```text
TenantProvisioningService
```

---

# 48. Provisioning Idempotency

Tenant provisioning must be safe to retry.

Do not create duplicate:

```text
admin role
default settings
templates
```

---

# 49. Tenant Onboarding

Recommended flow:

```text
Create Account
 ↓
Business Details
 ↓
Choose Plan
 ↓
Create Admin
 ↓
Branding
 ↓
Domain
 ↓
Factory/Workspace
 ↓
Catalog
 ↓
Users
 ↓
Complete
```

---

# 50. Tenant Setup Wizard

Tenant Admin should have a setup wizard showing:

```text
organization
branding
domain
users
catalog
pricing
factory
integrations
AI
```

---

# 51. Onboarding Completion

Track:

```text
onboarding_step
onboarding_completed
onboarding_completed_at
```

---

# 52. Tenant Organization

Tenant may contain:

```text
departments
branches
factories
offices
design studios
```

---

# 53. Organization Hierarchy

```text
Tenant
 ↓
Business Unit
 ↓
Branch / Factory
 ↓
Department
 ↓
User
```

---

# 54. Factory Isolation

Factory-specific records should include:

```text
factory_id
```

where relevant.

---

# 55. Tenant Users

User belongs to:

```text
tenant_id
```

and may have access to:

```text
one or more factories
one or more projects
```

---

# 56. Cross-Tenant Users

Do not assume one user belongs to only one tenant.

Support optional:

```text
user_tenant_memberships
```

for consultants/platform administrators.

---

# 57. Membership Table

```text
tenant_user_memberships
```

Fields:

```text
id
tenant_id
user_id
status
default_role_id
default_factory_id
joined_at
```

---

# 58. Tenant Admin

Tenant Admin can manage:

```text
users
roles
branding
domains
catalog
pricing
factories
settings
integrations
subscription
```

subject to plan.

---

# 59. Platform Super Admin

Platform Admin can manage:

```text
tenants
plans
subscriptions
domains
platform settings
global catalogs
support
system health
```

Platform Admin must not casually browse tenant data without explicit audited access.

---

# 60. Support Access

Support impersonation must be:

```text
explicit
time-limited
audited
permission-controlled
```

---

# 61. Impersonation

When support enters tenant context:

```text
support_user
target_tenant
reason
start_time
expiry
```

must be logged.

---

# 62. Impersonation Banner

UI must clearly show:

```text
You are viewing Tenant X as Support.
```

---

# 63. Exit Impersonation

Provide explicit:

```text
Exit Tenant Session
```

action.

---

# 64. Tenant Settings

Create:

```text
tenant_settings
```

with categories:

```text
GENERAL
LOCALIZATION
NUMBER_FORMAT
CURRENCY
TAX
DOCUMENTS
EMAIL
NOTIFICATIONS
DESIGN
MANUFACTURING
MES
AI
SECURITY
API
```

---

# 65. Configuration Hierarchy

Recommended:

```text
Platform Default
 ↓
Plan Default
 ↓
Tenant Configuration
 ↓
Factory Configuration
 ↓
Project Configuration
```

More specific configuration overrides broader configuration.

---

# 66. Configuration Service

Implement:

```text
ConfigurationService
```

to resolve effective values.

---

# 67. Configuration Validation

Every configurable field must define:

```text
type
default
allowed values
validation
scope
```

---

# 68. Localization

Tenant may configure:

```text
language
timezone
date format
number format
currency
measurement system
```

---

# 69. Supported Measurement Systems

At minimum:

```text
METRIC
IMPERIAL
```

---

# 70. Currency

Tenant may configure:

```text
INR
USD
EUR
GBP
AED
```

and future currencies.

---

# 71. Currency Rule

Financial calculations must use:

```text
decimal
```

not floating-point approximations.

---

# 72. Tax Configuration

Support tenant-level:

```text
tax names
rates
tax inclusion
tax rules
```

subject to jurisdictional requirements.

---

# 73. Timezone

Tenant default timezone should be configurable.

Factory may override tenant timezone.

---

# 74. Email Branding

All tenant-generated emails must support:

```text
tenant logo
tenant name
tenant colors
tenant footer
tenant reply-to
```

---

# 75. Email Domain

Support custom sender domain:

```text
notifications@abcinteriors.com
```

where technically configured.

---

# 76. Email Provider

Use provider abstraction:

```text
SMTP
transactional email provider
tenant-specific provider
```

---

# 77. Email Domain Verification

Support:

```text
SPF
DKIM
DMARC
```

configuration guidance/status where applicable.

---

# 78. Email Sender Configuration

Fields:

```text
from_name
from_email
reply_to
provider
status
```

---

# 79. Email Templates

Tenant-specific templates:

```text
welcome
password reset
project invite
proposal
quote
production update
invoice
notification
```

---

# 80. Email Template Variables

Use safe variable replacement:

```text
{{tenant.name}}
{{user.name}}
{{project.name}}
{{quote.number}}
```

---

# 81. No Arbitrary Template Code

Email templates must not execute PHP/JavaScript.

---

# 82. Document Branding

Tenant documents must support:

```text
logo
header
footer
colors
company details
GST/tax details
bank details
signature
terms
```

---

# 83. Document Templates

Support:

```text
proposal
quotation
BOQ
BOM
invoice
purchase order
work order
production order
delivery note
packing list
```

---

# 84. Template Versioning

Templates must be versioned:

```text
DRAFT
ACTIVE
ARCHIVED
```

---

# 85. Template Scope

Templates may be:

```text
PLATFORM
TENANT
FACTORY
PROJECT
```

---

# 86. Template Override

Tenant template overrides platform template.

Factory template overrides tenant template where configured.

---

# 87. Client Presentation Branding

Client-facing presentation must use tenant branding.

---

# 88. Client Portal Domain

Optional:

```text
portal.abcinteriors.com
```

---

# 89. Client Portal

Client may view:

```text
projects
designs
3D views
proposals
quotes
approvals
production status
documents
```

according to tenant configuration.

---

# 90. Client Isolation

Client users must only see:

```text
projects explicitly shared with them
```

---

# 91. Public Share Links

Support optional:

```text
secure share link
```

for:

```text
3D design
proposal
approval
```

---

# 92. Share Link Security

Use:

```text
opaque token
expiry
revocation
optional password
```

---

# 93. Tenant URL Routing

Application should resolve:

```text
request host
→ tenant domain
→ tenant context
→ application
```

---

# 94. Unknown Domain

Unknown/unregistered domain should return:

```text
404 / domain not configured
```

not another tenant's application.

---

# 95. Domain Cache

Domain → tenant resolution may be cached.

Cache must be invalidated when:

```text
domain changed
tenant suspended
domain revoked
```

---

# 96. Tenant Branding Cache

Branding may be cached for performance.

Invalidate on configuration changes.

---

# 97. Tenant Database Strategy

Initial recommendation:

```text
shared database
+
shared schema
+
tenant_id
```

with strict row-level application isolation.

---

# 98. Enterprise Database Strategy

Support future:

```text
database-per-tenant
```

for enterprise customers.

---

# 99. Hybrid Deployment

Architecture should permit:

```text
Shared SaaS
Dedicated Tenant
On-Premise
Private Cloud
```

without rewriting domain logic.

---

# 100. Deployment Model

Tenant may have:

```text
SAAS_SHARED
DEDICATED
PRIVATE_CLOUD
ON_PREMISE
```

---

# 101. Dedicated Tenant

A dedicated deployment may still use the same:

```text
application version
database schema
API contracts
tenant domain model
```

---

# 102. Enterprise Data Residency

Support future configuration:

```text
region
country
data residency policy
```

---

# 103. Storage Isolation

Tenant files must be stored under:

```text
tenant/{tenant_id}/
```

or equivalent isolated object-storage prefix.

---

# 104. File Access

Never expose raw storage paths containing predictable tenant data.

Use:

```text
signed URLs
authorization checks
```

---

# 105. Project Files

Example:

```text
tenant/TEN-001/projects/PROJ-100/
```

---

# 106. AI File Isolation

AI-uploaded files must inherit:

```text
tenant_id
project_id
```

and must not be retrievable across tenants.

---

# 107. Database Tenant Scope

Every repository querying tenant-owned tables must apply:

```text
tenant_id = currentTenantId
```

server-side.

---

# 108. Repository Pattern

Implement:

```text
TenantAwareRepository
```

or equivalent common abstraction.

---

# 109. Query Safety

Avoid scattered manual tenant filters where possible.

Centralize tenant scoping.

---

# 110. Tenant-Owned Tables

Typical tables include:

```text
projects
rooms
furniture
materials
laminates
hardware
bom
boq
quotes
production_orders
work_orders
panels
nesting_jobs
cnc_programs
mes_events
users/memberships
documents
ai_data
notifications
```

---

# 111. Global Tables

Potential platform-global tables:

```text
plans
platform_settings
global_feature_flags
global_catalogs
system_roles
system_permissions
```

---

# 112. Global Catalog Copy Strategy

When tenant adopts a platform catalog item, decide whether to:

```text
reference global item
```

or:

```text
clone into tenant catalog
```

---

# 113. Catalog Ownership

Every catalog item must identify:

```text
PLATFORM
TENANT
```

ownership.

---

# 114. Tenant Catalog

Tenant can create:

```text
materials
laminates
hardware
furniture templates
finishes
suppliers
```

---

# 115. Tenant Catalog Override

Tenant may override display/pricing attributes without modifying global master data.

---

# 116. Manufacturing Rules

Tenant-specific rules:

```text
minimum panel dimensions
edge rules
hardware rules
CNC constraints
machine capabilities
```

must be scoped to tenant/factory.

---

# 117. Pricing Isolation

Pricing must be tenant-scoped.

Never share tenant pricing with another tenant.

---

# 118. Supplier Data Isolation

Supplier contracts, costs and rates are tenant-private.

---

# 119. Manufacturing Data Isolation

Factory data must be tenant-scoped.

---

# 120. MES Isolation

MES dashboards must never mix factories/tenants unless the user is explicitly authorized for a multi-factory view.

---

# 121. Cross-Factory Reporting

Tenant-level users may see aggregated factory data if authorized.

---

# 122. Tenant Analytics

Analytics should support:

```text
tenant
factory
branch
project
```

dimensions.

---

# 123. SaaS Dashboard

Platform Admin dashboard:

```text
total tenants
active tenants
trial tenants
MRR/ARR if billing integrated
active users
projects
storage
AI usage
API usage
system health
```

---

# 124. Tenant Dashboard

Tenant Admin:

```text
projects
users
factories
storage
AI usage
subscription
feature usage
```

---

# 125. Usage Metering

Track:

```text
users
projects
storage
AI tokens
AI requests
API calls
generated documents
CNC jobs
MES panels
```

---

# 126. Usage Events

Create:

```text
usage_events
```

Fields:

```text
id
tenant_id
resource_type
resource_id
quantity
unit
timestamp
metadata_json
```

---

# 127. Usage Aggregation

Daily/monthly aggregation should be supported.

---

# 128. Usage Limits

When limit reached:

```text
warn
block
soft-limit
overage
```

according to plan.

---

# 129. Overage

Future support:

```text
pay-as-you-go
```

for selected resources.

---

# 130. Subscription Billing

If billing is implemented:

```text
BillingService
```

must be separated from application domain logic.

---

# 131. Payment Provider

Use provider abstraction.

Do not embed provider-specific billing logic throughout the application.

---

# 132. Subscription Webhooks

Billing webhooks must be:

```text
authenticated
idempotent
audited
```

---

# 133. Plan Changes

Support:

```text
upgrade
downgrade
trial conversion
renewal
cancellation
```

---

# 134. Plan Downgrade

If tenant exceeds new limits:

```text
do not delete data
```

Restrict new creation until compliant.

---

# 135. Tenant Deletion

Tenant deletion must be explicit and protected.

---

# 136. Deletion Workflow

```text
REQUESTED
 ↓
CONFIRMED
 ↓
GRACE PERIOD
 ↓
SOFT DELETED
 ↓
PURGED
```

---

# 137. Tenant Data Export

Before deletion, authorized tenant admin may request:

```text
projects
catalog
users
documents
manufacturing
MES
reports
```

export.

---

# 138. Export Format

Support where practical:

```text
CSV
JSON
ZIP
PDF
```

---

# 139. Export Job

Large exports must be asynchronous.

---

# 140. Backup

Tenant-aware backups must support:

```text
tenant
factory
database
files
configuration
```

---

# 141. Restore

Enterprise architecture should support:

```text
tenant restore
point-in-time restore
```

subject to infrastructure.

---

# 142. Tenant Disaster Recovery

Define:

```text
RPO
RTO
backup frequency
retention
```

per deployment tier.

---

# 143. Audit Logging

Tenant audit events:

```text
login
logout
user creation
role change
configuration change
domain change
branding change
catalog change
pricing change
production change
AI action
support access
data export
deletion
```

---

# 144. Audit Immutability

Audit records must be append-only.

---

# 145. Audit Fields

```text
tenant_id
user_id
action
resource_type
resource_id
old_value
new_value
IP
user_agent
timestamp
correlation_id
```

---

# 146. Security

Mandatory:

```text
HTTPS
secure cookies
CSRF protection
XSS protection
SQL injection protection
RBAC
tenant isolation
rate limiting
session security
audit logging
```

---

# 147. Authentication

Support:

```text
email/password
SSO
OAuth/OIDC
SAML for enterprise
```

where implemented.

---

# 148. SSO

Tenant-specific SSO configuration:

```text
issuer
client ID
client secret
metadata
certificate
domain
```

Secrets must be encrypted.

---

# 149. SSO Domain Mapping

Tenant may configure:

```text
company.com
```

to route users to the tenant's SSO.

---

# 150. Enterprise SSO

Support:

```text
OIDC
SAML 2.0
```

---

# 151. MFA

Support configurable:

```text
MFA required
MFA optional
MFA disabled
```

subject to security policy.

---

# 152. Session Policy

Tenant may configure:

```text
session timeout
idle timeout
concurrent sessions
```

within platform limits.

---

# 153. Password Policy

Tenant may configure:

```text
minimum length
complexity
expiry
history
```

if local authentication is used.

---

# 154. API Access

Tenant may generate:

```text
API keys
OAuth clients
service accounts
```

subject to plan.

---

# 155. API Key Scope

API keys must support:

```text
read
write
specific resource
specific project
specific factory
```

scopes where possible.

---

# 156. API Rate Limits

Tenant API usage must be rate-limited according to plan.

---

# 157. Webhooks

Tenant may configure webhooks for:

```text
project events
quote events
production events
MES events
panel events
dispatch events
```

---

# 158. Webhook Security

Use:

```text
HMAC signature
timestamp
nonce
retry protection
```

---

# 159. Webhook Delivery

Track:

```text
queued
sent
success
failed
retrying
dead-letter
```

---

# 160. Tenant Integration Registry

Store:

```text
integration
tenant
credentials
status
last_sync
configuration
```

---

# 161. Integration Secrets

Never store API secrets in plaintext where avoidable.

Use encryption/key management.

---

# 162. Tenant Notification Preferences

Configure:

```text
email
in-app
SMS
WhatsApp
push
```

where integrations exist.

---

# 163. Notification Templates

Tenant-branded templates should be versioned.

---

# 164. Tenant Workflow Configuration

Tenant may configure:

```text
approval workflows
production workflows
quality gates
quotation approval
client approval
```

---

# 165. Workflow Scope

Workflow can be:

```text
tenant
factory
project
```

---

# 166. Workflow Versioning

Published workflow versions must not be modified in-place.

Create new versions.

---

# 167. Tenant Custom Fields

Support configurable custom fields for:

```text
project
customer
room
furniture
material
production order
panel
quote
```

---

# 168. Custom Field Model

Fields:

```text
field_definition
field_type
entity_type
tenant_id
required
options
validation
```

---

# 169. Custom Field Types

Support:

```text
text
number
date
boolean
select
multi-select
currency
URL
```

---

# 170. Custom Fields in APIs

API must expose validated custom fields without allowing arbitrary SQL/JSON injection.

---

# 171. Tenant Branding in CAD

2D/3D application may use tenant:

```text
logo
name
colors
```

for client presentation/export where configured.

---

# 172. Tenant Branding in Reports

All reports must resolve effective branding.

---

# 173. Tenant Branding in Labels

Manufacturing labels may use tenant branding.

Factory may override label branding where configured.

---

# 174. Tenant Branding in MES

Shop-floor UI should show tenant/factory identity.

---

# 175. Tenant Branding in AI

AI Copilot may use:

```text
tenant application name
```

and tenant-specific terminology.

---

# 176. Tenant AI Policy

Tenant may configure:

```text
AI enabled
external models allowed
local models only
AI retention
AI usage limits
```

---

# 177. Tenant Knowledge Base

Each tenant can maintain:

```text
SOP
catalog docs
brand guidelines
manufacturing rules
sales policies
```

---

# 178. Tenant Knowledge Isolation

RAG retrieval must always enforce:

```text
tenant_id
```

and relevant scope.

---

# 179. Tenant AI Cost

AI cost must be attributed to:

```text
tenant
user
project
task
model
```

---

# 180. Tenant API Documentation

API docs should support tenant-specific credentials and scopes.

---

# 181. API Versioning

Use:

```text
/api/v1
```

and maintain backward compatibility.

---

# 182. Tenant API Base

Example:

```text
https://api.abcinteriors.com/api/v1
```

or:

```text
https://api.fmos.com/api/v1
```

with tenant authentication.

---

# 183. SaaS Health

Platform must expose internal health:

```text
database
cache
queue
storage
AI provider
email
file processing
```

without exposing sensitive information to tenants.

---

# 184. Tenant Health

Tenant Admin may see:

```text
service status
integration status
storage
usage
```

---

# 185. Maintenance Mode

Support:

```text
platform maintenance
tenant maintenance
factory maintenance
```

---

# 186. Tenant Maintenance

Tenant maintenance should show branded maintenance page.

---

# 187. Platform Maintenance

Platform maintenance page should use FMOS branding unless white-label policy requires tenant branding.

---

# 188. Error Pages

Tenant-facing:

```text
404
403
500
maintenance
```

must support tenant branding.

---

# 189. Logging

Application logs must include:

```text
tenant_id
user_id
request_id
correlation_id
```

where available.

---

# 190. Cross-Tenant Log Protection

Tenant users must never access raw platform logs.

---

# 191. Support Diagnostics

Support tools may inspect tenant diagnostics only through audited access.

---

# 192. Performance

Target:

```text
tenant resolution < 50 ms
branding resolution < 100 ms cached
normal API response < 500 ms
```

subject to operation complexity.

---

# 193. Database Performance

Tenant-scoped indexes should begin with:

```text
tenant_id
```

where query patterns require it.

---

# 194. Composite Index Examples

Examples:

```text
(tenant_id, status)
(tenant_id, project_id)
(tenant_id, created_at)
(tenant_id, factory_id, status)
```

---

# 195. Data Volume

Architecture must support:

```text
100+
tenants
```

initially and scale toward:

```text
1,000+
10,000+
```

without architectural redesign.

---

# 196. Large Tenant Strategy

Very large tenants may be migrated to:

```text
dedicated database
dedicated infrastructure
```

while retaining the same tenant model.

---

# 197. Caching

Tenant-aware cache keys must include:

```text
tenant_id
```

Example:

```text
tenant:TEN-001:branding
```

---

# 198. Cache Isolation

Never use:

```text
branding
```

as a global cache key.

---

# 199. Session Isolation

Session must contain validated tenant context.

Do not allow client-side tenant switching by changing a hidden field.

---

# 200. Tenant Switching

For users belonging to multiple tenants:

```text
select tenant
→ reauthenticate/authorize
→ create tenant-scoped session context
```

---

# 201. Tenant Switch Audit

Log:

```text
user
from_tenant
to_tenant
timestamp
```

---

# 202. Cross-Tenant Reports

Only platform-level or explicitly authorized users may aggregate tenants.

---

# 203. Tenant Data Export Security

Exports must require:

```text
permission
authentication
audit
```

---

# 204. Export Expiry

Generated export links should expire.

---

# 205. Download Security

Use signed temporary URLs where appropriate.

---

# 206. File Upload Limits

Tenant plans may define:

```text
max file size
storage quota
file types
```

---

# 207. Storage Metering

Track:

```text
file size
tenant
project
document
timestamp
```

---

# 208. Storage Quota

When quota is reached:

```text
warn
block new uploads
```

according to plan.

---

# 209. Tenant Backup Isolation

Tenant restore must not accidentally restore data into another tenant.

---

# 210. Migration Safety

All tenant migrations must be:

```text
versioned
transactional where possible
backward-compatible
audited
```

---

# 211. Database Migration

Never introduce schema changes that assume:

```text
only one tenant
```

---

# 212. Seed Data

Platform seed data should clearly distinguish:

```text
global seed
tenant seed
```

---

# 213. Demo Tenant

Create optional:

```text
DEMO tenant
```

with isolated fake data.

---

# 214. Trial Tenant

Trial tenant should be fully isolated like a production tenant.

---

# 215. Trial Restrictions

Use entitlements for:

```text
max users
projects
storage
AI requests
exports
```

---

# 216. Trial Conversion

Conversion to paid plan must retain:

```text
tenant ID
data
users
projects
configuration
```

---

# 217. Tenant Naming

Tenant slug must be unique.

Example:

```text
abc-interiors
```

---

# 218. Slug Rules

Allow:

```text
lowercase
numbers
hyphen
```

Reject unsafe characters.

---

# 219. Tenant Identifier

Use immutable internal:

```text
UUID/ULID
```

and human-readable:

```text
tenant_code
```

---

# 220. SaaS Database Minimum Tables

```text
tenants
tenant_domains
tenant_branding
tenant_settings
tenant_features
tenant_subscriptions
tenant_user_memberships
tenant_usage
tenant_api_keys
tenant_integrations
tenant_custom_fields
tenant_workflows
tenant_audit_logs
tenant_storage_usage
```

---

# 221. Tenants Table

Fields:

```text
id
tenant_code
slug
name
legal_name
status
plan_id
timezone
locale
currency
created_at
updated_at
deleted_at
```

---

# 222. Tenant Domains Table

Fields:

```text
id
tenant_id
domain
domain_type
verification_token_hash
verification_status
ssl_status
is_primary
created_at
verified_at
```

---

# 223. Tenant Settings Table

Fields:

```text
id
tenant_id
setting_key
setting_value
setting_type
scope
updated_by
updated_at
```

---

# 224. Tenant Features Table

Fields:

```text
id
tenant_id
feature_key
state
source
updated_at
```

---

# 225. Tenant Usage Table

Fields:

```text
id
tenant_id
resource_type
period_start
period_end
quantity
limit
updated_at
```

---

# 226. Tenant API Keys

Fields:

```text
id
tenant_id
name
key_hash
scopes
status
last_used_at
expires_at
created_at
```

Never store reusable API keys in plaintext.

---

# 227. Tenant Integrations

Fields:

```text
id
tenant_id
integration_type
status
configuration_json
secret_reference
last_sync_at
```

---

# 228. Tenant Custom Fields

Fields:

```text
id
tenant_id
entity_type
field_key
label
field_type
required
validation_json
options_json
status
```

---

# 229. Tenant Audit Logs

Fields:

```text
id
tenant_id
user_id
action
resource_type
resource_id
old_value_json
new_value_json
ip_address
user_agent
correlation_id
created_at
```

---

# 230. Tenant Storage Usage

Fields:

```text
id
tenant_id
resource_type
resource_id
bytes
created_at
```

---

# 231. API Middleware

Every tenant API request should pass:

```text
AuthenticationMiddleware
 ↓
TenantResolutionMiddleware
 ↓
TenantAuthorizationMiddleware
 ↓
RBACMiddleware
 ↓
Controller
```

---

# 232. Tenant Resolution Middleware

Responsibilities:

```text
read host
resolve tenant
validate status
attach tenant context
```

---

# 233. Tenant Authorization Middleware

Responsibilities:

```text
validate user membership
validate tenant status
validate domain
```

---

# 234. RBAC Integration

RBAC must be tenant-aware.

Example:

```text
Tenant Admin
Factory Manager
Designer
Engineer
Sales
Operator
Quality
Client
```

---

# 235. Tenant Role Scope

Roles may be:

```text
PLATFORM
TENANT
FACTORY
PROJECT
```

---

# 236. Tenant Permission Boundary

A role cannot grant access outside the user's tenant unless explicitly defined as a platform role.

---

# 237. Client Role

Client users should have highly restricted permissions:

```text
view shared project
comment
approve
download approved documents
```

---

# 238. White-Label Login

Login page must resolve tenant branding based on:

```text
domain
```

---

# 239. Login Branding

Show:

```text
tenant logo
tenant name
tenant background
tenant support contact
```

---

# 240. Login Domain Security

If user arrives on:

```text
abcinteriors.com
```

but domain is mapped to Tenant A, do not let query parameters switch tenant to Tenant B.

---

# 241. Password Reset Branding

Password reset emails/pages use tenant branding.

---

# 242. Invitation Branding

User invitations use tenant branding.

---

# 243. Tenant Support Contact

Tenant may configure:

```text
support_email
support_phone
support_url
```

---

# 244. Terms and Privacy

Tenant may configure customer-facing:

```text
terms URL
privacy URL
cookie policy URL
```

subject to platform/legal requirements.

---

# 245. Cookie Consent

Tenant-branded client portal may support configurable consent messaging.

---

# 246. Legal Identity

Tenant legal details must be stored separately from display branding.

Fields:

```text
legal_name
registration_number
tax_id
address
```

---

# 247. GST/Tax

Tenant may configure:

```text
GSTIN
tax registration
billing address
```

where applicable.

---

# 248. Tenant Invoice Identity

Invoices must use legal tenant details, not merely brand name.

---

# 249. Document Numbering

Tenant-specific sequences:

```text
QUOTE-0001
PROJ-0001
PO-0001
WO-0001
```

must not collide.

---

# 250. Number Sequence Table

```text
tenant_sequences
```

Fields:

```text
id
tenant_id
sequence_type
prefix
current_value
padding
reset_policy
```

---

# 251. Sequence Concurrency

Use transactional/atomic increment.

Never use:

```text
MAX(number)+1
```

---

# 252. Tenant Data Migration

Support future import:

```text
customers
projects
catalog
materials
users
```

---

# 253. Import Isolation

Imported records inherit target tenant.

---

# 254. Tenant Export/Import

Exported tenant data must carry:

```text
schema_version
tenant metadata
export timestamp
```

---

# 255. Import Validation

Validate:

```text
schema
relationships
IDs
duplicates
required fields
```

before commit.

---

# 256. SaaS Notifications

Platform-level notifications:

```text
subscription
maintenance
security
```

Tenant-level notifications:

```text
project
production
MES
AI
```

must remain separated.

---

# 257. Tenant Notification Queue

Queue items must contain:

```text
tenant_id
recipient
template
payload
```

---

# 258. Background Jobs

All queued jobs must retain:

```text
tenant_id
```

---

# 259. Job Isolation

A background job must validate tenant context before accessing data.

---

# 260. Scheduled Jobs

Examples:

```text
usage aggregation
subscription checks
domain checks
email
AI processing
document processing
production notifications
```

must run tenant-aware.

---

# 261. Queue Payload Security

Do not trust tenant ID alone from queue payload.

Validate against job/resource ownership.

---

# 262. Tenant-aware Search

Global search must filter by:

```text
tenant_id
```

before returning results.

---

# 263. Full-Text Search

Indexes must support tenant filtering.

---

# 264. Search Security

Do not rely solely on frontend filtering.

---

# 265. Tenant-aware Cache

All application caches involving tenant data must be scoped.

---

# 266. Tenant-aware Sessions

Session data must include:

```text
tenant_id
membership_id
```

and be server validated.

---

# 267. Tenant-aware Logs

Every important log line should include:

```text
tenant_id
request_id
```

---

# 268. Correlation ID

Requests should propagate:

```text
X-Correlation-ID
```

through:

```text
API
services
queues
AI
integrations
```

---

# 269. White-Label API

Provide API for tenant branding:

```http
GET /api/v1/tenant/branding
PATCH /api/v1/tenant/branding
```

---

# 270. Tenant Domain API

```http
GET /api/v1/tenant/domains
POST /api/v1/tenant/domains
POST /api/v1/tenant/domains/{id}/verify
DELETE /api/v1/tenant/domains/{id}
```

---

# 271. Tenant Settings API

```http
GET /api/v1/tenant/settings
PATCH /api/v1/tenant/settings
```

---

# 272. Tenant Features API

```http
GET /api/v1/tenant/features
PATCH /api/v1/tenant/features/{key}
```

---

# 273. Subscription API

```http
GET /api/v1/tenant/subscription
POST /api/v1/tenant/subscription/upgrade
POST /api/v1/tenant/subscription/cancel
```

---

# 274. Usage API

```http
GET /api/v1/tenant/usage
```

---

# 275. Admin Tenant API

Platform:

```http
GET /api/v1/platform/tenants
POST /api/v1/platform/tenants
GET /api/v1/platform/tenants/{id}
PATCH /api/v1/platform/tenants/{id}
POST /api/v1/platform/tenants/{id}/suspend
POST /api/v1/platform/tenants/{id}/restore
```

---

# 276. Tenant Provisioning API

```http
POST /api/v1/platform/tenants/provision
```

Must be idempotent.

---

# 277. Domain Resolution API

Internal service:

```text
resolveTenantByDomain(host)
```

---

# 278. Branding Resolution API

Internal:

```text
getEffectiveBranding(tenant, factory, project)
```

---

# 279. Configuration Resolution API

Internal:

```text
getEffectiveConfig(key, scope)
```

---

# 280. Entitlement Check API

Internal:

```text
hasEntitlement(tenant, feature)
```

---

# 281. Storage Service

Implement:

```text
TenantStorageService
```

responsible for:

```text
upload
download
delete
quota
signed URL
tenant isolation
```

---

# 282. Email Service

Implement:

```text
TenantEmailService
```

for:

```text
branding
template
sender
provider
audit
```

---

# 283. Document Service

Implement:

```text
TenantDocumentService
```

for:

```text
template
branding
versioning
generation
storage
```

---

# 284. SaaS Service Structure

Recommended PHP:

```text
src/
  SaaS/
    Tenant/
      TenantService.php
      TenantContextService.php
      TenantProvisioningService.php
      TenantResolver.php
    Domain/
      DomainService.php
      DomainVerificationService.php
    Branding/
      BrandingService.php
    Configuration/
      ConfigurationService.php
    Subscription/
      SubscriptionService.php
      EntitlementService.php
    Usage/
      UsageService.php
      MeteringService.php
    Storage/
      TenantStorageService.php
    Email/
      TenantEmailService.php
    Documents/
      TenantDocumentService.php
    Support/
      ImpersonationService.php
    Security/
      TenantAuthorizationService.php
```

---

# 285. ES6 Structure

Recommended:

```text
src/saas/
  tenant/
    TenantContext.js
    TenantResolver.js
  branding/
    BrandingManager.js
  settings/
    TenantSettings.js
  subscription/
    Entitlements.js
  domains/
    DomainManager.js
  admin/
    TenantAdmin.js
  onboarding/
    OnboardingWizard.js
```

---

# 286. Frontend Bootstrap

At application startup:

```text
resolve tenant
 ↓
load branding
 ↓
load configuration
 ↓
load entitlements
 ↓
load user permissions
 ↓
initialize application
```

---

# 287. Tenant Loading Failure

If tenant cannot be resolved:

```text
do not initialize tenant application
```

Show appropriate error.

---

# 288. Branding Loading Failure

Use safe platform fallback without exposing another tenant's branding.

---

# 289. Tenant Switch Frontend

After switching tenant:

```text
clear tenant-scoped cache
clear tenant-scoped state
reload branding
reload permissions
reload entitlements
```

---

# 290. Browser Storage

Keys must include tenant scope where tenant data is stored.

Example:

```text
fmos:TEN-001:ui-preferences
```

---

# 291. No Cross-Tenant Local State

Do not accidentally reuse:

```text
last project
last catalog
last AI context
```

from another tenant.

---

# 292. White-Label Theme

Theme must be dynamically loaded.

Do not rebuild frontend per tenant.

---

# 293. Tenant Custom CSS

Do not allow arbitrary CSS injection by tenant admins.

Use controlled theme variables.

---

# 294. Tenant Custom JavaScript

Do not allow tenant admins to inject arbitrary JavaScript.

This is a mandatory security requirement.

---

# 295. Tenant Custom HTML

Tenant-provided HTML must be sanitized.

---

# 296. Email HTML Security

Sanitize tenant-supplied HTML to prevent injection.

---

# 297. Client Portal Security

Client portal tokens must be:

```text
opaque
revocable
expirable
scoped
```

---

# 298. White-Label Mobile/PWA

If FMOS supports PWA/mobile:

```text
app name
icons
theme
splash
```

may be tenant-configurable where technically supported.

---

# 299. Enterprise Branding

Enterprise customers may request:

```text
custom login
custom domain
custom email
custom client portal
custom documents
custom support identity
```

---

# 300. Dedicated Deployment

Enterprise deployment may support:

```text
dedicated application
dedicated database
dedicated storage
dedicated AI gateway
```

---

# 301. Dedicated Deployment Configuration

Maintain deployment mode:

```text
shared
dedicated
private
on-premise
```

---

# 302. Core Domain Independence

Business modules must not depend directly on:

```text
shared SaaS infrastructure
```

Use interfaces/services.

---

# 303. Deployment Abstraction

Domain code should work in:

```text
shared SaaS
dedicated
on-premise
```

without changing business logic.

---

# 304. Tenant Migration

Support moving tenant from:

```text
shared
→ dedicated
```

with controlled migration.

---

# 305. Migration Process

```text
Pre-check
 ↓
Backup
 ↓
Export
 ↓
Import
 ↓
Validate
 ↓
DNS switch
 ↓
Smoke test
 ↓
Release
```

---

# 306. Migration Validation

Verify:

```text
users
projects
files
catalog
pricing
manufacturing
MES
AI
domains
branding
```

---

# 307. Tenant Versioning

Track:

```text
tenant_schema_version
tenant_feature_version
```

where required.

---

# 308. Backward Compatibility

APIs and data migrations must preserve existing tenant functionality during rollout.

---

# 309. SaaS Release Strategy

Support:

```text
platform release
tenant rollout
feature flag
rollback
```

---

# 310. Tenant Beta Program

Selected tenants may receive:

```text
beta features
```

without exposing them globally.

---

# 311. Tenant-Specific Feature Rollout

Support:

```text
tenant allowlist
```

for controlled releases.

---

# 312. Canary Tenant

Platform may designate:

```text
canary tenants
```

for release validation.

---

# 313. SaaS Monitoring

Monitor:

```text
requests
errors
latency
database
storage
queues
AI
email
tenant provisioning
domain resolution
```

---

# 314. Tenant-Level Monitoring

Track:

```text
API errors
usage
storage
AI failures
integration failures
```

---

# 315. Noisy Neighbor Protection

One tenant must not consume unlimited resources and degrade other tenants.

Use:

```text
rate limits
queue quotas
AI limits
storage quotas
job concurrency
```

---

# 316. Tenant Resource Limits

Support:

```text
max concurrent jobs
max API requests
max file upload
max background jobs
```

---

# 317. Queue Isolation

Critical factory/MES jobs may require separate queues from:

```text
AI
document processing
email
```

---

# 318. Priority Queues

Support:

```text
critical MES
production
standard
AI
background
```

---

# 319. Database Protection

Use connection/query limits and optimized indexes to protect shared database.

---

# 320. SaaS Security Testing

Mandatory:

```text
tenant escape tests
IDOR tests
authorization tests
domain spoof tests
cache isolation tests
file access tests
API key tests
SSO tests
impersonation tests
```

---

# 321. Tenant Escape Test

Attempt:

```text
Tenant A user
→ Tenant B resource ID
```

Expected:

```text
404 or 403
```

without leaking existence.

---

# 322. Cache Isolation Test

Verify:

```text
Tenant A branding
```

can never appear in:

```text
Tenant B
```

---

# 323. File Isolation Test

Verify Tenant A cannot download Tenant B files even if it knows the storage identifier.

---

# 324. API Isolation Test

Verify every API endpoint respects tenant scope.

---

# 325. Search Isolation Test

Verify global search does not leak other tenants.

---

# 326. AI Isolation Test

Verify AI cannot retrieve:

```text
another tenant's documents
catalog
projects
pricing
MES
```

---

# 327. Background Job Isolation Test

Verify queued jobs cannot execute against another tenant's records.

---

# 328. Webhook Isolation Test

Verify tenant webhook credentials/events remain isolated.

---

# 329. Support Impersonation Test

Verify:

```text
impersonation requires permission
reason required
expiry enforced
audit created
```

---

# 330. Domain Security Test

Verify:

```text
unverified domain
```

cannot activate tenant.

---

# 331. Subscription Test

Verify:

```text
suspended tenant
```

cannot use restricted features.

---

# 332. Entitlement Test

Verify:

```text
plan without MES
```

cannot access MES.

---

# 333. White-Label Acceptance Criteria

Given Tenant A:

```text
ABC Interiors
```

with:

```text
logo
custom colors
custom domain
```

the application must show:

```text
ABC Interiors branding
```

across configured tenant-facing screens.

---

# 334. Custom Domain Acceptance Criteria

Given:

```text
design.abcinteriors.com
```

after DNS verification:

```text
request
→ Tenant A
→ Tenant A branding
→ Tenant A login
```

must resolve correctly.

---

# 335. Tenant Isolation Acceptance Criteria

Given:

```text
Tenant A Project A
Tenant B Project B
```

Tenant A user must not be able to access:

```text
Project B
```

through:

```text
URL
API
search
file
AI
cache
```

---

# 336. Branding Acceptance Criteria

Changing tenant logo must update:

```text
login
application shell
documents
emails
client portal
```

where enabled.

---

# 337. Plan Acceptance Criteria

Tenant on Starter plan must not access features excluded from Starter.

---

# 338. Upgrade Acceptance Criteria

Upgrade must enable newly entitled features without changing:

```text
tenant ID
data
users
projects
```

---

# 339. Downgrade Acceptance Criteria

Downgrade must not delete existing data automatically.

---

# 340. Subscription Suspension Acceptance Criteria

Suspension must:

```text
preserve data
restrict configured writes
show status
```

---

# 341. Tenant Deletion Acceptance Criteria

Deletion must require:

```text
authorized user
confirmation
audit
grace period
```

---

# 342. Dedicated Deployment Acceptance Criteria

Moving a tenant to dedicated infrastructure must preserve:

```text
tenant identity
users
projects
catalog
branding
domains
```

---

# 343. Cursor Pre-Implementation Analysis

Before implementation, Cursor MUST inspect the current codebase for:

```text
authentication
users
roles
permissions
projects
factories
catalog
pricing
documents
file storage
email
AI
API
database
caching
queues
configuration
environment variables
deployment
```

Cursor must produce:

```text
CURRENT TENANCY MODEL
CURRENT USER MODEL
CURRENT AUTH MODEL
CURRENT RBAC
CURRENT DOMAIN ROUTING
CURRENT BRANDING
CURRENT FILE STORAGE
CURRENT EMAIL
CURRENT CONFIGURATION
CURRENT DATABASE
CURRENT API
CURRENT CACHE
CURRENT QUEUE
CURRENT AI
CURRENT SECURITY
CURRENT DUPLICATE LOGIC
CURRENT MULTI-TENANT GAPS
TARGET SAAS ARCHITECTURE
MIGRATION PLAN
```

Do not create duplicate:

```text
authentication
RBAC
configuration
storage
email
AI gateway
```

if equivalent infrastructure already exists.

---

# 344. Cursor Implementation Sequence

## Phase 1 — Tenant Core

```text
tenants
tenant context
tenant resolver
tenant middleware
```

## Phase 2 — Data Isolation

```text
tenant_id
repositories
query scopes
indexes
```

## Phase 3 — User Membership

```text
tenant memberships
roles
factory access
```

## Phase 4 — Branding

```text
branding
theme
logo
favicon
application name
```

## Phase 5 — Domains

```text
subdomain
custom domain
DNS verification
SSL status
```

## Phase 6 — Configuration

```text
tenant settings
effective configuration
factory overrides
```

## Phase 7 — Plans

```text
plans
subscriptions
entitlements
usage
```

## Phase 8 — White Label

```text
login
email
documents
client portal
labels
MES
```

## Phase 9 — Enterprise

```text
SSO
dedicated deployment
support impersonation
data residency
```

## Phase 10 — Hardening

```text
security tests
tenant escape tests
performance
backup
restore
migration
```

---

# 345. Recommended Service Flow

```text
HTTP Request
    ↓
Authentication
    ↓
Domain Resolver
    ↓
Tenant Context
    ↓
Tenant Status
    ↓
Membership
    ↓
RBAC
    ↓
Entitlement
    ↓
Domain Service
    ↓
Repository
    ↓
Tenant-scoped DB
```

---

# 346. Golden Rule

Every tenant-aware operation must answer:

```text
WHO?
```

```text
WHICH TENANT?
```

```text
WHICH FACTORY?
```

```text
WHICH PROJECT?
```

```text
WHICH RESOURCE?
```

```text
IS THE USER AUTHORIZED?
```

before modifying data.

---

# 347. Final Definition of Done

```text
[ ] Multi-tenant architecture implemented
[ ] Tenant model implemented
[ ] Tenant context implemented
[ ] Tenant resolution implemented
[ ] Tenant middleware implemented
[ ] Tenant database isolation implemented
[ ] Tenant-aware repositories implemented
[ ] Tenant-aware indexes implemented
[ ] User membership implemented
[ ] Tenant roles implemented
[ ] Factory scope implemented
[ ] Tenant branding implemented
[ ] Dynamic theme implemented
[ ] Logo implemented
[ ] Favicon implemented
[ ] Custom application name implemented
[ ] White-label mode implemented
[ ] Custom domains implemented
[ ] DNS verification implemented
[ ] SSL status implemented
[ ] Tenant settings implemented
[ ] Configuration hierarchy implemented
[ ] Plans implemented
[ ] Subscriptions implemented
[ ] Entitlements implemented
[ ] Usage metering implemented
[ ] Storage quotas implemented
[ ] Tenant provisioning implemented
[ ] Tenant onboarding implemented
[ ] Tenant suspension implemented
[ ] Tenant deletion workflow implemented
[ ] Tenant export implemented
[ ] Tenant backup strategy implemented
[ ] Tenant restore strategy implemented
[ ] Tenant email branding implemented
[ ] Custom email sender architecture implemented
[ ] Document branding implemented
[ ] Client portal branding implemented
[ ] Public share security implemented
[ ] Tenant API keys implemented
[ ] Tenant webhooks implemented
[ ] Tenant integrations implemented
[ ] Tenant custom fields implemented
[ ] Tenant workflows implemented
[ ] Tenant AI policy implemented
[ ] Tenant knowledge isolation implemented
[ ] Tenant AI cost tracking implemented
[ ] Tenant analytics implemented
[ ] Tenant audit logging implemented
[ ] Support impersonation implemented
[ ] Impersonation audit implemented
[ ] SSO architecture implemented
[ ] MFA architecture implemented
[ ] Dedicated deployment architecture supported
[ ] Shared/dedicated deployment abstraction implemented
[ ] Tenant migration architecture implemented
[ ] Noisy-neighbor protection implemented
[ ] Queue isolation implemented
[ ] Cache isolation implemented
[ ] File isolation implemented
[ ] Search isolation implemented
[ ] AI isolation implemented
[ ] API isolation tested
[ ] Tenant escape tests passed
[ ] Domain security tests passed
[ ] File security tests passed
[ ] RBAC tests passed
[ ] Subscription tests passed
[ ] Performance tests passed
[ ] Backup/restore tests passed
[ ] Production deployment validated
```

---

# 348. Final Architecture Principle

FMOS White-Label/SaaS must follow this model:

```text
                         FMOS PLATFORM
                              │
              ┌───────────────┴────────────────┐
              │                                │
        PLATFORM SERVICES                SHARED DOMAIN
              │                                │
       ┌──────┼──────┐                ┌────────┼────────┐
       ↓      ↓      ↓                ↓        ↓        ↓
     Auth   Billing  AI             CAD    Manufacturing MES
       │      │      │                │        │        │
       └──────┼──────┘                └────────┼────────┘
              │                                │
              └───────────────┬────────────────┘
                              ↓
                         TENANT CONTEXT
                              │
          ┌───────────────────┼───────────────────┐
          ↓                   ↓                   ↓
       Tenant A            Tenant B            Tenant C
          │                   │                   │
      Branding            Branding            Branding
      Domain              Domain              Domain
      Users               Users               Users
      Catalog             Catalog             Catalog
      Pricing             Pricing             Pricing
      Factory             Factory             Factory
      Projects            Projects            Projects
      AI Context          AI Context          AI Context
      MES                  MES                 MES
```

The fundamental rule is:

> **FMOS should be one product platform, but every tenant should experience it as its own private business operating system.**

That means the architecture must make **tenant isolation, configuration, branding, domain mapping, entitlement, and security first-class platform capabilities**, rather than features added later.

The result should allow FMOS to evolve from a single interior-design application into a **multi-tenant, white-label Design → Engineering → Manufacturing → MES SaaS platform**, while still supporting enterprise customers who require dedicated infrastructure or private deployments.
