-- Phase 5: Catalog

CREATE TABLE catalog_products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(32) NOT NULL,
  publish_status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  availability_status VARCHAR(32) NOT NULL DEFAULT 'INACTIVE',
  brand VARCHAR(128) NULL,
  thickness_mm DECIMAL(10,2) NULL,
  length_mm DECIMAL(10,2) NULL,
  width_mm DECIMAL(10,2) NULL,
  cost DECIMAL(14,4) NOT NULL DEFAULT 0,
  selling_price DECIMAL(14,4) NOT NULL DEFAULT 0,
  uom VARCHAR(16) NOT NULL DEFAULT 'SQ_FT',
  attributes_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_catalog_sku (tenant_id, sku),
  CONSTRAINT fk_catalog_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE app_meta SET meta_value = '5', updated_at = NOW() WHERE meta_key = 'schema_phase';
