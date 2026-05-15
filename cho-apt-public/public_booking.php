<?php
session_start();
require_once 'database.php';
require_once 'models/BookingModel.php';

$conn = getDBConnection();
$bookingModel = new BookingModel($conn);

$error = '';

// If no booking info in session, redirect to personal info first
if (!isset($_SESSION['booking_info'])) {
    header('Location: personal_info.php');
    exit;
}

// Handle date selection — insert booking and redirect to confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_date'])) {
    $info = $_SESSION['booking_info'];
    $appointment_date = $_POST['selected_date'];

    try {
        // 1. Check availability using the Model
        $availability = $bookingModel->isDateAvailable($appointment_date);
        
        if (!$availability['available']) {
            $error = $availability['error'];
        } else {
            // 2. Create booking using the Model
            $reference_number = $bookingModel->createBooking($info, $appointment_date);
            
            // Clear session and redirect
            unset($_SESSION['booking_info']);
            header('Location: booking_confirmation.php?ref=' . urlencode($reference_number));
            exit;
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHO Appointment Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/public_booking.css">
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

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Select Your Appointment Date</h3>
                        <a href="personal_info.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                    </div>
                    <div class="card-body p-4">

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px;">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php $info = $_SESSION['booking_info'] ?? []; ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 p-3" style="background:#d1f5e0;border-radius:12px;border-left:4px solid #28a745;">
                            <div>
                                <i class="fas fa-user-check me-2 text-success"></i>
                                <strong><?= htmlspecialchars(trim(($info['first_name'] ?? '') . ' ' . ($info['last_name'] ?? ''))) ?></strong>
                                <span class="text-muted ms-2" style="font-size:0.85rem;">— <?= htmlspecialchars($info['purpose'] ?? '') ?></span>
                            </div>
                            <a href="personal_info.php" style="font-size:0.82rem;color:#155724;font-weight:600;text-decoration:none;">
                                <i class="fas fa-edit me-1"></i>Edit Info
                            </a>
                        </div>

                        <div class="calendar-container mb-2">
                            <div class="calendar-header">
                                <div class="calendar-nav">
                                    <button type="button" class="btn btn-light btn-sm" onclick="changeMonth(-1)">&#8249;</button>
                                    <h4 id="current-month"></h4>
                                    <button type="button" class="btn btn-light btn-sm" onclick="changeMonth(1)">&#8250;</button>
                                </div>
                            </div>
                            <div class="calendar-grid" id="calendar-grid"></div>
                            <div class="calendar-legend">
                                <div class="legend-item"><div class="legend-color" style="background:linear-gradient(135deg,#d4edda,#c3e6cb);border:1px solid #c3e6cb;"></div><span>Available</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#f8d7da;border:1px solid #f5c6cb;"></div><span>Fully Booked</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:linear-gradient(135deg,#0d6efd,#0a58ca);"></div><span>Selected</span></div>
                                <div class="legend-item"><div class="legend-color" style="border:2px solid #0d6efd;background:#fff;"></div><span>Today</span></div>
                                <div class="legend-item"><div class="legend-color" style="background:#f5f5f5;border:1px solid #ddd;"></div><span>Weekend / Unavailable</span></div>
                            </div>
                        </div>

                        <div class="booking-panel" id="booking-panel">
                            <h5><i class="fas fa-calendar-day me-2"></i>Selected Appointment Date</h5>
                            <div class="date-display" id="date-display">—</div>
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <div class="badge bg-success fs-6 px-3 py-2">
                                    <i class="fas fa-clock me-1"></i>Whole Day (8:00 AM – 5:00 PM)
                                </div>
                            </div>
                            <form method="POST" id="date-form">
                                <input type="hidden" name="selected_date" id="selected-date-input">
                            </form>
                            <button type="button" class="btn-proceed" onclick="proceedToBooking()">
                                <i class="fas fa-calendar-check me-2"></i>Confirm Appointment
                            </button>
                        </div>

                    </div>
                </div>

                <div class="card shadow-lg">
                    <div class="card-header bg-gradient text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Important Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Booking is free — no account needed</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>CHO staff will confirm your appointment</li>
                                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Please provide accurate contact information</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-clock text-primary me-2"></i>Arrive 15 minutes before your appointment</li>
                                    <li class="mb-2"><i class="fas fa-phone-alt text-primary me-2"></i>For inquiries: Contact CHO Office</li>
                                    <li class="mb-2"><i class="fas fa-map-marker-alt text-primary me-2"></i>Visit us at City Health Office</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>window.serverTodayStr = '<?= date('Y-m-d') ?>';</script>
    <script src="assets/js/public_booking.js"></script>
</body>
</html>