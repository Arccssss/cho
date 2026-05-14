<?php
// views/layouts/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHO System - Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="assets/images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>Bacolod City Health Office - Admin Portal</p>
                </div>
            </div>
            <div class="header-right">
                <p>Logged in as</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></strong>
                <div class="live-datetime" id="liveDateTime">
                    <i class="fas fa-clock"></i>
                    <span id="dateTimeDisplay">Loading...</span>
                </div>
            </div>
        </div>

        <nav class="modern-navbar">
            <div class="navbar-left">
                <div class="nav-brand">
                    <i class="fas fa-heartbeat"></i>
                    <span>Admin Portal</span>
                </div>
            </div>
            <div class="navbar-center">
                <div class="nav-links">
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="all_forms.php" class="nav-link">
                        <i class="fas fa-file-medical"></i>
                        <span>Forms</span>
                    </a>
                    <a href="../cho-apt-public/manage_appointments.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                </div>
            </div>
            <div class="navbar-right">
                <div class="nav-actions">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <?php displayFlashMessage(); ?>