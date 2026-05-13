<?php
session_start();
require_once 'database.php';
$conn = getDBConnection();
$error = '';

// Handle form submission — save to session and redirect to calendar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $philhealth_no          = trim($_POST['philhealth_no'] ?? '');
    $last_name              = trim($_POST['last_name'] ?? '');
    $first_name             = trim($_POST['first_name'] ?? '');
    $middle_name            = trim($_POST['middle_name'] ?? '');
    $suffix                 = trim($_POST['suffix'] ?? '');
    $date_of_birth          = trim($_POST['date_of_birth'] ?? '');
    $sex                    = trim($_POST['sex'] ?? '');
    $civil_status           = trim($_POST['civil_status'] ?? '');
    $barangay               = trim($_POST['barangay'] ?? '');
    $contact_number         = trim($_POST['contact_number'] ?? '');
    $email                  = trim($_POST['email'] ?? '');
    $maiden_name            = trim($_POST['maiden_name'] ?? '');
    $spouse_name            = trim($_POST['spouse_name'] ?? '');
    $mother_name            = trim($_POST['mother_name'] ?? '');
    $educational_attainment = trim($_POST['educational_attainment'] ?? '');
    $employment_status      = trim($_POST['employment_status'] ?? '');
    $dswd_nhts              = trim($_POST['dswd_nhts'] ?? 'No');
    $four_ps_member         = trim($_POST['four_ps_member'] ?? 'No');
    $facility_household_no  = trim($_POST['facility_household_no'] ?? '');
    $household_no           = trim($_POST['household_no'] ?? '');
    $co_habitation          = trim($_POST['co_habitation'] ?? '');
    $family_member          = trim($_POST['family_member'] ?? '');
    $philhealth_member      = trim($_POST['philhealth_member'] ?? 'No');
    $philhealth_status_type = trim($_POST['philhealth_status_type'] ?? '');
    $primary_care_benefit   = trim($_POST['primary_care_benefit'] ?? 'No');
    $category               = trim($_POST['category'] ?? '');
    $mode_of_transaction    = trim($_POST['mode_of_transaction'] ?? 'Walk-in');
    $notes                  = trim($_POST['notes'] ?? '');
    $purposes               = isset($_POST['purpose']) ? $_POST['purpose'] : [];
    $purpose                = is_array($purposes) ? implode(', ', $purposes) : '';

    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($sex) || empty($civil_status) || empty($barangay) || empty($contact_number) || empty($purpose)) {
        $error = 'Please fill in all required fields, including at least one purpose of visit.';
    } elseif (strtolower($sex) === 'female' && strtolower($civil_status) === 'married' && empty($maiden_name)) {
        $error = 'Maiden name is required for married female clients.';
    } else {
        $_SESSION['booking_info'] = compact(
            'philhealth_no','last_name','first_name','middle_name','suffix',
            'date_of_birth','sex','civil_status','barangay','contact_number','email',
            'maiden_name','spouse_name','mother_name','educational_attainment',
            'employment_status','dswd_nhts','four_ps_member','facility_household_no',
            'household_no','co_habitation','family_member','philhealth_member',
            'philhealth_status_type','primary_care_benefit','category',
            'mode_of_transaction','notes','purpose'
        );
        header('Location: public_booking.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information - CHO Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dob-picker {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr;
            gap: 8px;
        }
        .dob-select {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 10px 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .dob-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.12);
            outline: none;
        }
        .dob-select.is-invalid { border-color: #dc3545; }
    </style>
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
        .purpose-checkboxes {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-top: 10px;
        }
        .purpose-checkboxes .form-check {
            margin-bottom: 12px;
            margin-right: 20px;
            display: inline-block;
        }
        .purpose-checkboxes .form-check-input {
            margin-top: 0.25rem;
        }
        .purpose-checkboxes .form-check-label {
            font-weight: 500;
            color: #495057;
            cursor: pointer;
        }
        .purpose-checkboxes .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
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
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                margin: 15px auto;
                border-radius: 15px;
            }
            
            .main-header {
                padding: 15px 0;
            }
            
            .logo-img {
                height: 40px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .logo-subtitle {
                font-size: 12px;
            }
            
            .appointment-summary {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .purpose-checkboxes {
                padding: 15px;
            }
            
            .purpose-checkboxes .form-check {
                display: block;
                margin-right: 0;
                margin-bottom: 8px;
            }
            
            .btn-back {
                padding: 8px 15px;
                font-size: 0.875rem;
                margin-bottom: 15px;
            }
            
            .btn-primary {
                padding: 10px 20px;
                font-size: 1rem;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .form-label {
                font-size: 0.875rem;
                margin-bottom: 6px;
            }
            
            .alert {
                padding: 12px;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 0 10px;
            }
            
            .form-container {
                padding: 15px;
                margin: 10px 0;
                border-radius: 10px;
            }
            
            .row {
                margin: 0;
            }
            
            .col-md-2, .col-md-4, .col-md-5, .col-md-6, .col-md-8 {
                padding: 0 5px;
                margin-bottom: 10px;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .alert-info {
                padding: 10px;
                font-size: 0.8rem;
            }
            
            .alert-info h6 {
                font-size: 0.9rem;
                margin-bottom: 8px;
            }
            
            .alert-info ul {
                font-size: 0.75rem;
                padding-left: 15px;
            }
        }
        
        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .form-control, .form-select {
                min-height: 44px;
            }
            
            .btn-primary {
                min-height: 44px;
                padding: 12px 20px;
            }
        }
        
        /* Small screen optimizations */
        @media (max-width: 480px) {
            .appointment-summary .row {
                flex-direction: column;
            }
            
            .appointment-summary .col-md-6 {
                margin-bottom: 10px;
            }
            
            .form-label {
                font-size: 0.8rem;
            }
        }

        /* ── Step Indicator ── */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .step.active .step-circle {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            box-shadow: 0 4px 12px rgba(13,110,253,0.35);
        }
        .step.completed .step-circle {
            background: #28a745;
            color: #fff;
        }
        .step-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #adb5bd;
            text-align: center;
            white-space: nowrap;
        }
        .step.active .step-label  { color: #0d6efd; }
        .step.completed .step-label { color: #28a745; }
        .step-line {
            flex: 1;
            height: 2px;
            background: #dee2e6;
            margin: 0 12px;
            margin-bottom: 22px;
            min-width: 60px;
            transition: background 0.3s;
        }
        .step-line.completed { background: #28a745; }

        /* Purpose items as cards */
        .purpose-item {
            display: inline-flex;
            align-items: center;
            margin: 0;
        }
        .purpose-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            background: #f8f9fa;
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
        }
        .purpose-item label {
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #dee2e6;
            background: #fff;
            width: 100%;
            font-weight: 500;
            color: #495057;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .purpose-item label:hover {
            border-color: #0d6efd;
            color: #0d6efd;
            background: #f0f6ff;
        }
        .purpose-item input[type="checkbox"] {
            flex-shrink: 0;
        }
        .purpose-item input[type="checkbox"]:checked + label {
            border-color: #0d6efd;
            background: #e8f0fe;
            color: #0d6efd;
        }

        /* ITR section titles */
        .itr-section-title {
            background: #1a1a2e;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 7px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="container">
            <div class="logo-container">
                <img src="images/bcd.jpg" alt="CHO Logo" class="logo-img">
                <div>
                    <h1 class="logo-text">BacolodCity Health Office</h1>
                    <p class="logo-subtitle">Appointment Booking</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="col-md-8">
                <div class="form-container">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="bookingForm">

                        <!-- Step Indicator -->
                        <div class="step-indicator mb-4">
                            <div class="step active" id="step-dot-1">
                                <div class="step-circle">1</div>
                                <div class="step-label">Purpose of Visit</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" id="step-dot-2">
                                <div class="step-circle">2</div>
                                <div class="step-label">Personal Information</div>
                            </div>
                        </div>

                        <!-- ── STEP 1: Purpose of Visit ── -->
                        <div id="step1">
                            <h4 class="mb-1"><i class="fas fa-clipboard-list me-2 text-primary"></i>Purpose of Visit</h4>
                            <p class="text-muted mb-4" style="font-size:0.9rem;">Select all services that apply to your visit.</p>

                            <div class="purpose-checkboxes">
                                <?php
                                $purposes_list = [
                                    'purpose-medical'    => 'Medical Consultation',
                                    'purpose-animal'     => 'Animal Bite',
                                    'purpose-dental'     => 'Dental',
                                ];
                                foreach ($purposes_list as $id => $label): ?>
                                <div class="purpose-item">
                                    <input class="form-check-input" type="checkbox" name="purpose[]" id="<?= $id ?>" value="<?= $label ?>">
                                    <label class="form-check-label" for="<?= $id ?>"><?= $label ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div id="purpose-error" class="text-danger mt-2" style="display:none; font-size:0.875rem;">
                                <i class="fas fa-exclamation-circle me-1"></i>Please select at least one purpose of visit.
                            </div>

                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-primary btn-lg" onclick="goToStep2()">
                                    Next: Personal Information <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ── STEP 2: Personal Information ── -->
                        <div id="step2" style="display:none;">
                            <h4 class="mb-1"><i class="fas fa-user me-2 text-primary"></i>Personal Information</h4>
                            <p class="text-muted mb-4" style="font-size:0.9rem;">Fields marked with <span class="text-danger">*</span> are required.</p>

                        <!-- I. Patient Information -->
                        <div class="itr-section-title">I. Patient Information</div>

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
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="mb-3">
                                    <label for="suffix" class="form-label">Suffix</label>
                                    <select class="form-select" id="suffix" name="suffix">
                                        <option value="">—</option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Maiden Name — shown only for Female + Married -->
                        <div class="row" id="maiden-name-row" style="display:none;">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="maiden_name" class="form-label required-field">Maiden Name (if married)</label>
                                    <input type="text" class="form-control" id="maiden_name" name="maiden_name"
                                           placeholder="Last name before marriage">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label required-field">Birth Date</label>
                                    <div class="dob-picker">
                                        <select id="dob_month" class="form-select dob-select">
                                            <option value="">Month</option>
                                            <option value="01">January</option>
                                            <option value="02">February</option>
                                            <option value="03">March</option>
                                            <option value="04">April</option>
                                            <option value="05">May</option>
                                            <option value="06">June</option>
                                            <option value="07">July</option>
                                            <option value="08">August</option>
                                            <option value="09">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                        <select id="dob_day" class="form-select dob-select">
                                            <option value="">Day</option>
                                        </select>
                                        <select id="dob_year" class="form-select dob-select">
                                            <option value="">Year</option>
                                        </select>
                                    </div>
                                    <input type="hidden" id="date_of_birth" name="date_of_birth">
                                    <div id="dob-error" class="text-danger mt-1" style="display:none; font-size:0.78rem;">
                                        <i class="fas fa-exclamation-circle me-1"></i>Please select your complete birth date.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="sex" class="form-label required-field">Sex</label>
                                    <select class="form-select" id="sex" name="sex" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="civil_status" class="form-label required-field">Civil Status</label>
                                    <select class="form-select" id="civil_status" name="civil_status" required>
                                        <option value="">Select</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="employment_status" class="form-label">Employment Status</label>
                                    <select class="form-select" id="employment_status" name="employment_status">
                                        <option value="">Select Status</option>
                                        <option value="Employed">Employed</option>
                                        <option value="Non-Employed">Non-Employed</option>
                                        <option value="OFW">OFW</option>
                                        <option value="Retired">Retired</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="barangay" class="form-label required-field">Residential Address (Barangay)</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" required placeholder="Barangay, Bacolod City">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_number" class="form-label required-field">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="educational_attainment" class="form-label">Educational Attainment</label>
                                    <select class="form-select" id="educational_attainment" name="educational_attainment">
                                        <option value="">Select</option>
                                        <option value="Elementary">Elementary</option>
                                        <option value="High School">High School</option>
                                        <option value="College">College</option>
                                        <option value="Vocational">Vocational</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="spouse_name" class="form-label">Spouse Name</label>
                                    <input type="text" class="form-control" id="spouse_name" name="spouse_name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mother_name" class="form-label">Mother's Name</label>
                                    <input type="text" class="form-control" id="mother_name" name="mother_name">
                                </div>
                            </div>
                        </div>

                        <!-- Mode of Transaction -->
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mode_of_transaction" class="form-label required-field">Mode of Transaction</label>
                                    <select class="form-select" id="mode_of_transaction" name="mode_of_transaction" required>
                                        <option value="">-- Select --</option>
                                        <option value="Walk-in" selected>Walk-in</option>
                                        <option value="Visited">Visited</option>
                                        <option value="Referral">Referral</option>
                                    </select>
                                    <div class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>Select how you are transacting with the clinic.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- II. Household & Social -->
                        <div class="itr-section-title mt-2">II. Household & Social Information</div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">DSWD NHTS Member</label>
                                    <select class="form-select" name="dswd_nhts">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">4P's Member</label>
                                    <select class="form-select" name="four_ps_member">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="facility_household_no" class="form-label">Facility Household No.</label>
                                    <input type="text" class="form-control" id="facility_household_no" name="facility_household_no">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="household_no" class="form-label">Household No.</label>
                                    <input type="text" class="form-control" id="household_no" name="household_no">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="co_habitation" class="form-label">Co-habitation</label>
                                    <input type="text" class="form-control" id="co_habitation" name="co_habitation" placeholder="e.g. Living with family">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="family_member" class="form-label">Family Member</label>
                                    <select class="form-select" id="family_member" name="family_member">
                                        <option value="">Select Family Member</option>
                                        <option value="Father">Father</option>
                                        <option value="Mother">Mother</option>
                                        <option value="Son">Son</option>
                                        <option value="Daughter">Daughter</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- III. PhilHealth -->
                        <div class="itr-section-title mt-2">III. PhilHealth Information</div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">PhilHealth Member</label>
                                    <select class="form-select" id="philhealth_member" name="philhealth_member" onchange="togglePhilhealth()">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="philhealth_no" class="form-label">PhilHealth Number</label>
                                    <input type="text" class="form-control" id="philhealth_no" name="philhealth_no" maxlength="14" placeholder="e.g. 12-345678901-2">
                                </div>
                            </div>
                            <div class="col-md-3" id="philhealth-status-wrap" style="display:none;">
                                <div class="mb-3">
                                    <label for="philhealth_status_type" class="form-label">Status Type</label>
                                    <select class="form-select" id="philhealth_status_type" name="philhealth_status_type">
                                        <option value="">Select Status</option>
                                        <option value="Member">Member</option>
                                        <option value="Dependent">Dependent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Primary Care Benefit Member</label>
                                    <select class="form-select" name="primary_care_benefit">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Select Category</option>
                                        <option value="FE-Private">FE- Private</option>
                                        <option value="FE-Government">FE- Government</option>
                                        <option value="IE">IE</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button type="button" class="btn btn-outline-secondary" onclick="goToStep1()">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-check me-2"></i>Complete Booking
                            </button>
                        </div>

                        </div><!-- /step2 -->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Birth Date: Month / Day / Year dropdowns ─────────────────
        (function () {
            const monthSel = document.getElementById('dob_month');
            const daySel   = document.getElementById('dob_day');
            const yearSel  = document.getElementById('dob_year');
            const hidden   = document.getElementById('date_of_birth');

            // Populate year dropdown: current year down to 1920
            const currentYear = new Date().getFullYear();
            for (let y = currentYear; y >= 1920; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSel.appendChild(opt);
            }

            // Populate days based on selected month/year
            function populateDays() {
                const m    = parseInt(monthSel.value) || 1;
                const y    = parseInt(yearSel.value)  || 2000;
                const prev = daySel.value;
                daySel.innerHTML = '<option value="">Day</option>';
                const daysInMonth = new Date(y, m, 0).getDate();
                for (let d = 1; d <= daysInMonth; d++) {
                    const opt = document.createElement('option');
                    opt.value = String(d).padStart(2, '0');
                    opt.textContent = d;
                    daySel.appendChild(opt);
                }
                if (prev && parseInt(prev) <= daysInMonth) daySel.value = prev;
            }

            function updateHidden() {
                const m = monthSel.value;
                const d = daySel.value;
                const y = yearSel.value;
                if (m && d && y) {
                    hidden.value = y + '-' + m + '-' + d;
                    [monthSel, daySel, yearSel].forEach(s => s.classList.remove('is-invalid'));
                    document.getElementById('dob-error').style.display = 'none';
                } else {
                    hidden.value = '';
                }
            }

            monthSel.addEventListener('change', function () { populateDays(); updateHidden(); });
            yearSel.addEventListener('change',  function () { populateDays(); updateHidden(); });
            daySel.addEventListener('change',   updateHidden);

            populateDays();

            window.validateDOB = function () {
                if (!hidden.value) {
                    [monthSel, daySel, yearSel].forEach(s => s.classList.add('is-invalid'));
                    document.getElementById('dob-error').style.display = 'block';
                    monthSel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                return true;
            };
        })();

        // Validate DOB on submit
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!window.validateDOB()) {
                e.preventDefault();
            }
        });

        function goToStep2() {
            const checked = document.querySelectorAll('input[name="purpose[]"]:checked');
            if (checked.length === 0) {
                document.getElementById('purpose-error').style.display = 'block';
                return;
            }
            document.getElementById('purpose-error').style.display = 'none';

            // Update step indicators
            document.getElementById('step-dot-1').classList.remove('active');
            document.getElementById('step-dot-1').classList.add('completed');
            document.getElementById('step-dot-1').querySelector('.step-circle').innerHTML = '<i class="fas fa-check" style="font-size:0.8rem"></i>';
            document.querySelector('.step-line').classList.add('completed');
            document.getElementById('step-dot-2').classList.add('active');

            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function goToStep1() {
            document.getElementById('step-dot-1').classList.add('active');
            document.getElementById('step-dot-1').classList.remove('completed');
            document.getElementById('step-dot-1').querySelector('.step-circle').textContent = '1';
            document.querySelector('.step-line').classList.remove('completed');
            document.getElementById('step-dot-2').classList.remove('active');

            document.getElementById('step2').style.display = 'none';
            document.getElementById('step1').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Checkbox card toggle styling
        document.querySelectorAll('input[name="purpose[]"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                const label = this.nextElementSibling;
                if (this.checked) {
                    label.style.borderColor = '#0d6efd';
                    label.style.background  = '#e8f0fe';
                    label.style.color       = '#0d6efd';
                } else {
                    label.style.borderColor = '#dee2e6';
                    label.style.background  = '#fff';
                    label.style.color       = '#495057';
                }
            });
        });

        // Maiden name: show & require when Female + Married
        function toggleMaidenName() {
            const sex         = document.getElementById('sex').value;
            const civilStatus = document.getElementById('civil_status').value;
            const row         = document.getElementById('maiden-name-row');
            const input       = document.getElementById('maiden_name');
            const label       = document.querySelector('label[for="maiden_name"]');

            if (sex === 'Female' && civilStatus === 'Married') {
                row.style.display = 'flex';
                input.required = true;
                // ensure the required asterisk shows
                label.classList.add('required-field');
            } else {
                row.style.display = 'none';
                input.required = false;
                input.value = '';
                label.classList.remove('required-field');
            }
        }

        document.getElementById('sex').addEventListener('change', toggleMaidenName);
        document.getElementById('civil_status').addEventListener('change', toggleMaidenName);

        // PhilHealth fields: show number & status type only when member = Yes
        function togglePhilhealth() {
            const isMember = document.getElementById('philhealth_member').value === 'Yes';
            document.getElementById('philhealth-status-wrap').style.display = isMember ? 'block' : 'none';
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>





