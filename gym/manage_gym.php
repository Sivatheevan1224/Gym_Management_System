<?php
//require_once('../auth.php');
require_once('../db.php');

$action = $_GET['action'] ?? '';
$gym_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$gym_data = [];

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize inputs
    $gym_id = $conn->real_escape_string(trim($_POST['gym_id'] ?? ''));
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $type = $conn->real_escape_string(trim($_POST['type'] ?? ''));
    
    // Validation
    if (empty($gym_id)) {
        $errors[] = "Gym ID is required.";
    }
    
    if (empty($name)) {
        $errors[] = "Gym name is required.";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required.";
    }
    
    if (!in_array($type, ['unisex', 'women', 'men'])) {
        $errors[] = "Invalid gym type.";
    }
    
    // Check for duplicate ID when adding
    if ($_POST['action'] === 'add') {
        $check = $conn->prepare("SELECT gym_id FROM gym WHERE gym_id = ?");
        $check->bind_param("s", $gym_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors[] = "Gym ID already exists.";
        }
        $check->close();
    }
    
    // Process form if no errors
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO gym (gym_id, gym_name, address, type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $gym_id, $name, $address, $type);
                $stmt->execute();
                $success = "Gym added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = $conn->real_escape_string(trim($_POST['original_id'] ?? ''));
                $stmt = $conn->prepare("UPDATE gym SET gym_id = ?, gym_name = ?, address = ?, type = ? WHERE gym_id = ?");
                $stmt->bind_param("sssss", $gym_id, $name, $address, $type, $original_id);
                $stmt->execute();
                $success = "Gym updated successfully!";
            }
            
            // Redirect to avoid form resubmission
            header("Location: manage_gym.php?success=" . urlencode($success));
            exit();
            
        } catch (mysqli_sql_exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle delete action
if ($action === 'delete' && !empty($gym_id)) {
    try {
        // Begin transaction for cascading deletes
        $conn->begin_transaction();
        
        // Delete related members, trainers, payments
        $conn->query("DELETE FROM member WHERE trainer_id IN (SELECT trainer_id FROM trainer WHERE pay_id IN (SELECT pay_id FROM payment WHERE gym_id = '$gym_id'))");
        $conn->query("DELETE FROM trainer WHERE pay_id IN (SELECT pay_id FROM payment WHERE gym_id = '$gym_id')");
        $conn->query("DELETE FROM payment WHERE gym_id = '$gym_id'");
        
        // Delete gym
        $conn->query("DELETE FROM gym WHERE gym_id = '$gym_id'");
        
        $conn->commit();
        $success = "Gym and all related data deleted successfully!";
        
        // Redirect to avoid refresh issues
        header("Location: manage_gym.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting gym: " . $e->getMessage();
    }
}

// Fetch gym data for editing
if ($action === 'edit' && !empty($gym_id)) {
    try {
        $stmt = $conn->prepare("SELECT * FROM gym WHERE gym_id = ?");
        $stmt->bind_param("s", $gym_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $gym_data = $result->fetch_assoc();
        } else {
            $errors[] = "Gym not found.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching gym data.";
    }
}

// Fetch all gyms for listing
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Count total records for pagination
    if (!empty($search)) {
        $search_term = "%$search%";
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM gym WHERE gym_id LIKE ? OR gym_name LIKE ? OR address LIKE ? OR type LIKE ?");
        $count_stmt->bind_param("ssss", $search_term, $search_term, $search_term, $search_term);
    } else {
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM gym");
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_row()[0];
    $total_pages = ceil($total_records / $limit);
    
    // Fetch paginated records
    if (!empty($search)) {
        $stmt = $conn->prepare("SELECT * FROM gym WHERE gym_id LIKE ? OR gym_name LIKE ? OR address LIKE ? OR type LIKE ? LIMIT ? OFFSET ?");
        $stmt->bind_param("ssssii", $search_term, $search_term, $search_term, $search_term, $limit, $offset);
    } else {
        $stmt = $conn->prepare("SELECT * FROM gym LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $gyms = $stmt->get_result();
    
} catch (mysqli_sql_exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching gyms list.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Gyms</title>
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
                    <h1 class="h2"><?= empty($gym_data) ? 'Add New Gym' : 'Edit Gym' ?></h1>
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
                        <form method="post" action="manage_gym.php">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="<?= empty($gym_data) ? 'add' : 'edit' ?>">
                            
                            <?php if (!empty($gym_data)): ?>
                                <input type="hidden" name="original_id" value="<?= htmlspecialchars($gym_data['gym_id']) ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="gym_id" class="form-label">Gym ID</label>
                                <input type="text" class="form-control" id="gym_id" name="gym_id" 
                                       value="<?= htmlspecialchars($gym_data['gym_id'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Gym Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($gym_data['gym_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?= htmlspecialchars($gym_data['address'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="type" class="form-label">Gym Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="unisex" <?= (isset($gym_data['type']) && $gym_data['type'] === 'unisex') ? 'selected' : '' ?>>Unisex</option>
                                    <option value="women" <?= (isset($gym_data['type']) && $gym_data['type'] === 'women') ? 'selected' : '' ?>>Women</option>
                                    <option value="men" <?= (isset($gym_data['type']) && $gym_data['type'] === 'men') ? 'selected' : '' ?>>Men</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <?= empty($gym_data) ? 'Add Gym' : 'Update Gym' ?>
                            </button>
                            
                            <?php if (!empty($gym_data)): ?>
                                <a href="manage_gym.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h2>Gyms List</h2>
                            <form method="get" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" placeholder="Enter gym name or gym id" 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Gym ID</th>
                                        <th>Name</th>
                                        <th>Address</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($gyms->num_rows > 0): ?>
                                        <?php while ($gym = $gyms->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($gym['gym_id']) ?></td>
                                                <td><?= htmlspecialchars($gym['gym_name']) ?></td>
                                                <td><?= htmlspecialchars($gym['address']) ?></td>
                                                <td><?= htmlspecialchars(ucfirst($gym['type'])) ?></td>
                                                <td>
                                                    <a href="manage_gym.php?action=edit&id=<?= urlencode($gym['gym_id']) ?>" 
                                                       class="btn btn-sm btn-warning me-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="manage_gym.php?action=delete&id=<?= urlencode($gym['gym_id']) ?>" 
                                                       class="btn btn-sm btn-danger text-dark" 
                                                       onclick="return confirm('Are you sure you want to delete this gym?')">
                                                        <i class="fas fa-trash-alt text-white"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No gyms found</td>
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
        // Confirm before delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>