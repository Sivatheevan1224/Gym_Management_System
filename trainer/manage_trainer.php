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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - Gym Management System</title>
    <link rel="stylesheet" href="trainer_management.css">
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
                                   value="<?= htmlspecialchars($trainer_data['trainer_id'] ?? '') ?>" 
                                   <?= !empty($trainer_data) ? 'readonly' : '' ?> required>
                            <?php if (!empty($trainer_data)): ?>
                                <div class="form-text readonly-note">ID cannot be changed after creation</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Trainer Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($trainer_data['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time">Available Time</label>
                            <input type="time" id="time" name="time" 
                                   value="<?= htmlspecialchars($trainer_data['time'] ?? '') ?>" required>
                            <div class="form-text">Select available training time</div>
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