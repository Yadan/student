<?php
session_start();
if($_SESSION['adminLogin']!=1)
{
    header("location:index.php");
    exit();
}
$con=mysqli_connect("localhost","root","","voting");
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

        <!-- Profile Panel -->
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

   <div class="container">
        <div class="heading"><h1>Online Voting System</h1></div>
        <div class="form">
            <h4>Add Positions</h4>
            <form action="" method="POST">
                <label class="label">Position Name:</label>
                <input type="text" name="position" class="input" placeholder="Enter position" required>

                <button class="button" name="add">Add</button>
            </form>
        </div>
   </div>
    <script src="../js/script.js"></script>
</body>
</html>

<?php
    $con=mysqli_connect("localhost","root","","voting");

    if(isset($_POST['add']))
    {

        $pos_name=$_POST['position'];
        echo $pos_name;
        $query="INSERT INTO can_position (position_name) VALUES ('$pos_name')";
        $data=mysqli_query($con,$query);

        if($data)
        {
            echo "
            <script>
                alert('position added successfully')
                location.href='position.php'
            </script>";
        }
        else
        {
            echo "
            <script>
                alert('position already added !')
                history.back()
            </script>";
        }
    }
?>