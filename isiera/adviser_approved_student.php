<?php  
include 'db_connection.php';

// Function to send approval email
function sendApprovalEmail($student, $conn) {
    $to = $student['email'];
    $subject = "Application Approved";
    
// Build the email message
$message = "Dear " . $student['first_name'] . " " . $student['last_name'] . ",\n\n";
$message .= "We are pleased to inform you that your application has been APPROVED!\n\n";
$message .= "Here are your registration details:\n";
$message .= "LRN: " . $student['lrn'] . "\n";
$message .= "Name: " . $student['first_name'] . " " . $student['middle_name'] . " " . $student['last_name'] . "\n";
$message .= "Grade Level: " . $student['grade_level'] . "\n";
$message .= "Section: " . $student['section'] . "\n";
$message .= "School Year: " . $student['school_year'] . "\n\n";
$message .= "You can now access your student account using your registered credentials.\n\n";
$message .= "To access the student portal, please use your LRN as the username and your date of birth (format: MM/DD/YY) as the password.\n\n";
$message .= "Sincerely,\nThe Administration Team";

    // Email headers
    $headers = [
        'From: "School Administration" <noreply@yourschool.com>',
        'Reply-To: admin@yourschool.com',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit'
    ];
    
    $headers = implode("\r\n", $headers);
    $additionalParams = '-fnoreply@yourschool.com';

    // Send email
    return mail($to, $subject, $message, $headers, $additionalParams);
}

if (isset($_GET['id'])) {
    $studentId = $_GET['id'];

    // Get student data from pending_students
    $query = "SELECT * FROM pending_students WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        // Validate required fields
        if (
            empty($student['section']) || 
            empty($student['school_year']) || 
            empty($student['guardian_address']) || 
            empty($student['grade_level'])
        ) {
            echo "<script>alert('Error: Some required fields are missing for this student.'); window.location.href='adviser_student_verification.php';</script>";
            exit();
        }

        // Determine student_type
        $gradeLevel = strtoupper(trim($student['grade_level']));
        $studentType = '';
        if (in_array($gradeLevel, ['GRADE 7', 'GRADE 8', 'GRADE 9', 'GRADE 10'])) {
            $studentType = 'JHS';
        } elseif (in_array($gradeLevel, ['GRADE 11', 'GRADE 12'])) {
            $studentType = 'SHS';
        }

        // Insert student into students table
        $insertQuery = "INSERT INTO students (
            lrn, first_name, middle_name, last_name, email, section, school_year, grade_level, student_type,
            date_of_birth, gender, citizenship, address, contact_number,
            guardian_name, guardian_contact, guardian_relationship, guardian_address,
            elementary_school, year_graduated, birth_certificate, id_photo, good_moral, student_signature
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("ssssssssssssssssssssssss", 
            $student['lrn'], 
            $student['first_name'], 
            $student['middle_name'], 
            $student['last_name'],
            $student['email'], 
            $student['section'],
            $student['school_year'],
            $student['grade_level'],
            $studentType,
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
            $student['student_signature']
        );

        if ($insertStmt->execute()) {
            // Send approval email
            $emailSent = sendApprovalEmail($student, $conn);
            
            // Delete student from pending_students
            $deleteQuery = "DELETE FROM pending_students WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param("i", $studentId);
            $deleteStmt->execute();

            if ($emailSent) {
                echo "<script>alert('Student approved successfully! Notification email sent.'); window.location.href='adviser_student_verification.php';</script>";
            } else {
                echo "<script>alert('Student approved successfully! But failed to send notification email.'); window.location.href='adviser_student_verification.php';</script>";
            }
        } else {
            echo "<script>alert('Error inserting into students: " . $insertStmt->error . "'); window.location.href='adviser_student_verification.php';</script>";
        }

    } else {
        echo "<script>alert('Student not found.'); window.location.href='adviser_student_verification.php';</script>";
    }
}
?>