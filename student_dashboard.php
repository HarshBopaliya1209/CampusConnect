<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}
?>
<style>
    body {
        background: red !important;
    }
</style>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect | Student Dashboard</title>

        <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

    <!-- Sidebar -->

    <div class="sidebar">

        <h2>Campus<span>Connect</span></h2>

        <ul>

            <li class="active">
    <a href="student_dashboard.php">
        <i class="fa-solid fa-house"></i>
        Dashboard
    </a>
</li>

<li>
    <a href="notices.php">
        <i class="fa-solid fa-bullhorn"></i>
        Notices
    </a>
</li>

<li>
    <a href="applications.php">
        <i class="fa-solid fa-file-lines"></i>
        Applications
    </a>
</li>

<li>
    <a href="library.php">
        <i class="fa-solid fa-book"></i>
        Library
    </a>
</li>

<li>
    <a href="canteen.php">
        <i class="fa-solid fa-utensils"></i>
        Canteen
    </a>
</li>

<li>
    <a href="payments.php">
        <i class="fa-solid fa-credit-card"></i>
        Payments
    </a>
</li>

<li>
    <a href="student_profile.php">
        <i class="fa-solid fa-user"></i>
        Profile
    </a>
</li>

<li>
    <a href="logout.php">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>
</li>

        </ul>

    </div>

    <!-- Main Content -->

    <div class="main-content">

        <!-- Header -->

        <div class="header">

            <div></div>

            <div class="profile">

                <div class="avatar">
                    <?php echo substr($_SESSION['name'], 0, 1); ?>
                </div>

                <div>

                   <h3><?php echo $_SESSION['name']; ?></h3>

                    <p>
                        <?php echo $_SESSION['course'] ." Semester " . $_SESSION['semester']; ?>
                    </p>

                </div>

            </div>

        </div>

        <!-- Welcome Card -->

        <div class="student-welcome">

            <div class="student-welcome-content">

                <span class="student-welcome-tag">
                    <i class="fa-solid fa-graduation-cap"></i>
                    Student Portal
                </span>

                <h2>Welcome, <?php echo $_SESSION['name']; ?> 👋</h2>

                <p>Manage notices, applications, library, canteen and payments from one place.</p>

                <div class="student-welcome-info">
                    <span>
                        <i class="fa-solid fa-id-card"></i>
                        Student ID: <?php echo $_SESSION['student_id']; ?>
                    </span>
                    <span>
                        <i class="fa-solid fa-book-open"></i>
                        <?php echo $_SESSION['course']; ?>
                    </span>
                    <span>
                        <i class="fa-solid fa-layer-group"></i>
                        Semester <?php echo $_SESSION['semester']; ?>
                    </span>
                </div>

            </div>

            <div class="student-welcome-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

        </div>

        <!-- Statistics -->

        <div class="cards">

            <div class="card">
                <h1>12</h1>
                <p>Total Notices</p>
            </div>

            <div class="card">
                <h1>03</h1>
                <p>Applications</p>
            </div>

            <div class="card">
                <h1>02</h1>
                <p>Books Issued</p>
            </div>

            <div class="card">
                <h1>05</h1>
                <p>Canteen Orders</p>
            </div>

        </div>

    </div>

</div>

</body>

</html>b