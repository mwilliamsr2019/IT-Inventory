<?php
require_once 'config/database.php';

/**
 * User Management Functions
 */

function getAllUsers($filters = []) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT u.*, at.name as auth_type_name, at.description as auth_type_desc
              FROM users_enhanced u
              LEFT JOIN auth_types at ON u.auth_type_id = at.id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['search'])) {
        $query .= " AND (u.username LIKE :search OR u.full_name LIKE :search2 OR u.email LIKE :search3)";
        $search = '%' . $filters['search'] . '%';
        $params[':search'] = $search;
        $params[':search2'] = $search;
        $params[':search3'] = $search;
    }
    
    if (isset($filters['is_active'])) {
        $query .= " AND u.is_active = :is_active";
        $params[':is_active'] = $filters['is_active'];
    }
    
    if (!empty($filters['auth_type_id'])) {
        $query .= " AND u.auth_type_id = :auth_type_id";
        $params[':auth_type_id'] = $filters['auth_type_id'];
    }
    
    $query .= " ORDER BY u.username ASC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById($userId) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT u.*, at.name as auth_type_name
              FROM users_enhanced u
              LEFT JOIN auth_types at ON u.auth_type_id = at.id
              WHERE u.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserGroups($userId) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT g.*, ug.assigned_at, ug.assigned_by
              FROM groups g
              JOIN user_groups ug ON g.id = ug.group_id
              WHERE ug.user_id = :user_id
              ORDER BY g.name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllGroups() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT * FROM groups ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAuthTypes() {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT * FROM auth_types WHERE is_active = 1 ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createUser($userData) {
    $database = new Database();
    $db = $database->connect();
    
    // Validate required fields
    $errors = [];
    
    if (empty($userData['username'])) {
        $errors[] = 'Username is required';
    }
    
    if (empty($userData['email'])) {
        $errors[] = 'Email is required';
    }
    
    if ($userData['auth_type_id'] == 1 && empty($userData['password'])) {
        $errors[] = 'Password is required for local authentication';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check for existing username
    $checkQuery = "SELECT COUNT(*) FROM users_enhanced WHERE username = :username";
    $stmt = $db->prepare($checkQuery);
    $stmt->bindParam(':username', $userData['username']);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'errors' => ['Username already exists']];
    }
    
    // Check for existing email
    $checkQuery = "SELECT COUNT(*) FROM users_enhanced WHERE email = :email";
    $stmt = $db->prepare($checkQuery);
    $stmt->bindParam(':email', $userData['email']);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'errors' => ['Email already exists']];
    }
    
    // Hash password if local authentication
    $passwordHash = null;
    if ($userData['auth_type_id'] == 1 && !empty($userData['password'])) {
        $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT);
    }
    
    $query = "INSERT INTO users_enhanced 
              (username, password_hash, email, full_name, display_name, phone, 
               department, job_title, auth_type_id, auth_identifier, is_active)
              VALUES 
              (:username, :password_hash, :email, :full_name, :display_name, :phone,
               :department, :job_title, :auth_type_id, :auth_identifier, :is_active)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $userData['username']);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':email', $userData['email']);
    $stmt->bindParam(':full_name', $userData['full_name']);
    $stmt->bindParam(':display_name', $userData['display_name']);
    $stmt->bindParam(':phone', $userData['phone']);
    $stmt->bindParam(':department', $userData['department']);
    $stmt->bindParam(':job_title', $userData['job_title']);
    $stmt->bindParam(':auth_type_id', $userData['auth_type_id']);
    $stmt->bindParam(':auth_identifier', $userData['auth_identifier']);
    $stmt->bindParam(':is_active', $userData['is_active']);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        
        // Assign to groups if provided
        if (!empty($userData['groups'])) {
            foreach ($userData['groups'] as $groupId) {
                assignUserToGroup($userId, $groupId);
            }
        }
        
        // Log the creation
        logUserAction($userId, 'USER_CREATED', 'users', $userId);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'errors' => ['Failed to create user']];
}

