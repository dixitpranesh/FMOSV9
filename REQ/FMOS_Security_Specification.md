# FMOS — Security Specification

**Document ID:** SEC-001  
**Version:** 1.0  
**Status:** Implementation Baseline  
**Product:** FMOS — End-to-End Interior Design, Furniture Engineering, Manufacturing & MES SaaS Platform  
**Technology:** PHP 8.x + MySQL 8.x + JavaScript ES6+ + Canvas/SVG + Three.js/WebGL  
**Audience:** Cursor, Software Engineering, QA, DevOps, Security Engineering, Product, Platform Administration  
**Date:** 2026-08-10

---

# 1. Purpose

This document defines the mandatory security architecture, controls, implementation requirements, security APIs, data protection rules, operational controls and security acceptance criteria for FMOS.

FMOS is a multi-tenant SaaS platform that handles:

- Customer and project information
- Interior designs
- 2D CAD geometry
- 3D/BIM data
- Parametric furniture models
- Material and hardware catalogs
- BOM/BOQ
- Pricing, cost and margin information
- Client approvals
- Manufacturing engineering data
- Nesting layouts
- CNC/CAM files
- Factory/MES data
- QR/panel genealogy
- Quality records
- Documents and files
- AI prompts, context and outputs
- Tenant branding
- User identities and permissions
- Subscription and usage information

Security must therefore protect both conventional SaaS data and highly sensitive engineering/manufacturing information.

---

# 2. Security Objectives

FMOS security shall provide:

```text
CONFIDENTIALITY
INTEGRITY
AVAILABILITY
AUTHENTICATION
AUTHORIZATION
TENANT ISOLATION
TRACEABILITY
NON-REPUDIATION WHERE REQUIRED
SECURE DATA PROCESSING
SECURE MANUFACTURING OUTPUT
```

The primary security principle is:

> A user, tenant, AI agent, API client, background job, file, or integration must never obtain or modify data or perform an operation beyond its explicitly authorized scope.

---

# 3. Security Principles

## SEC-PR-001 — Zero Trust

Every request must be authenticated and authorized according to its context.

Do not trust:

- UI state
- hidden buttons
- URL parameters
- client-provided tenant IDs
- client-provided role names
- client-provided ownership
- AI instructions
- file names
- QR payloads

---

## SEC-PR-002 — Server-Side Authorization

Frontend permissions are for UX.

Backend authorization is authoritative.

A user who cannot perform an action through the UI must also be unable to perform it through:

```text
REST API
direct URL
browser developer tools
mobile client
script
Postman
background endpoint
websocket
file endpoint
AI tool
```

---

## SEC-PR-003 — Tenant Isolation by Design

Every tenant-owned resource must be isolated at the application and data-access layers.

Tenant isolation must not depend solely on:

```text
WHERE tenant_id = ?
```

added manually by individual developers.

The architecture must provide reusable tenant-scoped data-access mechanisms.

---

## SEC-PR-004 — Least Privilege

Users, services, database accounts, API clients and AI tools receive only required permissions.

---

## SEC-PR-005 — Deny by Default

Unknown/unconfigured access must be denied.

---

## SEC-PR-006 — Secure by Default

New:

```text
users
roles
APIs
files
integrations
tenants
features
```

must default to the most restrictive safe state.

---

## SEC-PR-007 — Defense in Depth

Critical operations should have multiple controls:

```text
Authentication
→ Authorization
→ Scope validation
→ Business validation
→ Audit
```

---

## SEC-PR-008 — Immutable History

Approved/released manufacturing and commercial revisions must not be silently modified.

---

# 4. Threat Model

FMOS shall explicitly consider:

```text
External attacker
Authenticated malicious user
Compromised tenant user
Tenant admin misuse
Platform admin misuse
Stolen session
Stolen API token
Malicious client
Malicious uploaded file
Malicious CAD file
Malicious catalog data
Prompt injection
Compromised AI provider
Compromised integration
Compromised shop-floor device
Insider threat
Database compromise
Storage compromise
Supply-chain compromise
```

---

# 5. Security Trust Boundaries

The architecture contains the following trust boundaries:

```text
Browser
    ↓
Web/API Layer
    ↓
Application Services
    ↓
Database

Browser
    ↓
File Upload
    ↓
Object/File Storage

Browser
    ↓
AI Gateway
    ↓
External AI Provider

FMOS
    ↓
External Integrations

MES Device
    ↓
MES API

QR/Barcode
    ↓
Panel API
```

Every boundary must validate input and authorization.

---

# 6. Security Zones

Recommended logical zones:

```text
PUBLIC
AUTHENTICATED APPLICATION
ADMIN
MANUFACTURING
SHOP FLOOR
INTEGRATION
AI
STORAGE
DATABASE
OBSERVABILITY
```

High-risk manufacturing functions should not be treated as ordinary low-risk CRUD operations.

---

# 7. Identity Model

The identity model shall distinguish:

```text
User
Tenant Membership
Role
Permission
Scope
Session
API Credential
Service Account
Client User
Platform Administrator
```

---

# 8. User Identity Requirements

Each user must have:

```text
immutable user ID
email/username
status
authentication method
created timestamp
updated timestamp
```

Never use mutable email address as the primary relational identity.

---

# 9. User Status

Supported states should include:

```text
INVITED
ACTIVE
SUSPENDED
LOCKED
DISABLED
DELETED/DEACTIVATED
```

Disabled users cannot authenticate.

---

# 10. Tenant Membership

A user may belong to one or more tenants only if explicitly supported.

Membership must define:

```text
user_id
tenant_id
status
roles
scope
created_at
```

---

# 11. Tenant Context

Tenant context must be established server-side from trusted authentication/session information.

Never trust:

```text
tenant_id
```

from a browser request as proof of tenant membership.

If a tenant ID is supplied by the client, it must be treated as an untrusted selector and validated against authenticated membership.

---

# 12. Authentication

Supported authentication mechanisms may include:

```text
email/password
SSO
OAuth/OIDC
MFA
```

Use standards-based implementations.

Do not implement cryptographic authentication protocols manually.

---

# 13. Password Security

Passwords must:

- Never be stored in plaintext.
- Never be logged.
- Never be included in API responses.
- Be hashed using a modern password hashing algorithm supported by the PHP runtime.
- Use a unique salt per password.
- Support secure password reset.

Recommended:

```text
password_hash()
password_verify()
```

using an appropriate modern algorithm/configuration.

---

# 14. Password Policy

Tenant policy may configure:

```text
minimum length
complexity
password history
expiration if required
```

Avoid unnecessary complexity rules if they harm usability; strong password length is preferred.

---

# 15. Password Reset

Reset tokens must:

```text
be cryptographically random
be single-use
expire
be invalidated after successful reset
not expose whether an account exists unnecessarily
```

---

# 16. Login Rate Limiting

Rate-limit authentication attempts by combinations such as:

```text
account
IP
tenant
device/session
```

Avoid controls that can trivially become denial-of-service mechanisms.

---

# 17. Account Lockout

If account lockout is implemented:

- Avoid permanent lockout.
- Use progressive delay or controlled temporary lockout.
- Provide recovery path.
- Audit lockout events.

---

# 18. MFA

Where enabled, MFA must support secure enrollment and recovery.

MFA secrets must be protected.

