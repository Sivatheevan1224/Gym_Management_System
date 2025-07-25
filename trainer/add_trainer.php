<?php
require('../db.php');

$errors = array(); 
if (isset($_REQUEST['trainer'])) {
    $trainer_id = mysqli_real_escape_string($conn, $_REQUEST['id']);
    $name = mysqli_real_escape_string($conn, $_REQUEST['name']);
    $time = mysqli_real_escape_string($conn, $_REQUEST['time']);
    $mobileno = mysqli_real_escape_string($conn, $_REQUEST['mobileno']);
    $pay_id = mysqli_real_escape_string($conn, $_REQUEST['pay_id']);
    
    $user_check_query = "SELECT * FROM trainer WHERE trainer_id='$trainer_id' LIMIT 1";
    $result = mysqli_query($conn, $user_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) { 
        if ($user['trainer_id'] === $trainer_id) {
            array_push($errors, "<div class='alert alert-warning'><b>ID already exists</b></div>");
        }
    }

    if (count($errors) == 0) {
        $query = "INSERT INTO trainer (trainer_id,name,time,mobileno,pay_id) 
              VALUES('$trainer_id','$name','$time','$mobileno','$pay_id')";
        $sql=mysqli_query($conn, $query);
        if ($sql) {
            $msg="<div class='alert alert-success'><b>Trainer added successfully</b></div>";
        }else{
            $msg="<div class='alert alert-warning'><b>Trainer not added</b></div>";
        }
    }
}

// Fetch all payment IDs for dropdown
$payment_options = array();
$payment_query = "SELECT pay_id, amount FROM payment";
$payment_result = mysqli_query($conn, $payment_query);
while ($row = mysqli_fetch_assoc($payment_result)) {
    $payment_options[] = $row;
}
?>

<div class="container">
    <form class="mt-3 form-group" method="post" action="">
        <h3>ADD TRAINER</h3>
        <?php include('../errors.php'); 
        echo @$msg;
        ?>
        <label class="mt-3">TRAINER ID</label>
        <input type="text" name="id" class="form-control" required>
        <label class="mt-3">TRAINER NAME</label>
        <input type="text" name="name" class="form-control" required>
        <label class="mt-3">TIME</label>
        <input type="text" name="time" class="form-control" required>
        <label class="mt-3">MOBILE NO</label>
        <input type="text" name="mobileno" class="form-control" required>
        
        <label class="mt-3">PAYMENT PLAN</label>
        <select name="pay_id" class="form-control" required>
            <option value="">Select Payment Plan</option>
            <?php foreach ($payment_options as $payment): ?>
                <option value="<?php echo htmlspecialchars($payment['pay_id']); ?>">
                    <?php echo htmlspecialchars($payment['pay_id']) . " - LKR" . htmlspecialchars($payment['amount']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button class="btn btn-dark mt-3" type="submit" name="trainer">ADD</button>
    </form>
</div>