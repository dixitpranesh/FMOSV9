# FMOS Authentication & RBAC Security Audit

**Date:** 2026-08-16  

---

## 1. Authentication Capabilities (verified)

| Capability | State |
|------------|-------|
| Register (3 personas) | Exists — `RegistrationService` |
| Login / logout / me | Exists |
| Email verification | Exists — hashed tokens, TTL, single-use |
| Forgot / reset password | Exists — hashed tokens; revokes all sessions |
| Password hashing | `password_hash` / `password_verify` (`PASSWORD_DEFAULT`) |
| MFA | **Absent** |
| SSO / OIDC / SAML | Absent |
| Remember-me | Absent (session lifetime from env) |

---

## 2. Session Security

| Control | Implementation | Assessment |
|---------|----------------|------------|
| HttpOnly | Yes | Good |
| SameSite | Lax | Acceptable for SPA |
| Secure | HTTPS or `APP_ENV=production` | OK; local HTTP insecure by design |
| Regenerate on login | `session_regenerate_id(true)` | Good |
| API token at rest | SHA-256 in `sessions` | Good |
| API token in browser | `localStorage` plaintext | **High risk** with XSS |
| Absolute / idle timeout | Session row `expires_at` + PHP lifetime | Basic |
| Concurrent sessions | Allowed (multiple rows) | No device inventory UI |
| Logout | Deletes current session row | Good |
| Password reset | `revokeAllSessions` | Good |
| Status change | No auto-revoke | **SEC-006** |
| CSRF issued | Yes | Partial enforcement |
| CSRF validated | Cookie-only mutating; Bearer exempt | **SEC-004** |

---

## 3. Registration / Verification / Reset

| Check | Result |
|-------|--------|
| Email normalize / uniqueness | Yes; generic duplicate response |
| Password policy | Weak (length + small denylist) — SEC-010 |
| Token entropy | 32 bytes hex | Good |
| Token storage | SHA-256 | Good |
| Verify TTL | 24h default | Good |
| Reset TTL | 60m default | Good |
| Single-use | `used_at` | Good |
| Rate limits | Register/resend/forgot/reset/login IP | Present; races SEC-012 |
| Anti-enumeration | Mostly yes | Debug token / user_id on success path weakens |
| GET verify | Token in query | SEC-013 |

---

## 4. Login Hardening

| Control | State |
|---------|-------|
| IP rate limit | 30 / 5 min |
| Account soft lock | 5 failures → 15 min `locked_until` |
| Timing attack mitigation | Not constant-time on user existence (generic messages help) |
| Audit | Login success/fail events present |

---

## 5. RBAC

### Model

- Tables: `roles`, `permissions`, `role_permissions`, `user_roles`  
- `Auth::can` / `requirePermission`  
- `PLATFORM_SUPER_ADMIN` → `*`  
- Registration personas ≠ RBAC roles (`registration_type` separate)

### Strengths

Most domain routes call `requirePermission(...)`.

### Weaknesses

| Issue | Detail |
|-------|--------|
| Not router-declarative | Easy to forget on new routes |
| Open seed endpoint | SEC-002 |
| Impersonation permission | Seeded, unimplemented — SEC-026 |
| No resource-level ACL | Org-wide permissions only |
| Vertical escalation tests | Limited automated coverage |

---

## 6. Privilege Escalation Summary

| Type | Finding |
|------|---------|
| Vertical | Open RBAC seed; platform bootstrap |
| Horizontal (same tenant) | XSS + token theft; any user with furniture.update can hit IDs they know if tenant-scoped (expected) |
| Cross-tenant | FK pollution SEC-003; not classic GET IDOR |

---

## 7. Recommended Auth Hardening Sequence

1. Kill public `/tenants` and `/rbac/seed`.  
2. Revalidate status on every `Auth::user()`.  
3. Move browser auth to HttpOnly cookie **or** short-lived Bearer + refresh.  
4. XSS escape + CSP.  
5. Strengthen password policy.  
6. MFA for owners/platform (P3).  
7. Session inventory + revoke-other-sessions UI.
