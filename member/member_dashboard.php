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
            margin-top: 25px;
            padding: 0 10px;
        }
        
        .member-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(51, 65, 85, 0.9));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.25),
                0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        
        .member-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #4f46e5);
            background-size: 200% 100%;
            animation: gradientShift 3s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .member-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 8px 20px rgba(0, 0, 0, 0.2);
            border-color: rgba(79, 70, 229, 0.4);
        }
        
        .member-card:nth-child(1) { animation-delay: 0.2s; }
        .member-card:nth-child(2) { animation-delay: 0.4s; }
        
        .member-card h2 {
            color: #f8fafc;
            font-size: 1.4rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid transparent;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            background-size: 0% 2px;
            background-repeat: no-repeat;
            background-position: left bottom;
            transition: background-size 0.3s ease;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .member-card:hover h2 {
            background-size: 100% 2px;
        }
        
        .member-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 0;
            padding: 15px 18px;
            border-radius: 10px;
            background: linear-gradient(135deg, 
                rgba(79, 70, 229, 0.15), 
                rgba(124, 58, 237, 0.1));
            border: 1px solid rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(79, 70, 229, 0.2), 
                transparent);
            transition: left 0.5s ease;
        }
        
        .info-item:hover {
            background: linear-gradient(135deg, 
                rgba(79, 70, 229, 0.25), 
                rgba(124, 58, 237, 0.18));
            border-color: rgba(79, 70, 229, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.25);
        }
        
        .info-item:hover::before {
            left: 100%;
        }
        
        .info-label {
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 6px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }
        
        .info-value {
            color: #f1f5f9;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .highlight {
            color: #ffffff !important;
            font-weight: 700;
            position: relative;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        /* Enhanced mobile responsiveness */
        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 0 5px;
            }
            
            .member-card {
                padding: 18px 15px;
                margin: 0 5px;
            }
            
            .member-card h2 {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }
            
            .member-info {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .info-item {
                padding: 12px 15px;
            }
            
            .info-value {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .member-card {
                padding: 15px 12px;
                border-radius: 15px;
            }
            
            .member-card h2 {
                font-size: 1.2rem;
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }
            
            .info-item {
                padding: 10px 12px;
            }
            
            .info-label {
                font-size: 0.75rem;
            }
            
            .info-value {
                font-size: 0.95rem;
            }
        }
    </style>
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
</body>
</html>