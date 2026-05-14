<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php');
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// ── View mode: pre-fill form from DB record ──────────────────────────────────
$view_mode = false;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_mode = true;
    $view_id   = intval($_GET['view']);
    $vstmt = $conn->prepare("SELECT * FROM patient_enrollment WHERE id = ?");
    $vstmt->bind_param("i", $view_id);
    $vstmt->execute();
    $vrow = $vstmt->get_result()->fetch_assoc();
    $vstmt->close();
    if (!$vrow) {
        setFlashMessage('error', 'Record not found.');
        redirect('admin_dashboard.php');
    }
    // Map DB columns → $_POST so the form renders pre-filled
    $_POST = [
        'last_name'             => $vrow['last_name']            ?? '',
        'first_name'            => $vrow['first_name']           ?? '',
        'middle_name'           => $vrow['middle_name']          ?? '',
        'suffix'                => $vrow['suffix']               ?? '',
        'maiden_name'           => $vrow['maiden_name']          ?? '',
        'age'                   => $vrow['age']                  ?? '',
        'sex'                   => ucfirst(strtolower($vrow['sex'] ?? '')),
        'birth_date'            => $vrow['birth_date']           ?? '',
        'contact_number'        => $vrow['contact_number']       ?? '',
        'residential_address'   => $vrow['residential_address']  ?? '',
        'civil_status'          => ucfirst(strtolower($vrow['civil_status'] ?? '')),
        'spouse_name'           => $vrow['spouse_name']          ?? '',
        'educational_attainment'=> $vrow['educational_attainment'] ?? '',
        'employment_status'     => $vrow['employment_status']    ?? '',
        'dswd_nhts'             => $vrow['dswd_nhts']            ?? 'No',
        'four_ps_member'        => $vrow['four_ps_member']       ?? 'No',
        'facility_household_no' => $vrow['facility_household_no'] ?? '',
        'household_no'          => $vrow['household_no']         ?? '',
        'philhealth_member'     => $vrow['philhealth_member']    ?? 'No',
        'philhealth_status_type'=> $vrow['philhealth_status_type'] ?? '',
        'philhealth_no'         => $vrow['philhealth_no']        ?? '',
        'category'              => $vrow['category']             ?? '',
        'category_other'        => $vrow['category_other']       ?? '',
        'primary_care_benefit'  => $vrow['primary_care_benefit'] ?? 'No',
        'family_member'         => $vrow['family_member']        ?? '',
        'mothers_name'          => $vrow['mother_name'] ?? '',
        'mode_of_transaction'   => $vrow['mode_of_transaction']  ?? '',
        'date_of_consultation'  => $vrow['date_of_consultation'] ?? '',
        'consultation_time'     => $vrow['consultation_time']    ?? '',
        'nature_of_visit'       => array_filter(array_map('trim', explode(',', preg_replace('/\s*\[Ref:[^\]]*\]/', '', $vrow['purpose_of_visit'] ?? '')))),
        'temperature'           => $vrow['temperature']          ?? '',
        'heart_rate'            => $vrow['heart_rate']           ?? '',
        'respiratory_rate'      => $vrow['respiratory_rate']     ?? '',
        'height'                => $vrow['height']               ?? '',
        'blood_pressure'        => $vrow['blood_pressure']       ?? '',
        'weight'                => $vrow['weight']               ?? '',
        'waist'                 => $vrow['waist']                ?? '',
        'hip'                   => $vrow['hip']                  ?? '',
        'chief_complaints'      => $vrow['chief_complaints']     ?? '',
        'diagnosis'             => $vrow['diagnosis']            ?? '',
        'diagnosis_other'       => $vrow['diagnosis_other']      ?? '',
        'medication_treatment'  => $vrow['medication_treatment'] ?? '',
        'performed_lab_tests'   => $vrow['performed_lab_tests'] ?? $vrow['performed_laboratory_test'] ?? '',
        'lab_findings_impressions' => $vrow['lab_findings_impressions'] ?? $vrow['laboratory_findings'] ?? '',
    ];
}
// ─────────────────────────────────────────────────────────────────────────────

