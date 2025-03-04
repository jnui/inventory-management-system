-- Add new fields to inventory_change_entries table
ALTER TABLE inventory_change_entries 
ADD COLUMN items_added INT DEFAULT 0 COMMENT 'Number of items added to inventory',
ADD COLUMN items_removed INT DEFAULT 0 COMMENT 'Number of items removed from inventory';

-- Update existing records to have default values
UPDATE inventory_change_entries SET items_added = 0, items_removed = 0; 