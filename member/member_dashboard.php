<?php
require_once('../login/auth.php');
require_once('../db.php');

// Ensure the user is a member
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index/index.html");
    exit();
}

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    $member_id = $_SESSION['member_id'];
    
    // Handle personal details update
    if (isset($_POST['update_details'])) {
        $name = trim($_POST['name']);
        $age = trim($_POST['age']);
        $dob = trim($_POST['dob']);
        $mobileno = trim($_POST['mobileno']);
        
        // Validation
        if (empty($name)) {
            $errors[] = "Name is required.";
        }
        
        if (!is_numeric($age) || $age < 12 || $age > 100) {
            $errors[] = "Age must be between 12 and 100.";
        }
        
        if (empty($dob)) {
            $errors[] = "Date of birth is required.";
        }
        
        if (!preg_match('/^[0-9]{10,15}$/', $mobileno)) {
            $errors[] = "Invalid mobile number format.";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("UPDATE member SET name = ?, age = ?, dob = ?, mobileno = ? WHERE mem_id = ?");
                $stmt->bind_param("sisss", $name, $age, $dob, $mobileno, $member_id);
                $stmt->execute();
                $success = "Personal details updated successfully!";
            } catch (mysqli_sql_exception $e) {
                $errors[] = "Error updating details: " . $e->getMessage();
            }
        }
    }
    
    // Handle username update
    if (isset($_POST['update_username'])) {
        $new_username = trim($_POST['new_username']);
        
        if (empty($new_username)) {
            $errors[] = "Username is required.";
        } elseif (strlen($new_username) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        } else {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM login WHERE uname = ? AND member_id != ?");
            $check_stmt->bind_param("ss", $new_username, $member_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $errors[] = "Username already exists. Please choose a different one.";
            } else {
                try {
                    $stmt = $conn->prepare("UPDATE login SET uname = ? WHERE member_id = ?");
                    $stmt->bind_param("ss", $new_username, $member_id);
                    $stmt->execute();
                    $_SESSION['uname'] = $new_username;
                    $success = "Username updated successfully!";
                } catch (mysqli_sql_exception $e) {
                    $errors[] = "Error updating username: " . $e->getMessage();
                }
            }
        }
    }
    
    // Handle password update
    if (isset($_POST['update_password'])) {
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT pwd FROM login WHERE member_id = ?");
            $stmt->bind_param("s", $member_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (!password_verify($current_password, $result['pwd'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE login SET pwd = ? WHERE member_id = ?");
                    $stmt->bind_param("ss", $hashed_password, $member_id);
                    $stmt->execute();
                    $success = "Password updated successfully!";
                } catch (mysqli_sql_exception $e) {
                    $errors[] = "Error updating password: " . $e->getMessage();
                }
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch member details
$member_id = $_SESSION['member_id'];
$stmt = $conn->prepare("
    SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name, l.uname 
    FROM member m
    LEFT JOIN payment p ON m.pay_id = p.pay_id
    LEFT JOIN trainer t ON m.trainer_id = t.trainer_id
    LEFT JOIN gym g ON m.gym_id = g.gym_id
    LEFT JOIN login l ON m.mem_id = l.member_id
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
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="cards-container">
            <!-- Personal Details Card -->
            <div class="member-card">
                <h2>📋 Your Personal Details</h2>
                <div class="member-info">
                    <div class="info-item">
                        <div class="info-label">Member ID</div>
                        <div class="info-value"><?= htmlspecialchars($member['mem_id']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?= htmlspecialchars($member['name']) ?></div>
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
                <button class="edit-btn" onclick="showEditDetails()">✏️ Edit Personal Details</button>
            </div>
            
            <!-- Account Settings Card -->
            <div class="member-card">
                <h2>⚙️ Account Settings</h2>
                <div class="member-info">
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= htmlspecialchars($member['uname']) ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Password</div>
                        <div class="info-value">••••••••</div>
                    </div>
                </div>
                <div class="button-group">
                    <button class="edit-btn" onclick="showEditUsername()">✏️ Change Username</button>
                    <button class="edit-btn" onclick="showEditPassword()">🔒 Change Password</button>
                </div>
            </div>
            
            <!-- Gym Information Card (Read-only) -->
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
                <p class="read-only-note">ℹ️ Contact gym administration to change gym, trainer, or payment plan</p>
            </div>
        </div>
    </div>

    <!-- Edit Personal Details Modal -->
    <div id="editDetailsModal" class="modal">
        <div class="modal-wrapper">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Personal Details</h3>
                    <span class="close" onclick="hideEditDetails()">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($member['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" id="age" name="age" value="<?= htmlspecialchars($member['age']) ?>" min="12" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dob">Date of Birth:</label>
                        <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($member['dob']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobileno">Mobile Number:</label>
                        <input type="tel" id="mobileno" name="mobileno" value="<?= htmlspecialchars($member['mobileno']) ?>" pattern="[0-9]{10,15}" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_details" class="btn-save">💾 Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="hideEditDetails()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Username Modal -->
    <div id="editUsernameModal" class="modal">
        <div class="modal-wrapper">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Change Username</h3>
                    <span class="close" onclick="hideEditUsername()">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="new_username">New Username:</label>
                        <input type="text" id="new_username" name="new_username" value="<?= htmlspecialchars($member['uname']) ?>" required minlength="3">
                        <div class="form-help">Username must be at least 3 characters long</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_username" class="btn-save">💾 Update Username</button>
                        <button type="button" class="btn-cancel" onclick="hideEditUsername()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Password Modal -->
    <div id="editPasswordModal" class="modal">
        <div class="modal-wrapper">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Change Password</h3>
                    <span class="close" onclick="hideEditPassword()">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <div class="form-help">Password must be at least 6 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_password" class="btn-save">🔒 Update Password</button>
                        <button type="button" class="btn-cancel" onclick="hideEditPassword()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Modal functions for editing personal details
    function showEditDetails() {
        document.getElementById('editDetailsModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideEditDetails() {
        document.getElementById('editDetailsModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Modal functions for editing username
    function showEditUsername() {
        document.getElementById('editUsernameModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideEditUsername() {
        document.getElementById('editUsernameModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Modal functions for editing password
    function showEditPassword() {
        document.getElementById('editPasswordModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function hideEditPassword() {
        document.getElementById('editPasswordModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Calculate age from DOB
    document.getElementById('dob').addEventListener('change', function() {
        const dob = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        
        document.getElementById('age').value = age;
    });

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        
        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = ['editDetailsModal', 'editUsernameModal', 'editPasswordModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    };

    // Close modals on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            hideEditDetails();
            hideEditUsername();
            hideEditPassword();
        }
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