<?php
require_once('../login/auth.php');
require_once('../db.php');

// Ensure the user is a member
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.html");
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../styles.css">
    <style>
        body {
            background-image: url('../images/image1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #333;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: -1;
        }
        
        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 80px auto 20px;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            background: rgba(52, 152, 219, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .welcome-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .member-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
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
        
        .member-card:nth-child(1) { animation-delay: 0.1s; }
        .member-card:nth-child(2) { animation-delay: 0.2s; }
        
        .member-card h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .member-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(52, 152, 219, 0.05);
            transition: background-color 0.3s ease;
        }
        
        .info-item:hover {
            background: rgba(52, 152, 219, 0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .highlight {
            color: #3498db;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
                margin-top: 60px;
            }
            
            .welcome-header h1 {
                font-size: 2rem;
            }
            
            .member-card {
                padding: 20px;
            }
            
            .info-item {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include('../navbar.php'); ?>
    
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
</body>
</html>