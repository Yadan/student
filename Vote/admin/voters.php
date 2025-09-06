<?php
session_start();
if($_SESSION['adminLogin']!=1) {
    header("location:index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Voting System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <style>
        .edit, .delete {
            display: block;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            text-align: center;
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            text-decoration: none;
        }
        .edit { background-color: royalblue; }
        .delete { background-color: red; }
        td { padding: 1rem; }
        .table img { max-width: 80px; height: auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="menu-bar" id="show" onclick="showMenu()">&#9776;</span>
        <span class="menu-bar" id="hide" onclick="hideMenu()">&#9776;</span>
        <span class="logo">Voting System</span>
        <span class="profile" onclick="showProfile()"><img src="../res/user3.jpg" alt=""><label><?php echo $_SESSION['name']; ?></label></span>
    </div>
    <?php include '../includes/menu.php'; ?>
    <div id="profile-panel">
        <i class="fa-solid fa-circle-xmark" onclick="hidePanel()"></i>
        <div class="dp"><img src="../res/user3.jpg" alt=""></div>
        <div class="info">
            <h2><?php echo $_SESSION['name']; ?></h2>
            <h5>Admin</h5>
        </div>
        <div class="link"><a href="../includes/admin-logout.php" class="del"><i class='fa-solid fa-arrow-right-from-bracket'></i> Logout</a></div>
    </div>
    <div id="main">
        <?php
        $con = mysqli_connect('localhost','root','','voting');
        if ($con) {
            $query = "SELECT * FROM students";
            $data = mysqli_query($con, $query);
        ?>
        <div class="heading"><h2 style="background:royalblue;">Voters</h2></div>   
        <table class="table">
            <thead>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Date of Birth</th>
                <th>Gender</th>
                <th>Phone Number</th>
                <th>Address</th>
                <th>Date Approved</th>
                <th>Voted</th>
                <th>Action</th>               
            </thead>
            <tbody>
                <?php
                if(!$data) {
                    echo "<tr><td colspan='10' style='text-align:center;'>Query error</td></tr>";
                } elseif(mysqli_num_rows($data) > 0) {
                    while($result = mysqli_fetch_assoc($data)) {
                        $full_name = $result['fname'] . " " . $result['lname'];
                        echo "<tr>
                            <td>".$result['student_id']."</td>
                            <td>".$full_name."</td>
                            <td>".$result['email']."</td>
                            <td>".$result['dob']."</td>
                            <td>".$result['gender']."</td>
                            <td>".$result['phone']."</td>
                            <td>".$result['address']."</td>
                            <td>".$result['date_approved']."</td>
                            <td>".$result['voted']."</td>
                            <td>
                                <a href='edit_student.php?id=".$result['id']."' class='edit'>Edit</a>
                                <a href='delete_student.php?id=".$result['id']."' class='delete' onclick='return confirm(\"Delete this student?\")'>Delete</a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='10' style='text-align:center;'>No students found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <?php
        } 
        ?>
    </div>
</div>
<script src="../js/script.js"></script>
</body>
</html>
