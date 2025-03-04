-- Add reorder_threshold column to consumable_materials table
ALTER TABLE consumable_materials 
ADD COLUMN reorder_threshold INT DEFAULT 0 COMMENT 'Threshold at which items should be reordered';

-- Update existing records to set a default reorder_threshold
UPDATE consumable_materials SET reorder_threshold = 0; 