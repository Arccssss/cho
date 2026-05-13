<?php
require_once 'database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Start session
session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../../cho-apt/CHO/index.php?role=user');
}

// Only regular users can access this page
if (isAdmin()) {
    redirect('../../cho-apt/CHO/admin_dashboard.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $time_period = sanitize($_POST['time_period']);
    $purpose = sanitize($_POST['purpose']);
    $service_type = sanitize($_POST['service_type']);
    $notes = sanitize($_POST['notes']);
    
    // Combine name fields for backward compatibility
    $patient_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name . ' ' . $suffix);
    $patient_name = preg_replace('/\s+/', ' ', $patient_name); // Remove extra spaces
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($contact_number) || empty($appointment_date) || empty($time_period) || empty($purpose)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($appointment_date) <= strtotime(date('Y-m-d'))) {
        $error = 'Appointment date must be in the future.';
    } else {
        // Check if the time period is available
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND time_period = ? AND status NOT IN ('cancelled', 'no_show')");
        $stmt->bind_param("ss", $appointment_date, $time_period);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // Get max appointments for this time period
        $day_of_week = strtolower(date('l', strtotime($appointment_date)));
        $slot_stmt = $conn->prepare("SELECT max_appointments FROM appointment_time_slots WHERE day_of_week = ? AND time_period = ? AND is_active = TRUE");
        $slot_stmt->bind_param("ss", $day_of_week, $time_period);
        $slot_stmt->execute();
        $slot_result = $slot_stmt->get_result();
        $max_appointments = 50; // default
        
        if ($slot_result->num_rows > 0) {
            $slot_row = $slot_result->fetch_assoc();
            $max_appointments = $slot_row['max_appointments'];
        }
        
        if ($row['count'] >= $max_appointments) {
            $error = 'This time period is already fully booked. Please choose a different time period.';
        } else {
            // Set default time based on period
            $appointment_time = ($time_period === 'AM') ? '09:00:00' : '14:00:00';
            
            // Insert appointment with new fields
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, philhealth_no, last_name, first_name, middle_name, suffix, date_of_birth, sex, civil_status, barangay, contact_number, email, appointment_date, appointment_time, time_period, purpose, service_type, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssssssss", $_SESSION['user_id'], $philhealth_no, $last_name, $first_name, $middle_name, $suffix, $date_of_birth, $sex, $civil_status, $barangay, $contact_number, $email, $appointment_date, $appointment_time, $time_period, $purpose, $service_type, $notes, 'pending');
            
            if ($stmt->execute()) {
                $success = 'Appointment booked successfully! Your appointment is confirmed.';
                // Clear form
                $_POST = array();
            } else {
                $error = 'Failed to book appointment. Please try again.';
            }
            
            $stmt->close();
        }
        $conn->close();
    }
}

