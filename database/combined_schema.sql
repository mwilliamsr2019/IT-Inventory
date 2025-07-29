-- IT Inventory System - Complete Database Schema
-- This file combines both inventory and user management schemas

CREATE DATABASE IF NOT EXISTS it_inventory;
USE it_inventory;

-- ===========================
-- AUTHENTICATION SYSTEM
-- ===========================

-- Authentication types (local, ldap, sssd)
CREATE TABLE IF NOT EXISTS auth_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    config TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groups/Roles for permissions
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Enhanced users table (replaces old users table)
CREATE TABLE IF NOT EXISTS users_enhanced (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    display_name VARCHAR(100),
    phone VARCHAR(20),
    department VARCHAR(100),
    job_title VARCHAR(100),
    
    -- Authentication configuration
    auth_type_id INT NOT NULL DEFAULT 1,
    auth_identifier VARCHAR(100),
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    
    -- Account status
    is_active BOOLEAN DEFAULT TRUE,
    is_locked BOOLEAN DEFAULT FALSE,
    failed_login_attempts INT DEFAULT 0,
    lockout_until TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    password_changed_at TIMESTAMP NULL,
    
    -- Foreign keys
    FOREIGN KEY (auth_type_id) REFERENCES auth_types(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_auth_type (auth_type_id),
    INDEX idx_active (is_active)
);

-- User groups mapping (many-to-many)
CREATE TABLE IF NOT EXISTS user_groups (
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users_enhanced(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users_enhanced(id) ON DELETE SET NULL
);

-- Password history for security
CREATE TABLE IF NOT EXISTS password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_enhanced(id) ON DELETE CASCADE,
    INDEX idx_user_passwords (user_id, created_at)
);

-- User sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users_enhanced(id) ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id, is_active),
    INDEX idx_session_expires (expires_at)
);

-- Audit log for user actions
CREATE TABLE IF NOT EXISTS user_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id VARCHAR(100),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users_enhanced(id) ON DELETE SET NULL,
    INDEX idx_user_actions (user_id, action),
    INDEX idx_resource_actions (resource_type, resource_id),
    INDEX idx_audit_time (created_at)
);

-- ===========================
-- INVENTORY SYSTEM
-- ===========================

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
    FOREIGN KEY (created_by) REFERENCES users_enhanced(id) ON DELETE SET NULL
);

-- ===========================
-- INDEXES FOR PERFORMANCE
-- ===========================

-- Inventory indexes
CREATE INDEX idx_inventory_make ON inventory(make);
CREATE INDEX idx_inventory_model ON inventory(model);
CREATE INDEX idx_inventory_serial ON inventory(serial_number);
CREATE INDEX idx_inventory_property ON inventory(property_number);
CREATE INDEX idx_inventory_warranty ON inventory(warranty_end_date);
CREATE INDEX idx_inventory_excess ON inventory(excess_date);
CREATE INDEX idx_inventory_location ON inventory(location_id);
CREATE INDEX idx_inventory_use_case ON inventory(use_case_id);
CREATE INDEX idx_inventory_status ON inventory(status);

-- ===========================
-- DEFAULT DATA
-- ===========================

-- Insert default authentication types
INSERT IGNORE INTO auth_types (name, description, config) VALUES
('local', 'Local Database Authentication', '{"type": "local", "enabled": true}'),
('ldap', 'Active Directory/LDAP Authentication', '{"type": "ldap", "host": "localhost", "port": 389, "base_dn": "dc=example,dc=com", "user_filter": "(uid=%s)"}'),
('sssd', 'SSSD Authentication', '{"type": "sssd", "service": "sssd"}');

-- Insert default groups
INSERT IGNORE INTO groups (name, description, permissions) VALUES
('admin', 'System Administrators', '{"inventory": ["create", "read", "update", "delete"], "users": ["create", "read", "update", "delete"], "reports": ["read"], "settings": ["read", "update"]}'),
('manager', 'IT Managers', '{"inventory": ["create", "read", "update"], "users": ["read"], "reports": ["read"], "settings": ["read"]}'),
('operator', 'IT Operators', '{"inventory": ["create", "read", "update"], "users": ["read"], "reports": ["read"]}'),
('viewer', 'Read-Only Users', '{"inventory": ["read"], "reports": ["read"]}');

-- Insert default admin user with enhanced schema
INSERT IGNORE INTO users_enhanced (username, password_hash, email, full_name, display_name, auth_type_id, department, job_title) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 'Admin User', 1, 'IT', 'System Administrator');

-- Assign admin to admin group
INSERT IGNORE INTO user_groups (user_id, group_id, assigned_by) 
SELECT 1, 1, 1 WHERE NOT EXISTS (
    SELECT 1 FROM user_groups WHERE user_id = 1 AND group_id = 1
);

-- Insert default locations
INSERT IGNORE INTO locations (name, description) VALUES
('Server Room A', 'Primary server room on first floor'),
('Server Room B', 'Secondary server room on second floor'),
('Office 101', 'Main administrative office'),
('Office 102', 'IT department office'),
('Storage', 'Equipment storage area'),
('Lab A', 'Testing laboratory'),
('Lab B', 'Development laboratory');

-- Insert default use cases
INSERT IGNORE INTO use_cases (name, description) VALUES
('Production Server', 'Critical production server'),
('Development Workstation', 'Development and testing workstation'),
('Administrative Desktop', 'General administrative desktop'),
('Network Equipment', 'Switches, routers, and network devices'),
('Storage System', 'Storage arrays and backup systems'),
('Testing Equipment', 'Equipment used for testing purposes'),
('Backup System', 'Backup and disaster recovery systems');

-- Create sample inventory data
INSERT IGNORE INTO inventory (make, model, serial_number, property_number, warranty_end_date, excess_date, use_case_id, location_id, notes, status, created_by) VALUES
('Dell', 'PowerEdge R740', 'SN123456789', 'PROP-2024-001', '2027-12-31', NULL, 1, 1, 'Primary production server', 'active', 1),
('HP', 'ProLiant DL380', 'SN987654321', 'PROP-2024-002', '2026-08-15', NULL, 1, 2, 'Secondary production server', 'active', 1),
('Lenovo', 'ThinkCentre M720', 'SN456789123', 'PROP-2024-003', '2025-06-30', '2025-07-01', 3, 3, 'Admin desktop - planned for excess', 'excess', 1),
('Cisco', 'Catalyst 2960', 'SN789123456', 'PROP-2024-004', '2028-03-22', NULL, 4, 1, 'Core network switch', 'active', 1),
('Dell', 'EMC Unity 500', 'SN321654987', 'PROP-2024-005', '2027-11-10', NULL, 5, 1, 'Primary storage array', 'active', 1);

-- ===========================
-- MIGRATION NOTES
-- ===========================
-- This combined schema replaces:
-- - database/schema.sql (old inventory schema)
-- - database/user_management_schema.sql (user management schema)
-- 
-- Key changes:
-- 1. Replaced old 'users' table with 'users_enhanced' for full user management
-- 2. Added comprehensive authentication and authorization system
-- 3. Maintained all existing inventory functionality
-- 4. Added security features (password history, audit logging, sessions)
-- 5. All foreign keys updated to reference users_enhanced instead of users