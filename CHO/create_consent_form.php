<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?role=user');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = sanitize($_POST['patient_name']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $age = intval($_POST['age']);
    $sex = sanitize($_POST['sex']);
    $service_type = isset($_POST['service_type']) ? implode(', ', $_POST['service_type']) : '';
    $form_date = sanitize($_POST['form_date']);
    $photo_data = $_POST['photo_data'];
    $signature_data = $_POST['signature_data'];
    
    // Check if user wants to sign with ballpen
    $skip_signature = isset($_POST['skip_signature']) && $_POST['skip_signature'] === 'on';
    
    if (empty($patient_name) || empty($date_of_birth) || empty($age) || empty($sex) || empty($service_type) || empty($form_date) || empty($photo_data) || (!$skip_signature && empty($signature_data))) {
        $error = $skip_signature ? 'Please fill in all fields and capture photo' : 'Please fill in all fields, capture photo, and draw your signature';
    } else {
        // Create uploads directory if not exists
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Save photo from base64
        $photo_filename = 'photo_' . time() . '_' . $_SESSION['user_id'] . '.png';
        $photo_path = $upload_dir . $photo_filename;
        
        // Remove base64 header and save photo (handles png, jpeg, webp, etc.)
        $photo_data = preg_replace('/^data:image\/[a-zA-Z+]+;base64,/', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        // Handle signature - either save digital signature or mark as ballpen
        if ($skip_signature) {
            $signature_path = 'Signed with ballpen';
            $photo_saved = file_put_contents($photo_path, $photo_binary);
        } else {
            // Save signature from base64
            $signature_filename = 'signature_' . time() . '_' . $_SESSION['user_id'] . '.png';
            $signature_path = $upload_dir . $signature_filename;
            
            // Remove base64 header and save signature
            $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
            $signature_data = str_replace(' ', '+', $signature_data);
            $signature_binary = base64_decode($signature_data);
            
            $photo_saved = file_put_contents($photo_path, $photo_binary) && file_put_contents($signature_path, $signature_binary);
        }
        
        if ($photo_saved) {
            $conn = getDBConnection();
            $stmt = $conn->prepare("INSERT INTO consent_forms (user_id, patient_name, patient_photo, date_of_birth, age, sex, service_type, form_date, signature_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssissss", $_SESSION['user_id'], $patient_name, $photo_path, $date_of_birth, $age, $sex, $service_type, $form_date, $signature_path);
            
            if ($stmt->execute()) {
                $form_id = $stmt->insert_id;
                setFlashMessage('success', 'Consent form created successfully!');
                redirect('view_form.php?id=' . $form_id);
            } else {
                $error = 'Failed to save form. Please try again.';
                unlink($photo_path);
                if (!$skip_signature && file_exists($signature_path)) {
                    unlink($signature_path);
                }
            }
            
            $stmt->close();
            $conn->close();
        } else {
            $error = 'Failed to save photo. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHO Patient Consent System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.0.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@tensorflow-models/body-pix@2.2.0"></script>
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
            max-width: 900px;
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
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-title h2 {
            font-size: 28px;
            font-weight: 800;
            color: #FF6B35;
            margin: 0;
            letter-spacing: 1px;
        }

        .header-title p {
            font-size: 13px;
            color: #F7931E;
            margin: 5px 0 0 0;
            letter-spacing: 0.5px;
        }

        .header-right {
            text-align: right;
            color: #333;
        }

        .header-right p {
            font-size: 12px;
            color: #888;
            margin: 0;
        }

        .header-right strong {
            font-size: 16px;
            color: #FF6B35;
            display: block;
            margin-top: 5px;
        }

        /* Navigation */
        .navbar {
            background: white;
            padding: 0;
            border-radius: 20px;
            margin-bottom: 30px;
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

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 800;
            color: #FF6B35;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #FDB833;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #F7931E;
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

        .form-group select[multiple] {
            min-height: 120px;
            padding: 10px;
        }

        .form-group select[multiple] option {
            padding: 8px;
            margin-bottom: 5px;
        }

        /* Service Type Checkboxes */
        .service-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            user-select: none;
        }

        .checkbox-label:hover {
            background: #efefef;
        }

        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #FF6B35;
        }

        .checkbox-label span {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FF6B35;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        /* Photo Section */
        .photo-section {
            background: #f9f9f9;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            padding: 20px;
        }

        .camera-container {
            position: relative;
            width: 100%;
            max-width: 500px;
            margin: 0 auto 20px;
            border-radius: 8px;
            overflow: hidden;
        }

        #video, #canvas, #captured-photo {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
        }

        #video {
            border: 3px solid #ddd;
        }

        #video.face-detected {
            border-color: #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
        }

        #video.no-face {
            border-color: #ef4444;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.5);
        }

        #video.blurry-image {
            border-color: #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.5);
        }

        #captured-photo {
            display: none;
        }

        /* Face Detection Status */
        .detection-status {
            text-align: center;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            display: none;
        }

        .detection-status.active {
            display: block;
        }

        .detection-status.success {
            background: #D1FAE5;
            color: #065F46;
            border: 2px solid #10b981;
        }

        .detection-status.warning {
            background: #FEF3C7;
            color: #78350F;
            border: 2px solid #f59e0b;
        }

        .detection-status.error {
            background: #FEE2E2;
            color: #7F1D1D;
            border: 2px solid #ef4444;
        }

        .detection-icon {
            margin-right: 8px;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-camera {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Signature Section */
        #signatureCanvas {
            border: 1px solid #ccc;
            border-radius: 8px;
            display: block;
            margin: 0 auto 20px;
            background: white;
            cursor: crosshair;
            width: 100%;
            max-width: 500px;
            height: 150px;
        }

        /* Submit Buttons */
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 14px 35px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

            .form-card {
                padding: 25px;
            }

            .section-title {
                font-size: 18px;
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
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>Create Patient Consent Form</p>
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
        <?php if ($error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <form method="POST" action="" id="consentForm">
                <!-- Basic Information Section -->
                <h3 class="section-title"><i class="fas fa-info-circle"></i> Patient Information</h3>

                <div class="form-group">
                    <label for="patient_name"><i class="fas fa-user" style="margin-right: 5px;"></i> Patient Full Name</label>
                    <input type="text" id="patient_name" name="patient_name" required 
                           placeholder="Enter patient's full name">
                </div>

                <div class="form-group">
                    <label for="date_of_birth"><i class="fas fa-calendar" style="margin-right: 5px;"></i> Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required 
                           max="<?php echo date('Y-m-d'); ?>" onchange="calculateAge()">
                </div>

                <div class="form-group">
                    <label for="age"><i class="fas fa-hourglass-half" style="margin-right: 5px;"></i> Age</label>
                    <input type="number" id="age" name="age" required 
                           min="0" max="150" placeholder="Patient's age" readonly>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-venus-mars" style="margin-right: 5px;"></i> Sex</label>
                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio" name="sex" value="Male" required>
                            <span>Male</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio" name="sex" value="Female" required>
                            <span>Female</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <input type="radio" name="sex" value="Other" required>
                            <span>Other</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label style="margin-bottom: 12px;"><i class="fas fa-stethoscope" style="margin-right: 5px;"></i> Service Type (Select One or More)</label>
                    <div id="serviceTypeCheckboxes" class="service-checkboxes">
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="ABTC">
                            <span>ABTC</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Pedia Consultation">
                            <span>Pedia Consultation</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Adult Consultation">
                            <span>Adult Consultation</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Dental">
                            <span>Dental</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Social Hygiene">
                            <span>Social Hygiene</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Laboratory">
                            <span>Laboratory</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="X-Ray/Ultrasound">
                            <span>X-Ray/Ultrasound</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="TB Section">
                            <span>TB Section</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_type[]" value="Prenatal">
                            <span>Prenatal</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="form_date"><i class="fas fa-calendar" style="margin-right: 5px;"></i> Form Date</label>
                    <input type="date" id="form_date" name="form_date" required 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Photo Capture Section -->
                <h3 class="section-title"><i class="fas fa-camera"></i> Patient Photo</h3>
                <div class="photo-section">

                    <!-- Mode Toggle Tabs -->
                    <div style="display: flex; gap: 0; margin-bottom: 15px; border-radius: 10px; overflow: hidden; border: 2px solid #FF6B35;">
                        <button type="button" id="tabCamera" onclick="switchPhotoMode('camera')"
                            style="flex: 1; padding: 10px; border: none; background: linear-gradient(135deg, #FF6B35, #F7931E); color: white; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-camera"></i> Use Camera
                        </button>
                        <button type="button" id="tabUpload" onclick="switchPhotoMode('upload')"
                            style="flex: 1; padding: 10px; border: none; background: white; color: #FF6B35; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-upload"></i> Upload Selfie
                        </button>
                    </div>

                    <!-- Camera Mode -->
                    <div id="cameraModeSection">
                        <div class="detection-status" id="detectionStatus">
                            <span class="detection-icon"><i class="fas fa-spinner fa-spin"></i></span>
                            <span id="detectionMessage">Initializing face detection...</span>
                        </div>
                        <div class="camera-container">
                            <video id="video" autoplay playsinline></video>
                            <canvas id="canvas" style="display: none;"></canvas>
                            <img id="captured-photo" alt="Captured photo">
                        </div>
                        <div class="camera-controls">
                            <button type="button" id="startCamera" class="btn-camera btn-primary">
                                <i class="fas fa-camera"></i> Start Camera
                            </button>
                            <button type="button" id="capturePhoto" class="btn-camera btn-success" style="display: none;" disabled>
                                <i class="fas fa-camera-retro"></i> Capture
                            </button>
                            <button type="button" id="retakePhoto" class="btn-camera btn-secondary" style="display: none;">
                                <i class="fas fa-redo"></i> Retake
                            </button>
                        </div>
                    </div>

                    <!-- Upload Mode -->
                    <div id="uploadModeSection" style="display: none;">
                        <div style="text-align: center; margin-bottom: 12px; font-size: 13px; color: #666;">
                            <i class="fas fa-info-circle"></i> Upload a clear front-facing photo (JPG, PNG, WEBP)
                        </div>
                        <div class="camera-container" id="uploadPreviewContainer" style="cursor: pointer;" onclick="document.getElementById('selfieUploadInput').click()">
                            <img id="uploadPreview" alt="Upload preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            <div id="uploadPlaceholder" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #aaa; gap: 8px;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: #FF6B35;"></i>
                                <span style="font-size: 13px; font-weight: 600; color: #555;">Click to browse photo</span>
                                <span style="font-size: 11px; color: #aaa;">JPG, PNG, WEBP accepted</span>
                            </div>
                        </div>
                        <input type="file" id="selfieUploadInput" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        <div class="camera-controls" style="margin-top: 10px;">
                            <button type="button" id="clearUpload" class="btn-camera btn-secondary" style="display: none;" onclick="clearUploadedPhoto()">
                                <i class="fas fa-trash"></i> Remove Photo
                            </button>
                        </div>
                        <div id="uploadStatus" style="display: none; text-align: center; padding: 8px 16px; border-radius: 8px; font-size: 13px; margin-top: 8px;"></div>
                    </div>

                    <input type="hidden" name="photo_data" id="photo_data">
                </div>

                <!-- Signature Section -->
                <h3 class="section-title"><i class="fas fa-pen"></i> Your Signature</h3>
                <div class="photo-section">
                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; cursor: pointer; font-size: 14px; color: #374151;">
                            <input type="checkbox" id="skipSignature" name="skip_signature" onchange="toggleSignatureOption()" style="margin-right: 8px;">
                            <span>I will sign with a ballpen on the printed form</span>
                        </label>
                        <p style="font-size: 12px; color: #6b7280; margin-top: 5px; margin-left: 20px;">Check this if you prefer to sign manually after printing</p>
                    </div>
                    
                    <div id="digitalSignatureSection">
                        <p style="font-size: 13px; color: #666; margin-bottom: 15px; text-align: center;">Draw your signature in the box below</p>
                        <canvas id="signatureCanvas"></canvas>
                        <div class="camera-controls">
                            <button type="button" id="clearSignature" class="btn-camera btn-secondary">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                        </div>
                        <input type="hidden" name="signature_data" id="signature_data">
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="fas fa-save"></i> Create Consent Form
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Face detection using TensorFlow
        let cocoModel = null;
        let detectionActive = false;
        let faceDetectionInterval = null;
        let lastDetectionTime = 0;

        // Initialize COCO-SSD model for face detection
        async function initializeFaceDetection() {
            // Guard: wait until cocoSsd is available (handles slow networks)
            let retries = 0;
            while (typeof cocoSsd === 'undefined' && retries < 20) {
                await new Promise(resolve => setTimeout(resolve, 500));
                retries++;
            }

            if (typeof cocoSsd === 'undefined') {
                showDetectionStatus('error', 'Face detection unavailable. Please check your internet connection and reload.', '<i class="fas fa-exclamation-circle"></i>');
                return;
            }

            try {
                cocoModel = await cocoSsd.load();
                console.log('Face detection model loaded');
            } catch (err) {
                console.error('Error loading face detection model:', err);
                showDetectionStatus('error', 'Face detection unavailable. System requires stable internet connection.', '<i class="fas fa-exclamation-circle"></i>');
            }
        }

        // Initialize on page load
        window.addEventListener('load', initializeFaceDetection);

        // Detection status display
        function showDetectionStatus(type, message, icon = '') {
            const statusElement = document.getElementById('detectionStatus');
            const messageElement = document.getElementById('detectionMessage');
            
            statusElement.classList.remove('success', 'warning', 'error', 'active');
            if (type !== 'hidden') {
                statusElement.classList.add(type, 'active');
                messageElement.innerHTML = (icon || '') + message;
            }
        }

        // Detect faces and blur in video stream
        async function detectFaceAndBlur() {
            const video = document.getElementById('video');
            if (!video.srcObject || !cocoModel) return;

            try {
                const predictions = await cocoModel.detect(video);
                const faces = predictions.filter(pred => pred.class === 'person' && pred.score > 0.5);

                if (faces.length === 0) {
                    document.getElementById('video').classList.remove('face-detected', 'blurry-image');
                    document.getElementById('video').classList.add('no-face');
                    showDetectionStatus('error', '<i class="fas fa-face-dizzy"></i> No face detected. Please position your face in the camera.', '');
                    document.getElementById('capturePhoto').disabled = true;
                } else {
                    // Check for blur using Laplacian variance
                    const blurScore = await checkImageBlur(video);
                    
                    if (blurScore < 50) {
                        // Image is blurry
                        document.getElementById('video').classList.remove('face-detected');
                        document.getElementById('video').classList.add('blurry-image');
                        showDetectionStatus('warning', '<i class="fas fa-water"></i> Image is blurry. Please hold still and try again.', '');
                        document.getElementById('capturePhoto').disabled = true;
                    } else {
                        // Face detected and image is clear
                        document.getElementById('video').classList.remove('no-face', 'blurry-image');
                        document.getElementById('video').classList.add('face-detected');
                        showDetectionStatus('success', '<i class="fas fa-check-circle"></i> Face detected! Image quality is good. Click Capture when ready.', '');
                        document.getElementById('capturePhoto').disabled = false;
                    }
                }
            } catch (err) {
                console.error('Detection error:', err);
            }
        }

        // Calculate image blur using Laplacian variance
        async function checkImageBlur(video) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            ctx.drawImage(video, 0, 0);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;

            // Convert to grayscale
            const gray = [];
            for (let i = 0; i < data.length; i += 4) {
                gray.push(data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114);
            }

            // Apply Laplacian operator and calculate variance
            let laplacianSum = 0;
            let laplacianCount = 0;
            const width = canvas.width;
            const height = canvas.height;

            for (let y = 1; y < height - 1; y++) {
                for (let x = 1; x < width - 1; x++) {
                    const idx = y * width + x;
                    
                    // Laplacian kernel
                    const laplacian = -gray[idx - width - 1] - gray[idx - width] - gray[idx - width + 1]
                                    - gray[idx - 1] + 8 * gray[idx] - gray[idx + 1]
                                    - gray[idx + width - 1] - gray[idx + width] - gray[idx + width + 1];
                    
                    laplacianSum += laplacian * laplacian;
                    laplacianCount++;
                }
            }

            const variance = laplacianSum / laplacianCount;
            return variance;
        }

        // Photo capture functionality
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const capturedPhoto = document.getElementById('captured-photo');
        const startCameraBtn = document.getElementById('startCamera');
        const captureBtn = document.getElementById('capturePhoto');
        const retakeBtn = document.getElementById('retakePhoto');
        const photoDataInput = document.getElementById('photo_data');
        const submitBtn = document.getElementById('submitBtn');
        
        let stream = null;
        
        // Start camera
        startCameraBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    } 
                });
                video.srcObject = stream;
                startCameraBtn.style.display = 'none';
                captureBtn.style.display = 'inline-flex';
                
                detectionActive = true;
                showDetectionStatus('hidden', '');
                
                // Start continuous face detection
                if (faceDetectionInterval) clearInterval(faceDetectionInterval);
                faceDetectionInterval = setInterval(detectFaceAndBlur, 500);
            } catch (err) {
                alert('Error accessing camera: ' + err.message);
                showDetectionStatus('error', '<i class="fas fa-camera"></i> Camera access denied. Please enable camera permissions.', '');
            }
        });
        
        // Capture photo
        captureBtn.addEventListener('click', async () => {
            try {
                // Final validation before capture
                if (!cocoModel) {
                    alert('Face detection not ready. Please wait and try again.');
                    return;
                }

                const predictions = await cocoModel.detect(video);
                const faces = predictions.filter(pred => pred.class === 'person' && pred.score > 0.5);

                if (faces.length === 0) {
                    showDetectionStatus('error', '<i class="fas fa-face-dizzy"></i> No face detected. Cannot capture.', '');
                    return;
                }

                const blurScore = await checkImageBlur(video);
                if (blurScore < 50) {
                    showDetectionStatus('warning', '<i class="fas fa-water"></i> Image too blurry. Please hold still and try again.', '');
                    return;
                }

                // Capture is valid
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                const photoData = canvas.toDataURL('image/png');
                photoDataInput.value = photoData;
                
                capturedPhoto.src = photoData;
                capturedPhoto.style.display = 'block';
                video.style.display = 'none';
                
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'inline-flex';
                
                detectionActive = false;
                if (faceDetectionInterval) clearInterval(faceDetectionInterval);
                
                showDetectionStatus('success', '<i class="fas fa-check-circle"></i> Photo captured successfully!', '');
                checkFormCompletion();
                
                // Stop camera
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
            } catch (err) {
                console.error('Capture error:', err);
                showDetectionStatus('error', '<i class="fas fa-exclamation-circle"></i> Error capturing photo. Please try again.', '');
            }
        });
        
        // Retake photo
        retakeBtn.addEventListener('click', async () => {
            capturedPhoto.style.display = 'none';
            video.style.display = 'block';
            photoDataInput.value = '';
            checkFormCompletion();
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    } 
                });
                video.srcObject = stream;
                retakeBtn.style.display = 'none';
                captureBtn.style.display = 'inline-flex';
                
                detectionActive = true;
                
                // Resume face detection
                if (faceDetectionInterval) clearInterval(faceDetectionInterval);
                faceDetectionInterval = setInterval(detectFaceAndBlur, 500);
            } catch (err) {
                alert('Error accessing camera: ' + err.message);
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (faceDetectionInterval) clearInterval(faceDetectionInterval);
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });

        // ── PHOTO MODE SWITCHER ──────────────────────────────────────────
        function switchPhotoMode(mode) {
            const cameraSection  = document.getElementById('cameraModeSection');
            const uploadSection  = document.getElementById('uploadModeSection');
            const tabCamera      = document.getElementById('tabCamera');
            const tabUpload      = document.getElementById('tabUpload');

            if (mode === 'upload') {
                // Stop camera and clear camera state
                if (faceDetectionInterval) clearInterval(faceDetectionInterval);
                if (stream) stream.getTracks().forEach(t => t.stop());
                stream = null;
                detectionActive = false;

                // Reset camera UI
                video.style.display = 'block';
                capturedPhoto.style.display = 'none';
                captureBtn.style.display = 'none';
                retakeBtn.style.display = 'none';
                startCameraBtn.style.display = 'inline-flex';
                photoDataInput.value = '';
                checkFormCompletion();

                // Switch sections
                cameraSection.style.display = 'none';
                uploadSection.style.display = 'block';

                // Tab styles
                tabCamera.style.background = 'white';
                tabCamera.style.color = '#FF6B35';
                tabUpload.style.background = 'linear-gradient(135deg, #FF6B35, #F7931E)';
                tabUpload.style.color = 'white';

            } else {
                // Clear upload state
                clearUploadedPhoto();

                // Switch sections
                uploadSection.style.display = 'none';
                cameraSection.style.display = 'block';

                // Tab styles
                tabCamera.style.background = 'linear-gradient(135deg, #FF6B35, #F7931E)';
                tabCamera.style.color = 'white';
                tabUpload.style.background = 'white';
                tabUpload.style.color = '#FF6B35';
            }
        }

        // ── UPLOAD SELFIE HANDLER ────────────────────────────────────────
        document.getElementById('selfieUploadInput').addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showUploadStatus('error', '<i class="fas fa-exclamation-circle"></i> Invalid file type. Please upload JPG, PNG, or WEBP.');
                this.value = '';
                return;
            }

            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showUploadStatus('error', '<i class="fas fa-exclamation-circle"></i> File too large. Maximum size is 5MB.');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const base64 = e.target.result;

                // Show preview
                const preview     = document.getElementById('uploadPreview');
                const placeholder = document.getElementById('uploadPlaceholder');
                const clearBtn    = document.getElementById('clearUpload');

                preview.src = base64;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                clearBtn.style.display = 'inline-flex';

                // Store in hidden input (same as camera capture)
                photoDataInput.value = base64;
                checkFormCompletion();

                showUploadStatus('success', '<i class="fas fa-check-circle"></i> Photo uploaded successfully!');
            };
            reader.readAsDataURL(file);
        });

        function clearUploadedPhoto() {
            document.getElementById('uploadPreview').style.display   = 'none';
            document.getElementById('uploadPreview').src             = '';
            document.getElementById('uploadPlaceholder').style.display = 'flex';
            document.getElementById('clearUpload').style.display     = 'none';
            document.getElementById('selfieUploadInput').value       = '';
            document.getElementById('uploadStatus').style.display    = 'none';
            photoDataInput.value = '';
            checkFormCompletion();
        }

        function showUploadStatus(type, message) {
            const el = document.getElementById('uploadStatus');
            el.style.display = 'block';
            el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
            el.style.color      = type === 'success' ? '#065f46' : '#991b1b';
            el.style.border     = '1px solid ' + (type === 'success' ? '#6ee7b7' : '#fca5a5');
            el.innerHTML        = message;
        }

        // Signature canvas functionality
        const signatureCanvas = document.getElementById('signatureCanvas');
        const ctx = signatureCanvas.getContext('2d');
        const signatureDataInput = document.getElementById('signature_data');
        const clearSignatureBtn = document.getElementById('clearSignature');
        
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // Resize canvas to match display size
        function resizeCanvas() {
            const rect = signatureCanvas.getBoundingClientRect();
            signatureCanvas.width = signatureCanvas.offsetWidth;
            signatureCanvas.height = signatureCanvas.offsetHeight;
        }
        resizeCanvas();

        signatureCanvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const rect = signatureCanvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        });

        signatureCanvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            
            const rect = signatureCanvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            
            lastX = x;
            lastY = y;
        });

        signatureCanvas.addEventListener('mouseup', () => {
            isDrawing = false;
            signatureDataInput.value = signatureCanvas.toDataURL('image/png');
            checkFormCompletion();
        });

        signatureCanvas.addEventListener('mouseout', () => {
            isDrawing = false;
        });

        // Touch support for mobile
        signatureCanvas.addEventListener('touchstart', (e) => {
            isDrawing = true;
            const rect = signatureCanvas.getBoundingClientRect();
            const touch = e.touches[0];
            lastX = touch.clientX - rect.left;
            lastY = touch.clientY - rect.top;
            e.preventDefault();
        });

        signatureCanvas.addEventListener('touchmove', (e) => {
            if (!isDrawing) return;
            
            const rect = signatureCanvas.getBoundingClientRect();
            const touch = e.touches[0];
            const x = touch.clientX - rect.left;
            const y = touch.clientY - rect.top;
            
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            
            lastX = x;
            lastY = y;
            e.preventDefault();
        });

        signatureCanvas.addEventListener('touchend', () => {
            isDrawing = false;
            signatureDataInput.value = signatureCanvas.toDataURL('image/png');
            checkFormCompletion();
        });

        // Clear signature
        clearSignatureBtn.addEventListener('click', (e) => {
            e.preventDefault();
            ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            signatureDataInput.value = '';
            checkFormCompletion();
        });

        // Calculate age from date of birth
        function calculateAge() {
            const dobInput = document.getElementById('date_of_birth');
            const ageInput = document.getElementById('age');
            
            if (dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                ageInput.value = age;
            } else {
                ageInput.value = '';
            }
            
            checkFormCompletion();
        }

        // Toggle signature option
        function toggleSignatureOption() {
            const skipCheckbox = document.getElementById('skipSignature');
            const digitalSection = document.getElementById('digitalSignatureSection');
            const signatureCanvas = document.getElementById('signatureCanvas');
            const signatureDataInput = document.getElementById('signature_data');
            
            if (skipCheckbox.checked) {
                digitalSection.style.opacity = '0.5';
                digitalSection.style.pointerEvents = 'none';
                signatureDataInput.value = 'skipped';
            } else {
                digitalSection.style.opacity = '1';
                digitalSection.style.pointerEvents = 'auto';
                signatureDataInput.value = '';
            }
            checkFormCompletion();
        }
        
        // Check if form is complete
        function checkFormCompletion() {
            const hasName = document.getElementById('patient_name').value.trim() !== '';
            const hasDOB = document.getElementById('date_of_birth').value !== '';
            const hasAge = document.getElementById('age').value !== '';
            const hasSex = document.querySelector('input[name="sex"]:checked') !== null;
            const serviceCheckboxes = document.querySelectorAll('input[name="service_type[]"]:checked');
            const hasService = serviceCheckboxes.length > 0;
            const hasDate = document.getElementById('form_date').value !== '';
            const hasPhoto = photoDataInput.value !== '';
            const skipSignature = document.getElementById('skipSignature').checked;
            const hasSignature = skipSignature || signatureDataInput.value !== '';
            
            submitBtn.disabled = !(hasName && hasDOB && hasAge && hasSex && hasService && hasDate && hasPhoto && hasSignature);
        }

        // Enable/disable submit button as user fills form
        document.getElementById('patient_name').addEventListener('input', checkFormCompletion);
        document.getElementById('date_of_birth').addEventListener('change', calculateAge);
        document.querySelectorAll('input[name="sex"]').forEach(radio => {
            radio.addEventListener('change', checkFormCompletion);
        });
        document.querySelectorAll('input[name="service_type[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', checkFormCompletion);
        });
        document.getElementById('form_date').addEventListener('change', checkFormCompletion);
    </script>
</body>
</html>
