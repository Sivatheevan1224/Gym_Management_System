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
    $package_new = mysqli_real_escape_string($conn, $_POST['package']);
    $mobileno_new = mysqli_real_escape_string($conn, $_POST['mobileno']);

    $update_sql = "UPDATE member SET mem_id='$mem_id_new', name='$name_new', age='$age_new', dob='$dob_new', package='$package_new', mobileno='$mobileno_new' WHERE mem_id='$original_id'";
    if (mysqli_query($conn, $update_sql)) {
        $err = "<div class='alert alert-success'>Member updated successfully.</div>";
    } else {
        $err = "<div class='alert alert-danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

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
            <label class="mt-3">PACKAGE</label>
            <input type="text" name="package" value="<?php echo htmlspecialchars($res['package']); ?>" class="form-control" required>
            <label class="mt-3">MOBILE NO</label>
            <input type="text" name="mobileno" value="<?php echo htmlspecialchars($res['mobileno']); ?>" class="form-control" required>
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
                    <th>PACKAGE</th>
                    <th>MOBILE NO</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($name !== '') {
                    $name_esc = mysqli_real_escape_string($conn, $name);
                    $query = "SELECT * FROM member WHERE mem_id LIKE '%$name_esc%' OR name LIKE '%$name_esc%'";
                } else {
                    $query = "SELECT * FROM member";
                }

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['mem_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['age']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['package']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['mobileno']) . "</td>";
                        echo "<td><a href='home.php?info=manage_member&action=update&id=" . urlencode($row['mem_id']) . "'><i class='fas fa-pencil-alt'></i></a></td>";
                        echo "<td><a href='home.php?info=manage_member&action=delete&id=" . urlencode($row['mem_id']) . "' onclick=\"return confirm('Are you sure you want to delete this member?');\"><i class='fas fa-trash-alt'></i></a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>No members found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
