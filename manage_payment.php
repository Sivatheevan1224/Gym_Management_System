<?php
require('db.php');

$err = "";
$id = $_POST['id'] ?? "";
$action = $_GET['action'] ?? "";
$pay_id_param = $_GET['id'] ?? "";

// DELETE PAYMENT AREA
if ($action === 'delete' && $pay_id_param) {
    $pay_id = mysqli_real_escape_string($conn, $pay_id_param);

    // Delete related members and trainers first
    mysqli_query($conn, "DELETE FROM member WHERE trainer_id IN (SELECT trainer_id FROM trainer WHERE pay_id='$pay_id')");
    mysqli_query($conn, "DELETE FROM trainer WHERE pay_id='$pay_id'");

    // Delete payment
    if (mysqli_query($conn, "DELETE FROM payment WHERE pay_id='$pay_id'")) {
        header("Location: home.php?info=manage_payment");
        exit();
    } else {
        $err = "Error deleting payment area: " . mysqli_error($conn);
    }
}

// UPDATE PAYMENT AREA
if (isset($_POST['update_payment'])) {
    $original_id = mysqli_real_escape_string($conn, $_POST['original_id']);
    $pay_id_new = mysqli_real_escape_string($conn, $_POST['id']);
    $amount_new = mysqli_real_escape_string($conn, $_POST['amount']);

    $update_sql = "UPDATE payment SET pay_id='$pay_id_new', amount='$amount_new' WHERE pay_id='$original_id'";
    if (mysqli_query($conn, $update_sql)) {
        $err = "<div class='alert alert-success'>Payment Area updated successfully.</div>";
    } else {
        $err = "<div class='alert alert-danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

// Show update form if requested
if ($action === 'update' && $pay_id_param) {
    $pay_id = mysqli_real_escape_string($conn, $pay_id_param);
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payment WHERE pay_id='$pay_id'"));
    if (!$res) {
        echo "<div class='alert alert-danger'>Payment area not found.</div>";
        exit();
    }
    ?>

    <div class="container">
        <form method="post" class="form-group mt-3" action="home.php?info=manage_payment">
            <h3>UPDATE PAYMENT AREA</h3>
            <?php echo $err; ?>
            <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($res['pay_id']); ?>">
            <label class="mt-3">PAYMENT AREA ID</label>
            <input type="text" name="id" value="<?php echo htmlspecialchars($res['pay_id']); ?>" class="form-control" required>
            <label class="mt-3">AMOUNT</label>
            <input type="text" name="amount" value="<?php echo htmlspecialchars($res['amount']); ?>" class="form-control" required>
            <button type="submit" name="update_payment" class="btn btn-dark mt-3">UPDATE</button>
            <a href="home.php?info=manage_payment" class="btn btn-secondary mt-3 ms-2">Cancel</a>
        </form>
    </div>

    <?php
    exit();
}

// DISPLAY LIST + SEARCH
?>

<div class="container">
    <form class="form-group mt-3" method="post" action="home.php?info=manage_payment">
        <h3 class="lead">SEARCH PAYMENT AREA</h3>
        <input type="text" name="id" class="form-control" placeholder="ENTER PAYMENT AREA ID" value="<?php echo htmlspecialchars($id); ?>">
    </form>

    <div class="container">
        <table class="table table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>PAYMENT AREA ID</th>
                    <th>AMOUNT</th>
                    <th>GYM ID</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($id !== '') {
                    $id_esc = mysqli_real_escape_string($conn, $id);
                    $query = "SELECT * FROM payment WHERE pay_id LIKE '%$id_esc%'";
                } else {
                    $query = "SELECT * FROM payment";
                }

                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['pay_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['amount']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['gym_id']) . "</td>";
                        echo "<td><a href='home.php?info=manage_payment&action=update&id=" . urlencode($row['pay_id']) . "'><i class='fas fa-pencil-alt'></i></a></td>";
                        echo "<td><a href='home.php?info=manage_payment&action=delete&id=" . urlencode($row['pay_id']) . "' onclick=\"return confirm('Are you sure you want to delete this payment area?');\"><i class='fas fa-trash-alt'></i></a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No payment areas found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
