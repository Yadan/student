<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

ini_set('log_errors', 1);
ini_set('error_log', 'attendance_debug.log');

try {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Required parameters
    if (!isset($data['teacher_id'], $data['subject_id'], $data['section_id'])) {
        throw new Exception('Missing required parameters');
    }

    $teacherId = $data['teacher_id'];
    $subjectId = (int)$data['subject_id'];
    $sectionId = (int)$data['section_id'];
    $startDate = isset($data['start_date']) ? $data['start_date'] : null;
    $endDate = isset($data['end_date']) ? $data['end_date'] : $startDate;

    // Validate teacher-subject-section assignment
    $stmt = $conn->prepare("
        SELECT 1 FROM teacher_subjects ts
        JOIN faculty f ON ts.teacher_id = f.id
        WHERE f.teacher_id = ? AND ts.subject_id = ? AND ts.section_id = ?
    ");
    $stmt->bind_param("sii", $teacherId, $subjectId, $sectionId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Invalid teacher-subject-section assignment');
    }

    // Build the query
    $query = "
        SELECT 
            a.id,
            a.student_id,
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            DATE(a.attendance_date) as date,
            TIME(a.time_in) as time,
            a.status,
            s.lrn,
            s.rfid
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.section_id = ? AND a.subject_id = ?
    ";

    $params = [$sectionId, $subjectId];
    $types = "ii";

    // Add date filtering if provided
    if ($startDate) {
        $query .= " AND DATE(a.attendance_date) >= ?";
        $params[] = $startDate;
        $types .= "s";
        
        if ($endDate && $endDate != $startDate) {
            $query .= " AND DATE(a.attendance_date) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
    }

    $query .= " ORDER BY a.attendance_date DESC, a.time_in DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);

    // Group by date for better organization
    $groupedRecords = [];
    foreach ($records as $record) {
        $date = $record['date'];
        if (!isset($groupedRecords[$date])) {
            $groupedRecords[$date] = [];
        }
        $groupedRecords[$date][] = $record;
    }

    echo json_encode([
        'success' => true,
        'records' => $records,
        'grouped_by_date' => $groupedRecords,
        'meta' => [
            'total_records' => count($records),
            'total_days' => count($groupedRecords),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>