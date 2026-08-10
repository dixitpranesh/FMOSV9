-- CR-001 Phase 1: Canonical furniture domain + materials + mfg rules/sheets

-- Projects: dual mode (existing with furniture+room → LEGACY; new → FURNITURE_FIRST)
ALTER TABLE projects
  ADD COLUMN model_mode VARCHAR(32) NOT NULL DEFAULT 'FURNITURE_FIRST' AFTER workflow_stage;

UPDATE projects p
SET model_mode = 'LEGACY'
WHERE EXISTS (
  SELECT 1 FROM furniture_instances fi
  WHERE fi.project_id = p.id AND fi.room_id IS NOT NULL AND fi.deleted_at IS NULL
);

-- Furniture instances: nullable room + structured fields
ALTER TABLE furniture_instances DROP FOREIGN KEY fk_fi_room;

ALTER TABLE furniture_instances
  MODIFY room_id BIGINT UNSIGNED NULL,
  ADD COLUMN code VARCHAR(64) NULL AFTER name,
  ADD COLUMN category VARCHAR(64) NULL AFTER code,
  ADD COLUMN type VARCHAR(64) NULL AFTER category,
  ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER type,
  ADD COLUMN width_mm DECIMAL(12,2) NULL AFTER quantity,
  ADD COLUMN height_mm DECIMAL(12,2) NULL AFTER width_mm,
  ADD COLUMN depth_mm DECIMAL(12,2) NULL AFTER height_mm,
  ADD COLUMN exterior_finish_id BIGINT UNSIGNED NULL AFTER material_id,
  ADD COLUMN interior_finish_id BIGINT UNSIGNED NULL AFTER exterior_finish_id,
  ADD COLUMN specification_json JSON NULL AFTER interior_finish_id;

ALTER TABLE furniture_instances
  ADD CONSTRAINT fk_fi_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL;

