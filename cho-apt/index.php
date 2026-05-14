<?php
require_once 'config.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('images/ngc.jpg') center center / cover no-repeat;
            filter: blur(6px) brightness(0.9);
            transform: scale(1.05);
            z-index: -2;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            z-index: -1;
            pointer-events: none;
        }

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* Left Panel - Decorative */
        .login-left {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 25%, #FDB833 50%, #F37335 75%, #FF6B35 100%);
            background-size: 400% 400%;
            animation: maaskaraFlow 15s ease infinite;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        @keyframes maaskaraFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .left-content {
            position: relative;
            z-index: 2;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            max-width: 350px;
        }

        .left-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: bounce 2s ease-in-out infinite;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }

        .left-icon img {
            width: 130px;
            height: 130px;
            object-fit: contain;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-left h1 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
        }

        .login-left p {
            font-size: 16px;
            line-height: 1.8;
            opacity: 0.95;
            margin-top: 30px;
        }

        .tagline {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            margin: 20px 0 0 0;
            opacity: 1;
            color: #FFF;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            line-height: 1.6;
            display: block;
        }

        .tagline-subtitle {
            font-size: 14px;
            font-weight: 500;
            line-height: 1.8;
            letter-spacing: 0.5px;
            margin-top: 20px;
            opacity: 0.92;
            color: #FFF;
            display: block;
        }

        /* Decorative divider */
        .left-divider {
            width: 60px;
            height: 3px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 2px;
            margin: 25px 0;
        }

        /* Right Panel - Login Form */
        .login-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-section {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .login-right h2 {
            font-size: 32px;
            font-weight: 800;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .login-subtitle {
            text-align: center;
            color: #666;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF6B35;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .alert {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-sign-in {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-sign-in:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .footer-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .footer-link a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link a:hover {
            color: #F7931E;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-left {
                padding: 40px 30px;
                min-height: 300px;
            }

            .login-left h1 {
                font-size: 32px;
            }

            .login-right {
                padding: 40px 30px;
            }

            .login-right h2 {
                font-size: 24px;
            }
        }
    </style>
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
