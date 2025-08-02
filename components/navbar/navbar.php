<?php
ob_start(); // Start output buffering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="../home/home.css">
<link rel="stylesheet" href="../components/navbar/navbar.css">

<nav class="navbar">
    <div class="navbar-container">
        <button class="menu-toggle" id="menuToggle">☰</button>
        <a href="/GYM-MANAGEMENT-SYSTEM/home/home.php" class="navbar-brand">
            <img src="/GYM-MANAGEMENT-SYSTEM/images/dumbbell.png" alt="Gym Icon">
            GYM MANAGEMENT SYSTEM
        </a>
        
        <div class="user-info">
            <span class="user-name">
                <img src="/GYM-MANAGEMENT-SYSTEM/images/user.png" alt="User Icon">
                <?= htmlspecialchars($_SESSION['uname'] ?? 'Admin') ?>
            </span>
            <button class="logout-btn" id="logoutBtn">
                <img src="/GYM-MANAGEMENT-SYSTEM/images/logout.png" alt="Logout Icon">
                Logout
            </button>
        </div>
    </div>
</nav>

<!-- Logout Modal -->
<div class="modal" id="logoutModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5>
                <img src="/GYM-MANAGEMENT-SYSTEM/images/logout.png" alt="Logout">
                Confirm Logout
            </h5>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelLogout">
                <img src="/GYM-MANAGEMENT-SYSTEM/images/close.png" alt="Cancel">
                Cancel
            </button>
            <a href="/GYM-MANAGEMENT-SYSTEM/logout/logout.php" class="btn btn-danger">
                <img src="/GYM-MANAGEMENT-SYSTEM/images/logout.png" alt="Logout">
                Logout
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const closeModal = document.getElementById('closeModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    // Toggle sidebar - only use CSS classes, no manual display setting
    menuToggle.addEventListener('click', function() {
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    });
    
    // Hide sidebar when clicking outside of it on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            // Remove active class on larger screens to let CSS handle visibility
            sidebar.classList.remove('active');
        }
    });

    // Modal functionality
    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener('click', function() {
            logoutModal.style.display = 'flex';
        });
    }
    
    if (closeModal && logoutModal) {
        closeModal.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });
    }
    
    if (cancelLogout && logoutModal) {
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });
});
</script>