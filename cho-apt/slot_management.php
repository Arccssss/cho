<?php
session_start();
// 1. Require MVC Core
require_once 'config/helpers.php';
require_once 'config/database.php';
require_once 'models/SlotModel.php';

// 2. Authentication Check
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();
$slotModel = new SlotModel($conn);

// 3. Ensure tables exist (Replaces your long CREATE queries)
$slotModel->setupTables();

$success = '';
$error = '';

// 4. Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. Handle dental capacity update
    if (isset($_POST['dental_update'])) {
        try {
            $days = ['monday','tuesday','wednesday','thursday','friday'];
            $days_data = [];
            foreach ($days as $day) {
                $days_data[$day] = (int)($_POST['dental_' . $day] ?? 10);
            }
            
            $slotModel->updateDentalSlots($days_data);
            $success = "Dental slot capacities updated successfully.";
        } catch (Exception $e) {
            $error = 'Error updating dental slots: ' . $e->getMessage();
        }
    }

    // B. Handle date-specific capacity updates
    if (isset($_POST['date_specific_update'])) {
        try {
            // sanitize() assumes you have this function in helpers.php. 
            // If not, use $conn->real_escape_string($_POST['selected_date'])
            $selected_date = sanitize($_POST['selected_date']); 
            
            $am_capacity = (int)$_POST['date_daily_capacity_morning'];
            $pm_capacity = (int)$_POST['date_daily_capacity_afternoon'];

            $slotModel->setDailyOverride($selected_date, $am_capacity, $pm_capacity);
            
            $daily_capacity = $am_capacity + $pm_capacity;
            $success = "Daily capacity updated for $selected_date — $daily_capacity slots";
        } catch (Exception $e) {
            $error = 'Error setting date-specific capacity: ' . $e->getMessage();
        }
    }
}

// 5. Fetch Data for the View
$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$day_labels = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];

$dental_slots = $slotModel->getDentalSlots();

// Close connection before rendering HTML
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slot Capacity Management - CHO Admin</title>
    <link rel="stylesheet" href="assets/css/slot_management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body>
<div class="page-wrapper">

    <!-- Header -->
    <div class="header-card">
        <a href="manage_appointments_admin.php" class="back-btn">
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
                                Daily Capacity (Morning)
                            </label>
                            <input type="number" name="date_daily_capacity_morning" class="form-control capacity-number-input" min="0" max="500" value="100" required>
                            <label class="form-label">
                                <i class="fas fa-users me-1 text-primary"></i>
                                Daily Capacity (Afternoon)
                            </label>
                            <input type="number" name="date_daily_capacity_afternoon" class="form-control capacity-number-input" min="0" max="500" value="100" required>
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
