<?php
session_start();

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$admin_name = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clerk Homepage | Isiera</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eafaf1; /* light green background */
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 310px; /* keep space for sidebar */
            padding: 40px;
        }

        .welcome-box {
            background: #ffffff;
            padding: 40px; /* reduced padding */
            border-radius: 15px; /* slightly smaller corners */
            box-shadow: 0px 5px 15px rgba(0,0,0,0.12);
            text-align: center;
            max-width: 750px; /* smaller width */
            margin: 20px auto;
            border-top: 8px solid #8bc34a; /* green accent */
        }

        .welcome-box h1 {
            font-size: 2rem; /* smaller title */
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .welcome-box p {
            font-size: 1rem; /* smaller text */
            color: #555;
        }

        .welcome-icon {
            font-size: 55px; /* smaller icon */
            color: #4CAF50;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php include('clerk_sidebar.php'); ?>
    
    <div class="main-content">
        <div class="welcome-box">
            <div class="welcome-icon">
                <ion-icon name="person-circle-outline"></ion-icon>
            </div>
            <h1>Welcome to Clerk Homepage</h1>
            <p>Hello, <strong><?php echo htmlspecialchars($admin_name); ?></strong> 👋<br>
            You are logged in successfully.</p>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>      
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>