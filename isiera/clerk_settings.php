<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_role = $_SESSION['user_role'];
$error_message = '';
$success_message = '';

// Update password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify old password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();
    
    if (!password_verify($old_password, $hashed_password)) {
    $error_message = "Old password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed_password, $user_id);

        if ($stmt->execute()) {
            $success_message = "Password successfully updated!";
        } else {
            $error_message = "Error updating password.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .settings-container {
            max-width: 500px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-left: 600px
        }


        .form-control {
            width: 100%;
            padding: 10px 40px 6px 6px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            background: #28a745;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background: ##0b6609;
        }

        h2 {
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }

        h3 {
            margin: 20px 0 15px 0;
            color: #555;
            border-bottom: 2px solid #28a745;
            padding-bottom: 8px;
        }

        .message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .current-username {
            background: #white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: #495057;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 45px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php include('clerk_sidebar.php'); ?>
<div class="settings-container">
    <h2>Account Settings</h2>

    <!-- Display Current Username -->
    <div class="current-username">
        Current Username: <?= htmlspecialchars($username) ?>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="message success"><?= $success_message ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="message error"><?= $error_message ?></div>
    <?php endif; ?>

    <!-- Change Password -->
    <h3>Change Password</h3>
    <form method="POST">
        <div class="form-group">
            <label for="old_password">Old Password</label>
            <input type="password" name="old_password" id="old_password" class="form-control" required>
            <span class="password-toggle" onclick="togglePassword('old_password')">
                <ion-icon name="eye-off-outline"></ion-icon>
            </span>
        </div>
        
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
            <span class="password-toggle" onclick="togglePassword('new_password')">
                <ion-icon name="eye-off-outline"></ion-icon>
            </span>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                <ion-icon name="eye-off-outline"></ion-icon>
            </span>
        </div>
        
        <button type="submit" name="change_password">Save</button>
    </form>
</div>

<script>
    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = passwordInput.nextElementSibling.querySelector('ion-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.setAttribute('name', 'eye-outline');
        } else {
            passwordInput.type = 'password';
            toggleIcon.setAttribute('name', 'eye-off-outline');
        }
    }
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>