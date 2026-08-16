# FMOS Security Audit — Executive Summary

**Date:** 2026-08-16  
**Scope:** Full repository `NewFMOSPHPV2` (PHP 8.1+ / MySQL / vanilla JS SPA)  
**Mode:** Read-only audit — no production code modified  
**Audience:** Engineering leadership, product, security stakeholders  

---

## Executive Dashboard

```text
Overall Security Rating: 4.0 / 10

Critical: 2
High: 6
Medium: 9
Low: 5
Informational: 7

Confirmed vulnerabilities: 22
Potential / Requires Verification: 5

Cross-tenant vulnerabilities: 3
Authentication vulnerabilities: 6
Authorization vulnerabilities: 4
Data security vulnerabilities: 5
API vulnerabilities: 8
Infrastructure gaps: 6
```

```text
Enterprise SaaS Readiness (evidence-based):

Authentication       ██████░░░░  6/10  (verify/reset exist; no MFA; open bootstrap)
Authorization        █████░░░░░  5/10  (RBAC present; uneven FK ownership checks)
Tenant Isolation     ████░░░░░░  4/10  (reads mostly scoped; creates trust body FKs)
API Security         █████░░░░░  5/10  (auth+perms; open admin endpoints; CSRF gap)
Data Protection      ████░░░░░░  4/10  (hashed passwords/tokens; PAN/GST plain; XSS)
Audit Logging        █████░░░░░  5/10  (Audit.php used; incomplete coverage/alerts)
DevSecOps            ██░░░░░░░░  2/10  (no CI, no SAST/deps scanning, no Docker)
Infrastructure       ███░░░░░░░  3/10  (no deploy configs; headers missing; debug defaults)
```

**Rating basis:** Strong foundations (parameterized SQL, password hashing, hashed session/verify/reset tokens, permission checks on most domain routes, registration anti-enumeration) are offset by **unauthenticated platform bootstrap endpoints**, **cross-tenant foreign-key acceptance**, **XSS via `innerHTML`**, and **enterprise gaps** (MFA, CI security, subscription enforcement, monitoring).

---

## Overall Security Posture

FMOS is a **shared-database multi-tenant SaaS** (`tenant_id` on primary entities) with a custom PHP router, session + Bearer auth, and seeded RBAC. It is **not yet enterprise-ready for production multi-customer operation**.

Primary reads (projects, furniture, manufacturing packages) generally enforce `WHERE id = ? AND tenant_id = ?`. The highest risks are **open provisioning / RBAC mutation**, **trusting client-supplied organization/client/project/room IDs on create**, and **client-side XSS** that can steal Bearer tokens from `localStorage`.

---

## Top 10 Risks

| Rank | ID | Risk | Severity |
|------|-----|------|----------|
| 1 | SEC-001 | Unauthenticated `POST /api/v1/tenants` creates ACTIVE verified owners | Critical |
| 2 | SEC-002 | Unauthenticated `POST /api/v1/rbac/seed` mutates global RBAC | Critical |
| 3 | SEC-003 | Cross-tenant FK pollution (`organization_id` / `client_id` / `project_id` / `room_id`) | High |
| 4 | SEC-004 | API Bearer token in `localStorage` + CSRF exemption for Bearer | High |
| 5 | SEC-005 | Stored/DOM XSS via widespread `innerHTML` of API fields | High |
| 6 | SEC-006 | Post-login session does not re-check SUSPENDED/LOCKED/DEACTIVATED | High |
| 7 | SEC-007 | `debug_verify_token` + `storage/mail` plaintext tokens (`MAIL_DRIVER=log`) | High |
| 8 | SEC-008 | Missing security headers (CSP, HSTS, frame protection, nosniff) | Medium |
| 9 | SEC-009 | Error / health leakage (`APP_DEBUG`, manufacturing `JOB_FAILED`) | Medium |
| 10 | SEC-015 | No MFA; weak password policy; demo password in login UI | Medium–High (gap) |

---

## Critical Findings (summary)

1. **SEC-001** — Anyone can call `POST /api/v1/tenants` (`auth=false`) and receive a fully usable tenant + owner (ACTIVE, email verified).  
2. **SEC-002** — Anyone can call `POST /api/v1/rbac/seed` and reseed/alter platform role–permission maps.

## High Findings (summary)

- Cross-tenant FK linking on project/furniture/design/quotation create  
- Token theft via XSS + `localStorage` Bearer  
- Suspended users retain access until token expiry/logout  
- Verification/reset secrets exposed in log-mail mode / API debug field  

---

## Immediate Actions (P0 — do before any production exposure)

1. Disable or hard-gate `POST /api/v1/tenants` and `POST /api/v1/rbac/seed` (platform secret or remove from public router).  
2. Add server-side ownership asserts for every body/path FK (`organization_id`, `client_id`, `project_id`, `room_id`).  
3. Stop returning `debug_verify_token` outside explicit local test profiles; protect `storage/mail`.  
4. Force `APP_DEBUG=false` outside local; sanitize health/manufacturing errors.  
5. Remove default demo password from login UI; rotate any shared demo credentials used beyond local.

---

## Enterprise Readiness Verdict

| Area | Verdict |
|------|---------|
| Fit for internal/demo | Conditional (with P0 fixes) |
| Fit for multi-customer SaaS | **Not ready** |
| SOC 2 / ISO 27001 readiness | **Early** — many control gaps |
| DPDP / GDPR readiness | **Partial** — privacy mailbox exists; DSAR/deletion/encryption-at-rest incomplete |

---

## Deliverables Index

| # | Document |
|---|----------|
| 1 | This file — `SECURITY_AUDIT_EXECUTIVE_SUMMARY.md` |
| 2 | `SECURITY_VULNERABILITY_REGISTER.md` |
| 3 | `SECURITY_ARCHITECTURE_REVIEW.md` |
| 4 | `TENANT_ISOLATION_AUDIT.md` |
| 5 | `AUTH_RBAC_SECURITY_AUDIT.md` |
| 6 | `API_SECURITY_AUDIT.md` |
| 7 | `DATA_SECURITY_AUDIT.md` |
| 8 | `SECURITY_REMEDIATION_PLAN.md` |
| 9 | `SECURITY_TEST_PLAN.md` |
| 10 | `ENTERPRISE_SECURITY_READINESS.md` |

**Next step:** Review and approve the remediation plan. **Do not implement fixes until approved.**
