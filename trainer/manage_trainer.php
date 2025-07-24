<?php
require('../db.php');

$err = "";
$name = $_POST['name'] ?? "";
$action = $_GET['action'] ?? "";
$trainer_id_param = $_GET['id'] ?? "";

// DELETE TRAINER
if ($action === 'delete' && $trainer_id_param) {
    $trainer_id = mysqli_real_escape_string($conn, $trainer_id_param);

    // Delete related members first
    $del_mem = mysqli_query($conn, "DELETE FROM member WHERE trainer_id='$trainer_id'");

    if ($del_mem) {
        $del_trainer = mysqli_query($conn, "DELETE FROM trainer WHERE trainer_id='$trainer_id'");
        if ($del_trainer) {
            header("Location: home.php?info=manage_trainer");
            exit();
        } else {
            echo "Error deleting trainer: " . mysqli_error($conn);
        }
    } else {
        echo "Error deleting members: " . mysqli_error($conn);
    }
}

// UPDATE TRAINER
if (isset($_POST['trainer_update'])) {
    $original_id = mysqli_real_escape_string($conn, $_POST['original_id']);
    $trainer_id_new = mysqli_real_escape_string($conn, $_POST['id']);
    $name_new = mysqli_real_escape_string($conn, $_POST['name']);
    $time_new = mysqli_real_escape_string($conn, $_POST['time']);
    $mobileno_new = mysqli_real_escape_string($conn, $_POST['mobileno']);

    $update_sql = "UPDATE trainer SET trainer_id='$trainer_id_new', name='$name_new', time='$time_new', mobileno='$mobileno_new' WHERE trainer_id='$original_id'";
    if (mysqli_query($conn, $update_sql)) {
        $err = "<div class='alert alert-success'>Trainer updated successfully.</div>";
    } else {
        $err = "<div class='alert alert-danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

// Show update form if requested
if ($action === 'update' && $trainer_id_param) {
    $trainer_id = mysqli_real_escape_string($conn, $trainer_id_param);
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM trainer WHERE trainer_id='$trainer_id'"));
    if (!$res) {
        echo "<div class='alert alert-danger'>Trainer not found.</div>";
        exit();
    }
    ?>

    <div class="container">
        <form method="post" class="form-group mt-3" action="home.php?info=manage_trainer">
            <h3>UPDATE TRAINER</h3>
            <?php echo $err; ?>
            <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($res['trainer_id']); ?>">
            <label class="mt-3">TRAINER ID</label>
            <input type="text" name="id" value="<?php echo htmlspecialchars($res['trainer_id']); ?>" class="form-control" required>
            <label class="mt-3">TRAINER NAME</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($res['name']); ?>" class="form-control" required>
            <label class="mt-3">TIME</label>
            <input type="text" name="time" value="<?php echo htmlspecialchars($res['time']); ?>" class="form-control" required>
            <label class="mt-3">MOBILE NO</label>
            <input type="text" name="mobileno" value="<?php echo htmlspecialchars($res['mobileno']); ?>" class="form-control" required>
            <button type="submit" name="trainer_update" class="btn btn-dark mt-3">UPDATE</button>
            <a href="home.php?info=manage_trainer" class="btn btn-secondary mt-3 ms-2">Cancel</a>
        </form>
    </div>

    <?php
    exit();
}

// Display list + search
?>

<div class="container">
    <form class="form-group mt-3" method="post" action="home.php?info=manage_trainer">
        <h3 class="lead">SEARCH TRAINER</h3>
        <input type="text" name="name" class="form-control" placeholder="ENTER TRAINER NAME OR TRAINER ID" value="<?php echo htmlspecialchars($name); ?>">
    </form>

    <div class="container">
        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>TRAINER ID</th>
                    <th>NAME</th>
                    <th>TIME</th>
                    <th>MOBILE NO</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($name !== '') {
                    $name_esc = mysqli_real_escape_string($conn, $name);
                    $query = "SELECT * FROM trainer WHERE trainer_id LIKE '%$name_esc%' OR name LIKE '%$name_esc%'";
                } else {
                    $query = "SELECT * FROM trainer";
                }

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['trainer_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['time']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['mobileno']) . "</td>";
                        echo "<td><a href='home.php?info=manage_trainer&action=update&id=" . urlencode($row['trainer_id']) . "'><i class='fas fa-pencil-alt'></i></a></td>";
                        echo "<td><a href='home.php?info=manage_trainer&action=delete&id=" . urlencode($row['trainer_id']) . "' onclick=\"return confirm('Are you sure you want to delete this trainer?');\"><i class='fas fa-trash-alt'></i></a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No trainers found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
