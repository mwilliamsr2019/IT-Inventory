<?php
// User Management System Setup Script
echo "=== IT Inventory User Management Setup ===\n\n";

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Database configuration
$host = 'localhost';
$dbname = 'it_inventory';
$username = 'itinv_user';
$password = 'SecurePass123!';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    echo "✅ Database '$dbname' selected\n";
    
    // Read and execute combined schema
    $schema = file_get_contents('database/combined_schema.sql');
    if ($schema === false) {
        throw new Exception("Cannot read combined schema file");
    }
    
    // Split and execute SQL statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', trim($statement))) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Combined schema created successfully\n";
    
    // Create default admin user if doesn't exist
    $check = $pdo->query("SELECT COUNT(*) FROM users_enhanced WHERE username = 'admin'")->fetchColumn();
    
    if ($check == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->prepare("
            INSERT INTO users_enhanced 
            (username, password_hash, email, full_name, display_name, auth_type_id, department, job_title) 
            VALUES 
            ('admin', ?, 'admin@example.com', 'System Administrator', 'Admin User', 1, 'IT', 'System Administrator')
        ")->execute([$passwordHash]);
        
        // Get admin user ID
        $adminId = $pdo->lastInsertId();
        
        // Assign admin to admin group
        $pdo->prepare("
            INSERT INTO user_groups (user_id, group_id, assigned_by) 
            VALUES (?, 1, ?)
        ")->execute([$adminId, $adminId]);
        
        echo "✅ Default admin user created: admin / admin123\n";
    } else {
        echo "✅ Admin user already exists\n";
    }
    
    echo "\n=== Setup Complete ===\n";
    echo "✅ User management system is ready\n";
    echo "✅ Authentication types: Local, LDAP, SSSD\n";
    echo "✅ Default groups: admin, manager, operator, viewer\n";
    echo "✅ Password history tracking enabled\n";
    echo "✅ User audit logging enabled\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Access user management at: http://localhost/index.php?page=users\n";
    echo "2. Login with: admin / admin123\n";
    echo "3. Create additional users and groups as needed\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "❌ Make sure MySQL is running and accessible\n";
}
?>