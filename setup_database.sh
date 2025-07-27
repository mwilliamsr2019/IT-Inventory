#!/bin/bash

# IT Inventory Database Setup Script
# This script resolves MySQL/MariaDB authentication issues

echo "🔧 IT Inventory Database Setup"
echo "============================="

# Check if MySQL/MariaDB is running
if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mariadb; then
    echo "❌ MySQL/MariaDB is not running. Please start it first:"
    echo "   sudo systemctl start mysql"
    echo "   or"
    echo "   sudo systemctl start mariadb"
    exit 1
fi

# Create database and user
echo "🗄️  Setting up database..."

# Get MySQL root password
read -s -p "Enter MySQL root password: " MYSQL_ROOT_PASSWORD
echo ""

# Create database and user
mysql -u root -p"$MYSQL_ROOT_PASSWORD" << EOF
-- Create database
CREATE DATABASE IF NOT EXISTS it_inventory;
USE it_inventory;

-- Create dedicated user for the application
CREATE USER IF NOT EXISTS 'itinv_user'@'localhost' IDENTIFIED BY 'SecurePass123!';
GRANT ALL PRIVILEGES ON it_inventory.* TO 'itinv_user'@'localhost';
FLUSH PRIVILEGES;

-- Source the schema
SOURCE database/schema.sql;

-- Verify setup
SELECT 'Database setup complete!' AS status;
SELECT 'User itinv_user created with secure password' AS user_status;
EOF

if [ $? -eq 0 ]; then
    echo "✅ Database setup successful!"
    
    # Create .env file with correct credentials
    echo "📝 Creating .env file..."
    cat > config/.env << EOL
# Database Configuration
DB_HOST=localhost
DB_NAME=it_inventory
DB_USER=itinv_user
DB_PASS=SecurePass123!

# Application Configuration
APP_NAME="IT Inventory Management System"
APP_URL=http://localhost
APP_ENV=development

# Security
SESSION_TIMEOUT=3600
CSRF_TOKEN_LIFETIME=3600

# File Upload Settings
MAX_FILE_SIZE=10485760
ALLOWED_EXTENSIONS=xlsx,xls,csv

# LDAP Configuration (optional)
LDAP_HOST=localhost
LDAP_PORT=389
LDAP_BASE_DN=dc=example,dc=com
LDAP_BIND_DN=cn=admin,dc=example,dc=com
LDAP_BIND_PASSWORD=admin_password
LDAP_USER_FILTER=(uid=%s)
EOL
    
    echo "✅ .env file created with secure credentials"
    echo ""
    echo "📋 Next Steps:"
    echo "1. Ensure .env file has correct permissions: chmod 600 config/.env"
    echo "2. Access the application at: http://localhost/it-inventory"
    echo "3. Login with: admin / admin123"
    echo "4. Change the admin password immediately!"
    
else
    echo "❌ Database setup failed. Please check your MySQL root password."
    exit 1
fi