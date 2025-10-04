<?php
// Start output buffering to prevent any accidental output
ob_start();
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Get and validate input data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Input validation
    $rfid = isset($data['rfid']) ? preg_replace('/[^0-9]/', '', $data['rfid']) : '';
    $subjectId = isset($data['subject_id']) ? (int)$data['subject_id'] : 0;
    $sectionId = isset($data['section_id']) ? (int)$data['section_id'] : 0;
    $teacherCode = isset($data['teacher_id']) ? trim($data['teacher_id']) : '';
    $currentDateTime = date('Y-m-d H:i:s');
    $date = isset($data['date']) ? $data['date'] : $currentDateTime;

    if (empty($rfid) || $subjectId <= 0 || $sectionId <= 0 || empty($teacherCode)) {
        throw new Exception('Invalid input parameters');
    }

    // Begin transaction
    $conn->begin_transaction();

    // 1. Get complete student info - MODIFIED TO GET SEPARATE NAME FIELDS
    $studentQuery = $conn->prepare("
        SELECT id AS student_id, lrn, first_name, last_name 
        FROM students 
        WHERE rfid = ?
    ");
    $studentQuery->bind_param("s", $rfid);
    if (!$studentQuery->execute()) {
        throw new Exception('Student lookup failed: ' . $conn->error);
    }
    $student = $studentQuery->get_result()->fetch_assoc();
    
    if (!$student) {
        throw new Exception("RFID not registered in the system");
    }

    // Build student name with fallback for null values
    $firstName = $student['first_name'] ?? '';
    $lastName = $student['last_name'] ?? '';
    $studentName = trim("$firstName $lastName");
    if (empty($studentName)) {
        $studentName = "Student (LRN: {$student['lrn']})";
    }

    // 2. Verify teacher assignment
    $teacherVerifyQuery = $conn->prepare("
        SELECT ts.teacher_id, f.id AS faculty_id 
        FROM teacher_subjects ts
        JOIN faculty f ON ts.teacher_id = f.id
        WHERE f.teacher_id = ? 
        AND ts.subject_id = ? 
        AND ts.section_id = ?
    ");
    $teacherVerifyQuery->bind_param("sii", $teacherCode, $subjectId, $sectionId);
    
    if (!$teacherVerifyQuery->execute()) {
        throw new Exception('Teacher verification failed: ' . $conn->error);
    }

    $teacherAssignment = $teacherVerifyQuery->get_result()->fetch_assoc();
    if (!$teacherAssignment) {
        throw new Exception('Teacher is not assigned to teach this subject-section combination');
    }

    // 3. Get section info
    $sectionQuery = $conn->prepare("SELECT section_name FROM sections WHERE id = ?");
    $sectionQuery->bind_param("i", $sectionId);
    if (!$sectionQuery->execute()) {
        throw new Exception('Section lookup failed: ' . $conn->error);
    }
    $section = $sectionQuery->get_result()->fetch_assoc();
    
    if (!$section) {
        throw new Exception("Invalid section ID");
    }

    // 4. Verify student enrollment
    $enrollmentQuery = $conn->prepare("
        SELECT 1 FROM student_enrollments
        WHERE student_lrn = ?
        AND subject_id = ?
        AND section_name = ?
        AND teacher_id = ?
    ");
    $enrollmentQuery->bind_param("sisi", $student['lrn'], $subjectId, $section['section_name'], $teacherAssignment['faculty_id']);
    
    if (!$enrollmentQuery->execute()) {
        throw new Exception('Enrollment verification failed: ' . $conn->error);
    }

    if ($enrollmentQuery->get_result()->num_rows === 0) {
        throw new Exception("Student is not enrolled in this class");
    }

    // 5. Check for existing attendance
    $attendanceDate = date('Y-m-d', strtotime($date));
    $attendanceCheckQuery = $conn->prepare("
        SELECT 1 FROM attendance 
        WHERE student_id = ? 
        AND subject_id = ? 
        AND section_id = ?
        AND teacher_id = ?
        AND attendance_date = ?
    ");
    $attendanceCheckQuery->bind_param("iiiss", 
        $student['student_id'],
        $subjectId,
        $sectionId,
        $teacherAssignment['faculty_id'],
        $attendanceDate
    );
    
    if (!$attendanceCheckQuery->execute()) {
        throw new Exception('Attendance check failed: ' . $conn->error);
    }

    if ($attendanceCheckQuery->get_result()->num_rows > 0) {
        throw new Exception("Attendance already recorded for today");
    }

    // 6. Record attendance with all required fields
    $timeIn = date('H:i:s', strtotime($date));
    $status = 'present'; // Default status
    
    $insertQuery = $conn->prepare("
        INSERT INTO attendance 
        (student_id, teacher_id, subject_id, section_id, rfid, attendance_date, time_in, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertQuery->bind_param("iiiissss", 
        $student['student_id'],
        $teacherAssignment['faculty_id'],
        $subjectId,
        $sectionId,
        $rfid,
        $attendanceDate,
        $timeIn,
        $status
    );
    
    if (!$insertQuery->execute()) {
        throw new Exception("Failed to record attendance: " . $conn->error);
    }

    // Commit transaction
    $conn->commit();

    // Prepare success response with student name
    $response = [
        'success' => true,
        'message' => "Attendance recorded successfully for: $studentName",
        'data' => [
            'student' => [
                'id' => $student['student_id'],
                'name' => $studentName,
                'lrn' => $student['lrn'],
                'rfid' => $rfid
            ],
            'attendance' => [
                'subject_id' => $subjectId,
                'section_id' => $sectionId,
                'teacher_code' => $teacherCode,
                'attendance_date' => $attendanceDate,
                'time_in' => $timeIn,
                'status' => $status
            ],
            'timestamp' => $currentDateTime
        ]
    ];

    // Clean output buffer and send JSON
    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $conn->rollback();
    }
    
    // Clean output buffer and send error
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'rfid' => $rfid,
            'subject_id' => $subjectId,
            'section_id' => $sectionId,
            'teacher_code' => $teacherCode
        ]
    ]);
}