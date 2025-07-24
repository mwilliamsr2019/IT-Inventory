-- IT Systems Inventory Database Schema
CREATE DATABASE IF NOT EXISTS it_inventory;
USE it_inventory;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Use cases table
CREATE TABLE IF NOT EXISTS use_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Main inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    property_number VARCHAR(100) UNIQUE NOT NULL,
    warranty_end_date DATE,
    excess_date DATE,
    use_case_id INT,
    location_id INT,
    notes TEXT,
    status ENUM('active', 'excess', 'disposed', 'maintenance') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (use_case_id) REFERENCES use_cases(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes for better search performance
CREATE INDEX idx_inventory_make ON inventory(make);
CREATE INDEX idx_inventory_model ON inventory(model);
CREATE INDEX idx_inventory_serial ON inventory(serial_number);
CREATE INDEX idx_inventory_property ON inventory(property_number);
CREATE INDEX idx_inventory_warranty ON inventory(warranty_end_date);
CREATE INDEX idx_inventory_excess ON inventory(excess_date);
CREATE INDEX idx_inventory_location ON inventory(location_id);
CREATE INDEX idx_inventory_use_case ON inventory(use_case_id);

-- Insert default locations
INSERT INTO locations (name, description) VALUES
('Server Room A', 'Primary server room on first floor'),
('Server Room B', 'Secondary server room on second floor'),
('Office 101', 'Main administrative office'),
('Office 102', 'IT department office'),
('Storage', 'Equipment storage area'),
('Lab A', 'Testing laboratory'),
('Lab B', 'Development laboratory');

-- Insert default use cases
INSERT INTO use_cases (name, description) VALUES
('Production Server', 'Critical production server'),
('Development Workstation', 'Development and testing workstation'),
('Administrative Desktop', 'General administrative desktop'),
('Network Equipment', 'Switches, routers, and network devices'),
('Storage System', 'Storage arrays and backup systems'),
('Testing Equipment', 'Equipment used for testing purposes'),
('Backup System', 'Backup and disaster recovery systems');

-- Create default admin user (password: admin123)
INSERT INTO users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 'admin');

-- Create sample data
INSERT INTO inventory (make, model, serial_number, property_number, warranty_end_date, excess_date, use_case_id, location_id, notes, status) VALUES
('Dell', 'PowerEdge R740', 'SN123456789', 'PROP-2024-001', '2027-12-31', NULL, 1, 1, 'Primary production server', 'active'),
('HP', 'ProLiant DL380', 'SN987654321', 'PROP-2024-002', '2026-08-15', NULL, 1, 2, 'Secondary production server', 'active'),
('Lenovo', 'ThinkCentre M720', 'SN456789123', 'PROP-2024-003', '2025-06-30', '2025-07-01', 3, 3, 'Admin desktop - planned for excess', 'excess'),
('Cisco', 'Catalyst 2960', 'SN789123456', 'PROP-2024-004', '2028-03-22', NULL, 4, 1, 'Core network switch', 'active'),
('Dell', 'EMC Unity 500', 'SN321654987', 'PROP-2024-005', '2027-11-10', NULL, 5, 1, 'Primary storage array', 'active');