Recovery codes must be one-time-use.

---

# 19. Session Management

Sessions must:

```text
expire
rotate after authentication
be invalidated on logout
support revocation
use secure cookies
```

Recommended cookie controls:

```text
Secure
HttpOnly
SameSite
```

as appropriate to architecture.

---

# 20. Session Fixation

Session identifiers must rotate after:

```text
login
privilege elevation
MFA completion
```

where applicable.

---

# 21. Concurrent Sessions

Tenant/admin policy may control concurrent sessions.

Users should be able to review/revoke active sessions where supported.

---

# 22. API Authentication

Protected APIs must require authenticated credentials.

Use a consistent mechanism such as:

```text
session cookie
short-lived access token
OAuth/OIDC
```

depending on application architecture.

---

# 23. API Tokens

If personal/API tokens are supported:

- Store only hashed token representations where practical.
- Display token secret only once.
- Support expiration.
- Support revocation.
- Scope tokens to permissions.
- Audit token creation/revocation.

---

# 24. Service Accounts

Service accounts must:

```text
have explicit owner
have limited scope
have rotation capability
have expiration where appropriate
be auditable
```

---

# 25. Authorization Model

FMOS shall implement:

```text
RBAC
+
resource scope
+
tenant scope
+
factory/project scope where applicable
```

Example:

```text
Role:
Manufacturing Engineer

Permissions:
manufacturing.view
manufacturing.validate
manufacturing.generate
manufacturing.release

Scope:
Factory A
```

---

# 26. Permission Naming

Use stable permission identifiers such as:

```text
project.view
project.create
project.edit
project.delete
project.approve

cad.view
cad.edit
cad.export

furniture.view
furniture.create
furniture.edit
furniture.release

bom.view
bom.generate
bom.export

pricing.view
pricing.edit
pricing.margin.view

manufacturing.view
manufacturing.validate
manufacturing.release

nesting.view
nesting.generate
nesting.release

cnc.view
cnc.generate
cnc.release
cnc.download

mes.view
mes.execute
mes.pause
mes.complete

panel.view
panel.scan

quality.view
quality.inspect
quality.rework
quality.scrap

admin.users
admin.roles
admin.settings
admin.audit
```

---

# 27. Authorization Decision

Every protected operation should evaluate:

```text
authenticated?
tenant membership?
user active?
permission?
resource scope?
factory scope?
project scope?
object state?
business rule?
```

---

# 28. Horizontal Privilege Escalation

A user with access to:

```text
Project A
```

must not access:

```text
Project B
```

unless explicitly authorized.

---

# 29. Vertical Privilege Escalation

A low-privilege user must not invoke high-privilege functions.

Example:

```text
Operator
```

must not call:

```text
POST /production-orders/{id}/release
```

unless explicitly granted.

---

# 30. Resource-Level Authorization

Authorization must happen at the resource level.

Do not assume:

```text
has manufacturing.view
```

means user can view every manufacturing record.

Scope must still be evaluated.

---

# 31. Object-Level Authorization

For every object:

```text
project
room
furniture
BOM
quote
panel
production order
CNC file
```

the API must verify access to the specific object.

---

# 32. Tenant Data Access Layer

Implement a reusable tenant-aware data access pattern.

Recommended conceptual interface:

```php
TenantContext::id();

TenantScope::assertAccess($resource);

TenantRepository::find($id);
```

The exact implementation may vary.

---

# 33. Tenant Query Rule

All tenant-owned queries must automatically apply tenant scope.

Bad:

```php
SELECT * FROM projects WHERE id = ?
```

Preferred conceptual pattern:

```php
SELECT *
FROM projects
WHERE tenant_id = ?
AND id = ?
```

The architecture should make omission difficult.

---

# 34. Cross-Tenant IDOR Protection

If Tenant A requests:

```text
/api/v1/projects/{tenant_b_project_id}
```

response must be:

```text
403
```

or:

```text
404
```

according to information-disclosure policy.

No Tenant B data may be returned.

---

# 35. Search Isolation

Global search must apply:

```text
tenant
permission
scope
```

before results are returned.

---

# 36. Export Isolation

CSV/PDF/Excel/JSON exports must apply the same authorization rules as normal APIs.

---

# 37. File Isolation

File access must be authorized independently of:

```text
file name
file path
storage key
URL
```

A known file URL must not grant access.

---

# 38. Storage Key Design

Recommended conceptual structure:

```text
tenant/{tenant_id}/projects/{project_id}/...
```

but storage path is not a security boundary by itself.

---

# 39. Signed File URLs

If signed URLs are used:

- Short expiration.
- Resource-specific.
- Tenant authorization before generation.
- No permanent public URLs for private content.

---

# 40. File Upload Security

Allowed uploads must be explicitly defined.

Examples:

```text
PNG
JPEG
WEBP
PDF
DXF
CSV
JSON
approved CAD formats
```

Do not accept arbitrary executable file types.

---

# 41. File Upload Validation

Validate:

```text
extension
MIME type
magic bytes
size
content structure
virus/malware scan where appropriate
```

Do not trust the client-provided MIME type.

---

# 42. Filename Security

Normalize/reject filenames containing:

```text
../
..\ 
null bytes
control characters
unexpected path separators
```

Never use the original filename directly as a filesystem path.

---

# 43. File Storage

Uploaded files should be stored outside executable web roots where possible.

---

# 44. CAD File Security

CAD imports are untrusted.

Validate:

```text
file size
object count
geometry complexity
recursion
malformed records
unexpected object types
```

Prevent parser/resource-exhaustion attacks.

---

# 45. PDF Security

PDFs must be treated as untrusted input.

Use safe rendering/parsing processes.

---

# 46. Image Security

Images must be:

```text
validated
decoded safely
size-limited
optionally re-encoded
```

Avoid server-side processing vulnerabilities.

---

# 47. CSV Security

Protect against spreadsheet formula injection.

Fields beginning with dangerous formula characters should be escaped when generating spreadsheets intended for user download.

---

# 48. JSON Security

Validate schema and maximum nesting/size.

Do not blindly deserialize arbitrary PHP objects.

---

# 49. XML Security

If XML is supported, disable dangerous external entity processing and protect against XXE.

---

# 50. ZIP Security

If ZIP imports are supported:

- Prevent path traversal.
- Limit decompression size.
- Limit file count.
- Validate file types.
- Prevent archive bombs.

---

# 51. Web Security Headers

Configure appropriate headers including:

```text
Content-Security-Policy
Strict-Transport-Security
X-Content-Type-Options
Referrer-Policy
Permissions-Policy
```

Avoid legacy headers that no longer provide meaningful protection.

---

# 52. HTTPS

All authenticated and sensitive traffic must use HTTPS.

HTTP should redirect to HTTPS where appropriate.

---

# 53. TLS

Use current secure TLS configurations.

Do not support obsolete protocols/ciphers unnecessarily.

---

# 54. CSRF

State-changing cookie-authenticated requests must use CSRF protection.

Relevant operations:

```text
POST
PUT
PATCH
DELETE
```

---

# 55. CORS

CORS must use an allowlist.

Do not use:

```text
Access-Control-Allow-Origin: *
```

for authenticated private APIs.

---

# 56. XSS Protection

User-controlled data must be contextually escaped.

Areas include:

```text
project name
customer name
comments
catalog name
AI output
document content
```

