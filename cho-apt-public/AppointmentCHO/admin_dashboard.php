<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cho_consent_system';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get dashboard statistics
$stats = [];

// Today's appointments
$today_sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND status NOT IN ('cancelled', 'no_show')";
$today_result = $conn->query($today_sql);
$stats['today'] = $today_result->fetch_assoc()['count'];

// This week's appointments
$week_sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status NOT IN ('cancelled', 'no_show')";
$week_result = $conn->query($week_sql);
$stats['week'] = $week_result->fetch_assoc()['count'];

// Rescheduled appointments needing notification
$reschedule_sql = "SELECT COUNT(*) as count FROM appointments WHERE status = 'rescheduled' AND notification_sent = 0";
$reschedule_result = $conn->query($reschedule_sql);
$stats['pending_notifications'] = $reschedule_result->fetch_assoc()['count'];

// Upcoming holidays
$holiday_sql = "SELECT COUNT(*) as count FROM holidays WHERE holiday_date >= CURDATE() ORDER BY holiday_date ASC LIMIT 5";
$holiday_result = $conn->query($holiday_sql);
$stats['upcoming_holidays'] = $holiday_result->fetch_assoc()['count'];

// Get recent rescheduled appointments
$recent_sql = "SELECT * FROM appointments 
               WHERE status = 'rescheduled' 
               ORDER BY updated_at DESC 
               LIMIT 5";
$recent_result = $conn->query($recent_sql);

// Get upcoming holidays
$upcoming_holidays_sql = "SELECT * FROM holidays WHERE holiday_date >= CURDATE() ORDER BY holiday_date ASC LIMIT 3";
$upcoming_holidays_result = $conn->query($upcoming_holidays_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CHO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        .btn-custom {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
        }
        .btn-warning-custom {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
        }
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .reschedule-item {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .holiday-item {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        .quick-action {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-12 text-center">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
                    <p class="mb-0">Bacolod City Health Office - Appointment Management System</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-primary"><?= $stats['today'] ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-success"><?= $stats['week'] ?></div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?= $stats['pending_notifications'] ?></div>
                    <div class="stat-label">Pending Notifications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?= $stats['upcoming_holidays'] ?></div>
                    <div class="stat-label">Upcoming Holidays</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 quick-action">
                                <a href="../AppointmentCHO/slot_management.php" class="btn btn-info btn-custom">
                                    <i class="fas fa-cogs me-2"></i>Manage Slot Capacity
                                </a>
                            </div>
                            <div class="col-md-3 quick-action">
                                <a href="holiday_reschedule_management.php" class="btn btn-warning btn-custom">
                                    <i class="fas fa-calendar-times me-2"></i>Manage Holidays
                                </a>
                            </div>
                            <div class="col-md-3 quick-action">
                                <a href="notify_rescheduled_clients.php" class="btn btn-primary btn-custom">
                                    <i class="fas fa-bell me-2"></i>Send Notifications
                                </a>
                            </div>
                            <div class="col-md-3 quick-action">
                                <a href="verify_appointment.php" class="btn btn-success btn-custom">
                                    <i class="fas fa-qrcode me-2"></i>Verify Appointments
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6 quick-action">
                                <a href="public_booking.php" class="btn btn-primary btn-custom">
                                    <i class="fas fa-calendar-plus me-2"></i>View Calendar
                                </a>
                            </div>
                            <div class="col-md-6 quick-action">
                                <a href="../AppointmentCHO/create_patient_enrollment.php" class="btn btn-success btn-custom">
                                    <i class="fas fa-file-medical me-2"></i>Patient ITR (Online Bookings)
                                </a>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Reschedules -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Recent Reschedules</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_result->num_rows > 0): ?>
                            <?php while ($appointment = $recent_result->fetch_assoc()): ?>
                                <?php 
                                // Parse reschedule information from notes
                                $notes = $appointment['notes'];
                                preg_match('/RESCHEDULED: Originally scheduled for (\d{4}-\d{2}-\d{2})\. Reason: (.+)\. Rescheduled to (\d{4}-\d{2}-\d{2})/', $notes, $matches);
                                
                                $original_date = $matches[1] ?? '';
                                $reason = $matches[2] ?? '';
                                $new_date = $matches[3] ?? '';
                                ?>
                                <div class="reschedule-item">
                                    <h6><strong><?= htmlspecialchars($appointment['client_name']) ?></strong></h6>
                                    <p class="mb-1">
                                        <small>
                                            <?= date('M j', strtotime($original_date)) ?> → <?= date('M j', strtotime($new_date)) ?> | 
                                            <?= $appointment['notification_sent'] ? '✓ Notified' : '⏳ Pending' ?>
                                        </small>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No recent reschedules.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Holidays -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Holidays</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($upcoming_holidays_result->num_rows > 0): ?>
                            <?php while ($holiday = $upcoming_holidays_result->fetch_assoc()): ?>
                                <div class="holiday-item">
                                    <h6><strong><?= date('F j, Y', strtotime($holiday['holiday_date'])) ?></strong></h6>
                                    <p class="mb-1">
                                        <small>
                                            <?= htmlspecialchars($holiday['reason']) ?><br>
                                            Reschedule to: <?= date('F j, Y', strtotime($holiday['reschedule_to'])) ?>
                                        </small>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No upcoming holidays.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
