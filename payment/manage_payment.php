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
        // This query checks if a payment ID already exists in the database to prevent duplicates.
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
                $success = "Payment area added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = $conn->real_escape_string(trim($_POST['original_id'] ?? ''));
                $stmt = $conn->prepare("UPDATE payment SET pay_id = ?, amount = ?, gym_id = ? WHERE pay_id = ?");
                $stmt->bind_param("sdss", $pay_id, $amount, $gym_id, $original_id);
                $stmt->execute();
                $success = "Payment area updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_payment.php?success=" . urlencode($success));
            exit();
            
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
        $success = "Payment area and all related data deleted successfully!";
        
        // Redirect to avoid refresh issues
        header("Location: manage_payment.php?success=" . urlencode($success));
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
    // Fetches all gyms with their IDs and names.
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/GYM-MANAGEMENT-SYSTEM/home/home.css">
</head>
<body>
    <?php include('../navbar.php'); ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include('../sidebar.php'); ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?= empty($payment_data) ? 'Add New Payment Area' : 'Edit Payment Area' ?></h1>
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
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" action="manage_payment.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="<?= empty($payment_data) ? 'add' : 'edit' ?>">
                            
                            <?php if (!empty($payment_data)): ?>
                                <input type="hidden" name="original_id" value="<?= htmlspecialchars($payment_data['pay_id']) ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="pay_id" class="form-label">Payment Area ID</label>
                                <input type="text" class="form-control" id="pay_id" name="pay_id" 
                                       value="<?= htmlspecialchars($payment_data['pay_id'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount (LKR)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="<?= htmlspecialchars($payment_data['amount'] ?? '') ?>" 
                                       min="0" step="0.01" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="gym_id" class="form-label">Gym</label>
                                <select class="form-select" id="gym_id" name="gym_id" required>
                                    <option value="">Select Gym</option>
                                    <?php while ($gym = $gym_options->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($gym['gym_id']) ?>" 
                                            <?= (isset($payment_data['gym_id']) && $payment_data['gym_id'] === $gym['gym_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($gym['gym_id']) ?> - <?= htmlspecialchars($gym['gym_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <?= empty($payment_data) ? 'Add Payment Area' : 'Update Payment Area' ?>
                            </button>
                            
                            <?php if (!empty($payment_data)): ?>
                                <a href="manage_payment.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Payment Areas List</h2>
                            <form method="get" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" placeholder="Enter payment area id" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                                    <a href="manage_payment.php?action=edit&id=<?= urlencode($payment['pay_id']) ?>" 
                                                       class="btn btn-sm btn-warning me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="manage_payment.php?action=delete&id=<?= urlencode($payment['pay_id']) ?>" 
                                                       class="btn btn-sm btn-danger text-dark" 
                                                       onclick="return confirm('Are you sure you want to delete this payment area and all related trainers/members?')">
                                                        <i class="fas fa-trash-alt text-white"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No payment areas found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>">First</a>
                                    </li>
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                                    </li>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>">Last</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>