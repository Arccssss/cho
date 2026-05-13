<?php
session_start();
require_once 'database.php';
$conn = getDBConnection();

$error = '';

// If no booking info in session, redirect to personal info first
if (!isset($_SESSION['booking_info'])) {
    header('Location: personal_info.php');
    exit;
}

// Handle date selection — insert booking and redirect to confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_date'])) {
    $info             = $_SESSION['booking_info'];
    $appointment_date = $conn->real_escape_string($_POST['selected_date']);
    $appointment_time = 'AM';
    $time_period      = 'AM';

    $philhealth_no          = $conn->real_escape_string($info['philhealth_no']);
    $last_name              = $conn->real_escape_string($info['last_name']);
    $first_name             = $conn->real_escape_string($info['first_name']);
    $middle_name            = $conn->real_escape_string($info['middle_name']);
    $suffix                 = $conn->real_escape_string($info['suffix']);
    $date_of_birth          = $conn->real_escape_string($info['date_of_birth']);
    $sex                    = $conn->real_escape_string($info['sex']);
    $civil_status           = $conn->real_escape_string($info['civil_status']);
    $barangay               = $conn->real_escape_string($info['barangay']);
    $contact_number         = $conn->real_escape_string($info['contact_number']);
    $email                  = $conn->real_escape_string($info['email']);
    $maiden_name            = $conn->real_escape_string($info['maiden_name']);
    $spouse_name            = $conn->real_escape_string($info['spouse_name']);
    $mother_name            = $conn->real_escape_string($info['mother_name']);
    $educational_attainment = $conn->real_escape_string($info['educational_attainment']);
    $employment_status      = $conn->real_escape_string($info['employment_status']);
    $dswd_nhts              = $conn->real_escape_string($info['dswd_nhts']);
    $four_ps_member         = $conn->real_escape_string($info['four_ps_member']);
    $facility_household_no  = $conn->real_escape_string($info['facility_household_no']);
    $household_no           = $conn->real_escape_string($info['household_no']);
    $co_habitation          = $conn->real_escape_string($info['co_habitation']);
    $family_member          = $conn->real_escape_string($info['family_member']);
    $philhealth_member      = $conn->real_escape_string($info['philhealth_member']);
    $philhealth_status_type = $conn->real_escape_string($info['philhealth_status_type']);
    $primary_care_benefit   = $conn->real_escape_string($info['primary_care_benefit']);
    $category               = $conn->real_escape_string($info['category']);
    $mode_of_transaction    = $conn->real_escape_string($info['mode_of_transaction']);
    $notes                  = $conn->real_escape_string($info['notes']);
    $purpose                = $conn->real_escape_string($info['purpose']);

    try {
        $day_of_week = strtolower(date('l', strtotime($appointment_date)));
        $max_am = null; $max_pm = null;
        $ov = $conn->prepare("SELECT am_capacity, pm_capacity FROM date_slot_overrides WHERE override_date = ?");
        $ov->bind_param("s", $appointment_date); $ov->execute();
        $ovr = $ov->get_result();
        if ($ovr->num_rows > 0) {
            $row = $ovr->fetch_assoc();
            $max_am = (int)$row['am_capacity'];
            $max_pm = (int)$row['pm_capacity'];
        }
        $ov->close();
        if ($max_am === null) {
            $sl = $conn->prepare("SELECT time_period, max_appointments FROM appointment_am_pm_slots WHERE day_of_week = ? AND is_active = 1");
            $sl->bind_param("s", $day_of_week); $sl->execute();
            $slr = $sl->get_result();
            $max_am = 50; $max_pm = 50;
            while ($r = $slr->fetch_assoc()) {
                if ($r['time_period'] === 'AM') $max_am = (int)$r['max_appointments'];
                if ($r['time_period'] === 'PM') $max_pm = (int)$r['max_appointments'];
            }
            $sl->close();
        }
        $max_total = $max_am + $max_pm;

        $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date = ? AND status IN ('pending','confirmed','completed')");
        $cnt->bind_param("s", $appointment_date); $cnt->execute();
        $current = (int)$cnt->get_result()->fetch_assoc()['c'];
        $cnt->close();

        if ($max_total == 0) {
            $error = 'This date is not available. Please choose another date.';
        } elseif ($current >= $max_total) {
            $error = 'This date is fully booked. Please choose another date.';
        } else {
            $client_name = trim("$first_name $middle_name $last_name $suffix");
            $stmt = $conn->prepare("INSERT INTO appointments (client_name, contact_number, appointment_date, appointment_time, time_period, purpose, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssssss", $client_name, $contact_number, $appointment_date, $appointment_time, $time_period, $purpose);
            $stmt->execute();
            $appointment_id   = $stmt->insert_id;
            $reference_number = 'CHO-' . date('Y') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
            $stmt->close();

            $upd = $conn->prepare("UPDATE appointments SET philhealth_no=?, last_name=?, first_name=?, middle_name=?, suffix=?, date_of_birth=?, sex=?, civil_status=?, barangay=?, email=?, notes=?, reference_number=?, maiden_name=? WHERE id=?");
            $upd->bind_param("sssssssssssssi", $philhealth_no, $last_name, $first_name, $middle_name, $suffix, $date_of_birth, $sex, $civil_status, $barangay, $email, $notes, $reference_number, $maiden_name, $appointment_id);
            $upd->execute(); $upd->close();

            try {
                $age            = (int)date_diff(date_create($date_of_birth), date_create('today'))->y;
                $system_uid     = 3;
                $enroll_purpose = $purpose . ' [Ref: ' . $reference_number . ']';
                $ec = $conn->prepare("SELECT id FROM patient_enrollment WHERE purpose_of_visit LIKE ? LIMIT 1");
                $rs = '%' . $reference_number . '%';
                $ec->bind_param("s", $rs); $ec->execute();
                if ($ec->get_result()->num_rows === 0) {
                    $es = $conn->prepare("INSERT INTO patient_enrollment (user_id,last_name,first_name,middle_name,suffix,maiden_name,age,sex,birth_date,contact_number,residential_address,civil_status,philhealth_no,philhealth_member,philhealth_status_type,primary_care_benefit,category,spouse_name,mother_name,educational_attainment,employment_status,dswd_nhts,four_ps_member,facility_household_no,household_no,co_habitation,family_member,date_of_consultation,consultation_time,purpose_of_visit,mode_of_transaction,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                    $es->bind_param("isssssissssssssssssssssssssssss", $system_uid, $last_name, $first_name, $middle_name, $suffix, $maiden_name, $age, $sex, $date_of_birth, $contact_number, $barangay, $civil_status, $philhealth_no, $philhealth_member, $philhealth_status_type, $primary_care_benefit, $category, $spouse_name, $mother_name, $educational_attainment, $employment_status, $dswd_nhts, $four_ps_member, $facility_household_no, $household_no, $co_habitation, $family_member, $appointment_date, $appointment_time, $enroll_purpose, $mode_of_transaction);
                    $es->execute(); $es->close();
                }
                $ec->close();
            } catch (Exception $ee) {
                error_log('enrollment failed: ' . $ee->getMessage());
            }

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
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .main-header { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; padding: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .logo-container { display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px; }
        .logo-img { height: 60px; width: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .logo-text { font-size: 28px; font-weight: 700; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .logo-subtitle { font-size: 14px; opacity: 0.9; margin: 0; }

        .calendar-container { background: white; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); overflow: hidden; }
        .calendar-header { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; padding: 20px 25px; }
        .calendar-nav { display: flex; justify-content: space-between; align-items: center; }
        .calendar-nav h4 { margin: 0; font-size: 22px; font-weight: 700; }
        .calendar-nav button { font-size: 20px; padding: 6px 16px; min-width: 44px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #dee2e6; border: 1px solid #dee2e6; }
        .calendar-day-header { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; padding: 12px 5px; text-align: center; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .calendar-day { background: white; padding: 10px 6px; text-align: center; cursor: default; min-height: 80px; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; transition: all 0.2s ease; border: 1px solid #f0f0f0; gap: 4px; }
        .calendar-day.available { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-color: #c3e6cb; cursor: pointer; }
        .calendar-day.available:hover { background: linear-gradient(135deg, #b8dfc4 0%, #a8d4b0 100%); transform: scale(1.03); box-shadow: 0 4px 15px rgba(40,167,69,0.25); border-color: #28a745; z-index: 1; }
        .calendar-day.selected { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important; color: white !important; box-shadow: 0 4px 15px rgba(13,110,253,0.4); border-color: #0a58ca !important; transform: scale(1.03); z-index: 2; }
        .calendar-day.selected .slot-info, .calendar-day.selected .day-number { color: white !important; }
        .calendar-day.weekend { background: #f5f5f5; color: #ccc; cursor: not-allowed; }
        .calendar-day.weekend:hover { background: #f5f5f5; transform: none; box-shadow: none; }
        .calendar-day.past { background: #fafafa; color: #ccc; cursor: not-allowed; }
        .calendar-day.past:hover { background: #fafafa; transform: none; box-shadow: none; }
        .calendar-day.today { border: 2px solid #0d6efd !important; }
        .calendar-day.fully-booked { background: #f8d7da !important; color: #721c24 !important; cursor: not-allowed !important; border: 1px solid #f5c6cb !important; }
        .calendar-day.fully-booked:hover { background: #f8d7da !important; transform: none !important; box-shadow: none !important; }
        .day-number { font-size: 15px; font-weight: 700; line-height: 1; }
        .slot-info { font-size: 10px; font-weight: 600; color: #155724; }
        .slot-info.low { color: #856404; }
        .slot-info.critical { color: #dc3545; }
        .fully-booked-label { font-size: 9px; color: #721c24; font-weight: 700; text-transform: uppercase; }
        .book-btn { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; margin-top: 3px; transition: all 0.2s ease; text-transform: uppercase; letter-spacing: 0.3px; }
        .book-btn:hover { background: linear-gradient(135deg, #218838 0%, #1ea085 100%); transform: scale(1.05); }
        .booking-panel { display: none; margin-top: 24px; background: linear-gradient(135deg, #e8f0fe 0%, #d1e9ff 100%); border-radius: 14px; padding: 28px 32px; border-left: 5px solid #0d6efd; box-shadow: 0 4px 15px rgba(13,110,253,0.1); }
        .booking-panel h5 { font-weight: 700; color: #0d6efd; margin-bottom: 6px; }
        .booking-panel .date-display { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; margin-bottom: 20px; }
        .btn-proceed { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border: none; border-radius: 10px; color: white; font-weight: 700; font-size: 1rem; padding: 14px 32px; width: 100%; transition: all 0.2s; }
        .btn-proceed:hover { background: linear-gradient(135deg, #0a58ca 0%, #0946a0 100%); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13,110,253,0.35); color: white; }
        .calendar-legend { display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 16px; background: #f8f9fa; border-top: 1px solid #dee2e6; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #555; }
        .legend-color { width: 14px; height: 14px; border-radius: 3px; }
        .card { border: none; border-radius: 15px; }
        .bg-gradient { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important; }
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

                        <!-- Calendar -->
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

                        <!-- Booking Confirm Panel -->
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

                <!-- Info card -->
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
    <script>
        // Use PHP server date to avoid browser timezone issues
        const serverToday = new Date('<?= date('Y-m-d') ?>T00:00:00');
        let currentYear  = serverToday.getFullYear();
        let currentMonth = serverToday.getMonth(); // 0-indexed
        let selectedDateStr = null;
        window.bookings = null;

        function renderCalendar() {
            const year  = currentYear;
            const month = currentMonth;

            const monthNames = ['January','February','March','April','May','June',
                                'July','August','September','October','November','December'];
            document.getElementById('current-month').textContent = monthNames[month] + ' ' + year;

            const grid = document.getElementById('calendar-grid');
            grid.innerHTML = '';

            // Day headers — Sun first
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
                const h = document.createElement('div');
                h.className = 'calendar-day-header';
                h.textContent = d;
                grid.appendChild(h);
            });

            const daysInMonth = new Date(year, month + 1, 0).getDate();
            // getDay() returns 0=Sun,1=Mon...6=Sat — correct for Sun-first grid
            const firstDayOfWeek = new Date(year, month, 1).getDay();

            // Leading empty cells
            for (let i = 0; i < firstDayOfWeek; i++) {
                const e = document.createElement('div');
                e.className = 'calendar-day';
                e.style.background = '#fafafa';
                grid.appendChild(e);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const el = document.createElement('div');
                el.className = 'calendar-day';

                // Build date string without timezone issues
                const mm      = String(month + 1).padStart(2, '0');
                const dd      = String(day).padStart(2, '0');
                const dateStr = year + '-' + mm + '-' + dd;

                // Day of week: use UTC to avoid DST shifts
                const cellDate   = new Date(year, month, day);
                const dow        = cellDate.getDay(); // 0=Sun … 6=Sat
                const todayStr   = serverToday.getFullYear() + '-' +
                                   String(serverToday.getMonth()+1).padStart(2,'0') + '-' +
                                   String(serverToday.getDate()).padStart(2,'0');
                const isPast     = dateStr < todayStr;
                const isToday    = dateStr === todayStr;
                const isWeekend  = (dow === 0 || dow === 6);

                const numSpan = document.createElement('span');
                numSpan.className = 'day-number';
                numSpan.textContent = day;
                el.appendChild(numSpan);

                if (isWeekend) {
                    el.classList.add('weekend');
                    const lbl = document.createElement('span');
                    lbl.style.cssText = 'font-size:9px;color:#ccc;font-weight:600;text-transform:uppercase;';
                    lbl.textContent = dow === 0 ? 'Sunday' : 'Saturday';
                    el.appendChild(lbl);
                } else if (isPast) {
                    el.classList.add('past');
                } else {
                    const data    = window.bookings && window.bookings.bookings && window.bookings.bookings[dateStr];
                    const amSlots = data ? data.AM : 50;
                    const pmSlots = data ? data.PM : 50;
                    const total   = amSlots + pmSlots;

                    if (total === 0) {
                        el.classList.add('fully-booked');
                        const lbl = document.createElement('span');
                        lbl.className = 'fully-booked-label';
                        lbl.textContent = 'Fully Booked';
                        el.appendChild(lbl);
                    } else {
                        el.classList.add('available');

                        const btn = document.createElement('button');
                        btn.className = 'book-btn';
                        btn.textContent = 'Book';
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            pickDate(dateStr, total);
                        });
                        el.appendChild(btn);
                        el.addEventListener('click', function() { pickDate(dateStr, total); });
                    }
                }

                if (isToday) el.classList.add('today');
                if (dateStr === selectedDateStr) el.classList.add('selected');

                grid.appendChild(el);
            }
        }

        function changeMonth(dir) {
            currentMonth += dir;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
            fetchBookings(currentYear, currentMonth + 1);
        }

        function fetchBookings(year, month) {
            fetch('get_month_bookings.php?year=' + year + '&month=' + month)
                .then(r => r.json())
                .then(data => { window.bookings = data; renderCalendar(); })
                .catch(() => renderCalendar());
        }

        function pickDate(dateStr, totalSlots) {
            document.querySelectorAll('.calendar-day.selected').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.calendar-day.available').forEach(cell => {
                const n = cell.querySelector('.day-number');
                const mm = String(currentMonth + 1).padStart(2,'0');
                const dd = String(parseInt(n ? n.textContent : 0)).padStart(2,'0');
                if (n && (currentYear + '-' + mm + '-' + dd) === dateStr) cell.classList.add('selected');
            });

            selectedDateStr = dateStr;
            const parts = dateStr.split('-');
            const d = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
            document.getElementById('date-display').textContent = d.toLocaleDateString('en-US', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });



            const panel = document.getElementById('booking-panel');
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function proceedToBooking() {
            if (!selectedDateStr) return;
            document.getElementById('selected-date-input').value = selectedDateStr;
            document.getElementById('date-form').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetchBookings(currentYear, currentMonth + 1);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