---

# 57. DOM XSS

Avoid unsafe JavaScript patterns such as:

```javascript
innerHTML = userInput;
```

unless content is sanitized and safe by design.

Prefer:

```javascript
textContent
```

or trusted rendering libraries.

---

# 58. SQL Injection

All database queries must use parameterized statements.

Never concatenate user input into SQL.

---

# 59. PHP Database Access

Use the existing database abstraction layer or PDO with prepared statements.

Do not introduce ad-hoc raw SQL concatenation.

---

# 60. Command Injection

Never directly concatenate user-controlled values into:

```text
shell commands
CLI tools
CNC converters
PDF processors
image processors
```

Use safe argument APIs and strict allowlists.

---

# 61. Server-Side Request Forgery

If FMOS fetches external URLs:

- Validate allowed protocols.
- Restrict destinations.
- Block internal IP ranges.
- Block metadata endpoints.
- Resolve/revalidate DNS carefully.
- Use allowlists for integrations.

---

# 62. Open Redirect

Redirect destinations must be allowlisted or validated.

---

# 63. Path Traversal

Every filesystem path derived from user input must be normalized and validated.

---

# 64. Mass Assignment

Do not map arbitrary request fields directly into database models.

Use explicit allowlists:

```text
fillable fields
```

---

# 65. Parameter Pollution

Reject or normalize duplicate parameters where ambiguity could create authorization/security problems.

---

# 66. Input Validation

Validate on the server:

```text
type
length
range
format
enum
relationship
ownership
state
```

---

# 67. Output Encoding

Encode output according to context:

```text
HTML
JavaScript
URL
JSON
CSV
PDF
SQL
```

---

# 68. Error Handling

Production responses must not reveal:

```text
stack traces
SQL
filesystem paths
credentials
internal class names
environment variables
```

---

# 69. Error Correlation

Return a safe:

```text
error code
correlation ID
```

for troubleshooting.

---

# 70. Logging Security

Never log:

```text
passwords
session tokens
API secrets
MFA secrets
private keys
raw authorization headers
```

---

# 71. PII Logging

Minimize logging of:

```text
phone
email
address
customer details
```

unless required.

Mask sensitive values where possible.

---

# 72. Audit Logging

Audit high-risk actions:

```text
login
logout
failed login
role change
permission change
tenant creation
tenant suspension
project approval
quote approval
manufacturing release
CNC release
scrap
dispatch
data export
API token creation
AI privileged tool call
support impersonation
```

---

# 73. Audit Event Structure

Recommended:

```text
audit_id
tenant_id
actor_user_id
actor_type
action
resource_type
resource_id
old_state_hash/reference where appropriate
new_state_hash/reference where appropriate
timestamp
IP
user_agent
correlation_id
```

Do not store unnecessary sensitive payloads.

---

# 74. Audit Integrity

Audit records should be append-only for normal users.

Administrative deletion requires exceptional controlled procedure.

---

# 75. Time Synchronization

Servers must use reliable time synchronization.

Audit timestamps should be stored consistently, preferably UTC.

---

# 76. Data Classification

Classify FMOS data as:

```text
PUBLIC
INTERNAL
CONFIDENTIAL
RESTRICTED
CRITICAL
```

---

# 77. Suggested Classification

## PUBLIC

Marketing/tenant-approved public content.

## INTERNAL

General operational information.

## CONFIDENTIAL

Customer/project/design information.

## RESTRICTED

Pricing, margin, business-sensitive data.

## CRITICAL

CNC, manufacturing rules, credentials, security secrets.

---

# 78. Data Access Rules

Each classification requires progressively stronger controls.

---

# 79. Encryption at Rest

Sensitive data should use encryption at rest through:

```text
database encryption
disk encryption
managed storage encryption
```

depending on infrastructure.

---

# 80. Application-Level Encryption

Consider application-level encryption for highly sensitive fields when infrastructure encryption is insufficient.

Examples:

```text
integration secrets
SSO secrets
API credentials
private configuration
```

---

# 81. Key Management

Do not store encryption keys in source code.

Use:

```text
environment secret manager
KMS
vault
cloud secret manager
```

where available.

---

# 82. Key Rotation

Keys/secrets should support controlled rotation without unnecessary service disruption.

---

# 83. Secrets Management

Secrets must never be committed to:

```text
Git
frontend bundles
JavaScript source
database seed files
documentation
logs
```

---

# 84. Environment Separation

Use different credentials for:

```text
DEV
QA
STAGING
PROD
```

---

# 85. Production Credentials

Developers should not routinely use unrestricted production credentials.

---

# 86. Database Accounts

Use least-privilege database accounts.

Separate:

```text
application
migration
reporting
backup
```

where practical.

---

# 87. Database Network Security

Database should not be directly exposed to the public Internet unless there is an explicit hardened architecture.

---

# 88. Database Backups

Backups must be encrypted and access controlled.

---

# 89. Backup Restore

Restore testing must be performed periodically.

---

# 90. Database Row-Level Security

If MySQL architecture does not provide native RLS suitable for the design, implement tenant isolation in the application/repository layer with centralized enforcement and automated tests.

---

# 91. Cache Isolation

Every cache key containing tenant data must include tenant context.

Example:

```text
tenant:{tenantId}:project:{projectId}
```

---

# 92. Cache Authorization

Never assume cached data is safe merely because the initial request was authorized.

---

# 93. Queue Isolation

Background jobs must contain trusted:

```text
tenant_id
resource_id
```

and revalidate authorization/ownership before processing where required.

---

# 94. Background Job Security

A job must not trust stale client-provided authorization.

Re-resolve resource state server-side.

---

# 95. Queue Poisoning

Validate job payloads.

Reject malformed or unexpected jobs.

---

# 96. Job Idempotency

Critical jobs must be safely retryable.

---

# 97. Webhook Security

If webhooks are supported:

```text
signature verification
timestamp validation
replay protection
source validation
idempotency
```

must be implemented.

---

# 98. API Rate Limiting

Rate-limit sensitive endpoints:

```text
login
password reset
AI
file upload
exports
search
QR
CNC generation
nesting
```

according to risk.

---

# 99. API Abuse Protection

Protect expensive operations against:

```text
request floods
large payloads
repeated generation
AI abuse
file upload abuse
```

---

# 100. Pagination Limits

APIs must enforce maximum page size.

Never allow:

```text
?page_size=10000000
```

to cause uncontrolled database queries.

---

# 101. Request Body Limits

Configure maximum:

```text
JSON payload
multipart upload
file upload
batch operation
```

sizes.

---

# 102. Resource Exhaustion

Protect computationally expensive functions:

```text
3D processing
CAD imports
nesting
CNC generation
PDF generation
AI processing
large catalog imports
```

with:

```text
limits
timeouts
queues
quotas
```

---

# 103. Tenant Quotas

Where applicable enforce:

```text
storage
users
projects
AI usage
API calls
large imports
```

---

# 104. Manufacturing Release Security

Production release requires explicit permission.

Suggested:

```text
manufacturing.release
```

---

# 105. CNC Release Security

CNC release requires explicit authorization.

Suggested:

```text
cnc.release
```

---

# 106. CNC Download Security

CNC files must only be downloadable by authorized users.

---

# 107. CNC File Integrity

