<?php
require_once '../../cho-apt/CHO/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();
$success = '';
$error = '';

// Ensure dental_slots table exists with defaults
$conn->query("CREATE TABLE IF NOT EXISTS `dental_slots` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `max_appointments` int(11) NOT NULL DEFAULT 10,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$conn->query("INSERT IGNORE INTO dental_slots (day_of_week, max_appointments) VALUES
    ('monday',10),('tuesday',10),('wednesday',10),('thursday',10),('friday',10),('saturday',0),('sunday',0)");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle dental capacity update
    if (isset($_POST['dental_update'])) {
        try {
            $days = ['monday','tuesday','wednesday','thursday','friday'];
            foreach ($days as $day) {
                $cap = (int)($_POST['dental_' . $day] ?? 10);
                $stmt = $conn->prepare("UPDATE dental_slots SET max_appointments = ?, updated_at = NOW() WHERE day_of_week = ?");
                $stmt->bind_param("is", $cap, $day);
                $stmt->execute();
                $stmt->close();
            }
            $success = "Dental slot capacities updated successfully.";
        } catch (Exception $e) {
            $error = 'Error updating dental slots: ' . $e->getMessage();
        }
    }

    // Handle date-specific capacity updates
    if (isset($_POST['date_specific_update'])) {
        try {
            $selected_date = $conn->real_escape_string($_POST['selected_date']);
            // Single daily capacity — store same value for both AM and PM
            $daily_capacity = (int)$_POST['date_daily_capacity'];
            $am_capacity = $daily_capacity/2;
            $pm_capacity = $daily_capacity/2;

            // Create table if it doesn't exist
            $table_check = $conn->query("SHOW TABLES LIKE 'date_slot_overrides'");
            if ($table_check->num_rows === 0) {
                $create_table_sql = "CREATE TABLE `date_slot_overrides` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `override_date` date NOT NULL,
                    `am_capacity` int(11) NOT NULL DEFAULT 50,
                    `pm_capacity` int(11) NOT NULL DEFAULT 50,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_date` (`override_date`),
                    KEY `idx_date` (`override_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                $conn->query($create_table_sql);
            }

            $check_sql = "SELECT id FROM date_slot_overrides WHERE override_date = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $selected_date);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows === 0) {
                $insert_sql = "INSERT INTO date_slot_overrides (override_date, am_capacity, pm_capacity, created_at) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sii", $selected_date, $am_capacity, $pm_capacity);
                $insert_stmt->execute();
                $insert_stmt->close();
                $success = "Daily capacity set for $selected_date — $daily_capacity slots";
            } else {
                $update_sql = "UPDATE date_slot_overrides SET am_capacity = ?, pm_capacity = ?, updated_at = NOW() WHERE override_date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iis", $am_capacity, $pm_capacity, $selected_date);
                $update_stmt->execute();
                $update_stmt->close();
                $success = "Daily capacity updated for $selected_date — $daily_capacity slots";
            }
        } catch (Exception $e) {
            $error = 'Error setting date-specific capacity: ' . $e->getMessage();
        }
    }

    // Handle deletion of date overrides
    if (isset($_POST['override_id'])) {
        try {
            $override_id = (int)$_POST['override_id'];
            if ($override_id <= 0) { $error = 'Invalid override ID'; }
            else {
                $table_check = $conn->query("SHOW TABLES LIKE 'date_slot_overrides'");
                if ($table_check->num_rows > 0) {
                    $verify_stmt = $conn->prepare("SELECT id FROM date_slot_overrides WHERE id = ?");
                    $verify_stmt->bind_param("i", $override_id);
                    $verify_stmt->execute();
                    if ($verify_stmt->get_result()->num_rows > 0) {
                        $delete_stmt = $conn->prepare("DELETE FROM date_slot_overrides WHERE id = ?");
                        $delete_stmt->bind_param("i", $override_id);
                        $delete_stmt->execute();
                        $delete_stmt->close();
                        $success = "Date override deleted successfully.";
                    } else {
                        $error = "Override not found or already deleted.";
                    }
                    $verify_stmt->close();
                }
            }
        } catch (Exception $e) {
            $error = 'Error deleting date override: ' . $e->getMessage();
        }
    }
}

// Fetch existing overrides
$overrides = [];
$table_exists = false;
try {
    $overrides_result = $conn->query("SELECT id, override_date, am_capacity, pm_capacity FROM date_slot_overrides WHERE override_date >= CURDATE() ORDER BY override_date ASC");
    if ($overrides_result) {
        while ($row = $overrides_result->fetch_assoc()) $overrides[] = $row;
        $table_exists = true;
    }
} catch (Exception $e) {
    $overrides = [];
    $table_exists = false;
}

$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$day_labels = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];

