<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

include 'db_connection.php';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    $getStudent = mysqli_query($conn, "SELECT * FROM register WHERE id='$id'");
    if (mysqli_num_rows($getStudent) > 0) {
        $student = mysqli_fetch_assoc($getStudent);
        $student_email = $student['email'];
        $student_name = $student['fname'] . " " . $student['lname'];

        $headers = "From: admin@votingsystem.com\r\n";
        $headers .= "Reply-To: admin@votingsystem.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if ($action == "approve") {
            $insert = mysqli_query($conn, "INSERT INTO students 
                (student_id, fname, lname, email, dob, gender, phone, address, voted, status, date_approved)
                VALUES (
                    '".$student['student_id']."',
                    '".$student['fname']."',
                    '".$student['lname']."',
                    '".$student['email']."',
                    '".$student['dob']."',
                    '".$student['gender']."',
                    '".$student['phone']."',
                    '".$student['address']."',
                    'no',
                    'not voted',
                    NOW()
                )");

            if ($insert) {
                mysqli_query($conn, "DELETE FROM register WHERE id='$id'");
                mail($student_email, "Voting Registration Approved", 
                     "Hello $student_name,\n\nYour voter registration has been APPROVED.\nYou can now log in to the voting system.\n\nThank you!", $headers);
            }

        } elseif ($action == "reject") {
            mysqli_query($conn, "DELETE FROM register WHERE id='$id'");
            mail($student_email, "Voting Registration Rejected", 
                 "Hello $student_name,\n\nWe are sorry to inform you that your voter registration has been REJECTED.\nPlease contact the admin for further details.\n\nThank you!", $headers);
        }
    }
}

header("Location: voter_request.php");
exit();
?>