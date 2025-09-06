<?php
session_start();

// Reset error message
if(!isset($_SESSION['userLogin'])) $_SESSION['userLogin'] = 0;

// Database connection
$con = mysqli_connect("localhost", "root", "", "voting");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle login
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password_input = mysqli_real_escape_string($con, $_POST['password']);

// ---------- ADMIN LOGIN ----------
$admin_query = "SELECT * FROM admin WHERE email='$username' AND password='$password_input'";
$admin_result = mysqli_query($con, $admin_query);
if (mysqli_num_rows($admin_result) > 0) {
    $admin = mysqli_fetch_assoc($admin_result);
    $_SESSION['adminLogin'] = 1;           // <-- Added
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['email'] = $admin['email'];
    $_SESSION['userLogin'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['name'] = $admin['fname'] . ' ' . $admin['lname']; // Optional
    header("location: admin/admin-panel.php");
    exit();
}


// ---------- STUDENT LOGIN ----------
$dob = convertDobFormat($password_input); // Only valid if MMDDYYYY
if ($dob) {
    $student_query = "SELECT * FROM students WHERE student_id='$username' AND dob='$dob'";
    $student_result = mysqli_query($con, $student_query);
    if (mysqli_num_rows($student_result) > 0) {
        $student = mysqli_fetch_assoc($student_result);
        $_SESSION['id'] = $student['id'];
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['fname'] = $student['fname'];
        $_SESSION['lname'] = $student['lname'];
        $_SESSION['voted'] = $student['voted'];
        $_SESSION['userLogin'] = 1;
        $_SESSION['role'] = 'student';
        $_SESSION['phone'] = $student['phone']; // <-- Add this line
        $_SESSION['idcard'] = $student['idcard']; // if used in voting-system.php
        header("location: voting-system.php");
        exit();
    }


        // ---------- CANDIDATE LOGIN ----------
        $candidate_query = "SELECT * FROM candidates WHERE username='$username' AND dob='$dob'";
        $candidate_result = mysqli_query($con, $candidate_query);
        if (mysqli_num_rows($candidate_result) > 0) {
            $candidate = mysqli_fetch_assoc($candidate_result);
            $_SESSION['candidate_id'] = $candidate['id'];
            $_SESSION['username'] = $candidate['username'];
            $_SESSION['userLogin'] = 1;
            $_SESSION['role'] = 'candidate';
            header("location: candidate/candidate.php");
            exit();
        }
    }

    // If none match
    $_SESSION['error'] = "Invalid username or password";
}

// Function to convert MMDDYYYY to YYYY-MM-DD
function convertDobFormat($dob_input) {
    if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $dob_input, $matches)) {
        $month = $matches[1];
        $day = $matches[2];
        $year = $matches[3];
        if (checkdate($month, $day, $year)) {
            return $year . '-' . $month . '-' . $day;
        }
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Voting System</title>
<link rel="stylesheet" href="css/style.css">
<style>
.input { padding-right: 40px; box-sizing: border-box; }
.format-hint { font-size: 12px; color: #666; margin-top: 5px; }
.error { color: red; text-align: center; margin-top: 10px; }
</style>
</head>
<body>
<div class="container">
    <div class="heading"><h1>Online Voting System</h1></div>
    <div class="form">
        <h4>Login</h4>
        <form action="" method="POST">
            <label class="label">Username:</label>
            <input type="text" name="username" class="input" placeholder="Enter your Username" required>

            <label class="label">Password:</label>
            <input type="password" name="password" class="input" placeholder="Enter your Password" required>

            <button class="button" name="login">Login</button>
            <div class="link1">New user? <a href="registration.php">Register here</a></div>
        </form>
        <p class="error"><?php echo isset($_SESSION['error']) ? $_SESSION['error'] : ''; unset($_SESSION['error']); ?></p>
    </div>
</div>
</body>
</html>