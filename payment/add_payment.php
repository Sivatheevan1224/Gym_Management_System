<?php
require('../db.php');

$errors = array(); 
if (isset($_REQUEST['payment'])) {
    $pay_id = mysqli_real_escape_string($conn, $_REQUEST['id']);
    $amount = mysqli_real_escape_string($conn, $_REQUEST['amount']);
    $gym_id = mysqli_real_escape_string($conn, $_REQUEST['gym_id']);
    
    $user_check_query = "SELECT * FROM payment WHERE pay_id='$pay_id' LIMIT 1";
    $result = mysqli_query($conn, $user_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) { 
        if ($user['pay_id'] === $pay_id) {
            array_push($errors, "<div class='alert alert-warning'><b>ID already exists</b></div>");
        }
    }

    if (count($errors) == 0) {
        $query = "INSERT INTO payment (pay_id,amount,gym_id) 
              VALUES('$pay_id','$amount','$gym_id')";
        $sql=mysqli_query($conn, $query);
        if ($sql) {
            $msg="<div class='alert alert-success'><b>Payment area added successfully</b></div>";
        }else{
            $msg="<div class='alert alert-warning'><b>Payment area not added</b></div>";
        }
    }
}

// Fetch all gym IDs for dropdown
$gym_options = array();
$gym_query = "SELECT gym_id, gym_name FROM gym";
$gym_result = mysqli_query($conn, $gym_query);
while ($row = mysqli_fetch_assoc($gym_result)) {
    $gym_options[] = $row;
}
?>

<div class="container">
    <form class="mt-3 form-group" method="post" action="">
        <h3>ADD PAYMENT AREA</h3>
        <?php include('../errors.php'); 
        echo @$msg;
        ?>
        <label class="mt-3">PAYMENT AREA ID</label>
        <input type="text" name="id" class="form-control" required>
        <label class="mt-3">AMOUNT</label>
        <input type="text" name="amount" class="form-control" required>
        
        <label class="mt-3">GYM</label>
        <select name="gym_id" class="form-control" required>
            <option value="">Select Gym</option>
            <?php foreach ($gym_options as $gym): ?>
                <option value="<?php echo htmlspecialchars($gym['gym_id']); ?>">
                    <?php echo htmlspecialchars($gym['gym_id']) . " - " . htmlspecialchars($gym['gym_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button class="btn btn-dark mt-3" type="submit" name="payment">ADD</button>
    </form>
</div>