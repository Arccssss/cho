<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

if (!isset($_GET['id'])) {
    redirect('admin_dashboard.php');
}

$form_id = intval($_GET['id']);
$conn = getDBConnection();

// Get form details
$stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email FROM consent_forms cf JOIN users u ON cf.user_id = u.id WHERE cf.id = ?");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Form not found.');
    redirect('admin_dashboard.php');
}

$form = $result->fetch_assoc();
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = sanitize($_POST['patient_name']);
    $service_type = sanitize($_POST['service_type']);
    $form_date = sanitize($_POST['form_date']);
    
    if (empty($patient_name) || empty($service_type) || empty($form_date)) {
        $error = 'Please fill in all required fields';
    } else {
        // Update the form
        $stmt = $conn->prepare("UPDATE consent_forms SET patient_name = ?, service_type = ?, form_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $patient_name, $service_type, $form_date, $form_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Consent form updated successfully.');
            redirect('admin_dashboard.php');
        } else {
            $error = 'Failed to update the form. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Consent Form - CHO Patient Consent System</title>
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
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .logo-box {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 15px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-title h2 {
            font-size: 24px;
            font-weight: 800;
            color: #FF6B35;
            margin: 0;
            letter-spacing: 1px;
        }

        .header-title p {
            font-size: 12px;
            color: #F7931E;
            margin: 5px 0 0 0;
            letter-spacing: 0.5px;
        }

        .header-right {
            text-align: right;
            color: #333;
        }

        .header-right p {
            font-size: 11px;
            color: #888;
            margin: 0;
        }

        .header-right strong {
            font-size: 14px;
            color: #FF6B35;
            display: block;
            margin-top: 5px;
        }

        /* Form Card */
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .form-title {
            font-size: 26px;
            font-weight: 800;
            color: #FF6B35;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-title i {
            color: #F7931E;
        }

        .form-subtitle {
            font-size: 13px;
            color: #888;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FDB833;
        }

        /* Alert */
        .alert {
            background: #FEE;
            border-left: 5px solid #EF4444;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            color: #C53030;
        }

        .alert i {
            margin-right: 10px;
            color: #EF4444;
        }

        /* Info Box */
        .info-box {
            background: #f9f9f9;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid #FF6B35;
        }

        .info-box p {
            margin: 0;
            font-size: 13px;
            color: #333;
        }

        .info-box strong {
            color: #FF6B35;
        }

        .info-box p:first-child {
            margin-bottom: 8px;
        }

        /* Form Group */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label i {
            color: #FF6B35;
            margin-right: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
            color: #333;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
            background: #fffbf9;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 35px;
        }

        .btn {
            padding: 14px 35px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: #6B7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4B5563;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .header-left {
                flex-direction: column;
            }

            .form-card {
                padding: 25px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Link -->
        <a href="admin_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>Edit Consent Form</p>
                </div>
            </div>
            <div class="header-right">
                <p>Form ID</p>
                <strong>#<?php echo $form_id; ?></strong>
            </div>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <h3 class="form-title">
                <i class="fas fa-edit"></i> Edit Consent Form
            </h3>
            <p class="form-subtitle">Modify Form Details</p>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Creator Info -->
            <div class="info-box">
                <p><strong>Created by:</strong> <?php echo htmlspecialchars($form['creator_name']); ?> (<?php echo htmlspecialchars($form['creator_email']); ?>)</p>
                <p><strong>Created on:</strong> <?php echo date('M d, Y h:i A', strtotime($form['created_at'])); ?></p>
            </div>

            <!-- Form -->
            <form method="POST">
                <div class="form-group">
                    <label for="patient_name">
                        <i class="fas fa-user"></i> Patient Full Name
                    </label>
                    <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($form['patient_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="service_type">
                        <i class="fas fa-stethoscope"></i> Service Type
                    </label>
                    <select id="service_type" name="service_type" required>
                        <option value="">-- Select Service Type --</option>
                        <option value="ABTC" <?php echo $form['service_type'] == 'ABTC' ? 'selected' : ''; ?>>ABTC</option>
                        <option value="Pedia Consultation" <?php echo $form['service_type'] == 'Pedia Consultation' ? 'selected' : ''; ?>>Pedia Consultation</option>
                        <option value="Adult Consultation" <?php echo $form['service_type'] == 'Adult Consultation' ? 'selected' : ''; ?>>Adult Consultation</option>
                        <option value="Dental" <?php echo $form['service_type'] == 'Dental' ? 'selected' : ''; ?>>Dental</option>
                        <option value="Social Hygiene" <?php echo $form['service_type'] == 'Social Hygiene' ? 'selected' : ''; ?>>Social Hygiene</option>
                        <option value="Laboratory" <?php echo $form['service_type'] == 'Laboratory' ? 'selected' : ''; ?>>Laboratory</option>
                        <option value="X-Ray/Ultrasound" <?php echo $form['service_type'] == 'X-Ray/Ultrasound' ? 'selected' : ''; ?>>X-Ray/Ultrasound</option>
                        <option value="TB Section" <?php echo $form['service_type'] == 'TB Section' ? 'selected' : ''; ?>>TB Section</option>
                        <option value="Prenatal" <?php echo $form['service_type'] == 'Prenatal' ? 'selected' : ''; ?>>Prenatal</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="form_date">
                        <i class="fas fa-calendar"></i> Form Date
                    </label>
                    <input type="date" id="form_date" name="form_date" value="<?php echo $form['form_date']; ?>" required>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Form
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
