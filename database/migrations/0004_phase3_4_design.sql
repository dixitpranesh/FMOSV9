-- Phase 3-4: Design objects (2D/3D shared domain model)

CREATE TABLE design_objects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  room_id BIGINT UNSIGNED NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  object_type VARCHAR(32) NOT NULL,
  name VARCHAR(255) NULL,
  geometry_json JSON NOT NULL,
  parameters_json JSON NULL,
  materials_json JSON NULL,
  metadata_json JSON NULL,
  revision INT NOT NULL DEFAULT 1,
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  INDEX idx_design_room (room_id, object_type),
  CONSTRAINT fk_design_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_design_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE app_meta SET meta_value = '4', updated_at = NOW() WHERE meta_key = 'schema_phase';
