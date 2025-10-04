<?php
header('Content-Type: application/json');
require 'db_connection.php';

try {
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);

    $teacherCode = $input['teacher_id'] ?? null;
    $subjectId = $input['subject_id'] ?? null;

    if (!$teacherCode || !$subjectId) {
        throw new Exception('Teacher ID and Subject ID required');
    }

    // Query to get all sections the teacher teaches for this subject
    $query = $conn->prepare("
        SELECT DISTINCT 
            ts.section_id as id, 
            s.section_name as section_name
        FROM teacher_subjects ts
        JOIN sections s ON ts.section_id = s.id
        JOIN faculty f ON ts.teacher_id = f.id
        WHERE f.teacher_id = ? 
        AND ts.subject_id = ?
        ORDER BY s.section_name
    ");
    $query->bind_param("si", $teacherCode, $subjectId);
    $query->execute();
    $result = $query->get_result();

    $sections = [];
    while ($row = $result->fetch_assoc()) {
        $sections[] = [
            'id' => $row['id'],
            'name' => $row['section_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>