Released CNC files should have integrity metadata such as:

```text
hash
algorithm version
machine profile
source revision
```

---

# 108. Nesting Integrity

Released nesting must reference:

```text
panel set
material
sheet
algorithm version
parameters
```

---

# 109. Manufacturing Revision Security

A production package must identify exact:

```text
design revision
furniture revision
BOM revision
manufacturing revision
nesting revision
CNC revision
```

---

# 110. Prevent Stale Manufacturing Output

If upstream data changes:

```text
BOM
nesting
CNC
production
```

must be marked stale/affected where applicable.

Do not silently continue using outdated outputs.

---

# 111. MES State Security

State transitions must be server-side validated.

---

# 112. MES Operator Scope

Operator access must be restricted by:

```text
tenant
factory
work center
station
role
```

as applicable.

---

# 113. Panel QR Security

QR codes must not contain sensitive secrets.

Prefer opaque identifiers.

Example:

```text
FMOS panel public identifier
```

rather than embedding:

```text
password
database key
internal token
```

---

# 114. QR Replay Protection

Where scan events require uniqueness, prevent accidental/malicious duplicate events.

---

# 115. QR Authorization

Scanning a QR must still perform server-side authorization.

A QR code is not an authorization credential.

---

# 116. Offline Security

Offline shop-floor clients may temporarily cache required operational data.

They must:

```text
encrypt local sensitive storage where practical
minimize cached data
expire stale sessions
protect device-local credentials
```

---

# 117. Offline Event Integrity

Queued events must include sufficient integrity protection and be validated when synchronized.

Never blindly trust client timestamps or status.

---

# 118. Offline Conflict Resolution

Server state is authoritative.

Conflicts must be:

```text
detected
logged
resolved according to defined policy
```

---

# 119. Client Portal Security

Client users must only see resources explicitly shared with them.

They must not receive:

```text
internal cost
margin
manufacturing rules
CNC
factory data
internal notes
```

unless explicitly intended.

---

# 120. Client Approval Security

Approval must record:

```text
client identity
revision
timestamp
decision
comments
```

---

# 121. Approval Integrity

A client must not approve a revision other than the one displayed/authorized.

---

# 122. Quote Security

Client-facing quote must not expose internal:

```text
cost
margin
supplier pricing
internal notes
```

unless explicitly configured.

---

# 123. Pricing Security

Sensitive pricing permissions must be separated.

Example:

```text
pricing.view
pricing.edit
pricing.cost.view
pricing.margin.view
pricing.approve
```

---

# 124. Export Security

Exports are high-risk because they can bypass UI restrictions.

Every export must re-evaluate authorization.

---

# 125. Bulk Export Security

Large exports require:

```text
permission
rate limit
audit
```

and possibly asynchronous processing.

---

# 126. Report Security

Reports must respect all underlying record permissions.

---

# 127. Search Security

Search must not become a side-channel.

A user should not be able to infer unauthorized data from:

```text
result count
autocomplete
error messages
```

---

# 128. Autocomplete Security

Autocomplete suggestions must apply permission filtering.

---

# 129. Notification Security

Notifications must not reveal confidential data to unauthorized recipients.

---

# 130. Email Security

Email content should contain only the minimum required sensitive information.

Use secure links for detailed information.

---

# 131. Email Link Security

Links must:

```text
expire where appropriate
require authentication where necessary
validate tenant context
```

---

# 132. Document Sharing

Shared documents must use:

```text
explicit authorization
expiring access
revocation
audit
```

where appropriate.

---

# 133. Public Links

If public links are supported, they must be:

```text
random
unguessable
scoped
revocable
expiring
```

---

# 134. AI Architecture

AI must be accessed through an FMOS-controlled AI gateway/service.

Do not expose provider credentials to browser JavaScript.

---

# 135. AI Tenant Isolation

Every AI request must include trusted context:

```text
tenant
user
permissions
project
scope
```

---

# 136. AI Retrieval Security

Retrieval systems must filter by:

```text
tenant_id
user permissions
resource scope
```

before context reaches the model.

---

# 137. AI Prompt Injection

Treat project documents, comments, catalogs, uploaded files and retrieved text as untrusted content.

Never allow retrieved content to override system authorization.

---

# 138. AI Tool Authorization

Each AI tool must have its own permission.

Example:

```text
ai.project.read
ai.furniture.suggest
ai.furniture.apply
ai.pricing.analyze
ai.manufacturing.analyze
```

---

# 139. AI High-Risk Actions

AI must not autonomously execute high-impact operations such as:

```text
production release
CNC release
quote approval
payment
scrap
dispatch
role assignment
```

without explicit authorized human workflow.

---

# 140. AI Proposed Changes

AI changes should use:

```text
propose
→ validate
→ preview
→ user approve
→ commit
→ audit
```

---

# 141. AI Data Leakage

Test whether prompts can cause exposure of:

```text
other tenant data
internal system prompts
credentials
hidden catalog data
margin
private notes
```

---

# 142. AI Provider Data Policy

Do not send tenant data to external AI providers unless the configured integration and contract allow it.

---

# 143. AI Retention

Document and control:

```text
prompt retention
response retention
provider retention
training use
```

according to tenant/product policy.

---

# 144. AI Logging

Do not log complete prompts/responses if they contain sensitive customer information unless required and appropriately protected.

---

# 145. AI Abuse

Rate-limit:

```text
prompt volume
large documents
large image processing
expensive generation
```

---

# 146. AI Output Validation

Never treat AI output as trusted application data.

Validate:

```text
schema
types
ranges
permissions
business rules
manufacturing constraints
```

---

# 147. AI Prompt Versioning

Critical AI workflows should track:

```text
prompt version
model/provider
tool version
timestamp
```

without storing unnecessary sensitive content.

---

# 148. Integration Security

External integrations must use:

```text
OAuth
API keys
signed requests
mTLS
```

as appropriate.

---

# 149. Integration Secrets

Store integration credentials in secure server-side storage.

Never expose them to frontend JavaScript.

---

# 150. Integration Scope

Each integration should request minimum permissions.

---

# 151. Integration Failure

External integration failure must not bypass authorization or leave corrupted state.

---

# 152. Webhook Replay

Use:

```text
timestamp
signature
event ID
idempotency
```

to prevent replay.

---

# 153. SSO Security

SSO must validate:

```text
issuer
audience
signature
nonce
state
redirect URI
```

according to the protocol.

---

# 154. SSO Tenant Mapping

Identity must map to the correct tenant.

Do not map solely from an untrusted email domain if stronger identity claims are available.

---

# 155. OAuth Security

Use:

```text
PKCE
state
nonce
short-lived tokens
```

as applicable.

---

# 156. SCIM Security

If user provisioning is supported:

```text
authenticate source
validate signatures/credentials
scope tenant
audit provisioning
```

---

# 157. Admin Security

Platform administrators have elevated access and require:

```text
MFA
strong authentication
audit
least privilege
session controls
```

---

# 158. Tenant Admin Security

Tenant admins should have:

```text
MFA where available
audit
role-management restrictions
```

---

# 159. Support Impersonation

Support impersonation requires:

```text
permission
reason
time limit
visible indicator
audit
exit mechanism
```

---

# 160. Impersonation Restrictions

Support users must not be able to:

```text
silently change credentials
perform irreversible actions
```

