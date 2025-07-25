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
        // This query checks if a trainer ID already exists in the database to prevent duplicates.
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
    // Fetches all payment plans with their IDs and amounts.
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
                    <h1 class="h2"><?= empty($trainer_data) ? 'Add New Trainer' : 'Edit Trainer' ?></h1>
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
                        <form method="post" action="manage_trainer.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="<?= empty($trainer_data) ? 'add' : 'edit' ?>">
                            
                            <?php if (!empty($trainer_data)): ?>
                                <input type="hidden" name="original_id" value="<?= htmlspecialchars($trainer_data['trainer_id']) ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="trainer_id" class="form-label">Trainer ID</label>
                                <input type="text" class="form-control" id="trainer_id" name="trainer_id" 
                                       value="<?= htmlspecialchars($trainer_data['trainer_id'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Trainer Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($trainer_data['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="time" class="form-label">Available Time</label>
                                <input type="text" class="form-control" id="time" name="time" 
                                       value="<?= htmlspecialchars($trainer_data['time'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mobileno" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control" id="mobileno" name="mobileno" 
                                       value="<?= htmlspecialchars($trainer_data['mobileno'] ?? '') ?>" 
                                       pattern="[0-9]{10,15}" required>
                                <div class="form-text">Enter 10 digits srilankan number</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pay_id" class="form-label">Payment Plan</label>
                                <select class="form-select" id="pay_id" name="pay_id" required>
                                    <option value="">Select Payment Plan</option>
                                    <?php while ($payment = $payment_options->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                            <?= (isset($trainer_data['pay_id']) && $trainer_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <?= empty($trainer_data) ? 'Add Trainer' : 'Update Trainer' ?>
                            </button>
                            
                            <?php if (!empty($trainer_data)): ?>
                                <a href="manage_trainer.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Trainers List</h2>
                            <form method="get" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" placeholder="Enter trainer name or trainer id" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                                    <a href="manage_trainer.php?action=edit&id=<?= urlencode($trainer['trainer_id']) ?>" 
                                                       class="btn btn-sm btn-warning me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="manage_trainer.php?action=delete&id=<?= urlencode($trainer['trainer_id']) ?>" 
                                                       class="btn btn-sm btn-danger text-dark" 
                                                       onclick="return confirm('Are you sure you want to delete this trainer?')">
                                                        <i class="fas fa-trash-alt text-white"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No trainers found</td
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