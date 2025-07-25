<?php

require_once('../db.php');

// Fetch counts for dashboard
try {
    $gym_count = $conn->query("SELECT COUNT(*) FROM gym")->fetch_row()[0];
    $member_count = $conn->query("SELECT COUNT(*) FROM member")->fetch_row()[0];
    $trainer_count = $conn->query("SELECT COUNT(*) FROM trainer")->fetch_row()[0];
    $payment_count = $conn->query("SELECT COUNT(*) FROM payment")->fetch_row()[0];
} catch (mysqli_sql_exception $e) {
    error_log("Dashboard count error: " . $e->getMessage());
    $gym_count = $member_count = $trainer_count = $payment_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Gym Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="home.css">
    <style>
        #sidebar {
            width: 180px; /* Reduced width to match sidebar.php */
        }

        .sidebar .list-group-item {
            font-size: 0.9rem; /* Adjusted font size for consistency */
        }
    </style>
</head>
<body>
    <!-- Consolidated Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fs-4 fw-bold" href="home.php">
                <i class="fas fa-dumbbell me-2"></i>GYM MANAGEMENT SYSTEM
            </a>
            
            <div class="d-flex align-items-center">
                <span class="text-light me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= htmlspecialchars($_SESSION['uname'] ?? 'Admin') ?>
                </span>
                <button class="btn btn-danger logout-btn" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </button>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Consolidated Sidebar -->
            <nav id="sidebar" class="col-md-2 d-none d-md-block sidebar">
                <div class="list-group list-group-flush">
                    <a href="home.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    
                    <a href="../gym/manage_gym.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dumbbell me-2"></i> Manage Gyms
                    </a>
                    
                    <a href="manage_trainer.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-tie me-2"></i> Manage Trainers
                    </a>
                    
                    <a href="manage_member.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Manage Members
                    </a>
                    
                    <a href="manage_payment.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-credit-card me-2"></i> Manage Payments
                    </a>
                </div>
            </nav>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-center pt-3 pb-2 mb-3 border-bottom">
              <h1 class="h2">Dashboard</h1>
            </div>
                <section id="intro-section" class="mb-5">
                    <h1 class="animate-fade-in">Welcome to Gym Management System</h1>
                    <p class="animate-slide-up">
                        Manage your gym facilities, members, trainers, and payments all in one place.
                        The system provides comprehensive tools to streamline your gym operations.
                    </p>
                </section>
                
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card stats-card h-100 animate-scale-in border border-primary border-2">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="fas fa-dumbbell fa-3x text-primary"></i>
                                </div>
                                <h3 class="card-title text-primary fw-bold"><?= $gym_count ?></h3>
                                <p class="card-text">Total Gyms</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stats-card h-100 animate-scale-in-delay border border-success border-2">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="fas fa-users fa-3x text-success"></i>
                                </div>
                                <h3 class="card-title text-success fw-bold"><?= $member_count ?></h3>
                                <p class="card-text">Total Members</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stats-card h-100 animate-scale-in-delay border border-warning border-2">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="fas fa-user-tie fa-3x text-warning"></i>
                                </div>
                                <h3 class="card-title text-warning fw-bold"><?= $trainer_count ?></h3>
                                <p class="card-text">Total Trainers</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stats-card h-100 animate-scale-in-delay-2 border border-info border-2">
                            <div class="card-body text-center">
                                <div class="stats-icon mb-3">
                                    <i class="fas fa-credit-card fa-3x text-info"></i>
                                </div>
                                <h3 class="card-title text-info fw-bold"><?= $payment_count ?></h3>
                                <p class="card-text">Payment Plans</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Logout Modal (still needed) -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>