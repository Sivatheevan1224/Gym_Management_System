<?php
require_once('../login/auth.php');
require_once('../classes/Database.php');
require_once('../classes/BaseModel.php');
require_once('../classes/Trainer.php');
require_once('../classes/Payment.php');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Initialize model classes
$trainerModel = new Trainer();
$paymentModel = new Payment();

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
    $input_data = [
        'trainer_id' => trim($_POST['trainer_id'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'time' => trim($_POST['time'] ?? ''),
        'mobileno' => trim($_POST['mobileno'] ?? ''),
        'pay_id' => trim($_POST['pay_id'] ?? '')
    ];
    
    // Use the Trainer class validation method
    $errors = $trainerModel->validate($input_data);
    
    // Additional validations specific to the form
    if (empty($input_data['pay_id'])) {
        $errors[] = "Payment plan is required.";
    }
    
    // Check for duplicate ID when adding
    if ($_POST['action'] === 'add') {
        if ($trainerModel->findById($input_data['trainer_id'])) {
            $errors[] = "Trainer ID already exists.";
        }
    }
    
    // Process form if no errors
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $trainerModel->create($input_data);
                $success = "Trainer added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = trim($_POST['original_id'] ?? '');
                $trainerModel->update($original_id, $input_data);
                $success = "Trainer updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_trainer.php?success=" . urlencode($success));
            exit();
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle delete action - OPTIMIZED
if ($action === 'delete' && !empty($trainer_id)) {
    // Add timing for performance monitoring
    $start_time = microtime(true);
    
    try {
        // Validate trainer ID before attempting deletion
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $trainer_id)) {
            $errors[] = "Invalid trainer ID format.";
        } else {
            $delete_result = $trainerModel->delete($trainer_id);
            
            if ($delete_result) {
                $end_time = microtime(true);
                $execution_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
                
                $success = "Trainer deleted successfully!";
                
                // Log successful deletion for performance tracking
                error_log("Trainer deletion successful - ID: $trainer_id, Time: {$execution_time}ms");
            } else {
                $errors[] = "Trainer not found or could not be deleted.";
            }
        }
        
        // Redirect to avoid refresh issues
        if (!empty($success)) {
            header("Location: manage_trainer.php?success=" . urlencode($success));
        } else {
            header("Location: manage_trainer.php?error=" . urlencode(implode(', ', $errors)));
        }
        exit();
        
    } catch (Exception $e) {
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        error_log("Trainer deletion error - ID: $trainer_id, Time: {$execution_time}ms, Error: " . $e->getMessage());
        $errors[] = "Error deleting trainer: " . $e->getMessage();
        
        header("Location: manage_trainer.php?error=" . urlencode(implode(', ', $errors)));
        exit();
    }
}

// Fetch trainer data for editing
if ($action === 'edit' && !empty($trainer_id)) {
    try {
        $trainer_data = $trainerModel->findById($trainer_id);
        if (!$trainer_data) {
            $errors[] = "Trainer not found.";
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching trainer data.";
    }
}

// Fetch payment options for dropdown
try {
    $payment_options = $paymentModel->getAll();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching payment options.";
}

// Fetch all trainers for listing
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get trainers with search functionality
    if (!empty($search)) {
        $trainers_list = $trainerModel->search($search);
        $total_records = count($trainers_list);
        // Apply pagination to search results
        $trainers_list = array_slice($trainers_list, $offset, $limit);
    } else {
        $trainers_list = $trainerModel->getAllWithDetails();
        $total_records = count($trainers_list);
        // Apply pagination
        $trainers_list = array_slice($trainers_list, $offset, $limit);
    }
    
    $total_pages = ceil($total_records / $limit);
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching trainers list.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trainers</title>
    <link rel="stylesheet" href="trainer_management.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
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
                                <?php foreach ($payment_options as $payment): ?>
                                    <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                        <?= (isset($trainer_data['pay_id']) && $trainer_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <?php if (!empty($trainers_list)): ?>
                            <?php foreach ($trainers_list as $trainer): ?>
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
                                                data-trainer-id="<?= htmlspecialchars($trainer['trainer_id']) ?>"
                                                data-trainer-name="<?= htmlspecialchars($trainer['name']) ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
        // Optimized trainer deletion with loading feedback
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const trainerId = this.getAttribute('data-trainer-id');
                    const trainerName = this.getAttribute('data-trainer-name');
                    
                    // Updated confirmation message
                    const confirmMessage = `Are you sure you want to delete trainer "${trainerName}" (ID: ${trainerId})?\n\nThis will remove the trainer reference from assigned members, but members will not be deleted.`;
                    
                    if (confirm(confirmMessage)) {
                        // Show loading state
                        this.disabled = true;
                        this.textContent = 'Deleting...';
                        this.style.opacity = '0.6';
                        
                        // Add loading indicator to the page
                        const loadingDiv = document.createElement('div');
                        loadingDiv.innerHTML = '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 5px; z-index: 9999;">⏳ Deleting trainer...</div>';
                        document.body.appendChild(loadingDiv);
                        
                        // Start timer for performance monitoring
                        const startTime = Date.now();
                        
                        // Redirect to deletion URL
                        window.location.href = `manage_trainer.php?action=delete&id=${encodeURIComponent(trainerId)}`;
                    }
                });
            });
        });
    </script>
</body>
</html>