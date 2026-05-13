<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();

// Get all forms with user info
$stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                       FROM consent_forms cf 
                       JOIN users u ON cf.user_id = u.id 
                       ORDER BY cf.created_at DESC");
$stmt->execute();
$forms = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Forms - CHO Patient Consent System</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF9F5 100%);
            padding: 35px 40px;
            border-radius: 24px;
            margin-bottom: 45px;
            box-shadow: 0 15px 40px rgba(255, 107, 53, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            border: 2px solid rgba(255, 107, 53, 0.08);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 25px;
            flex: 1;
        }

        .logo-box {
            width: 85px;
            height: 85px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            transition: all 0.3s ease;
            position: relative;
        }

        .logo-box:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .header-title h2 {
            font-size: 32px;
            font-weight: 900;
            color: #FF6B35;
            margin: 0;
            letter-spacing: 0.8px;
            text-shadow: 0 2px 4px rgba(255, 107, 53, 0.1);
            position: relative;
        }

        .header-title p {
            font-size: 14px;
            color: #F7931E;
            margin: 12px 0 0 0;
            letter-spacing: 0.4px;
            font-weight: 500;
            opacity: 0.9;
        }

        .header-right {
            text-align: right;
            color: #333;
            background: linear-gradient(135deg, #FFF9F5 0%, #FFFFFF 100%);
            padding: 20px 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 107, 53, 0.1);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.08);
            transition: all 0.3s ease;
        }

        .header-right:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.12);
        }

        .header-right p {
            font-size: 12px;
            color: #888;
            margin: 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .header-right strong {
            font-size: 18px;
            color: #FF6B35;
            display: block;
            margin-top: 8px;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(255, 107, 53, 0.1);
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 0;
            border-radius: 20px;
            margin-bottom: 45px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .navbar-nav {
            display: flex;
            gap: 0;
            padding: 0;
            margin: 0;
        }

        .navbar-nav a {
            flex: 1;
            padding: 15px 20px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            border-right: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            text-align: center;
        }

        .navbar-nav a:last-child {
            border-right: none;
        }

        .navbar-nav a:hover {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        /* Alert */
        .alert {
            background: white;
            border-left: 5px solid #FF6B35;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert i {
            color: #FF6B35;
            margin-right: 10px;
        }

        /* Section Title */
        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: #FF6B35;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }

        .section-title i {
            margin-right: 10px;
            color: #F7931E;
        }

        /* Table */
        .table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        table thead th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn {
            padding: 8px 15px;
            font-size: 11px;
            white-space: nowrap;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data i {
            font-size: 40px;
            color: #ddd;
            margin-bottom: 10px;
            display: block;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .header-left {
                flex-direction: column;
            }

            .navbar-nav a {
                padding: 12px 15px;
                font-size: 13px;
            }

            table {
                font-size: 12px;
            }

            table thead th,
            table tbody td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>All Consent Forms Records</p>
                </div>
            </div>
            <div class="header-right">
                <p>Logged in as</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-nav">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </nav>

        <!-- Alert Messages -->
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="alert">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Section Title -->
        <h3 class="section-title"><i class="fas fa-file-medical-alt"></i> All Consent Forms</h3>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-id-card"></i> Patient Name</th>
                        <th><i class="fas fa-stethoscope"></i> Service Type</th>
                        <th><i class="fas fa-user"></i> Created By</th>
                        <th><i class="fas fa-calendar"></i> Form Date</th>
                        <th><i class="fas fa-clock"></i> Created</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($forms->num_rows > 0): ?>
                        <?php while ($form = $forms->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $form['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($form['patient_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($form['service_type']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($form['creator_name']); ?><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($form['creator_email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($form['form_date'])); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($form['created_at'])); ?></td>
                                <td>
                                    <a href="view_form.php?id=<?php echo $form['id']; ?>" class="btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No forms submitted yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
