<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="../home/home.css">
<link rel="stylesheet" href="../components/sidebar/sidebar.css">

<nav class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="../home/home.php" class="sidebar-link">
                <img src="../images/dashboard.png" alt="Dashboard">
                Dashboard
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../gym/manage_gym.php" class="sidebar-link">
                <img src="../images/dumbbell.png" alt="Gym">
                Manage Gyms
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../trainer/manage_trainer.php" class="sidebar-link">
                <img src="../images/trainer.png" alt="Trainer">
                Manage Trainers
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../member/manage_member.php" class="sidebar-link">
                <img src="../images/member.png" alt="Members">
                Manage Members
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../payment/manage_payment.php" class="sidebar-link">
                <img src="../images/payment.png" alt="Payment">
                Manage Payments
            </a>
        </li>
    </ul>
</nav>

<script>
// Toggle sidebar on mobile
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality is handled by the main page script
});
</script>