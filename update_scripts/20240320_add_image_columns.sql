-- Add image columns to consumable_materials table
ALTER TABLE consumable_materials
ADD COLUMN image_full VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_thumb_50 VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_thumb_150 VARCHAR(255) DEFAULT NULL;

-- Create images directory if it doesn't exist
CREATE TABLE IF NOT EXISTS image_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    original_filename VARCHAR(255) NOT NULL,
    full_path VARCHAR(255) NOT NULL,
    thumb_50_path VARCHAR(255) NOT NULL,
    thumb_150_path VARCHAR(255) NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
); 