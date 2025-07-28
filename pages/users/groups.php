<?php
require_once 'includes/user_functions.php';
require_once 'includes/auth.php';

// Ensure user is admin
requireAdmin();

$error = '';
$success = '';

// Handle group actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_group') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $permissions = $_POST['permissions'] ?? [];
        
        if (empty($name)) {
            $error = 'Group name is required';
        } else {
            $database = new Database();
            $db = $database->connect();
            
            // Check for existing group
            $checkQuery = "SELECT COUNT(*) FROM groups WHERE name = :name";
            $stmt = $db->prepare($checkQuery);
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'Group name already exists';
            } else {
                $query = "INSERT INTO groups (name, description, permissions) 
                          VALUES (:name, :description, :permissions)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindValue(':permissions', json_encode($permissions));
                
                if ($stmt->execute()) {
                    $success = 'Group created successfully!';
                } else {
                    $error = 'Failed to create group';
                }
            }
        }
    }
}

$groups = getAllGroups();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Group Management</h1>
    <a href="index.php?page=users&action=list" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Users
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Groups (<?php echo count($groups); ?>)</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class="fas fa-plus"></i> Add Group
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($groups)): ?>
                    <div class="alert alert-info">No groups found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Users</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $group): ?>
                                    <?php
                                    // Count users in this group
                                    $database = new Database();
                                    $db = $database->connect();
                                    $countQuery = "SELECT COUNT(*) FROM user_groups WHERE group_id = :group_id";
                                    $countStmt = $db->prepare($countQuery);
                                    $countStmt->bindParam(':group_id', $group['id']);
                                    $countStmt->execute();
                                    $userCount = $countStmt->fetchColumn();
                                    
                                    $permissions = json_decode($group['permissions'], true);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($group['description'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $userCount; ?> users</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="editGroup(<?php echo $group['id']; ?>)"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="viewGroup(<?php echo $group['id']; ?>)"
                                                        title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Available Permissions</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>Inventory:</strong> create, read, update, delete</li>
                    <li><strong>Users:</strong> create, read, update, delete</li>
                    <li><strong>Reports:</strong> read</li>
                    <li><strong>Settings:</strong> read, update</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add Group Modal -->
<div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_group">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGroupModalLabel">Add New Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="group_name" class="form-label">Group Name *</label>
                        <input type="text" class="form-control" id="group_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="group_description" class="form-label">Description</label>
                        <textarea class="form-control" id="group_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Inventory</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="inventory_create" id="perm_inventory_create">
                                    <label class="form-check-label" for="perm_inventory_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="inventory_read" id="perm_inventory_read">
                                    <label class="form-check-label" for="perm_inventory_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="inventory_update" id="perm_inventory_update">
                                    <label class="form-check-label" for="perm_inventory_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="inventory_delete" id="perm_inventory_delete">
                                    <label class="form-check-label" for="perm_inventory_delete">Delete</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Users</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="users_create" id="perm_users_create">
                                    <label class="form-check-label" for="perm_users_create">Create</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="users_read" id="perm_users_read">
                                    <label class="form-check-label" for="perm_users_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="users_update" id="perm_users_update">
                                    <label class="form-check-label" for="perm_users_update">Update</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="users_delete" id="perm_users_delete">
                                    <label class="form-check-label" for="perm_users_delete">Delete</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Reports</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="reports_read" id="perm_reports_read">
                                    <label class="form-check-label" for="perm_reports_read">Read</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Settings</h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="settings_read" id="perm_settings_read">
                                    <label class="form-check-label" for="perm_settings_read">Read</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="settings_update" id="perm_settings_update">
                                    <label class="form-check-label" for="perm_settings_update">Update</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editGroup(groupId) {
    alert('Edit group functionality will be implemented in the next update');
}

function viewGroup(groupId) {
    alert('View group functionality will be implemented in the next update');
}
</script>