// Get available time periods
function getAvailableTimeSlots($date) {
    $conn = getDBConnection();
    $day_of_week = strtolower(date('l', strtotime($date)));
    
    // Get time periods for the day
    $stmt = $conn->prepare("SELECT time_period, max_appointments FROM appointment_time_slots WHERE day_of_week = ? AND is_active = TRUE ORDER BY time_period");
    $stmt->bind_param("s", $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $time_slots = array();
    while ($row = $result->fetch_assoc()) {
        $time_period = $row['time_period'];
        $max_appointments = $row['max_appointments'];
        
        // Check how many appointments are already booked for this period
        $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ? AND time_period = ? AND status NOT IN ('cancelled', 'no_show')");
        $count_stmt->bind_param("ss", $date, $time_period);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        
        if ($count_row['count'] < $max_appointments) {
            $time_slots[] = array(
                'time' => $time_period,
                'available' => $max_appointments - $count_row['count']
            );
        }
        
        $count_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    
    return $time_slots;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - CHO System</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo-box {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
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
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.12);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 107, 53, 0.1);
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
        .modern-navbar {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.25);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .navbar-left {
            padding: 20px 30px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-weight: 700;
            font-size: 18px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-brand i {
            font-size: 24px;
        }

        .navbar-center {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 10px;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .navbar-right {
            padding: 20px 30px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Appointment Form Container */
        .appointment-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
        }

        .form-header {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .form-header-content {
            position: relative;
            z-index: 5;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 900;
            margin: 0;
            letter-spacing: 1.2px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .form-body {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 4px solid #FF6B35;
        }

        .form-section h3 {
            color: #FF6B35;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #FF6B35;
            font-size: 12px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .time-slot:hover {
            border-color: #FF6B35;
            background: #FFF9F5;
        }

        .time-slot.selected {
            border-color: #FF6B35;
            background: #FF6B35;
            color: white;
        }

        .time-slot.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .time-slot .time {
            font-weight: 600;
            font-size: 14px;
        }

        .time-slot .available {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .time-slot.selected .available {
            color: rgba(255, 255, 255, 0.9);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #FEE2E2;
            color: #7F1D1D;
            border: 2px solid #ef4444;
        }

        .form-actions {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            margin: 0 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .navbar-nav {
                flex-direction: column;
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Radio Button Styles */
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #FF6B35;
        }

        .radio-item label {
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin: 0;
            color: #333;
        }

        .radio-item input[type="radio"]:checked + label {
            color: #FF6B35;
            font-weight: 600;
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
                    <p>Patient Portal - Appointment Booking</p>
                </div>
            </div>
            <div class="header-right">
                <p>Welcome,</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="modern-navbar">
            <div class="navbar-left">
                <a href="../../cho-apt/CHO/user_dashboard.php" class="nav-brand">
                    <i class="fas fa-heartbeat"></i>
                    <span>Patient Portal</span>
                </a>
            </div>
            <div class="navbar-center">
                <ul class="navbar-nav">
                    <li><a href="../../cho-apt/CHO/user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="book_appointment.php" class="active"><i class="fas fa-calendar-plus"></i> Book Appointment</a></li>
                    <li><a href="appointment_calendar.php"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
                    <li><a href="my_appointments.php"><i class="fas fa-list"></i> My Appointments</a></li>
                    <li><a href="../../cho-apt/CHO/create_consent_form.php"><i class="fas fa-file-medical"></i> Consent Form</a></li>
                    <li><a href="../../cho-apt/CHO/patient_medical_record.php"><i class="fas fa-notes-medical"></i> Medical Record</a></li>
                </ul>
            </div>
            <div class="navbar-right">
                <a href="../../cho-apt/CHO/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Appointment Booking Form -->
        <div class="appointment-container">
            <div class="form-header">
                <div class="form-header-content">
                    <h1>Book Your Appointment</h1>
                    <div class="subtitle">BACOLOD CITY HEALTH OFFICE</div>
                </div>
            </div>

            <div class="form-body">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="appointmentForm">
                    <!-- Patient Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Patient Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="philhealth_no"><i class="fas fa-id-card"></i> PhilHealth No.</label>
                                <input type="text" id="philhealth_no" name="philhealth_no" class="form-control" placeholder="Enter PhilHealth Number" value="<?php echo isset($_POST['philhealth_no']) ? htmlspecialchars($_POST['philhealth_no']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name"><i class="fas fa-user"></i> Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Enter Last Name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Enter First Name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="middle_name"><i class="fas fa-user"></i> Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" placeholder="Enter Middle Name" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="suffix"><i class="fas fa-tag"></i> Suffix</label>
                                <select id="suffix" name="suffix" class="form-control">
                                    <option value="">Select Suffix</option>
                                    <option value="Jr." <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                                    <option value="Sr." <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                                    <option value="III" <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'III') ? 'selected' : ''; ?>>III</option>
                                    <option value="IV" <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'IV') ? 'selected' : ''; ?>>IV</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date_of_birth"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-venus-mars"></i> Sex</label>
                                <div class="radio-group">
                                    <div class="radio-item">
                                        <input type="radio" id="male" name="sex" value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'checked' : ''; ?>>
                                        <label for="male">Male</label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" id="female" name="sex" value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'checked' : ''; ?>>
                                        <label for="female">Female</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="civil_status"><i class="fas fa-heart"></i> Civil Status</label>
                                <select id="civil_status" name="civil_status" class="form-control">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                    <option value="Widowed" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="Separated" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                                    <option value="Divorced" <?php echo (isset($_POST['civil_status']) && $_POST['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="barangay"><i class="fas fa-map-marker-alt"></i> Barangay</label>
                                <select id="barangay" name="barangay" class="form-control">
                                    <option value="">Select Barangay</option>
                                    <option value="Alangilan" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Alangilan') ? 'selected' : ''; ?>>Alangilan</option>
                                    <option value="Banago" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Banago') ? 'selected' : ''; ?>>Banago</option>
                                    <option value="Bata" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Bata') ? 'selected' : ''; ?>>Bata</option>
                                    <option value="Cabug" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Cabug') ? 'selected' : ''; ?>>Cabug</option>
                                    <option value="Estefania" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Estefania') ? 'selected' : ''; ?>>Estefania</option>
                                    <option value="Felisa" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Felisa') ? 'selected' : ''; ?>>Felisa</option>
                                    <option value="Granada" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Granada') ? 'selected' : ''; ?>>Granada</option>
                                    <option value="Handumanan" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Handumanan') ? 'selected' : ''; ?>>Handumanan</option>
                                    <option value="Mandalagan" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Mandalagan') ? 'selected' : ''; ?>>Mandalagan</option>
                                    <option value="Mansilingan" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Mansilingan') ? 'selected' : ''; ?>>Mansilingan</option>
                                    <option value="Montevista" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Montevista') ? 'selected' : ''; ?>>Montevista</option>
                                    <option value="Pahanocoy" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Pahanocoy') ? 'selected' : ''; ?>>Pahanocoy</option>
                                    <option value="Punta Taytay" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Punta Taytay') ? 'selected' : ''; ?>>Punta Taytay</option>
                                    <option value="Singcang-Airport" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Singcang-Airport') ? 'selected' : ''; ?>>Singcang-Airport</option>
                                    <option value="Sum-ag" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Sum-ag') ? 'selected' : ''; ?>>Sum-ag</option>
                                    <option value="Taculing" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Taculing') ? 'selected' : ''; ?>>Taculing</option>
                                    <option value="Tangub" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Tangub') ? 'selected' : ''; ?>>Tangub</option>
                                    <option value="Villamonte" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Villamonte') ? 'selected' : ''; ?>>Villamonte</option>
                                    <option value="Vista Alegre" <?php echo (isset($_POST['barangay']) && $_POST['barangay'] == 'Vista Alegre') ? 'selected' : ''; ?>>Vista Alegre</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact_number"><i class="fas fa-phone"></i> Contact Number *</label>
                                <input type="tel" id="contact_number" name="contact_number" class="form-control" placeholder="Enter Contact Number" required value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="Enter Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> Appointment Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="appointment_date"><i class="fas fa-calendar"></i> Preferred Date</label>
                                <input type="date" id="appointment_date" name="appointment_date" class="form-control" required value="<?php echo isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="purpose"><i class="fas fa-bullseye"></i> Purpose of Visit</label>
                                <select id="purpose" name="purpose" class="form-control" required>
                                    <option value="">Select Purpose</option>
                                    <option value="Consultation" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Consultation') ? 'selected' : ''; ?>>General Consultation</option>
                                    <option value="Follow-up" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Follow-up') ? 'selected' : ''; ?>>Follow-up Check-up</option>
                                    <option value="Vaccination" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                                    <option value="Laboratory" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Laboratory') ? 'selected' : ''; ?>>Laboratory Tests</option>
                                    <option value="Dental" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Dental') ? 'selected' : ''; ?>>Dental Services</option>
                                    <option value="Maternal" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Maternal') ? 'selected' : ''; ?>>Maternal Care</option>
                                    <option value="Child" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Child') ? 'selected' : ''; ?>>Child Care</option>
                                    <option value="Other" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="service_type"><i class="fas fa-stethoscope"></i> Service Type</label>
                                <select id="service_type" name="service_type" class="form-control">
                                    <option value="">Select Service Type</option>
                                    <option value="ABTC" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'ABTC') ? 'selected' : ''; ?>>ABTC (Anti-Rabies)</option>
                                    <option value="Medical" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Medical') ? 'selected' : ''; ?>>Medical Consultation</option>
                                    <option value="Dental" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Dental') ? 'selected' : ''; ?>>Dental Services</option>
                                    <option value="Laboratory" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Laboratory') ? 'selected' : ''; ?>>Laboratory</option>
                                    <option value="Maternal" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Maternal') ? 'selected' : ''; ?>>Maternal Care</option>
                                    <option value="Child" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Child') ? 'selected' : ''; ?>>Child Care</option>
                                    <option value="Other" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Time Period Selection -->
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Preferred Time Period</label>
                            <div id="timeSlots" class="time-slots">
                                <div class="time-slot disabled">
                                    <div class="time">Select a date first</div>
                                    <div class="available">--</div>
                                </div>
                            </div>
                            <input type="hidden" id="time_period" name="time_period" required>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="form-section">
                        <h3><i class="fas fa-notes-medical"></i> Additional Information</h3>
                        <div class="form-group">
                            <label for="notes"><i class="fas fa-comment"></i> Additional Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" placeholder="Please provide any additional information or special requirements..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Book Appointment
                        </button>
                        <button type="button" onclick="window.location.href='../../cho-apt/CHO/user_dashboard.php'" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];

        // Handle date selection and load time slots
        document.getElementById('appointment_date').addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                loadTimeSlots(selectedDate);
            }
        });

        function loadTimeSlots(date) {
            // Show loading state
            const timeSlotsContainer = document.getElementById('timeSlots');
            timeSlotsContainer.innerHTML = '<div class="time-slot disabled"><div class="time">Loading...</div></div>';

            // Make AJAX request to get available time slots
            fetch('get_time_slots.php?date=' + encodeURIComponent(date))
                .then(response => response.json())
                .then(data => {
                    displayTimeSlots(data);
                })
                .catch(error => {
                    console.error('Error loading time slots:', error);
                    timeSlotsContainer.innerHTML = '<div class="time-slot disabled"><div class="time">Error loading slots</div></div>';
                });
        }

        function displayTimeSlots(timeSlots) {
            const timeSlotsContainer = document.getElementById('timeSlots');
            
            if (timeSlots.length === 0) {
                timeSlotsContainer.innerHTML = '<div class="time-slot disabled"><div class="time">No available slots</div></div>';
                return;
            }

            timeSlotsContainer.innerHTML = '';
            
            timeSlots.forEach(slot => {
                const slotElement = document.createElement('div');
                slotElement.className = 'time-slot';
                slotElement.innerHTML = `
                    <div class="time">${slot.time}</div>
                    <div class="available">${slot.available} slots available</div>
                `;
                
                slotElement.addEventListener('click', function() {
                    // Remove previous selection
                    document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
                    
                    // Select this slot
                    this.classList.add('selected');
                    document.getElementById('time_period').value = slot.time;
                });
                
                timeSlotsContainer.appendChild(slotElement);
            });
        }

        // Form validation
        document.getElementById('appointmentForm').addEventListener('submit', function(e) {
            const timePeriod = document.getElementById('time_period').value;
            
            if (!timePeriod) {
                e.preventDefault();
                alert('Please select a preferred time period (AM or PM).');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
