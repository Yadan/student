<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

// DB Connection
$conn = mysqli_connect("localhost", "root", "", "voting");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_POST['update'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $fname = mysqli_real_escape_string($conn, $_POST['fname']);
    $lname = mysqli_real_escape_string($conn, $_POST['lname']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // ✅ Update only students table (voters.php displays from here)
    $sql_students = "UPDATE students 
                     SET student_id='$student_id', fname='$fname', lname='$lname', dob='$dob',
                         gender='$gender', phone='$phone', address='$address' 
                     WHERE id='$id'";

    if (mysqli_query($conn, $sql_students)) {
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
    }

    header("Location: voters.php");
    exit();
}
?>