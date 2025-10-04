<?php
include('db_connection.php');

// ✅ Default fallback values
$schoolYear = "Not Set";
$student_name = "Name not found";

// ✅ Fetch school year from students table for the current student
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    
    // First try to get school year from students table
    $sy_stmt = $conn->prepare("SELECT school_year FROM students WHERE id = ?");
    $sy_stmt->bind_param("i", $student_id);
    $sy_stmt->execute();
    $sy_result = $sy_stmt->get_result();
    
    if ($sy_result->num_rows > 0) {
        $row = $sy_result->fetch_assoc();
        $schoolYear = $row['school_year'] ?? "Not Set";
    }
}

// ✅ Fetch student name
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $name_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM students WHERE id = ?");
    $name_stmt->bind_param("i", $student_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();

    if ($name_result->num_rows > 0) {
        $name_row = $name_result->fetch_assoc();
        $student_name = $name_row['first_name'] . " " . $name_row['middle_name'] . " " . $name_row['last_name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal | Isiera</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/student_portal_nav.css" />
</head>
<body>

<div class="student-nav-container">
  <!-- Logo -->
  <a href="#" class="nav-item logo">
    <img src="assets/imgs/isiera.jpg" alt="Logo" />
  </a>

  <!-- Navigation Links -->
  <div class="nav-items-container" id="navItems">
    <a href="information.php" class="nav-item">
      <ion-icon name="person-circle-outline"></ion-icon>
      Information
    </a>
    <a href="attendance.php" class="nav-item">
      <ion-icon name="document-text-outline"></ion-icon>
      Attendance
    </a>
    <a href="#" class="nav-item">
      <ion-icon name="calendar-outline"></ion-icon>
      SY: <?= htmlspecialchars($schoolYear) ?>
    </a>
    <a href="student_setting.php" class="nav-item">
      <ion-icon name="settings-outline"></ion-icon>
      User: <?= htmlspecialchars($student_name) ?>
    </a>
    <a href="logout.php" class="nav-item">
      <ion-icon name="log-out-outline"></ion-icon>
      Sign Out
    </a>
  </div>

  <!-- Hamburger menu -->
  <div class="hamburger-menu" onclick="toggleMenu()">
    <div class="bar"></div>
    <div class="bar"></div>
    <div class="bar"></div>
  </div>
</div>

<!-- Ionicons -->
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<script>
function toggleMenu() {
  document.getElementById('navItems').classList.toggle('active');
}
</script>
</body>
</html>