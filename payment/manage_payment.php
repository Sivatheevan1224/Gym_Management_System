<?php
require_once('../login/auth.php');
require_once('../classes/Database.php');
require_once('../classes/BaseModel.php');
require_once('../classes/Payment.php');
require_once('../classes/Gym.php');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Initialize model classes
$paymentModel = new Payment();
$gymModel = new Gym();

$action = $_GET['action'] ?? '';
$pay_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$payment_data = [];

// Process form submissions for adding/editing payment areas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize inputs
    $input_data = [
        'pay_id' => trim($_POST['pay_id'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'gym_id' => trim($_POST['gym_id'] ?? '')
    ];
    
    // Use the Payment class validation method
    $errors = $paymentModel->validate($input_data);
    
    // Check if payment ID already exists when adding new payment area
    if ($_POST['action'] === 'add') {
        if ($paymentModel->findById($input_data['pay_id'])) {
            $errors[] = "Payment ID already exists.";
        }
    }
    
    // Execute database operations if validation passes
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $paymentModel->create($input_data);
                $success = "Payment area added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = trim($_POST['original_id'] ?? '');
                $paymentModel->update($original_id, $input_data);
                $success = "Payment area updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_payment.php?success=" . urlencode($success));
            exit();
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle payment area deletion with cascading trainer and member deletion
if ($action === 'delete' && !empty($pay_id)) {
    try {
        if ($paymentModel->delete($pay_id)) {
            $success = "Payment area and all related data deleted successfully!";
        } else {
            $errors[] = "Payment area not found.";
        }
        
        // Redirect to avoid refresh issues
        header("Location: manage_payment.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting payment area: " . $e->getMessage();
    }
}

// Fetch payment data when editing existing payment area
if ($action === 'edit' && !empty($pay_id)) {
    try {
        $payment_data = $paymentModel->findById($pay_id);
        if (!$payment_data) {
            $errors[] = "Payment area not found.";
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching payment data.";
    }
}

// Load gym options for form dropdown
try {
    $gym_options = $gymModel->getAll();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching gym options.";
}

// Setup pagination and search for payment areas list
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get payments with search functionality
    if (!empty($search)) {
        $payments_list = $paymentModel->search($search);
        $total_records = count($payments_list);
        // Apply pagination to search results
        $payments_list = array_slice($payments_list, $offset, $limit);
    } else {
        $payments_list = $paymentModel->getAllWithDetails();
        $total_records = count($payments_list);
        // Apply pagination
        $payments_list = array_slice($payments_list, $offset, $limit);
    }
    
    $total_pages = ceil($total_records / $limit);
    
} catch (Exception $e) {
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
    <link rel="icon" type="image/png" href="../images/logo.png">
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
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_GET['success']) ?>
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
                                <?php foreach ($gym_options as $gym): ?>
                                    <option value="<?= htmlspecialchars($gym['gym_id']) ?>" 
                                        <?= (isset($payment_data['gym_id']) && $payment_data['gym_id'] === $gym['gym_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gym['gym_id']) ?> - <?= htmlspecialchars($gym['gym_name']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <?php if (!empty($payments_list)): ?>
                            <?php foreach ($payments_list as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['pay_id']) ?></td>
                                    <td>LKR <?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <?= htmlspecialchars($payment['gym_name'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_payment.php?action=edit&id=<?= urlencode($payment['pay_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                data-payment-id="<?= htmlspecialchars($payment['pay_id']) ?>"
                                                data-payment-amount="<?= htmlspecialchars($payment['amount']) ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                const paymentId = this.getAttribute('data-payment-id');
                const paymentAmount = this.getAttribute('data-payment-amount');
                
                if (confirm(`Are you sure you want to delete the payment area with ID ${paymentId} and amount LKR ${paymentAmount}? This will remove payment references from all related members and trainers, but members will not be deleted.`)) {
                    // Proceed with deletion
                    location.href = `manage_payment.php?action=delete&id=${encodeURIComponent(paymentId)}`;
                } else {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>