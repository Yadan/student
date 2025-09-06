<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

// ✅ DB Connection
$conn = mysqli_connect("localhost", "root", "", "voting");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // ✅ Delete candidate by id
    $sql = "DELETE FROM candidate WHERE id='$id'";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Candidate deleted successfully!";
    } else {
        $_SESSION['error'] = "Delete failed: " . mysqli_error($conn);
    }
}

// ✅ Redirect back to candidate.php
header("Location: candidates.php");
exit();
?>
