<?php
// Ensure no output before headers
ob_start();

// Include database connection
include 'db_connection.php';

// Enable error reporting (log errors but don't display them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Function to clean output and send JSON response
function sendJsonResponse($success, $message) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success, 
        'message' => $message
    ]);
    exit;
}

// Function to send rejection email
function sendRejectionEmail($student, $rejectionReasons) {
    $to = $student['email'];
    $subject = "Application Status Update - Important Information";
    
    // Build the email message
    $message = "Dear " . $student['first_name'] . " " . $student['last_name'] . ",\n\n";
    $message .= "Thank you for your application to our school. After careful review, we regret to inform you that your application has not been approved at this time.\n\n";
    $message .= "Reason(s) for rejection:\n";
    
    foreach ($rejectionReasons as $reason) {
        $message .= "- " . $reason . "\n";
    }
    
    $message .= "\nWhat you can do next:\n";
    $message .= "1. Review the reasons for rejection listed above\n";
    $message .= "2. Address these issues in your application\n";
    $message .= "If you have questions about this decision, please contact our administration office during business hours.\n\n";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Input validation
        if (!isset($_POST['student_id']) || !isset($_POST['rejection_reasons'])) {
            throw new Exception('Missing required parameters');
        }

        $studentId = (int)$_POST['student_id'];
        $rejectionReasons = json_decode($_POST['rejection_reasons'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid rejection reasons format');
        }

        if ($studentId <= 0) {
            throw new Exception('Invalid student ID');
        }

        if (empty($rejectionReasons)) {
            throw new Exception('Rejection reasons are required');
        }

        // Get student details
        $query = "SELECT * FROM pending_students WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if (!$student) {
            throw new Exception('Student not found');
        }

        // Verify the student has an email address
        if (empty($student['email'])) {
            throw new Exception('Student email address is missing');
        }

        // Send rejection email
        $emailSent = sendRejectionEmail($student, $rejectionReasons);

        if (!$emailSent) {
            // Log detailed error information
            $errorDetails = [
                'date' => date('Y-m-d H:i:s'),
                'student_id' => $studentId,
                'to' => $student['email'],
                'server' => $_SERVER['SERVER_NAME'],
                'php_error' => error_get_last() ? error_get_last()['message'] : 'Unknown error'
            ];
            
            error_log("Email send failed: " . print_r($errorDetails, true));
            
            // Save failed email to a log file as fallback
            $failedEmail = [
                'to' => $student['email'],
                'subject' => 'Application Status Update - Important Information',
                'message' => "Rejection reasons: " . implode(", ", $rejectionReasons),
                'time' => date('Y-m-d H:i:s')
            ];
            file_put_contents('failed_emails.log', json_encode($failedEmail)."\n", FILE_APPEND);
        }

        // Delete from pending students
        $deleteQuery = "DELETE FROM pending_students WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $studentId);
        $deleteSuccess = $deleteStmt->execute();

        if (!$deleteSuccess) {
            throw new Exception('Failed to delete student record');
        }

        // Return success response with email status
        if ($emailSent) {
            sendJsonResponse(true, 'Student rejected and notification email sent successfully.');
        } else {
            sendJsonResponse(true, 'Student rejected successfully, but email notification failed. The rejection details have been logged.');
        }
        
    } catch (Exception $e) {
        sendJsonResponse(false, $e->getMessage());
    }
}

// If not POST request
sendJsonResponse(false, 'Invalid request method');
exit;
?>