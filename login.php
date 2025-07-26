<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

session_start();
setSecurityHeaders();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Check rate limiting
            if (!checkLoginAttempts($username)) {
                $error = 'Too many login attempts. Please try again in 15 minutes.';
                logSecurityEvent('LOGIN_RATE_LIMIT_EXCEEDED', null, "Username: $username");
            } else {
                // Check database authentication
                $database = new Database();
                $db = $database->connect();
                
                $query = "SELECT id, username, password_hash, role FROM users WHERE username = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password_hash'])) {
                    // Successful login
                    clearLoginAttempts($username);
                    loginUser($user['id'], $user['username'], $user['role']);
                    
                    logSecurityEvent('SUCCESSFUL_LOGIN', $user['id'], "Username: $username");
                    
                    // Redirect to intended page or dashboard
                    $redirect = $_SESSION['redirect_to'] ?? 'index.php';
                    unset($_SESSION['redirect_to']);
                    header("Location: $redirect");
                    exit();
                } else {
                    // Try SSSD/LDAP authentication if database fails
                    if (extension_loaded('ldap')) {
                        // SSSD/LDAP authentication attempt
                        $ldapSuccess = authenticateSSSD($username, $password);
                        if ($ldapSuccess) {
                            // Check if user exists in database, create if not
                            $query = "SELECT id, username, role FROM users WHERE username = :username";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':username', $username);
                            $stmt->execute();
                            
                            $user = $stmt->fetch();
                            
                            if (!$user) {
                                // Create new user from LDAP
                                $query = "INSERT INTO users (username, password_hash, email, full_name, role)
                                          VALUES (:username, :password_hash, :email, :full_name, 'user')";
                                $stmt = $db->prepare($query);
                                $stmt->bindValue(':username', $username);
                                $stmt->bindValue(':password_hash', password_hash(uniqid(), PASSWORD_BCRYPT));
                                $stmt->bindValue(':email', $username . '@company.com');
                                $stmt->bindValue(':full_name', ucfirst($username));
                                $stmt->execute();
                                
                                $userId = $db->lastInsertId();
                            } else {
                                $userId = $user['id'];
                                $user['role'] = $user['role'];
                            }
                            
                            clearLoginAttempts($username);
                            loginUser($userId, $username, $user['role'] ?? 'user');
                            
                            logSecurityEvent('SUCCESSFUL_LOGIN', $userId, "Username: $username (LDAP)");
                            
                            $redirect = $_SESSION['redirect_to'] ?? 'index.php';
                            unset($_SESSION['redirect_to']);
                            header("Location: $redirect");
                            exit();
                        }
                    }
                    
                    recordLoginAttempt($username);
                    logSecurityEvent('FAILED_LOGIN', null, "Username: $username");
                    $error = 'Invalid username or password.';
                }
            }
        }
    }
}

function authenticateSSSD($username, $password) {
    // SSSD/LDAP authentication configuration
    $ldapHost = $_ENV['LDAP_HOST'] ?? 'localhost';
    $ldapPort = $_ENV['LDAP_PORT'] ?? 389;
    $ldapBaseDn = $_ENV['LDAP_BASE_DN'] ?? 'dc=example,dc=com';
    $ldapUserFilter = $_ENV['LDAP_USER_FILTER'] ?? '(uid=%s)';
    
    $ldapConn = ldap_connect($ldapHost, $ldapPort);
    
    if (!$ldapConn) {
        return false;
    }
    
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
    
    $userDn = sprintf('uid=%s,%s', $username, $ldapBaseDn);
    
    if (@ldap_bind($ldapConn, $userDn, $password)) {
        ldap_close($ldapConn);
        return true;
    }
    
    ldap_close($ldapConn);
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/login.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header text-center bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-server me-2"></i>
                            IT Inventory Login
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="login.php">
                           <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                           <div class="mb-3">
                               <label for="username" class="form-label">Username</label>
                               <div class="input-group">
                                   <span class="input-group-text">
                                       <i class="fas fa-user"></i>
                                   </span>
                                   <input type="text" class="form-control" id="username" name="username"
                                          value="<?php echo htmlspecialchars($username); ?>" required autofocus
                                          autocomplete="username">
                               </div>
                           </div>
                           
                           <div class="mb-3">
                               <label for="password" class="form-label">Password</label>
                               <div class="input-group">
                                   <span class="input-group-text">
                                       <i class="fas fa-lock"></i>
                                   </span>
                                   <input type="password" class="form-control" id="password" name="password" required
                                          autocomplete="current-password">
                               </div>
                           </div>
                           
                           <div class="d-grid">
                               <button type="submit" class="btn btn-primary">
                                   <i class="fas fa-sign-in-alt me-2"></i>
                                   Login
                               </button>
                           </div>
                       </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Use your SSSD credentials or database login
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>