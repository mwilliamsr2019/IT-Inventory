<?php
require_once 'includes/user_functions.php';
require_once 'includes/auth.php';

// Ensure user is admin
requireAdmin();

if (!isset($_GET['action'])) {
    header('Location: index.php?page=users&action=list');
    exit();
}

$action = $_GET['action'];
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

switch ($action) {
    case 'activate':
        $user = getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: index.php?page=users&action=list');
            exit();
        }
        
        $database = new Database();
        $db = $database->connect();
        
        $query = "UPDATE users_enhanced SET is_active = 1 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logUserAction($userId, 'USER_ACTIVATED', 'users', $userId);
            $_SESSION['success'] = 'User activated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to activate user';
        }
        break;
        
    case 'deactivate':
        $user = getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: index.php?page=users&action=list');
            exit();
        }
        
        // Prevent deactivation of last admin
        $database = new Database();
        $db = $database->connect();
        
        $adminCountQuery = "SELECT COUNT(*) FROM users_enhanced WHERE is_active = 1";
        $adminStmt = $db->prepare($adminCountQuery);
        $adminStmt->execute();
        
        if ($adminStmt->fetchColumn() <= 1) {
            $_SESSION['error'] = 'Cannot deactivate the last active user';
            header('Location: index.php?page=users&action=list');
            exit();
        }
        
        $query = "UPDATE users_enhanced SET is_active = 0 WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            logUserAction($userId, 'USER_DEACTIVATED', 'users', $userId);
            $_SESSION['success'] = 'User deactivated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to deactivate user';
        }
        break;
        
    case 'delete':
        if (!isset($_POST['confirm_delete'])) {
            $_SESSION['error'] = 'Invalid request';
            header('Location: index.php?page=users&action=list');
            exit();
        }
        
        $user = getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: index.php?page=users&action=list');
            exit();
        }
        
        $result = deleteUser($userId);
        
        if ($result['success']) {
            $_SESSION['success'] = 'User deleted successfully!';
        } else {
            $_SESSION['error'] = implode('<br>', $result['errors']);
        }
        break;
}

header('Location: index.php?page=users&action=list');
exit();
?>