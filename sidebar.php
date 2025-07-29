<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="../home/home.css">

<style>
/* Sidebar Styles */
.sidebar {
    width: 180px;
    background: linear-gradient(135deg, rgba(8, 8, 8, 0.95), rgba(18, 18, 18, 0.9));
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 100;
    top: 56px; /* Height of navbar */
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.sidebar-menu {
    list-style: none;
    padding: 20px 0;
    margin: 0;
}

.sidebar-item {
    margin-bottom: 5px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    border-radius: 4px;
    margin: 2px 8px;
}

.sidebar-link:hover, .sidebar-link.active {
    background-color: rgba(255, 255, 255, 0.1);
    color: #00d4ff;
    transform: translateX(4px);
}

.sidebar-link img {
    height: 18px;
    width: 18px;
    margin-right: 10px;
    opacity: 0.8;
    transition: all 0.3s ease;
}

.sidebar-link:hover img {
    opacity: 1;
    transform: scale(1.1);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: fixed;
        display: none;
        background: linear-gradient(135deg, rgba(8, 8, 8, 0.97), rgba(18, 18, 18, 0.95));
        backdrop-filter: blur(10px);
        padding-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    
    .sidebar.active {
        display: block;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .sidebar-menu {
        padding: 10px;
    }

    .sidebar-link {
        padding: 12px 20px;
        margin: 5px 8px;
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar-link:active {
        transform: scale(0.98);
    }
}
</style>

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