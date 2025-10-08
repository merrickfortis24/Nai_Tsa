-- Migration: create unified product_variant table (sizes + flavors)
START TRANSACTION;

CREATE TABLE IF NOT EXISTS product_variant (
  Variant_ID INT AUTO_INCREMENT PRIMARY KEY,
  Product_ID INT NOT NULL,
  variant_type ENUM('size','flavor') NOT NULL,
  code VARCHAR(50) NOT NULL,
  label VARCHAR(100) NOT NULL,
  price_mode ENUM('ABSOLUTE','DELTA') NOT NULL DEFAULT 'DELTA',
  price_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_variant_product FOREIGN KEY (Product_ID) REFERENCES product(Product_ID) ON DELETE CASCADE,
  UNIQUE KEY uq_product_variant_code (Product_ID, variant_type, code),
  KEY idx_variant_type_product (variant_type, Product_ID),
  KEY idx_variant_product_active (Product_ID, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Optional: legacy sizes migration stub (uncomment and adapt if an old sizes table exists)
-- INSERT INTO product_variant (Product_ID, variant_type, code, label, price_mode, price_value, is_primary, active, sort_order, created_at, updated_at)
-- SELECT Product_ID, 'size', Size_Code, Size_Label, Price_Mode, Price_Value, Is_Primary, Active, Sort_Order, NOW(), NOW() FROM product_sizes;

COMMIT;
