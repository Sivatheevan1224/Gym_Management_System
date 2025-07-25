<?php
include("../login/auth.php");
require('../db.php'); // Include DB connection

$info = isset($_GET['info']) ? $_GET['info'] : "";

// Fetch counts
$gym_count = $conn->query("SELECT COUNT(*) AS total FROM gym")->fetch_assoc()['total'] ?? 0;
$member_count = $conn->query("SELECT COUNT(*) AS total FROM member")->fetch_assoc()['total'] ?? 0;
$trainer_count = $conn->query("SELECT COUNT(*) AS total FROM trainer")->fetch_assoc()['total'] ?? 0;

// Helper to check if section is active
function isSectionActive($info, $section) {
    return strpos($info, $section) !== false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Gym Management System</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />

  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
  
  <!-- TailwindCSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="home.css" />
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand fs-4 fw-bold" href="home.php">
        <i class="fas fa-dumbbell me-2"></i>GYM MANAGEMENT SYSTEM
      </a>
      <button class="btn btn-danger logout-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fas fa-sign-out-alt me-1"></i> Logout
      </button>
    </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar pt-5">
      <div class="list-group">
        <a class="list-group-item list-group-item-action fw-bold text-uppercase sidebar-toggle" data-target="gymSubmenu">
          <i class="fas fa-dumbbell me-2"></i> Gym <i class="fas fa-chevron-down toggle-icon"></i>
        </a>
        <div class="submenu <?php if(isSectionActive($info, 'gym')) echo 'show'; ?>" id="gymSubmenu">
          <a href="home.php?info=add_gym" class="list-group-item list-group-item-action submenu-item <?php if($info=='add_gym') echo 'active'; ?>">Add Gym</a>
          <a href="home.php?info=manage_gym" class="list-group-item list-group-item-action submenu-item <?php if($info=='manage_gym') echo 'active'; ?>">View Gyms</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3 sidebar-toggle" data-target="paymentSubmenu">
          <i class="fas fa-credit-card me-2"></i> Payment Department <i class="fas fa-chevron-down toggle-icon"></i>
        </a>
        <div class="submenu <?php if(isSectionActive($info, 'payment')) echo 'show'; ?>" id="paymentSubmenu">
          <a href="home.php?info=add_payment" class="list-group-item list-group-item-action submenu-item <?php if($info=='add_payment') echo 'active'; ?>">Add Payment Area</a>
          <a href="home.php?info=manage_payment" class="list-group-item list-group-item-action submenu-item <?php if($info=='manage_payment') echo 'active'; ?>">View Payment Areas</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3 sidebar-toggle" data-target="membersSubmenu">
          <i class="fas fa-users me-2"></i> Members <i class="fas fa-chevron-down toggle-icon"></i>
        </a>
        <div class="submenu <?php if(isSectionActive($info, 'member')) echo 'show'; ?>" id="membersSubmenu">
          <a href="home.php?info=add_member" class="list-group-item list-group-item-action submenu-item <?php if($info=='add_member') echo 'active'; ?>">Add Member</a>
          <a href="home.php?info=manage_member" class="list-group-item list-group-item-action submenu-item <?php if($info=='manage_member') echo 'active'; ?>">View Members</a>
        </div>

        <a class="list-group-item list-group-item-action fw-bold text-uppercase mt-3 sidebar-toggle" data-target="trainersSubmenu">
          <i class="fas fa-user-tie me-2"></i> Trainers <i class="fas fa-chevron-down toggle-icon"></i>
        </a>
        <div class="submenu <?php if(isSectionActive($info, 'trainer')) echo 'show'; ?>" id="trainersSubmenu">
          <a href="home.php?info=add_trainer" class="list-group-item list-group-item-action submenu-item <?php if($info=='add_trainer') echo 'active'; ?>">Add Trainer</a>
          <a href="home.php?info=manage_trainer" class="list-group-item list-group-item-action submenu-item <?php if($info=='manage_trainer') echo 'active'; ?>">View Trainers</a>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main id="main-content" class="col-md-10 py-5 px-5">
      <?php if ($info === ""): ?>
        <!-- Show welcome/about gym content -->
        <section id="intro-section" class="mb-5">
          <h1 class="animate-fade-in">Welcome to Our Gym Management System</h1>
          <p class="animate-slide-up">
            Our gym offers top-notch fitness equipment, expert trainers, and a friendly atmosphere to help you achieve your fitness goals.
            Manage gyms, trainers, members, and payments effortlessly with our comprehensive system.
          </p>
          <p class="animate-slide-up-delay">Join us and transform your fitness journey today!</p>
        </section>

  <!-- Stats Cards -->
<div class="row g-4 mb-5">
  <div class="col-md-4">
    <div class="card stats-card h-100 animate-scale-in border border-primary border-2">
      <div class="card-body text-center">
        <div class="stats-icon mb-3">
          <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
        </div>
        <h3 class="card-title text-primary fw-bold"><?php echo $gym_count; ?></h3>
        <p class="card-text text-muted">Total Gyms</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stats-card h-100 animate-scale-in-delay border border-success border-2">
      <div class="card-body text-center">
        <div class="stats-icon mb-3">
          <i class="bi bi-people text-success" style="font-size: 3rem;"></i>
        </div>
        <h3 class="card-title text-success fw-bold"><?php echo $member_count; ?></h3>
        <p class="card-text text-muted">Total Members</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stats-card h-100 animate-scale-in-delay-2 border border-warning border-2">
      <div class="card-body text-center">
        <div class="stats-icon mb-3">
          <i class="bi bi-person-badge text-warning" style="font-size: 3rem;"></i>
        </div>
        <h3 class="card-title text-warning fw-bold"><?php echo $trainer_count; ?></h3>
        <p class="card-text text-muted">Total Trainers</p>
      </div>
    </div>
  </div>
</div>
      <?php else: ?>
        <div class="card p-4">
          <?php
            // Load the requested page inside card
            switch ($info) {
              case "add_gym":
                include('../gym/add_gym.php');
                break;
              case "add_payment":
                include('../payment/add_payment.php');
                break;
              case "manage_payment":
                include('../payment/manage_payment.php');
                break;
              case "add_member":
                include('../member/add_member.php');
                break;
              case "manage_member":
                include('../member/manage_member.php');
                break;
              case "add_trainer":
                include('../trainer/add_trainer.php');
                break;
              case "manage_trainer":
                include('../trainer/manage_trainer.php');
                break;
              case "manage_gym":
                include('../gym/manage_gym.php');
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

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white border border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">
          <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="lead">Are you sure you want to logout?</p>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cancel
        </button>
        <a href="../logout/logout.php" class="btn btn-danger">
          <i class="fas fa-sign-out-alt me-1"></i> Logout
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Simple sidebar toggle functionality
    const sidebarToggles = document.querySelectorAll('.sidebar-toggle');
    
    sidebarToggles.forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('data-target');
        const targetSubmenu = document.getElementById(targetId);
        const toggleIcon = this.querySelector('.toggle-icon');
        
        // Close all other submenus
        document.querySelectorAll('.submenu').forEach(submenu => {
          if (submenu !== targetSubmenu) {
            submenu.classList.remove('show');
            // Reset other toggle icons
            const otherToggle = document.querySelector(`[data-target="${submenu.id}"] .toggle-icon`);
            if (otherToggle) {
              otherToggle.style.transform = 'rotate(0deg)';
            }
          }
        });
        
        // Toggle current submenu
        if (targetSubmenu.classList.contains('show')) {
          targetSubmenu.classList.remove('show');
          toggleIcon.style.transform = 'rotate(0deg)';
        } else {
          targetSubmenu.classList.add('show');
          toggleIcon.style.transform = 'rotate(180deg)';
        }
      });
    });
    
    // Set initial state for active sections
    const info = '<?php echo $info; ?>';
    if (info.includes('gym')) {
      document.getElementById('gymSubmenu').classList.add('show');
      document.querySelector('[data-target="gymSubmenu"] .toggle-icon').style.transform = 'rotate(180deg)';
    } else if (info.includes('payment')) {
      document.getElementById('paymentSubmenu').classList.add('show');
      document.querySelector('[data-target="paymentSubmenu"] .toggle-icon').style.transform = 'rotate(180deg)';
    } else if (info.includes('member')) {
      document.getElementById('membersSubmenu').classList.add('show');
      document.querySelector('[data-target="membersSubmenu"] .toggle-icon').style.transform = 'rotate(180deg)';
    } else if (info.includes('trainer')) {
      document.getElementById('trainersSubmenu').classList.add('show');
      document.querySelector('[data-target="trainersSubmenu"] .toggle-icon').style.transform = 'rotate(180deg)';
    }
  });
</script>

</body>
</html>