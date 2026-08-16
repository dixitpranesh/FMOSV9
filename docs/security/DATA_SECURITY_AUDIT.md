# FMOS Data Security Audit

**Date:** 2026-08-16  

---

## 1. Sensitive Data Inventory

| Data | Storage | Encryption at rest | API exposure | Logging | Notes |
|------|---------|--------------------|--------------|---------|-------|
| Passwords | `users.password_hash` | Hash (bcrypt/argon via PASSWORD_DEFAULT) | Never returned | Not logged | Good |
| Session/API tokens | `sessions.token` SHA-256 | Hash | Raw once at login → localStorage | Avoid | Browser storage risk |
| Verify/reset tokens | hashed columns | Hash | Raw in email; debug/API/mail log | Mail JSON | SEC-007 |
| Email | `users.email` | Plain | Yes | Domain-only in some audits | |
| Mobile | users columns | Plain | Via profile fields | | |
| GSTIN / PAN | organizations | **Plain** | Org APIs; mask helpers exist | Must not full-log | SEC-022 |
| Project/client names | DB | Plain | Yes | | XSS sink |
| Design / furniture JSON | DB | Plain | Large JSON APIs | | DoS / integrity |
| Cutlist / nest exports | `storage/exports` + response body | Plain files | Authenticated JSON | | SEC-014 |
| Laminate textures | `public/media/tenants/{id}` | Plain | **Static public URL** | | Guessable paths |
| Mail contents | `storage/mail` | Plain JSON | Disk | Contains secrets | SEC-007 |
| Audit payloads | `audit_logs` | Plain | No list API found | after_json | Avoid secrets |

---

## 2. Cryptography

| Use | Algorithm | Verdict |
|-----|-----------|---------|
| Passwords | `password_hash` DEFAULT | Good |
| API/session tokens | `random_bytes` + SHA-256 store | Good (not for passwords) |
| CSRF | `random_bytes` + `hash_equals` | Good |
| JWT | Not used | N/A |
| Field encryption (PAN/GST) | Not implemented | Gap for enterprise |
| TLS | Assumed at reverse proxy | **Requires infrastructure verification** |

No MD5/SHA1 password hashing found. No custom crypto.

---

## 3. Secrets Management

| Item | Status |
|------|--------|
| `.env` | Gitignored; not in `git ls-files` |
| `.env.example` | Safe placeholders; `APP_DEBUG=true` default is risky guidance |
| Hard-coded demo password | In `bin/seed.php`, docs, **login UI** — SEC-011 |
| CI secrets | No CI |
| Secret scanning | Not present |
| Rotation | Manual / undocumented |

**Do not commit** real SMTP passwords or DB credentials. Rotate if ever pushed historically (not verified deep history in this audit — **Requires Verification** via `git log -p` / secret scanners).

---

## 4. Logging & Audit

**Present:** LOGIN/LOGOUT, registration/verify/reset events, many domain audits via `Audit::record`.

**Gaps:**

- No centralized alerting  
- Authorization denials not consistently audited  
- Cross-tenant assert failures (once added) need audit  
- Large export events should be audited  
- Ensure GSTIN/PAN never in `after_json`

---

## 5. Frontend Data Exposure

- Bearer + CSRF in `localStorage`  
- `innerHTML` XSS  
- Demo credentials in form defaults  

---

## 6. Recommendations

1. Treat `storage/` as non-web; add deny rules in server config.  
2. Signed, short-lived media URLs or auth proxy.  
3. Encrypt PAN/GST at application layer (envelope encryption) before enterprise customers.  
4. Data retention + DSAR/delete workflows for DPDP.  
5. Redact tokens from log-mail or encrypt mail dumps.  
6. SBOM + secret scanning when dependencies appear.
