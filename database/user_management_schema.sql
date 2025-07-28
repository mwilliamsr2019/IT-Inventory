-- Enhanced User Management Schema for IT Inventory System

-- Authentication types
CREATE TABLE IF NOT EXISTS auth_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    config TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groups/Roles table
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Enhanced users table
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

-- Insert default authentication types
INSERT INTO auth_types (name, description, config) VALUES
('local', 'Local Database Authentication', '{"type": "local", "enabled": true}'),
('ldap', 'Active Directory/LDAP Authentication', '{"type": "ldap", "host": "localhost", "port": 389, "base_dn": "dc=example,dc=com", "user_filter": "(uid=%s)"}'),
('sssd', 'SSSD Authentication', '{"type": "sssd", "service": "sssd"}');

-- Insert default groups
INSERT INTO groups (name, description, permissions) VALUES
('admin', 'System Administrators', '{"inventory": ["create", "read", "update", "delete"], "users": ["create", "read", "update", "delete"], "reports": ["read"], "settings": ["read", "update"]}'),
('manager', 'IT Managers', '{"inventory": ["create", "read", "update"], "users": ["read"], "reports": ["read"], "settings": ["read"]}'),
('operator', 'IT Operators', '{"inventory": ["create", "read", "update"], "users": ["read"], "reports": ["read"]}'),
('viewer', 'Read-Only Users', '{"inventory": ["read"], "reports": ["read"]}');

-- Insert default admin user with enhanced schema
INSERT INTO users_enhanced (username, password_hash, email, full_name, display_name, auth_type_id, role, department, job_title) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 'Admin User', 1, 'admin', 'IT', 'System Administrator');

-- Assign admin to admin group
INSERT INTO user_groups (user_id, group_id, assigned_by) VALUES
(1, 1, 1);

-- Create triggers for password history
DELIMITER $$

CREATE TRIGGER after_user_password_update
    AFTER UPDATE ON users_enhanced
    FOR EACH ROW
BEGIN
    IF OLD.password_hash != NEW.password_hash THEN
        INSERT INTO password_history (user_id, password_hash)
        VALUES (OLD.id, OLD.password_hash);
    END IF;
END$$

DELIMITER ;