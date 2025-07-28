<?php
require_once 'includes/user_functions.php';
require_once 'includes/auth.php';

// Ensure user is admin
requireAdmin();

$filters = [
    'search' => $_GET['search'] ?? '',
    'is_active' => isset($_GET['is_active']) ? (int)$_GET['is_active'] : null,
    'auth_type_id' => $_GET['auth_type_id'] ?? null
];

$users = getAllUsers($filters);
$authTypes = getAuthTypes();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">User Management</h1>
    <div>
        <a href="index.php?page=users&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add User
        </a>
        <a href="index.php?page=users&action=groups" class="btn btn-secondary">
            <i class="fas fa-users"></i> Manage Groups
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="users">
            <input type="hidden" name="action" value="list">
            
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>" 
                       placeholder="Username, name, or email">
            </div>
            
            <div class="col-md-3">
                <label for="auth_type_id" class="form-label">Auth Type</label>
                <select class="form-select" id="auth_type_id" name="auth_type_id">
                    <option value="">All Types</option>
                    <?php foreach ($authTypes as $authType): ?>
                        <option value="<?php echo $authType['id']; ?>" 
                                <?php echo $filters['auth_type_id'] == $authType['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($authType['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">All</option>
                    <option value="1" <?php echo $filters['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $filters['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Users (<?php echo count($users); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">No users found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Auth Type</th>
                            <th>Groups</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($user['auth_type_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $userGroups = getUserGroups($user['id']);
                                    $groupNames = array_column($userGroups, 'name');
                                    echo htmlspecialchars(implode(', ', $groupNames)) ?: 'None';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="index.php?page=users&action=edit&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="index.php?page=users&action=view&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($user['is_active']): ?>
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="confirmDeactivate(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                    title="Deactivate">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="confirmActivate(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                    title="Activate">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDeactivate(userId, username) {
    if (confirm(`Are you sure you want to deactivate user "${username}"?`)) {
        window.location.href = `index.php?page=users&action=deactivate&id=${userId}`;
    }
}

function confirmActivate(userId, username) {
    if (confirm(`Are you sure you want to activate user "${username}"?`)) {
        window.location.href = `index.php?page=users&action=activate&id=${userId}`;
    }
}
</script>