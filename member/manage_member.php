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
        $check = $conn->prepare("SELECT mem_id FROM member WHERE mem_id = ?"); // This query checks if a member ID already exists in the database to prevent duplicates.
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
    $payment_options = $conn->query("SELECT pay_id, amount FROM payment"); // Fetches all payment plans with their IDs and amounts.
    $trainer_options = $conn->query("SELECT trainer_id, name FROM trainer"); // Fetches all trainers with their IDs and names.
    $gym_options = $conn->query("SELECT gym_id, gym_name FROM gym"); // Fetches all gyms with their IDs and names.
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
        "); // This query fetches member details along with related payment, trainer, and gym information.
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
                    <h1 class="h2"><?= empty($member_data) ? 'Add New Member' : 'Edit Member' ?></h1>
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
                        <form method="post" action="manage_member.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="<?= empty($member_data) ? 'add' : 'edit' ?>">
                            
                            <?php if (!empty($member_data)): ?>
                                <input type="hidden" name="original_id" value="<?= htmlspecialchars($member_data['mem_id']) ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="mem_id" class="form-label">Member ID</label>
                                <input type="text" class="form-control" id="mem_id" name="mem_id" 
                                       value="<?= htmlspecialchars($member_data['mem_id'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Member Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($member_data['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" 
                                           value="<?= htmlspecialchars($member_data['age'] ?? '') ?>" 
                                           min="12" max="100" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob" 
                                           value="<?= htmlspecialchars($member_data['dob'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mobileno" class="form-label">Mobile Number</label>
                                <input type="tel" class="form-control" id="mobileno" name="mobileno" 
                                       value="<?= htmlspecialchars($member_data['mobileno'] ?? '') ?>" 
                                       pattern="[0-9]{10,15}" required>
                                <div class="form-text">Enter 10 digits srilankan number</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="pay_id" class="form-label">Payment Plan</label>
                                    <select class="form-select" id="pay_id" name="pay_id" required>
                                        <option value="">Select Payment Plan</option>
                                        <?php while ($payment = $payment_options->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                                <?= (isset($member_data['pay_id']) && $member_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="trainer_id" class="form-label">Trainer</label>
                                    <select class="form-select" id="trainer_id" name="trainer_id" required>
                                        <option value="">Select Trainer</option>
                                        <?php while ($trainer = $trainer_options->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($trainer['trainer_id']) ?>" 
                                                <?= (isset($member_data['trainer_id']) && $member_data['trainer_id'] === $trainer['trainer_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($trainer['trainer_id']) ?> - <?= htmlspecialchars($trainer['name']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="gym_id" class="form-label">Gym</label>
                                    <select class="form-select" id="gym_id" name="gym_id" required>
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
                            
                            <button type="submit" class="btn btn-primary">
                                <?= empty($member_data) ? 'Add Member' : 'Update Member' ?>
                            </button>
                            
                            <?php if (!empty($member_data)): ?>
                                <a href="manage_member.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Members List</h2>
                            <form method="get" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" placeholder="Enter member name or member id" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>DOB</th>
                                        <th>Mobile</th>
                                        <th style="width: 15%;">Payment Plan</th>
                                        <th>Trainer</th>
                                        <th style="width: 10%;">Gym</th>
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
                                                    <a href="manage_trainer.php?action=edit&id=<?= urlencode($member['mem_id']) ?>" 
                                                       class="btn btn-sm btn-warning me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="manage_trainer.php?action=delete&id=<?= urlencode($member['mem_id']) ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this member?')">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No members found</td>
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
    </script>
</body>
</html>