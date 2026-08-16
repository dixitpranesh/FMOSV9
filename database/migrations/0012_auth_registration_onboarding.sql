-- Phase: Auth registration, email verification, org India profile, password reset tokens

ALTER TABLE users
  ADD COLUMN first_name VARCHAR(128) NULL AFTER name,
  ADD COLUMN last_name VARCHAR(128) NULL AFTER first_name,
  ADD COLUMN display_name VARCHAR(255) NULL AFTER last_name,
  ADD COLUMN mobile_country_code VARCHAR(8) NULL AFTER display_name,
  ADD COLUMN mobile VARCHAR(32) NULL AFTER mobile_country_code,
  ADD COLUMN designation VARCHAR(128) NULL AFTER mobile,
  ADD COLUMN registration_type VARCHAR(64) NULL AFTER designation,
  ADD COLUMN email_verified_at DATETIME NULL AFTER registration_type,
  ADD COLUMN terms_accepted_at DATETIME NULL AFTER email_verified_at,
  ADD COLUMN terms_version VARCHAR(32) NULL AFTER terms_accepted_at,
  ADD COLUMN privacy_acknowledged_at DATETIME NULL AFTER terms_version,
  ADD COLUMN privacy_version VARCHAR(32) NULL AFTER privacy_acknowledged_at,
  ADD COLUMN marketing_email_consent TINYINT(1) NOT NULL DEFAULT 0 AFTER privacy_version,
  ADD COLUMN failed_login_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER marketing_email_consent,
  ADD COLUMN locked_until DATETIME NULL AFTER failed_login_count;

-- Existing accounts remain usable (verified + ACTIVE).
UPDATE users
SET email_verified_at = COALESCE(email_verified_at, created_at, NOW()),
    status = CASE WHEN status IS NULL OR status = '' THEN 'ACTIVE' ELSE status END
WHERE deleted_at IS NULL;

ALTER TABLE organizations
  ADD COLUMN owner_user_id BIGINT UNSIGNED NULL AFTER tenant_id,
  ADD COLUMN legal_name VARCHAR(255) NULL AFTER name,
  ADD COLUMN trade_name VARCHAR(255) NULL AFTER legal_name,
  ADD COLUMN constitution VARCHAR(64) NULL AFTER trade_name,
  ADD COLUMN pan VARCHAR(16) NULL AFTER constitution,
  ADD COLUMN gst_registered VARCHAR(16) NULL AFTER pan,
  ADD COLUMN gstin VARCHAR(16) NULL AFTER gst_registered,
  ADD COLUMN gst_verification_status VARCHAR(32) NOT NULL DEFAULT 'NOT_CHECKED' AFTER gstin,
  ADD COLUMN business_email VARCHAR(255) NULL AFTER gst_verification_status,
  ADD COLUMN business_phone VARCHAR(32) NULL AFTER business_email,
  ADD COLUMN website VARCHAR(512) NULL AFTER business_phone,
  ADD COLUMN year_established SMALLINT UNSIGNED NULL AFTER website,
  ADD COLUMN manufacturing_locations_count INT UNSIGNED NULL AFTER year_established,
  ADD COLUMN profile_json JSON NULL AFTER manufacturing_locations_count;

ALTER TABLE organizations
  ADD CONSTRAINT fk_org_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE organization_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id BIGINT UNSIGNED NOT NULL,
  address_type VARCHAR(32) NOT NULL DEFAULT 'PRINCIPAL',
  is_principal TINYINT(1) NOT NULL DEFAULT 0,
  line1 VARCHAR(255) NOT NULL,
  line2 VARCHAR(255) NULL,
  premises VARCHAR(255) NULL,
  locality VARCHAR(255) NULL,
  city VARCHAR(128) NULL,
  district VARCHAR(128) NULL,
  state VARCHAR(128) NOT NULL,
  pin_code VARCHAR(16) NOT NULL,
  country VARCHAR(64) NOT NULL DEFAULT 'IN',
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  INDEX idx_org_addr_org (organization_id),
  CONSTRAINT fk_org_addr_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_verification_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_email_verify_hash (token_hash),
  INDEX idx_email_verify_user (user_id),
  CONSTRAINT fk_email_verify_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_resets was unused; switch to hashed tokens.
ALTER TABLE password_resets
  CHANGE COLUMN token token_hash CHAR(64) NOT NULL,
  ADD COLUMN purpose VARCHAR(32) NOT NULL DEFAULT 'PASSWORD_RESET' AFTER token_hash;

UPDATE app_meta SET meta_value = '12', updated_at = NOW() WHERE meta_key = 'schema_phase';
