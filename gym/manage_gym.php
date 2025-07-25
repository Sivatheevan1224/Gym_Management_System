<?php
ob_start();
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        
        /* Sidebar - keep your existing sidebar styles */
        
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
            margin-left:300px;
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
            margin-left:300px;
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
    </style>
</head>
<body>
  <?php include('../navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../sidebar.php'); ?>
        
        <div class="main-content">
            <!-- Form Section at the TOP -->
            <div class="form-section <?= !empty($gym_data) ? 'edit-mode' : '' ?>">
                <div class="form-header">
                    <h2 class="form-title">
                        <?= empty($gym_data) ? 'Add New Gym' : 'Edit Gym' ?>
                    </h2>
                    <?php if (!empty($gym_data)): ?>
                        <a href="manage_gym.php" class="btn btn-success">+ Add New Gym</a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div style="color: #e74c3c; margin-bottom: 15px; padding: 10px;  border-radius: 4px;">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="color: #155724; margin-bottom: 15px; padding: 10px; border-radius: 4px;">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="manage_gym.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="<?= empty($gym_data) ? 'add' : 'edit' ?>">
                    
                    <?php if (!empty($gym_data)): ?>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($gym_data['gym_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="gym_id">Gym ID</label>
                            <input type="text" id="gym_id" name="gym_id" 
                                   value="<?= htmlspecialchars($gym_data['gym_id'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Gym Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($gym_data['gym_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" 
                                   value="<?= htmlspecialchars($gym_data['address'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="type">Gym Type</label>
                            <select id="type" name="type" required>
                                <option value="unisex" <?= (isset($gym_data['type']) && $gym_data['type'] === 'unisex') ? 'selected' : '' ?>>Unisex</option>
                                <option value="women" <?= (isset($gym_data['type']) && $gym_data['type'] === 'women') ? 'selected' : '' ?>>Women</option>
                                <option value="men" <?= (isset($gym_data['type']) && $gym_data['type'] === 'men') ? 'selected' : '' ?>>Men</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn <?= empty($gym_data) ? 'btn-success' : 'btn-primary' ?>">
                            <?= empty($gym_data) ? 'Add Gym' : 'Update Gym' ?>
                        </button>
                        <?php if (!empty($gym_data)): ?>
                            <a href="manage_gym.php" class="btn btn-secondary">Cancel</a>
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
                        <td><input type="text" name="search" placeholder="Search gyms..." 
                               value="<?= htmlspecialchars($search ?? '') ?>"></td>
                        <td><button type="submit">Search</button></td>
                        </tr>
                        </table>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
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
                                    <td><?= htmlspecialchars(ucfirst($gym['type'])) ?></td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_gym.php?action=edit&id=<?= urlencode($gym['gym_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                onclick="if(confirm('Are you sure you want to delete this gym?')) location.href='manage_gym.php?action=delete&id=<?= urlencode($gym['gym_id']) ?>'">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No gyms found</td>
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
                if (!confirm('Are you sure you want to delete this gym?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>