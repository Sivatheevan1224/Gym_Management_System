<?php
require_once('../login/auth.php');
require_once('../db.php');

$action = $_GET['action'] ?? '';
$mem_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$member_data = [];

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize inputs
    $mem_id = $conn->real_escape_string(trim($_POST['mem_id'] ?? ''));
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $age = $conn->real_escape_string(trim($_POST['age'] ?? ''));
    $dob = $conn->real_escape_string(trim($_POST['dob'] ?? ''));
    $mobileno = $conn->real_escape_string(trim($_POST['mobileno'] ?? ''));
    $pay_id = $conn->real_escape_string(trim($_POST['pay_id'] ?? ''));
    $trainer_id = $conn->real_escape_string(trim($_POST['trainer_id'] ?? ''));
    $gym_id = $conn->real_escape_string(trim($_POST['gym_id'] ?? ''));
    
    // Validation
    if (empty($mem_id)) {
        $errors[] = "Member ID is required.";
    }
    
    if (empty($name)) {
        $errors[] = "Member name is required.";
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
    
    if (empty($pay_id)) {
        $errors[] = "Payment plan is required.";
    }
    
    if (empty($trainer_id)) {
        $errors[] = "Trainer is required.";
    }
    
    if (empty($gym_id)) {
        $errors[] = "Gym is required.";
    }
    
    // Check for duplicate ID when adding
    if ($_POST['action'] === 'add') {
        $check = $conn->prepare("SELECT mem_id FROM member WHERE mem_id = ?");
        $check->bind_param("s", $mem_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors[] = "Member ID already exists.";
        }
        $check->close();
    }
    
    // Process form if no errors
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO member (mem_id, name, age, dob, mobileno, pay_id, trainer_id, gym_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssss", $mem_id, $name, $age, $dob, $mobileno, $pay_id, $trainer_id, $gym_id);
                $stmt->execute();
                $success = "Member added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = $conn->real_escape_string(trim($_POST['original_id'] ?? ''));
                $stmt = $conn->prepare("UPDATE member SET mem_id = ?, name = ?, age = ?, dob = ?, mobileno = ?, pay_id = ?, trainer_id = ?, gym_id = ? WHERE mem_id = ?");
                $stmt->bind_param("ssissssss", $mem_id, $name, $age, $dob, $mobileno, $pay_id, $trainer_id, $gym_id, $original_id);
                $stmt->execute();
                $success = "Member updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_member.php?success=" . urlencode($success));
            exit();
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle delete action
if ($action === 'delete' && !empty($mem_id)) {
    try {
        $stmt = $conn->prepare("DELETE FROM member WHERE mem_id = ?");
        $stmt->bind_param("s", $mem_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $success = "Member deleted successfully!";
        } else {
            $errors[] = "Member not found.";
        }
        
        // Redirect to avoid refresh issues
        header("Location: manage_member.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting member: " . $e->getMessage();
    }
}

// Fetch member data for editing
if ($action === 'edit' && !empty($mem_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM member WHERE mem_id = ?");
        $stmt->bind_param("s", $mem_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $member_data = $result->fetch_assoc();
        } else {
            $errors[] = "Member not found.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching member data.";
    }
}

// Fetch dropdown options
try {
    $payment_options = $conn->query("SELECT pay_id, amount FROM payment");
    $trainer_options = $conn->query("SELECT trainer_id, name FROM trainer");
    $gym_options = $conn->query("SELECT gym_id, gym_name FROM gym");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching dropdown options.";
}

// Fetch all members for listing
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count total records for pagination
    if (!empty($search)) {
        $search_term = "%$search%";
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM member WHERE mem_id LIKE ? OR name LIKE ?");
        $count_stmt->bind_param("ss", $search_term, $search_term);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM member");
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);
    
    // Fetch paginated records with related data
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name 
            FROM member m
            LEFT JOIN payment p ON m.pay_id = p.pay_id
            LEFT JOIN trainer t ON m.trainer_id = t.trainer_id
            LEFT JOIN gym g ON m.gym_id = g.gym_id
            WHERE m.mem_id LIKE ? OR m.name LIKE ?
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ssii", $search_term, $search_term, $limit, $offset);
    } else {
        $stmt = $conn->prepare("
            SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name 
            FROM member m
            LEFT JOIN payment p ON m.pay_id = p.pay_id
            LEFT JOIN trainer t ON m.trainer_id = t.trainer_id
            LEFT JOIN gym g ON m.gym_id = g.gym_id
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $members = $stmt->get_result();
    
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching members list.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Members</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
        }
        
        /* Main Layout */
        .app-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        /* Form Section */
        .form-section {
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            margin-left: 300px;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-title {
            font-size: 1.3rem;
            color: white;
        }
        
        .edit-mode .form-title {
            color: #3498db;
        }
        
        /* Table Section */
        .table-section {
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex: 1;
            margin-left: 300px;
        }
        
        /* Form Elements */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 8px 12px;
            font-size: 0.9rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        /* Table Styles */
        .search-box {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 8px 12px;
            font-size: 0.9rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-box button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-btn {
            padding: 6px 10px;
            font-size: 0.8rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .edit-btn {
            background: #3498db;
            color: white;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
        
        /* Error and success messages */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Form text helper */
        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
  <?php include('../navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../sidebar.php'); ?>
        
        <div class="main-content">
            <!-- Form Section at the TOP -->
            <div class="form-section <?= !empty($member_data) ? 'edit-mode' : '' ?>">
                <div class="form-header">
                    <h2 class="form-title">
                        <?= empty($member_data) ? 'Add New Member' : 'Edit Member' ?>
                    </h2>
                    <?php if (!empty($member_data)): ?>
                        <a href="manage_member.php" class="btn btn-success">+ Add New Member</a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="manage_member.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="<?= empty($member_data) ? 'add' : 'edit' ?>">
                    
                    <?php if (!empty($member_data)): ?>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($member_data['mem_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mem_id">Member ID</label>
                            <input type="text" id="mem_id" name="mem_id" 
                                   value="<?= htmlspecialchars($member_data['mem_id'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Member Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($member_data['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" 
                                   value="<?= htmlspecialchars($member_data['age'] ?? '') ?>" 
                                   min="12" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" 
                                   value="<?= htmlspecialchars($member_data['dob'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobileno">Mobile Number</label>
                            <input type="tel" id="mobileno" name="mobileno" 
                                   value="<?= htmlspecialchars($member_data['mobileno'] ?? '') ?>" 
                                   pattern="[0-9]{10,15}" required>
                            <div class="form-text">Enter 10 digits Sri Lankan number</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pay_id">Payment Plan</label>
                            <select id="pay_id" name="pay_id" required>
                                <option value="">Select Payment Plan</option>
                                <?php while ($payment = $payment_options->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                        <?= (isset($member_data['pay_id']) && $member_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="trainer_id">Trainer</label>
                            <select id="trainer_id" name="trainer_id" required>
                                <option value="">Select Trainer</option>
                                <?php while ($trainer = $trainer_options->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($trainer['trainer_id']) ?>" 
                                        <?= (isset($member_data['trainer_id']) && $member_data['trainer_id'] === $trainer['trainer_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($trainer['trainer_id']) ?> - <?= htmlspecialchars($trainer['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gym_id">Gym</label>
                            <select id="gym_id" name="gym_id" required>
                                <option value="">Select Gym</option>
                                <?php while ($gym = $gym_options->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($gym['gym_id']) ?>" 
                                        <?= (isset($member_data['gym_id']) && $member_data['gym_id'] === $gym['gym_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gym['gym_id']) ?> - <?= htmlspecialchars($gym['gym_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn <?= empty($member_data) ? 'btn-success' : 'btn-primary' ?>">
                            <?= empty($member_data) ? 'Add Member' : 'Update Member' ?>
                        </button>
                        <?php if (!empty($member_data)): ?>
                            <a href="manage_member.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Table Section Below the Form -->
            <div class="table-section">
                <div class="search-box">
                    <form method="get" style="width: 100%;">
                        <table>
                            <tr>
                                <td><input type="text" name="search" placeholder="Search members..." 
                               value="<?= htmlspecialchars($search ?? '') ?>"></td>
                                <td><button type="submit">Search</button></td>
                            </tr>
                        </table>
                        
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>DOB</th>
                            <th>Mobile</th>
                            <th>Payment Plan</th>
                            <th>Trainer</th>
                            <th>Gym</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($members->num_rows > 0): ?>
                            <?php while ($member = $members->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['mem_id']) ?></td>
                                    <td><?= htmlspecialchars($member['name']) ?></td>
                                    <td><?= htmlspecialchars($member['age']) ?></td>
                                    <td><?= htmlspecialchars($member['dob']) ?></td>
                                    <td><?= htmlspecialchars($member['mobileno']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($member['pay_id']) ?>
                                        (LKR <?= htmlspecialchars($member['amount'] ?? 'N/A') ?>)
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($member['trainer_id']) ?>
                                        (<?= htmlspecialchars($member['trainer_name'] ?? 'N/A') ?>)
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($member['gym_id']) ?>
                                        (<?= htmlspecialchars($member['gym_name'] ?? 'N/A') ?>)
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_member.php?action=edit&id=<?= urlencode($member['mem_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                onclick="if(confirm('Are you sure you want to delete this member?')) location.href='manage_member.php?action=delete&id=<?= urlencode($member['mem_id']) ?>'">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No members found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&search=<?= urlencode($search ?? '') ?>" class="btn btn-secondary">First</a>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>" 
                               class="btn <?= $i == $page ? 'btn-primary' : 'btn-secondary' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>" class="btn btn-secondary">Next</a>
                            <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search ?? '') ?>" class="btn btn-secondary">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
        
        // Confirm before delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this member?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>