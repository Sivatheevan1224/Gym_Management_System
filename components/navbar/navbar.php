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
    color: #6FB1FC;
    background: linear-gradient(45deg, #4364F7, #6FB1FC);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-decoration: none;
    transition: all 0.3s ease;
}

.navbar-brand img {
    height: 50px;
    margin-right: 15px;
    border-radius: 50%;
    padding: 6px;
    background: linear-gradient(135deg, rgba(67, 100, 247, 0.1), rgba(111, 177, 252, 0.1));
    border: 1px solid rgba(67, 100, 247, 0.3);
    box-shadow: 0 3px 10px rgba(67, 100, 247, 0.3);
    transition: all 0.3s ease;
}

.navbar-brand:hover img {
    transform: scale(1.05) rotate(-2deg);
    background: linear-gradient(135deg, rgba(67, 100, 247, 0.2), rgba(111, 177, 252, 0.2));
    border-color: rgba(67, 100, 247, 0.5);
    box-shadow: 0 5px 15px rgba(67, 100, 247, 0.5);
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
    height: 35px;
    margin-right: 8px;
    border-radius: 50%;
    padding: 4px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.user-name:hover img {
    transform: scale(1.1);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
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
    font-weight: bold;
}

.logout-btn:hover {
    background-color: #c82333;
}

.logout-btn img {
    height: 24px;
    margin-right: 8px;
    border-radius: 50%;
    padding: 3px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.logout-btn:hover img {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
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

/* Remove all text decorations from modal elements */
.modal * {
    text-decoration: none !important;
}

.modal a {
    text-decoration: none !important;
}

.modal a:hover,
.modal a:focus,
.modal a:active,
.modal a:visited {
    text-decoration: none !important;
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
    height: 28px;
    margin-right: 12px;
    border-radius: 50%;
    padding: 4px;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
    border: 1px solid rgba(220, 53, 69, 0.3);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
    transition: all 0.3s ease;
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
    text-decoration: none !important;
}

.btn:hover,
.btn:focus,
.btn:active,
.btn:visited {
    text-decoration: none !important;
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
    text-decoration: none !important;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #c82333;
    text-decoration: none !important;
}

.btn-danger:focus,
.btn-danger:active,
.btn-danger:visited {
    text-decoration: none !important;
}

/* Specifically target the logout link in modal */
.modal-footer a.btn-danger {
    text-decoration: none !important;
}

.modal-footer a.btn-danger:hover,
.modal-footer a.btn-danger:focus,
.modal-footer a.btn-danger:active,
.modal-footer a.btn-danger:visited {
    text-decoration: none !important;
}

.btn img {
    height: 20px;
    margin-right: 8px;
    border-radius: 50%;
    padding: 3px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.btn:hover img {
    transform: scale(1.05);
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
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
        <a href="../home/home.php" class="navbar-brand">
            <img src="../images/dumbbell.png" alt="Gym Icon">
            GYM MANAGEMENT SYSTEM
        </a>
        
        <div class="user-info">
            <span class="user-name">
                <img src="../images/user.png" alt="User Icon">
                <?= htmlspecialchars($_SESSION['uname'] ?? 'Admin') ?>
            </span>
            <button class="logout-btn" id="logoutBtn">
                <img src="../images/logout.png" alt="Logout Icon">
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
                <img src="../images/logout.png" alt="Logout">
                Confirm Logout
            </h5>
            <button class="close-btn" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" id="cancelLogout">
                <img src="../images/close.png" alt="Cancel">
                Cancel
            </button>
            <a href="../logout/logout.php" class="btn btn-danger">
                <img src="../images/logout.png" alt="Logout">
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
    
    // Toggle sidebar with improved mobile functionality
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle the active class
            sidebar.classList.toggle('active');
            
            // Add visual feedback to toggle button
            menuToggle.style.transform = sidebar.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
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
    
    if (logoutModal) {
        window.addEventListener('click', function(event) {
            if (event.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });
    }
});
</script>