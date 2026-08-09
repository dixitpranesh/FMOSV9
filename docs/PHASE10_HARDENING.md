# Phase 10 — MVP Hardening Notes

## Completed hardening in this phase

- End-to-end automated journey: `php tests/e2e_mvp.php`
- Pricing formula unit checks: `php tests/run.php`
- Secrets via `.env` (not committed); `.env.example` provided
- Tenant isolation enforced in queries via `tenant_id`
- Manufacturing release gated by `manufacturing.release` permission
- Audit events on critical actions
- Feature flags deferred (scaffold via app_meta phase marker)

## Ops checklist

1. `php bin/migrate.php`
2. `php bin/seed.php`
3. `php -S localhost:8080 -t public public/router.php`
4. `php tests/e2e_mvp.php`
5. Backup MySQL `fmos_v9` regularly in production

## Demo credentials

- `owner@demo.fmos` / `Password123!`
- `platform@fmos.local` / `Password123!`
- `support@fmos.local` / `Password123!`
