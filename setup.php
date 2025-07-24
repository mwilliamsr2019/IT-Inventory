<?php
// Setup script for IT Inventory Management System
// Run this file once to initialize the system

require_once 'config/database.php';

echo "IT Inventory Management System - Setup Script\n";
echo "============================================\n\n";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Test database connection
    echo "✓ Database connection successful\n";
    
    // Create tables if they don't exist
    $sql = file_get_contents('database/schema.sql');
    
    // Execute the schema
    $db->exec($sql);
    
    echo "✓ Database schema created successfully\n";
    echo "✓ Default data inserted\n";
    echo "✓ Admin user created (username: admin, password: admin123)\n\n";
    
    echo "Setup completed successfully!\n";
    echo "Now you can access the system at: http://your-server/setup.php\n";
    echo "Please delete this file after setup for security.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in config/.env\n";
}
?>