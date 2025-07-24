<?php
include("auth.php");

$info = isset($_GET['info']) ? $_GET['info'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Gym Management System</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="home.css" />
</head>

<body>

<nav class="navbar navbar-expand-md navbar-dark shadow-sm fixed-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fs-4 fw-bold" href="home.php">GYM MANAGEMENT SYSTEM</a>
    <div class="d-flex align-items-center">
      <a href="logout.html" class="logout-link nav-link px-3">Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar pt-5">
      <div class="list-group">
        <a class="list-group-item list-group-item-action fw-bold text-uppercase" data-bs-toggle="collapse" href="#collapseGym" role="button" aria-expanded="false" aria-controls="collapseGym">
          <i class="fas fa-dumbbell me-2"></i> Gym
        </a>
        <div class="collapse" id="collapseGym" data-bs-parent="#sidebar">
          <a href="home.php?info=add_gym" class="list-group-item list-group-item-action <?php if($info=='add_gym') echo 'active'; ?>">Add Gym</a>
          <a href="home.php?info=manage_gym" class="list-group-item list-group-item-action <?php if($info=='manage_gym') echo 'active'; ?>">View Gyms</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3" data-bs-toggle="collapse" href="#collapsePayment" role="button" aria-expanded="false" aria-controls="collapsePayment">
          <i class="fas fa-credit-card me-2"></i> Payment Department
        </a>
        <div class="collapse" id="collapsePayment" data-bs-parent="#sidebar">
          <a href="home.php?info=add_payment" class="list-group-item list-group-item-action <?php if($info=='add_payment') echo 'active'; ?>">Add Payment Area</a>
          <a href="home.php?info=manage_payment" class="list-group-item list-group-item-action <?php if($info=='manage_payment') echo 'active'; ?>">View Payment Areas</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3" data-bs-toggle="collapse" href="#collapseMembers" role="button" aria-expanded="false" aria-controls="collapseMembers">
          <i class="fas fa-users me-2"></i> Members
        </a>
        <div class="collapse" id="collapseMembers" data-bs-parent="#sidebar">
          <a href="home.php?info=add_member" class="list-group-item list-group-item-action <?php if($info=='add_member') echo 'active'; ?>">Add Member</a>
          <a href="home.php?info=manage_member" class="list-group-item list-group-item-action <?php if($info=='manage_member') echo 'active'; ?>">View Members</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3" data-bs-toggle="collapse" href="#collapseTrainers" role="button" aria-expanded="false" aria-controls="collapseTrainers">
          <i class="fas fa-user-tie me-2"></i> Trainers
        </a>
        <div class="collapse" id="collapseTrainers" data-bs-parent="#sidebar">
          <a href="home.php?info=add_trainer" class="list-group-item list-group-item-action <?php if($info=='add_trainer') echo 'active'; ?>">Add Trainer</a>
          <a href="home.php?info=manage_trainer" class="list-group-item list-group-item-action <?php if($info=='manage_trainer') echo 'active'; ?>">View Trainers</a>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main id="main-content" class="col-md-10 py-5 px-5">
      <?php if ($info === ""): ?>
        <!-- Show welcome/about gym content -->
        <section id="intro-section" class="mb-5">
          <h1>Welcome to Our Gym Management System</h1>
          <p>
            Our gym offers top-notch fitness equipment, expert trainers, and a friendly atmosphere to help you achieve your fitness goals.
            Manage gyms, trainers, members, and payments effortlessly with our comprehensive system.
          </p>
          <p>Join us and transform your fitness journey today!</p>
        </section>
      <?php else: ?>
        <div class="card p-4">
          <?php
            // Load the requested page inside card
            switch ($info) {
              case "add_gym":
                include('add_gym.php');
                break;
              case "add_payment":
                include('add_payment.php');
                break;
              case "manage_payment":
                include('manage_payment.php');
                break;
              case "add_member":
                include('add_member.php');
                break;
              case "manage_member":
                include('manage_member.php');
                break;
              case "add_trainer":
                include('add_trainer.php');
                break;
              case "manage_trainer":
                include('manage_trainer.php');
                break;
              case "manage_gym":
                include('manage_gym.php');
                break;
              default:
                echo "<p>Invalid selection. Please choose from the menu.</p>";
            }
          ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
