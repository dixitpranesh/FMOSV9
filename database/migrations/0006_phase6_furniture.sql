-- Phase 6: Parametric furniture

CREATE TABLE furniture_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  code VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(64) NOT NULL,
  version INT NOT NULL DEFAULT 1,
  parameters_json JSON NOT NULL,
  rules_json JSON NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'PUBLISHED',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ft_code_ver (tenant_id, code, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE furniture_instances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  template_id BIGINT UNSIGNED NOT NULL,
  template_version INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  parameter_values_json JSON NOT NULL,
  position_json JSON NOT NULL,
  components_json JSON NOT NULL,
  material_id BIGINT UNSIGNED NULL,
  revision INT NOT NULL DEFAULT 1,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  stale_flags_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_fi_template FOREIGN KEY (template_id) REFERENCES furniture_templates(id),
  CONSTRAINT fk_fi_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_fi_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE app_meta SET meta_value = '6', updated_at = NOW() WHERE meta_key = 'schema_phase';
