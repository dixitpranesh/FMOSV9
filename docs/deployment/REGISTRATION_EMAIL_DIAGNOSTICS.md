# Registration & verification email diagnostics

## Browser console vs server logs

**These diagnostics do NOT appear in Chrome/Firefox DevTools Console.**

They are written on the server under `storage/logs/`. Validate with:

1. Browser **Network** tab → `POST /api/v1/auth/register` → Response JSON  
   - `registration_id` (e.g. `reg_20260818_…`)  
   - `email_sent` (`true` / `false`)  
   - Response header `X-Request-Id`
2. cPanel **File Manager** → application root (parent of `public/`) → `storage/logs/`  
   - `app-YYYY-MM-DD.log`  
   - `email-YYYY-MM-DD.log`
3. cPanel **Errors** / PHP `error_log` — look for `[FMOS]` when file writes fail or on ERROR events.

If the user row exists in MySQL but `storage/logs` is empty:

- Confirm deploy includes commit with `Logger::event('REGISTRATION_…')` (not an older build).
- Confirm `storage/` and `storage/logs/` are writable by the PHP user (`chmod 775` / correct owner).
- Search File Manager for `app.log` under the real app root (not only under `public/`).

---

After a user registers and does not receive a verification email, answer from logs alone:

- Did registration start / validate / create the user?
- Was the verification token generated and stored?
- Was the verification URL built with the expected `APP_URL` host?
- Did the mail pipeline run? Which driver (`log` vs `smtp`)?
- If SMTP: connect / auth / accept — where did it fail?

## Architecture (actual)

```text
public/assets/js/ui.js  (renderRegister)
        ↓ POST /api/v1/auth/register
src/Http/Routes/01_identity.php
        ↓
RegistrationService::register()
        ↓ TenantService::createTenantForRegistration()
        ↓ EmailVerificationService::issueAndSend()
        ↓ Mailer::sendTemplate('verify_email')
        ↓ SmtpMailTransport OR LogMailTransport
```

Resend: `POST /api/v1/auth/resend-verification` → `EmailVerificationService::resend()` → same `issueAndSend()`.

Verify: hash link `#verify-email?token=…` → `POST /api/v1/auth/verify-email` → `EmailVerificationService::verify()`.

## Log location

Outside the public web root when Document Root = `…/public`:

```text
{STORAGE_PATH}/logs/app-YYYY-MM-DD.log      # all structured events
{STORAGE_PATH}/logs/email-YYYY-MM-DD.log    # mail / SMTP events
{STORAGE_PATH}/logs/error-YYYY-MM-DD.log    # ERROR + CRITICAL
{STORAGE_PATH}/logs/app.log                 # legacy append (same JSON lines)
```

Default `STORAGE_PATH=storage` → project `storage/logs/`.

HTTP access is denied via `storage/.htaccess` (`Require all denied`).

Retention: dated files older than ~14 days are pruned opportunistically.

## Correlation IDs

| Field | Source |
| --- | --- |
| `request_id` | `Logger::requestId()` — also returned as `X-Request-Id` / API `meta.request_id` |
| `registration_id` | Created in `RegistrationService` (`reg_…`) — also in register JSON response |
| `user_id` | Bound after user create / lookup |

Search either ID across `app-*.log` and `email-*.log`.

## How to debug a missing verification email

On the MilesWeb/cPanel host (SSH or File Manager → path outside `public`):

```bash
# Replace date and IDs
cd /home/fuiyrsce/apps/fmos   # or your real app root
grep -F 'reg_YYYYMMDD_' storage/logs/app-$(date +%F).log
grep -F 'reg_YYYYMMDD_' storage/logs/email-$(date +%F).log
```

Or capture `registration_id` from the register API response / Network tab, then:

```bash
grep -F 'reg_20260818_194231_8F4A' storage/logs/*.log
```

### Expected success chain (MAIL_DRIVER=smtp)

```text
REGISTRATION_STARTED
REGISTRATION_VALIDATION_SUCCESS
USER_CREATION_STARTED
REGISTRATION_TRANSACTION_COMMITTED
USER_CREATED
VERIFICATION_TOKEN_GENERATED
VERIFICATION_TOKEN_STORED
VERIFICATION_URL_GENERATED
VERIFICATION_EMAIL_STARTED
EMAIL_PIPELINE_STARTED
EMAIL_TEMPLATE_GENERATED
EMAIL_SEND_STARTED
SMTP_CONNECTION_STARTED
SMTP_CONNECTION_SUCCESS
SMTP_AUTH_STARTED
SMTP_AUTH_SUCCESS
EMAIL_ACCEPTED_BY_SMTP
EMAIL_SEND_SUCCESS
REGISTRATION_COMPLETED   (email_sent=true)
```

### Common failure patterns

| Last good event | Next failure event | Likely cause |
| --- | --- | --- |
| `EMAIL_SEND_STARTED` | `EMAIL_SENT_TO_LOG_DRIVER` + `mail_driver=log` | Production still on `MAIL_DRIVER=log` — emails only in `storage/mail/` |
| `SMTP_CONNECTION_STARTED` | `SMTP_CONNECTION_FAILED` | Host/port/firewall/`MAIL_HOST` wrong |
| `SMTP_AUTH_STARTED` | `SMTP_AUTH_FAILED` / `EMAIL_SEND_FAILED` `SMTP_AUTHENTICATION_FAILED` | Bad username/password |
| `VERIFICATION_URL_GENERATED` with wrong `app_url_host` | (email may still send) | `APP_URL` not `https://app.fmos.in` — link broken for user |
| `USER_CREATED` then `EMAIL_SEND_FAILED` | `REGISTRATION_COMPLETED` `email_sent=false` | Account exists; user sees resend message |

## Production configuration checklist

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.fmos.in
MAIL_DRIVER=smtp
MAIL_HOST=<provider host>
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=<smtp user>
MAIL_PASSWORD=<smtp password>
MAIL_FROM_ACCOUNTS=accounts@fmos.in
MAIL_FROM_NAME=FMOS
MAIL_REPLY_TO=support@fmos.in
MAIL_FALLBACK_LOG=false
MAIL_EXPOSE_TOKENS=false
```

Confirm SPF/DKIM for `fmos.in` allow the chosen SMTP sender. Logs never print SMTP passwords.

## API response notes

Register `201` includes:

- `message` — generic on success; explicit “account created but email failed” when send fails after user create
- `registration_id` — use for log search
- `email_sent` — `true` / `false` / `null` (duplicate-path may omit)

Never returns verification tokens unless local `MAIL_EXPOSE_TOKENS=true` + `APP_ENV=local` + `APP_DEBUG=true`.

## Related code

| File | Role |
| --- | --- |
| `src/Core/Logger.php` | Structured JSON logging |
| `src/Core/Mailer.php` | Template + send pipeline logs |
| `src/Core/Mail/SmtpMailTransport.php` | SMTP lifecycle + diagnostics |
| `src/Core/Mail/LogMailTransport.php` | Dev/file driver |
| `src/Domains/Identity/RegistrationService.php` | Registration lifecycle |
| `src/Domains/Identity/EmailVerificationService.php` | Token + verify + resend |
| `src/Core/Auth.php` | Login / unverified block logs |
| `src/Http/Routes/01_identity.php` | HTTP entry points |
