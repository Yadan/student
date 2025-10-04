<?php
header('Content-Type: application/json');
require 'db_connection.php';

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$teacherCode = $input['teacher_id'] ?? null;

if (!$teacherCode) {
    echo json_encode(['success' => false, 'message' => 'Teacher ID required']);
    exit;
}

// Step 1: Get teacher's assigned subjects and sections
$teacherQuery = $conn->prepare("
    SELECT ts.subject_id, ts.section_id, 
           sub.name AS subject_name, sec.name AS section_name
    FROM teacher_subjects ts
    JOIN subjects sub ON ts.subject_id = sub.id
    JOIN sections sec ON ts.section_id = sec.id
    JOIN faculty f ON ts.teacher_id = f.id
    WHERE f.teacher_id = ?
");
$teacherQuery->bind_param("s", $teacherCode);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();

if ($teacherResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No teaching assignments found']);
    exit;
}

$verificationList = [];

// Step 2: For each assigned subject-section, get unverified students
while ($assignment = $teacherResult->fetch_assoc()) {
    $studentQuery = $conn->prepare("
        SELECT se.id AS enrollment_id, se.student_lrn, se.student_name,
               se.rfid, se.grade_level, se.section_name,
               se.subject_id, se.subject_name, se.section_id
        FROM student_enrollment se
        WHERE se.subject_id = ? 
        AND se.section_id = ?
        AND (se.teacher_id IS NULL OR se.teacher_id = 0)
    ");
    $studentQuery->bind_param("ii", $assignment['subject_id'], $assignment['section_id']);
    $studentQuery->execute();
    $studentResult = $studentQuery->get_result();
    
    while ($student = $studentResult->fetch_assoc()) {
        $verificationList[] = [
            'enrollment_id' => $student['enrollment_id'],
            'student_lrn' => $student['student_lrn'],
            'student_name' => $student['student_name'],
            'rfid' => $student['rfid'],
            'grade_level' => $student['grade_level'],
            'section_name' => $student['section_name'],
            'subject_name' => $student['subject_name'],
            'subject_id' => $student['subject_id'],
            'section_id' => $student['section_id']
        ];
    }
}

echo json_encode(['success' => true, 'students' => $verificationList]);
?>