-- Materials (laminates / finishes) — separate from commercial catalog_products
CREATE TABLE materials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(128) NOT NULL,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(64) NOT NULL DEFAULT 'LAMINATE',
  series_code VARCHAR(32) NULL,
  series_name VARCHAR(64) NULL,
  supplier_code VARCHAR(64) NULL,
  design_index INT NULL,
  colorway_index INT NULL,
  default_roughness DECIMAL(5,3) NOT NULL DEFAULT 0.550,
  default_metalness DECIMAL(5,3) NOT NULL DEFAULT 0.000,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  attributes_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_materials_sku (tenant_id, sku),
  INDEX idx_materials_series (tenant_id, series_code),
  CONSTRAINT fk_materials_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE material_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  material_id BIGINT UNSIGNED NOT NULL,
  asset_type VARCHAR(32) NOT NULL,
  storage_path VARCHAR(512) NOT NULL,
  public_url VARCHAR(512) NOT NULL,
  mime VARCHAR(64) NOT NULL DEFAULT 'image/webp',
  width_px INT NULL,
  height_px INT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_mat_assets_material (material_id),
  CONSTRAINT fk_mat_assets_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  CONSTRAINT fk_mat_assets_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE furniture_instances
  ADD CONSTRAINT fk_fi_exterior_finish FOREIGN KEY (exterior_finish_id) REFERENCES materials(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_fi_interior_finish FOREIGN KEY (interior_finish_id) REFERENCES materials(id) ON DELETE SET NULL;

CREATE TABLE furniture_components (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  furniture_id BIGINT UNSIGNED NOT NULL,
  parent_component_id BIGINT UNSIGNED NULL,
  component_key VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  component_type VARCHAR(64) NOT NULL DEFAULT 'PANEL',
  sort_order INT NOT NULL DEFAULT 0,
  quantity INT NOT NULL DEFAULT 1,
  length_mm DECIMAL(12,2) NOT NULL DEFAULT 0,
  width_mm DECIMAL(12,2) NOT NULL DEFAULT 0,
  thickness_mm DECIMAL(12,2) NOT NULL DEFAULT 0,
  material_id BIGINT UNSIGNED NULL,
  finish_id BIGINT UNSIGNED NULL,
  geometry_json JSON NULL,
  manufacturing_data_json JSON NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_fc_key (furniture_id, component_key),
  INDEX idx_fc_furniture (tenant_id, furniture_id),
  CONSTRAINT fk_fc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  CONSTRAINT fk_fc_furniture FOREIGN KEY (furniture_id) REFERENCES furniture_instances(id) ON DELETE CASCADE,
  CONSTRAINT fk_fc_parent FOREIGN KEY (parent_component_id) REFERENCES furniture_components(id) ON DELETE SET NULL,
  CONSTRAINT fk_fc_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE SET NULL,
  CONSTRAINT fk_fc_finish FOREIGN KEY (finish_id) REFERENCES materials(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tenant_manufacturing_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  rule_key VARCHAR(64) NOT NULL,
  rule_value_json JSON NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_tmr_key (tenant_id, rule_key),
  CONSTRAINT fk_tmr_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_definitions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  length_mm DECIMAL(12,2) NOT NULL,
  width_mm DECIMAL(12,2) NOT NULL,
  thickness_mm DECIMAL(12,2) NOT NULL DEFAULT 18,
  material_category VARCHAR(64) NULL,
  margin_mm DECIMAL(12,2) NOT NULL DEFAULT 10,
  kerf_mm DECIMAL(12,2) NOT NULL DEFAULT 4,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_sheet_code (tenant_id, code),
  CONSTRAINT fk_sheet_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill furniture dims / category from existing JSON + template
UPDATE furniture_instances fi
LEFT JOIN furniture_templates ft ON ft.id = fi.template_id
SET
  fi.width_mm = CAST(JSON_UNQUOTE(JSON_EXTRACT(fi.parameter_values_json, '$.width')) AS DECIMAL(12,2)),
  fi.height_mm = CAST(JSON_UNQUOTE(JSON_EXTRACT(fi.parameter_values_json, '$.height')) AS DECIMAL(12,2)),
  fi.depth_mm = CAST(JSON_UNQUOTE(JSON_EXTRACT(fi.parameter_values_json, '$.depth')) AS DECIMAL(12,2)),
  fi.category = COALESCE(fi.category, ft.category),
  fi.type = COALESCE(fi.type, ft.code),
  fi.code = COALESCE(fi.code, CONCAT(ft.code, '-', fi.id));

-- Seed default manufacturing rules + sheet for every tenant
INSERT INTO tenant_manufacturing_rules (tenant_id, rule_key, rule_value_json, description, is_active, created_at, updated_at)
SELECT t.id, 'cutting_size', JSON_OBJECT(
  'mode', 'sum_opposite_edges',
  'rounding_decimals', 1,
  'fallback_fixed_mm_per_edged_side', 0
), 'Cutting size = finishing - sum of opposite edge thicknesses', 1, NOW(), NOW()
FROM tenants t
WHERE NOT EXISTS (
  SELECT 1 FROM tenant_manufacturing_rules r WHERE r.tenant_id = t.id AND r.rule_key = 'cutting_size'
);

INSERT INTO tenant_manufacturing_rules (tenant_id, rule_key, rule_value_json, description, is_active, created_at, updated_at)
SELECT t.id, 'nesting_defaults', JSON_OBJECT(
  'allow_rotation', true,
  'default_kerf_mm', 4,
  'default_margin_mm', 10
), 'Default nesting behaviour', 1, NOW(), NOW()
FROM tenants t
WHERE NOT EXISTS (
  SELECT 1 FROM tenant_manufacturing_rules r WHERE r.tenant_id = t.id AND r.rule_key = 'nesting_defaults'
);

INSERT INTO sheet_definitions (tenant_id, code, name, length_mm, width_mm, thickness_mm, material_category, margin_mm, kerf_mm, is_default, status, created_at, updated_at)
SELECT t.id, 'SHEET-2440x1220-18', 'Standard 8x4 board 18mm', 2440, 1220, 18, 'BOARD', 10, 4, 1, 'ACTIVE', NOW(), NOW()
FROM tenants t
WHERE NOT EXISTS (
  SELECT 1 FROM sheet_definitions s WHERE s.tenant_id = t.id AND s.code = 'SHEET-2440x1220-18'
);

UPDATE app_meta SET meta_value = '9', updated_at = NOW() WHERE meta_key = 'schema_phase';
