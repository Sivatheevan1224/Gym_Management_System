<?php
ob_start(); // Start output buffering
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="/GYM-MANAGEMENT-SYSTEM/home/home.css">

<style>
/* Navbar Styles */
.navbar {
    background-color: #343a40;
    color: white;
    padding: 15px 20px;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.navbar-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 15px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    text-decoration: none;
}

.navbar-brand img {
    height: 30px;
    margin-right: 10px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-name {
    display: flex;
    align-items: center;
    color: white;
}

.user-name img {
    height: 20px;
    margin-right: 5px;
}

.logout-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background-color 0.3s ease;
}

.logout-btn:hover {
    background-color: #c82333;
}

.logout-btn img {
    height: 16px;
    margin-right: 5px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #343a40;
    color: white;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    border: 1px solid #6c757d;
    overflow: hidden;
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #6c757d;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    display: flex;
    align-items: center;
    font-size: 1.25rem;
}

.modal-header h5 img {
    height: 20px;
    margin-right: 10px;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.modal-body p {
    font-size: 1.1rem;
    margin-bottom: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #6c757d;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn {
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid #6c757d;
    color: white;
}

.btn-outline:hover {
    background-color: #6c757d;
}

.btn-danger {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #c82333;
}

.btn img {
    height: 16px;
    margin-right: 5px;
}

/* Menu Toggle Button */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0 15px;
    transition: all 0.3s ease;
}

.menu-toggle:hover {
    color: #00d4ff;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }
    
    .navbar-container {
        position: relative;
    }

    .user-info {
        margin-left: auto;
    }

    .user-name {
        display: none;
    }
}
</style>

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
    
    // Toggle sidebar
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Hide sidebar on window resize if screen becomes larger
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.style.display = 'block';
        } else {
            sidebar.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        }
    });

    // Existing modal code
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