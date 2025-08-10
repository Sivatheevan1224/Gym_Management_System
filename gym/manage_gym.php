<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['uname']) || empty($_SESSION['uname'])) {
    header("Location: ../index/index.html");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index/index.html");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include required classes
require_once('../classes/Database.php');
require_once('../classes/BaseModel.php');
require_once('../classes/Gym.php');

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Initialize Gym model
$gymModel = new Gym();

// Initialize variables for form processing and data display
$action = $_GET['action'] ?? '';
$gym_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$gym_data = [];

// Process form submissions for adding/editing gyms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize all input data
    $input_data = [
        'gym_id' => trim($_POST['gym_id'] ?? ''),
        'gym_name' => trim($_POST['name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'type' => trim($_POST['type'] ?? '')
    ];
    
    // Use the Gym class validation method
    $errors = $gymModel->validate($input_data);
    
    // Check if gym ID already exists when adding new gym
    if ($_POST['action'] === 'add') {
        if ($gymModel->findById($input_data['gym_id'])) {
            $errors[] = "Gym ID already exists.";
        }
    }
    
    // Execute database operations if validation passes
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                $gymModel->create($input_data);
                $_SESSION['success_message'] = "Gym added successfully!";
            } 
            elseif ($_POST['action'] === 'edit') {
                $original_id = trim($_POST['original_id'] ?? '');
                $gymModel->update($original_id, $input_data);
                $_SESSION['success_message'] = "Gym updated successfully!";
            }
            
            header("Location: manage_gym.php?action=success");
            exit();
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle gym deletion with cascading deletion of related data
if ($action === 'delete' && !empty($gym_id)) {
    try {
        if ($gymModel->delete($gym_id)) {
            $_SESSION['success_message'] = "Gym and all related data deleted successfully!";
        } else {
            $errors[] = "Gym not found.";
        }
        
        header("Location: manage_gym.php?action=success");
        exit();
        
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting gym: " . $e->getMessage();
    }
}

// Fetch gym data when editing existing gym
if ($action === 'edit' && !empty($gym_id)) {
    try {
        $gym_data = $gymModel->findById($gym_id);
        if (!$gym_data) {
            $errors[] = "Gym not found.";
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching gym data.";
    }
}

// Setup pagination and search for gyms list
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get gyms with search functionality
    if (!empty($search)) {
        $gyms_list = Gym::search($search);
        $total_records = count($gyms_list);
        // Apply pagination to search results
        $gyms_list = array_slice($gyms_list, $offset, $limit);
    } else {
        $gyms_list = $gymModel->getAll();
        $total_records = count($gyms_list);
        // Apply pagination
        $gyms_list = array_slice($gyms_list, $offset, $limit);
    }
    
    $total_pages = ceil($total_records / $limit);
    
} catch (Exception $e) {
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
    <link rel="stylesheet" href="gym_management.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
</head>
<body>
  <?php include('../components/navbar/navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../components/sidebar/sidebar.php'); ?>
        
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
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                    <?php unset($_SESSION['success_message']); // Clear message after displaying ?>
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
                                   value="<?= htmlspecialchars($gym_data['gym_id'] ?? '') ?>" 
                                   <?= !empty($gym_data) ? 'readonly' : '' ?> required>
                            <?php if (!empty($gym_data)): ?>
                                <small class="readonly-note">ID cannot be changed after creation</small>
                            <?php endif; ?>
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
                        <?php if ($gyms_list): ?>
                            <?php foreach ($gyms_list as $gym): ?>
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
                                                data-gym-id="<?= htmlspecialchars($gym['gym_id']) ?>"
                                                data-gym-name="<?= htmlspecialchars($gym['gym_name']) ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                const gymId = this.getAttribute('data-gym-id');
                const gymName = this.getAttribute('data-gym-name');
                if (confirm('Are you sure you want to delete the gym "' + gymName + '"? This will remove gym references from all members, but members will not be deleted.')) {
                    location.href = 'manage_gym.php?action=delete&id=' + encodeURIComponent(gymId);
                }
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>