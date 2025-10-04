<?php

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>

<div class="container">
    <div class="navigation">
        <ul>
            <li class="brand-logo">
                <a href="clerk_dashboard.php">
                    <div class="logo-container">
                        <img src="assets/imgs/isiera.jpg" alt="Isiera Logo">
                    </div>
                    <span class="title">Isiera</span>
                </a>
            </li>                    

            <li>
                <a href="clerk_id_generation.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'clerk_id_generation.php' ? 'active' : ''; ?>">
                    <span class="icon"><ion-icon name="card-outline"></ion-icon></span>
                    <span class="title">ID Generation</span>
                </a>
            </li>

            <li>
                <a href="clerk_settings.php">
                    <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
                    <span class="title">Account Settings</span>
                </a>
            </li>

            <li>
                <a href="logout.php">
                    <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                    <span class="title">Sign Out</span>
                </a>
            </li>
        </ul>
    </div>
</div>