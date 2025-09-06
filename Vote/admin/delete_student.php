<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

// Database connection
$conn = mysqli_connect("localhost", "root", "", "voting");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get student ID from URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete student from students table
    $sql = "DELETE FROM students WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Student deleted successfully!";
    } else {
        $_SESSION['error'] = "Delete failed: " . mysqli_error($conn);
    }
}

header("Location: voters.php");
exit();
?>
