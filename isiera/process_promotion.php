<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

// Check if database connection was successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_students = $_POST['selected_students'] ?? [];
    $current_grade = trim($_POST['current_grade'] ?? '');
    $current_section = trim($_POST['current_section'] ?? '');
    $new_section = trim($_POST['new_section'] ?? '');
    $archive_graduates = isset($_POST['archive_graduates']) && $_POST['archive_graduates'] === 'true';
    $graduate_type = trim($_POST['graduate_type'] ?? '');

    if (empty($selected_students) || empty($current_grade) || empty($current_section)) {
        die("Missing required data.");
    }

    // Extract grade number
    preg_match('/\d+/', $current_grade, $matches);
    $current_grade_num = $matches[0] ?? null;

    if (!$current_grade_num) {
        die("Invalid current grade level.");
    }

    $is_graduating_grade = $current_grade_num == 10 || $current_grade_num == 12;
    $errors = 0;
    $success_count = 0;

    if ($archive_graduates && $is_graduating_grade) {
        // Archive graduating students (Grade 10 or Grade 12)
        foreach ($selected_students as $lrn) {
            $lrn_safe = mysqli_real_escape_string($conn, trim($lrn));

            // Get student details
            $query = "SELECT * FROM students WHERE lrn = '$lrn_safe' AND grade_level = '$current_grade' AND section = '$current_section'";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $student = mysqli_fetch_assoc($result);

                // Check if archived_students table has the required columns
                $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM archived_students LIKE 'archive_type'");
                if (mysqli_num_rows($check_columns) == 0) {
                    // Add missing columns if they don't exist
                    mysqli_query($conn, "ALTER TABLE archived_students ADD COLUMN archive_type VARCHAR(50) NOT NULL DEFAULT ''");
                    mysqli_query($conn, "ALTER TABLE archived_students ADD COLUMN archived_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                }

                $insert = "INSERT INTO archived_students (
                    lrn, first_name, middle_name, last_name, email, section, school_year, grade_level,
                    student_type, date_of_birth, gender, citizenship, address, contact_number,
                    guardian_name, guardian_contact, guardian_relationship, guardian_address,
                    elementary_school, year_graduated, birth_certificate, id_photo, good_moral, 
                    student_signature, archive_type, date_archived
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                if ($stmt_insert = $conn->prepare($insert)) {
                    // Set the archive type based on grade level
                    $archive_type = ($current_grade_num == 10) ? 'JHS Graduate' : 'SHS Graduate';
                    
                    $stmt_insert->bind_param(
                        'sssssssssssssssssssssssss',
                        $student['lrn'],
                        $student['first_name'],
                        $student['middle_name'],
                        $student['last_name'],
                        $student['email'],
                        $student['section'],
                        $student['school_year'],
                        $student['grade_level'],
                        $student['student_type'],
                        $student['date_of_birth'],
                        $student['gender'],
                        $student['citizenship'],
                        $student['address'],
                        $student['contact_number'],
                        $student['guardian_name'],
                        $student['guardian_contact'],
                        $student['guardian_relationship'],
                        $student['guardian_address'],
                        $student['elementary_school'],
                        $student['year_graduated'],
                        $student['birth_certificate'],
                        $student['id_photo'],
                        $student['good_moral'],
                        $student['student_signature'],
                        $archive_type
                    );

                    if ($stmt_insert->execute()) {
                        // Delete from active students table after successful archiving
                        mysqli_query($conn, "DELETE FROM students WHERE lrn = '$lrn_safe'");
                        $success_count++;
                    } else {
                        $errors++;
                        error_log("Archive failed for LRN: $lrn_safe - " . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                } else {
                    $errors++;
                    error_log("Prepare failed: " . $conn->error);
                }
            } else {
                $errors++;
                error_log("Student not found: LRN: $lrn_safe, Grade: $current_grade, Section: $current_section");
            }
        }
    } else {
        // Promote to next grade level (regular promotion)
        $next_grade = 'Grade ' . ($current_grade_num + 1);
        $next_grade_safe = mysqli_real_escape_string($conn, $next_grade);
        $new_section_safe = mysqli_real_escape_string($conn, $new_section);
        $current_grade_safe = mysqli_real_escape_string($conn, $current_grade);
        $current_section_safe = mysqli_real_escape_string($conn, $current_section);

        if ($stmt = $conn->prepare("UPDATE students SET grade_level = ?, section = ? WHERE lrn = ? AND grade_level = ? AND section = ?")) {
            foreach ($selected_students as $lrn) {
                $lrn_safe = trim($lrn);
                $stmt->bind_param("sssss", $next_grade_safe, $new_section_safe, $lrn_safe, $current_grade_safe, $current_section_safe);
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $errors++;
                    error_log("Promotion failed for LRN: $lrn_safe - " . $stmt->error);
                }
            }
            $stmt->close();
        } else {
            $errors++;
            error_log("Prepare failed: " . $conn->error);
        }
    }

    $conn->close();

    if ($errors === 0) {
        header("Location: promote_students.php?section=" . urlencode($current_section) . 
               "&grade_level=" . urlencode($current_grade) . 
               "&promoted=1&success_count=" . $success_count);
        exit;
    } else {
        // Show a user-friendly error message
        echo "<h2>Operation Completed with Errors</h2>";
        echo "<p>$success_count students were processed successfully.</p>";
        echo "<p>$errors operations failed. Please check the error logs for details.</p>";
        echo "<p><a href='promote_students.php?section=" . urlencode($current_section) . 
             "&grade_level=" . urlencode($current_grade) . "'>Return to Promotion Page</a></p>";
    }
} else {
    echo "Invalid request method. Please use the promotion form.";
}
?>