unless explicitly authorized.

High-risk actions should require additional controls.

---

# 161. Admin Audit

Admin actions must be more heavily audited than ordinary user activity.

---

# 162. Source Code Security

Do not commit:

```text
credentials
API keys
certificates
private keys
production URLs with secrets
```

---

# 163. Git Security

Use:

```text
secret scanning
branch protection
code review
dependency scanning
```

---

# 164. Pull Request Security Review

Changes involving:

```text
auth
RBAC
tenant scope
database
file uploads
AI
CNC
manufacturing
pricing
```

require security review.

---

# 165. Dependency Management

Pin or constrain dependency versions appropriately.

Review security advisories.

---

# 166. PHP Security

Maintain current supported PHP version.

Disable unnecessary dangerous capabilities.

Use secure configuration.

---

# 167. PHP Configuration

Review:

```text
display_errors
expose_php
file_uploads
upload limits
session settings
```

for production.

Do not display internal errors to users.

---

# 168. JavaScript Security

Avoid:

```text
eval()
new Function()
unsafe dynamic script injection
```

unless there is a compelling, reviewed requirement.

---

# 169. Third-Party JavaScript

Inventory third-party libraries.

Avoid loading unnecessary remote scripts.

---

# 170. Content Security Policy

Use CSP to reduce:

```text
XSS
script injection
unexpected external resources
```

Start with report-only mode if necessary, then enforce.

---

# 171. WebSocket Security

If used:

```text
authenticate
authorize
validate messages
rate limit
tenant scope
```

---

# 172. WebSocket Tenant Isolation

A user subscribed to Tenant A must not receive Tenant B events.

---

# 173. Realtime Event Security

Every realtime event must be filtered according to user scope.

---

# 174. Database Injection via Filters

Search/filter/sort parameters must use allowlisted column names.

Never concatenate arbitrary sort fields into SQL.

---

# 175. Dynamic SQL

Dynamic SQL is allowed only with:

```text
strict allowlists
parameterized values
```

---

# 176. Batch API Security

Batch operations must authorize every item or enforce a common authorized scope.

---

# 177. Bulk Delete

Bulk deletion requires:

```text
explicit permission
scope validation
confirmation
audit
```

---

# 178. Soft Delete

Where historical integrity matters, prefer soft deletion/archive over destructive deletion.

---

# 179. Manufacturing Deletion

Released manufacturing artifacts should not be physically deleted through ordinary application flows.

Use:

```text
cancel
obsolete
superseded
archived
```

states.

---

# 180. Pricing Deletion

Historical quote/pricing records should remain traceable.

---

# 181. Catalog Deletion

Catalog items referenced by historical records must be archived rather than removed.

---

# 182. Revision Security

Revision numbers must be generated server-side.

Do not trust client-supplied revision numbers.

---

# 183. Approval Security

Approval state transitions must be server-controlled.

---

# 184. State Machine Security

Every workflow state transition must validate:

```text
current state
requested state
actor permission
business rules
```

---

# 185. Production Release State Machine

Example:

```text
Draft
→ Validated
→ Approved
→ Released
→ In Production
→ Completed
→ Closed
```

Invalid transitions must fail.

---

# 186. CNC State Machine

Example:

```text
Draft
→ Generated
→ Validated
→ Approved
→ Released
→ Archived
```

---

# 187. Quote State Machine

Example:

```text
Draft
→ Internal Review
→ Sent
→ Viewed
→ Approved
→ Rejected
→ Expired
```

---

# 188. Security Headers on APIs

API responses must be configured appropriately to prevent caching of sensitive content.

---

# 189. Sensitive API Response Caching

Do not allow shared/browser caching of confidential API responses unless explicitly safe.

---

# 190. Browser Storage

Do not store sensitive secrets in:

```text
localStorage
sessionStorage
URL query parameters
```

unless explicitly justified and protected.

---

# 191. Token Storage

Prefer secure HttpOnly cookies for browser sessions where architecture permits.

---

# 192. URL Security

Do not place:

```text
password
API token
session token
private data
```

in URLs.

---

# 193. Referrer Leakage

Avoid sensitive data in URLs because referrers may expose them.

---

# 194. Clickjacking Protection

Use appropriate:

```text
frame-ancestors
```

CSP policy and related controls.

---

# 195. CSRF and Client Portal

Client portal state-changing actions must also have CSRF protection where cookie-based sessions are used.

---

# 196. Session Timeout

Define inactivity and absolute session timeout according to risk.

Manufacturing/admin sessions should have stronger controls than low-risk viewing.

---

# 197. Reauthentication

Require reauthentication or step-up authentication for high-risk actions where appropriate:

```text
role changes
security settings
API credentials
CNC release
financial approval
tenant deletion
```

---

# 198. Tenant Deletion

Tenant deletion must require:

```text
highest authorization
confirmation
backup/export policy
cooling-off period where required
audit
```

---

# 199. Data Retention

Define retention policies for:

```text
projects
documents
audit
MES events
QR history
quotes
financial data
AI data
```

---

# 200. Secure Deletion

When data must be deleted:

```text
database
files
cache
search index
AI retrieval index
backups
```

must follow documented retention/deletion policy.

---

# 201. Search Index Security

Search indexes must carry tenant/security metadata.

---

# 202. AI Vector Store Security

If vector databases are used:

```text
tenant ID
document ID
permission scope
```

must be attached to indexed records and enforced at retrieval.

---

# 203. Vector Deletion

Deleting/revoking a document must remove or invalidate its AI retrieval representation according to retention policy.

---

# 204. Manufacturing File Security

CNC/DXF and machine files are sensitive intellectual property.

Protect them like restricted data.

---

# 205. Intellectual Property

FMOS must protect:

```text
parametric rules
furniture templates
manufacturing rules
machine profiles
CNC postprocessors
nesting algorithms
customer designs
```

---

# 206. Catalog Security

Enterprise product catalogs may be confidential.

Catalog access must be permission controlled.

---

# 207. Hardware Security

Hardware supplier information and pricing may be restricted.

---

# 208. Cost Data

Internal cost and supplier cost must be separated from client-visible pricing.

---

# 209. Margin Data

Margin should require explicit permission.

---

# 210. Client Portal Data Model

Use an explicit client-visible projection rather than simply exposing internal project objects.

---

# 211. API Response Filtering

Backend DTO/serializer must explicitly define fields returned.

Do not serialize complete database models automatically.

---

# 212. Sensitive Field Exclusion

Fields such as:

```text
cost
margin
internal_notes
supplier_price
security metadata
```

must not be exposed unless explicitly authorized.

---

# 213. Graph/Relationship Access

If APIs support nested objects, every nested object must be authorized.

Example:

```text
Project
→ Furniture
→ Material
→ Supplier
```

must not leak supplier information.

---

# 214. N+1 Security

Optimizing queries must not accidentally bypass tenant filters.

---

# 215. ORM Security

If ORM is used:

```text
global tenant scope
explicit authorization
safe eager loading
```

must be maintained.

---

# 216. Database Connection Security

Use:

```text
TLS
least privilege
secret storage
connection limits
```

where supported.

---

# 217. Database Credentials Rotation

Production DB credentials should be rotatable.

---

# 218. Admin Database Access

Direct database access must be tightly controlled and audited.

---

# 219. Production SSH

