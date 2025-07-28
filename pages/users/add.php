<?php
require_once 'includes/user_functions.php';
require_once 'includes/auth.php';

// Ensure user is admin
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'display_name' => sanitizeInput($_POST['display_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'department' => sanitizeInput($_POST['department'] ?? ''),
        'job_title' => sanitizeInput($_POST['job_title'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'auth_type_id' => intval($_POST['auth_type_id'] ?? 1),
        'auth_identifier' => sanitizeInput($_POST['auth_identifier'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'groups' => $_POST['groups'] ?? []
    ];
    
    $result = createUser($userData);
    
    if ($result['success']) {
        $_SESSION['success'] = 'User created successfully!';
        header('Location: index.php?page=users&action=list');
        exit();
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

$authTypes = getAuthTypes();
$groups = getAllGroups();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Add New User</h1>
    <a href="index.php?page=users&action=list" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">User Information</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name</label>
                        <input type="text" class="form-control" id="display_name" name="display_name" 
                               value="<?php echo htmlspecialchars($_POST['display_name'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" 
                               value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="job_title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="job_title" name="job_title" 
                               value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="auth_type_id" class="form-label">Authentication Type *</label>
                        <select class="form-select" id="auth_type_id" name="auth_type_id" required>
                            <?php foreach ($authTypes as $authType): ?>
                                <option value="<?php echo $authType['id']; ?>" 
                                        <?php echo (intval($_POST['auth_type_id'] ?? 1) == $authType['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($authType['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">
                            Required for local authentication only
                        </small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="auth_identifier" class="form-label">Auth Identifier</label>
                        <input type="text" class="form-control" id="auth_identifier" name="auth_identifier" 
                               value="<?php echo htmlspecialchars($_POST['auth_identifier'] ?? ''); ?>">
                        <small class="form-text text-muted">
                            LDAP username or external ID
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Groups</label>
                <div class="row">
                    <?php foreach ($groups as $index => $group): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="groups[]" value="<?php echo $group['id']; ?>" 
                                       id="group_<?php echo $group['id']; ?>" 
                                       <?php echo in_array($group['id'], $_POST['groups'] ?? []) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="group_<?php echo $group['id']; ?>">
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           <?php echo isset($_POST['is_active']) || !isset($_POST['username']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">
                        Active User
                    </label>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?page=users&action=list" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('auth_type_id').addEventListener('change', function() {
    const passwordField = document.getElementById('password');
    const authIdentifierField = document.getElementById('auth_identifier');
    
    if (this.value == 1) { // Local
        passwordField.required = true;
        passwordField.disabled = false;
        authIdentifierField.placeholder = '';
    } else { // LDAP/SSSD
        passwordField.required = false;
        passwordField.disabled = true;
        passwordField.value = '';
        authIdentifierField.placeholder = 'LDAP username';
    }
});
</script>