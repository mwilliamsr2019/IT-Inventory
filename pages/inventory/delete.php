<?php
require_once 'config/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "DELETE FROM inventory WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Inventory item deleted successfully!';
    } else {
        $_SESSION['error'] = 'Error deleting inventory item.';
    }
} else {
    $_SESSION['error'] = 'Invalid inventory item ID.';
}

header('Location: index.php?page=inventory');
exit();
?>