Production server access should use:

```text
key-based authentication
MFA/bastion where possible
least privilege
audit
```

Disable password SSH where operationally appropriate.

---

# 220. Server Hardening

Production hosts should follow hardened baseline:

```text
minimal services
firewall
patching
least privilege
secure SSH
monitoring
log management
```

---

# 221. Container Security

If containers are used:

```text
minimal images
non-root
dependency scanning
read-only filesystem where practical
resource limits
```

---

# 222. Web Server Security

Configure:

```text
TLS
security headers
request limits
access logs
error logs
```

---

# 223. PHP-FPM Security

Apply:

```text
resource limits
process limits
secure pool configuration
```

---

# 224. Upload Processing Isolation

CPU/memory-intensive parsers should ideally run in isolated worker processes/containers.

---

# 225. CNC Conversion Isolation

External conversion tools must run with:

```text
restricted permissions
timeouts
resource limits
no unnecessary network access
```

---

# 226. AI Processing Isolation

AI workers should have only the data and network access required.

---

# 227. Worker Security

Workers must not run with unrestricted administrator privileges.

---

# 228. Scheduled Jobs

Scheduled jobs must:

```text
authenticate internally
use service identity
have least privilege
log execution
```

---

# 229. Cron Security

Do not place secrets directly in cron command lines.

---

# 230. Monitoring & Alerting

Alert on:

```text
repeated failed login
authorization failures
cross-tenant access attempts
role changes
mass export
large downloads
API abuse
CNC release
unusual AI usage
admin impersonation
```

---

# 231. Security Event Monitoring

Security-relevant logs should be centrally collected where infrastructure permits.

---

# 232. Alert Severity

Suggested:

```text
Critical
High
Medium
Low
```

---

# 233. Incident Response

Define:

```text
detect
contain
investigate
eradicate
recover
review
```

---

# 234. Security Incident Categories

Examples:

```text
account compromise
tenant data exposure
credential leakage
malware
API abuse
data corruption
manufacturing IP theft
AI data leakage
```

---

# 235. Incident Evidence

Preserve:

```text
logs
audit events
request IDs
timestamps
affected resources
```

while respecting privacy/retention requirements.

---

# 236. Security Breach Response

A breach affecting tenant data must trigger documented:

```text
containment
impact assessment
tenant notification decision
legal/compliance assessment
credential rotation
recovery
postmortem
```

---

# 237. Vulnerability Management

Track vulnerabilities with:

```text
CVE
severity
affected component
version
remediation
owner
deadline
```

---

# 238. Patch Management

Prioritize:

```text
Critical
High
Medium
Low
```

based on exploitability and exposure.

---

# 239. Dependency Vulnerability Policy

Critical/high vulnerabilities in internet-facing dependencies require explicit review before release.

---

# 240. Security Testing Types

Required:

```text
SAST
DAST
dependency scanning
secret scanning
API security testing
penetration testing
configuration scanning
container scanning if applicable
```

---

# 241. SAST

Scan:

```text
PHP
JavaScript
SQL
configuration
```

---

# 242. DAST

Run against staging.

Test:

```text
authentication
authorization
injection
session
file upload
API
```

---

# 243. Dependency Scanning

Scan:

```text
composer
npm
server packages
container packages
```

---

# 244. Secret Scanning

Run in:

```text
pre-commit
CI
repository history where possible
```

---

# 245. Security Test Cases

Minimum permanent security tests:

```text
SEC-T001 Cross-tenant project access
SEC-T002 Cross-tenant update
SEC-T003 Cross-tenant delete
SEC-T004 Cross-tenant search
SEC-T005 Cross-tenant file
SEC-T006 Cross-tenant AI retrieval
SEC-T007 Role escalation
SEC-T008 Permission bypass
SEC-T009 IDOR
SEC-T010 SQL injection
SEC-T011 XSS
SEC-T012 CSRF
SEC-T013 SSRF
SEC-T014 Path traversal
SEC-T015 Malicious upload
SEC-T016 Session fixation
SEC-T017 Token replay
SEC-T018 API abuse
SEC-T019 Audit tampering
SEC-T020 CNC unauthorized release
SEC-T021 Manufacturing unauthorized release
SEC-T022 Pricing/margin leakage
SEC-T023 Client portal leakage
SEC-T024 AI prompt injection
SEC-T025 AI tool escalation
```

---

# 246. Tenant Isolation Automated Test Pattern

For every critical resource:

```text
Create Tenant A
Create Tenant B

Create resource under A
Authenticate A
Verify A can access resource

Authenticate B
Verify B cannot access resource

Attempt:
GET
POST
PUT/PATCH
DELETE
SEARCH
EXPORT
FILE
AI
```

---

# 247. RBAC Automated Test Pattern

For every sensitive permission:

```text
Role without permission
→ attempt action
→ deny

Role with permission
→ attempt action
→ allow

Same permission but wrong scope
→ deny
```

---

# 248. Manufacturing Security Test Pattern

```text
Designer
→ cannot release production

Production Planner
→ can release if authorized

Operator
→ cannot release

CNC Operator
→ can execute only authorized CNC workflow

Unauthorized user
→ cannot download CNC files
```

---

# 249. Client Security Test Pattern

Client should only access:

```text
shared project
shared revision
shared quote
shared documents
```

Client must not access:

```text
internal BOM
supplier cost
margin
factory
CNC
MES
internal notes
```

unless explicitly configured.

---

# 250. AI Security Test Pattern

```text
User
→ asks AI for unauthorized project
→ denied

User
→ asks AI to reveal system prompt
→ protected

User
→ uploads prompt injection
→ ignored as instruction

AI
→ proposes privileged operation
→ authorization blocks tool/action
```

---

# 251. File Security Test Pattern

Upload:

```text
valid image
malicious extension
fake MIME
oversized file
path traversal filename
script disguised as image
malformed PDF
malformed CAD
```

Expected:

```text
safe validation/rejection
```

---

# 252. API Security Test Pattern

For each sensitive endpoint:

```text
No auth
Invalid auth
Expired auth
Wrong tenant
Wrong role
Wrong scope
Malformed request
Oversized request
Valid request
```

---

# 253. Security Acceptance Criteria

A release is not security-complete unless:

```text
[ ] Authentication secure
[ ] Session secure
[ ] MFA/SSO validated where enabled
[ ] RBAC enforced server-side
[ ] Tenant isolation automated
[ ] Object-level authorization tested
[ ] File security tested
[ ] SQL injection tested
[ ] XSS tested
[ ] CSRF tested
[ ] SSRF tested where relevant
[ ] IDOR tested
[ ] Secrets scanned
[ ] Dependencies scanned
[ ] SAST passed
[ ] DAST completed for release
[ ] Audit logging verified
[ ] Security headers verified
[ ] HTTPS enforced
[ ] Rate limiting verified
[ ] API limits verified
[ ] AI isolation verified
[ ] Manufacturing release protected
[ ] CNC release protected
[ ] Client portal isolated
[ ] Backup security verified
[ ] Incident process documented
```

---

# 254. Security Severity

## Critical

Examples:

```text
authentication bypass
tenant data leak
remote code execution
CNC/manufacturing integrity compromise
credential theft
mass data exposure
```

Release blocker.

## High

Examples:

```text
privilege escalation
stored XSS
major authorization bypass
sensitive file exposure
```