function updateUser($userId, $userData) {
    $database = new Database();
    $db = $database->connect();
    
    // Get existing user
    $existingUser = getUserById($userId);
    if (!$existingUser) {
        return ['success' => false, 'errors' => ['User not found']];
    }
    
    // Check for existing username
    if (!empty($userData['username']) && $userData['username'] != $existingUser['username']) {
        $checkQuery = "SELECT COUNT(*) FROM users_enhanced WHERE username = :username AND id != :id";
        $stmt = $db->prepare($checkQuery);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'errors' => ['Username already exists']];
        }
    }
    
    // Check for existing email
    if (!empty($userData['email']) && $userData['email'] != $existingUser['email']) {
        $checkQuery = "SELECT COUNT(*) FROM users_enhanced WHERE email = :email AND id != :id";
        $stmt = $db->prepare($checkQuery);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'errors' => ['Email already exists']];
        }
    }
    
    // Update user
    $query = "UPDATE users_enhanced SET 
              username = :username, email = :email, full_name = :full_name,
              display_name = :display_name, phone = :phone, department = :department,
              job_title = :job_title, auth_type_id = :auth_type_id, 
              auth_identifier = :auth_identifier, is_active = :is_active";
    
    // Update password if provided
    $params = [
        ':username' => $userData['username'] ?? $existingUser['username'],
        ':email' => $userData['email'] ?? $existingUser['email'],
        ':full_name' => $userData['full_name'] ?? $existingUser['full_name'],
        ':display_name' => $userData['display_name'] ?? $existingUser['display_name'],
        ':phone' => $userData['phone'] ?? $existingUser['phone'],
        ':department' => $userData['department'] ?? $existingUser['department'],
        ':job_title' => $userData['job_title'] ?? $existingUser['job_title'],
        ':auth_type_id' => $userData['auth_type_id'] ?? $existingUser['auth_type_id'],
        ':auth_identifier' => $userData['auth_identifier'] ?? $existingUser['auth_identifier'],
        ':is_active' => $userData['is_active'] ?? $existingUser['is_active'],
        ':id' => $userId
    ];
    
    if (!empty($userData['password']) && $existingUser['auth_type_id'] == 1) {
        $query .= ", password_hash = :password_hash, password_changed_at = NOW()";
        $params[':password_hash'] = password_hash($userData['password'], PASSWORD_BCRYPT);
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        // Update groups if provided
        if (isset($userData['groups'])) {
            // Remove existing groups
            $deleteQuery = "DELETE FROM user_groups WHERE user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $deleteStmt->execute();
            
            // Add new groups
            foreach ($userData['groups'] as $groupId) {
                assignUserToGroup($userId, $groupId);
            }
        }
        
        logUserAction($userId, 'USER_UPDATED', 'users', $userId);
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Failed to update user']];
}

function deleteUser($userId) {
    $database = new Database();
    $db = $database->connect();
    
    // Prevent deletion of last admin
    $adminCountQuery = "SELECT COUNT(*) FROM users_enhanced WHERE role = 'admin' AND is_active = 1";
    $adminStmt = $db->prepare($adminCountQuery);
    $adminStmt->execute();
    
    $user = getUserById($userId);
    if ($user && $user['role'] == 'admin' && $adminStmt->fetchColumn() <= 1) {
        return ['success' => false, 'errors' => ['Cannot delete the last active admin user']];
    }
    
    // Soft delete (deactivate)
    $query = "UPDATE users_enhanced SET is_active = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        logUserAction($userId, 'USER_DEACTIVATED', 'users', $userId);
        return ['success' => true];
    }
    
    return ['success' => false, 'errors' => ['Failed to deactivate user']];
}

function assignUserToGroup($userId, $groupId) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "INSERT INTO user_groups (user_id, group_id, assigned_by) 
              VALUES (:user_id, :group_id, :assigned_by)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':group_id', $groupId, PDO::PARAM_INT);
    $stmt->bindParam(':assigned_by', $_SESSION['user_id'], PDO::PARAM_INT);
    
    return $stmt->execute();
}

function logUserAction($userId, $action, $resourceType = null, $resourceId = null) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "INSERT INTO user_audit_log 
              (user_id, action, resource_type, resource_id, ip_address, user_agent)
              VALUES 
              (:user_id, :action, :resource_type, :resource_id, :ip_address, :user_agent)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':resource_type', $resourceType);
    $stmt->bindParam(':resource_id', $resourceId);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt->bindParam(':ip_address', $ipAddress);
    $stmt->bindParam(':user_agent', $userAgent);
    
    return $stmt->execute();
}

function getUserPermissions($userId) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT g.permissions
              FROM groups g
              JOIN user_groups ug ON g.id = ug.group_id
              WHERE ug.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $permissions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $groupPermissions = json_decode($row['permissions'], true);
        if (is_array($groupPermissions)) {
            $permissions = array_merge_recursive($permissions, $groupPermissions);
        }
    }
    
    return $permissions;
}

function hasPermission($userId, $resource, $action) {
    $permissions = getUserPermissions($userId);
    return isset($permissions[$resource]) && in_array($action, $permissions[$resource]);
}
?>