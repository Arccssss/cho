<?php
require_once 'config.php';
// if (!isLoggedIn()) { redirect('index.php?role=user'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BCHO Official Patient Record System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- UI DASHBOARD STYLES --- */
        body { font-family: 'Inter', sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
        .dashboard-card { max-width: 1200px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 8px solid #e67e22; }

        /* --- HEADER STYLES --- */
        /* Container for the 3 logos and text */
        .bcho-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header-logos {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .header-icon {
            height: 65px; /* Adjust based on your image resolution */
            width: auto;
        }

        .header-center-text {
            flex: 2;
            text-align: left;
            padding-left: 15px;
            max-width: 300px;
        }

        .agency-name {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
        }

        .agency-address {
            font-size: 9pt;
            margin: 0;
        }

       .document-title {
    font-family: 'Arial Black', sans-serif; /* Heavy block weight to match the ink */
    font-size: 13pt; /* Reduced from 16pt to make it smaller */
    font-weight: 900;
    text-align: center;
    margin-top: 2px;
    margin-bottom: 2px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    /* Removed border-bottom to eliminate the decorative line */
    display: block;
    width: 100%;
}
        .header-right-tracking {
            flex: 1;
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
        }

        /* Sub-header (PhilHealth, Yakap, Date) */
        .subheader-details {
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        .dashed-line {
            letter-spacing: 2px;
        }

        .checkbox-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            margin-right: 5px;
        }
        .form-section { margin-bottom: 25px; padding: 20px; border: 1px solid #edf2f7; border-radius: 12px; }
        .form-section h3 { font-size: 13px; text-transform: uppercase; color: #718096; margin-bottom: 20px; border-left: 4px solid #e67e22; padding-left: 10px; }
        
        /* Grid Layouts for Dashboard */
        .grid-name { display: grid; grid-template-columns: 0.6fr 1.2fr 1.2fr 1.2fr 0.6fr; gap: 15px; margin-bottom: 15px; }
        .grid-stats { display: grid; grid-template-columns: 1fr 0.5fr 1fr 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        
        .field-box { display: flex; flex-direction: column; }
        .field-box label { font-size: 11px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }
        .field-box input, .field-box select { padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; }
        
        .membership-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; background: #f8fafc; padding: 15px; border-radius: 8px; }
        .m-opt { font-size: 12px; display: flex; align-items: center; gap: 7px; cursor: pointer; }

        .btn-print { background: #27ae60; color: white; padding: 18px; border: none; width: 100%; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }
        
        .btn-save { background: #28a745; color: white; padding: 18px; border: none; width: 100%; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }
        
        .btn-view { background: #6c757d; color: white; padding: 18px; border: none; width: 100%; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }
        
        .btn-back { background: #e74c3c; color: white; padding: 18px; border: none; width: 100%; border-radius: 8px; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }

        /* Tracking field styling */
        .tracking-input {
            display: inline-block;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: monospace;
            font-size: 10pt;
            width: 120px;
            outline: none;
        }

        /* PhilHealth ID input styling */
        .philhealth-input {
            display: inline-block;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: monospace;
            font-size: 10pt;
            width: 50px;
            outline: none;
            text-align: center;
        }

        .philhealth-separator {
            margin: 0 5px;
            font-weight: bold;
        }

        

        .tracking-input:focus {
            outline: none;
            border-bottom-color: #0066cc;
        }

        /* --- PRINT REPLICA (Physical Form Layout) --- */
        #bcho-hardcopy { display: none; }

        @media print {
            @page { size: portrait; margin: 0.3in; }
            .dashboard-card { display: none !important; }
            #bcho-hardcopy { display: block !important; width: 100%; color: black; font-family: Arial, sans-serif; }
            
            .hc-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: -1px; }
            .hc-table td { border: 1px solid black; padding: 2px 5px; vertical-align: top; height: 40px; }
            .label { font-size: 6pt; font-weight: bold; display: block; text-transform: uppercase; line-height: 1; margin-bottom: 2px; }
            .value { font-size: 10pt; font-weight: bold; display: inline; text-transform: uppercase; }
            
            .checklist { font-size: 6.5pt; display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; margin-top: 3px; }
            .box { width: 9px; height: 9px; border: 1px solid black; display: inline-block; position: relative; top: 1px; }
            .box.checked::after { content: '✔'; position: absolute; top: -5px; left: 0px; font-size: 10pt; }
        }
    </style>
</head>
<body>

<div class="dashboard-card">
    <div class="form-section">
        <h3><i class="fas fa-user-edit"></i> Patient Identity (Grouped)</h3>
        <div class="grid-name">
            <div class="field-box">
                <label>Prefix</label>
                <select id="in_pre"><option value="">None</option><option value="Mr.">Mr.</option><option value="Ms.">Ms.</option><option value="Mrs.">Mrs.</option><option value="Dr.">Dr.</option><option value="Atty.">Atty.</option><option value="Engr.">Engr.</option><option value="N/A">N/A</option></select>
            </div>
            <div class="field-box"><label>Last Name</label><input type="text" id="in_ln"></div>
            <div class="field-box"><label>First Name</label><input type="text" id="in_fn"></div>
            <div class="field-box"><label>Middle Name</label><input type="text" id="in_mn"></div>
            <div class="field-box">
                <label>Suffix</label>
                <select id="in_suffix"><option value="">None</option><option value="Jr.">Jr.</option><option value="Sr.">Sr.</option><option value="II">II</option><option value="III">III</option></select>
            </div>
        </div>

        <div class="grid-stats">
            <div class="field-box"><label>Date of Birth</label><input type="date" id="in_dob"></div>
            <div class="field-box"><label>Age</label><input type="number" id="in_age"></div>
            <div class="field-box">
                <label>Sex</label>
                <select id="in_sex"><option value="MALE">MALE</option><option value="FEMALE">FEMALE</option></select>
            </div>
            <div class="field-box">
                <label>Civil Status</label>
                <select id="in_status"><option>SINGLE</option><option>MARRIED</option><option>WIDOWED</option><option>SEPARATED</option></select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>Header Information</h3>
    <div class="grid-stats">
        <div class="field-box">
            <label>PhilHealth ID</label>
            <div style="display: flex; gap: 5px;">
                <input type="text" id="in_ph1" maxlength="2" style="width: 30px;" placeholder="00">
                <input type="text" id="in_ph2" maxlength="9" style="width: 80px;" placeholder="000000000">
                <input type="text" id="in_ph3" maxlength="1" style="width: 25px;" placeholder="0">
            </div>
        </div>

        <div class="field-box">
            <label>YAKAP Center</label>
            <select id="in_yakap_center">
                <option value="">Select Center</option>
                <option value="BCHO">BCHO</option>
                <option value="OTHERS">OTHERS</option>
            </select>
        </div>
        <div class="field-box" id="others_container" style="display:none;">
            <label>Specify Other Center</label>
            <input type="text" id="in_yakap_others">
        </div>

        <div class="field-box">
            <label>Contact Number</label>
            <input type="text" id="in_contact">
        </div>
    </div>
</div>


    <div class="form-section">
        <h3><i class="fas fa-user-graduate"></i> Educational & Demographic Information</h3>
        <div class="grid-3">
            <div class="field-box">
                <label>Educational Attainment</label>
                <select id="in_edu">
                    <option value="None">None</option>
                    <option value="Pre-School">Pre-School</option>
                    <option value="Elementary">Elementary</option>
                    <option value="High School">High School</option>
                    <option value="Senior High">Senior High</option>
                    <option value="College">College</option>
                    <option value="Vocational">Vocational</option>
                    <option value="ALS">ALS</option>
                    <option value="POST-GRADUATE">POST-GRADUATE</option>
                </select>
            </div>
            <div class="field-box">
                <label>Employment Status</label>
                <select id="in_emp">
                    <option>Employed</option><option>Self-Employed</option><option>Unemployed</option><option>Retired</option>
                </select>
            </div>
            <div class="field-box">
                <label>Religion</label>
                <select id="in_religion">
                    <option value="Roman Catholic">Roman Catholic</option>
                    <option value="Islam">Islam</option>
                    <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                    <option value="Seventh-day Adventist">Seventh-day Adventist</option>
                    <option value="Aglipay Philippine Independent Church">Aglipay Philippine Independent Church</option>
                    <option value="Bible Baptist Church">Bible Baptist Church</option>
                    <option value="United Church of Christ in the Philippines">United Church of Christ in the Philippines</option>
                    <option value="Jehovah's Witness">Jehovah's Witness</option>
                    <option value="Church of Jesus Christ of Latter-day Saints">Church of Jesus Christ of Latter-day Saints</option>
                    <option value="Others">Others</option>
                </select>
            </div>
        </div>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>Blood Type</label>
                <select id="in_bt"><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select>
            </div>
            <div class="field-box">
                <label>City</label>
                <select id="in_city" onchange="updateBarangays()">
                    <option value="">Select City/Municipality</option>
                    
                    <!-- Negros Island Region Cities -->
                    <optgroup label="Negros Island Region">
                        <option value="Bacolod">Bacolod City</option>
                        <option value="Bago">Bago City</option>
                        <option value="Cadiz">Cadiz City</option>
                        <option value="Escalante">Escalante City</option>
                        <option value="Himamaylan">Himamaylan City</option>
                        <option value="Kabankalan">Kabankalan City</option>
                        <option value="La Carlota">La Carlota City</option>
                        <option value="Sagay">Sagay City</option>
                        <option value="San Carlos">San Carlos City</option>
                        <option value="Silay">Silay City</option>
                        <option value="Sipalay">Sipalay City</option>
                        <option value="Talisay">Talisay City</option>
                        <option value="Victorias">Victorias City</option>
                        <option value="Murcia">Murcia</option>
                        <option value="Binalbagan">Binalbagan</option>
                        <option value="Calatrava">Calatrava</option>
                        <option value="Candoni">Candoni</option>
                        <option value="Cauayan">Cauayan</option>
                        <option value="E.B. Magalona">E.B. Magalona</option>
                        <option value="Hinigaran">Hinigaran</option>
                        <option value="Hinobaan">Hinobaan</option>
                        <option value="Ilog">Ilog</option>
                        <option value="Isabela">Isabela</option>
                        <option value="La Castellana">La Castellana</option>
                        <option value="Manapla">Manapla</option>
                        <option value="Moises Padilla">Moises Padilla</option>
                        <option value="Pontevedra">Pontevedra</option>
                        <option value="Pulupandan">Pulupandan</option>
                        <option value="Don Salvador Benedicto">Don Salvador Benedicto</option>
                        <option value="San Enrique">San Enrique</option>
                        <option value="Toboso">Toboso</option>
                        <option value="Valladolid">Valladolid</option>
                    </optgroup>
                </select>
            </div>
            <div class="field-box">
                <label>Barangay</label>
                <select id="in_brgy">
                    <option value="">Select Barangay</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fas fa-users"></i> Family Member Information</h3>
        <div class="grid-3">
            <div class="field-box">
                <label>Family Member</label>
                <select id="in_family_member">
                    <option value="">Select Relationship</option>
                    <option value="Head of Family">Head of Family</option>
                    <option value="Father">Father</option>
                    <option value="Mother">Mother</option>
                    <option value="Daughter">Daughter</option>
                    <option value="Son">Son</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Husband">Husband</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="field-box">
                <label>Facility Household Number</label>
                <input type="text" id="in_household_num" placeholder="Enter household number">
            </div>
            <div class="field-box">
                <label>DSWD 4Ps Member</label>
                <select id="in_4ps_member">
                    <option value="NO">NO</option>
                    <option value="YES">YES</option>
                </select>
            </div>
        </div>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>Family Serial Number</label>
                <input type="text" id="in_family_serial" placeholder="Enter family serial number">
            </div>
            <div class="field-box">
                <label>PWD</label>
                <select id="in_pwd">
                    <option value="NO">NO</option>
                    <option value="YES">YES</option>
                </select>
            </div>
            <div class="field-box">
                <!-- Empty field for layout balance -->
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fas fa-id-card"></i> PHILHEALTH MEMBER</h3>
        <div class="grid-3">
            <div class="field-box">
                <label>PHILHEALTH MEMBER</label>
                <select id="in_philhealth_member">
                    <option value="NO">NO</option>
                    <option value="YES">YES</option>
                </select>
            </div>
            <div class="field-box">
                <label>PHILHEALTH STATUS</label>
                <select id="in_philhealth_status">
                    <option value="MEMBER">MEMBER</option>
                    <option value="DEPENDENT">DEPENDENT</option>
                </select>
            </div>
            <div class="field-box">
                <label>PHILHEALTH CATEGORY</label>
                <select id="in_philhealth_category">
                    <option value="">Select Category</option>
                    <option value="DIRECT">DIRECT</option>
                    <option value="INDIRECT">INDIRECT</option>
                    <option value="FORMAL ECONOMY">FORMAL ECONOMY</option>
                    <option value="INFORMAL ECONOMY">INFORMAL ECONOMY</option>
                    <option value="OFW">OFW</option>
                    <option value="SELF-EARNING">SELF-EARNING</option>
                    <option value="LIFETIME">LIFETIME</option>
                    <option value="SENIOR CITIZEN">SENIOR CITIZEN</option>
                    <option value="INDIGENT">INDIGENT</option>
                    <option value="SPONSORED">SPONSORED</option>
                </select>
            </div>
        </div>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>YAKAP REGISTERED</label>
                <select id="in_yakap_registered">
                    <option value="NO">NO</option>
                    <option value="YES">YES</option>
                </select>
            </div>
            <div class="field-box">
                <!-- Empty field for layout balance -->
            </div>
            <div class="field-box">
                <!-- Empty field for layout balance -->
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fas fa-heartbeat"></i> Vital Signs & Measurements</h3>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>BP (Blood Pressure)</label>
                <input type="text" id="in_bp" placeholder="120/80">
            </div>
            <div class="field-box">
                <label>PR (Pulse Rate)</label>
                <input type="number" id="in_pr" placeholder="72">
            </div>
            <div class="field-box">
                <label>RR (Respiratory Rate)</label>
                <input type="number" id="in_rr" placeholder="16">
            </div>
        </div>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>TEMP (Temperature)</label>
                <input type="text" id="in_temp" placeholder="36.5°C">
            </div>
            <div class="field-box">
                <label>HEIGHT</label>
                <input type="text" id="in_height" placeholder="cm">
            </div>
            <div class="field-box">
                <label>WEIGHT</label>
                <input type="text" id="in_weight" placeholder="kg">
            </div>
            <div class="field-box">
                <label>WAIST CIRC</label>
                <input type="text" id="in_waist_circ" placeholder="cm">
            </div>
        </div>
        <div class="grid-3" style="margin-top: 15px;">
            <div class="field-box">
                <label>HIP CIRC</label>
                <input type="text" id="in_hip_circ" placeholder="cm">
            </div>
            <div class="field-box">
                <!-- Empty field for layout balance -->
            </div>
            <div class="field-box">
                <!-- Empty field for layout balance -->
            </div>
        </div>
    </div>

    
    <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button class="btn-back" onclick="goToDashboard()">
            <i class="fas fa-arrow-left"></i> BACK TO DASHBOARD
        </button>
        <button class="btn-save" onclick="savePatientRecord()">
            <i class="fas fa-save"></i> SAVE RECORD
        </button>
        <button class="btn-view" onclick="viewSavedRecords()">
            <i class="fas fa-list"></i> VIEW RECORDS
        </button>
        <button class="btn-print" onclick="handleHardcopyPrint()">
            <i class="fas fa-print"></i> PRINT PATIENT RECORD
        </button>
    </div>
</div>

<div id="bcho-hardcopy">
    <div class="bcho-header-container">
    <div class="header-logos">
        <img src="images/doh.png" alt="DOH Logo" class="header-icon">
        <img src="images/bcdseal.png" alt="Bacolod Logo" class="header-icon">
        <img src="images/bcd.jpg" alt="CHO Logo" class="header-icon">
    </div>

    <div class="header-center-text">
        <p class="agency-name">BACOLOD CITY HEALTH OFFICE</p>
        <p class="agency-address">A.B. PARRENO ST., BRGY 20</p>
        <h1 class="document-title">PATIENT MEDICAL RECORD</h1>
    </div>

    <div class="header-right-tracking">
        <div class="tracking-line">FPE: <span id="fpe-display" class="tracking-input">______________</span></div>
        <div class="tracking-line">CONS: <span id="cons-display" class="tracking-input">_____________</span></div>
    </div>
</div>

<div class="subheader-details">
    <div class="details-left">
        <p>PhilHealth ID Number: <span id="out_philhealth" class="value">____-_________-__</span></p>
        <p>YAKAP Center: 
            <span class="checkbox-box" id="out_check_bcho"></span> BCHO 
            <span class="checkbox-box" id="out_check_others"></span> OTHERS 
            <span id="out_others_text" class="value">________________</span>
        </p>
        <p>Contact Number: <span id="out_contact" class="value">_________________________</span></p>
    </div>
    <div class="details-right">
        <p>DATE: <span id="out_date" class="value">________________</span></p>
        <p>TIME: <span id="out_time" class="value">________________</span></p>
    </div>
</div>

    <table class="hc-table">
        <tr>
            <td width="10%"><span class="label">PREFIX</span><span id="out_pre" class="value"></span></td>
            <td width="25%"><span class="label">LAST NAME</span><span id="out_ln" class="value"></span></td>
            <td width="25%"><span class="label">FIRST NAME</span><span id="out_fn" class="value"></span></td>
            <td width="25%"><span class="label">MIDDLE NAME</span><span id="out_mn" class="value"></span></td>
            <td width="15%"><span class="label">SUFFIX</span><span id="out_suffix" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="25%"><span class="label">DATE OF BIRTH</span><span id="out_dob" class="value"></span></td>
            <td width="10%"><span class="label">AGE</span><span id="out_age" class="value"></span></td>
            <td width="20%"><span class="label">SEX</span><span id="out_sex" class="value"></span></td>
            <td width="25%"><span class="label">CIVIL STATUS</span><span id="out_status" class="value"></span></td>
            <td width="20%"><span class="label">BLOOD TYPE</span><span id="out_bt" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="33%"><span class="label">EDUCATIONAL ATTAINMENT</span><span id="out_edu" class="value"></span></td>
            <td width="33%"><span class="label">EMPLOYMENT STATUS</span><span id="out_emp" class="value"></span></td>
            <td width="34%"><span class="label">BARANGAY</span><span id="out_brgy" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="50%"><span class="label">RELIGION</span><span id="out_religion" class="value"></span></td>
            <td width="50%"><span class="label">CITY & MUNICIPALITY</span><span id="out_city" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="25%"><span class="label">FAMILY MEMBER</span><span id="out_family_member" class="value"></span></td>
            <td width="25%"><span class="label">HOUSEHOLD NUMBER</span><span id="out_household_num" class="value"></span></td>
            <td width="25%"><span class="label">DSWD 4PS MEMBER</span><span id="out_4ps_member" class="value"></span></td>
            <td width="25%"><span class="label">FAMILY SERIAL NUMBER</span><span id="out_family_serial" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="50%"><span class="label">PWD</span><span id="out_pwd" class="value"></span></td>
            <td width="50%"><span class="label">PHILHEALTH MEMBER</span><span id="out_philhealth_member" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="50%"><span class="label">PHILHEALTH STATUS</span><span id="out_philhealth_status" class="value"></span></td>
            <td width="50%"><span class="label">PHILHEALTH CATEGORY</span><span id="out_philhealth_category" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="50%"><span class="label">YAKAP REGISTERED</span><span id="out_yakap_registered" class="value"></span></td>
            <td width="50%"><span class="label">FACILITY HOUSEHOLD NUMBER</span><span id="out_household_num" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="25%"><span class="label">BP</span><span id="out_bp" class="value"></span></td>
            <td width="25%"><span class="label">PR</span><span id="out_pr" class="value"></span></td>
            <td width="25%"><span class="label">TEMP (°C)</span><span id="out_temp" class="value"></span></td>
            <td width="25%"><span class="label">RR (mm)</span><span id="out_rr" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td width="25%"><span class="label">HEIGHT (cm)</span><span id="out_height" class="value"></span></td>
            <td width="25%"><span class="label">WEIGHT (kg)</span><span id="out_weight" class="value"></span></td>
            <td width="25%"><span class="label">WAIST CIRC (cm)</span><span id="out_waist_circ" class="value"></span></td>
            <td width="25%"><span class="label">HIP CIRC (cm)</span><span id="out_hip_circ" class="value"></span></td>
        </tr>
    </table>

    <table class="hc-table">
        <tr>
            <td>
                <span class="label">PHILHEALTH MEMBERSHIP CATEGORY:</span>
                <div class="checklist">
                    <span class="check-item"><span class="box" id="p_m_member"></span> Member</span>
                    <span class="check-item"><span class="box" id="p_m_dependent"></span> Dependent</span>
                    <span class="check-item"><span class="box" id="p_m_indigent"></span> Indigent</span>
                    <span class="check-item"><span class="box" id="p_box_4ps"></span> 4Ps Recipient</span>
                    <span class="check-item"><span class="box" id="p_m_employed"></span> Employed</span>
                    <span class="check-item"><span class="box" id="p_m_senior"></span> Senior Citizen</span>
                    <span class="check-item"><span class="box" id="p_m_lifetime"></span> Lifetime</span>
                </div>
            </td>
        </tr>
    </table>
</div>

<script>
// Auto-calculate age from date of birth
document.getElementById('in_dob').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    document.getElementById('in_age').value = age;
});

// Functions to handle tracking field inputs
function updateTrackingField(fieldId, value) {
    const element = document.getElementById(fieldId);
    if (element) {
        element.innerText = value;
    }
}

function updatePhilHealthId(part1, part2, part3) {
    const philHealthIdElement = document.querySelector('.philhealth-id-display');
    if (philHealthIdElement) {
        philHealthIdElement.innerHTML = `${part1} - ${part2} - ${part3}`;
    }
}

function updateYakapCenter(center) {
    const yakapElement = document.querySelector('.yakap-center-display');
    if (yakapElement) {
        // Clear previous selections
        const checkboxes = yakapElement.querySelectorAll('.checkbox-box');
        checkboxes.forEach(cb => cb.classList.remove('checked'));
        
        // Check the appropriate box
        if (center === 'BCHO') {
            checkboxes[0].classList.add('checked');
        } else if (center === 'OTHERS') {
            checkboxes[1].classList.add('checked');
        }
    }
}

// Initialize tracking fields
document.addEventListener('DOMContentLoaded', function() {
    const syncFields = () => {
        // 1. PhilHealth ID Sync
        const ph1 = document.getElementById('in_ph1').value;
        const ph2 = document.getElementById('in_ph2').value;
        const ph3 = document.getElementById('in_ph3').value;
        document.getElementById('out_philhealth').innerText = (ph1 || ph2 || ph3) ? `${ph1}-${ph2}-${ph3}` : "____-_________-__";

        // 2. YAKAP Center & Checkboxes
        const center = document.getElementById('in_yakap_center').value;
        const othersInput = document.getElementById('in_yakap_others').value;
        const othersContainer = document.getElementById('others_container');

        // Reset display boxes
        document.getElementById('out_check_bcho').innerHTML = "";
        document.getElementById('out_check_others').innerHTML = "";
        document.getElementById('out_others_text').innerText = "________________";

        if (center === "BCHO") {
            document.getElementById('out_check_bcho').innerHTML = "✔";
            othersContainer.style.display = "none";
        } else if (center === "OTHERS") {
            document.getElementById('out_check_others').innerHTML = "✔";
            document.getElementById('out_others_text').innerText = othersInput;
            othersContainer.style.display = "block";
        }

        // 3. Contact Number
        const contact = document.getElementById('in_contact').value;
        document.getElementById('out_contact').innerText = contact || "_________________________";
    };

    // Attach listeners to all inputs
    const inputs = ['in_ph1', 'in_ph2', 'in_ph3', 'in_yakap_center', 'in_yakap_others', 'in_contact'];
    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', syncFields);
    });
});

// Initialize tracking fields
document.addEventListener('DOMContentLoaded', function() {
    // Set up tracking field event listeners
    const fpeField = document.querySelector('.tracking-line:nth-child(1)');
    const consField = document.querySelector('.tracking-line:nth-child(2)');
    const dateField = document.querySelector('.date-field');
    const timeField = document.querySelector('.time-field');
    
    if (fpeField) {
        fpeField.addEventListener('input', function() {
            updateTrackingField('fpe-display', this.value);
        });
    }
    
    if (consField) {
        consField.addEventListener('input', function() {
            updateTrackingField('cons-display', this.value);
        });
    }
    
    if (dateField) {
        dateField.addEventListener('input', function() {
            updateTrackingField('date-display', this.value);
        });
    }
    
    if (timeField) {
        timeField.addEventListener('input', function() {
            updateTrackingField('time-display', this.value);
        });
    }
});

function handleHardcopyPrint() {
    console.log('Print function triggered'); // Debug line
    
    try {
        // 1. Sync header fields first
        console.log('Syncing header fields...');
        
        // PhilHealth ID Sync
        const ph1 = document.getElementById('in_ph1');
        const ph2 = document.getElementById('in_ph2');
        const ph3 = document.getElementById('in_ph3');
        const outPhilhealth = document.getElementById('out_philhealth');
        
        if (ph1 && ph2 && ph3 && outPhilhealth) {
            const philHealthValue = (ph1.value || ph2.value || ph3.value) ? `${ph1.value}-${ph2.value}-${ph3.value}` : "____-_________-__";
            outPhilhealth.innerText = philHealthValue;
            console.log('PhilHealth ID synced:', philHealthValue);
        } else {
            console.error('PhilHealth elements not found:', {ph1, ph2, ph3, outPhilhealth});
        }

        // YAKAP Center & Checkboxes
        const center = document.getElementById('in_yakap_center');
        const othersInput = document.getElementById('in_yakap_others');
        const othersContainer = document.getElementById('others_container');
        const outCheckBcho = document.getElementById('out_check_bcho');
        const outCheckOthers = document.getElementById('out_check_others');
        const outOthersText = document.getElementById('out_others_text');

        if (center && outCheckBcho && outCheckOthers && outOthersText) {
            // Reset display boxes
            outCheckBcho.innerHTML = "";
            outCheckOthers.innerHTML = "";
            outOthersText.innerText = "________________";

            if (center.value === "BCHO") {
                outCheckBcho.innerHTML = "✔";
                if (othersContainer) othersContainer.style.display = "none";
                console.log('YAKAP Center set to BCHO');
            } else if (center.value === "OTHERS") {
                outCheckOthers.innerHTML = "✔";
                outOthersText.innerText = othersInput ? othersInput.value : "";
                if (othersContainer) othersContainer.style.display = "block";
                console.log('YAKAP Center set to OTHERS');
            }
        } else {
            console.error('YAKAP elements not found:', {center, outCheckBcho, outCheckOthers, outOthersText});
        }

        // Contact Number
        const contact = document.getElementById('in_contact');
        const outContact = document.getElementById('out_contact');
        if (contact && outContact) {
            outContact.innerText = contact.value || "_________________________";
            console.log('Contact Number synced:', contact.value);
        } else {
            console.error('Contact elements not found:', {contact, outContact});
        }
        
        // 2. Handle regular patient fields
        console.log('Syncing regular patient fields...');
        const fields = ['pre','ln','fn','mn','suffix','dob','age','sex','status','bt','edu','emp','religion','city','brgy','family_member','household_num','4ps_member','family_serial','pwd','philhealth_member','philhealth_status','philhealth_category','yakap_registered','bp','pr','temp','height','weight','waist_circ','hip_circ','rr'];
        
        fields.forEach(f => {
            const inputElement = document.getElementById('in_' + f);
            const outputElement = document.getElementById('out_' + f);
            if (inputElement && outputElement) {
                outputElement.innerText = inputElement.value;
                console.log('Field ' + f + ': ' + inputElement.value + ' -> ' + outputElement.innerText);
            } else {
                console.warn('Field not found:', f, {inputElement, outputElement});
            }
        });

        // Reset and check boxes
        console.log('Resetting checkboxes...');
        document.querySelectorAll('.box').forEach(b => b.classList.remove('checked'));
        const selected = document.querySelector('input[name="m_cat"]:checked');
        if(selected && document.getElementById('p_' + selected.value)) {
            document.getElementById('p_' + selected.value).classList.add('checked');
        }
        if(document.getElementById('in_4ps') && document.getElementById('in_4ps').value === "YES" && document.getElementById('p_box_4ps')) {
            document.getElementById('p_box_4ps').classList.add('checked');
        }

        console.log('Opening print dialog'); // Debug print
        window.print();
        
    } catch (error) {
        console.error('Error in print function:', error);
        alert('Error printing patient record. Please check the console for details.');
    }
}

// Save Patient Record Function
function savePatientRecord() {
    console.log('Saving patient record...'); // Debug save
    
    // Collect all form data
    const patientData = {
        // Patient Identity
        prefix: document.getElementById('in_pre').value,
        lastName: document.getElementById('in_ln').value,
        firstName: document.getElementById('in_fn').value,
        middleName: document.getElementById('in_mn').value,
        suffix: document.getElementById('in_suffix').value,
        dateOfBirth: document.getElementById('in_dob').value,
        age: document.getElementById('in_age').value,
        sex: document.getElementById('in_sex').value,
        civilStatus: document.getElementById('in_status').value,
        bloodType: document.getElementById('in_bt').value,
        
        // Educational & Demographic
        educationalAttainment: document.getElementById('in_edu').value,
        employmentStatus: document.getElementById('in_emp').value,
        religion: document.getElementById('in_religion').value,
        city: document.getElementById('in_city').value,
        barangay: document.getElementById('in_brgy').value,
        
        // Header Information
        philHealthId: {
            part1: document.getElementById('in_ph1').value,
            part2: document.getElementById('in_ph2').value,
            part3: document.getElementById('in_ph3').value
        },
        yakapCenter: document.getElementById('in_yakap_center').value,
        yakapOthers: document.getElementById('in_yakap_others').value,
        contactNumber: document.getElementById('in_contact').value,
        
        // Family Member Information
        familyMember: document.getElementById('in_family_member').value,
        householdNumber: document.getElementById('in_household_num').value,
        fourpsMember: document.getElementById('in_4ps_member').value,
        familySerial: document.getElementById('in_family_serial').value,
        pwd: document.getElementById('in_pwd').value,
        
        // PhilHealth Member Information
        philhealthMember: document.getElementById('in_philhealth_member').value,
        philhealthStatus: document.getElementById('in_philhealth_status').value,
        philhealthCategory: document.getElementById('in_philhealth_category').value,
        yakapRegistered: document.getElementById('in_yakap_registered').value,
        
        // Vital Signs & Measurements
        bloodPressure: document.getElementById('in_bp').value,
        pulseRate: document.getElementById('in_pr').value,
        temperature: document.getElementById('in_temp').value,
        height: document.getElementById('in_height').value,
        weight: document.getElementById('in_weight').value,
        waistCircumference: document.getElementById('in_waist_circ').value,
        hipCircumference: document.getElementById('in_hip_circ').value,
        respiratoryRate: document.getElementById('in_rr').value,
        
        // Metadata
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
    };
    
    // Validate required fields
    const requiredFields = ['lastName', 'firstName', 'dateOfBirth', 'sex'];
    const missingFields = requiredFields.filter(field => !patientData[field] || patientData[field].trim() === '');
    
    if (missingFields.length > 0) {
        alert('Please fill in required fields: ' + missingFields.join(', '));
        return false;
    }
    
    // Save to localStorage (for demo purposes)
    try {
        // Get existing records
        const existingRecords = JSON.parse(localStorage.getItem('patientRecords') || '[]');
        
        // Add new record
        const newRecord = {
            id: Date.now(),
            ...patientData,
            status: 'active'
        };
        
        existingRecords.push(newRecord);
        
        // Save to localStorage
        localStorage.setItem('patientRecords', JSON.stringify(existingRecords));
        
        console.log('Patient record saved:', newRecord);
        alert('Patient record saved successfully!');
        
        // Clear form after save
        clearForm();
        
        return true;
    } catch (error) {
        console.error('Error saving patient record:', error);
        alert('Error saving patient record. Please try again.');
        return false;
    }
}

// Clear Form Function
function clearForm() {
    // Clear all input fields
    const inputFields = document.querySelectorAll('input, select');
    inputFields.forEach(field => {
        if (field.type === 'select') {
            field.selectedIndex = 0;
        } else {
            field.value = '';
        }
    });
    
    console.log('Form cleared');
}

// View Saved Records Function
function viewSavedRecords() {
    try {
        const records = JSON.parse(localStorage.getItem('patientRecords') || '[]');
        
        if (records.length === 0) {
            alert('No saved records found.');
            return;
        }
        
        console.log('Found', records.length, 'saved records');
        
        // Create modal overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
        `;
        
        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            width: 90%;
            max-width: 1200px;
            height: 80vh;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        `;
        
        // Create header
        const modalHeader = document.createElement('div');
        modalHeader.style.cssText = `
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        `;
        modalHeader.innerHTML = `
            <h2 style="margin: 0; font-size: 24px;">
                <i class="fas fa-users"></i> SAVED PATIENT RECORDS (${records.length})
            </h2>
            <button onclick="closeRecordsModal()" style="
                background: rgba(255,255,255,0.2);
                border: 2px solid white;
                color: white;
                padding: 8px 16px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
            ">✕ CLOSE</button>
        `;
        
        // Create content area
        const modalBody = document.createElement('div');
        modalBody.style.cssText = `
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        `;
        
        // Create records list
        let recordsHTML = '<div style="display: grid; gap: 20px;">';
        
        records.forEach((record, index) => {
            const recordDate = new Date(record.createdAt).toLocaleDateString();
            const recordTime = new Date(record.createdAt).toLocaleTimeString();
            
            recordsHTML += `
                <div style="
                    border: 2px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    background: #f8fafc;
                    transition: all 0.3s ease;
                " onmouseover="this.style.borderColor='#667eea'; this.style.boxShadow='0 4px 12px rgba(102,126,234,0.15)'" 
                   onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0 0 10px 0; color: #2d3748; font-size: 20px;">
                                ${record.prefix ? record.prefix + ' ' : ''}${record.firstName} ${record.lastName} ${record.suffix || ''}
                            </h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 14px; color: #4a5568;">
                                <span><strong>Date of Birth:</strong> ${record.dateOfBirth}</span>
                                <span><strong>Age:</strong> ${record.age}</span>
                                <span><strong>Sex:</strong> ${record.sex}</span>
                                <span><strong>Civil Status:</strong> ${record.civilStatus}</span>
                                <span><strong>Blood Type:</strong> ${record.bloodType || 'N/A'}</span>
                                <span><strong>Contact:</strong> ${record.contactNumber || 'N/A'}</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; color: #718096; margin-bottom: 10px;">
                                <div>Created: ${recordDate}</div>
                                <div>Time: ${recordTime}</div>
                                <div>Status: <span style="color: #48bb78; font-weight: bold;">${record.status}</span></div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="loadPatientRecord(${record.id})" style="
                                    background: #4299e1;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    font-weight: 600;
                                ">📋 LOAD</button>
                                <button onclick="printPatientRecord(${record.id})" style="
                                    background: #48bb78;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    font-weight: 600;
                                ">🖨️ PRINT</button>
                                <button onclick="deletePatientRecord(${record.id})" style="
                                    background: #f56565;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 12px;
                                    font-weight: 600;
                                ">🗑️ DELETE</button>
                            </div>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #2d3748; font-size: 16px;">Medical Information</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px; color: #4a5568;">
                            <span><strong>PhilHealth ID:</strong> ${record.philHealthId.part1}-${record.philHealthId.part2}-${record.philHealthId.part3}</span>
                            <span><strong>YAKAP Center:</strong> ${record.yakapCenter || 'N/A'}</span>
                            <span><strong>Education:</strong> ${record.educationalAttainment}</span>
                            <span><strong>Employment:</strong> ${record.employmentStatus}</span>
                            <span><strong>Religion:</strong> ${record.religion}</span>
                            <span><strong>City:</strong> ${record.city}</span>
                            <span><strong>Barangay:</strong> ${record.barangay}</span>
                            <span><strong>4Ps Member:</strong> ${record.fourpsMember}</span>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                        <h4 style="margin: 0 0 10px 0; color: #2d3748; font-size: 16px;">Vital Signs</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 13px; color: #4a5568;">
                            <span><strong>BP:</strong> ${record.bloodPressure || 'N/A'}</span>
                            <span><strong>PR:</strong> ${record.pulseRate || 'N/A'}</span>
                            <span><strong>Temp:</strong> ${record.temperature || 'N/A'}</span>
                            <span><strong>Height:</strong> ${record.height || 'N/A'}</span>
                            <span><strong>Weight:</strong> ${record.weight || 'N/A'}</span>
                            <span><strong>Waist:</strong> ${record.waistCircumference || 'N/A'}</span>
                            <span><strong>Hip:</strong> ${record.hipCircumference || 'N/A'}</span>
                            <span><strong>RR:</strong> ${record.respiratoryRate || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        recordsHTML += '</div>';
        modalBody.innerHTML = recordsHTML;
        
        // Assemble modal
        modalContent.appendChild(modalHeader);
        modalContent.appendChild(modalBody);
        modalOverlay.appendChild(modalContent);
        
        // Add to page
        document.body.appendChild(modalOverlay);
        
        // Add close on background click
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeRecordsModal();
            }
        });
        
    } catch (error) {
        console.error('Error viewing records:', error);
        alert('Error retrieving saved records.');
    }
}

// Close Records Modal
function closeRecordsModal() {
    const modal = document.querySelector('[style*="position: fixed"]');
    if (modal) {
        modal.remove();
    }
}

// Load Patient Record
function loadPatientRecord(recordId) {
    try {
        const records = JSON.parse(localStorage.getItem('patientRecords') || '[]');
        const record = records.find(r => r.id === recordId);
        
        if (!record) {
            alert('Record not found.');
            return;
        }
        
        // Fill form with record data
        document.getElementById('in_pre').value = record.prefix || '';
        document.getElementById('in_ln').value = record.lastName || '';
        document.getElementById('in_fn').value = record.firstName || '';
        document.getElementById('in_mn').value = record.middleName || '';
        document.getElementById('in_suffix').value = record.suffix || '';
        document.getElementById('in_dob').value = record.dateOfBirth || '';
        document.getElementById('in_age').value = record.age || '';
        document.getElementById('in_sex').value = record.sex || '';
        document.getElementById('in_status').value = record.civilStatus || '';
        document.getElementById('in_bt').value = record.bloodType || '';
        
        // Educational & Demographic
        document.getElementById('in_edu').value = record.educationalAttainment || '';
        document.getElementById('in_emp').value = record.employmentStatus || '';
        document.getElementById('in_religion').value = record.religion || '';
        document.getElementById('in_city').value = record.city || '';
        document.getElementById('in_brgy').value = record.barangay || '';
        
        // Header Information
        document.getElementById('in_ph1').value = record.philHealthId.part1 || '';
        document.getElementById('in_ph2').value = record.philHealthId.part2 || '';
        document.getElementById('in_ph3').value = record.philHealthId.part3 || '';
        document.getElementById('in_yakap_center').value = record.yakapCenter || '';
        document.getElementById('in_yakap_others').value = record.yakapOthers || '';
        document.getElementById('in_contact').value = record.contactNumber || '';
        
        // Family Member Information
        document.getElementById('in_family_member').value = record.familyMember || '';
        document.getElementById('in_household_num').value = record.householdNumber || '';
        document.getElementById('in_4ps_member').value = record.fourpsMember || '';
        document.getElementById('in_family_serial').value = record.familySerial || '';
        document.getElementById('in_pwd').value = record.pwd || '';
        
        // PhilHealth Member Information
        document.getElementById('in_philhealth_member').value = record.philhealthMember || '';
        document.getElementById('in_philhealth_status').value = record.philhealthStatus || '';
        document.getElementById('in_philhealth_category').value = record.philhealthCategory || '';
        document.getElementById('in_yakap_registered').value = record.yakapRegistered || '';
        
        // Vital Signs & Measurements
        document.getElementById('in_bp').value = record.bloodPressure || '';
        document.getElementById('in_pr').value = record.pulseRate || '';
        document.getElementById('in_temp').value = record.temperature || '';
        document.getElementById('in_height').value = record.height || '';
        document.getElementById('in_weight').value = record.weight || '';
        document.getElementById('in_waist_circ').value = record.waistCircumference || '';
        document.getElementById('in_hip_circ').value = record.hipCircumference || '';
        document.getElementById('in_rr').value = record.respiratoryRate || '';
        
        // Trigger sync to update display
        const syncEvent = new Event('input');
        document.getElementById('in_ph1').dispatchEvent(syncEvent);
        
        closeRecordsModal();
        
        alert('Patient record loaded successfully!');
        
    } catch (error) {
        console.error('Error loading patient record:', error);
        alert('Error loading patient record.');
    }
}

// Print Patient Record
function printPatientRecord(recordId) {
    try {
        const records = JSON.parse(localStorage.getItem('patientRecords') || '[]');
        const record = records.find(r => r.id === recordId);
        
        if (!record) {
            alert('Record not found.');
            return;
        }
        
        // Load record into form first
        loadPatientRecord(recordId);
        
        // Then trigger print
        setTimeout(() => {
            handleHardcopyPrint();
        }, 500);
        
    } catch (error) {
        console.error('Error printing patient record:', error);
        alert('Error printing patient record.');
    }
}

// Delete Patient Record
function deletePatientRecord(recordId) {
    try {
        if (!confirm('Are you sure you want to delete this patient record? This action cannot be undone.')) {
            return;
        }
        
        const records = JSON.parse(localStorage.getItem('patientRecords') || '[]');
        const updatedRecords = records.filter(r => r.id !== recordId);
        
        localStorage.setItem('patientRecords', JSON.stringify(updatedRecords));
        
        // Refresh the modal
        closeRecordsModal();
        viewSavedRecords();
        
        alert('Patient record deleted successfully!');
        
    } catch (error) {
        console.error('Error deleting patient record:', error);
        alert('Error deleting patient record.');
    }
}
// Automatic Date and Time Update
function updateDateTime() {
    const now = new Date();
    
    // Format date: MM/DD/YYYY
    const date = now.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit', 
        year: 'numeric'
    });
    
    // Format time: HH:MM AM/PM
    const time = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
    
    // Update display elements
    const dateElement = document.getElementById('out_date');
    const timeElement = document.getElementById('out_time');
    
    if (dateElement) {
        dateElement.innerText = date;
    }
    
    if (timeElement) {
        timeElement.innerText = time;
    }
    
    console.log('Date/Time updated:', date, time);
}

// Initialize date/time on page load and update every minute
document.addEventListener('DOMContentLoaded', function() {
    // Update immediately
    updateDateTime();
    
    // Update every minute
    setInterval(updateDateTime, 60000);
    
    console.log('Date/Time updater initialized');
});

// Dashboard Navigation Function
function goToDashboard() {
    window.location.href = 'admin_dashboard.php';
}

// Update Barangay Dropdown based on selected City
function updateBarangays() {
    const citySelect = document.getElementById('in_city');
    const barangaySelect = document.getElementById('in_brgy');
    
    // Clear current barangay options
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    const selectedCity = citySelect.value;
    
    if (!selectedCity) return;
    
    // Comprehensive barangay data for all Philippine cities
    const barangays = {
        "Bacolod": [
            "Alangilan", "Alijis", "Banago", "Bata", "Cabug", "Estefania", "Felisa", 
            "Granada", "Handumanan", "Mandalagan", "Mansilingan", "Montevista", 
            "Pahanocoy", "Punta Taytay", "Singcang-Airport", "Sum-ag", "Tangub", 
            "Villamonte", "Vista Alegre", "Barangay 1", "Barangay 2", "Barangay 3", 
            "Barangay 4", "Barangay 5", "Barangay 6", "Barangay 7", "Barangay 8", 
            "Barangay 9", "Barangay 10", "Barangay 11", "Barangay 12", "Barangay 13", 
            "Barangay 14", "Barangay 15", "Barangay 16", "Barangay 17", "Barangay 18", 
            "Barangay 19", "Barangay 20", "Barangay 21", "Barangay 22", "Barangay 23", 
            "Barangay 24", "Barangay 25", "Barangay 26", "Barangay 27", "Barangay 28", 
            "Barangay 29", "Barangay 30", "Barangay 31", "Barangay 32", "Barangay 33", 
            "Barangay 34", "Barangay 35", "Barangay 36", "Barangay 37", "Barangay 38", 
            "Barangay 39", "Barangay 40", "Barangay 41"
        ],
      "Talisay": [
    "Zone 1", "Zone 2", "Zone 3", "Zone 4", "Zone 4-A", "Zone 5", "Zone 6", 
    "Zone 7", "Zone 8", "Zone 9", "Zone 10", "Zone 11", "Zone 12", "Zone 12-A", 
    "Zone 13", "Zone 14", "Zone 14-A", "Zone 15", "Zone 16", "Bubog", 
    "Cabacungan", "Concepcion", "Dos Hermanas", "Efigenio Lizares", 
    "Katubhan", "Matab-ang", "San Fernando"
],
       "Silay": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Cinco de Noviembre)", 
    "Barangay IV (Poblacion)", "Barangay V (Poblacion)", "Barangay VI (Hawaiian)", 
    "Bagtic", "Balaring", "Eustaquio Lopez", "Guimbala-on", "Guinhalaran", 
    "Kapitan Ramon", "Lantad", "Mambulac", "Patag", "Rizal"
], 
       "Bago": [
    "Poblacion", "Abuanan", "Alianza", "Atipuluan", "Bacong-Montilla", 
    "Bagroy", "Balingasag", "Binubuhan", "Busay", "Calumangan", 
    "Caridad", "Don Jorge L. Araneta", "Dulao", "Ilijan", "Lag-Asan", 
    "Ma-ao", "Mailum", "Malingin", "Napoles", "Pacol", "Sagasa", 
    "Sampinit", "Tabunan", "Taloc"
],
      "Cadiz": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", 
    "Abuanan", "Andres Bonifacio", "Banquerohan", "Burgos", "Cabahug", 
    "Cadiz Viejo", "Caduha-an", "Celestino Villacin", "Daga", "Jerusalem", 
    "Luna", "Mabini", "Magsaysay", "Sicaba", "Tiglawigan", "Tinampa-an", 
    "V. F. Gustilo"
],
       "Escalante": [
    "Old Poblacion", "Alimango", "Balintawak (New Escalante)", "Binaguiohan", 
    "Buenavista", "Cervantes", "Dian-ay", "Hda. Fe", "Japitan", "Jonobjonob", 
    "Langub", "Libertad", "Mabini", "Magsaysay", "Malasibog", "Paitan", 
    "Pinapugasan", "Rizal", "Tamlang", "Udtongan", "Washington"
],

    "Himamaylan": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", 
    "Barangay IV (Poblacion)", "Aguisan", "Buenavista", "Cabadiangan", 
    "Cabanbanan", "Carabalan", "Caradio-an", "Libacao", "Mahalang", 
    "Mambagaton", "Nabali-an", "San Antonio", "Sara-et", "Su-ay", 
    "Talaban", "To-oy"
],

    "Kabankalan": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)",  "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", 
    "Barangay 7 (Poblacion)", "Barangay 8 (Poblacion)", "Barangay 9 (Poblacion)", 
    "Bantayan", "Binicuil", "Camansi", "Camingawan", "Camugao", "Carol-an", 
    "Daan Banua", "Hilamonan", "Inapoy", "Linao", "Locotan", "Magballo", 
    "Oringao", "Orong", "Pinaguinpinan", "Salong", "Tabu", "Tagukon", 
    "Talubangi", "Tampalon", "Tan-awan", "Tayum", "Yanango"
],

    "La Carlota": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", 
    "Barangay RSB (Consuelo)", "Ara-al", "Ayungon", "Balabag", "Batuan", 
    "Cubay", "Haguimit", "La Granja", "Nagasi", "San Miguel", "Yubo"
],

   "Sagay": [
    "Poblacion I (Barangay 1)", "Poblacion II (Barangay 2)", "Andres Bonifacio", 
    "Bato", "Baviera", "Bulanon", "Campo Himoga-an", "Campo Santiago", 
    "Colonia Divina", "Fabrica", "General Luna", "Himoga-an Baybay", 
    "Lopez Jaena", "Malubon", "Maquiling", "Molocaboc", "Old Sagay", 
    "Paraiso", "Plaridel", "Puey", "Rafaela Barrera", "Rizal", 
    "Taba-ao", "Tadlong", "Vito"
],

    "San Carlos": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", 
    "Barangay IV (Poblacion)", "Barangay V (Poblacion)", "Barangay VI (Poblacion)", 
    "Bagonbon", "Buluangan", "Codcod", "Ermita (Sipaway)", "Guadalupe", 
    "Nataban", "Palampas", "Prosperidad", "Punao", "Quezon", "Rizal", 
    "San Juan (Sipaway)"
],

   "Sipalay": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Cabadiangan", 
    "Camindangan", "Canturay", "Cartagena", "Cayhagan", "Gil Montilla", 
    "Mambaroto", "Manlucahoc", "Maricalum", "Nabulao", "Nauhang", "San Jose"
],

   "Victorias": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Quezon; Pob.)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Estrella/Salvacion)", 
    "Barangay 6-A (Boulevard/Pasil)", "Barangay 7 (Poblacion)", "Barangay 8 (Old Simboryo)", 
    "Barangay 9 (Daan Banwa)", "Barangay 10 (Estado)", "Barangay 11 (Gawahon)", 
    "Barangay 12 (Dacumon)", "Barangay 13 (Gloryville)", "Barangay 14 (Sayding)", 
    "Barangay 15 West Caticlan", "Barangay 15-A East Caticlan", "Barangay 16 (Millsite)", 
    "Barangay 16-A (New Barrio)", "Barangay 17 (Garden)", "Barangay 18 (Palma)", 
    "Barangay 18-A (Golf)", "Barangay 19 (Bacayan)", "Barangay 19-A (Canetown)", 
    "Barangay 20 (Cuaycong)", "Barangay 21 (Relocation)"
],

   "Murcia": [
    "Zone I (Poblacion)", "Zone II (Poblacion)", "Zone III (Poblacion)", 
    "Zone IV (Poblacion)", "Zone V (Poblacion)", "Abo-abo", "Alegria", 
    "Amayco", "Blumentritt", "Buenavista", "Caliban", "Canlandog", 
    "Cansilayan", "Damsite", "Iglau-an", "Lopez Jaena", "Minoyan", 
    "Pandanon (Silos)", "San Miguel", "Santa Cruz", "Santa Rosa", 
    "Salvacion", "Talotog"
],

   "Binalbagan": [
    "Pagla-um (Poblacion)", "San Pedro (Poblacion)", "Santo Rosario (Poblacion)", 
    "Amontay", "Bagroy", "Bi-ao", "Canmoros", "Enclaro", "Marina", 
    "Payao", "Progreso", "San Jose", "San Juan", "San Teodoro", 
    "San Vicente", "Santol"
],

   "Calatrava": [
    "Lo-ok (Poblacion)", "Suba (Poblacion)", "Agpangi", "Ani-e", "Bagacay", 
    "Bantayanon", "Buenavista", "Cabungahan", "Calampisawan", "Cambayobo", 
    "Castellano", "Cruz", "Dolis", "Hilub-Ang", "Hinab-Ongan", "Ilaya", 
    "Laga-an", "Lalong", "Lemery", "Lipat-on", "Ma-aslob", "Macasilao", 
    "Mahilum", "Malanog", "Malatas", "Marcelo", "Menchaca", "Mina-utok", 
    "Minapasuk", "Paghumayan", "Pantao", "Patun-an", "Pinocutan", "Refugio", 
    "San Benito", "San Isidro", "Telim", "Tigbao", "Tigbon", "Winaswasan"
],

   "Candoni": [
    "Poblacion East", "Poblacion West", "Agboy", "Banga", "Cabia-an", 
    "Caningay", "Gatuslao", "Haba", "Payauan"
],

   "Cauayan": [
    "Poblacion", "Abaca", "Basak", "Buclao", "Bulata", "Caliling", 
    "Camalanda-an", "Camindangan", "Elihan", "Guiljungan", "Inayawan", 
    "Isio", "Linaon", "Lumbia", "Mambugsay", "Man-uling", "Masaling", 
    "Molobolo", "Sura", "Talacdan", "Tiling", "Tomina", "Tuyom", "Yaoyao"
],

   "E.B. Magalona": [
    "Poblacion I (Barangay 1)", "Poblacion II (Barangay 2)", "Poblacion III (Barangay 3)", 
    "Alacaygan", "Alicante", "Batea", "Canlusong", "Consing", "Cudangdang", 
    "Damgo", "Gahit", "Latasan", "Madalag", "Manta-angan", "Nanca", "Pasil", 
    "San Isidro", "San Jose", "Santo Niño", "Tabigue", "Tanza", "Tomongtong", 
    "Tuburan"
],

   "Hinigaran": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", 
    "Barangay IV (Poblacion)", "Anahaw", "Aranda", "Baga-as", "Bato", 
    "Calapi", "Camalobalo", "Camba-og", "Cambugsa", "Candumarao", 
    "Gargato", "Himaya", "Miranda", "Nanunga", "Narauis", "Palayog", 
    "Paticui", "Pilar", "Quiwi", "Tagda", "Tuguis"
],

   "Hinobaan": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Alim", "Asia", 
    "Bacuyangan", "Bulwangan", "Culipapa", "Damutan", "Daug", 
    "Po-ok", "San Rafael", "Sangke", "Talacagay"
],

   "Ilog": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Andulauan", "Balicotoc", 
    "Bocana", "Calubang", "Canlamay", "Consuelo", "Dancalan", "Delicioso", 
    "Galicia", "Manalad", "Pinggot", "Tabu", "Vista Alegre"
],

   "Isabela": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", 
    "Barangay 7 (Poblacion)", "Barangay 8 (Poblacion)", "Barangay 9 (Poblacion)", 
    "Amin", "Banogbanog", "Bulad", "Bungahin", "Cabcab", "Camangcamang", 
    "Camp Clark", "Cansalongon", "Guintubhan", "Libas", "Limalima", 
    "Makilignit", "Mansablay", "Maytubig", "Panaquiao", "Riverside", 
    "Rumirang", "San Agustin", "Sebucawan", "Sikatuna", "Tinongan"
],

   "La Castellana": [
    "Robles (Poblacion)", "Biaknabato", "Cabacungan", "Cabagnaan", 
    "Camandag", "Lalagsan", "Manghanoy", "Mansalanao", "Masulog", 
    "Nato", "Puso", "Sag-ang", "Talaptap"
],

   "Manapla": [
    "Barangay I (Poblacion)", "Barangay I-A (Poblacion)", "Barangay I-B (Poblacion)", 
    "Barangay II (Poblacion)", "Barangay II-A (Poblacion)", "Chamberi", 
    "Punta Mesa", "Punta Salong", "Purisima", "San Pablo", 
    "Santa Teresa", "Tortosa"
],

   "Moises Padilla": [
    "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", 
    "Barangay 4 (Poblacion)", "Barangay 5 (Poblacion)", "Barangay 6 (Poblacion)", 
    "Barangay 7 (Poblacion)", "Crossing Magallon", "Guinpana-an", 
    "Inolingan (Hda. Salapid)", "Macagahay", "Magallon Cadre.", 
    "Montilla", "Odiong", "Quintin Remo"
],

   "Pontevedra": [
    "Barangay I (Poblacion)", "Barangay II (Poblacion)", "Barangay III (Poblacion)", 
    "Antipolo", "Buenavista Gibong", "Buenavista Rizal", "Burgos", 
    "Cambarus", "Canroma", "General Malvar", "Gomez", "M. H. Del Pilar", 
    "Mabini", "Miranda", "Pandan", "Recreo", "San Isidro", "San Juan", 
    "Zamora"
],

   "Pulupandan": [
    "Barangay Zone 1 (Pob. / Green beach)", "Barangay Zone 1-A (Pob. / Paco beach)", 
    "Barangay Zone 2 (Poblacion)", "Barangay Zone 3 (Poblacion)", "Barangay Zone 4 (Poblacion)", 
    "Barangay Zone 4-A (Poblacion)", "Barangay Zone 5 (Poblacion)", "Barangay Zone 6 (Poblacion)", 
    "Barangay Zone 7 (Poblacion)", "Canjusa", "Crossing Pulupandan", "Culo", 
    "Mabini", "Pag-ayon", "Palaka Norte", "Palaka Sur", "Patic (Sitio Calubihan)", 
    "Tapong", "Ubay", "Utod"
],

    "Don Salvador Benedicto": [
    "Bago (Lalung)", "Bagong Silang (Marcelo)", "Bunga", "Igmaya-an", 
    "Kumaliskis", "Pandanon", "Pinowayan (Prosperidad)"
],
  
    "San Enrique": [
    "Poblacion", "Bagonawa", "Baliwagan", "Batuan", "Guintorilan", 
    "Nayon", "Sibucao", "Tabao Baybay", "Tabao Rizal", "Tibsok"
],
   
    "Toboso": [
    "Poblacion", "Bandilla", "Bug-ang", "General Luna", "Magticol", 
    "Salamanca", "San Isidro", "San Jose", "Tabun-ac"
],

    "Valladolid": [
    "Poblacion", "Alijis", "Ayungon", "Bagumbayan", "Batuan", "Bayabas", 
    "Central Tabao", "Doldol", "Guintorilan", "Lacaron", "Mabini", 
    "Pacol", "Palaka", "Paloma", "Sagua Banua", "Tabao Proper"
]
    };
    
    // Get barangays for selected city
    const cityBarangays = barangays[selectedCity];
    
    if (cityBarangays && cityBarangays.length > 0) {
        cityBarangays.forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay;
            option.textContent = barangay;
            barangaySelect.appendChild(option);
        });
    } else {
        // For cities not in our data, add a default option
        const option = document.createElement('option');
        option.value = 'Not Available';
        option.textContent = 'Barangay data not available';
        barangaySelect.appendChild(option);
    }
}

</script>
</body>
</html>