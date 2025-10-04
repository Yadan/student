<?php
include 'db_connection.php';

if (isset($_GET['teacher_id'])) {
    $teacherId = $_GET['teacher_id'];
    
    $query = "
        SELECT DISTINCT s.id, s.subject_name 
        FROM teacher_subjects ts 
        JOIN subjects s ON ts.subject_id = s.id 
        WHERE ts.teacher_id = ? 
        ORDER BY s.subject_name ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($subjects);
    exit;
}

echo json_encode([]);