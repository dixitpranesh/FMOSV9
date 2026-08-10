-- CR-001 Phases 7-12 schema extensions

CREATE TABLE manufacturing_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'OPEN',
  validation_json JSON NULL,
  snapshot_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_mfg_jobs_project (tenant_id, project_id),
  CONSTRAINT fk_mfg_jobs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  CONSTRAINT fk_mfg_jobs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE manufacturing_job_furniture (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  manufacturing_job_id BIGINT UNSIGNED NOT NULL,
  furniture_id BIGINT UNSIGNED NOT NULL,
  manufacturing_package_id BIGINT UNSIGNED NULL,
  validation_json JSON NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'PENDING',
  UNIQUE KEY uq_mjf (manufacturing_job_id, furniture_id),
  CONSTRAINT fk_mjf_job FOREIGN KEY (manufacturing_job_id) REFERENCES manufacturing_jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_mjf_furniture FOREIGN KEY (furniture_id) REFERENCES furniture_instances(id) ON DELETE CASCADE,
  CONSTRAINT fk_mjf_pkg FOREIGN KEY (manufacturing_package_id) REFERENCES manufacturing_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE manufacturing_packages
  ADD COLUMN manufacturing_job_id BIGINT UNSIGNED NULL AFTER furniture_id,
  ADD COLUMN bom_revision_id BIGINT UNSIGNED NULL AFTER manufacturing_job_id,
  ADD COLUMN sheet_definition_id BIGINT UNSIGNED NULL AFTER bom_revision_id;

ALTER TABLE panels
  ADD COLUMN component_id BIGINT UNSIGNED NULL AFTER furniture_id,
  ADD COLUMN finishing_length_mm DECIMAL(12,2) NULL AFTER length_mm,
  ADD COLUMN finishing_width_mm DECIMAL(12,2) NULL AFTER finishing_length_mm,
  ADD COLUMN cutting_length_mm DECIMAL(12,2) NULL AFTER finishing_width_mm,
  ADD COLUMN cutting_width_mm DECIMAL(12,2) NULL AFTER cutting_length_mm,
  ADD COLUMN material_id BIGINT UNSIGNED NULL AFTER material_name,
  ADD COLUMN finish_id BIGINT UNSIGNED NULL AFTER material_id,
  ADD COLUMN finish_name VARCHAR(128) NULL AFTER finish_id,
  ADD COLUMN edge_1 DECIMAL(8,2) NULL AFTER edge_json,
  ADD COLUMN edge_2 DECIMAL(8,2) NULL AFTER edge_1,
  ADD COLUMN edge_3 DECIMAL(8,2) NULL AFTER edge_2,
  ADD COLUMN edge_4 DECIMAL(8,2) NULL AFTER edge_3,
  ADD COLUMN note VARCHAR(255) NULL AFTER edge_4;

ALTER TABLE cutlist_items
  ADD COLUMN finishing_length_mm DECIMAL(12,2) NULL AFTER length_mm,
  ADD COLUMN finishing_width_mm DECIMAL(12,2) NULL AFTER finishing_length_mm,
  ADD COLUMN cutting_length_mm DECIMAL(12,2) NULL AFTER finishing_width_mm,
  ADD COLUMN cutting_width_mm DECIMAL(12,2) NULL AFTER cutting_length_mm,
  ADD COLUMN colour VARCHAR(128) NULL AFTER material_name,
  ADD COLUMN edgeband_color VARCHAR(128) NULL AFTER colour,
  ADD COLUMN edge_1 DECIMAL(8,2) NULL AFTER edgeband_color,
  ADD COLUMN edge_2 DECIMAL(8,2) NULL AFTER edge_1,
  ADD COLUMN edge_3 DECIMAL(8,2) NULL AFTER edge_2,
  ADD COLUMN edge_4 DECIMAL(8,2) NULL AFTER edge_3,
  ADD COLUMN note VARCHAR(255) NULL AFTER edge_4,
  ADD COLUMN rotate_flag TINYINT(1) NOT NULL DEFAULT 0 AFTER note;

ALTER TABLE nesting_jobs
  ADD COLUMN sheet_definition_id BIGINT UNSIGNED NULL AFTER manufacturing_package_id,
  ADD COLUMN locked_placements_json JSON NULL AFTER layout_json;

UPDATE panels
SET finishing_length_mm = length_mm,
    finishing_width_mm = width_mm,
    cutting_length_mm = length_mm,
    cutting_width_mm = width_mm
WHERE finishing_length_mm IS NULL;

UPDATE cutlist_items
SET finishing_length_mm = length_mm,
    finishing_width_mm = width_mm,
    cutting_length_mm = length_mm,
    cutting_width_mm = width_mm
WHERE finishing_length_mm IS NULL;

-- Default edge band thickness rule for cutting-size mode sum_opposite_edges
INSERT INTO tenant_manufacturing_rules (tenant_id, rule_key, rule_value_json, description, is_active, created_at, updated_at)
SELECT t.id, 'default_edges', JSON_OBJECT(
  'edge_1', 0.8,
  'edge_2', 0.8,
  'edge_3', 0.8,
  'edge_4', 0.8,
  'apply_to_thickness_gte_mm', 12
), 'Default 0.8mm edge banding on four sides for carcass panels', 1, NOW(), NOW()
FROM tenants t
WHERE NOT EXISTS (
  SELECT 1 FROM tenant_manufacturing_rules r WHERE r.tenant_id = t.id AND r.rule_key = 'default_edges'
);

UPDATE app_meta SET meta_value = '10', updated_at = NOW() WHERE meta_key = 'schema_phase';
