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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // ✅ Get the position_name first from can_position
    $pos_res = mysqli_query($conn, "SELECT position_name FROM can_position WHERE id='$id'");
    if ($pos_res && mysqli_num_rows($pos_res) > 0) {
        $row = mysqli_fetch_assoc($pos_res);
        $position_name = mysqli_real_escape_string($conn, $row['position_name']);

        // ✅ Delete related records in can_position itself
        $sql_can = "DELETE FROM can_position WHERE position_name='$position_name'";
        if (mysqli_query($conn, $sql_can)) {
            $_SESSION['success'] = "Position deleted successfully!";
        } else {
            $_SESSION['error'] = "Delete failed: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Position not found.";
    }
}

header("Location: position.php");
exit();
?>
