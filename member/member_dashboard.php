<?php
require_once('../login/auth.php');
require_once('../db.php');

// Ensure the user is a member
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index/index.html");
    exit();
}

// Fetch member details
$member_id = $_SESSION['member_id'];
$stmt = $conn->prepare("
    SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name 
    FROM member m
    LEFT JOIN payment p ON m.pay_id = p.pay_id
    LEFT JOIN trainer t ON m.trainer_id = t.trainer_id
    LEFT JOIN gym g ON m.gym_id = g.gym_id
    WHERE m.mem_id = ?
");
$stmt->bind_param("s", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Member Dashboard - <?= htmlspecialchars($member['name']) ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="../style/styles.css">
    <link rel="stylesheet" href="../style/responsive.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="../components/navbar/navbar.css">
    <link rel="stylesheet" href="../components/sidebar/sidebar.css">
</head>
<body>
    <?php include('../components/navbar/navbar.php'); ?>
    
    <div class="dashboard-container">
        <div class="welcome-header">
            <h1>Welcome, <?= htmlspecialchars($member['name']) ?>!</h1>
        </div>
        
        <div class="cards-container">
            <div class="member-card">
                <h2>📋 Your Membership Details</h2>
                <div class="member-info">
                    <div class="info-item">
                        <div class="info-label">Member ID</div>
                        <div class="info-value"><?= htmlspecialchars($member['mem_id']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Age</div>
                        <div class="info-value"><?= htmlspecialchars($member['age']) ?> years</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?= htmlspecialchars($member['dob']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value"><?= htmlspecialchars($member['mobileno']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="member-card">
                <h2>🏋️‍♂️ Gym Information</h2>
                <div class="member-info">
                    <div class="info-item">
                        <div class="info-label">Gym Location</div>
                        <div class="info-value highlight"><?= htmlspecialchars($member['gym_name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Personal Trainer</div>
                        <div class="info-value highlight"><?= htmlspecialchars($member['trainer_name']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Payment Plan</div>
                        <div class="info-value highlight">LKR <?= htmlspecialchars($member['amount']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Set current year in footer if present
    if (document.getElementById("year")) {
        document.getElementById("year").textContent = new Date().getFullYear();
    }

    // Logout functionality
    function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function hideLogoutModal() {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Close modal on clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('logoutModal');
        if (modal && event.target === modal) {
            hideLogoutModal();
        }
    };

    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            hideLogoutModal();
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Animation for cards on load
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.member-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
            }, 200 * (index + 1));
        });
    });
    </script>
</body>
</html>