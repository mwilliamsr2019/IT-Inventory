<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

session_start();
setSecurityHeaders();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-server"></i> IT Inventory
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'inventory' ? 'active' : ''; ?>" href="index.php?page=inventory">
                            <i class="fas fa-list"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'search' ? 'active' : ''; ?>" href="index.php?page=search">
                            <i class="fas fa-search"></i> Search
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'export' ? 'active' : ''; ?>" href="index.php?page=export">
                            <i class="fas fa-file-export"></i> Export/Import
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $page === 'users' ? 'active' : ''; ?>"
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=users&action=list">
                                <i class="fas fa-list"></i> All Users
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?page=users&action=add">
                                <i class="fas fa-plus"></i> Add User
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?page=users&action=groups">
                                <i class="fas fa-users-cog"></i> Manage Groups
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-cog"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'inventory':
                $action = $_GET['action'] ?? 'list';
                switch ($action) {
                    case 'add':
                        include 'pages/inventory/add.php';
                        break;
                    case 'edit':
                        include 'pages/inventory/edit.php';
                        break;
                    case 'view':
                        include 'pages/inventory/view.php';
                        break;
                    case 'delete':
                        include 'pages/inventory/delete.php';
                        break;
                    default:
                        include 'pages/inventory/list.php';
                }
                break;
            case 'search':
                include 'pages/search.php';
                break;
            case 'export':
                include 'pages/export_import.php';
                break;
            case 'profile':
                include 'pages/profile.php';
                break;
            case 'users':
                $action = $_GET['action'] ?? 'list';
                switch ($action) {
                    case 'add':
                        include 'pages/users/add.php';
                        break;
                    case 'edit':
                        include 'pages/users/edit.php';
                        break;
                    case 'view':
                        include 'pages/users/view.php';
                        break;
                    case 'groups':
                        include 'pages/users/groups.php';
                        break;
                    case 'activate':
                    case 'deactivate':
                        include 'pages/users/actions.php';
                        break;
                    default:
                        include 'pages/users/list.php';
                }
                break;
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>