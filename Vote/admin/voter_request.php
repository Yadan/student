<?php
session_start();

if($_SESSION['adminLogin']!=1)
{
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
        .approve, .reject {
            display: block;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            text-align: center;
            padding: 5px 10px;
            border-radius: 5px;
            color: #fff;
            text-decoration: none;
        }
        .approve { background-color: green; }
        .reject { background-color: red; }
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
        // Database connection
        $con = mysqli_connect('localhost','root','','voting');
        
        if ($con) {
            $table_check = mysqli_query($con, "SHOW TABLES LIKE 'register'");
            if (mysqli_num_rows($table_check) > 0) {
                
                $columns_query = mysqli_query($con, "SHOW COLUMNS FROM register");
                $columns = [];
                while ($col = mysqli_fetch_assoc($columns_query)) {
                    $columns[] = $col['Field'];
                }
                
                $has_fname = in_array('fname', $columns);
                $has_frame = in_array('frame', $columns);
                $has_name = in_array('name', $columns);
                
                if ($has_fname && in_array('lname', $columns)) {
                    $name_field1 = 'fname';
                    $name_field2 = 'lname';
                } elseif ($has_frame && $has_name) {
                    $name_field1 = 'frame';
                    $name_field2 = 'name';
                } else {
                    $name_field1 = $has_fname ? 'fname' : ($has_frame ? 'frame' : '');
                    $name_field2 = in_array('lname', $columns) ? 'lname' : ($has_name ? 'name' : '');
                }
        ?>
        
<div class="heading"><h2 style="background:royalblue;">Voters Request Information</h2></div>   
<table class="table">
       <thead>
            <!-- Removed ID and Status -->
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>Phone Number</th>
            <th>Address</th>
            <th>Action</th>               
       </thead>
       <tbody>
              <?php
              $query = "SELECT * FROM register";
              $data = mysqli_query($con, $query);
              
              if(!$data) {
                  echo "<tr><td colspan='7' style='text-align:center;'>Query error</td></tr>";
              } elseif(mysqli_num_rows($data) > 0) {
                  while($result = mysqli_fetch_assoc($data))
                  {
                    $full_name = isset($result[$name_field1]) && isset($result[$name_field2]) 
                        ? $result[$name_field1] . " " . $result[$name_field2] 
                        : 'Name not available';

                        echo "<tr>
    <td>".(isset($result['student_id']) ? $result['student_id'] : '')."</td>
    <td>".$full_name."</td>
    <td>".(isset($result['email']) ? $result['email'] : '')."</td>
    <td>".(isset($result['dob']) ? $result['dob'] : '')."</td>
    <td>".(isset($result['gender']) ? $result['gender'] : '')."</td>
    <td>".(isset($result['phone']) ? $result['phone'] : '')."</td>
    <td>".(isset($result['address']) ? $result['address'] : '')."</td>
    <td>
        <a href='verify.php?action=approve&id=".$result['id']."' class='approve' onclick='return confirm(\"Approve this voter?\")'>Approve</a>
        <a href='verify.php?action=reject&id=".$result['id']."' class='reject' onclick='return confirm(\"Reject and delete this voter?\")'>Reject</a>
    </td>
</tr>";
                  }
              } else {
                  echo "<tr><td colspan='7' style='text-align:center;'>No voters found</td></tr>";
              }
              ?>
       </tbody>
   </table>
        <?php
            } 
        } 
        ?>
        </div>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>
