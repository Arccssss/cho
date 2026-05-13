<?php
require_once 'database.php';

// Get database connection
$conn = getDBConnection();

// Get reference number from URL parameter
$reference_number = isset($_GET['ref']) ? $_GET['ref'] : '';

// Validate reference number
if (empty($reference_number)) {
    header('Location: public_booking.php');
    exit;
}

// Get appointment details using reference number
$sql = "SELECT * FROM appointments WHERE reference_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $reference_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Invalid reference number. Appointment not found.';
} else {
    $appointment = $result->fetch_assoc();
    
    // Generate QR code data
    $qr_data = "CHO Appointment Reference: " . $reference_number . "\n";
    $qr_data .= "Name: " . $appointment['client_name'] . "\n";
    $qr_data .= "Date: " . date('F j, Y', strtotime($appointment['appointment_date'])) . "\n";
    $qr_data .= "Time: WHOLE DAY (8:00 AM - 5:00 PM)\n";
    $qr_data .= "Purpose: " . $appointment['purpose'] . "\n";
    $qr_data .= "Contact: " . $appointment['contact_number'];
    
    // Update QR code data in database
    $update_sql = "UPDATE appointments SET qr_code_data = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $qr_data, $appointment['id']);
    $update_stmt->execute();
    $update_stmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - CHO Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }
        .main-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .logo-img {
            height: 60px;
            width: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .logo-text {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .logo-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        .confirmation-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            border: none;
            margin: 30px auto;
            max-width: 900px;
            position: relative;
            animation: slideInUp 0.8s ease-out;
        }
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .success-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 25px;
            animation: bounceIn 1s ease-out, pulse 2s infinite 1s;
            position: relative;
            z-index: 1;
        }
        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .reference-number {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 3px dashed #0d6efd;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            text-align: center;
            position: relative;
            animation: fadeIn 1s ease-out 0.5s both;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .ref-number-display {
            font-size: 32px;
            font-weight: bold;
            color: #0d6efd;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(13, 110, 253, 0.2);
            padding: 10px;
            background: rgba(13, 110, 253, 0.05);
            border-radius: 8px;
            display: inline-block;
        }
        .qr-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px;
            text-align: center;
            position: relative;
        }
        .qr-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #28a745, #20c997, #0d6efd);
            background-size: 300% 100%;
            animation: gradient 3s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .qr-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            margin: 20px 0;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .qr-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .qr-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #0d6efd, #28a745, #20c997);
            border-radius: 20px;
            z-index: -1;
        }
        #qrcode {
            margin: 20px 0;
        }
        .appointment-details {
            background: white;
            padding: 40px;
            position: relative;
        }
        .appointment-details h4 {
            color: #333;
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 24px;
            position: relative;
            padding-left: 40px;
        }
        .appointment-details h4::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd, #28a745);
            border-radius: 2px;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        .detail-item:hover {
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.05), transparent);
            padding-left: 15px;
            margin: 0 -15px;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 16px;
        }
        .detail-value {
            font-weight: 500;
            color: #333;
            font-size: 16px;
        }
        .action-buttons {
            padding: 40px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }
        .action-buttons::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0d6efd, #28a745, #20c997, #0d6efd);
            background-size: 300% 100%;
            animation: gradient 3s ease infinite;
        }
        .btn-custom {
            margin: 8px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 12px 24px;
            font-size: 16px;
            position: relative;
            overflow: hidden;
        }
        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-custom:hover::before {
            left: 100%;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #0946a0 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.4);
        }
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-success-custom:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        .instructions {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: none;
            border-radius: 15px;
            padding: 30px;
            margin: 25px 0;
            position: relative;
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.2);
        }
        .instructions::before {
            content: '⚠️';
            position: absolute;
            top: -15px;
            left: 20px;
            font-size: 30px;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .instructions h5 {
            color: #856404;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .instructions ul li {
            margin-bottom: 12px;
            position: relative;
            padding-left: 25px;
        }
        .instructions ul li::before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 1px;
            color: #28a745;
            font-weight: bold;
        }
        .print-only {
            display: none;
        }
        @media print {
            body { background: white; }
            .main-header { display: none; }
            .action-buttons { display: none; }
            .instructions { display: none; }
            .print-only { display: block; }
            .confirmation-card { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="logo-container">
                <img src="images/bcd.jpg" alt="CHO Logo" class="logo-img">
                <div>
                    <h1 class="logo-text">Bacolod City Health Office</h1>
                    <p class="logo-subtitle">Appointment Booking</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <div class="mt-3">
                    <a href="public_booking.php" class="btn btn-primary">Return to Booking</a>
                </div>
            </div>
        <?php else: ?>
            <div class="confirmation-card">
                <div class="success-header">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Appointment Confirmed!</h2>
                    <p class="mb-0">Your appointment has been successfully booked. Please save this confirmation.</p>
                </div>

                <div class="reference-number">
                    <h5><i class="fas fa-ticket-alt me-2"></i>Reference Number</h5>
                    <div class="ref-number-display"><?php echo htmlspecialchars($reference_number); ?></div>
                    <small class="text-muted">Please present this reference number when you visit the office</small>
                </div>

                <div class="qr-section">
                    <h4><i class="fas fa-qrcode me-2"></i>QR Code for Quick Check-in</h4>
                    <p class="text-muted">Scan this QR code at the health office for faster processing</p>
                    <div class="qr-container">
                        <div id="qrcode"></div>
                    </div>
                </div>

                <div class="appointment-details">
                    <h4 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Appointment Details</h4>
                    
                    <div class="detail-item">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['client_name']); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Date:</span>
                        <span class="detail-value"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Time:</span>
                        <span class="detail-value">WHOLE DAY (8:00 AM - 5:00 PM)</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Purpose:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['purpose']); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Contact Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($appointment['contact_number']); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><span class="badge bg-success">Confirmed</span></span>
                    </div>
                </div>

                <div class="instructions">
                    <h5><i class="fas fa-info-circle me-2"></i>Important Instructions</h5>
                    <ul class="mb-0">
                        <li>Please arrive 15 minutes before your scheduled appointment time</li>
                        <li>Bring a valid ID and this reference number (printed or digital)</li>
                        <li>The QR code can be scanned for quick check-in at the health office</li>
                        <li>If you need to reschedule, please call the CHO office at least 24 hours in advance</li>
                    </ul>
                </div>

                <div class="print-only text-center">
                    <hr>
                    <p><small>This is a printed confirmation of your appointment</small></p>
                </div>

                <div class="action-buttons">
                    <button onclick="window.print()" class="btn btn-success-custom btn-custom">
                        <i class="fas fa-print me-2"></i>Print Confirmation
                    </button>
                    <button onclick="downloadQR()" class="btn btn-primary-custom btn-custom">
                        <i class="fas fa-download me-2"></i>Download QR Code
                    </button>
                    <a href="public_booking.php?booking=success" class="btn btn-outline-primary btn-custom">
                        <i class="fas fa-plus me-2"></i>Book Another Appointment
                    </a>
                    <a href="index.php" class="btn btn-custom btn-secondary">
                        <i class="fas fa-home me-2"></i>Return to Home
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isset($appointment)): ?>
            // Generate QR Code
            document.addEventListener('DOMContentLoaded', function() {
                var qrData = <?php echo json_encode($qr_data); ?>;
                var qrcode = new QRCode(document.getElementById("qrcode"), {
                    text: qrData,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            });

            // Download QR Code function
            function downloadQR() {
                var canvas = document.querySelector('#qrcode canvas');
                if (canvas) {
                    var link = document.createElement('a');
                    link.download = 'CHO_Appointment_<?php echo $reference_number; ?>.png';
                    link.href = canvas.toDataURL();
                    link.click();
                }
            }
        <?php endif; ?>
    </script>
</body>
</html>
