<?php
session_start();
require('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // Use prepared statement
    $stmt = $conn->prepare("SELECT * FROM login WHERE uname = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password using password_verify()
        if (password_verify($password, $user['pwd'])) { // Compare entered password with hashed password
            $_SESSION['uname'] = $username;
            header("Location: home/home.php");
            exit();
        }
    }
    
    header("Location: index.html?error=1");
    exit();
} else {
    header("Location: index.html");
    exit();
}