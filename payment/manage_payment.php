<?php
require_once('../login/auth.php');
require_once('../db.php');

$action = $_GET['action'] ?? '';
$pay_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$payment_data = [];

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize inputs
    $pay_id = $conn->real_escape_string(trim($_POST['pay_id'] ?? ''));
    $amount = $conn->real_escape_string(trim($_POST['amount'] ?? ''));
    $gym_id = $conn->real_escape_string(trim($_POST['gym_id'] ?? ''));
    
    // Validation
    if (empty($pay_id)) {
        $errors[] = "Payment ID is required.";
    }
    
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Amount must be a positive number.";
    }
    
    if (empty($gym_id)) {
        $errors[] = "Gym is required.";
    }
    
    // Check for duplicate ID when adding
    if ($_POST['action'] === 'add') {
        $check = $conn->prepare("SELECT pay_id FROM payment WHERE pay_id = ?");
        $check->bind_param("s", $pay_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors[] = "Payment ID already exists.";
        }
        $check->close();
    }
    
    // Process form if no errors
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO payment (pay_id, amount, gym_id) VALUES (?, ?, ?)");
                $stmt->bind_param("sds", $pay_id, $amount, $gym_id);
                $stmt->execute();
                
                // Store success message in session instead of URL
                $_SESSION['success_message'] = "Payment area added successfully!";
                header("Location: manage_payment.php?action=success");
                exit();
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = $conn->real_escape_string(trim($_POST['original_id'] ?? ''));
                // Don't update the pay_id - use original_id for WHERE clause
                $stmt = $conn->prepare("UPDATE payment SET amount = ?, gym_id = ? WHERE pay_id = ?");
                $stmt->bind_param("dss", $amount, $gym_id, $original_id);
                $stmt->execute();
                
                // Store success message in session instead of URL
                $_SESSION['success_message'] = "Payment area updated successfully!";
                header("Location: manage_payment.php?action=success");
                exit();
            }
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle delete action
if ($action === 'delete' && !empty($pay_id)) {
    try {
        // Begin transaction for cascading deletes
        $conn->begin_transaction();
        
        // Delete related members first
        $conn->query("DELETE FROM member WHERE trainer_id IN (SELECT trainer_id FROM trainer WHERE pay_id = '$pay_id')");
        
        // Delete related trainers
        $conn->query("DELETE FROM trainer WHERE pay_id = '$pay_id'");
        
        // Delete payment
        $conn->query("DELETE FROM payment WHERE pay_id = '$pay_id'");
        
        $conn->commit();
        
        // Store success message in session instead of URL
        $_SESSION['success_message'] = "Payment area and all related data deleted successfully!";
        header("Location: manage_payment.php?action=success");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting payment area: " . $e->getMessage();
    }
}

// Fetch payment data for editing
if ($action === 'edit' && !empty($pay_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM payment WHERE pay_id = ?");
        $stmt->bind_param("s", $pay_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $payment_data = $result->fetch_assoc();
        } else {
            $errors[] = "Payment area not found.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching payment data.";
    }
}

// Fetch gym options for dropdown
try {
    $gym_options = $conn->query("SELECT gym_id, gym_name FROM gym");
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching gym options.";
}

// Fetch all payments for listing
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count total records for pagination
    if (!empty($search)) {
        $search_term = "%$search%";
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM payment WHERE pay_id LIKE ?");
        $count_stmt->bind_param("s", $search_term);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM payment");
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);
    
    // Fetch paginated records with gym info
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT p.*, g.gym_name 
            FROM payment p
            LEFT JOIN gym g ON p.gym_id = g.gym_id
            WHERE p.pay_id LIKE ?
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("sii", $search_term, $limit, $offset);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, g.gym_name 
            FROM payment p
            LEFT JOIN gym g ON p.gym_id = g.gym_id
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $payments = $stmt->get_result();
    
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching payments list.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Payments</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="payment_management.css">
</head>
<body>
  <?php include('../components/navbar/navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../components/sidebar/sidebar.php'); ?>
        
        <div class="main-content">
            <!-- Form Section at the TOP -->
            <div class="form-section <?= !empty($payment_data) ? 'edit-mode' : '' ?>">
                <div class="form-header">
                    <h2 class="form-title">
                        <?= empty($payment_data) ? 'Add New Payment Area' : 'Edit Payment Area' ?>
                    </h2>
                    <?php if (!empty($payment_data)): ?>
                        <a href="manage_payment.php" class="btn btn-success">+ Add New Payment</a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <?php unset($_SESSION['success_message']); // Clear message after displaying ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="manage_payment.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="<?= empty($payment_data) ? 'add' : 'edit' ?>">
                    
                    <?php if (!empty($payment_data)): ?>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($payment_data['pay_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="pay_id">Payment Area ID</label>
                            <input type="text" id="pay_id" name="pay_id" 
                                   value="<?= htmlspecialchars($payment_data['pay_id'] ?? '') ?>" 
                                   <?= !empty($payment_data) ? 'readonly' : '' ?> required>
                            <?php if (!empty($payment_data)): ?>
                                <small class="readonly-note">ID cannot be changed after creation</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount (LKR)</label>
                            <input type="number" id="amount" name="amount" 
                                   value="<?= htmlspecialchars($payment_data['amount'] ?? '') ?>" 
                                   min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="gym_id">Gym</label>
                            <select id="gym_id" name="gym_id" required>
                                <option value="">Select Gym</option>
                                <?php while ($gym = $gym_options->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($gym['gym_id']) ?>" 
                                        <?= (isset($payment_data['gym_id']) && $payment_data['gym_id'] === $gym['gym_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gym['gym_id']) ?> - <?= htmlspecialchars($gym['gym_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn <?= empty($payment_data) ? 'btn-success' : 'btn-primary' ?>">
                            <?= empty($payment_data) ? 'Add Payment Area' : 'Update Payment Area' ?>
                        </button>
                        <?php if (!empty($payment_data)): ?>
                            <a href="manage_payment.php" class="btn btn-secondary">Cancel</a>
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
                                <td><input type="text" name="search" placeholder="Search payment areas..." 
                                value="<?= htmlspecialchars($search ?? '') ?>"></td>
                                <td><button type="submit">Search</button></td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Amount</th>
                            <th>Gym</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['pay_id']) ?></td>
                                    <td>LKR <?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <?= htmlspecialchars($payment['gym_id']) ?>
                                        (<?= htmlspecialchars($payment['gym_name'] ?? 'N/A') ?>)
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_payment.php?action=edit&id=<?= urlencode($payment['pay_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                onclick="if(confirm('Are you sure you want to delete this payment area and all related trainers/members?')) location.href='manage_payment.php?action=delete&id=<?= urlencode($payment['pay_id']) ?>'">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No payment areas found</td>
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
                if (!confirm('Are you sure you want to delete this payment area and all related trainers/members?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>