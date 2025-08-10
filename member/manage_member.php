<?php
require_once('../login/auth.php');
require_once('../classes/Database.php');
require_once('../classes/BaseModel.php');
require_once('../classes/Member.php');
require_once('../classes/Payment.php');
require_once('../classes/Trainer.php');
require_once('../classes/Gym.php');

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get database connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Initialize Member class
$memberModel = new Member();

// Initialize variables FIRST
$action = $_GET['action'] ?? '';
$mem_id = $_GET['id'] ?? '';
$errors = [];
$success = '';
$member_data = [];

// Creates login credentials for new gym members automatically
function createMemberAccount($mem_id, $name, $age) {
    global $conn;
    
    // Generate username and password
    $username = strtolower(str_replace(' ', '', $name)); // Just the name without spaces
    $password = strtolower(str_replace(' ', '', $name)) . $age; // name+age as password
    
    // Hash the password using PHP's password_hash function
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Create login entry
        $stmt = $conn->prepare("INSERT INTO login (uname, pwd, role, member_id) VALUES (?, ?, 'member', ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $mem_id);
        $stmt->execute();
        
        return array($username, $password);
    } catch (Exception $e) {
        error_log("Error creating member account: " . $e->getMessage());
        throw $e;
    }
}

// Handle gym selection for filtering payment plans using sessions
if (isset($_POST['gym_change']) && !empty($_POST['selected_gym_id'])) {
    // Store selected gym in session
    $_SESSION['selected_gym_id'] = $_POST['selected_gym_id'];
    
    // Store all current form data in session to preserve it
    $_SESSION['form_data'] = [
        'mem_id' => $_POST['mem_id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'age' => $_POST['age'] ?? '',
        'dob' => $_POST['dob'] ?? '',
        'mobileno' => $_POST['mobileno'] ?? '',
        'trainer_id' => $_POST['trainer_id'] ?? ''
    ];
    
    // Preserve edit mode if we're editing a member
    $edit_member_id = $_POST['edit_member_id'] ?? '';
    if (!empty($edit_member_id)) {
        // Redirect back to edit mode with the member ID
        header("Location: manage_member.php?action=edit&id=" . urlencode($edit_member_id));
    } else {
        // Redirect to clean URL without parameters (add mode)
        header("Location: manage_member.php");
    }
    exit();
}

// Fetch member data when editing existing member (NOW variables are defined)
if ($action === 'edit' && !empty($mem_id)) {
    try {
        $member_data = $memberModel->findById($mem_id);
        if (!$member_data) {
            $errors[] = "Member not found.";
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching member data.";
    }
}

// Get form data from session or member data
$selected_gym_id = $_SESSION['selected_gym_id'] ?? ($member_data['gym_id'] ?? '');
$form_data = $_SESSION['form_data'] ?? [];

// Clear temporary session data when starting fresh (not gym filtering or editing)
if (!isset($_POST['gym_change']) && $action !== 'edit' && $action !== 'success' && !isset($_SESSION['selected_gym_id'])) {
    unset($_SESSION['temp_gym_id'], $_SESSION['temp_mem_id'], $_SESSION['temp_name'], 
          $_SESSION['temp_age'], $_SESSION['temp_dob'], $_SESSION['temp_mobileno'], 
          $_SESSION['temp_trainer_id'], $_SESSION['selected_gym_id'], $_SESSION['form_data']);
}

// Process form submissions for adding/editing members
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
    }

    // Sanitize all input data
    $input_data = [
        'mem_id' => trim($_POST['mem_id'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'age' => trim($_POST['age'] ?? ''),
        'dob' => trim($_POST['dob'] ?? ''),
        'mobileno' => trim($_POST['mobileno'] ?? ''),
        'pay_id' => trim($_POST['pay_id'] ?? ''),
        'trainer_id' => trim($_POST['trainer_id'] ?? ''),
        'gym_id' => trim($_POST['gym_id'] ?? '')
    ];
    
    // Use the Member class validation method
    $errors = $memberModel->validate($input_data);
    
    // Additional validations specific to the form
    if (empty($input_data['pay_id'])) {
        $errors[] = "Payment plan is required.";
    }
    
    if (empty($input_data['trainer_id'])) {
        $errors[] = "Trainer is required.";
    }
    
    if (empty($input_data['gym_id'])) {
        $errors[] = "Gym is required.";
    }
    
    // Check if member ID already exists when adding new member
    if ($_POST['action'] === 'add') {
        if ($memberModel->findById($input_data['mem_id'])) {
            $errors[] = "Member ID already exists.";
        }
    }
    
    // Execute database operations if validation passes
    if (empty($errors)) {
        try {
            if ($_POST['action'] === 'add') {
                // Create new member using OOP method
                $memberModel->create($input_data);
                
                // Create member login account
                list($username, $password) = createMemberAccount($input_data['mem_id'], $input_data['name'], $input_data['age']);
                
                // Store success message and credentials in session instead of URL
                $_SESSION['success_message'] = "Member added successfully!";
                $_SESSION['login_credentials'] = array(
                    'username' => $username,
                    'password' => $password
                );
                
                // Clear temporary form data from session
                unset($_SESSION['temp_gym_id'], $_SESSION['temp_mem_id'], $_SESSION['temp_name'], 
                      $_SESSION['temp_age'], $_SESSION['temp_dob'], $_SESSION['temp_mobileno'], 
                      $_SESSION['temp_trainer_id'], $_SESSION['selected_gym_id'], $_SESSION['form_data']);
                
                // Redirect without the long success message in URL
                header("Location: manage_member.php?action=success");
                exit();
            } 
            elseif ($_POST['action'] === 'edit') {
                // Update existing member using OOP method
                $original_id = trim($_POST['original_id'] ?? '');
                $memberModel->update($original_id, $input_data);
                
                // Clear temporary form data from session after successful edit
                unset($_SESSION['selected_gym_id'], $_SESSION['form_data']);
                
                // Store success message in session for consistency
                $_SESSION['success_message'] = "Member updated successfully!";
                header("Location: manage_member.php?action=success");
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = "Database error occurred. Please try again.";
        }
    }
}

// Handle member deletion with transaction safety
if ($action === 'delete' && !empty($mem_id)) {
    try {
        if ($memberModel->delete($mem_id)) {
            $_SESSION['success_message'] = "Member deleted successfully!";
            header("Location: manage_member.php?action=success");
        } else {
            $errors[] = "Member not found.";
        }
        exit();
        
    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        $errors[] = "Error deleting member: " . $e->getMessage();
    }
}

// Load dropdown options for form selects (payments, trainers, gyms)
try {
    // Initialize other models for dropdown data
    $paymentModel = new Payment();
    $trainerModel = new Trainer();
    $gymModel = new Gym();
    
    // If gym is selected via session or from member data, filter payment plans by gym
    if (!empty($selected_gym_id)) {
        $payment_options = $paymentModel->getByGym($selected_gym_id);
    } else {
        // If no gym selected, show empty payment options
        $payment_options = [];
    }
    
    $trainer_options = $trainerModel->getAll();
    $gym_options = $gymModel->getAll();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error fetching dropdown options.";
}

// Setup pagination and search for members list
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // Get members with pagination and search using OOP method
    $members_data = $memberModel->getAllWithDetails($search, $limit, $offset);
    $members = $members_data['members'];
    $total_records = $members_data['total'];
    $total_pages = ceil($total_records / $limit);
} catch (Exception $e) {
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
    <link rel="stylesheet" href="member_management.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
</head>
<body>
  <?php include('../components/navbar/navbar.php'); ?>
    
    <div class="app-container">
        <?php include('../components/sidebar/sidebar.php'); ?>
        
        <div class="main-content">
            <!-- Form Section at the TOP -->
            <div class="form-section <?= !empty($member_data) ? 'edit-mode' : '' ?>">
                <div class="form-header">
                    <h2 class="form-title">
                        <?= empty($member_data) ? 'Add New Member' : 'Edit Member' ?>
                    </h2>
                    <?php if (!empty($member_data)): ?>
                        <a href="manage_member.php" class="btn btn-success">+ Add New Member</a>
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
                
                <?php 
                // Display success message with login credentials from session
                if ($action === 'success' && isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <h4><?= htmlspecialchars($_SESSION['success_message']) ?></h4>
                        <?php if (isset($_SESSION['login_credentials'])): ?>
                            <div class="login-credentials">
                                <h5 style="color: white !important;">🔑 Login Credentials Created:</h5>
                                <p style="color: white !important; font-weight: bold;"><strong>Username:</strong> <?= htmlspecialchars($_SESSION['login_credentials']['username']) ?></p>
                                <p style="color: white !important; font-weight: bold;"><strong>Password:</strong> <?= htmlspecialchars($_SESSION['login_credentials']['password']) ?></p>
                                <small style="color: white !important;">Please share these credentials with the member securely.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                    // Clear the session variables after displaying
                    unset($_SESSION['success_message']);
                    unset($_SESSION['login_credentials']);
                    ?>
                <?php endif; ?>
                
                <form method="post" action="manage_member.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="<?= empty($member_data) ? 'add' : 'edit' ?>">
                    
                    <?php if (!empty($member_data)): ?>
                        <input type="hidden" name="original_id" value="<?= htmlspecialchars($member_data['mem_id']) ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mem_id">Member ID</label>
                            <input type="text" id="mem_id" name="mem_id" 
                                   value="<?= htmlspecialchars($member_data['mem_id'] ?? $form_data['mem_id'] ?? '') ?>" 
                                   <?= !empty($member_data) ? 'readonly' : '' ?> required>
                            <?php if (!empty($member_data)): ?>
                                <small class="readonly-note">ID cannot be changed after creation</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Member Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?= htmlspecialchars($member_data['name'] ?? $form_data['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" 
                                   value="<?= htmlspecialchars($member_data['age'] ?? $form_data['age'] ?? '') ?>" 
                                   min="12" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" 
                                   value="<?= htmlspecialchars($member_data['dob'] ?? $form_data['dob'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobileno">Mobile Number</label>
                            <input type="tel" id="mobileno" name="mobileno" 
                                   value="<?= htmlspecialchars($member_data['mobileno'] ?? $form_data['mobileno'] ?? '') ?>" 
                                   pattern="[0-9]{10,15}" required>
                            <div class="form-text">Enter 10 digits Sri Lankan number</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="gym_id">Gym</label>
                            <select id="gym_id" name="gym_id" required onchange="handleGymChange(this.value)">
                                <option value="">Select Gym</option>
                                <?php foreach ($gym_options as $gym): ?>
                                    <option value="<?= htmlspecialchars($gym['gym_id']) ?>" 
                                        <?= (isset($member_data['gym_id']) && $member_data['gym_id'] === $gym['gym_id']) ? 'selected' : '' ?>
                                        <?= (isset($selected_gym_id) && $selected_gym_id === $gym['gym_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gym['gym_id']) ?> - <?= htmlspecialchars($gym['gym_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="pay_id">Payment Plan</label>
                            <select id="pay_id" name="pay_id" required>
                                <?php if (empty($selected_gym_id)): ?>
                                    <option value="">Select Gym First</option>
                                <?php else: ?>
                                    <option value="">Select Payment Plan</option>
                                    <?php foreach ($payment_options as $payment): ?>
                                        <option value="<?= htmlspecialchars($payment['pay_id']) ?>" 
                                            <?= (isset($member_data['pay_id']) && $member_data['pay_id'] === $payment['pay_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($payment['pay_id']) ?> - LKR <?= htmlspecialchars($payment['amount']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="trainer_id">Trainer</label>
                            <select id="trainer_id" name="trainer_id" required>
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainer_options as $trainer): ?>
                                    <option value="<?= htmlspecialchars($trainer['trainer_id']) ?>" 
                                        <?= (isset($member_data['trainer_id']) && $member_data['trainer_id'] === $trainer['trainer_id']) ? 'selected' : '' ?>
                                        <?= (isset($form_data['trainer_id']) && $form_data['trainer_id'] === $trainer['trainer_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($trainer['trainer_id']) ?> - <?= htmlspecialchars($trainer['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn <?= empty($member_data) ? 'btn-success' : 'btn-primary' ?>">
                            <?= empty($member_data) ? 'Add Member' : 'Update Member' ?>
                        </button>
                        <?php if (!empty($member_data)): ?>
                            <a href="manage_member.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Hidden form for gym change functionality (moved outside main form) -->
                <form id="gymChangeForm" method="post" action="manage_member.php" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="gym_change" value="1">
                    <input type="hidden" name="selected_gym_id" id="hidden_gym_id">
                    <input type="hidden" name="edit_member_id" id="hidden_edit_member_id" value="<?= htmlspecialchars($member_data['mem_id'] ?? '') ?>">
                    <input type="hidden" name="mem_id" id="hidden_mem_id">
                    <input type="hidden" name="name" id="hidden_name">
                    <input type="hidden" name="age" id="hidden_age">
                    <input type="hidden" name="dob" id="hidden_dob">
                    <input type="hidden" name="mobileno" id="hidden_mobileno">
                    <input type="hidden" name="trainer_id" id="hidden_trainer_id">
                </form>
            </div>
            
            <!-- Table Section Below the Form -->
            <div class="table-section">
                <div class="search-box">
                    <form method="get" style="width: 100%;">
                        <table>
                            <tr>
                                <td><input type="text" name="search" placeholder="Search members..." 
                               value="<?= htmlspecialchars($search ?? '') ?>"></td>
                                <td><button type="submit">Search</button></td>
                            </tr>
                        </table>
                        
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Age</th>
                            <th>DOB</th>
                            <th>Mobile</th>
                            <th>Payment Plan</th>
                            <th>Trainer</th>
                            <th>Gym</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($members)): ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['mem_id']) ?></td>
                                    <td><?= htmlspecialchars($member['name']) ?></td>
                                    <td><?= htmlspecialchars($member['age']) ?></td>
                                    <td><?= htmlspecialchars($member['dob']) ?></td>
                                    <td><?= htmlspecialchars($member['mobileno']) ?></td>
                                    <td>
                                        LKR <?= htmlspecialchars($member['amount'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($member['trainer_name'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($member['gym_name'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <button class="action-btn edit-btn" 
                                                onclick="location.href='manage_member.php?action=edit&id=<?= urlencode($member['mem_id']) ?>'">
                                            Edit
                                        </button>
                                        <button class="action-btn delete-btn" 
                                                data-member-id="<?= htmlspecialchars($member['mem_id']) ?>"
                                                data-member-name="<?= htmlspecialchars($member['name']) ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">No members found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
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

    <!-- JavaScript for gym change functionality -->
    <script>
        function handleGymChange(gymId) {
            if (gymId) {
                // Capture current form values
                document.getElementById('hidden_gym_id').value = gymId;
                document.getElementById('hidden_mem_id').value = document.getElementById('mem_id').value;
                document.getElementById('hidden_name').value = document.getElementById('name').value;
                document.getElementById('hidden_age').value = document.getElementById('age').value;
                document.getElementById('hidden_dob').value = document.getElementById('dob').value;
                document.getElementById('hidden_mobileno').value = document.getElementById('mobileno').value;
                document.getElementById('hidden_trainer_id').value = document.getElementById('trainer_id').value;
                
                // Submit the hidden form
                document.getElementById('gymChangeForm').submit();
            }
        }

        // Delete confirmation
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const memberId = this.getAttribute('data-member-id');
                    const memberName = this.getAttribute('data-member-name');
                    
                    if (confirm(`Are you sure you want to delete member "${memberName}" (ID: ${memberId})? This action cannot be undone.`)) {
                        window.location.href = `manage_member.php?action=delete&id=${encodeURIComponent(memberId)}`;
                    }
                });
            });
        });
    </script>
</body>
</html>