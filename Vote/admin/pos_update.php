<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

$psnm = $_GET['psnm'];
$id = $_GET['id'];
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

        <!-- Candidate Position Update Form -->
        <div class="container">
            <div class="heading"><h1>Online Voting System</h1></div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form">
                    <h4>Candidate Position Update</h4>

                    <label class="label">Candidate Position:</label>
                    <input type="text" name="position" class="input" value="<?php echo $psnm; ?>">

                    <button class="button" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (isset($_POST['update'])) {
        $position = $_POST['position'];
        $con = mysqli_connect("localhost", "root", "", "voting");
        $query = "UPDATE can_position SET position_name='$position' WHERE id='$id'";

        $data = mysqli_query($con, $query);

        if ($data) {
            echo "
                <script>
                    alert('Position updated successfully!');
                    location.href='position.php';
                </script>
            ";
        } else {
            echo "
                <script>
                    alert('Something went wrong!');
                </script>
            ";
        }
    }
    ?>

    <script src="../js/script.js"></script>
</body>
</html>
