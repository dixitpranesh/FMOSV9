# FMOS Enterprise Security Readiness

**Date:** 2026-08-16  
**Verdict:** **Not ready** for multi-customer enterprise SaaS production without P0/P1 remediation.

---

## 1. Maturity Matrix

| Capability | Current State | Risk | Enterprise Requirement | Gap | Recommendation |
|------------|---------------|------|------------------------|-----|----------------|
| Authentication | Password + verify + reset | Med | MFA + hardened session | MFA missing; token storage | P1 then P3 MFA |
| MFA | Absent | High for enterprise | Mandatory for privileged | Full | TOTP/WebAuthn |
| RBAC | Seeded roles/perms | Med | Enforced + reviewed | Open seed; non-declarative | Lock seed; route map |
| Tenant Isolation | Shared DB + tenant_id | High | Proven isolation tests | FK create gaps | TenantGuard + tests |
| API Security | Auth+perm majority | Med | Gateway controls | Public bootstrap; no payload limits | P0/P1 |
| Encryption | Password hash; TLS assumed | Med | TLS verify; field crypto | PAN/GST plain; TLS not in repo | Infra + field encrypt |
| Secrets Management | `.env` local | Med | Vault/KMS | No scanner/CI | Add scanning + vault |
| Audit Logging | Partial Audit.php | Med | Immutable, queryable, alerts | No SIEM; incomplete events | Expand + export |
| Rate Limiting | Auth endpoints only | Med | Global + expensive ops | File races; no mfg limits | Redis + quotas |
| Monitoring | App logs | High | Security alerting | None | Metrics + alerts |
| Backup / DR | Not in repo | High | Tested restore | Unknown | Infra runbooks |
| Dependency Security | No Composer pkgs | Low now | SBOM + CVE gate | No CI | Add when deps arrive |
| Secure SDLC | Manual tests | High | PR security gates | No pipeline | CI security suite |
| Vuln Management | Ad hoc | High | Track SLAs | No register process | Use this register |
| Incident Response | revokeAllSessions exists | Med | Playbooks + suspend kill-switch | Status not killing sessions | SEC-006 + runbooks |
| Subscription AuthZ | Absent | High when billed | Server entitlements | None | Build before billing |
| Privacy (DPDP/GDPR) | Mailboxes designated | Med | DSAR/delete/retention | Workflows missing | Privacy program |

---

## 2. SOC 2 Readiness (indicative)

| Trust Service Criteria (sample) | Readiness |
|---------------------------------|-----------|
| CC6 Logical access | Partial — fix P0/P1 first |
| CC7 Monitoring | Weak |
| CC8 Change management | Weak (no CI evidence) |
| CC9 Risk mitigation | Starting (this audit) |

**Not a certification assessment.**

---

## 3. ISO 27001 Readiness

Code controls alone insufficient. Need ISMS policies, asset inventory, supplier management, IR drills. Application gaps above must close first.

---

## 4. GDPR / DPDP Considerations

| Topic | State |
|-------|-------|
| Privacy contact | `privacy@fmos.in` configured in mail map |
| Lawful basis / notices | Templates partial; legal pages not audited in depth |
| DSAR / deletion | No end-to-end workflow found |
| Cross-border | Depends on hosting — infra verification |
| Breach notification | No playbook in repo |

---

## 5. Customer Security Questionnaire — Likely Fail Points Today

- Unauthenticated admin/bootstrap APIs  
- Evidence of tenant isolation tests  
- MFA  
- Security headers / CSP  
- Vulnerability scanning in CI  
- Encryption of tax identifiers  
- Centralized logging/monitoring  

---

## 6. Target State (12–18 months)

1. P0/P1 closed; isolation test suite in CI  
2. MFA + optional SSO  
3. Entitlements enforced server-side  
4. Secrets in manager; no debug defaults in prod images  
5. Security monitoring with on-call alerts  
6. Formal IR playbooks using session revoke + tenant suspend  

---

## 7. Stop Point

This completes the audit and remediation planning deliverables.

**Await approval before modifying application code.**
