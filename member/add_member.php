<?php
require('../db.php');

$errors = array(); 
if (isset($_REQUEST['member'])) {
    $mem_id = mysqli_real_escape_string($conn, $_REQUEST['id']);
    $name = mysqli_real_escape_string($conn, $_REQUEST['name']);
    $age = mysqli_real_escape_string($conn, $_REQUEST['age']);
    $dob = mysqli_real_escape_string($conn, $_REQUEST['dob']);
    $mobileno = mysqli_real_escape_string($conn, $_REQUEST['mobileno']);
    $pay_id = mysqli_real_escape_string($conn, $_REQUEST['pay_id']);
    $trainer_id = mysqli_real_escape_string($conn, $_REQUEST['trainer_id']);
    $gym_id = mysqli_real_escape_string($conn, $_REQUEST['gym_id']);
    
    $user_check_query = "SELECT * FROM member WHERE mem_id='$mem_id' LIMIT 1";
    $result = mysqli_query($conn, $user_check_query);
    $user = mysqli_fetch_assoc($result);
    
    if ($user) { 
        if ($user['mem_id'] === $mem_id) {
            array_push($errors, "<div class='alert alert-warning'><b>ID already exists</b></div>");
        }
    }

    if (count($errors) == 0) {
        $query = "INSERT INTO member (mem_id,name,age,dob,mobileno,pay_id,trainer_id,gym_id) 
              VALUES('$mem_id','$name','$age','$dob','$mobileno','$pay_id','$trainer_id','$gym_id')";
        $sql=mysqli_query($conn, $query);
        if ($sql) {
            $msg="<div class='alert alert-success'><b>Member added successfully</b></div>";
        }else{
            $msg="<div class='alert alert-warning'><b>Member not added</b></div>";
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

// Fetch all trainer IDs for dropdown
$trainer_options = array();
$trainer_query = "SELECT trainer_id, name FROM trainer";
$trainer_result = mysqli_query($conn, $trainer_query);
while ($row = mysqli_fetch_assoc($trainer_result)) {
    $trainer_options[] = $row;
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
    <form class="form-group mt-3" method="post" action="">
        <div><h3>ADD MEMBER</h3></div>
        <?php include('../errors.php'); 
        echo @$msg;
        ?>
        <label class="mt-3">MEMBER ID</label>
        <input type="text" name="id" class="form-control" required>
        <label class="mt-3">MEMBER NAME</label>
        <input type="text" name="name" class="form-control" required>
        <label class="mt-3">AGE</label>
        <input type="text" name="age" class="form-control" required>
        <label class="mt-3">DOB</label>
        <input type="date" name="dob" class="form-control" required>
        <label class="mt-3">MOBILE NO</label>
        <input type="text" name="mobileno" class="form-control" required>
        
        <label class="mt-3">PAYMENT PLAN</label>
        <select name="pay_id" class="form-control" required>
            <option value="">Select Payment Plan</option>
            <?php foreach ($payment_options as $payment): ?>
                <option value="<?php echo htmlspecialchars($payment['pay_id']); ?>">
                    <?php echo htmlspecialchars($payment['pay_id']) . " - LKR " . htmlspecialchars($payment['amount']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label class="mt-3">TRAINER</label>
        <select name="trainer_id" class="form-control" required>
            <option value="">Select Trainer</option>
            <?php foreach ($trainer_options as $trainer): ?>
                <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>">
                    <?php echo htmlspecialchars($trainer['trainer_id']) . " - " . htmlspecialchars($trainer['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label class="mt-3">GYM</label>
        <select name="gym_id" class="form-control" required>
            <option value="">Select Gym</option>
            <?php foreach ($gym_options as $gym): ?>
                <option value="<?php echo htmlspecialchars($gym['gym_id']); ?>">
                    <?php echo htmlspecialchars($gym['gym_id']) . " - " . htmlspecialchars($gym['gym_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button class="btn btn-dark mt-3" type="submit" name="member">ADD</button>
    </form>
</div>