<?php
session_start();
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
    .error { color: red; text-align: center; margin-top: 10px; }
    .success { color: green; text-align: center; margin-top: 10px; }
</style>
</head>
<body>
<div class="container">
    <div class="heading"><h1>Online Voting System</h1></div>
    <form action="register_data.php" method="POST" onsubmit="return validateForm()">
        <div class="form">
            <h4>Voter Registration</h4>

            <!-- Display messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <label class="label"><sup>*</sup>Student ID:</label>
            <input type="text" name="student_id" id="student_id" class="input" placeholder="Enter Student ID" pattern="[0-9]+" title="Numeric Student ID" required>

            <label class="label"><sup>*</sup>Firstname:</label>
            <input type="text" name="fname" class="input" placeholder="Enter First Name" required>

            <label class="label"><sup>*</sup>Lastname:</label>
            <input type="text" name="lname" class="input" placeholder="Enter Last Name" required>

            <label class="label"><sup>*</sup>Email:</label>
            <input type="email" name="email" id="email" class="input" placeholder="Enter Email" required>

            <label class="label"><sup>*</sup>Date of Birth:</label>
            <input type="date" name="dob" id="dob" class="input" required max="<?php echo date('Y-m-d'); ?>">

            <label class="label"><sup>*</sup>Gender:</label>
            <input type="radio" value="male" name="gender" required>Male
            <input type="radio" value="female" name="gender">Female
            <input type="radio" value="other" name="gender">Other

            <label class="label"><sup>*</sup>Phone Number:</label>
            <input type="text" name="phone" id="phone" class="input" placeholder="09XXXXXXXXX" required pattern="09\d{9}" title="Phone number must start with 09 and be 11 digits">

            <label class="label"><sup>*</sup>Address:</label>
            <input type="text" name="address" class="input" placeholder="Enter Address" required>

            <button class="button" name="register">Register</button>
            <div class="link1">Already have account? <a href="index.php">Login here</a></div>
        </div>
    </form>
</div>

<script>
function validateForm() {
    // Student ID
    const studentIdInput = document.getElementById('student_id');
    if (!/^[0-9]+$/.test(studentIdInput.value)) {
        alert('Please enter a valid Student ID (numbers only)');
        studentIdInput.focus();
        return false;
    }

    // Phone Number
    const phoneInput = document.getElementById('phone');
    if (!/^09\d{9}$/.test(phoneInput.value)) {
        alert('Phone number must start with 09 and be 11 digits');
        phoneInput.focus();
        return false;
    }

    // Email
    const emailInput = document.getElementById('email');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(emailInput.value)) {
        alert('Please enter a valid email address');
        emailInput.focus();
        return false;
    }

    // Date picker ensures valid date automatically
    return true;
}
</script>
</body>
</html>
