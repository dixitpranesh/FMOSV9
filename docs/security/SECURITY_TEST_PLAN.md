# FMOS Security Test Plan

**Date:** 2026-08-16  

Existing coverage: `tests/auth_registration.php` (register/verify/reset), domain unit tests, some smoke API. **Missing:** systematic authz, tenant isolation, XSS, abuse tests.

---

## 1. Authentication Tests

| ID | Case | Expected |
|----|------|----------|
| A1 | Valid login demo owner | 200 + token |
| A2 | Invalid password | Fail; no enumeration of existence beyond generic |
| A3 | Unverified user login | 403 `EMAIL_NOT_VERIFIED` |
| A4 | Suspended user login | Denied |
| A5 | Suspended user with existing token | Denied after SEC-006 fix |
| A6 | Brute force | Lock after 5; IP rate limit |
| A7 | Password reset reuse | Second use fails |
| A8 | Expired reset/verify tokens | Fail |
| A9 | Reset revokes sessions | Old Bearer 401 |
| A10 | Session fixation | New session id after login |
| A11 | Logout | Token unusable |
| A12 | Register duplicate email | Generic success, no new user |

---

## 2. Authorization Tests

| ID | Case | Expected |
|----|------|----------|
| Z1 | No auth on protected route | 401 |
| Z2 | Authenticated missing permission | 403 |
| Z3 | Designer cannot approve quote (if denied by role) | 403 |
| Z4 | Role manipulation via body | Ignored / 403 |
| Z5 | Platform user without tenant on project API | 403 tenant required |

---

## 3. Tenant Isolation Tests (mandatory)

For each resource: project, client, org, room, furniture, design object, quotation, manufacturing package, kitchen composition:

| ID | Case | Expected |
|----|------|----------|
| T1 | Tenant A → own resource GET | 200 |
| T2 | Tenant A → Tenant B id GET | 404/403 |
| T3 | Tenant A CREATE with B’s `organization_id` | **Reject** |
| T4 | Tenant A CREATE with B’s `client_id` | **Reject** |
| T5 | Tenant A furniture with B’s `project_id` | **Reject** |
| T6 | Tenant A quotation with B’s `client_id` | **Reject** |
| T7 | Export package of B | **Reject** |

Automate with two seeded tenants in `tests/security_tenant_isolation.php`.

---

## 4. API / Abuse Tests

| ID | Case | Expected |
|----|------|----------|
| P1 | Unauth `POST /tenants` | Denied (after fix) |
| P2 | Unauth `POST /rbac/seed` | Denied |
| P3 | Oversized JSON body | 413/400 |
| P4 | Manufacturing spam | Rate limited |
| P5 | SQL meta chars in name fields | Stored safely; no injection |
| P6 | Mass assignment `tenant_id` in body | Ignored |

---

## 5. XSS / Frontend Tests

| ID | Payload location | Expected |
|----|------------------|----------|
| X1 | Project name `<img onerror=...>` | Escaped text; no script |
| X2 | Client name | Escaped |
| X3 | Catalog SKU | Escaped |
| X4 | API error message HTML | Escaped |
| X5 | `javascript:` URL in image field | Blocked |

---

## 6. File / Export Tests

| ID | Case | Expected |
|----|------|----------|
| F1 | Download export without auth | Denied |
| F2 | Path traversal in any file param | Denied |
| F3 | Direct HTTP to `storage/mail` | 404 (infra) |
| F4 | (Future) upload `.php` / SVG | Rejected |

---

## 7. Business Logic

| ID | Case | Expected |
|----|------|----------|
| B1 | Bypass UI; call manufacturing without permission | 403 |
| B2 | (Future) subscription feature without plan | 402/403 |
| B3 | Verify token replay | Fail |
| B4 | Change `owner_user_id` via API | Not allowed |

---

## 8. Security Automation (recommended)

```text
Developer → secret scan pre-commit
PR → SAST (PHP), dependency scan, unit + tenant isolation tests
CI → auth registration + security suite gate
Staging → DAST smoke on public endpoints
Prod → failed-login / 403-tenant alerts
```

No Docker/CI today — add GitHub Actions (or equivalent) as P3.
