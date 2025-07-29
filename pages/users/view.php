<?php
require_once '../../includes/user_functions.php';
require_once '../../includes/auth.php';

// Ensure user is admin or viewing their own profile
requireAdmin();

$userId = $_GET['id'] ?? null;

if (!$userId) {
    header('Location: index.php?page=users&action=list');
    exit();
}

try {
    $user = getUserById($userId);
    if (!$user) {
        throw new Exception("User not found");
    }
    
    $groups = getUserGroups($userId);
    $permissions = getUserPermissions($userId);
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-user"></i> User Details</h1>
        <div>
            <a href="index.php?page=users&action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit User
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">User ID:</dt>
                        <dd class="col-sm-8"><?php echo $user['id']; ?></dd>

                        <dt class="col-sm-4">Username:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['username']); ?></dd>

                        <dt class="col-sm-4">Full Name:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4">Display Name:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['display_name'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['email']); ?></dd>

                        <dt class="col-sm-4">Phone:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4">Department:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4">Job Title:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($user['job_title'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4">Authentication Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-info"><?php echo htmlspecialchars($user['auth_type_name']); ?></span>
                        </dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Account Locked:</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?php echo $user['is_locked'] ? 'bg-warning' : 'bg-success'; ?>">
                                <?php echo $user['is_locked'] ? 'Locked' : 'Unlocked'; ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Failed Login Attempts:</dt>
                        <dd class="col-sm-8"><?php echo $user['failed_login_attempts']; ?></dd>

                        <dt class="col-sm-4">Login Count:</dt>
                        <dd class="col-sm-8"><?php echo $user['login_count']; ?></dd>

                        <dt class="col-sm-4">Last Login:</dt>
                        <dd class="col-sm-8">
                            <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                        </dd>

                        <dt class="col-sm-4">Created:</dt>
                        <dd class="col-sm-8"><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></dd>

                        <dt class="col-sm-4">Updated:</dt>
                        <dd class="col-sm-8"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></dd>

                        <?php if ($user['password_changed_at']): ?>
                        <dt class="col-sm-4">Password Changed:</dt>
                        <dd class="col-sm-8"><?php echo date('M j, Y g:i A', strtotime($user['password_changed_at'])); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Groups & Permissions</h5>
                </div>
                <div class="card-body">
                    <h6>Assigned Groups:</h6>
                    <?php if (empty($groups)): ?>
                        <p class="text-muted">No groups assigned</p>
                    <?php else: ?>
                        <div class="list-group mb-3">
                            <?php foreach ($groups as $group): ?>
                                <div class="list-group-item">
                                    <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($group['description']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <h6>Permissions:</h6>
                    <?php if (empty($permissions)): ?>
                        <p class="text-muted">No permissions assigned</p>
                    <?php else: ?>
                        <ul class="list-unstyled">
                            <?php foreach ($permissions as $resource => $actions): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars(ucfirst($resource)); ?>:</strong>
                                    <span class="badge bg-secondary ms-1"><?php echo implode('</span><span class="badge bg-secondary ms-1">', $actions); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Activity log integration coming soon...</p>
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