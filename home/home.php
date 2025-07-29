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
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            line-height: 1.6;
            background-image: url('../images/image1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: -1;
        }
        
        /* Navbar Styles */
        .navbar {
            background-color: rgba(52, 58, 64, 0.9);
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
        
        /* Main Container */
        .container {
            display: flex;
            margin-top: 70px;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 120px; /* Reduced width */
            background-color: #343a40;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
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
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(73, 80, 87, 0.9);
            color: white;
        }
        
        .sidebar-link img {
            height: 18px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 120px; /* Match reduced sidebar width */
            padding: 20px;
        }
        
        .page-header {
            padding-bottom: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(222, 226, 230, 0.2);
            display: flex;
            justify-content: center;
            margin-left: 300px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            margin-left: 300px;
        }
        
        .stats-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            border: 2px solid transparent;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-icon {
            margin-bottom: 15px;
        }
        
        .stats-icon img {
            height: 50px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .gym-card {
            border-color: #007bff;
        }
        
        .gym-card .stats-number {
            color: #007bff;
        }
        
        .member-card {
            border-color: #28a745;
        }
        
        .member-card .stats-number {
            color: #28a745;
        }
        
        .trainer-card {
            border-color: #ffc107;
        }
        
        .trainer-card .stats-number {
            color: #ffc107;
        }
        
        .payment-card {
            border-color: #17a2b8;
        }
        
        .payment-card .stats-number {
            color: #17a2b8;
        }
        
        /* Intro Section */
        .intro-section {
            margin-bottom: 40px;
            text-align: center;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: 300px;
        }
        
        .intro-section h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #343a40;
            animation: fadeIn 1s ease;
        }
        
        .intro-section p {
            font-size: 1.1rem;
            color: #6c757d;
            animation: slideUp 1s ease;
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
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .animate-scale-in {
            animation: scaleIn 0.5s ease forwards;
        }
        
        .animate-scale-in-delay {
            animation: scaleIn 0.5s ease 0.2s forwards;
            opacity: 0;
        }
        
        .animate-scale-in-delay-2 {
            animation: scaleIn 0.5s ease 0.4s forwards;
            opacity: 0;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 160px;
            }
            
            .main-content {
                margin-left: 160px;
                width: calc(100% - 160px);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                display: none; /* Hidden by default on mobile */
            }
            
            .sidebar.active {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .page-header {
                margin-left: 0;
            }

            .stats-grid {
                margin-left: 0;
                padding: 10px;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 10px;
            }
            
            .intro-section {
                margin: 10px;
                padding: 15px;
            }

            .intro-section h1 {
                font-size: 1.5rem;
            }
            
            .intro-section p {
                font-size: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<?php include('../navbar.php'); ?>

    <div class="container">
        <?php include('../sidebar.php'); ?>
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