// ── Appointment lookup: fetch recent appointments for pre-fill dropdown ───────
$appointments = [];
if (!$view_mode) {
    $astmt = $conn->prepare("SELECT id, reference_number, last_name, first_name, middle_name, 
                              suffix, date_of_birth, sex, civil_status, barangay, contact_number, 
                              philhealth_no, purpose, appointment_date
                              FROM appointments 
                              WHERE status IN ('pending','confirmed')
                              ORDER BY appointment_date DESC, id DESC 
                              LIMIT 100");
    $astmt->execute();
    $aresult = $astmt->get_result();
    while ($arow = $aresult->fetch_assoc()) {
        $appointments[] = $arow;
    }
    $astmt->close();
}
// ─────────────────────────────────────────────────────────────────────────────

// Handle form submission
if (!$view_mode && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all form data
    $last_name = sanitize($_POST['last_name'] ?? '');
    $first_name = sanitize($_POST['first_name'] ?? '');
    $middle_name = sanitize($_POST['middle_name'] ?? '');
    $suffix = sanitize($_POST['suffix'] ?? '');
    $maiden_name = sanitize($_POST['maiden_name'] ?? '');
    $age = sanitize($_POST['age'] ?? '');
    $sex = ucfirst(strtolower(sanitize($_POST['sex'] ?? '')));
    $birth_date = sanitize($_POST['birth_date'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $residential_address = sanitize($_POST['residential_address'] ?? '');
    $civil_status = ucfirst(strtolower(sanitize($_POST['civil_status'] ?? '')));
    $spouse_name = sanitize($_POST['spouse_name'] ?? '');
    $educational_attainment = sanitize($_POST['educational_attainment'] ?? '');
    $employment_status = sanitize($_POST['employment_status'] ?? '');
    $dswd_nhts = sanitize($_POST['dswd_nhts'] ?? 'No');
    $four_ps_member = sanitize($_POST['four_ps_member'] ?? 'No');
    $facility_household_no = sanitize($_POST['facility_household_no'] ?? '');
    $household_no = sanitize($_POST['household_no'] ?? '');
    $philhealth_member = sanitize($_POST['philhealth_member'] ?? 'No');
    $philhealth_status_type = sanitize($_POST['philhealth_status_type'] ?? '');
    $philhealth_no = sanitize($_POST['philhealth_no'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $category_other = sanitize($_POST['category_other'] ?? '');
    $primary_care_benefit = sanitize($_POST['primary_care_benefit'] ?? 'No');
    $family_member = sanitize($_POST['family_member'] ?? '');
    $mothers_name = sanitize($_POST['mothers_name'] ?? '');
    $mode_of_transaction = sanitize($_POST['mode_of_transaction'] ?? '');
    $date_of_consultation = sanitize($_POST['date_of_consultation'] ?? '');
    $consultation_time = sanitize($_POST['consultation_time'] ?? '');
    $nature_of_visit = isset($_POST['nature_of_visit']) ? implode(', ', array_map('sanitize', $_POST['nature_of_visit'])) : '';
    $temperature = sanitize($_POST['temperature'] ?? '');
    $heart_rate = intval($_POST['heart_rate'] ?? 0) ?: null;
    $respiratory_rate = intval($_POST['respiratory_rate'] ?? 0) ?: null;
    $height = sanitize($_POST['height'] ?? '');
    $blood_pressure = sanitize($_POST['blood_pressure'] ?? '');
    $weight = sanitize($_POST['weight'] ?? '');
    $waist = floatval($_POST['waist'] ?? 0) ?: null;
    $hip = floatval($_POST['hip'] ?? 0) ?: null;
    $chief_complaints = sanitize($_POST['chief_complaints'] ?? '');
    $diagnosis = sanitize($_POST['diagnosis'] ?? '');
    $diagnosis_other = sanitize($_POST['diagnosis_other'] ?? '');
    $medication_treatment = sanitize($_POST['medication_treatment'] ?? '');
    $performed_lab_tests = sanitize($_POST['performed_lab_tests'] ?? '');
    $lab_findings_impressions = sanitize($_POST['lab_findings_impressions'] ?? '');
    
    // If Others is selected, use the specified value
    if ($category === 'Others' && !empty($category_other)) {
        $category = 'Others: ' . $category_other;
    }
    
    // Validate required fields
    if (empty($last_name) || empty($first_name) || empty($age) || empty($sex)) {
        setFlashMessage('error', 'Please fill in all required fields. Missing: ' . 
            (empty($last_name) ? 'Last Name ' : '') .
            (empty($first_name) ? 'First Name ' : '') .
            (empty($age) ? 'Age ' : '') .
            (empty($sex) ? 'Sex' : ''));
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO patient_enrollment (
            user_id, last_name, first_name, middle_name, suffix, maiden_name, mother_name, age, sex,
            birth_date, contact_number, residential_address, civil_status, spouse_name,
            educational_attainment, employment_status, dswd_nhts, four_ps_member,
            facility_household_no, household_no, philhealth_member, philhealth_status_type,
            philhealth_no, category, primary_care_benefit, family_member,
            mode_of_transaction, date_of_consultation, consultation_time, purpose_of_visit,
            temperature, height, blood_pressure, weight, chief_complaints, diagnosis,
            medication_treatment, performed_laboratory_test, laboratory_findings, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("isssssssssssssssssssssssssssssssssssssss",
            $user_id, $last_name, $first_name, $middle_name, $suffix, $maiden_name, $mothers_name, $age, $sex,
            $birth_date, $contact_number, $residential_address, $civil_status, $spouse_name,
            $educational_attainment, $employment_status, $dswd_nhts, $four_ps_member,
            $facility_household_no, $household_no, $philhealth_member, $philhealth_status_type,
            $philhealth_no, $category, $primary_care_benefit, $family_member,
            $mode_of_transaction, $date_of_consultation, $consultation_time, $nature_of_visit,
            $temperature, $height, $blood_pressure, $weight, $chief_complaints, $diagnosis,
            $medication_treatment, $performed_lab_tests, $lab_findings_impressions
        );
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Patient enrollment form created successfully.');
            redirect('admin_dashboard.php');
        } else {
            setFlashMessage('error', 'Failed to create enrollment form: ' . $stmt->error);
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Enrollment Record - Bacolod City Health Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/create_patient_enrollment.css">

</head>
<body>
    <div class="container">
        <?php displayFlashMessage(); ?>

        <form method="POST" action="">
            <!-- Header Section -->
            <div class="header">
                <div class="header-left">
                    <img src="images/bcd.jpg" alt="Bacolod City Health Office Logo" class="doh-logo">
                    <div class="doh-text">
                        <div class="republic">Republic of the Philippines</div>
                        <div class="department">Department of Health</div>
                        <div class="kagawaran">Kagawaran ng Kalusugan</div>
                    </div>
                </div>
                <div class="header-center">
                    <h2>BACOLOD CITY HEALTH OFFICE</h2>
                    <p>BACOLOD CITY</p>
                </div>
                <div class="header-right">
                    <div class="fee-item">
                        <span>Date Of Consultation:</span>
                        <span class="underline" id="print-date"></span>
                    </div>
                    <div class="fee-item">
                        <span>Consultation Time:</span>
                        <span class="underline" id="print-time"></span>
                    </div>
                </div>
            </div>

            <!-- Main Title -->
            <div class="main-title">
                <h1>PATIENT ENROLLMENT RECORD / INDIVIDUAL TREATMENT RECORD</h1>
            </div>

            <!-- Form Section -->
            <div class="form-section">
                <?php if (!$view_mode && !empty($appointments)): ?>
                <div class="appointment-lookup-bar" style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <label style="font-weight:700;font-size:12px;white-space:nowrap;"><i class="fas fa-calendar-check" style="color:#FF6B35;"></i> Pull from Appointment:</label>
                    <select id="appointmentLookup" style="flex:1;min-width:200px;font-size:12px;padding:5px 8px;border:1px solid #ccc;border-radius:6px;">
                        <option value="">— Select an appointment to pre-fill —</option>
                        <?php foreach ($appointments as $apt): ?>
                        <option value="<?php echo $apt['id']; ?>"
                            data-last="<?php echo htmlspecialchars($apt['last_name']); ?>"
                            data-first="<?php echo htmlspecialchars($apt['first_name']); ?>"
                            data-middle="<?php echo htmlspecialchars($apt['middle_name']); ?>"
                            data-suffix="<?php echo htmlspecialchars($apt['suffix']); ?>"
                            data-birth="<?php echo htmlspecialchars($apt['date_of_birth']); ?>"
                            data-sex="<?php echo htmlspecialchars(ucfirst(strtolower($apt['sex']))); ?>"
                            data-civil="<?php echo htmlspecialchars(ucfirst(strtolower($apt['civil_status']))); ?>"
                            data-address="<?php echo htmlspecialchars($apt['barangay']); ?>"
                            data-contact="<?php echo htmlspecialchars($apt['contact_number']); ?>"
                            data-philhealth="<?php echo htmlspecialchars($apt['philhealth_no']); ?>"
                            data-purpose="<?php echo htmlspecialchars($apt['purpose']); ?>"
                            data-date="<?php echo htmlspecialchars($apt['appointment_date']); ?>">
                            <?php echo htmlspecialchars($apt['last_name'] . ', ' . $apt['first_name'] . ' — ' . $apt['reference_number'] . ' (' . date('M d, Y', strtotime($apt['appointment_date'])) . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="fillFromAppointment()" style="background:linear-gradient(135deg,#FF6B35,#F7931E);color:white;border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:12px;cursor:pointer;">
                        <i class="fas fa-file-import"></i> Fill Form
                    </button>
                </div>
                <?php endif; ?>

                <div class="section-header">I. PATIENT INFORMATION (IMPORMASYON NG PASYENTE)</div>

                <!-- Personal Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name <span class="required-mark">*</span></label>
                        <input type="text" name="last_name" required placeholder="Enter last name">
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="required-mark">*</span></label>
                        <input type="text" name="first_name" required placeholder="Enter first name">
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" placeholder="Enter middle name">
                    </div>
                    <div class="form-group suffix-group">
                        <label>Suffix</label>
                        <input type="text" name="suffix" placeholder="Jr., Sr., etc.">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Maiden Name (if married)</label>
                        <input type="text" name="maiden_name" placeholder="Enter maiden name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Birth Date</label>
                        <input type="date" name="birth_date">
                    </div>
                    <div class="form-group">
                        <label>Age <span class="required-mark">*</span></label>
                        <input type="number" name="age" required placeholder="Enter age" min="0" max="150">
                    </div>
                    <div class="form-group">
                        <label>Sex <span class="required-mark">*</span></label>
                        <select name="sex" required>
                            <option value="">Select Sex</option>
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="civil_status">
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Annuled">Annuled</option>
                            <option value="Separated">Separated</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Residential Address</label>
                        <input type="text" name="residential_address" placeholder="Enter complete address">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="Enter contact number">
                    </div>
                    <div class="form-group">
                        <label>Employment Status</label>
                        <select name="employment_status">
                            <option value="">Select Status</option>
                            <option value="Employed">Employed</option>
                            <option value="Non-Employed">Non-Employed</option>
                            <option value="OFW">OFW</option>
                            <option value="Retired">Retired</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Spouse Name</label>
                        <input type="text" name="spouse_name" placeholder="Enter spouse name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Mother's Name</label>
                        <input type="text" name="mothers_name" placeholder="Enter mother's full name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>DSWD NHTS Member</label>
                        <select name="dswd_nhts">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Facility Household No.</label>
                        <input type="text" name="facility_household_no" placeholder="Enter facility household no.">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>4P's Member</label>
                        <select name="four_ps_member">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Household No.</label>
                        <input type="text" name="household_no" placeholder="Enter household no.">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Educational Attainment</label>
                        <select name="educational_attainment">
                            <option value="">Select Education</option>
                            <option value="No Formal Education">No Formal Education</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="College">College</option>
                            <option value="Vocational">Vocational</option>
                            <option value="Post Graduate">Post Graduate</option>
                            <option value="Student">Student</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>

                <!-- Philhealth Information -->
                <div class="subsection">
                    <h3 style="margin-bottom: 12px; color: var(--text-primary); font-size: 13px;">Philhealth Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Philhealth Member</label>
                            <select name="philhealth_member">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status Type</label>
                            <select name="philhealth_status_type">
                                <option value="">Select Status</option>
                                <option value="Member">Member</option>
                                <option value="Dependent">Dependent</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Philhealth Number</label>
                            <input type="text" name="philhealth_no" placeholder="Enter Philhealth number">
                        </div>
                        <div class="form-group">
                            <label>If Others, specify</label>
                            <input type="text" name="category_other" placeholder="Specify category">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Primary Care Benefit Member</label>
                            <select name="primary_care_benefit">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Family Member</label>
                            <select name="family_member">
                                <option value="">Select Family Member</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="form-group category-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">Select Category</option>
                                <option value="FE-Private">FE- Private</option>
                                <option value="FE-Government">FE- Government</option>
                                <option value="IE">IE</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Mother's Information -->

                <div class="subsection" style="margin-top: 10px;">
                    <div class="section-header chu-header" style="font-size: 10px;">FOR CHO / BHS PERSONNEL ONLY (PARA SA KINATAWAN NG CHO)</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="no-colon">Mode of Transaction:</label>
                            <select name="mode_of_transaction">
                                <option value="">-- Select --</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Visited">Visited</option>
                                <option value="Referral">Referral</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="no-colon">Purpose of Visit:</label>
                            <div class="checkbox-list">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Medical Consultation">
                                    <label>Medical Consultation</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Adult/Pediatric">
                                    <label>Adult/Pediatric</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Animal Bite">
                                    <label>Animal Bite</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Prenatal Checkup">
                                    <label>Prenatal Checkup</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Postnatal Checkup">
                                    <label>Postnatal Checkup</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Dental Care">
                                    <label>Dental Care</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="TB">
                                    <label>TB</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Social Hygiene">
                                    <label>Social Hygiene</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Immunization">
                                    <label>Immunization</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Drug Testing">
                                    <label>Drug Testing</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Laboratory">
                                    <label>Laboratory</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Chest X-ray">
                                    <label>Chest X-ray</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Wound/Injury">
                                    <label>Wound/Injury</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="nature_of_visit[]" value="Family Planning">
                                    <label>Family Planning</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" style="grid-template-columns: repeat(4, 1fr);">
                        <div class="form-group">
                            <label class="no-colon">Temperature:</label>
                            <input type="text" name="temperature" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Heart Rate:</label>
                            <input type="text" name="heart_rate" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Respiratory Rate:</label>
                            <input type="text" name="respiratory_rate" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Blood Pressure:</label>
                            <input type="text" name="blood_pressure" class="input-small">
                        </div>
                    </div>
                    <div class="form-row" style="grid-template-columns: repeat(5, 1fr);">
                        <div class="form-group">
                            <label class="no-colon">Height (cm):</label>
                            <input type="text" name="height" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Weight (kg):</label>
                            <input type="text" name="weight" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Waist (cm):</label>
                            <input type="text" name="waist" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">Hip (cm):</label>
                            <input type="text" name="hip" class="input-small">
                        </div>
                        <div class="form-group">
                            <label class="no-colon">O2 Sat:</label>
                            <input type="text" name="hip" class="input-small">
                        </div>
                    </div>

                </div>

                <!-- Patient's Consent Section (full width) -->
                <div class="consent-section">
                    <div class="section-header consent-header" style="font-size: 20px;">II. PATIENT'S CONSENT (PAHINTULOT NG PASYENTE)</div>
                    <div class="consent-text">
                        Aking nabasa at naintindihan ang Impormasyon ng Pasyente matapos ako'y bigyang-kaalaman ng mga nilalaman nito. Sa isang pag-uusap kasama ang kinatawan ng CHO/BHS, ako ay binigyang-paunawa nang mahusay tungkol sa kakanyahan at kahalagahan ng Integrated Clinic Information System (IClinicSys/YAKAP). Lahat ng aking mga katanungan sa panahon ng pag-uusap ay nasagot ng sapat at ako ay binigyan ng sapat na oras upang magpasya nito.
                        <br><br>
                        Higit pa rito, pinapayagan ko ang CHO/BHS upang i-encode ang mga Impormasyon patungkol sa akin at ang mga nakolektang impormasyon tungkol sa mga sintomas ng aking sakit at konsultasyong kaugnay dito para sa nasabing information system.
                        <br><br>
                        Nais kong malaman at maipaalam sa aking direktang kapamilya ang aking mga medikal na resulta. Gayundin, maari kong kanselahin ang aking pahintulot sa CHO/BHS anumang oras na walang ibinibigay na dahilan at walang kinalaman sa anumang kawalan para sa aking medikal na pagpapagamot.
                    </div>
                    <div class="consent-signatures">
                        <div class="consent-sig-box">
                            <div class="consent-sig-line"></div>
                            <div class="consent-sig-label">Signature of Patient / Date</div>
                        </div>
                        <div class="consent-sig-box">
                            <div class="consent-sig-line"></div>
                            <div class="consent-sig-label">Name of CHO/BHS representative</div>
                        </div>
                    </div>
                </div>

                <!-- Past Medical History -->
                <div class="history-section">
                    <div class="history-header past-medical">PAST MEDICAL HISTORY</div>
                    <div class="history-body">
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Allergy"><label>Allergy</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Asthma"><label>Asthma</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Cancer"><label>Cancer</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Cerebrovascular Disease"><label>Cerebrovascular Disease</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Coronary Artery Disease"><label>Coronary Artery Disease</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Specify Allergy:</span>
                            <input type="text" name="pmh_allergy_specify" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Specify organ with cancer:</span>
                            <input type="text" name="pmh_cancer_specify" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Diabetes Mellitus"><label>Diabetes Mellitus</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Emphysema"><label>Emphysema</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Epilepsy/Seizure Disorder"><label>Epilepsy/Seizure Disorder</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Hepatitis"><label>Hepatitis</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Hyperlipidemia"><label>Hyperlipidemia</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Latest FBS/RBS:</span>
                            <input type="text" name="pmh_fbs" class="hfield-input">
                            <span class="hfield-label">Date:</span>
                            <input type="text" name="pmh_fbs_date" class="hfield-input">
                            <span class="hfield-label">Specify hepatitis type:</span>
                            <input type="text" name="pmh_hepatitis_type" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Hypertension"><label>Hypertension</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Peptic Ulcer"><label>Peptic Ulcer</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Pneumonia"><label>Pneumonia</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Thyroid Disease"><label>Thyroid Disease</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Pulmonary Tuberculosis"><label>Pulmonary Tuberculosis</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Highest Blood Pressure:</span>
                            <input type="text" name="pmh_bp" class="hfield-input">
                            <span class="hfield-label">Specify Pulmonary Tuberculosis Category:</span>
                            <input type="text" name="pmh_tb_category" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Extrapulmonary Tuberculosis"><label>Extrapulmonary Tuberculosis</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Urinary Tract Infection"><label>Urinary Tract Infection</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="pmh[]" value="Mental Illness"><label>Mental Illness</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="Others"><label>Others</label></div>
                            <div class="hcheck"><input type="checkbox" name="pmh[]" value="None"><label>None</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Specify Extrapulmonary Tuberculosis category:</span>
                            <input type="text" name="pmh_extrapulm_specify" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Past Surgical History:</span>
                            <input type="text" name="pmh_surgical" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Date:</span>
                            <input type="text" name="pmh_surgical_date" class="hfield-input">
                        </div>
                    </div>
                </div>

                <!-- Family and Personal History -->
                <div class="history-section">
                    <div class="history-header family-personal">FAMILY AND PERSONAL HISTORY</div>
                    <div class="history-body">
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Allergy"><label>Allergy</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Asthma"><label>Asthma</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Cancer"><label>Cancer</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Cerebrovascular Disease"><label>Cerebrovascular Disease</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Coronary Artery Disease"><label>Coronary Artery Disease</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Specify Allergy:</span>
                            <input type="text" name="fph_allergy_specify" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Specify organ with cancer:</span>
                            <input type="text" name="fph_cancer_specify" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Diabetes Mellitus"><label>Diabetes Mellitus</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Emphysema"><label>Emphysema</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Epilepsy/Seizure Disorder"><label>Epilepsy/Seizure Disorder</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Hepatitis"><label>Hepatitis</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Hyperlipidemia"><label>Hyperlipidemia</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Latest FBS/RBS:</span>
                            <input type="text" name="fph_fbs" class="hfield-input">
                            <span class="hfield-label">Date:</span>
                            <input type="text" name="fph_fbs_date" class="hfield-input">
                            <span class="hfield-label">Specify hepatitis type:</span>
                            <input type="text" name="fph_hepatitis_type" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Hypertension"><label>Hypertension</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Peptic Ulcer"><label>Peptic Ulcer</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Pneumonia"><label>Pneumonia</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Thyroid Disease"><label>Thyroid Disease</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Pulmonary Tuberculosis"><label>Pulmonary Tuberculosis</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Highest High blood pressure:</span>
                            <input type="text" name="fph_bp" class="hfield-input">
                            <span class="hfield-label">Specify Pulmonary Tuberculosis Category:</span>
                            <input type="text" name="fph_tb_category" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Extrapulmonary Tuberculosis"><label>Extrapulmonary Tuberculosis</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Urinary Tract Infection"><label>Urinary Tract Infection</label></div>
                            <div class="hcheck hcheck-wide"><input type="checkbox" name="fph[]" value="Mental Illness"><label>Mental Illness</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="Others"><label>Others</label></div>
                            <div class="hcheck"><input type="checkbox" name="fph[]" value="None"><label>None</label></div>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Specify Extrapulmonary Tuberculosis category:</span>
                            <input type="text" name="fph_extrapulm_specify" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Past Surgical History:</span>
                            <input type="text" name="fph_surgical" class="hfield-input hfield-input-lg">
                            <span class="hfield-label">Date:</span>
                            <input type="text" name="fph_surgical_date" class="hfield-input">
                        </div>
                    </div>
                </div>

                <!-- Personal / Social History -->
                <div class="history-section">
                    <div class="history-header personal-social">PERSONAL / SOCIAL HISTORY</div>
                    <div class="history-body">
                        <div class="history-row">
                            <span class="hfield-label">*Smoking</span>
                            <div class="hcheck"><input type="checkbox" name="psh_smoking" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_smoking" value="No"><label>No</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_smoking" value="Quit"><label>Quit</label></div>
                            <span class="hfield-label">No. of sticks/day?</span>
                            <input type="text" name="psh_smoking_sticks" class="hfield-input">
                            <span class="hfield-label">X No. of years</span>
                            <input type="text" name="psh_smoking_years" class="hfield-input">
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">*Alcohol</span>
                            <div class="hcheck"><input type="checkbox" name="psh_alcohol" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_alcohol" value="No"><label>No</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_alcohol" value="Quit"><label>Quit</label></div>
                            <span class="hfield-label">No. of bottles / day?</span>
                            <input type="text" name="psh_alcohol_bottles" class="hfield-input">
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">*Illicit Drugs</span>
                            <div class="hcheck"><input type="checkbox" name="psh_drugs" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_drugs" value="No"><label>No</label></div>
                            <span class="hfield-label" style="margin-left:10px;">*Sexually Active</span>
                            <div class="hcheck"><input type="checkbox" name="psh_sexually_active" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="psh_sexually_active" value="No"><label>No</label></div>
                        </div>
                    </div>
                </div>

                <!-- Immunizations -->
                <div class="history-section">
                    <div class="history-header" style="background:#ffe066;">IMMUNIZATIONS</div>
                    <div class="immunization-body">
                        <div class="immunization-cols">
                            <!-- For Children -->
                            <div class="immunization-col">
                                <div class="immunization-col-header">For Children</div>
                                <div class="history-row">
                                    <span class="hfield-label">BCG</span><input type="text" name="imm_bcg" class="hfield-input imm-input">
                                    <span class="hfield-label">OPV1</span><input type="text" name="imm_opv1" class="hfield-input imm-input">
                                    <span class="hfield-label">OPV2</span><input type="text" name="imm_opv2" class="hfield-input imm-input">
                                    <span class="hfield-label">DPT1</span><input type="text" name="imm_dpt1" class="hfield-input imm-input">
                                    <span class="hfield-label">DPT2</span><input type="text" name="imm_dpt2" class="hfield-input imm-input">
                                    <span class="hfield-label">DPT3</span><input type="text" name="imm_dpt3" class="hfield-input imm-input">
                                </div>
                                <div class="history-row">
                                    <span class="hfield-label">Measles</span><input type="text" name="imm_measles" class="hfield-input imm-input">
                                    <span class="hfield-label">Hepatitis B1</span><input type="text" name="imm_hepb1" class="hfield-input imm-input">
                                    <span class="hfield-label">Hepatitis B2</span><input type="text" name="imm_hepb2" class="hfield-input imm-input">
                                    <span class="hfield-label">Hepatitis B3</span><input type="text" name="imm_hepb3" class="hfield-input imm-input">
                                </div>
                                <div class="history-row">
                                    <span class="hfield-label">Varicella (Chicken Pox)</span><input type="text" name="imm_varicella" class="hfield-input imm-input">
                                    <span class="hfield-label">None:</span><input type="text" name="imm_children_none" class="hfield-input imm-input">
                                </div>
                            </div>
                            <!-- For Adult -->
                            <div class="immunization-col immunization-col-right">
                                <div class="immunization-col-header">For Adult</div>
                                <div class="history-row">
                                    <span class="hfield-label">HPV</span><input type="text" name="imm_hpv" class="hfield-input imm-input">
                                    <span class="hfield-label">Tetanus Toxoid</span><input type="text" name="imm_tetanus" class="hfield-input imm-input">
                                </div>
                                <div class="history-row">
                                    <span class="hfield-label">MMR</span><input type="text" name="imm_mmr" class="hfield-input imm-input">
                                </div>
                                <div class="history-row">
                                    <span class="hfield-label">Pneumococcal Vaccine:</span><input type="text" name="imm_pneumo" class="hfield-input imm-input">
                                    <span class="hfield-label">Flu Vaccine:</span><input type="text" name="imm_flu" class="hfield-input imm-input">
                                    <span class="hfield-label">Covax:</span><input type="text" name="imm_covax" class="hfield-input hfield-input-lg">
                                </div>
                                <div class="history-row">
                                    <span class="hfield-label">None:</span><input type="text" name="imm_adult_none" class="hfield-input imm-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OB-GYNE History -->
                <div class="history-section">
                    <div class="history-header" style="background:#ffe066;">OB-GYNE HISTORY (MP-Menstrual Period; BCM-Birth Control Method)</div>
                    <div class="history-body">
                        <div class="history-row">
                            <span class="hfield-label">With access to family planning counseling?</span>
                            <div class="hcheck"><input type="checkbox" name="obgyne_fp_counseling" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="obgyne_fp_counseling" value="No"><label>No</label></div>
                            <span class="hfield-label" style="margin-left:10px;">Menstrual History:</span>
                            <div class="hcheck"><input type="checkbox" name="obgyne_menstrual" value="Applicable"><label>Applicable</label></div>
                            <input type="text" name="obgyne_menstrual_applicable" class="hfield-input imm-input">
                            <div class="hcheck"><input type="checkbox" name="obgyne_menstrual" value="Not Applicable"><label>Not Applicable</label></div>
                            <input type="text" name="obgyne_menstrual_na" class="hfield-input imm-input">
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Menarche:</span>
                            <input type="text" name="obgyne_menarche" class="hfield-input imm-input">
                            <span class="hfield-label">yrs old</span>
                            <span class="hfield-label" style="margin-left:6px;">Onset of sexual intercourse:</span>
                            <input type="text" name="obgyne_sexual_onset" class="hfield-input imm-input">
                            <span class="hfield-label">yrs old</span>
                            <span class="hfield-label" style="margin-left:6px;">Menopause?</span>
                            <div class="hcheck"><input type="checkbox" name="obgyne_menopause" value="Yes"><label>Yes</label></div>
                            <div class="hcheck"><input type="checkbox" name="obgyne_menopause" value="No"><label>No</label></div>
                            <span class="hfield-label">If yes, what age?</span>
                            <input type="text" name="obgyne_menopause_age" class="hfield-input hfield-input-lg">
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Last MP:</span>
                            <input type="text" name="obgyne_last_mp" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">BCM:</span>
                            <input type="text" name="obgyne_bcm" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">Period Duration</span>
                            <input type="text" name="obgyne_period_duration" class="hfield-input imm-input">
                            <span class="hfield-label">days</span>
                            <span class="hfield-label" style="margin-left:6px;">Interval cycle</span>
                            <input type="text" name="obgyne_interval_cycle" class="hfield-input imm-input">
                            <span class="hfield-label">days</span>
                            <span class="hfield-label" style="margin-left:6px;">No. of pads during menstruation</span>
                            <input type="text" name="obgyne_pads" class="hfield-input imm-input">
                            <span class="hfield-label">/day</span>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">Pregnancy History:</span>
                            <div class="hcheck"><input type="checkbox" name="obgyne_pregnancy" value="Applicable"><label>Applicable</label></div>
                            <input type="text" name="obgyne_pregnancy_applicable" class="hfield-input imm-input">
                            <div class="hcheck"><input type="checkbox" name="obgyne_pregnancy" value="Not Applicable"><label>Not Applicable</label></div>
                            <input type="text" name="obgyne_pregnancy_na" class="hfield-input imm-input">
                            <span class="hfield-label" style="margin-left:10px;">Gravidity:</span>
                            <input type="text" name="obgyne_gravidity" class="hfield-input imm-input">
                            <span class="hfield-label" style="margin-left:6px;">Parity:</span>
                            <input type="text" name="obgyne_parity" class="hfield-input imm-input">
                            <span class="hfield-label" style="margin-left:10px;">Type (Encircle)</span>
                            <span class="hfield-label">Vaginal</span>
                            <span class="hfield-label">CS</span>
                            <span class="hfield-label">Both</span>
                        </div>
                        <div class="history-row">
                            <span class="hfield-label">No. of full term:</span>
                            <input type="text" name="obgyne_full_term" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">Premature:</span>
                            <input type="text" name="obgyne_premature" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">Abortion:</span>
                            <input type="text" name="obgyne_abortion" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">Living children:</span>
                            <input type="text" name="obgyne_living_children" class="hfield-input hfield-input-lg">
                            <span class="hfield-label" style="margin-left:6px;">(Pre-eclampsia)</span>
                            <input type="text" name="obgyne_preeclampsia" class="hfield-input hfield-input-lg">
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════ -->
                <!-- PAGE 2 — BACK OF ITR                                   -->
                <!-- ═══════════════════════════════════════════════════════ -->
                <div class="back-page">

                    <!-- NCD Risk Assessment -->
                    <div class="back-section-header">NCD RISK ASSESSMENT (for 20 years and above)</div>
                    <div class="back-body">
                        <div class="back-row">
                            <span class="bl">Eats processed/fast foods:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp;&nbsp;
                            <span class="bl">8 Servings vegetables daily:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp;&nbsp;
                            <span class="bl">2-3 servings of fruits daily:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Does at least 2.5 hours a week of moderate-intensity physical activity:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Was patient diagnosed as having diabetes?</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span> Do not Know <span class="bline bline-xs"></span>
                            &nbsp; If yes: With medication <span class="bline bline-sm"></span> Without medication <span class="bline bline-sm"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Symptoms: Polydipsia:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Polyphagia:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Polyuria:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Raised blood glucose:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">FBS/RBS: mg/dL</span> <span class="bline bline-sm"></span> mmol/L <span class="bline bline-sm"></span>
                            &nbsp; <span class="bl">Date Taken:</span> <span class="bline bline-md"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Raised Blood Lipids:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Total Cholesterol:</span> <span class="bline bline-md"></span>
                            &nbsp; <span class="bl">Date Taken:</span> <span class="bline bline-md"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Presence of Urine Ketones:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Urine Ketone:</span> <span class="bline bline-md"></span>
                            &nbsp; <span class="bl">Date Taken:</span> <span class="bline bline-md"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Presence of Protein:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span>
                            &nbsp; <span class="bl">Urine Protein:</span> <span class="bline bline-md"></span>
                            &nbsp; <span class="bl">Date Taken:</span> <span class="bline bline-md"></span>
                        </div>

                        <!-- CEC/FPE box -->
                        <div class="back-row" style="align-items:flex-start;">
                            <div style="flex:1;">
                                <div class="back-row"><span class="bl">Questionnaire to Determine Probable Angina, Heart Attack, Stroke or Transient Ischemic Attack:</span> Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row">1. Have you had any pain or discomfort or any pressure or heaviness in your chest? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span> &nbsp; If No, go to question 8</div>
                                <div class="back-row questionnaire-row">2. Do you get the pain in the center of the chest or left arm? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span> &nbsp; If No, go to question 5</div>
                                <div class="back-row questionnaire-row">3. Do you get it when you walk uphill or hurry? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row">4. Do you slowdown if you get the pain while walking? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row">5. Does the pain go away if you stand still or if you take a tablet under the tongue? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row">6. Does the pain go away in less than 10 minutes? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row">7. Have you ever had a severe chest pain across the front of your chest lasting for half an hour or more? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row" style="font-style:italic;">If the answer to Question 3 or 4 or 5 or 6 or 7 is Yes, patient have angina or heart attack and needs to see the doctor</div>
                                <div class="back-row questionnaire-row">8. Have you ever had any of the following: difficulty in talking, weakness of any leg on one side of the body or numbness on one side of the body? Yes <span class="bline bline-xs"></span> No <span class="bline bline-xs"></span></div>
                                <div class="back-row questionnaire-row" style="font-style:italic;">If the answer to question 8 is Yes, the patient may have had a TIA or stroke and needs to see the doctor.</div>
                                <div class="back-row questionnaire-row">
                                    <span class="bl">RISK LEVEL:</span>
                                    &lt;10% <span class="bline bline-xs"></span>
                                    10% to &lt;20% <span class="bline bline-xs"></span>
                                    20% to &lt;30% <span class="bline bline-xs"></span>
                                    30% to &lt;40% <span class="bline bline-xs"></span>
                                    -40% <span class="bline bline-xs"></span>
                                </div>
                            </div>
                            <div class="cec-box">
                                <div class="cec-row"><span class="bl">CEC:</span> <span class="bline bline-sm"></span></div>
                                <div class="cec-row"><span class="bl">FPE:</span> <span class="bline bline-sm"></span></div>
                                <div class="cec-row"><span class="bl">CONS:</span> <span class="bline bline-sm"></span></div>
                                <div class="cec-row"><span class="bl">LAB:</span> <span class="bline bline-sm"></span></div>
                                <div class="cec-row"><span class="bl">MEDS:</span> <span class="bline bline-sm"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pediatric -->
                    <div class="back-body" style="border-top:0.5px solid #000; padding-top:2px;">
                        <div class="back-row">
                            <span class="bl">Pediatric Client age 0-24 months</span>
                            &nbsp;&nbsp; <span class="bl">Blood Type: A</span> <span class="bline bline-xs"></span> <span class="bl">B</span> <span class="bline bline-xs"></span> <span class="bl">AB</span> <span class="bline bline-xs"></span> <span class="bl">O</span> <span class="bline bline-xs"></span> <span class="bl">Rh</span> <span class="bline bline-xs"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Length:</span> <span class="bline bline-sm"></span>
                            &nbsp; <span class="bl">Head Circumference:</span> <span class="bline bline-sm"></span>
                            &nbsp; <span class="bl">Skinfold Thickness:</span> <span class="bline bline-sm"></span>
                            &nbsp; <span class="bl">MUAC:</span> <span class="bline bline-sm"></span>
                        </div>
                        <div class="back-row">
                            <span class="bl">Pediatric Client aged 0-60 months: Z-score</span> <span class="bline bline-md"></span>
                        </div>
                    </div>

                    <!-- Pertinent Findings -->
                    <div class="back-section-header" style="background:#fff; border:0.5px solid #000; font-size:8px;">PERTINENT FINDINGS PER SYSTEM (EN-Essentially Normal) PLS ENCIRCLE</div>
                    <div class="back-body pertinent-body" style="line-height:1.6;">
                        <div class="back-row"><span class="bl">A. HEENT:</span> EN <span class="bline bline-xs"></span> Abnormal Pupillary reaction <span class="bline bline-sm"></span> Cervical lymphadenopathy <span class="bline bline-sm"></span> Dry mucous membrane <span class="bline bline-sm"></span></div>
                        <div class="back-row">Icteric sclerae <span class="bline bline-sm"></span> Pale conjunctivae <span class="bline bline-sm"></span> Sunken eyeballs <span class="bline bline-sm"></span> Sunken fontanelle <span class="bline bline-sm"></span> Others <span class="bline bline-md"></span></div>
                        <div class="back-row"><span class="bl">B. Chest / Breast / Lungs:</span> EN <span class="bline bline-xs"></span> Asymmetrical chest expansion <span class="bline bline-sm"></span> Decreased breath sounds <span class="bline bline-sm"></span> Wheezes <span class="bline bline-sm"></span></div>
                        <div class="back-row">Lumps over breast/s <span class="bline bline-sm"></span> Crackles/rales <span class="bline bline-sm"></span> Retractions <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">Heart:</span> EN <span class="bline bline-xs"></span> Displaced apex <span class="bline bline-sm"></span> Heaves/thrills/irregular rhythm <span class="bline bline-sm"></span></div>
                        <div class="back-row">Muffled heart sounds <span class="bline bline-sm"></span> Murmurs Pericardial bulge <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">D. Abdomen:</span> EN <span class="bline bline-xs"></span> Abdominal rigidity <span class="bline bline-sm"></span> Abdominal tenderness <span class="bline bline-sm"></span> Hyperactive bowel sounds <span class="bline bline-sm"></span></div>
                        <div class="back-row">Palpable mass(es) <span class="bline bline-sm"></span> Tympanitic/dull abdomen <span class="bline bline-sm"></span> Uterine Contraction <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">E. Genitourinary:</span> EN <span class="bline bline-xs"></span> Blood stain on exam finger <span class="bline bline-sm"></span> Cervical dilatation <span class="bline bline-sm"></span> Presence of Abnormal Discharge <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">F. Digital Rectal Examination:</span> EN <span class="bline bline-xs"></span> Enlarged Prostate Mass <span class="bline bline-sm"></span> Hemorrhoids <span class="bline bline-sm"></span> Pus <span class="bline bline-sm"></span> NA <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">G. Skin / Extremities:</span> EN <span class="bline bline-xs"></span> Clubbing Cold Clammy <span class="bline bline-sm"></span> Cyanosis/mottled skin <span class="bline bline-sm"></span> Edema/swelling <span class="bline bline-sm"></span></div>
                        <div class="back-row">Decreased mobility <span class="bline bline-sm"></span> Pale nailbeds <span class="bline bline-sm"></span> Poor skin turgor <span class="bline bline-sm"></span> Rashes/Petechiae <span class="bline bline-sm"></span> Weak pulses <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                        <div class="back-row"><span class="bl">H. Neurological Examination Others:</span> EN <span class="bline bline-xs"></span> Abnormal gait <span class="bline bline-sm"></span> Abnormal position sense <span class="bline bline-sm"></span> Abnormal sensation <span class="bline bline-sm"></span></div>
                        <div class="back-row">Abnormal reflex(es) <span class="bline bline-sm"></span> Poor/altered memory <span class="bline bline-sm"></span> Poor musle tone/strength <span class="bline bline-sm"></span> Poor coordination <span class="bline bline-sm"></span> Others <span class="bline bline-sm"></span></div>
                    </div>

                    <!-- Two-column consultation records -->
                    <div class="consult-cols">
                        <?php for ($col = 0; $col < 2; $col++): ?>
                        <div class="consult-col <?php echo $col === 1 ? 'consult-col-right' : ''; ?>">
                            <div style="display:flex; align-items:center; border-bottom:0.5px solid #000; padding-bottom:1mm; margin-bottom:0;">
                                <span class="bl">CC:</span>
                                <span class="bl" style="flex:1; text-align:center;">DATE:</span>
                            </div>
                            <div class="consult-field"><span class="bl">S:</span></div>
                            <div class="consult-field"><span class="bl">O:</span></div>
                            <div class="consult-field"><span class="bl">A:</span></div>
                            <div class="consult-field"><span class="bl">LABS:</span></div>
                            <div class="consult-field" style="border-bottom:none;">
                                <span class="bl">MEDS:</span>
                                <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-end;">
                                    <div style="border-top:0.5px solid #000; width:60%; margin:0 auto;"></div>
                                    <div class="medical-officer-label" style="text-align:center; font-weight:700; margin-top:1mm;">MEDICAL OFFICER</div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                </div>
                <!-- END PAGE 2 -->

                <div class="form-actions">
                    <?php if ($view_mode): ?>
                    <a href="admin_dashboard.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <?php else: ?>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Save Enrollment Record
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Form
                    </button>
                    <?php if (!$view_mode): ?>
                    <a href="admin_dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <script>
        <?php if ($view_mode && $vrow): ?>
        // Pre-fill all form fields from saved record
        document.addEventListener('DOMContentLoaded', function() {
            var data = <?php echo json_encode([
                'last_name'              => $vrow['last_name']            ?? '',
                'first_name'             => $vrow['first_name']           ?? '',
                'middle_name'            => $vrow['middle_name']          ?? '',
                'suffix'                 => $vrow['suffix']               ?? '',
                'maiden_name'            => $vrow['maiden_name']          ?? '',
                'age'                    => $vrow['age']                  ?? '',
                'sex'                    => ucfirst(strtolower($vrow['sex'] ?? '')),
                'birth_date'             => $vrow['birth_date']           ?? '',
                'contact_number'         => $vrow['contact_number']       ?? '',
                'residential_address'    => $vrow['residential_address']  ?? '',
                'civil_status'           => ucfirst(strtolower($vrow['civil_status'] ?? '')),
                'spouse_name'            => $vrow['spouse_name']          ?? '',
                'educational_attainment' => $vrow['educational_attainment'] ?? '',
                'employment_status'      => $vrow['employment_status']    ?? '',
                'dswd_nhts'              => $vrow['dswd_nhts']            ?? '',
                'four_ps_member'         => $vrow['four_ps_member']       ?? '',
                'facility_household_no'  => $vrow['facility_household_no'] ?? '',
                'household_no'           => $vrow['household_no']         ?? '',
                'philhealth_member'      => $vrow['philhealth_member']    ?? '',
                'philhealth_status_type' => $vrow['philhealth_status_type'] ?? '',
                'philhealth_no'          => $vrow['philhealth_no']        ?? '',
                'category'               => preg_replace('/^Others:.*/', 'Others', $vrow['category'] ?? ''),
                'category_other'         => $vrow['category_other']       ?? '',
                'primary_care_benefit'   => $vrow['primary_care_benefit'] ?? '',
                'family_member'          => $vrow['family_member']        ?? '',
                'mothers_name'           => $vrow['mother_name'] ?? '',
                'mode_of_transaction'    => $vrow['mode_of_transaction']  ?? '',
                'date_of_consultation'   => $vrow['date_of_consultation'] ?? '',
                'consultation_time'      => $vrow['consultation_time']    ?? '',
                'temperature'            => $vrow['temperature']          ?? '',
                'heart_rate'             => $vrow['heart_rate']           ?? '',
                'respiratory_rate'       => $vrow['respiratory_rate']     ?? '',
                'height'                 => $vrow['height']               ?? '',
                'blood_pressure'         => $vrow['blood_pressure']       ?? '',
                'weight'                 => $vrow['weight']               ?? '',
                'waist'                  => $vrow['waist']                ?? '',
                'hip'                    => $vrow['hip']                  ?? '',
                'chief_complaints'       => $vrow['chief_complaints']     ?? '',
                'diagnosis'              => $vrow['diagnosis']            ?? '',
                'medication_treatment'   => $vrow['medication_treatment'] ?? '',
                'performed_lab_tests'    => $vrow['performed_lab_tests'] ?? $vrow['performed_laboratory_test'] ?? '',
                'lab_findings_impressions' => $vrow['lab_findings_impressions'] ?? $vrow['laboratory_findings'] ?? '',
                'health_care_provider'   => $vrow['health_care_provider'] ?? $vrow['healthcare_provider_name'] ?? '',
            ]); ?>;

            var visits = <?php echo json_encode(array_values(array_filter(array_map('trim', explode(',', preg_replace('/\s*\[Ref:[^\]]*\]/', '', $vrow['purpose_of_visit'] ?? '')))))); ?>;

            // Fill text inputs and textareas
            Object.keys(data).forEach(function(name) {
                var els = document.querySelectorAll('[name="' + name + '"]');
                els.forEach(function(el) {
                    if (el.tagName === 'SELECT') {
                        el.value = data[name];
                        // If select didn't match (e.g. case mismatch), try case-insensitive
                        if (el.value !== data[name] && data[name]) {
                            Array.from(el.options).forEach(function(opt) {
                                if (opt.value.toLowerCase() === data[name].toLowerCase()) {
                                    el.value = opt.value;
                                }
                            });
                        }
                    } else if (el.tagName === 'TEXTAREA') {
                        el.value = data[name];
                    } else if (el.type === 'date') {
                        el.value = data[name]; // already YYYY-MM-DD from DB
                        // Store formatted MM/DD/YYYY for print CSS fallback
                        if (data[name]) {
                            var parts = data[name].split('-');
                            if (parts.length === 3) {
                                el.setAttribute('data-print-value', parts[1] + '/' + parts[2] + '/' + parts[0]);
                            }
                        }
                    } else {
                        el.value = data[name];
                    }
                });
            });

            // Check nature_of_visit checkboxes
            document.querySelectorAll('[name="nature_of_visit[]"]').forEach(function(cb) {
                cb.checked = visits.indexOf(cb.value) !== -1;
            });

            // Disable all inputs in view mode
            document.querySelectorAll('input, select, textarea, button[type="submit"]').forEach(function(el) {
                if (el.type !== 'submit' && el.name !== '') el.disabled = true;
            });
            document.querySelector('.btn-submit') && (document.querySelector('.btn-submit').style.display = 'none');
        });
        <?php endif; ?>

        // Fill form from selected appointment
        function fillFromAppointment() {
            var sel = document.getElementById('appointmentLookup');
            var opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) return alert('Please select an appointment first.');

            document.querySelector('[name="last_name"]').value = opt.dataset.last || '';
            document.querySelector('[name="first_name"]').value = opt.dataset.first || '';
            document.querySelector('[name="middle_name"]').value = opt.dataset.middle || '';
            document.querySelector('[name="suffix"]').value = opt.dataset.suffix || '';
            document.querySelector('[name="birth_date"]').value = opt.dataset.birth || '';
            document.querySelector('[name="sex"]').value = opt.dataset.sex || '';
            document.querySelector('[name="civil_status"]').value = opt.dataset.civil || '';
            document.querySelector('[name="residential_address"]').value = opt.dataset.address || '';
            document.querySelector('[name="contact_number"]').value = opt.dataset.contact || '';
            document.querySelector('[name="philhealth_no"]').value = opt.dataset.philhealth || '';
            document.querySelector('[name="date_of_consultation"]').value = opt.dataset.date || '';

            // Map appointment purpose to nature_of_visit checkboxes
            var purposeMap = {
                'Medical Consultation': 'Medical Consultation',
                'Animal Bite': 'Animal Bite',
                'Dental': 'Dental Care',
                'Prenatal Check-up': 'Prenatal Checkup',
                'Chest X-ray': 'Chest X-ray',
                'Laboratory': 'Laboratory',
                'TB': 'TB',
                'Drug Testing': 'Drug Testing',
                'Family Planning': 'Family Planning',
                'Social Hygiene': 'Social Hygiene'
            };
            var mappedPurpose = purposeMap[opt.dataset.purpose] || opt.dataset.purpose;
            document.querySelectorAll('[name="nature_of_visit[]"]').forEach(function(cb) {
                cb.checked = cb.value === mappedPurpose;
            });

            // Calculate age from birth date
            if (opt.dataset.birth) {
                var bd = new Date(opt.dataset.birth);
                var age = Math.floor((new Date() - bd) / 31557600000);
                document.querySelector('[name="age"]').value = age;
            }

            alert('Form pre-filled from appointment. Please review and complete remaining fields.');
        }

        function syncPrintHeader() {
            var now = new Date();

            var month = String(now.getMonth() + 1).padStart(2, '0');
            var day   = String(now.getDate()).padStart(2, '0');
            var year  = now.getFullYear();
            var dateStr = month + '/' + day + '/' + year;

            var hours   = now.getHours();
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var ampm    = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            var timeStr = String(hours).padStart(2, '0') + ':' + minutes + ' ' + ampm;

            var printDate = document.getElementById('print-date');
            var printTime = document.getElementById('print-time');
            if (printDate) printDate.textContent = dateStr;
            if (printTime) printTime.textContent = timeStr;
        }

        // Before printing: clear placeholder text so the form prints blank
        window.addEventListener('beforeprint', function() {
            document.querySelectorAll('input[placeholder]').forEach(function(el) {
                el.dataset.placeholder = el.placeholder;
                el.placeholder = '';
            });
            // Always convert date inputs to text so value prints visibly
            document.querySelectorAll('input[type="date"]').forEach(function(el) {
                el.dataset.printType = 'date';
                el.dataset.printValue = el.value;
                el.type = 'text';
                if (el.dataset.printValue) {
                    // Format YYYY-MM-DD → MM/DD/YYYY for display
                    var parts = el.dataset.printValue.split('-');
                    if (parts.length === 3) {
                        el.value = parts[1] + '/' + parts[2] + '/' + parts[0];
                    }
                }
            });
            // Convert selects to text inputs so values print visibly
            document.querySelectorAll('.form-group select').forEach(function(el) {
                var selectedText = el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
                // Skip placeholder options
                if (!el.value || el.options[el.selectedIndex].value === '') selectedText = '';
                el.dataset.printSelect = '1';
                el.dataset.printSelectValue = selectedText;
                el.style.visibility = 'hidden';
                el.style.position = 'absolute';
                // Insert a sibling span to show the value
                var span = document.createElement('span');
                span.className = 'print-select-value';
                span.textContent = selectedText;
                span.style.fontWeight = '700';
                span.style.fontSize = '10px';
                el.parentNode.insertBefore(span, el.nextSibling);
            });
        });

        window.addEventListener('afterprint', function() {
            document.querySelectorAll('input[data-placeholder]').forEach(function(el) {
                el.placeholder = el.dataset.placeholder;
                delete el.dataset.placeholder;
            });
            document.querySelectorAll('input[data-print-type]').forEach(function(el) {
                el.value = el.dataset.printValue || '';
                el.type = el.dataset.printType;
                delete el.dataset.printType;
                delete el.dataset.printValue;
            });
            // Restore selects
            document.querySelectorAll('select[data-print-select]').forEach(function(el) {
                el.style.visibility = '';
                el.style.position = '';
                delete el.dataset.printSelect;
                delete el.dataset.printSelectValue;
            });
            document.querySelectorAll('.print-select-value').forEach(function(el) {
                el.parentNode.removeChild(el);
            });
        });

        document.addEventListener('DOMContentLoaded', syncPrintHeader);
    </script>
</body>
</html>
