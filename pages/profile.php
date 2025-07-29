<?php
require_once '../includes/user_functions.php';
require_once '../config/database.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get user details
    $stmt = $db->prepare("
        SELECT u.*, at.name as auth_type_name
        FROM users_enhanced u
        LEFT JOIN auth_types at ON u.auth_type_id = at.id
        WHERE u.id = :id
    ");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Get user's groups
    $stmt = $db->prepare("
        SELECT g.name, g.description
        FROM groups g
        JOIN user_groups ug ON g.id = ug.group_id
        WHERE ug.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission for profile updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            // Update profile information
            $fullName = trim($_POST['full_name'] ?? '');
            $displayName = trim($_POST['display_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $jobTitle = trim($_POST['job_title'] ?? '');
            
            if (empty($fullName) || empty($email)) {
                $error = 'Full name and email are required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } else {
                $stmt = $db->prepare("
                    UPDATE users_enhanced
                    SET full_name = :full_name, display_name = :display_name, email = :email,
                        phone = :phone, department = :department, job_title = :job_title,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                $stmt->bindParam(':full_name', $fullName);
                $stmt->bindParam(':display_name', $displayName);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':department', $department);
                $stmt->bindParam(':job_title', $jobTitle);
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $message = 'Profile updated successfully.';
                
                // Refresh user data
                $stmt = $db->prepare("SELECT u.*, at.name as auth_type_name FROM users_enhanced u LEFT JOIN auth_types at ON u.auth_type_id = at.id WHERE u.id = :id");
                $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } elseif ($action === 'change_password') {
            // Skip password change for non-local auth
            if ($user['auth_type_id'] != 1) {
                $error = 'Password can only be changed for local authentication users.';
            } else {
                // Change password
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'All password fields are required.';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        UPDATE users_enhanced
                        SET password_hash = :password_hash, password_changed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    $stmt->bindParam(':password_hash', $newPasswordHash);
                    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
                    $stmt->execute();
                    $message = 'Password changed successfully.';
                    
                    // Store old password in history
                    $stmt = $db->prepare("INSERT INTO password_history (user_id, password_hash) VALUES (:user_id, :password_hash)");
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $stmt->bindParam(':password_hash', $user['password_hash']);
                    $stmt->execute();
                }
            }
        }
    }
    
} catch (Exception $e) {
    $error = 'Error loading profile: ' . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1><i class="fas fa-user"></i> User Profile</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-circle"></i> Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="display_name" class="form-label">Display Name</label>
                                    <input type="text" class="form-control" id="display_name" name="display_name" 
                                           value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" name="department" 
                                           value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="job_title" class="form-label">Job Title</label>
                                    <input type="text" class="form-control" id="job_title" name="job_title" 
                                           value="<?php echo htmlspecialchars($user['job_title'] ?? ''); ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-shield-alt"></i> Account Details</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">User ID:</dt>
                                <dd class="col-sm-8"><?php echo $user['id']; ?></dd>
                                
                                <dt class="col-sm-4">Auth Type:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($user['auth_type_name']); ?></dd>
                                
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></dd>
                                
                                <dt class="col-sm-4">Last Login:</dt>
                                <dd class="col-sm-8">
                                    <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </dd>
                                
                                <dt class="col-sm-4">Login Count:</dt>
                                <dd class="col-sm-8"><?php echo $user['login_count']; ?></dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-users"></i> Groups & Roles</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($groups)): ?>
                                <p class="text-muted">No groups assigned</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($groups as $group): ?>
                                        <div class="list-group-item">
                                            <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($group['description']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($user['auth_type_id'] == 1): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-key"></i> Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Password Management</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <i class="fas fa-shield-alt"></i>
                                Password for <?php echo htmlspecialchars($user['auth_type_name']); ?> authentication
                                is managed externally.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style>