<?php
require_once 'config/database.php';

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function getLocations() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT * FROM locations ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getUseCases() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT * FROM use_cases ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getInventoryCount() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT COUNT(*) as count FROM inventory WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch()['count'];
}

function getWarrantyExpiringCount() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT COUNT(*) as count FROM inventory 
              WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
              AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch()['count'];
}

function getExcessCount() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT COUNT(*) as count FROM inventory WHERE status = 'excess'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch()['count'];
}

function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('M j, Y', strtotime($date));
}

function formatDateForInput($date) {
    if (empty($date)) return '';
    return date('Y-m-d', strtotime($date));
}

function displayStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'excess' => '<span class="badge bg-warning">Excess</span>',
        'disposed' => '<span class="badge bg-danger">Disposed</span>',
        'maintenance' => '<span class="badge bg-info">Maintenance</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function validateInventoryData($data) {
    $errors = [];
    
    if (empty($data['make'])) {
        $errors['make'] = 'Make is required';
    }
    
    if (empty($data['model'])) {
        $errors['model'] = 'Model is required';
    }
    
    if (empty($data['serial_number'])) {
        $errors['serial_number'] = 'Serial number is required';
    } else {
        // Check if serial number already exists
        $database = new Database();
        $db = $database->connect();
        
        $query = "SELECT COUNT(*) FROM inventory WHERE serial_number = :serial_number";
        if (isset($data['id'])) {
            $query .= " AND id != :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':serial_number', $data['serial_number']);
        if (isset($data['id'])) {
            $stmt->bindParam(':id', $data['id']);
        }
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $errors['serial_number'] = 'Serial number already exists';
        }
    }
    
    if (empty($data['property_number'])) {
        $errors['property_number'] = 'Property number is required';
    } else {
        // Check if property number already exists
        $database = new Database();
        $db = $database->connect();
        
        $query = "SELECT COUNT(*) FROM inventory WHERE property_number = :property_number";
        if (isset($data['id'])) {
            $query .= " AND id != :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':property_number', $data['property_number']);
        if (isset($data['id'])) {
            $stmt->bindParam(':id', $data['id']);
        }
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $errors['property_number'] = 'Property number already exists';
        }
    }
    
    if (!empty($data['warranty_end_date']) && !strtotime($data['warranty_end_date'])) {
        $errors['warranty_end_date'] = 'Invalid warranty end date';
    }
    
    if (!empty($data['excess_date']) && !strtotime($data['excess_date'])) {
        $errors['excess_date'] = 'Invalid excess date';
    }
    
    return $errors;
}
?>