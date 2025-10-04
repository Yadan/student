<?php
// includes/student_functions.php

function updateStudentEnrollments($conn, $lrn, $firstName, $middleName, $lastName, $rfid, $section, $gradeLevel) {
    $fullName = trim("$firstName " . ($middleName ? "$middleName " : "") . $lastName);
    
    $stmt = $conn->prepare("
        UPDATE student_enrollments 
        SET 
            student_name = ?,
            rfid = ?,
            section_name = ?,
            grade_level = ?
        WHERE student_lrn = ?
    ");
    $stmt->bind_param("sssis", $fullName, $rfid, $section, $gradeLevel, $lrn);
    return $stmt->execute();
}