<?php ob_start(); ?>
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
    <link rel="stylesheet" href="home1.css">
</head>
<body>
<?php include('../components/navbar/navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../components/sidebar/sidebar.php'); ?>
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
            </div>
            
            <section class="intro-section">
                <h1>Welcome to Gym Management System</h1>
                <p>
                    Manage your gym facilities, members, trainers, and payments all in one place.
                    The system provides comprehensive tools to streamline your gym operations.
                </p>
            </section>
            
            <div class="stats-grid">
                <div class="stats-card gym-card animate-scale-in">
                    <div class="stats-icon">
                        <img src="../images/dumbbell.png" alt="Gym">
                    </div>
                    <h3 class="stats-number"><?= $gym_count ?></h3>
                    <p class="stats-text">Total Gyms</p>
                </div>
                
                <div class="stats-card member-card animate-scale-in-delay">
                    <div class="stats-icon">
                        <img src="../images/member.png" alt="Members">
                    </div>
                    <h3 class="stats-number"><?= $member_count ?></h3>
                    <p class="stats-text">Total Members</p>
                </div>
                
                <div class="stats-card trainer-card animate-scale-in-delay">
                    <div class="stats-icon">
                        <img src="../images/trainer.png" alt="Trainers">
                    </div>
                    <h3 class="stats-number"><?= $trainer_count ?></h3>
                    <p class="stats-text">Total Trainers</p>
                </div>
                
                <div class="stats-card payment-card animate-scale-in-delay-2">
                    <div class="stats-icon">
                        <img src="../images/payment.png" alt="Payments">
                    </div>
                    <h3 class="stats-number"><?= $payment_count ?></h3>
                    <p class="stats-text">Payment Plans</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Only keep modal functionality
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutModal = document.getElementById('logoutModal');
            const closeModal = document.getElementById('closeModal');
            const cancelLogout = document.getElementById('cancelLogout');
            
            logoutBtn.addEventListener('click', function() {
                logoutModal.style.display = 'flex';
            });
            
            closeModal.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });
            
            cancelLogout.addEventListener('click', function() {
                logoutModal.style.display = 'none';
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === logoutModal) {
                    logoutModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>