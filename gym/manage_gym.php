<?php
require('../db.php');

$err = "";
$name = $_POST['name'] ?? "";
$action = $_GET['action'] ?? "";
$gym_id_param = $_GET['id'] ?? "";

// DELETE GYM
if ($action === 'delete' && $gym_id_param) {
    $gym_id = mysqli_real_escape_string($conn, $gym_id_param);

    // Delete related members, trainers, payments
    mysqli_query($conn, "DELETE FROM member WHERE trainer_id IN (SELECT trainer_id FROM trainer WHERE pay_id IN (SELECT pay_id FROM payment WHERE gym_id='$gym_id'))");
    mysqli_query($conn, "DELETE FROM trainer WHERE pay_id IN (SELECT pay_id FROM payment WHERE gym_id='$gym_id')");
    mysqli_query($conn, "DELETE FROM payment WHERE gym_id='$gym_id'");

    // Delete gym
    if (mysqli_query($conn, "DELETE FROM gym WHERE gym_id='$gym_id'")) {
        header("Location: home.php?info=manage_gym");
        exit();
    } else {
        $err = "Error deleting gym: " . mysqli_error($conn);
    }
}

// UPDATE GYM
if (isset($_POST['update_gym'])) {
    $original_id = mysqli_real_escape_string($conn, $_POST['original_id']);
    $gym_id_new = mysqli_real_escape_string($conn, $_POST['id']);
    $name_new = mysqli_real_escape_string($conn, $_POST['name']);
    $address_new = mysqli_real_escape_string($conn, $_POST['address']);
    $type_new = mysqli_real_escape_string($conn, $_POST['type']);

    $update_sql = "UPDATE gym SET gym_id='$gym_id_new', gym_name='$name_new', address='$address_new', type='$type_new' WHERE gym_id='$original_id'";
    if (mysqli_query($conn, $update_sql)) {
        $err = "<div class='alert alert-success'>Gym details updated successfully.</div>";
    } else {
        $err = "<div class='alert alert-danger'>Update failed: " . mysqli_error($conn) . "</div>";
    }
}

// Show update form if requested
if ($action === 'update' && $gym_id_param) {
    $gym_id = mysqli_real_escape_string($conn, $gym_id_param);
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM gym WHERE gym_id='$gym_id'"));
    if (!$res) {
        echo "<div class='alert alert-danger'>Gym not found.</div>";
        exit();
    }
    ?>

    <div class="container">
      <form method="post" class="form-group mt-3" action="home.php?info=manage_gym">
        <h3>Update Gym</h3>
        <?php echo $err; ?>
        <input type="hidden" name="original_id" value="<?php echo htmlspecialchars($res['gym_id']); ?>">
        <label class="mt-3">Gym ID</label>
        <input type="text" name="id" value="<?php echo htmlspecialchars($res['gym_id']); ?>" class="form-control" required>
        <label class="mt-3">Gym Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($res['gym_name']); ?>" class="form-control" required>
        <label class="mt-3">Gym Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($res['address']); ?>" class="form-control" required>
        <label class="mt-3">Gym Type</label>
        <input type="text" name="type" value="<?php echo htmlspecialchars($res['type']); ?>" class="form-control" required>
        <button type="submit" name="update_gym" class="btn btn-dark mt-3">Update</button>
        <a href="home.php?info=manage_gym" class="btn btn-secondary mt-3 ms-2">Cancel</a>
      </form>
    </div>

    <?php
    exit();
}

// DISPLAY LIST + SEARCH

?>

<div class="container">
  <form class="form-group mt-3" method="post" action="home.php?info=manage_gym">
    <h3 class="lead">SEARCH GYM</h3>
    <input type="text" name="name" class="form-control" placeholder="ENTER GYM NAME OR GYM ID" value="<?php echo htmlspecialchars($name); ?>">
  </form>

  <div class="container">
    <table class="table table-bordered table-hover mt-3">
      <thead>
        <tr>
          <th>GYM ID</th>
          <th>GYM NAME</th>
          <th>GYM ADDRESS</th>
          <th>GYM TYPE</th>
          <th>Update</th>
          <th>Delete</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($name !== '') {
            $name_esc = mysqli_real_escape_string($conn, $name);
            $query = "SELECT * FROM gym WHERE CONCAT(gym_id, gym_name, address, type) LIKE '%$name_esc%'";
        } else {
            $query = "SELECT * FROM gym";
        }

        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['gym_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['gym_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                echo "<td><a href='home.php?info=manage_gym&action=update&id=" . urlencode($row['gym_id']) . "'><i class='fas fa-pencil-alt'></i></a></td>";
                echo "<td><a href='home.php?info=manage_gym&action=delete&id=" . urlencode($row['gym_id']) . "' onclick=\"return confirm('Are you sure you want to delete this gym?');\"><i class='fas fa-trash-alt'></i></a></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center'>No gyms found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
