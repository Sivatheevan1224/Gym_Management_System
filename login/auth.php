<?php
session_start();
require_once('../classes/Database.php');

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isAuthenticated() {
    return isset($_SESSION['uname']) && !empty($_SESSION['uname']);
}

// For authenticated pages, redirect to login if not logged in
if (!isAuthenticated() && basename($_SERVER['PHP_SELF']) !== 'auth.php') {
    header("Location: ../index/index.html");
    exit();
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // Use prepared statement
    $stmt = $conn->prepare("SELECT id, uname, pwd, role, member_id FROM login WHERE uname = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['pwd'])) {
            // Set session variables
            $_SESSION['uname'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../home/home.php");
            } else {
                header("Location: ../member/member_dashboard.php");
            }
            exit();
        }
    }
    
    // If login fails
    header("Location: ../index/index.html?error=1");
    exit();
}
?>