// Fetch current dental slot capacities
$dental_slots = [];
$dental_result = $conn->query("SELECT day_of_week, max_appointments FROM dental_slots ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')");
if ($dental_result) {
    while ($row = $dental_result->fetch_assoc()) {
        $dental_slots[$row['day_of_week']] = (int)$row['max_appointments'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Capacity Management - CHO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 15px;
        }

        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ── Header Card ── */
        .header-card {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border-radius: 18px 18px 0 0;
            padding: 36px 52px 30px;
            position: relative;
            text-align: center;
            box-shadow: 0 4px 20px rgba(13,110,253,0.4);
        }
        .header-card h2 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 6px;
        }
        .header-card p {
            color: rgba(255,255,255,0.8);
            margin: 0;
            font-size: 0.9rem;
        }
        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1.5px solid rgba(255,255,255,0.4);
            transition: background 0.2s, border-color 0.2s, transform 0.2s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.28);
            border-color: #fff;
            color: #fff;
            transform: translateY(-50%) translateX(-3px);
        }

        /* ── Main Card ── */
        .main-card {
            background: #fff;
            border-radius: 0 0 18px 18px;
            padding: 48px 52px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
        }

        /* ── Section titles ── */
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
        }
        .section-title .icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        /* ── Divider ── */
        .section-divider {
            border: none;
            border-top: 1px solid #e9ecef;
            margin: 28px 0;
        }

        /* ── Quick action buttons ── */
        .quick-btn {
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .quick-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        /* ── Form controls ── */
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #dee2e6;
            font-size: 0.9rem;
            padding: 9px 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.12);
        }

        /* ── Date pill buttons ── */
        .date-pill {
            border-radius: 50px;
            padding: 7px 16px;
            font-size: 0.82rem;
            font-weight: 500;
            border: 1.5px solid #0d6efd;
            color: #0d6efd;
            background: #fff;
            cursor: pointer;
            transition: all 0.18s;
        }
        .date-pill:hover, .date-pill.active {
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 3px 10px rgba(13,110,253,0.3);
        }

        /* ── Capacity form card ── */
        .capacity-card {
            background: linear-gradient(135deg, #f0f6ff 0%, #e8f0fe 100%);
            border: 1.5px solid #c7d9f8;
            border-radius: 14px;
            padding: 22px 24px;
            margin-top: 20px;
        }
        .capacity-card .card-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0d6efd;
            margin-bottom: 4px;
        }
        .capacity-card .selected-date-badge {
            display: inline-block;
            background: #0d6efd;
            color: #fff;
            border-radius: 8px;
            padding: 4px 14px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .capacity-number-input {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .btn-save {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            padding: 10px 24px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.35);
            color: #fff;
        }

        /* ── Alerts ── */
        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
            border: none;
        }
        .alert-success { background: #d1f5e0; color: #155724; }
        .alert-danger  { background: #fde8e8; color: #721c24; }
        .alert-info    { background: #e8f4fd; color: #0c5460; }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- Header -->
    <div class="header-card">
        <a href="../../cho-apt/CHO/admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h2><i class="fas fa-sliders-h me-2"></i>Slot Capacity Management</h2>
        <p>Set appointment slot capacities for specific dates</p>
    </div>

    <!-- Main Content -->
    <div class="main-card">

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Section: Date Selector -->
        <div class="section-title">
            <div class="icon-wrap"><i class="fas fa-calendar-day"></i></div>
            Select a Date
        </div>

        <!-- Quick Actions -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="button" class="btn btn-outline-primary quick-btn" onclick="showNextWeekDates()">
                <i class="fas fa-calendar-week me-2"></i>Next Week
            </button>
            <button type="button" class="btn btn-outline-info quick-btn" onclick="showThisMonthDates()">
                <i class="fas fa-calendar-alt me-2"></i>This Month
            </button>
        </div>

        <!-- Filters -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Day of Week</label>
                <select id="daySelector" class="form-select">
                    <option value="">Choose a day...</option>
                    <?php foreach ($days as $day): ?>
                        <option value="<?php echo $day; ?>"><?php echo $day_labels[$day]; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Month</label>
                <select id="monthSelector" class="form-select">
                    <option value="<?php echo date('n'); ?>"><?php echo date('F'); ?></option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <?php if ($m != date('n')): ?>
                            <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endif; ?>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Year</label>
                <select id="yearSelector" class="form-select">
                    <?php for ($y = date('Y'); $y <= date('Y') + 2; $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- Date Pills -->
        <div id="dateList" style="display:none;" class="mb-2">
            <p class="form-label mb-2">Available Dates:</p>
            <div id="dateButtons" class="d-flex flex-wrap gap-2"></div>
        </div>

        <!-- Capacity Form -->
        <div id="dateCapacityForm" style="display:none;">
            <div class="capacity-card">
                <p class="card-label">Setting capacity for</p>
                <div class="selected-date-badge" id="selectedDateDisplay">—</div>
                <form method="POST">
                    <input type="hidden" name="date_specific_update" value="1">
                    <input type="hidden" id="selectedDate" name="selected_date">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-8">
                            <label class="form-label">
                                <i class="fas fa-users me-1 text-primary"></i>
                                Daily Capacity (Whole Day)
                            </label>
                            <input type="number" name="date_daily_capacity" class="form-control capacity-number-input" min="0" max="500" value="100" required>
                            <div class="form-text text-muted" style="font-size:0.75rem;">
                                Total number of appointments allowed for this date (8:00 AM – 5:00 PM)
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <button type="submit" class="btn btn-save w-100">
                                <i class="fas fa-save me-2"></i>Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <hr style="border:none;border-top:1px solid #e9ecef;margin:28px 0;">

        <!-- Section: Dental Slot Capacity -->
        <div class="section-title">
            <div class="icon-wrap" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                <i class="fas fa-tooth"></i>
            </div>
            Dental Slot Capacity
        </div>
        <p class="text-muted mb-4" style="font-size:0.88rem;">
            Set the maximum number of <strong>Dental</strong> appointments per weekday.
            These are tracked separately from general appointments.
        </p>

        <form method="POST">
            <input type="hidden" name="dental_update" value="1">
            <div class="row g-3 align-items-end mb-2">
                <?php
                $weekdays = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday'];
                foreach ($weekdays as $day => $label):
                    $val = $dental_slots[$day] ?? 10;
                ?>
                <div class="col">
                    <label class="form-label text-center d-block" style="font-size:0.8rem;font-weight:700;color:#0284c7;">
                        <?= $label ?>
                    </label>
                    <input type="number" name="dental_<?= $day ?>"
                           class="form-control capacity-number-input"
                           min="0" max="100" value="<?= $val ?>" required
                           style="border-color:#bae6fd;text-align:center;font-size:1.1rem;font-weight:600;">
                </div>
                <?php endforeach; ?>
                <div class="col-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-save" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);white-space:nowrap;">
                        <i class="fas fa-save me-2"></i>Save Dental Slots
                    </button>
                </div>
            </div>
            <div class="form-text text-muted" style="font-size:0.78rem;">
                <i class="fas fa-info-circle me-1 text-info"></i>
                Dental slots are counted separately. Clients booking for <strong>Dental</strong> consume from this pool only.
            </div>
        </form>
    </div><!-- /main-card -->
</div><!-- /page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const daySelector   = document.getElementById('daySelector');
    const monthSelector = document.getElementById('monthSelector');
    const yearSelector  = document.getElementById('yearSelector');
    const dateList      = document.getElementById('dateList');
    const dateButtons   = document.getElementById('dateButtons');
    const dateCapacityForm   = document.getElementById('dateCapacityForm');
    const selectedDateDisplay = document.getElementById('selectedDateDisplay');
    const selectedDateInput   = document.getElementById('selectedDate');

    function getDayNumber(weekday) {
        return {sunday:0,monday:1,tuesday:2,wednesday:3,thursday:4,friday:5,saturday:6}[weekday] ?? 1;
    }

    function getWeekdayDates(year, month, weekday) {
        const dates = [];
        const target = getDayNumber(weekday);
        for (let d = 1; d <= 31; d++) {
            const date = new Date(year, month - 1, d);
            if (date.getMonth() !== month - 1) break;
            if (date.getDay() === target) dates.push(new Date(date));
        }
        return dates;
    }

    function updateDateList() {
        const selectedDay   = daySelector.value;
        const selectedMonth = parseInt(monthSelector.value);
        const selectedYear  = parseInt(yearSelector.value);

        if (!selectedDay) {
            dateList.style.display = 'none';
            dateCapacityForm.style.display = 'none';
            return;
        }

        const dates = getWeekdayDates(selectedYear, selectedMonth, selectedDay);
        dateButtons.innerHTML = '';

        if (dates.length > 0) {
            dates.forEach(date => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                const dateStr    = `${y}-${m}-${d}`;
                const displayStr = date.toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'date-pill';
                btn.textContent = displayStr;
                btn.dataset.dateStr    = dateStr;
                btn.dataset.displayStr = displayStr;
                btn.onclick = function() { selectDate(this); };
                dateButtons.appendChild(btn);
            });
            dateList.style.display = 'block';
        } else {
            dateList.style.display = 'none';
            dateCapacityForm.style.display = 'none';
        }
    }

    function selectDate(btn) {
        selectedDateInput.value       = btn.dataset.dateStr;
        selectedDateDisplay.textContent = btn.dataset.displayStr;
        dateCapacityForm.style.display = 'block';

        document.querySelectorAll('.date-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        dateCapacityForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    daySelector.addEventListener('change', updateDateList);
    monthSelector.addEventListener('change', updateDateList);
    yearSelector.addEventListener('change', updateDateList);

    function showNextWeekDates() {
        const nextWeek = new Date(Date.now() + 7 * 86400000);
        monthSelector.value = nextWeek.getMonth() + 1;
        yearSelector.value  = nextWeek.getFullYear();
        if (!daySelector.value) daySelector.value = 'monday';
        updateDateList();
    }

    function showThisMonthDates() {
        const today = new Date();
        monthSelector.value = today.getMonth() + 1;
        yearSelector.value  = today.getFullYear();
        if (!daySelector.value) daySelector.value = 'monday';
        updateDateList();
    }
</script>
</body>
</html>
<?php $conn->close(); ?>
