-- Create order_status table
CREATE TABLE IF NOT EXISTS order_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create order_history table
CREATE TABLE IF NOT EXISTS order_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consumable_id INT NOT NULL,
    status_id INT NOT NULL,
    quantity_ordered INT NOT NULL,
    notes TEXT,
    ordered_by VARCHAR(100) NOT NULL,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumable_id) REFERENCES consumable_materials(id),
    FOREIGN KEY (status_id) REFERENCES order_status(id)
);

-- Insert default order statuses
INSERT IGNORE INTO order_status (id, status_name, description) VALUES 
    (1, 'Not Ordered', 'Item needs to be ordered'),
    (2, 'Ordered & Waiting', 'Order has been placed and is waiting for delivery'),
    (3, 'Backordered', 'Item is backordered by supplier'); 