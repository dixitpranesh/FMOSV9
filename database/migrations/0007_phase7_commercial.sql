-- Phase 7: BOM / BOQ / Pricing / Quotations

CREATE TABLE bom_headers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  furniture_id BIGINT UNSIGNED NULL,
  bom_number VARCHAR(64) NOT NULL,
  current_revision INT NOT NULL DEFAULT 1,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_bom_number (tenant_id, bom_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bom_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bom_id BIGINT UNSIGNED NOT NULL,
  revision_number INT NOT NULL,
  source_hash VARCHAR(64) NULL,
  catalog_version_label VARCHAR(64) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  released_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_bom_rev (bom_id, revision_number),
  CONSTRAINT fk_bomrev_bom FOREIGN KEY (bom_id) REFERENCES bom_headers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bom_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bom_revision_id BIGINT UNSIGNED NOT NULL,
  item_type VARCHAR(32) NOT NULL,
  catalog_product_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(14,4) NOT NULL,
  uom VARCHAR(16) NOT NULL,
  unit_cost DECIMAL(14,4) NOT NULL DEFAULT 0,
  total_cost DECIMAL(14,4) NOT NULL DEFAULT 0,
  source_ref VARCHAR(128) NULL,
  CONSTRAINT fk_bomitem_rev FOREIGN KEY (bom_revision_id) REFERENCES bom_revisions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE boq_headers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  bom_id BIGINT UNSIGNED NULL,
  boq_number VARCHAR(64) NOT NULL,
  current_revision INT NOT NULL DEFAULT 1,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_boq_number (tenant_id, boq_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE boq_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  boq_id BIGINT UNSIGNED NOT NULL,
  revision_number INT NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_boq_rev (boq_id, revision_number),
  CONSTRAINT fk_boqrev_boq FOREIGN KEY (boq_id) REFERENCES boq_headers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE boq_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  boq_revision_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  category VARCHAR(64) NULL,
  quantity DECIMAL(14,4) NOT NULL,
  uom VARCHAR(16) NOT NULL,
  unit_rate DECIMAL(14,4) NOT NULL DEFAULT 0,
  discount_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  tax_percent DECIMAL(8,4) NOT NULL DEFAULT 18,
  line_total DECIMAL(14,4) NOT NULL DEFAULT 0,
  CONSTRAINT fk_boqitem_rev FOREIGN KEY (boq_revision_id) REFERENCES boq_revisions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pricing_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  commercial_mode VARCHAR(32) NOT NULL DEFAULT 'markup',
  default_markup_percent DECIMAL(8,4) NOT NULL DEFAULT 25,
  default_margin_percent DECIMAL(8,4) NOT NULL DEFAULT 20,
  area_uom VARCHAR(16) NOT NULL DEFAULT 'SQ_FT',
  status VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pricing_calculations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  boq_id BIGINT UNSIGNED NULL,
  pricing_version_id BIGINT UNSIGNED NOT NULL,
  cost_total DECIMAL(14,4) NOT NULL,
  markup_percent DECIMAL(8,4) NOT NULL,
  gross_selling DECIMAL(14,4) NOT NULL,
  discount_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  tax_percent DECIMAL(8,4) NOT NULL DEFAULT 18,
  final_price DECIMAL(14,4) NOT NULL,
  breakdown_json JSON NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quotations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  project_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  quote_number VARCHAR(64) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'DRAFT',
  pricing_calculation_id BIGINT UNSIGNED NULL,
  pricing_snapshot_json JSON NULL,
  grand_total DECIMAL(14,4) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_quote_number (tenant_id, quote_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE app_meta SET meta_value = '7', updated_at = NOW() WHERE meta_key = 'schema_phase';
