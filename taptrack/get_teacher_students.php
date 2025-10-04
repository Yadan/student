<?php
header('Content-Type: application/json');
require 'db_connection.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $teacherCode = $input['teacher_id'] ?? null;
    $subjectId = $input['subject_id'] ?? null;
    $sectionId = $input['section_id'] ?? null;

    if (!$teacherCode || !$subjectId || !$sectionId) {
        throw new Exception('Teacher ID, Subject ID, and Section ID required');
    }

    // Verify teacher assignment
    $verifyQuery = $conn->prepare("
        SELECT 1 FROM teacher_subjects ts
        JOIN faculty f ON ts.teacher_id = f.id
        WHERE f.teacher_id = ? AND ts.subject_id = ? AND ts.section_id = ?
    ");
    $verifyQuery->bind_param("sii", $teacherCode, $subjectId, $sectionId);
    
    if (!$verifyQuery->execute()) {
        throw new Exception('Verification query failed');
    }

    if ($verifyQuery->get_result()->num_rows === 0) {
        throw new Exception('Unauthorized access');
    }

    // Get students with RFID from students table
    $studentQuery = $conn->prepare("
        SELECT 
            se.id,
            se.student_lrn as lrn,
            se.student_name as name,
            s.rfid,  -- Get RFID from students table
            se.section_name as section,
            se.grade_level
        FROM student_enrollments se
        JOIN students s ON se.student_lrn = s.lrn  -- Join with students table
        WHERE se.subject_id = ? 
        AND se.section_id = ?
        AND se.teacher_id IS NOT NULL
    ");
    $studentQuery->bind_param("ii", $subjectId, $sectionId);
    
    if (!$studentQuery->execute()) {
        throw new Exception('Student query failed');
    }

    $students = $studentQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>