Normally release blocker.

## Medium

Examples:

```text
limited information disclosure
weak rate limit on low-risk endpoint
```

Risk assessed.

## Low

Minor hardening issue.

---

# 255. Security Release Gate

NO-GO if any of the following exist without explicit risk acceptance:

```text
cross-tenant data exposure
authentication bypass
RBAC bypass
critical IDOR
production CNC unauthorized access
critical secrets exposure
critical SQL injection
critical file upload vulnerability
critical AI data leakage
critical audit integrity failure
```

---

# 256. Secure Coding Rules for Cursor

Cursor MUST:

1. Never hard-code secrets.
2. Never trust tenant IDs from clients.
3. Never trust role/permission claims from clients.
4. Never rely on hidden UI buttons for security.
5. Use prepared database statements.
6. Validate all inputs server-side.
7. Escape output contextually.
8. Centralize authorization.
9. Centralize tenant scoping.
10. Avoid exposing database models directly through APIs.
11. Avoid logging secrets.
12. Avoid storing tokens in unsafe browser storage.
13. Protect state-changing requests against CSRF where applicable.
14. Validate uploaded files.
15. Apply rate limits to expensive endpoints.
16. Use secure random identifiers/tokens.
17. Audit critical operations.
18. Preserve revision integrity.
19. Validate AI output before committing it.
20. Re-authorize AI tool calls.
21. Treat QR codes as identifiers, not credentials.
22. Treat uploaded CAD/document content as untrusted.
23. Do not bypass security for tests or convenience.
24. Do not disable security controls to make development easier.
25. Do not expose production secrets in development.

---

# 257. Cursor Security Review Before Coding

Before implementing any security-sensitive feature, Cursor must identify:

```text
Threats
Trust boundaries
Authentication
Authorization
Tenant scope
Data classification
Input vectors
Output vectors
File handling
External services
Logging
Audit
Failure modes
```

---

# 258. Cursor Security Checklist for Every API

Before declaring an endpoint complete:

```text
[ ] Authentication
[ ] Tenant resolution
[ ] Permission
[ ] Resource ownership/scope
[ ] Input validation
[ ] Rate limit
[ ] CSRF where applicable
[ ] Error handling
[ ] Audit where required
[ ] Sensitive response filtering
[ ] Tests
```

---

# 259. Cursor Security Checklist for Every Database Query

```text
[ ] Tenant scope
[ ] Authorization scope
[ ] Parameterized values
[ ] Safe sorting/filtering
[ ] Correct joins
[ ] No accidental sensitive fields
```

---

# 260. Cursor Security Checklist for Every File Endpoint

```text
[ ] Authentication
[ ] Authorization
[ ] Tenant scope
[ ] File existence
[ ] MIME/type validation
[ ] Safe path
[ ] Download headers
[ ] Audit if sensitive
```

---

# 261. Cursor Security Checklist for Every AI Feature

```text
[ ] User authenticated
[ ] Tenant context
[ ] Permission check
[ ] Retrieval filtering
[ ] Prompt injection defense
[ ] Tool authorization
[ ] Output schema validation
[ ] Deterministic business validation
[ ] Sensitive logging avoided
[ ] Audit for high-risk action
```

---

# 262. Cursor Security Checklist for Manufacturing

```text
[ ] Correct project
[ ] Correct revision
[ ] Correct tenant
[ ] Correct factory
[ ] Correct role
[ ] Manufacturing validation
[ ] Release authorization
[ ] Output integrity
[ ] Audit
```

---

# 263. Cursor Security Checklist for QR

```text
[ ] Opaque ID
[ ] Authentication
[ ] Tenant validation
[ ] Panel authorization
[ ] Station validation
[ ] State validation
[ ] Duplicate protection
[ ] Audit
```

---

# 264. Secure Development Lifecycle

FMOS development should follow:

```text
Requirements
 ↓
Threat Modeling
 ↓
Secure Design
 ↓
Implementation
 ↓
Code Review
 ↓
SAST
 ↓
Unit/API Security Tests
 ↓
DAST
 ↓
Penetration Testing
 ↓
Release
 ↓
Monitoring
 ↓
Incident Response
```

---

# 265. Threat Modeling Requirement

Threat modeling must be performed for:

```text
authentication
tenant architecture
file uploads
AI
CNC
MES
client portal
integrations
custom domains
```

---

# 266. Security Architecture Decision Records

Security-sensitive architecture decisions should be documented.

Examples:

```text
ADR-SEC-001 Tenant Isolation
ADR-SEC-002 Session Architecture
ADR-SEC-003 AI Security
ADR-SEC-004 File Storage
ADR-SEC-005 CNC Security
```

---

# 267. Penetration Testing Scope

Before commercial production launch, test:

```text
Web application
API
authentication
authorization
tenant isolation
client portal
file upload
AI endpoints
admin endpoints
```

---

# 268. External Security Assessment

A qualified third-party assessment should be considered before large enterprise deployments.

---

# 269. Security Documentation

Maintain:

```text
Security Architecture
Threat Model
Data Classification
Incident Response
Access Control
Secrets Management
Backup/Recovery
Security Test Results
```

---

# 270. Security Training

Developers and administrators should understand:

```text
OWASP
tenant isolation
RBAC
secure PHP
secure JavaScript
SQL injection
XSS
CSRF
SSRF
file security
AI security
```

---

# 271. Production Security Monitoring

Monitor:

```text
failed authentication
authorization failures
unexpected admin actions
large exports
large downloads
AI spikes
API spikes
file upload anomalies
manufacturing release anomalies
```

---

# 272. Anomaly Detection

Define thresholds for:

```text
login failures
API calls
exports
AI usage
file downloads
CNC downloads
```

---

# 273. Security Dashboard

Platform administrators should have a restricted security dashboard showing:

```text
failed logins
locked accounts
security alerts
recent privileged actions
active sessions
API token activity
tenant security events
```

---

# 274. Security Audit Export

Authorized security administrators may export audit records.

Exports must themselves be audited.

---

# 275. Compliance Readiness

FMOS architecture should be capable of supporting common enterprise security questionnaires and controls.

Potential future alignment:

```text
OWASP ASVS
OWASP Top 10
SOC 2
ISO 27001
GDPR where applicable
DPDP Act where applicable
```

Actual certification/compliance must be assessed separately.

---

# 276. Privacy

Privacy requirements must distinguish:

```text
customer data
employee data
client data
factory data
analytics
AI data
```

---

# 277. Data Minimization

Collect only data required for the intended business purpose.

---

# 278. Privacy by Design

New features handling personal information must consider:

```text
purpose
access
retention
deletion
export
audit
```

---

# 279. Personal Data Export

Where required, authorized processes should support export of user/customer data.

---

# 280. Personal Data Deletion

Where legally required and operationally possible, support controlled deletion/anonymization without destroying legally required audit/manufacturing records.

---

# 281. Privacy in AI

Do not send unnecessary personal information to AI services.

---

# 282. Security of Client Data

Client data must remain separated from:

```text
internal manufacturing data
supplier information
internal costing
other clients
other tenants
```

---

# 283. Security of Design IP

Customer CAD/BIM/furniture data is valuable intellectual property.

Protect against:

```text
unauthorized export
unauthorized sharing
cross-tenant leakage
public URLs
AI leakage
```

