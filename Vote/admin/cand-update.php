<?php
session_start();
if ($_SESSION['adminLogin'] != 1) {
    header("location:index.php");
    exit();
}

$cn = $_GET['cn'];
$sy = $_GET['sy'];
$ps = $_GET['ps'];

// ✅ DB Connection
$con = mysqli_connect("localhost", "root", "", "voting");
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
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
</head>
<body>
    <div class="container">
        <!-- ✅ Header -->
        <div class="header">
            <span class="menu-bar" id="show" onclick="showMenu()">&#9776;</span>
            <span class="menu-bar" id="hide" onclick="hideMenu()">&#9776;</span>
            <span class="logo">Voting System</span>
            <span class="profile" onclick="showProfile()">
                <img src="../res/user3.jpg" alt="">
                <label><?php echo $_SESSION['name']; ?></label>
            </span>
        </div>

        <!-- ✅ Profile Panel -->
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

        <!-- ✅ Side Menu -->
        <?php include '../includes/menu.php'; ?>

        <!-- ✅ Main Content -->
        <div id="main">
            <div class="heading"><h2>Update Candidate Information</h2></div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form">
                    <label class="label">Candidate Name:</label>
                    <input type="text" name="cname" class="input" value="<?php echo $cn; ?>">

                    <label class="label">Candidate Symbol Name:</label>
                    <input type="text" name="symbol" class="input" value="<?php echo $sy; ?>">

                    <label class="label">Candidate Position:</label>
                    <select name="position" class="input">
                        <?php
                        include "../includes/all-select-data.php";
                        echo "<option value='$ps'>$ps (already selected)</option>";
                        while ($result = mysqli_fetch_assoc($pos_data)) {
                            echo "<option value='" . $result['position_name'] . "'>" . $result['position_name'] . "</option>";
                        }
                        ?>
                    </select>

                    <button class="button" name="update">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>

<?php
if (isset($_POST['update'])) {
    $cname = mysqli_real_escape_string($con, $_POST['cname']);
    $symbol = mysqli_real_escape_string($con, $_POST['symbol']);
    $position = mysqli_real_escape_string($con, $_POST['position']);

    $query = "UPDATE candidate 
              SET cname='$cname', symbol='$symbol', position='$position' 
              WHERE symbol='$sy'";

    $data = mysqli_query($con, $query);

    if ($data) {
        echo "<script>
                alert('Candidate updated successfully');
                location.href='candidates.php';
              </script>";
    } else {
        echo "<script>alert('Update failed: " . mysqli_error($con) . "');</script>";
    }
}
?>