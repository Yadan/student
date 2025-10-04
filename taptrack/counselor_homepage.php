<?php
session_start();

// Redirect if not a counselor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'counselor') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Homepage | Isiera</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", sans-serif;
            background-color: #f5f5f5 !important; /* all uniform gray */
            color: #333;
            height: 100%;
            overflow: hidden; /* no scroll */
        }

        .main-content {
            margin-left: 310px; /* space for sidebar */
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* slightly higher */
            padding-top: 100px;
        }

        .main-card {
            background: #ffffff; /* pure white card */
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 50px 60px;
            max-width: 850px;
            width: 100%;
            text-align: center;
            border-top: 8px solid #4CAF50;
        }

        .main-card h1 {
            font-size: 2rem;
            color: #2e7d32;
            margin-bottom: 20px;
        }

        .main-card p {
            font-size: 1.1rem;
            color: #555;
        }

        .welcome-icon {
            font-size: 50px;
            color: #4CAF50;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include('counselor_sidebar.php'); ?>

    <div class="main-content">
        <div class="main-card">
            <div class="welcome-icon">
                <ion-icon name="person-circle-outline"></ion-icon>
            </div>
            <h1>Welcome to Counselor Homepage</h1>
            <p>Hello, <strong><?php echo htmlspecialchars($username); ?></strong> 👋<br>
            You are logged in successfully.</p>
        </div>
    </div>

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>