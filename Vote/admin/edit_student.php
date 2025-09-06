<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

// Direct DB connection if include is failing
$conn = mysqli_connect("localhost", "root", "", "voting");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch student details
$sql = "SELECT * FROM students WHERE id='$student_id'";
$result = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($result);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Voting System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        .error { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <span class="menu-bar" id="show" onclick="showMenu()">&#9776;</span>
            <span class="menu-bar" id="hide" onclick="hideMenu()">&#9776;</span>
            <span class="logo">Voting System</span>
            <span class="profile" onclick="showProfile()">
                <img src="../res/user3.jpg" alt="">
                <label><?php echo $_SESSION['name']; ?></label>
            </span>
        </div>

        <!-- ✅ Menu bar -->
        <?php include '../includes/menu.php'; ?>

        <!-- Profile Panel -->
        <div id="profile-panel">
            <i class="fa-solid fa-circle-xmark" onclick="hidePanel()"></i>
            <div class="dp"><img src="../res/user3.jpg" alt=""></div>
            <div class="info">
                <h2><?php echo $_SESSION['name']; ?></h2>
                <h5>Admin</h5>
            </div>
            <div class="link">
                <a href="../includes/admin-logout.php" class="del">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="container">
            <div class="heading"><h1>Online Voting System</h1></div>
            <form action="update_student.php" method="POST">
    <div class="form">
        <h4>Edit Student Information</h4>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">

        <label class="label">Student ID:</label>
        <input type="text" name="student_id" class="input" value="<?php echo $student['student_id']; ?>">

        <label class="label">Firstname:</label>
        <input type="text" name="fname" class="input" value="<?php echo $student['fname']; ?>">

        <label class="label">Lastname:</label>
        <input type="text" name="lname" class="input" value="<?php echo $student['lname']; ?>">

        <label class="label">Email:</label>
        <input type="email" name="email" class="input" value="<?php echo $student['email']; ?>" required>

        <label class="label">Date of Birth:</label>
        <input type="text" name="dob" id="dob" class="input" value="<?php echo $student['dob']; ?>">

        <label class="label">Gender:</label>
        <input type="radio" value="male" name="gender" <?php if ($student['gender']=="male") echo "checked"; ?>>Male
        <input type="radio" value="female" name="gender" <?php if ($student['gender']=="female") echo "checked"; ?>>Female
        <input type="radio" value="other" name="gender" <?php if ($student['gender']=="other") echo "checked"; ?>>Other

        <label class="label">Phone Number:</label>
        <input type="text" name="phone" class="input" value="<?php echo $student['phone']; ?>">

        <label class="label">Address:</label>
        <input type="text" name="address" class="input" value="<?php echo $student['address']; ?>">

        <button class="button" name="update">Update</button>
    </div>
</form>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
