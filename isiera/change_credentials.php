<?php
session_start();
ob_start(); // Start output buffering

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers");

// Initialize response array
$response = ["success" => false, "message" => "Initial error"];

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception("No input data received");
    }

    $input = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format");
    }

    // Validate required fields
    if (!isset($input["current_teacher_id"]) || !isset($input["current_dob"]) || 
        !isset($input["new_teacher_id"]) || !isset($input["new_password"])) {
        throw new Exception("Missing required fields");
    }

    $currentTeacherId = trim($input["current_teacher_id"]);
    $currentDob = trim($input["current_dob"]);
    $newTeacherId = trim($input["new_teacher_id"]);
    $newPassword = trim($input["new_password"]);

    // Connect to database
    $conn = new mysqli("localhost", "u474266573_isierauser", "Isieranisanpablo_01", "u474266573_TapInTime");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // First verify current credentials - convert MMDDYYYY to YYYY-MM-DD
    if (strlen($currentDob) === 8 && is_numeric($currentDob)) {
        $month = substr($currentDob, 0, 2);
        $day = substr($currentDob, 2, 2);
        $year = substr($currentDob, 4, 4);
        $formattedDob = $year . '-' . $month . '-' . $day;
    } else {
        $formattedDob = $currentDob;
    }

    $verifyQuery = "SELECT teacher_id FROM faculty WHERE teacher_id = ? AND dob = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    if (!$verifyStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $verifyStmt->bind_param("ss", $currentTeacherId, $formattedDob);
    if (!$verifyStmt->execute()) {
        throw new Exception("Execute failed: " . $verifyStmt->error);
    }

    $verifyResult = $verifyStmt->get_result();

    if ($verifyResult->num_rows !== 1) {
        throw new Exception("Invalid current credentials");
    }

    $verifyStmt->close();

    // Check if new teacher ID already exists (if changing ID)
    if ($currentTeacherId !== $newTeacherId) {
        $checkQuery = "SELECT teacher_id FROM faculty WHERE teacher_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        if (!$checkStmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $checkStmt->bind_param("s", $newTeacherId);
        if (!$checkStmt->execute()) {
            throw new Exception("Execute failed: " . $checkStmt->error);
        }
        
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            throw new Exception("New teacher ID already exists");
        }
        $checkStmt->close();
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update credentials
    $updateQuery = "UPDATE faculty SET teacher_id = ?, password = ? WHERE teacher_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $updateStmt->bind_param("sss", $newTeacherId, $hashedPassword, $currentTeacherId);
    if (!$updateStmt->execute()) {
        throw new Exception("Update failed: " . $updateStmt->error);
    }

    if ($updateStmt->affected_rows === 1) {
        $response = [
            "success" => true,
            "message" => "Credentials updated successfully"
        ];
    } else {
        throw new Exception("No changes made");
    }

    $updateStmt->close();
    $conn->close();

} catch (Exception $e) {
    $response = ["success" => false, "message" => $e->getMessage()];
} finally {
    // Clean any output and send JSON response
    ob_end_clean();
    echo json_encode($response);
    exit;
}
?>