<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isAuthenticated() {
    return isset($_SESSION['uname']) && !empty($_SESSION['uname']);
}

// Redirect if not authenticated
if (!isAuthenticated() && basename($_SERVER['PHP_SELF']) != 'index.php') {
    header("Location: ../index.php");
    exit();
}

// Hashes the password using bcrypt algorithm for secure storage
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verifies the provided password against the stored hash
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}