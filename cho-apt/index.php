<?php
require_once 'config/helpers.php';
require_once 'config/database.php';

// If already logged in, redirect to admin dashboard
if (isLoggedIn()) {
    redirect('admin_dashboard.php');
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Only allow admin users to login
                if ($user['role'] !== 'admin') {
                    $error = 'Access denied. Only admin accounts are allowed.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    setFlashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');
                    redirect('admin_dashboard.php');
                }
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Email not found';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHO System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <div class="login-wrapper">
        <!-- Left Panel -->
        <div class="login-left">
            <div class="left-content">
                <div class="left-icon">
                    <img src="images/onesmile.png" alt="OneSmile Logo">
                </div>
                <h1>Welcome Back!</h1>
                <div class="left-divider"></div>
                <p class="tagline">Healthcare for All—Our commitment to every Bacoleño</p>
                <p class="tagline-subtitle">Access your patient portal to manage your health records and appointment information with CHO.</p>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="login-right">
            <div class="logo-section">
                <img src="images/bcd.jpg" alt="CHO Logo">
            </div>

            <h2>Sign In</h2>
            <p class="login-subtitle">Enter your credentials to access your account.</p>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope" style="margin-right: 5px;"></i> Admin Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="admin@cho.gov.ph" autofocus>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock" style="margin-right: 5px;"></i> Admin Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-sign-in">
                    Admin Sign In <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="footer-link">
                <div>
                    <i class="fas fa-shield-alt"></i> Administrator Access Only
                </div>
            </div>
        </div>
    </div>

</body>
</html>
