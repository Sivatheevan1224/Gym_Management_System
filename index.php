<?php
session_start();
require('db.php');

if (isset($_POST['login_user'])) {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $pwd = mysqli_real_escape_string($conn, $_POST['pwd']);

  $query = "SELECT * FROM login WHERE uname='$username' AND pwd='$pwd'";
  $results = mysqli_query($conn, $query);

  if (mysqli_num_rows($results) == 1) {
    $_SESSION['uname'] = $username;
    header("location:home.php");
    exit();
  } else {
    echo "<script>alert('Wrong username/password combination'); window.location.href='index.html';</script>";
  }
} else {
  header("location:index.html");
  exit();
}
?>
