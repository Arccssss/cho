<?php
require_once 'config/helpers.php';
require_once 'config/database.php';
require_once 'models/DashboardModel.php';
require_once 'models/FormModel.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

if (!isset($_GET['id'])) {
    redirect('admin_dashboard.php');
}

$form_id = intval($_GET['id']);
$conn = getDBConnection();

// Instantiate the Model
$formModel = new FormModel($conn);

// Get form details via Model
$form = $formModel->getConsentFormWithCreator($form_id);

if (!$form) {
    setFlashMessage('error', 'Form not found.');
    redirect('admin_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = sanitize($_POST['patient_name']);
    $service_type = sanitize($_POST['service_type']);
    $form_date = sanitize($_POST['form_date']);
    
    if (empty($patient_name) || empty($service_type) || empty($form_date)) {
        $error = 'Please fill in all required fields';
    } else {
        // Update the form via Model
        if ($formModel->updateConsentForm($form_id, $patient_name, $service_type, $form_date)) {
            setFlashMessage('success', 'Consent form updated successfully.');
            redirect('admin_dashboard.php');
        } else {
            $error = 'Failed to update the form. Please try again.';
        }
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
    <link rel="stylesheet" href="assets/css/admin_edit_form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

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
                <strong>#<?php echo htmlspecialchars($form_id); ?></strong>
            </div>
        </div>

        <div class="form-card">
            <h3 class="form-title">
                <i class="fas fa-edit"></i> Edit Consent Form
            </h3>
            <p class="form-subtitle">Modify Form Details</p>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <p><strong>Created by:</strong> <?php echo htmlspecialchars($form['creator_name']); ?> (<?php echo htmlspecialchars($form['creator_email']); ?>)</p>
                <p><strong>Created on:</strong> <?php echo date('M d, Y h:i A', strtotime($form['created_at'])); ?></p>
            </div>

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
                    <input type="date" id="form_date" name="form_date" value="<?php echo htmlspecialchars($form['form_date']); ?>" required>
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