---

# 284. Security of Manufacturing IP

Protect:

```text
cutting rules
nesting algorithms
CNC postprocessors
machine profiles
hardware rules
parametric templates
```

---

# 285. Security of Supplier Data

Supplier catalogs and prices should have controlled visibility.

---

# 286. Security of Business Data

Revenue, pricing, margin and commercial pipeline should be role-protected.

---

# 287. Security of Factory Data

Production metrics and factory performance should be factory-scoped where appropriate.

---

# 288. Security of Machine Data

Machine status and machine configurations should be accessible only to authorized factory roles.

---

# 289. Security of QR Data

Public QR scanning must not automatically expose confidential production information.

---

# 290. Secure QR Display

A QR label may contain only the minimum information required for physical identification.

---

# 291. Security of Offline Devices

Recommended:

```text
device lock
short session lifetime
encrypted local storage
minimal cached data
remote revocation
```

where supported.

---

# 292. Remote Session Revocation

Tenant admins/platform admins should be able to revoke sessions after suspected compromise.

---

# 293. Credential Compromise

If credentials are suspected compromised:

```text
revoke sessions
rotate tokens
reset password
invalidate API credentials
review audit
```

---

# 294. Security Incident Playbook

Create playbooks for:

```text
tenant breach
admin compromise
API token leak
database compromise
AI data leak
CNC IP leak
malware upload
```

---

# 295. Post-Incident Review

Every material security incident should produce:

```text
root cause
impact
timeline
containment
fix
test added
preventive action
```

---

# 296. Security Regression

Every fixed vulnerability must receive a permanent regression test where feasible.

---

# 297. Security Acceptance Criteria

The security specification is considered implemented only when:

```text
[ ] Authentication implemented securely
[ ] Session management implemented
[ ] RBAC centralized
[ ] Resource authorization centralized
[ ] Tenant isolation centralized
[ ] API authorization enforced
[ ] File authorization enforced
[ ] Search isolation enforced
[ ] Export isolation enforced
[ ] AI retrieval isolation enforced
[ ] AI tools independently authorized
[ ] CNC release secured
[ ] Manufacturing release secured
[ ] MES transitions secured
[ ] QR scanning secured
[ ] Client portal isolated
[ ] Pricing/margin protected
[ ] Audit logging operational
[ ] Security headers configured
[ ] HTTPS configured
[ ] Secrets protected
[ ] Dependency scanning configured
[ ] SAST configured
[ ] DAST configured
[ ] Security regression suite implemented
[ ] Backup security verified
[ ] Incident response documented
[ ] Security monitoring configured
```

---

# 298. Master Security Test Matrix

| Security Area | Required | Automated | Manual/External |
|---|---:|---:|---:|
| Authentication | P0 | Yes | Yes |
| Session | P0 | Yes | Yes |
| MFA/SSO | P0 if enabled | Yes | Yes |
| RBAC | P0 | Yes | Yes |
| Tenant Isolation | P0 | Yes | Yes |
| IDOR | P0 | Yes | Yes |
| API Security | P0 | Yes | Yes |
| SQL Injection | P0 | Yes | Yes |
| XSS | P0 | Yes | Yes |
| CSRF | P0 | Yes | Yes |
| File Upload | P0 | Yes | Yes |
| SSRF | P1 if applicable | Yes | Yes |
| Secrets | P0 | Yes | Yes |
| Audit | P0 | Yes | Yes |
| AI Security | P0 | Yes | Yes |
| CNC Security | P0 | Yes | SME |
| MES Security | P0 | Yes | SME |
| QR Security | P0 | Yes | Yes |
| Client Portal | P0 | Yes | Yes |
| Performance Abuse | P1 | Yes | Yes |
| Penetration Test | P0 before production | No | External |
| DR | P1 | Partial | Yes |

---

# 299. Security Definition of Done

A security-sensitive feature is Done only when:

```text
[ ] Threat model considered
[ ] Authentication requirement implemented
[ ] Authorization implemented
[ ] Tenant scope implemented
[ ] Input validation implemented
[ ] Output filtering implemented
[ ] Audit requirement implemented
[ ] Sensitive data handling reviewed
[ ] Security tests implemented
[ ] Negative tests implemented
[ ] Dependency/security scans pass
[ ] Logging reviewed for secrets
[ ] Error messages reviewed
[ ] Documentation updated
```

---

# 300. Final FMOS Security Principle

The most important security requirement for FMOS is:

> **No user, tenant, client, AI agent, API consumer, background job, QR code, file, or integration should ever be able to bypass the authorization, tenant isolation, revision integrity or business-state controls of the platform.**

FMOS must protect the complete chain:

```text
IDENTITY
 ↓
TENANT
 ↓
USER
 ↓
ROLE
 ↓
PERMISSION
 ↓
PROJECT
 ↓
DESIGN
 ↓
FURNITURE
 ↓
BOM
 ↓
PRICING
 ↓
APPROVAL
 ↓
MANUFACTURING
 ↓
NESTING
 ↓
CNC
 ↓
MES
 ↓
PANEL
 ↓
QR
 ↓
QUALITY
 ↓
PACKING
 ↓
DISPATCH
```

Security must follow the object through the entire lifecycle.

A security implementation is not complete merely because login works. It is complete when FMOS can demonstrate that:

```text
The right person
+
from the right tenant
+
with the right role
+
within the right scope
+
can perform the right operation
+
on the right revision
+
using the right data
+
and every critical action is traceable.
```

---

# 301. Cursor Master Security Instruction

When implementing FMOS, Cursor MUST treat this document as a mandatory engineering specification.

Before coding any security-sensitive feature:

```text
1. Inspect existing authentication.
2. Inspect existing RBAC.
3. Inspect tenant resolution.
4. Inspect repository/data access.
5. Inspect API middleware.
6. Inspect session handling.
7. Inspect file storage.
8. Inspect logging.
9. Inspect secrets/configuration.
10. Inspect existing security tests.
11. Identify security gaps.
12. Implement centralized controls.
13. Add positive tests.
14. Add negative/security tests.
15. Run regression.
16. Document the security behavior.
```

Cursor MUST NOT:

```text
disable authorization to make development easier
hard-code tenant IDs
trust client-side permissions
expose secrets
bypass validation
disable CSRF
use unsafe SQL
expose internal models directly
allow AI to bypass authorization
treat QR as authentication
silently mutate released revisions
```

For every new endpoint:

```text
Authentication
→ Tenant Scope
→ Permission
→ Resource Scope
→ Business State
→ Input Validation
→ Operation
→ Audit
```

For every new file:

```text
Authentication
→ Authorization
→ Tenant Scope
→ File Validation
→ Safe Storage
→ Secure Retrieval
→ Audit
```

For every new AI capability:

```text
Authentication
→ Tenant Scope
→ Retrieval Authorization
→ Prompt Injection Defense
→ AI Processing
→ Output Validation
→ Tool Authorization
→ User Approval
→ Commit
→ Audit
```

For every manufacturing release:

```text
Authentication
→ Tenant
→ Factory
→ Role
→ Revision
→ Manufacturing Validation
→ Release Permission
→ Output Integrity
→ Audit
```

**No feature should be declared production-ready until the applicable security controls and security regression tests defined in this document are implemented and passing.**
