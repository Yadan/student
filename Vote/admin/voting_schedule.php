<?php
session_start();
if($_SESSION['adminLogin']!=1)
{
    header("location:index.php");
    exit();
}
$con=mysqli_connect("localhost","root","","voting");
if(!$con){
    die("Database connection failed: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Schedule - Voting System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/all.min.css">
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

        <!-- Voting Form -->
        <div class="container">
            <div class="heading"><h1>Online Voting System</h1></div>
            <div class="form">
                <form action="" method="POST">
                    <!-- Voting Title -->
                    <label class="label">Voting Title:</label>
                    <input type="text" name="title" class="input" placeholder="Enter voting title" required>

                    <h4>Voting Schedule</h4>

                    <label class="label">Valid From:</label>
                    <input type="datetime-local" name="start" class="input" required>

                    <label class="label">Valid To:</label>
                    <input type="datetime-local" name="end" class="input" required>

                    <button class="button" name="set">Set</button>
                </form>
            </div>
        </div>
   </div>
  <script src="../js/script.js"></script>
</body>
</html>

<?php
if(isset($_POST['set']))
{
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $starting = mysqli_real_escape_string($con, $_POST['start']);
    $ending = mysqli_real_escape_string($con, $_POST['end']);

    // ✅ If voting table should only have 1 row, use UPDATE
    $query = "UPDATE voting 
              SET voting_title='$title', vot_start_date='$starting', vot_end_date='$ending'
              WHERE id=1"; // make sure your table has id=1

    // ✅ If you want multiple records instead, use INSERT
    // $query = "INSERT INTO voting (voting_title, vot_start_date, vot_end_date) 
    //           VALUES ('$title','$starting','$ending')";

    $data = mysqli_query($con,$query);

    if($data)
    {
        echo "<script>alert('Voting title & schedule saved successfully!');</script>";
    }
    else
    {
        echo "<script>alert('Something went wrong: ".mysqli_error($con)."');</script>";
    }
}
?>
