<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_connection.php';

$teacher_session_id = $_SESSION['teacher_id'];
$error_message = '';
$success_message = '';

// First, get the actual teacher_id from the database
$stmt = $conn->prepare("SELECT teacher_id FROM faculty WHERE id = ?");
$stmt->bind_param("s", $teacher_session_id);
$stmt->execute();
$stmt->bind_result($teacher_id);
$stmt->fetch();
$stmt->close();

// Update password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify old password - check against both password and dob columns in faculty table
    $stmt = $conn->prepare("SELECT password, dob FROM faculty WHERE id = ?");
    $stmt->bind_param("s", $teacher_session_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password, $dob);
    $stmt->fetch();
    $stmt->close();
    
    // Check if we actually got a result
    if ($dob === null) {
        $error_message = "Your date of birth is not set in the system. Please contact administrator to set your date of birth before changing password.";
    } else {
        // Format DOB for comparison - match the login format (mdY)
        $dob_formatted_login = date("mdY", strtotime($dob));
        
        // Check if old password matches either the stored password or the DOB in login format
        $password_valid = false;
        
        if (!empty($hashed_password) && password_verify($old_password, $hashed_password)) {
            $password_valid = true;
        } elseif ($old_password === $dob_formatted_login) {
            $password_valid = true;
        }
        
        // Additional check for different date formats
        if (!$password_valid) {
            // Try different date formats that user might have entered
            $date_formats = [
                date("mdY", strtotime($dob)),      // 10242003
                date("mdy", strtotime($dob)),      // 102403 (without century)
                date("Ymd", strtotime($dob)),      // 20031024
                str_replace("-", "", $dob),        // 2003-10-24 -> 20031024
                str_replace("/", "", $dob),        // 10/24/2003 -> 10242003
            ];
            
            foreach ($date_formats as $format) {
                if ($old_password === $format) {
                    $password_valid = true;
                    break;
                }
            }
        }
        
        if (!$password_valid) {
            $error_message = "Old password is incorrect. For first-time login, use your date of birth in MMDDYYYY format. Your DOB is: " . date("m/d/Y", strtotime($dob)) . " (try: " . date("mdY", strtotime($dob)) . ")";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE faculty SET password = ? WHERE id = ?");
            $stmt->bind_param("ss", $new_hashed_password, $teacher_session_id);

            if ($stmt->execute()) {
                $success_message = "Password successfully updated!";
            } else {
                $error_message = "Error updating password: " . $stmt->error;
            }
            $stmt->close();
        }
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
            margin-left: 650px
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
            background: #0b6609;
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
        
        .password-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: -10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include('adviser_sidebar.php'); ?>
<div class="settings-container">
    <h2>Account Settings</h2>

    <!-- Display Current Teacher ID -->
    <div class="current-username">
        Current Faculty ID: <?= htmlspecialchars($teacher_id) ?>
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
            <div class="password-hint">For first-time login, use your date of birth in MMDDYYYY format (e.g., 01011990 for January 1, 1990)</div>
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