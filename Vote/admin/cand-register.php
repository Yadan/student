<?php
error_reporting(0);
session_start();
include "../includes/all-select-data.php";

if($_SESSION['adminLogin']!=1)
{
    header("location:index.php");
    exit();
}

// Count voters who already voted (from students instead of register)
$voter_voted_query="SELECT * FROM students WHERE status='voted'";
$voter_voted_data=mysqli_query($con,$voter_voted_query);
$voter_voted=mysqli_num_rows($voter_voted_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Registration - Voting System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <script src="../js/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <span class="menu-bar" id="show" onclick="showMenu()">&#9776;</span>
            <span class="menu-bar" id="hide" onclick="hideMenu()">&#9776;</span>
            <span class="logo">Voting System</span>
            <span class="profile" onclick="showProfile()">
                <img src="../res/user3.jpg" alt="">
                <label><?php echo $_SESSION['name']; ?></label>
            </span>
        </div>

        <!-- ✅ Menu bar -->
        <?php include '../includes/menu.php'; ?>

        <!-- Profile panel -->
        <div id="profile-panel">
            <i class="fa-solid fa-circle-xmark" onclick="hidePanel()"></i>
            <div class="dp"><img src="../res/user3.jpg" alt=""></div>
            <div class="info">
                <h2><?php echo $_SESSION['name']; ?></h2>
                <h5>Admin</h5>
            </div>
            <div class="link">
                <a href="../includes/admin-logout.php" class="del">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="heading"><h1>Online Voting System</h1></div>
            <div class="form">
                <h4>Candidate Registraton</h4>
                <label class="label">Candidate Name:</label>
                <input type="text" name="cname" id="" class="input" placeholder=" Enter Candidate Name" required>

                <label class="label">Symbol Name:</label>
                <input type="text" name="csymbol" id="" class="input" placeholder="Enter Candidate Symbol Name" required>

                <label class="label">Choose symbol Image:</label>
                <input type="file" accept="image/*" name="cphoto" class="input" required>

                <label class="label">Select Position:</label>
                <select name="position" class="input">
                    <?php
                    
                        include "../includes/all-select-data.php";

                        while($result=mysqli_fetch_assoc($pos_data))
                        {
                            echo "<option value='$result[position_name]'>$result[position_name]</option>";
                        }
                    
                    ?>
                </select>

                <button class="button" name="register">Register</button>
            </div>
        </form>
        </form>
   </div>
</body>
</html>

  <!-- JS -->
    <script src="../js/script.js"></script>
</body>
</html>