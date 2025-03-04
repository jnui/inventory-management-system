-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    initials VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create an initial admin user (password: admin123)
-- The password is hashed using PHP's password_hash function with PASSWORD_DEFAULT
-- You should change this password after first login
INSERT INTO users (name, initials, password, role) 
VALUES ('Administrator', 'ADMIN', '$2y$10$qCpZxXYTGOoXGgS8yvxVwOPvF/mJRBIVh7nKgbHONDxQ5RVLX4Wd2', 'admin'); 