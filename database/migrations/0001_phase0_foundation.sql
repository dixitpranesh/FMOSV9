-- Phase 0 foundation marker
CREATE TABLE IF NOT EXISTS app_meta (
    meta_key VARCHAR(100) PRIMARY KEY,
    meta_value VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_meta (meta_key, meta_value, updated_at)
VALUES ('schema_phase', '0', NOW())
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW();
