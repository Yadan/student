<?php
session_start();
include 'db_connection.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (preg_match('/^\d{12}$/', $username)) {
        // Student login logic (UPDATED)
        $stmt = $conn->prepare("SELECT id, lrn, date_of_birth, first_name, password FROM students WHERE lrn = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            $dob_db = $student['date_of_birth'];
            $dob_formatted = date("mdY", strtotime($dob_db));
            
            // Check if password matches (UPDATED)
            $password_valid = false;
            
            // First check if they have a password set
            if (!empty($student['password'])) {
                // If password is set, ONLY check the password - don't fall back to DOB
                if (password_verify($password, $student['password'])) {
                    $password_valid = true;
                }
            } else {
                // If no password is set, check against DOB
                if ($password === $dob_formatted) {
                    $password_valid = true;
                } else {
                    // Try different date formats that user might have entered
                    $date_formats = [
                        date("mdY", strtotime($dob_db)),      // 10242003
                        date("mdy", strtotime($dob_db)),      // 102403 (without century)
                        date("Ymd", strtotime($dob_db)),      // 20031024
                        str_replace("-", "", $dob_db),        // 2003-10-24 -> 20031024
                        str_replace("/", "", $dob_db),        // 10/24/2003 -> 10242003
                    ];
                    
                    foreach ($date_formats as $format) {
                        if ($password === $format) {
                            $password_valid = true;
                            break;
                        }
                    }
                }
            }

            if ($password_valid) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['lrn'] = $student['lrn'];
                $_SESSION['student_name'] = $student['first_name'];
                $_SESSION['just_logged_in'] = true;
                header("Location: student_portal.php");
                exit();
            } else {
                $error = "Invalid LRN or password!";
            }
        } else {
            $error = "Invalid LRN or password!";
        }
        $stmt->close();
    } else {
        // First check if the teacher exists in the faculty table
        $stmt = $conn->prepare("SELECT f.id, f.teacher_id, f.dob, f.name, f.password 
                              FROM faculty f
                              WHERE f.teacher_id = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $teacher = $result->fetch_assoc();
            
            // Now check if this teacher is assigned as a section adviser
            $stmt2 = $conn->prepare("SELECT sa.id FROM section_advisers sa WHERE sa.teacher_id = ?");
            $stmt2->bind_param("s", $teacher['id']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            
            if ($result2->num_rows === 0) {
                $error = "You are not assigned as a class adviser. Please contact administrator.";
                $stmt2->close();
                $stmt->close();
            } else {
                $stmt2->close();
                
                // Teacher is an adviser, proceed with password verification
                $dob_db = $teacher['dob'];
                $dob_formatted = date("mdY", strtotime($dob_db));
                
                // Check if password matches
                $password_valid = false;
                
                // First check if they have a password set
                if (!empty($teacher['password'])) {
                    // If password is set, ONLY check the password - don't fall back to DOB
                    if (password_verify($password, $teacher['password'])) {
                        $password_valid = true;
                    }
                } else {
                    // If no password is set, check against DOB and various date formats
                    if ($password === $dob_formatted) {
                        $password_valid = true;
                    } else {
                        // Try different date formats that user might have entered
                        $date_formats = [
                            date("mdY", strtotime($dob_db)),      // 10242003
                            date("mdy", strtotime($dob_db)),      // 102403 (without century)
                            date("Ymd", strtotime($dob_db)),      // 20031024
                            str_replace("-", "", $dob_db),        // 2003-10-24 -> 20031024
                            str_replace("/", "", $dob_db),        // 10/24/2003 -> 10242003
                        ];
                        
                        foreach ($date_formats as $format) {
                            if ($password === $format) {
                                $password_valid = true;
                                break;
                            }
                        }
                    }
                }

                if ($password_valid) {
                    $_SESSION['teacher_id'] = $teacher['id'];
                    $_SESSION['teacher_name'] = $teacher['name'];
                    $_SESSION['just_logged_in'] = true;
                    header("Location: adviser_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid Teacher ID or password!";
                }
            }
        } else {
            // Admin login logic (unchanged)
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header("Location: clerk_homepage.php");
                        exit();
                    } elseif ($user['role'] === 'superadmin') {
                        header("Location: dashboard.php");
                        exit();
                    } elseif ($user['role'] === 'counselor') {
                        header("Location: counselor_homepage.php");
                        exit();
                    } else {
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Invalid username or password!";
                }
            } else {
                $error = "Invalid username or password!";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Isiera | Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: url('assets/imgs/rbasashs.jpg') no-repeat center center fixed;
      background-size: cover;
      margin: 0;
      padding: 0;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: inherit;
      filter: blur(5px);
      z-index: -1;
    }

    .login-container {
      max-width: 380px;
      width: 90%;
      margin: 50px auto;
      padding: 30px 20px;
      border-radius: 16px;
      background: rgba(240, 240, 240, 0.85);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      color: #333;
    }

    .login-container h3 {
      font-weight: 600;
      margin-bottom: 20px;
      color: #222;
      font-size: 1.5rem;
    }

    .form-control {
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 10px;
      padding: 12px;
      min-height: 44px;
      font-size: 16px; /* Prevent zoom on iOS */
    }

    .form-control:focus {
      box-shadow: none;
      border-color: #0d6efd;
    }

    .btn-primary {
      background-color: #0d6efd;
      border-radius: 10px;
      font-weight: 600;
      padding: 12px;
      min-height: 44px;
    }

    .btn-primary:hover {
      background-color: #0b5ed7;
    }

    .logo {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin: 0 auto 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      color: #fff;
      font-weight: bold;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    a {
      color: #0d6efd;
      font-weight: 500;
    }

    a:hover {
      color: #0a58ca;
      text-decoration: underline;
    }

    .alert {
      font-size: 14px;
    }
    
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
      z-index: 5;
    }

    .password-container {
      position: relative;
    }

    /* Mobile-specific adjustments */
    @media (max-width: 576px) {
      .login-container {
        margin: 20px auto;
        padding: 25px 15px;
      }
      
      body {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
<body>
  <div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
        <div class="login-container text-center">
          <div class="logo">
            <img src="assets/imgs/isiera.jpg" alt="Isiera">
          </div>
          <h3>Welcome to Isiera</h3>
          <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
          <form method="POST" action="">
            <div class="mb-3">
              <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3 password-container">
              <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
              <span class="password-toggle" onclick="togglePassword()">
                <ion-icon name="eye-off-outline"></ion-icon>
              </span>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
          <hr>
          <p>Application for New Student <br><a href="register.php">Register here</a></p>
          <p>
  Download the mobile app: 
  <a href="isiera_mobile_app/app-release.apk" download="Isiera.apk">Click here</a>
</p>
        </div>
      </div>
    </div>
  </div>
  
    <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.password-toggle ion-icon');
      
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