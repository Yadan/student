<?php
session_start();
include 'db_connection.php';

// Clear form data if coming from a redirect (back button)
if (!isset($_POST['submit'])) {
    $_POST = array();
    $_FILES = array();
}

$error = "";
$success = "";

$target_dir = "uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $lrn = trim($_POST['lrn']);
    $date_of_birth = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $citizenship = trim($_POST['citizenship']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $section = trim($_POST['section']);
    $school_year = trim($_POST['school_year']);
    $grade_level = trim($_POST['grade_level']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_contact = trim($_POST['guardian_contact']);
    $guardian_address = trim($_POST['guardian_address']);
    $guardian_relationship = trim($_POST['guardian_relationship']);
    $elementary_school = trim($_POST['elementary_school']);
    $year_graduated = trim($_POST['year_graduated']);
    $created_at = date('Y-m-d H:i:s');

    if (in_array($grade_level, ["Grade 7", "Grade 8", "Grade 9", "Grade 10"])) {
        $student_type = "JHS";
    } elseif (in_array($grade_level, ["Grade 11", "Grade 12"])) {
        $student_type = "SHS";
    }
    
// ADD THIS VALIDATION FOR CONTACT NUMBERS
// Validate contact numbers
if (!preg_match('/^09[0-9]{9}$/', $contact_number)) {
    $error = "Contact number must be exactly 11 digits and start with '09'.";
}

if (empty($error)) {
    if (!preg_match('/^09[0-9]{9}$/', $guardian_contact)) {
        $error = "Guardian contact number must be exactly 11 digits and start with '09'.";
    }
}
    if (!preg_match('/^\d{4}-\d{4}$/', $school_year)) {
        $error = "Invalid school year format. Please use 'YYYY-YYYY'.";
    } else {
        list($start_year, $end_year) = explode("-", $school_year);
        $start_year = (int)$start_year;
        $end_year = (int)$end_year;

        $expected_gap = 0;
        if ($grade_level === "Grade 7") $expected_gap = 4;
        elseif ($grade_level === "Grade 8") $expected_gap = 3;
        elseif ($grade_level === "Grade 9") $expected_gap = 2;
        elseif ($grade_level === "Grade 10") $expected_gap = 1;
        elseif ($grade_level === "Grade 11") $expected_gap = 2;
        elseif ($grade_level === "Grade 12") $expected_gap = 1;

        if ($end_year != ($start_year + $expected_gap)) {
            $error = "Invalid school year range for $grade_level. It must be $expected_gap years after the start year.";
        }
    }

    if (empty($error)) {
        $check_stmt = $conn->prepare("SELECT lrn, email FROM pending_students WHERE lrn = ? OR email = ? UNION SELECT lrn, email FROM students WHERE lrn = ? OR email = ?");
        $check_stmt->bind_param("ssss", $lrn, $email, $lrn, $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->bind_result($existing_lrn, $existing_email);
            while ($check_stmt->fetch()) {
                if ($existing_lrn === $lrn) {
                    $error = "The LRN '$lrn' already exists in the system. Please use a different LRN.";
                }
                if ($existing_email === $email) {
                    $error = "The email '$email' is already registered. Please use a different email.";
                }
            }
        } else {
            $valid_extensions = ["jpg", "jpeg", "png"];
            $uploaded_files = [];

            foreach (["birth_certificate", "id_photo", "good_moral", "student_signature"] as $file_key) {
                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                    $file_ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, $valid_extensions)) {
                        $new_file_name = uniqid() . "_" . $file_key . "." . $file_ext;
                        $target_file = $target_dir . $new_file_name;
                        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
                            $uploaded_files[$file_key] = $target_file;
                        } else {
                            $error = "Error uploading $file_key.";
                        }
                    } else {
                        $error = "Invalid file type for $file_key. Only JPG, JPEG, and PNG are allowed.";
                    }
                } else {
                    $uploaded_files[$file_key] = null;
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO pending_students 
                    (first_name, middle_name, last_name, lrn, date_of_birth, gender, citizenship, address, contact_number, email, section, school_year, student_type, guardian_name, guardian_contact, guardian_address, guardian_relationship, elementary_school, year_graduated, birth_certificate, id_photo, good_moral, student_signature, created_at, grade_level) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if (!$stmt) {
                    die("SQL Prepare Error: " . $conn->error);
                }

                $stmt->bind_param("sssssssssssssssssssssssss",
                    $first_name, $middle_name, $last_name, $lrn, $date_of_birth, $gender,
                    $citizenship, $address, $contact_number, $email, $section, $school_year, $student_type,
                    $guardian_name, $guardian_contact, $guardian_address, $guardian_relationship, $elementary_school,
                    $year_graduated,
                    $uploaded_files['birth_certificate'], $uploaded_files['id_photo'],
                    $uploaded_files['good_moral'], $uploaded_files['student_signature'],
                    $created_at, $grade_level
                );

                if ($stmt->execute()) {
                    $success = "Registration successful! Your application is pending approval.";
                    // Clear form data after successful submission
                    $_POST = array();
                } else {
                    $error = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Handle AJAX request for fetching sections
if (isset($_GET['grade_level'])) {
    $grade_level = $_GET['grade_level'];
    
    // Fetch sections from the database based on grade level
    $stmt = $conn->prepare("SELECT section_name FROM sections WHERE grade_level = ?");
    $stmt->bind_param("s", $grade_level);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($sections);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #28a745;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --green: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 20px;
        }
        
        .container {
            max-width: 900px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            padding: 0;
            margin-top: 15px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .header {
            text-align: center;
            margin-bottom: 0;
            color: var(--secondary-color);
            background: #0b6609;
            padding: 20px 15px;
            color: white;
        }
        
        .header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .form-content {
            padding: 20px;
        }
        
        .form-section {
            background-color: var(--light-bg);
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-color);
        }
        
        .form-section h4 {
            color: var(--secondary-color);
            margin-bottom: 18px;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .form-section h4::before {
            content: "";
            display: inline-block;
            height: 20px;
            width: 5px;
            background-color: var(--primary-color);
            margin-right: 10px;
            border-radius: 10px;
        }
        
        .required-label::after {
            content: " *";
            color: var(--accent-color);
            font-weight: bold;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
            color: #444;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: #28a745;
            border: none;
            padding: 7px;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
            margin-top: 10px;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background:#0b6609;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.4);
        }
        
        .form-control {
            padding: 7px;
            border-radius: 2px;
            border: 1.5px solid #ddd;
            font-size: 14px;
            transition: all 0.3s;
            height: auto;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.2);
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12L8 12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s;
            border-left: 4px solid #dc3545;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .file-upload-label {
            display: block;
            background: #f1f8ff;
            border: 2px dashed #b3d7ff;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 5px;
        }
        
        .file-upload-label:hover {
            background: #e6f2ff;
            border-color: var(--primary-color);
        }
        
        /* Mobile-specific adjustments */
        @media (max-width: 768px) {
            .container {
                border-radius: 0;
                margin-top: 0;
                margin-bottom: 0;
                box-shadow: none;
            }
            
            .header {
                border-radius: 0;
                padding: 15px 10px;
            }
            
            .header h2 {
                font-size: 1.5rem;
            }
            
            .form-content {
                padding: 15px;
            }
            
            .form-section {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .form-section h4 {
                font-size: 1.2rem;
            }
            
            .form-control {
                padding: 6px;
            }
            
            .btn-primary {
                padding: 15px;
                font-size: 1rem;
                border-radius: 8px;
            }
            
            .row {
                margin-left: -6px;
                margin-right: -6px;
            }
            
            [class*="col-"] {
                padding-left: 6px;
                padding-right: 6px;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                padding: 12px 10px;
            }
            
            .header h2 {
                font-size: 1.4rem;
            }
            
            .header p {
                font-size: 0.9rem;
            }
            
            .form-content {
                padding: 12px;
            }
            
            .form-section {
                padding: 12px;
            }
            
            .form-section h4 {
                font-size: 1.1rem;
                margin-bottom: 15px;
            }
            
            .form-label {
                font-size: 0.9rem;
            }
            
            .form-control {
                padding: 10px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn-primary {
                padding: 14px;
            }
        }
        
        /* Loading indicator for section dropdown */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Student Registration</h2>
            <p>Please fill out all required fields completely and accurately</p>
        </div>

        <div class="form-content">
            <!-- Success Alert -->
            <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" autocomplete="off">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h4>Personal Information</h4>
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
    <label class="form-label required-label">LRN (12 digits)</label>
    <input type="text" name="lrn" class="form-control" 
           value="<?php echo isset($_POST['lrn']) ? htmlspecialchars($_POST['lrn']) : ''; ?>" 
           maxlength="12" pattern="[0-9]{12}" required>
</div>
                        
<!-- Replace the existing Date of Birth field with this code -->
<div class="col-12 col-md-4 mb-3">
    <label class="form-label required-label">Date of Birth</label>
    <input type="date" name="date_of_birth" class="form-control" 
           value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>" 
           pattern="\d{4}-\d{2}-\d{2}" required>

</div>
                        
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">Gender</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Citizenship</label>
                            <input type="text" name="citizenship" class="form-control" value="<?php echo isset($_POST['citizenship']) ? htmlspecialchars($_POST['citizenship']) : ''; ?>" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>
                        </div>
<div class="col-12 col-md-6 mb-3">
    <label class="form-label required-label">Contact Number</label>
    <input type="text" name="contact_number" class="form-control" 
           value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>" 
           maxlength="11" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX" required>
</div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Academic Information Section -->
                <div class="form-section">
                    <h4>Academic Information</h4>
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">Grade Level</label>
                            <select name="grade_level" id="grade_level" class="form-control" required>
                                <option value="">Select Grade Level</option>
                                <option value="Grade 7" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 7') ? 'selected' : ''; ?>>Grade 7</option>
                                <option value="Grade 8" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 8') ? 'selected' : ''; ?>>Grade 8</option>
                                <option value="Grade 9" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 9') ? 'selected' : ''; ?>>Grade 9</option>
                                <option value="Grade 10" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 10') ? 'selected' : ''; ?>>Grade 10</option>
                                <option value="Grade 11" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="Grade 12" <?php echo (isset($_POST['grade_level']) && $_POST['grade_level'] == 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">Section</label>
                            <select name="section" id="section" class="form-control" required>
                                <option value="">Select Section</option>
                                <?php if (isset($_POST['section'])): ?>
                                    <option value="<?php echo $_POST['section']; ?>" selected><?php echo $_POST['section']; ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4 mb-3">
                            <label class="form-label required-label">School Year</label>
                            <input type="text" name="school_year" id="school_year" class="form-control" placeholder="YYYY-YYYY" value="<?php echo isset($_POST['school_year']) ? htmlspecialchars($_POST['school_year']) : ''; ?>" required>
                        </div>
                        
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Elementary School</label>
                            <input type="text" name="elementary_school" class="form-control" value="<?php echo isset($_POST['elementary_school']) ? htmlspecialchars($_POST['elementary_school']) : ''; ?>" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Year Graduated</label>
                            <input type="text" name="year_graduated" class="form-control" value="<?php echo isset($_POST['year_graduated']) ? htmlspecialchars($_POST['year_graduated']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Parent/Guardian Information Section -->
                <div class="form-section">
                    <h4>Parent/Guardian Information</h4>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Full Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="<?php echo isset($_POST['guardian_name']) ? htmlspecialchars($_POST['guardian_name']) : ''; ?>" required>
                        </div>
<div class="col-12 col-md-6 mb-3">
    <label class="form-label required-label">Contact Number</label>
    <input type="text" name="guardian_contact" class="form-control" 
           value="<?php echo isset($_POST['guardian_contact']) ? htmlspecialchars($_POST['guardian_contact']) : ''; ?>" 
           maxlength="11" pattern="09[0-9]{9}" placeholder="09XXXXXXXXX" required>
</div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Guardian Address</label>
                            <input type="text" name="guardian_address" class="form-control" value="<?php echo isset($_POST['guardian_address']) ? htmlspecialchars($_POST['guardian_address']) : ''; ?>" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label required-label">Relationship to Student</label>
                            <input type="text" name="guardian_relationship" class="form-control" value="<?php echo isset($_POST['guardian_relationship']) ? htmlspecialchars($_POST['guardian_relationship']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Required Documents Section -->
                <div class="form-section">
                    <h4>Required Documents</h4>
                    <div class="mb-3">
                        <label class="form-label required-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Form 137 (Good Moral)</label>
                        <input type="file" name="good_moral" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-label">Student Signature</label>
                        <input type="file" name="student_signature" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-label">Student ID Picture</label>
                        <input type="file" name="id_photo" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-primary w-100">Register</button>
            </form>
        </div>
    </div>

    <script>
        // Function to validate contact numbers
function validateContactNumber(input, fieldName) {
    const value = input.value.trim();

    
    if (value === '') {
        if (errorElement) errorElement.remove();
        input.classList.remove('is-invalid');
        return true;
    }
    
    if (!/^09[0-9]{9}$/.test(value) || value.length !== 11) {
        if (!errorElement) {
            const error = document.createElement('div');
            error.id = `${fieldName}-error`;
            error.className = 'invalid-feedback';
            input.parentNode.appendChild(error);
        }
        input.classList.add('is-invalid');
        return false;
    }
    
    if (errorElement) errorElement.remove();
    input.classList.remove('is-invalid');
    return true;
}


// Function to fetch sections based on grade level
function fetchSections(gradeLevel) {
    if (!gradeLevel || gradeLevel === "") {
        // Clear sections if no grade level is selected
        const sectionDropdown = document.getElementById('section');
        sectionDropdown.innerHTML = '<option value="">Select Section</option>';
        return;
    }
    
    // Show loading state
    const sectionDropdown = document.getElementById('section');
    sectionDropdown.innerHTML = '<option value="">Loading sections...</option>';
    sectionDropdown.classList.add('loading');
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `?grade_level=${encodeURIComponent(gradeLevel)}`, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        sectionDropdown.classList.remove('loading');
        
        if (this.status === 200) {
            try {
                const sections = JSON.parse(this.responseText);
                sectionDropdown.innerHTML = '<option value="">Select Section</option>';
                
                if (sections.length === 0) {
                    sectionDropdown.innerHTML = '<option value="">No sections available for this grade level</option>';
                    return;
                }
                
                sections.forEach(section => {
                    const opt = document.createElement('option');
                    opt.value = section.section_name;
                    opt.textContent = section.section_name;
                    
                    // Preselect if this was the previously selected section
                    const previouslySelectedSection = '<?php echo isset($_POST["section"]) ? $_POST["section"] : ""; ?>';
                    if (section.section_name === previouslySelectedSection) {
                        opt.selected = true;
                    }
                    
                    sectionDropdown.appendChild(opt);
                });
            } catch (e) {
                console.error('Error parsing sections:', e);
                sectionDropdown.innerHTML = '<option value="">Error loading sections</option>';
            }
        } else {
            sectionDropdown.innerHTML = '<option value="">Error loading sections</option>';
        }
    };
    
    xhr.onerror = function() {
        sectionDropdown.classList.remove('loading');
        sectionDropdown.innerHTML = '<option value="">Error loading sections</option>';
        console.error('Request failed');
    };
    
    xhr.send();
}

// Function to update school year based on grade level
function autoUpdateSchoolYear() {
    const grade = document.querySelector('[name="grade_level"]').value;
    const yearInput = document.getElementById('school_year');

    if (yearInput.value.length === 4 && !isNaN(yearInput.value)) {
        const start = parseInt(yearInput.value);
        let gap = 0;

        if (grade === "Grade 7") gap = 4;
        else if (grade === "Grade 8") gap = 3;
        else if (grade === "Grade 9") gap = 2;
        else if (grade === "Grade 10") gap = 1;
        else if (grade === "Grade 11") gap = 2;
        else if (grade === "Grade 12") gap = 1;

        if (gap > 0) {
            yearInput.value = `${start}-${start + gap}`;
        }
    }
}

// Fix for iOS date input
function fixIOSDateInput() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const dateInput = document.querySelector('input[type="date"]');
    
    if (isIOS && dateInput) {
        // Remove any placeholder manipulation for iOS
        dateInput.removeAttribute('placeholder');
        
        // Ensure proper format for iOS
        dateInput.addEventListener('blur', function() {
            if (this.value) {
                // Convert to YYYY-MM-DD format if needed
                const date = new Date(this.value);
                if (!isNaN(date.getTime())) {
                    const formattedDate = date.toISOString().split('T')[0];
                    this.value = formattedDate;
                }
            }
        });
    }
}

// Auto-dismiss alerts after 5 seconds
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set up iOS date fix
    fixIOSDateInput();
    
    // Auto-dismiss alerts
    autoDismissAlerts();
    
    // Set up event listeners
    const gradeLevelSelect = document.querySelector('[name="grade_level"]');
    if (gradeLevelSelect) {
        gradeLevelSelect.addEventListener('change', function() {
            autoUpdateSchoolYear();
            fetchSections(this.value);
        });
    }
    
    const schoolYearInput = document.getElementById('school_year');
    if (schoolYearInput) {
        schoolYearInput.addEventListener('input', autoUpdateSchoolYear);
    }
    
    // Fetch sections if grade level is already selected (e.g., after form submission with error)
    const selectedGradeLevel = gradeLevelSelect ? gradeLevelSelect.value : null;
    if (selectedGradeLevel) {
        fetchSections(selectedGradeLevel);
    }
    
    // Improve date input for non-iOS devices
    const dateInput = document.querySelector('input[type="date"]');
    if (dateInput && !dateInput.value) {
        dateInput.setAttribute('placeholder', 'YYYY-MM-DD');
    }
});

// Add contact number validation on input
const contactInputs = [
    document.querySelector('[name="contact_number"]'),
    document.querySelector('[name="guardian_contact"]')
];

contactInputs.forEach(input => {
    if (input) {
        const fieldName = input.getAttribute('name');
        input.addEventListener('input', function() {
            validateContactNumber(this, fieldName);
        });
        
        input.addEventListener('blur', function() {
            validateContactNumber(this, fieldName);
        });
    }
});

// Form submission validation
document.querySelector('form').addEventListener('submit', function(e) {
    const contactNumber = document.querySelector('[name="contact_number"]');
    const guardianContact = document.querySelector('[name="guardian_contact"]');
    
    const isContactValid = validateContactNumber(contactNumber, 'contact_number');
    const isGuardianValid = validateContactNumber(guardianContact, 'guardian_contact');
    
    if (!isContactValid || !isGuardianValid) {
        e.preventDefault();
        
        // Scroll to the first error
        const firstError = document.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>