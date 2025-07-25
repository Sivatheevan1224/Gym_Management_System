<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../home/home.css">

<style>
#sidebar {
    width: 180px; /* Reduced width */
}

.sidebar .list-group-item {
    font-size: 0.9rem; /* Adjusted font size for smaller sidebar */
}
</style>

<nav id="sidebar" class="col-md-2 d-none d-md-block sidebar">
    <div class="list-group list-group-flush">
        <a href="../home/home.php" class="list-group-item list-group-item-action">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        
        <a href="../gym/manage_gym.php" class="list-group-item list-group-item-action">
            <i class="fas fa-dumbbell me-2"></i> Manage Gyms
        </a>
        
        <a href="../trainer/manage_trainer.php" class="list-group-item list-group-item-action">
            <i class="fas fa-user-tie me-2"></i> Manage Trainers
        </a>
        
        <a href="../member/manage_member.php" class="list-group-item list-group-item-action">
            <i class="fas fa-users me-2"></i> Manage Members
        </a>
        
        <a href="../payment/manage_payment.php" class="list-group-item list-group-item-action">
            <i class="fas fa-credit-card me-2"></i> Manage Payments
        </a>
    </div>
</nav>