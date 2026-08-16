-- Kitchen compositions: group multiple furniture instances into L/straight layouts.
-- Each module remains its own furniture_instance (cutlist/BOM unchanged).

CREATE TABLE kitchen_compositions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  shape VARCHAR(32) NOT NULL DEFAULT 'L',
  height_mm DECIMAL(10,2) NOT NULL DEFAULT 720,
  depth_mm DECIMAL(10,2) NOT NULL DEFAULT 560,
  corner_size_mm DECIMAL(10,2) NOT NULL DEFAULT 900,
  modules_json JSON NOT NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  INDEX idx_kc_project (tenant_id, project_id),
  CONSTRAINT fk_kc_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
