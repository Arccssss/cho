<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?role=admin');
}

// Get selected date and time from URL parameters
$selected_date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$selected_time = isset($_GET['time']) ? sanitize($_GET['time']) : '';

// Validate date and time
if (empty($selected_date) || empty($selected_time)) {
    setFlashMessage('danger', 'Please select a date and time for your appointment.');
    redirect('public_booking.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    
    try {
        // Collect form data
        $philhealth_no = sanitize($_POST['philhealth_no']);
        $last_name = sanitize($_POST['last_name']);
        $first_name = sanitize($_POST['first_name']);
        $middle_name = sanitize($_POST['middle_name']);
        $suffix = sanitize($_POST['suffix']);
        $date_of_birth = sanitize($_POST['date_of_birth']);
        $sex = sanitize($_POST['sex']);
        $civil_status = sanitize($_POST['civil_status']);
        $barangay = sanitize($_POST['barangay']);
        $contact_number = sanitize($_POST['contact_number']);
        $email = sanitize($_POST['email']);
        $appointment_date = sanitize($_POST['appointment_date']);
        $appointment_time = sanitize($_POST['appointment_time']);
        $time_period = sanitize($_POST['time_period']);
        $purpose = sanitize($_POST['purpose']);
        $notes = sanitize($_POST['notes']);
        
        // Create client name
        $client_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name . ' ' . $suffix);
        
        // Check if appointment slot is still available
        $check_sql = "SELECT COUNT(*) as count FROM appointments 
                     WHERE appointment_date = ? AND appointment_time = ? 
                     AND status NOT IN ('cancelled', 'no_show', 'completed')";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $appointment_date, $appointment_time);
        $check_stmt->execute();
        $current_count = $check_stmt->get_result()->fetch_assoc()['count'];
        
        $max_appointments = 50; // Maximum per time slot
        
        if ($current_count >= $max_appointments) {
            $error = 'Sorry, this time slot is already fully booked. Please select a different time.';
        } else {
            // Insert appointment with minimal required fields first
            $sql = "INSERT INTO appointments (client_name, contact_number, appointment_date, appointment_time, time_period, purpose, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $status = 'pending';
            $stmt->bind_param("sssssss", $client_name, $contact_number, $appointment_date, $appointment_time, $time_period, $purpose, $status);
            
            if ($stmt->execute()) {
                $appointment_id = $stmt->insert_id;
                
                // Generate unique reference number
                $reference_number = 'CHO-' . date('Y') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
                
                // Update appointment with all additional fields
                $update_sql = "UPDATE appointments SET 
                              philhealth_no = ?, 
                              last_name = ?, 
                              first_name = ?, 
                              middle_name = ?, 
                              suffix = ?, 
                              date_of_birth = ?, 
                              sex = ?, 
                              civil_status = ?, 
                              barangay = ?, 
                              email = ?, 
                              notes = ?,
                              reference_number = ?,
                              user_id = ?
                              WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $user_id = $_SESSION['user_id'];
                $update_stmt->bind_param("sssssssssssssi", $philhealth_no, $last_name, $first_name, $middle_name, $suffix, $date_of_birth, $sex, $civil_status, $barangay, $email, $notes, $reference_number, $user_id, $appointment_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                setFlashMessage('success', 'Appointment booked successfully! Reference: ' . $reference_number);
                redirect('booking_confirmation.php?ref=' . urlencode($reference_number));
            } else {
                $error = 'Error booking appointment. Please try again.';
            }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information - CHO Appointment</title>
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
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            margin: 30px auto;
            max-width: 900px;
        }
        .appointment-summary {
            background: linear-gradient(135deg, #e7f3ff 0%, #d1e9ff 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #0d6efd;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0a58ca 0%, #0946a0 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-hospital me-2"></i>CHO Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="manage_appointments.php">
                    <i class="fas fa-calendar-check me-1"></i>Appointments
                </a>
                <a class="nav-link" href="public_booking.php">
                    <i class="fas fa-calendar-plus me-1"></i>Book Appointment
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <a href="public_booking.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Calendar
                </a>
            </div>
            <div class="col-md-8">
                <div class="form-container">
                    <div class="appointment-summary">
                        <h4 class="mb-3"><i class="fas fa-calendar-check"></i> Selected Appointment</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($selected_date)) ?></p>
                                <p><strong>Time:</strong> <?= $selected_time === 'AM' ? 'MORNING (8:00 AM - 12:00 PM)' : 'AFTERNOON (1:00 PM - 5:00 PM)' ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Day:</strong> <?= date('l', strtotime($selected_date)) ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-success">Available</span></p>
                            </div>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="appointment_date" value="<?= htmlspecialchars($selected_date) ?>">
                        <input type="hidden" name="appointment_time" value="<?= htmlspecialchars($selected_time) ?>">
                        <input type="hidden" name="time_period" value="<?= htmlspecialchars($selected_time) ?>">
                        
                        <h4 class="mb-4"><i class="fas fa-user me-2"></i>Personal Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="philhealth_no" class="form-label">PhilHealth Number</label>
                                    <input type="text" class="form-control" id="philhealth_no" name="philhealth_no" maxlength="12">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="suffix" class="form-label">Suffix</label>
                                    <select class="form-select" id="suffix" name="suffix">
                                        <option value="">None</option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label required-field">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label required-field">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sex" class="form-label required-field">Sex</label>
                                    <select class="form-select" id="sex" name="sex" required>
                                        <option value="">Select Sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="civil_status" class="form-label required-field">Civil Status</label>
                                    <select class="form-select" id="civil_status" name="civil_status" required>
                                        <option value="">Select Civil Status</option>
                                        <option value="single">Single</option>
                                        <option value="married">Married</option>
                                        <option value="widowed">Widowed</option>
                                        <option value="separated">Separated</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="barangay" class="form-label required-field">Barangay</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label required-field">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="purpose" class="form-label required-field">Purpose of Visit</label>
                            <select class="form-select" id="purpose" name="purpose" required>
                                <option value="">Select Purpose</option>
                                <option value="Medical Consultation">Medical Consultation</option>
                                <option value="Animal Bite">Animal Bite</option>
                                <option value="Dental">Dental</option>
                                <option value="Prenatal Check-up">Prenatal Check-up</option>
                                <option value="Chest X-ray">Chest X-ray</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="TB">TB</option>
                                <option value="Drug Testing">Drug Testing</option>
                                <option value="Family Planning">Family Planning</option>
                                <option value="Social Hygiene">Social Hygiene</option>
                                 <option value="Others">Others</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information or special requests..."></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-check me-2"></i>Complete Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set maximum date for date of birth (must be at least 1 year old)
        document.getElementById('date_of_birth').max = new Date(Date.now() - 31536000000).toISOString().split('T')[0];
    </script>
</body>
</html>
