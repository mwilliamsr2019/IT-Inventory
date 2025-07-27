# Database Connection Issue Resolution

## Problem Analysis
The error `SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'` occurs when MySQL/MariaDB uses **socket authentication** instead of password authentication for the root user.

## Quick Fix Solutions

### Solution 1: Create Dedicated Database User (Recommended)

```bash
# 1. Login to MySQL as root
sudo mysql -u root

# 2. Create database and user
CREATE DATABASE it_inventory;
CREATE USER 'itinv_user'@'localhost' IDENTIFIED BY 'SecurePass123!';
GRANT ALL PRIVILEGES ON it_inventory.* TO 'itinv_user'@'localhost';
FLUSH PRIVILEGES;

# 3. Import the schema
USE it_inventory;
SOURCE database/schema.sql;
EXIT;
```

### Solution 2: Fix Root Authentication (Alternative)

```bash
# 1. Login to MySQL
sudo mysql -u root

# 2. Change root authentication method
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_new_password';
FLUSH PRIVILEGES;
EXIT;

# 3. Update your .env file
```

### Solution 3: Use Socket Authentication (Modern)

```bash
# Create .env file with socket connection
cat > config/.env << EOL
DB_HOST=localhost
DB_NAME=it_inventory
DB_USER=your_system_user
DB_PASS=
EOL

# Note: Leave DB_PASS empty for socket auth
```

## Step-by-Step Setup

### 1. Check Current Authentication Method
```bash
sudo mysql -u root -e "SELECT user, host, plugin FROM mysql.user WHERE user='root';"
```

### 2. Create Database and User
```bash
# Method 1: Using the provided script
chmod +x setup_database.sh
./setup_database.sh

# Method 2: Manual setup
sudo mysql -u root -p << EOF
CREATE DATABASE it_inventory;
CREATE USER 'itinv_user'@'localhost' IDENTIFIED BY 'SecurePass123!';
GRANT ALL PRIVILEGES ON it_inventory.* TO 'itinv_user'@'localhost';
FLUSH PRIVILEGES;
USE it_inventory;
SOURCE database/schema.sql;
EOF
```

### 3. Update Configuration
```bash
# Update .env file
cp config/.env.example config/.env
# Edit config/.env with:
DB_USER=itinv_user
DB_PASS=SecurePass123!
```

### 4. Test Connection
```bash
# Test PHP connection
php -r "
require_once 'config/database.php';
\$db = new Database();
\$conn = \$db->connect();
if(\$conn) echo '✅ Database connection successful!';
else echo '❌ Connection failed';
"
```

## Common Issues & Fixes

### Issue: "Access denied for user" with correct password
**Fix**: Ensure MySQL service is running:
```bash
sudo systemctl start mysql
sudo systemctl status mysql
```

### Issue: "Can't connect to local MySQL server"
**Fix**: Check socket path:
```bash
sudo mysql -u root -e "SHOW VARIABLES LIKE 'socket';"
```

### Issue: PHP can't connect
**Fix**: Install PHP MySQL extension:
```bash
sudo apt install php-mysql php-pdo
sudo systemctl restart apache2
```

## Verification Steps

1. **Test MySQL Connection**:
   ```bash
   mysql -u itinv_user -pSecurePass123! -e "SELECT 1;"
   ```

2. **Test PHP Connection**:
   ```php
   <?php
   try {
       $pdo = new PDO('mysql:host=localhost;dbname=it_inventory', 'itinv_user', 'SecurePass123!');
       echo "✅ Connection successful!";
   } catch(PDOException $e) {
       echo "❌ Connection failed: " . $e->getMessage();
   }
   ?>
   ```

3. **Check Database Schema**:
   ```bash
   mysql -u itinv_user -pSecurePass123! it_inventory -e "SHOW TABLES;"
   ```

## Environment-Specific Instructions

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install mysql-server php-mysql
sudo mysql_secure_installation
```

### CentOS/RHEL
```bash
sudo yum install mariadb-server php-mysql
sudo systemctl start mariadb
sudo mysql_secure_installation
```

### Windows/XAMPP
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `it_inventory`
3. Import: `database/schema.sql`
4. Update `config/.env` with XAMPP credentials

## Troubleshooting Checklist

- [ ] MySQL/MariaDB service is running
- [ ] Database `it_inventory` exists
- [ ] User `itinv_user` exists with correct password
- [ ] User has proper permissions
- [ ] PHP MySQL extension is installed
- [ ] `.env` file has correct credentials
- [ ] File permissions are correct (600 for .env)

## Quick Test Script
Create `test_db.php`:
```php
<?php
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->connect();
    echo "✅ Database connection successful!\n";
} catch(Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
```

Run: `php test_db.php`