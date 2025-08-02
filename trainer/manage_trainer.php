<?php
require_once('../login/auth.php');
require_once('../db.php');

$action = $_GET['action'] ?? '';
$trainer_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$trainer_data = [];

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize inputs
    $trainer_id = $conn->real_escape_string(trim($_POST['trainer_id'] ?? ''));
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $time = $conn->real_escape_string(trim($_POST['time'] ?? ''));
    $mobileno = $conn->real_escape_string(trim($_POST['mobileno'] ?? ''));
    $pay_id = $conn->real_escape_string(trim($_POST['pay_id'] ?? ''));
    
    // Validation
    if (empty($trainer_id)) {
        $errors[] = "Trainer ID is required.";
    }
    
    if (empty($name)) {
        $errors[] = "Trainer name is required.";
    }
    
    if (empty($time)) {
        $errors[] = "Time is required.";
    }
    
    if (!preg_match('/^[0-9]{10,15}$/', $mobileno)) {
        $errors[] = "Invalid mobile number format.";
    }
    
    if (empty($pay_id)) {
        $errors[] = "Payment plan is required.";
    }
    
    // Check for duplicate ID when adding
    if ($_POST['action'] === 'add') {
        $check = $conn->prepare("SELECT trainer_id FROM trainer WHERE trainer_id = ?");
        $check->bind_param("s", $trainer_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors[] = "Trainer ID already exists.";
        }
        $check->close();
    }
    
    // Process form if no errors
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO trainer (trainer_id, name, time, mobileno, pay_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $trainer_id, $name, $time, $mobileno, $pay_id);
                $stmt->execute();
                $success = "Trainer added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = $conn->real_escape_string(trim($_POST['original_id'] ?? ''));
                $stmt = $conn->prepare("UPDATE trainer SET trainer_id = ?, name = ?, time = ?, mobileno = ?, pay_id = ? WHERE trainer_id = ?");
                $stmt->bind_param("ssssss", $trainer_id, $name, $time, $mobileno, $pay_id, $original_id);
                $stmt->execute();
                $success = "Trainer updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_trainer.php?success=" . urlencode($success));
            exit();
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle delete action
if ($action === 'delete' && !empty($trainer_id)) {
    try {
        // Begin transaction for cascading deletes
        $conn->begin_transaction();
        
        // Delete related members first
        $conn->query("DELETE FROM member WHERE trainer_id = '$trainer_id'");
        
        // Delete trainer
        $conn->query("DELETE FROM trainer WHERE trainer_id = '$trainer_id'");
        
        $conn->commit();
        $success = "Trainer and all related members deleted successfully!";
        
        // Redirect to avoid refresh issues
        header("Location: manage_trainer.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting trainer: " . $e->getMessage();
    }
}

// Fetch trainer data for editing
if ($action === 'edit' && !empty($trainer_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM trainer WHERE trainer_id = ?");
        $stmt->bind_param("s", $trainer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $trainer_data = $result->fetch_assoc();
        } else {
            $errors[] = "Trainer not found.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching trainer data.";
    }
}

// Fetch payment options for dropdown
try {
    $payment_options = $conn->query("SELECT pay_id, amount FROM payment");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching payment options.";
}

// Fetch all trainers for listing
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count total records for pagination
    if (!empty($search)) {
        $search_term = "%$search%";
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM trainer WHERE trainer_id LIKE ? OR name LIKE ? OR time LIKE ? OR mobileno LIKE ?");
        $count_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM trainer");
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);
    
    // Fetch paginated records with payment info
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT t.*, p.amount 
            FROM trainer t
            LEFT JOIN payment p ON t.pay_id = p.pay_id
            WHERE t.trainer_id LIKE ? OR t.name LIKE ? OR t.time LIKE ? OR t.mobileno LIKE ?
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $limit, $offset);
    } else {
        $stmt = $conn->prepare("
            SELECT t.*, p.amount 
            FROM trainer t
            LEFT JOIN payment p ON t.pay_id = p.pay_id
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $trainers = $stmt->get_result();
    
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching trainers list.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Trainers</title>
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
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-section, 
            .table-section {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .form-header {
                flex-direction: column;
                gap: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group {
                margin-bottom: 15px;
            }

            input, select {
                padding: 10px;
                font-size: 16px; /* Prevent zoom on mobile */
            }

            .form-actions {
                flex-direction: column;
                width: 100%;
            }

            .form-actions .btn {
                width: 100%;
                margin-bottom: 8px;
                padding: 12px;
            }

            .search-box {
                flex-direction: column;
                gap: 8px;
            }

            .search-box input {
                width: 100%;
                padding: 10px;
            }

            .search-box button {
                width: 100%;
                padding: 12px;
            }
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
            .form-section, 
            .table-section {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .form-header {
                flex-direction: column;
                gap: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
                width: 100%;
            }

            .form-actions .btn {
                width: 100%;
                margin-bottom: 5px;
            }

            .table {
                font-size: 0.9rem;
            }

            td {
                padding: 8px;
            }

            .action-btn {
                padding: 6px 8px;
                font-size: 0.8rem;
                margin-bottom: 5px;
                width: 100%;
                text-align: center;
            }

            td .action-btn:last-child {
                margin-bottom: 0;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box button {
                width: 100%;
            }
        }
        
        /* Table scroll for mobile */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -15px;
            padding: 0 15px;
        }
        
        /* Stack action buttons on mobile */
        @media (max-width: 768px) {
            td:last-child {
                display: flex;
                flex-direction: column;
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
  <?php include('../components/navbar/navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../components/sidebar/sidebar.php'); ?>
        
        <div class="main-content">
            <!-- Form Section at the TOP -->
            <div class="form-section <?= !empty($trainer_data) ? 'edit-mode' : '' ?>">
                <div class="form-header">
                    <h2 class="form-title">
                        <?= empty($trainer_data) ? 'Add New Trainer' : 'Edit Trainer' ?>
                    </h2>
                    <?php if (!empty($trainer_data)): ?>
                        <a href="manage_trainer.php" class="btn btn-success">+ Add New Trainer</a>
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
                
                <form method="post" action="manage_trainer.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="<?= empty($trainer_data) ? 'add' : 'edit' ?>">
                    
                    <?php if (!empty($trainer_data)): ?>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($trainer_data['trainer_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="trainer_id">Trainer ID</label>
                            <input type="text" id="trainer_id" name="trainer_id" 
                                   value="<?= htmlspecialchars($trainer_data['trainer_id'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Trainer Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($trainer_data['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Available Time</label>
                            <input type="text" id="time" name="time" 
                                   value="<?= htmlspecialchars($trainer_data['time'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobileno">Mobile Number</label>
                            <input type="tel" id="mobileno" name="mobileno" 
                                   value="<?= htmlspecialchars($trainer_data['mobileno'] ?? '') ?>" 
                                   pattern="[0-9]{10,15}" required>
                            <div class="form-text">Enter 10 digits Sri Lankan number</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pay_id">Payment Plan</label>
                            <select id="pay_id" name="pay_id" required>
                                <option value="">Select Payment Plan</option>
                                <?php while ($payment = $payment_options->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                        <?= (isset($trainer_data['pay_id']) && $trainer_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn <?= empty($trainer_data) ? 'btn-success' : 'btn-primary' ?>">
                            <?= empty($trainer_data) ? 'Add Trainer' : 'Update Trainer' ?>
                        </button>
                        <?php if (!empty($trainer_data)): ?>
                            <a href="manage_trainer.php" class="btn btn-secondary">Cancel</a>
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
                                <td><input type="text" name="search" placeholder="Search trainers..." 
                                value="<?= htmlspecialchars($search ?? '') ?>"></td>
                                <td><button type="submit">Search</button></td>
                            </tr>
                        </table> 
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Trainer ID</th>
                            <th>Name</th>
                            <th>Time</th>
                            <th>Mobile</th>
                            <th>Payment Plan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($trainers->num_rows > 0): ?>
                            <?php while ($trainer = $trainers->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($trainer['trainer_id']) ?></td>
                                    <td><?= htmlspecialchars($trainer['name']) ?></td>
                                    <td><?= htmlspecialchars($trainer['time']) ?></td>
                                    <td><?= htmlspecialchars($trainer['mobileno']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($trainer['pay_id']) ?>
                                        (LKR <?= htmlspecialchars($trainer['amount'] ?? 'N/A') ?>)
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_trainer.php?action=edit&id=<?= urlencode($trainer['trainer_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                onclick="if(confirm('Are you sure you want to delete this trainer and all related members?')) location.href='manage_trainer.php?action=delete&id=<?= urlencode($trainer['trainer_id']) ?>'">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No trainers found</td>
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
        // Confirm before delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this trainer and all related members?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>