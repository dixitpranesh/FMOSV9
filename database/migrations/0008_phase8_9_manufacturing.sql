-- Phase 8-9: Manufacturing, cutlist, nesting, panel labels

CREATE TABLE manufacturing_packages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  furniture_id BIGINT UNSIGNED NULL,
  revision_number INT NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  validation_json JSON NULL,
  snapshot_json JSON NULL,
  released_at DATETIME NULL,
  released_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE panels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  manufacturing_package_id BIGINT UNSIGNED NOT NULL,
  furniture_id BIGINT UNSIGNED NULL,
  public_id VARCHAR(64) NOT NULL,
  name VARCHAR(128) NOT NULL,
  material_name VARCHAR(128) NULL,
  thickness_mm DECIMAL(10,2) NOT NULL,
  length_mm DECIMAL(12,2) NOT NULL,
  width_mm DECIMAL(12,2) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  grain_direction VARCHAR(16) NULL,
  edge_json JSON NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'CREATED',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_panel_public (tenant_id, public_id),
  CONSTRAINT fk_panels_pkg FOREIGN KEY (manufacturing_package_id) REFERENCES manufacturing_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cutlist_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  manufacturing_package_id BIGINT UNSIGNED NOT NULL,
  panel_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  length_mm DECIMAL(12,2) NOT NULL,
  width_mm DECIMAL(12,2) NOT NULL,
  thickness_mm DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  material_name VARCHAR(128) NULL,
  CONSTRAINT fk_cut_pkg FOREIGN KEY (manufacturing_package_id) REFERENCES manufacturing_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nesting_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  manufacturing_package_id BIGINT UNSIGNED NOT NULL,
  sheet_length_mm DECIMAL(12,2) NOT NULL,
  sheet_width_mm DECIMAL(12,2) NOT NULL,
  kerf_mm DECIMAL(8,2) NOT NULL DEFAULT 3,
  sheet_count INT NOT NULL DEFAULT 0,
  used_area DECIMAL(14,4) NOT NULL DEFAULT 0,
  waste_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  layout_json JSON NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'COMPLETED',
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_nest_pkg FOREIGN KEY (manufacturing_package_id) REFERENCES manufacturing_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE app_meta SET meta_value = '9', updated_at = NOW() WHERE meta_key = 'schema_phase';
