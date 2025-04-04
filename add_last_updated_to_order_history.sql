-- Add last_updated column to order_history table
ALTER TABLE order_history
ADD COLUMN last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create a trigger to update last_updated when order_history is modified
DELIMITER //
CREATE TRIGGER update_order_history_timestamp
BEFORE UPDATE ON order_history
FOR EACH ROW
BEGIN
    SET NEW.last_updated = CURRENT_TIMESTAMP;
END;//
DELIMITER ; 