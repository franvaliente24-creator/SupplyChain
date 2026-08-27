-- Add qr_code field to inventory_items table for automatic QR generation
ALTER TABLE inventory_items 
ADD COLUMN qr_code VARCHAR(255) UNIQUE DEFAULT NULL 
AFTER warehouse_zone;

-- Generate QR codes for existing inventory items
UPDATE inventory_items SET qr_code = CONCAT('INV-', UPPER(SUBSTRING(sku, 1, 3)), '-', LPAD(item_id, 6, '0')) WHERE qr_code IS NULL;
