<?php
require('../db.php');

$err = "";
$name = $_POST['name'] ?? "";
$action = $_GET['action'] ?? "";
$mem_id_param = $_GET['id'] ?? "";

// DELETE MEMBER
if ($action === 'delete' && $mem_id_param) {
    $mem_id = mysqli_real_escape_string($conn, $mem_id_param);
    if (mysqli_query($conn, "DELETE FROM member WHERE mem_id='$mem_id'")) {
        header("Location: home.php?info=manage_member");
        exit();
    } else {
        echo "Error deleting member: " . mysqli_error($conn);
    }
}

// UPDATE MEMBER
if (isset($_POST['member_update'])) {
    $original_id = mysqli_real_escape_string($conn, $_POST['original_id']);
    $mem_id_new = mysqli_real_escape_string($conn, $_POST['id']);
    $name_new = mysqli_real_escape_string($conn, $_POST['name']);
    $age_new = mysqli_real_escape_string($conn, $_POST['age']);
    $dob_new = mysqli_real_escape_string($conn, $_POST['dob']);
    $mobileno_new = mysqli_real_escape_string($conn, $_POST['mobileno']);
    $pay_id_new = mysqli_real_escape_string($conn, $_POST['pay_id']);
    $trainer_id_new = mysqli_real_escape_string($conn, $_POST['trainer_id']);
    $gym_id_new = mysqli_real_escape_string($conn, $_POST['gym_id']);

    $update_sql = "UPDATE member SET mem_id='$mem_id_new', name='$name_new', age='$age_new', 
                  dob='$dob_new', mobileno='$mobileno_new', pay_id='$pay_id_new', 
                  trainer_id='$trainer_id_new', gym_id='$gym_id_new' 
                  WHERE mem_id='$original_id'";
    if (mysqli_query($conn, $update_sql)) {
        $err = "<div class='alert alert-success'>Member updated successfully.</div>";
    } else {
        $err = "<div class='alert alert-danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

// Fetch options for dropdowns
$payment_options = mysqli_query($conn, "SELECT pay_id, amount FROM payment");
$trainer_options = mysqli_query($conn, "SELECT trainer_id, name FROM trainer");
$gym_options = mysqli_query($conn, "SELECT gym_id, gym_name FROM gym");

// Show update form if requested
if ($action === 'update' && $mem_id_param) {
    $mem_id = mysqli_real_escape_string($conn, $mem_id_param);
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM member WHERE mem_id='$mem_id'"));
    if (!$res) {
        echo "<div class='alert alert-danger'>Member not found.</div>";
        exit();
    }
    ?>

    <div class="container">
        <form method="post" class="form-group mt-3" action="home.php?info=manage_member">
            <h3>UPDATE MEMBER</h3>
            <?php echo $err; ?>
            <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($res['mem_id']); ?>">
            <label class="mt-3">MEMBER ID</label>
            <input type="text" name="id" value="<?php echo htmlspecialchars($res['mem_id']); ?>" class="form-control" required>
            <label class="mt-3">MEMBER NAME</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($res['name']); ?>" class="form-control" required>
            <label class="mt-3">AGE</label>
            <input type="text" name="age" value="<?php echo htmlspecialchars($res['age']); ?>" class="form-control" required>
            <label class="mt-3">DOB</label>
            <input type="date" name="dob" value="<?php echo htmlspecialchars($res['dob']); ?>" class="form-control" required>
            <label class="mt-3">MOBILE NO</label>
            <input type="text" name="mobileno" value="<?php echo htmlspecialchars($res['mobileno']); ?>" class="form-control" required>
            
            <label class="mt-3">PAYMENT PLAN</label>
            <select name="pay_id" class="form-control" required>
                <option value="">Select Payment Plan</option>
                <?php while ($payment = mysqli_fetch_assoc($payment_options)): ?>
                    <option value="<?php echo htmlspecialchars($payment['pay_id']); ?>" <?php echo ($payment['pay_id'] == $res['pay_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($payment['pay_id']) . " - LKR " . htmlspecialchars($payment['amount']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label class="mt-3">TRAINER</label>
            <select name="trainer_id" class="form-control" required>
                <option value="">Select Trainer</option>
                <?php while ($trainer = mysqli_fetch_assoc($trainer_options)): ?>
                    <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>" <?php echo ($trainer['trainer_id'] == $res['trainer_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trainer['trainer_id']) . " - " . htmlspecialchars($trainer['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <label class="mt-3">GYM</label>
            <select name="gym_id" class="form-control" required>
                <option value="">Select Gym</option>
                <?php while ($gym = mysqli_fetch_assoc($gym_options)): ?>
                    <option value="<?php echo htmlspecialchars($gym['gym_id']); ?>" <?php echo ($gym['gym_id'] == $res['gym_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($gym['gym_id']) . " - " . htmlspecialchars($gym['gym_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" name="member_update" class="btn btn-dark mt-3">UPDATE</button>
            <a href="home.php?info=manage_member" class="btn btn-secondary mt-3 ms-2">Cancel</a>
        </form>
    </div>

    <?php
    exit();
}

// Display list + search
?>

<div class="container">
    <form class="form-group mt-3" method="post" action="home.php?info=manage_member">
        <h3 class="lead">SEARCH MEMBER</h3>
        <input type="text" name="name" class="form-control" placeholder="ENTER MEMBER NAME OR MEMBER ID" value="<?php echo htmlspecialchars($name); ?>">
    </form>

    <div class="container">
        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>MEMBER ID</th>
                    <th>MEMBER NAME</th>
                    <th>AGE</th>
                    <th>DOB</th>
                    <th>MOBILE NO</th>
                    <th>PAYMENT PLAN</th>
                    <th>TRAINER</th>
                    <th>GYM</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($name !== '') {
                    $name_esc = mysqli_real_escape_string($conn, $name);
                    $query = "SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name 
                             FROM member m
                             JOIN payment p ON m.pay_id = p.pay_id
                             JOIN trainer t ON m.trainer_id = t.trainer_id
                             JOIN gym g ON m.gym_id = g.gym_id
                             WHERE m.mem_id LIKE '%$name_esc%' OR m.name LIKE '%$name_esc%'";
                } else {
                    $query = "SELECT m.*, p.amount, t.name AS trainer_name, g.gym_name 
                             FROM member m
                             JOIN payment p ON m.pay_id = p.pay_id
                             JOIN trainer t ON m.trainer_id = t.trainer_id
                             JOIN gym g ON m.gym_id = g.gym_id";
                }

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['mem_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['age']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['mobileno']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['pay_id']) . " (LKR " . htmlspecialchars($row['amount']) . ")</td>";
                        echo "<td>" . htmlspecialchars($row['trainer_id']) . " (" . htmlspecialchars($row['trainer_name']) . ")</td>";
                        echo "<td>" . htmlspecialchars($row['gym_id']) . " (" . htmlspecialchars($row['gym_name']) . ")</td>";
                        echo "<td><a href='home.php?info=manage_member&action=update&id=" . urlencode($row['mem_id']) . "'><i class='fas fa-pencil-alt'></i></a></td>";
                        echo "<td><a href='home.php?info=manage_member&action=delete&id=" . urlencode($row['mem_id']) . "' onclick=\"return confirm('Are you sure you want to delete this member?');\"><i class='fas fa-trash-alt'></i></a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' class='text-center'>No members found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>