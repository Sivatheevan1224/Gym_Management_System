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
    height: 40px;
    width: 40px;
    margin-right: 15px;
    opacity: 0.8;
    transition: all 0.3s ease;
    border-radius: 50%;
    padding: 6px;
    background: linear-gradient(135deg, rgba(67, 100, 247, 0.1), rgba(111, 177, 252, 0.1));
    border: 1px solid rgba(67, 100, 247, 0.2);
    box-shadow: 0 2px 8px rgba(67, 100, 247, 0.2);
}

.sidebar-link:hover img {
    opacity: 1;
    transform: scale(1.2) rotate(5deg);
    background: linear-gradient(135deg, rgba(67, 100, 247, 0.3), rgba(111, 177, 252, 0.3));
    border-color: rgba(67, 100, 247, 0.5);
    box-shadow: 0 4px 15px rgba(67, 100, 247, 0.4);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .sidebar {
        width: 300px !important; /* Fixed width instead of percentage */
        min-width: 280px; /* Ensure minimum width */
        max-width: 350px; /* Maximum width for very large phones */
        height: auto;
        position: fixed;
        top: 70px; /* Adjust for navbar height */
        left: 0;
        transform: translateX(-100%); /* Changed to translateX for side slide */
        opacity: 0;
        visibility: hidden;
        background: linear-gradient(135deg, rgba(8, 8, 8, 0.97), rgba(18, 18, 18, 0.95));
        backdrop-filter: blur(10px);
        padding-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
        z-index: 999;
        border-radius: 0 15px 15px 0; /* Add rounded corners on the right */
    }
    
    .sidebar.active {
        transform: translateX(0) !important; /* Slide in from left */
        opacity: 1;
        visibility: visible;
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
        padding: 20px 15px; /* Increased padding for better spacing */
        width: 100%;
    }

    .sidebar-link {
        padding: 18px 25px; /* Increased padding for easier touch targets */
        margin: 10px 15px; /* Increased margins for better spacing */
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px; /* More rounded corners */
        font-size: 1.1rem; /* Larger font on mobile */
        width: calc(100% - 30px); /* Ensure full width usage */
        box-sizing: border-box;
    }

    .sidebar-link:active {
        transform: scale(0.98);
    }
    
    .sidebar-link:hover {
        background-color: rgba(255, 255, 255, 0.18);
        transform: translateX(0);
    }
    
    /* Ensure sidebar text doesn't wrap */
    .sidebar-link {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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