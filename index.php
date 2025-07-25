<?php
session_start();
require('db.php'); // Make sure $conn is defined in db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['pwd']);

    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM login WHERE uname = ? AND pwd = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $_SESSION['uname'] = $username;
        header("Location: home/home.php");
        exit();
    } else {
        // Redirect back with error flag
        header("Location: index.html?error=1");
        exit();
    }
} else {
    header("Location: index.html");
    exit();
}
?>