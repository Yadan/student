<?php
session_start();
error_reporting(0);

$con = mysqli_connect("localhost", "root", "", "voting");
if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}

if(isset($_POST['register'])) {
    $student_id = mysqli_real_escape_string($con, $_POST['student_id']);
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $dob = mysqli_real_escape_string($con, $_POST['dob']); // YYYY-MM-DD
    $gender = mysqli_real_escape_string($con, $_POST['gender']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || !strtotime($dob)) {
        $_SESSION['error'] = "Invalid date format. Use the calendar to select a valid date.";
        header("location: registration.php"); exit();
    }

    // Validate phone
    if(!preg_match('/^09\d{9}$/', $phone)) {
        $_SESSION['error'] = "Phone number must start with 09 and be 11 digits";
        header("location: registration.php"); exit();
    }

    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['error'] = "Invalid email address";
        header("location: registration.php"); exit();
    }

    // Check Student ID uniqueness
    $check_id = mysqli_query($con, "SELECT * FROM register WHERE student_id='$student_id'");
    if(mysqli_num_rows($check_id) > 0){
        $_SESSION['error'] = "Student ID already registered";
        header("location: registration.php"); exit();
    }

    // Check phone uniqueness
    $check_phone = mysqli_query($con, "SELECT * FROM register WHERE phone='$phone'");
    if(mysqli_num_rows($check_phone) > 0){
        $_SESSION['error'] = "Phone number already registered";
        header("location: registration.php"); exit();
    }

    // Check email uniqueness
    $check_email = mysqli_query($con, "SELECT * FROM register WHERE email='$email'");
    if(mysqli_num_rows($check_email) > 0){
        $_SESSION['error'] = "Email already registered";
        header("location: registration.php"); exit();
    }

    // Insert record
    $query = "INSERT INTO register (student_id, fname, lname, email, dob, gender, phone, address)
              VALUES ('$student_id', '$fname', '$lname', '$email', '$dob', '$gender', '$phone', '$address')";

    if(mysqli_query($con, $query)){
        $_SESSION['success'] = "You have registered successfully!";
        header("location: registration.php"); exit();
    } else {
        $_SESSION['error'] = "Registration failed: ".mysqli_error($con);
        header("location: registration.php"); exit();
    }
}
?>
