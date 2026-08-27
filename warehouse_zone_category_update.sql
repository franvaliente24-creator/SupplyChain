-- Add zone_category field to warehouse_zones table
-- This allows categorizing warehouse zones by specific classes/categories
-- (e.g., Warehouse Equipment, Electronics, etc.)

ALTER TABLE warehouse_zones 
ADD COLUMN zone_category VARCHAR(100) DEFAULT NULL 
AFTER rack_code;

-- Update existing zones with default categories
UPDATE warehouse_zones SET zone_category = 'General Storage' WHERE zone_category IS NULL;
