<?php
/**
 * Security functions for IT Inventory Management System
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Additional input sanitization
 */
function sanitizeHTML($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Rate limiting for login attempts
 */
function checkLoginAttempts($username) {
    $key = 'login_attempts_' . md5($username);
    $attempts = $_SESSION[$key] ?? 0;
    
    if ($attempts >= 5) {
        $lockout_time = $_SESSION[$key . '_lockout'] ?? 0;
        if (time() - $lockout_time < 900) { // 15 minutes
            return false;
        }
        // Reset after lockout period
        unset($_SESSION[$key]);
        unset($_SESSION[$key . '_lockout']);
    }
    
    return true;
}

function recordLoginAttempt($username) {
    $key = 'login_attempts_' . md5($username);
    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    
    if ($_SESSION[$key] >= 5) {
        $_SESSION[$key . '_lockout'] = time();
    }
}

function clearLoginAttempts($username) {
    $key = 'login_attempts_' . md5($username);
    unset($_SESSION[$key]);
    unset($_SESSION[$key . '_lockout']);
}

/**
 * File upload security validation
 */
function validateFileUpload($file, $allowed_types = ['csv', 'xlsx', 'xls']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return false;
        default:
            return false;
    }

    // Check file size (10MB max)
    if ($file['size'] > 10485760) {
        return false;
    }

    // Validate file extension
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        return false;
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    $allowed_mimes = [
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel'
    ];
    
    return in_array($mime_type, $allowed_mimes);
}

/**
 * Security headers
 */
function setSecurityHeaders() {
    // Prevent XSS
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enforce HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com;");
}

/**
 * Input validation for specific fields
 */
function validateSerialNumber($serial) {
    return preg_match('/^[a-zA-Z0-9\-_]+$/', $serial);
}

function validatePropertyNumber($property) {
    return preg_match('/^[a-zA-Z0-9\-_\/]+$/', $property);
}

/**
 * Audit logging
 */
function logSecurityEvent($event_type, $user_id = null, $details = '') {
    $log_file = __DIR__ . '/../logs/security.log';
    
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = sprintf(
        "[%s] %s - User ID: %s - IP: %s - User Agent: %s - Details: %s\n",
        $timestamp,
        $event_type,
        $user_id ?? 'guest',
        $ip_address,
        $user_agent,
        $details
    );
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Database query logging for debugging
 */
function logQuery($query, $params = []) {
    if ($_ENV['APP_ENV'] === 'development') {
        $log_file = __DIR__ . '/../logs/queries.log';
        
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] Query: %s - Params: %s\n",
            $timestamp,
            $query,
            json_encode($params)
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>