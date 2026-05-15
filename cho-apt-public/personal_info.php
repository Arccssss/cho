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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Information - CHO Appointment</title>
        <link rel="stylesheet" href="assets/css/personal_info.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="assets/js/personal_info.js" defer></script>    
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

</body>
</html>

<?php $conn->close(); ?>





