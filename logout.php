<?php
require_once 'includes/auth.php';

session_start();
logoutUser();
header('Location: login.php');
exit();
?>