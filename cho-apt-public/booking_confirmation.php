<?php
require_once 'database.php';
require_once 'models/BookingModel.php';

$conn = getDBConnection();
$bookingModel = new BookingModel($conn);

// Get and validate reference number
$reference_number = $_GET['ref'] ?? '';

if (empty($reference_number)) {
    header('Location: public_booking.php');
    exit;
}

// Fetch appointment using the Model
$appointment = $bookingModel->getAppointmentByRef($reference_number);

if (!$appointment) {
    $error = 'Invalid reference number. Appointment not found.';
} else {
    // Generate QR code data
    $qr_data = "CHO Appointment Reference: " . $reference_number . "\n";
    $qr_data .= "Name: " . $appointment['client_name'] . "\n";
    $qr_data .= "Date: " . date('F j, Y', strtotime($appointment['appointment_date'])) . "\n";
    $qr_data .= "Time: WHOLE DAY (8:00 AM - 5:00 PM)\n";
    $qr_data .= "Purpose: " . $appointment['purpose'] . "\n";
    $qr_data .= "Contact: " . $appointment['contact_number'];
    
    // Update QR code data in database using the Model
    $bookingModel->updateQRCodeData($appointment['id'], $qr_data);
}

// Always close connection at the very end
closeDBConnection();
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
    <link rel="stylesheet" href="assets/css/booking_confirmation.css">
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
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
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
                    <small class="text-muted d-block mt-2">Please present this reference number when you visit the office</small>
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
                    <a href="public_booking.php" class="btn btn-outline-primary btn-custom" style="background: white; border: 2px solid #0d6efd; color: #0d6efd;">
                        <i class="fas fa-plus me-2"></i>Book Another
                    </a>
                    <a href="index.php" class="btn btn-custom btn-secondary">
                        <i class="fas fa-home me-2"></i>Home
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($appointment)): ?>
        <script>
            window.qrData = <?php echo json_encode($qr_data); ?>;
            window.refNumber = <?php echo json_encode($reference_number); ?>;
        </script>
        <script src="assets/js/booking_confirmation.js"></script>
    <?php endif; ?>
</body>
</html>