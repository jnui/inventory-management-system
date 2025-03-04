-- Add whole_quantity field to consumable_materials table
ALTER TABLE consumable_materials 
ADD COLUMN whole_quantity INT DEFAULT 0 COMMENT 'Running total of whole pieces';

-- Update existing records to have default values
UPDATE consumable_materials SET whole_quantity = 0;

-- Add whole_quantity field to inventory_change_entries table
ALTER TABLE inventory_change_entries 
ADD COLUMN whole_quantity INT DEFAULT 0 COMMENT 'Running total of whole pieces';

-- Update existing records to have default values
UPDATE inventory_change_entries SET whole_quantity = 0; 