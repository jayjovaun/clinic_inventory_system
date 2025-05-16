-- Create the database
CREATE DATABASE IF NOT EXISTS clinic_inventory;
USE clinic_inventory;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Medicine categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Medicine inventory table
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    expiration_date DATE NOT NULL,
    date_delivered DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Low Stock') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    days_remaining INT DEFAULT NULL,
    type ENUM('Medicine', 'Medical Supply', 'Dental Supply') DEFAULT 'Medicine'
);

-- Medical supplies table
CREATE TABLE medical_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    category_id INT,
    quantity INT NOT NULL DEFAULT 0,
    expiration_date DATE,
    date_delivered DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Low Stock') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Dental supplies table
CREATE TABLE dental_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(100),
    category_id INT,
    quantity INT NOT NULL DEFAULT 0,
    expiration_date DATE,
    date_delivered DATE NOT NULL,
    status ENUM('Active', 'Expired', 'Low Stock') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Stock history table
CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    type ENUM('In', 'Out') NOT NULL,
    date_recorded DATE NOT NULL,
    recorded_by INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    notification_type ENUM('Expired', 'Expiring Soon', 'Low Stock') NOT NULL,
    is_cleared BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- User sessions table
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Dispensed medicines table
CREATE TABLE dispensed_medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity_dispensed INT NOT NULL,
    date_dispensed DATE NOT NULL,
    dispensed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (dispensed_by) REFERENCES users(id)
);

-- Insert default categories
TRUNCATE TABLE categories;
INSERT INTO categories (name, description) VALUES
('Medicine', 'General medicines'),
('Medical Supplies', 'Supplies used for medical purposes'),
('Dental Supplies', 'Supplies used for dental purposes');

-- Insert default admin user
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Admin');

-- Create indexes for better performance
CREATE INDEX idx_medicines_expiration ON medicines(expiration_date);
CREATE INDEX idx_medicines_status ON medicines(status);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_stock_history_medicine ON stock_history(medicine_id);

-- Add a table for storing cleared notifications
CREATE TABLE IF NOT EXISTS cleared_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    medicine_id INT NOT NULL,
    cleared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
);

-- Add a table for storing periodic inventory checks
CREATE TABLE IF NOT EXISTS inventory_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_medicines INT NOT NULL,
    expired_medicines INT NOT NULL,
    expiring_soon_medicines INT NOT NULL,
    low_stock_medicines INT NOT NULL
);

-- Add a table for storing API logs for debugging purposes
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    request_payload TEXT,
    response_payload TEXT,
    status_code INT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Remove the category_id column from the medicines table
ALTER TABLE medicines DROP COLUMN category_id;