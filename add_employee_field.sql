-- Check if employee_id column exists and add it if it doesn't
SET @columnExists = 0;
SELECT COUNT(*) INTO @columnExists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'smccontr_inventory' 
AND TABLE_NAME = 'inventory_change_entries' 
AND COLUMN_NAME = 'employee_id';

SET @addColumnSQL = IF(@columnExists = 0, 
    'ALTER TABLE inventory_change_entries ADD COLUMN employee_id INT DEFAULT NULL COMMENT "Reference to the employee who made the change"', 
    'SELECT "Column employee_id already exists" AS message');

PREPARE stmt FROM @addColumnSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if foreign key constraint exists and add it if it doesn't
SET @constraintExists = 0;
SELECT COUNT(*) INTO @constraintExists 
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
WHERE TABLE_SCHEMA = 'smccontr_inventory' 
AND TABLE_NAME = 'inventory_change_entries' 
AND CONSTRAINT_NAME = 'fk_employee_id';

SET @addConstraintSQL = IF(@constraintExists = 0, 
    'ALTER TABLE inventory_change_entries ADD CONSTRAINT fk_employee_id FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL', 
    'SELECT "Constraint fk_employee_id already exists" AS message');

PREPARE stmt FROM @addConstraintSQL;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records to have NULL employee_id
UPDATE inventory_change_entries SET employee_id = NULL; 