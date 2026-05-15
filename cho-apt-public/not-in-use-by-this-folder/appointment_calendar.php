<?php
require_once 'database.php';

session_start();

function isLoggedIn() { return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); }
function isAdmin()    { return isset($_SESSION['role'])    && $_SESSION['role'] === 'admin'; }

if (!isLoggedIn() || !isAdmin()) {
    header('../cho-apt/admin_dashboard.php');
    exit;
}

// Month / year navigation
$month = isset($_GET['month']) ? intval($_GET['month']) : (int)date('n');
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : (int)date('Y');
if ($month < 1 || $month > 12) $month = (int)date('n');
if ($year  < 2020 || $year > 2030) $year = (int)date('Y');

$prev_month = $month == 1  ? 12 : $month - 1;
$prev_year  = $month == 1  ? $year - 1 : $year;
$next_month = $month == 12 ? 1  : $month + 1;
$next_year  = $month == 12 ? $year + 1 : $year;

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_dow     = (int)date('N', mktime(0,0,0,$month,1,$year)); // 1=Mon … 7=Sun
$month_name    = date('F', mktime(0,0,0,$month,1,$year));

// ── Build slot data for every weekday in the month ──────────────────────────
$conn = getDBConnection();

// Load weekday capacities from appointment_am_pm_slots
$cap = [];
$sl  = $conn->query("SELECT day_of_week, time_period, max_appointments FROM appointment_am_pm_slots WHERE is_active = 1");
if ($sl) {
    while ($r = $sl->fetch_assoc()) {
        $cap[$r['day_of_week']][$r['time_period']] = (int)$r['max_appointments'];
    }
}

// Load date-specific overrides for this month
$overrides = [];
try {
    $ov = $conn->prepare("SELECT override_date, am_capacity, pm_capacity FROM date_slot_overrides WHERE MONTH(override_date)=? AND YEAR(override_date)=?");
    $ov->bind_param("ii", $month, $year);
    $ov->execute();
    $ovr = $ov->get_result();
    while ($r = $ovr->fetch_assoc()) {
        $overrides[$r['override_date']] = ['AM' => (int)$r['am_capacity'], 'PM' => (int)$r['pm_capacity']];
    }
    $ov->close();
} catch (Exception $e) {}

// Count booked appointments per date/period
$booked = [];
$bq = $conn->prepare("SELECT appointment_date, time_period, COUNT(*) AS n FROM appointments WHERE MONTH(appointment_date)=? AND YEAR(appointment_date)=? AND status IN ('pending','confirmed','completed') GROUP BY appointment_date, time_period");
$bq->bind_param("ii", $month, $year);
$bq->execute();
$bqr = $bq->get_result();
while ($r = $bqr->fetch_assoc()) {
    $booked[$r['appointment_date']][$r['time_period']] = (int)$r['n'];
}
$bq->close();

// Load dental slot capacities per day-of-week
$dental_cap = [];
try {
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

    // Seed defaults if empty
    $conn->query("INSERT IGNORE INTO dental_slots (day_of_week, max_appointments) VALUES
        ('monday',10),('tuesday',10),('wednesday',10),('thursday',10),('friday',10),('saturday',0),('sunday',0)");

    $ds = $conn->query("SELECT day_of_week, max_appointments FROM dental_slots WHERE is_active = 1");
    if ($ds) {
        while ($r = $ds->fetch_assoc()) {
            $dental_cap[$r['day_of_week']] = (int)$r['max_appointments'];
        }
    }
} catch (Exception $e) {}

// Count booked dental appointments per date
$dental_booked = [];
try {
    $dbq = $conn->prepare("SELECT appointment_date, COUNT(*) AS n FROM appointments WHERE MONTH(appointment_date)=? AND YEAR(appointment_date)=? AND status IN ('pending','confirmed','completed') AND purpose LIKE '%Dental%' GROUP BY appointment_date");
    $dbq->bind_param("ii", $month, $year);
    $dbq->execute();
    $dbqr = $dbq->get_result();
    while ($r = $dbqr->fetch_assoc()) {
        $dental_booked[$r['appointment_date']] = (int)$r['n'];
    }
    $dbq->close();
} catch (Exception $e) {}

$conn->close();
// Build final slots array keyed by date string
$slots = [];
for ($d = 1; $d <= $days_in_month; $d++) {
    $date    = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $dow     = strtolower(date('l', strtotime($date)));
    $dow_num = (int)date('N', strtotime($date)); // 1=Mon…7=Sun
    if ($dow_num >= 6) continue; // skip weekends

    if (isset($overrides[$date])) {
        $max_am = $overrides[$date]['AM'];
        $max_pm = $overrides[$date]['PM'];
    } else {
        $max_am = $cap[$dow]['AM'] ?? 50;
        $max_pm = $cap[$dow]['PM'] ?? 50;
    }

    $used_am = $booked[$date]['AM'] ?? 0;
    $used_pm = $booked[$date]['PM'] ?? 0;

    $dental_max  = $dental_cap[$dow] ?? 10;
    $dental_used = $dental_booked[$date] ?? 0;

    $slots[$date] = [
        'am_max'       => $max_am,
        'pm_max'       => $max_pm,
        'am_used'      => $used_am,
        'pm_used'      => $used_pm,
        'am_left'      => max(0, $max_am - $used_am),
        'pm_left'      => max(0, $max_pm - $used_pm),
        'dental_max'   => $dental_max,
        'dental_used'  => $dental_used,
        'dental_left'  => max(0, $dental_max - $dental_used),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slot Calendar – CHO Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f5f5f5; min-height: 100vh; }

/* ── Layout ── */
.admin-container { display: flex; min-height: 100vh; }
.sidebar {
    width: 250px; flex-shrink: 0;
    background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
    color: white; padding: 20px;
    box-shadow: 2px 0 10px rgba(0,0,0,.1);
}
.sidebar-header { text-align: center; padding-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,.2); margin-bottom: 24px; }
.sidebar-header h2 { font-size: 22px; font-weight: 700; }
.sidebar-header p  { font-size: 13px; opacity: .85; margin-top: 4px; }
.sidebar-nav { list-style: none; }
.sidebar-nav li { margin-bottom: 4px; }
.sidebar-nav a {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 14px; color: white; text-decoration: none;
    border-radius: 8px; font-weight: 500; transition: all .2s;
}
.sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,.25); transform: translateX(4px); }

.main-content { flex: 1; padding: 28px; overflow-x: auto; }

/* ── Page header ── */
.page-header {
    background: white; border-radius: 14px;
    padding: 22px 28px; margin-bottom: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
}
.page-header h1 { color: #FF6B35; font-size: 26px; font-weight: 700; }
.page-header .actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn {
    padding: 9px 18px; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none; transition: all .2s;
}
.btn-primary { background: linear-gradient(135deg,#FF6B35,#F7931E); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(255,107,53,.35); }
.btn-secondary { background: #6b7280; color: white; }
.btn-secondary:hover { background: #4b5563; }

/* ── Calendar card ── */
.cal-card {
    background: white; border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden;
}
.cal-nav-bar {
    background: linear-gradient(135deg,#FF6B35,#F7931E);
    color: white; padding: 18px 24px;
    display: flex; justify-content: space-between; align-items: center;
}
.cal-nav-bar h2 { font-size: 22px; font-weight: 700; }
.cal-nav-bar .nav-links { display: flex; gap: 10px; }
.cal-nav-bar a {
    color: white; text-decoration: none;
    padding: 7px 14px; border-radius: 7px;
    background: rgba(255,255,255,.2); font-weight: 600; font-size: 13px;
    transition: background .2s;
}
.cal-nav-bar a:hover { background: rgba(255,255,255,.35); }

/* ── Grid ── */
.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border-left: 1px solid #e5e7eb;
    border-top: 1px solid #e5e7eb;
}
.cal-dow {
    background: #f9fafb; padding: 12px 6px;
    text-align: center; font-size: 12px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; color: #6b7280;
    border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;
}
.cal-cell {
    min-height: 110px; padding: 8px;
    border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;
    display: flex; flex-direction: column; gap: 4px;
    position: relative;
}
.cal-cell.other-month { background: #f9fafb; }
.cal-cell.weekend     { background: #fafafa; }
.cal-cell.is-today    { background: #fff8f5; }
.cal-cell.is-today .day-num {
    background: #FF6B35; color: white;
    border-radius: 50%; width: 26px; height: 26px;
    display: flex; align-items: center; justify-content: center;
}
.day-num { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 2px; }
.other-month .day-num { color: #d1d5db; }
.weekend .day-num     { color: #9ca3af; }

/* ── Slot pills ── */
.slot-pill {
    display: flex; align-items: center; justify-content: space-between;
    padding: 3px 7px; border-radius: 6px; font-size: 10px; font-weight: 700;
    line-height: 1.3;
}
.slot-pill .period { text-transform: uppercase; letter-spacing: .04em; opacity: .75; }
.slot-pill .count  { font-size: 11px; }

.pill-open   { background: #dcfce7; color: #166534; }
.pill-low    { background: #fef9c3; color: #854d0e; }
.pill-full   { background: #fee2e2; color: #991b1b; }
.pill-na     { background: #f3f4f6; color: #9ca3af; }

/* ── Legend ── */
.cal-legend {
    display: flex; gap: 18px; flex-wrap: wrap;
    padding: 14px 20px; border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6b7280; }
.legend-dot  { width: 12px; height: 12px; border-radius: 3px; }

/* ── Summary bar ── */
.summary-bar {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px; margin-bottom: 24px;
}
.sum-card {
    background: white; border-radius: 12px; padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07); text-align: center;
}
.sum-card .num  { font-size: 28px; font-weight: 700; color: #FF6B35; }
.sum-card .lbl  { font-size: 12px; color: #6b7280; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

@media (max-width: 900px) {
    .admin-container { flex-direction: column; }
    .sidebar { width: 100%; }
    .cal-cell { min-height: 80px; }
    .slot-pill { font-size: 9px; padding: 2px 5px; }
}
</style>
</head>
<body>
<div class="admin-container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>CHO Admin</h2>
            <p>Appointment Management</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../cho-apt/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_appointments.php"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
            <li><a href="slot_management.php"><i class="fas fa-cogs"></i> Manage Slot Capacity</a></li>
            <li><a href="appointment_calendar.php" class="active"><i class="fas fa-calendar"></i> Slot Calendar</a></li>
            <li><a href="../cho-apt/all_forms.php"><i class="fas fa-file-medical"></i> Consent Forms</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main -->
    <main class="main-content">

        <!-- Page header -->
        <div class="page-header">
            <h1><i class="fas fa-calendar-check" style="color:#FF6B35;margin-right:10px;"></i>Slot Availability Calendar</h1>
            <div class="actions">
                <a href="manage_appointments.php" class="btn btn-secondary"><i class="fas fa-list"></i> Manage Appointments</a>
                <a href="slot_management.php"     class="btn btn-primary"><i class="fas fa-cogs"></i> Slot Settings</a>
            </div>
        </div>

        <?php
        // ── Month summary numbers ────────────────────────────────────
        $total_am_left = $total_pm_left = $total_am_max = $total_pm_max = 0;
        $total_dental_left = $total_dental_used = 0;
        $fully_booked_days = 0;
        foreach ($slots as $s) {
            $total_am_left += $s['am_left'];
            $total_pm_left += $s['pm_left'];
            $total_am_max  += $s['am_max'];
            $total_pm_max  += $s['pm_max'];
            $total_dental_left += $s['dental_left'];
            $total_dental_used += $s['dental_used'];
            if ($s['am_left'] === 0 && $s['pm_left'] === 0) $fully_booked_days++;
        }
        $total_booked = array_sum(array_column($slots,'am_used')) + array_sum(array_column($slots,'pm_used'));
        $working_days = count($slots);
        ?>

        <!-- Summary bar -->
        <div class="summary-bar">
            <div class="sum-card">
                <div class="num"><?= $working_days ?></div>
                <div class="lbl">Working Days</div>
            </div>
            <div class="sum-card">
                <div class="num"><?= $total_booked ?></div>
                <div class="lbl">Total Booked</div>
            </div>
            <div class="sum-card">
                <div class="num" style="color:#166534;"><?= $total_am_left + $total_pm_left ?></div>
                <div class="lbl">General Slots Left</div>
            </div>
            <div class="sum-card">
                <div class="num" style="color:#1d4ed8;"><?= $total_dental_left ?></div>
                <div class="lbl">🦷 Dental Slots Left</div>
            </div>
            <div class="sum-card">
                <div class="num" style="color:#991b1b;"><?= $fully_booked_days ?></div>
                <div class="lbl">Fully Booked Days</div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="cal-card">
            <!-- Nav bar -->
            <div class="cal-nav-bar">
                <div class="nav-links">
                    <a href="?month=<?= $prev_month ?>&year=<?= $prev_year ?>"><i class="fas fa-chevron-left"></i> Prev</a>
                    <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>">Today</a>
                    <a href="?month=<?= $next_month ?>&year=<?= $next_year ?>">Next <i class="fas fa-chevron-right"></i></a>
                </div>
                <h2><?= $month_name ?> <?= $year ?></h2>
                <div style="width:160px;"></div><!-- spacer -->
            </div>

            <!-- Grid -->
            <div class="cal-grid">
                <!-- Day-of-week headers (Mon–Sun) -->
                <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $h): ?>
                    <div class="cal-dow"><?= $h ?></div>
                <?php endforeach; ?>

                <?php
                $today_str = date('Y-m-d');

                // Leading empty cells (Mon=1 … so offset = $first_dow - 1)
                for ($i = 1; $i < $first_dow; $i++) {
                    echo '<div class="cal-cell other-month"></div>';
                }

                for ($d = 1; $d <= $days_in_month; $d++) {
                    $date    = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $dow_num = (int)date('N', strtotime($date)); // 1=Mon…7=Sun
                    $is_today   = ($date === $today_str);
                    $is_weekend = ($dow_num >= 6);

                    $classes = 'cal-cell';
                    if ($is_today)   $classes .= ' is-today';
                    if ($is_weekend) $classes .= ' weekend';

                    echo '<div class="' . $classes . '">';
                    echo '<div class="day-num">' . $d . '</div>';

                    if (!$is_weekend && isset($slots[$date])) {
                        $s = $slots[$date];

                        // General slot pill (excludes dental bookings)
                        $gen_max  = $s['am_max'] + $s['pm_max'];
                        $gen_used = ($s['am_used'] + $s['pm_used']) - ($s['dental_used']);
                        $gen_left = max(0, $gen_max - $gen_used);
                        $total_used = $s['am_used'] + $s['pm_used'];

                        if ($gen_max == 0) {
                            $gen_class = 'pill-na'; $gen_txt = 'N/A';
                        } elseif ($gen_left == 0) {
                            $gen_class = 'pill-full'; $gen_txt = 'Full';
                        } elseif ($gen_left <= 10) {
                            $gen_class = 'pill-low'; $gen_txt = $gen_left . ' left';
                        } else {
                            $gen_class = 'pill-open'; $gen_txt = $gen_left . ' slots';
                        }

                        echo '<div class="slot-pill ' . $gen_class . '">'
                           . '<span class="period">General</span>'
                           . '<span class="count">' . $gen_txt . '</span>'
                           . '</div>';

                        // Dental slot pill
                        $d_max  = $s['dental_max'];
                        $d_left = $s['dental_left'];
                        $d_used = $s['dental_used'];

                        if ($d_max == 0) {
                            $d_class = 'pill-na'; $d_txt = 'N/A';
                        } elseif ($d_left == 0) {
                            $d_class = 'pill-full'; $d_txt = 'Full';
                        } elseif ($d_left <= 3) {
                            $d_class = 'pill-low'; $d_txt = $d_left . ' left';
                        } else {
                            $d_class = 'pill-open'; $d_txt = $d_left . ' slots';
                        }

                        echo '<div class="slot-pill ' . $d_class . '" style="margin-top:2px;">'
                           . '<span class="period" style="color:#1d4ed8;">🦷 Dental</span>'
                           . '<span class="count">' . $d_txt . '</span>'
                           . '</div>';

                        if ($total_used > 0) {
                            echo '<div style="font-size:9px;color:#9ca3af;text-align:right;margin-top:auto;">'
                               . $total_used . ' booked</div>';
                        }
                    } elseif ($is_weekend) {
                        echo '<div style="font-size:10px;color:#d1d5db;margin-top:4px;">Weekend</div>';
                    }

                    echo '</div>';
                }

                // Trailing empty cells to complete the last row
                $trailing = (7 - (($first_dow - 1 + $days_in_month) % 7)) % 7;
                for ($i = 0; $i < $trailing; $i++) {
                    echo '<div class="cal-cell other-month"></div>';
                }
                ?>
            </div><!-- /cal-grid -->

            <!-- Legend -->
            <div class="cal-legend">
                <div class="legend-item"><div class="legend-dot" style="background:#dcfce7;border:1px solid #86efac;"></div> Available</div>
                <div class="legend-item"><div class="legend-dot" style="background:#fef9c3;border:1px solid #fde047;"></div> Almost Full (≤5)</div>
                <div class="legend-item"><div class="legend-dot" style="background:#fee2e2;border:1px solid #fca5a5;"></div> Fully Booked</div>
                <div class="legend-item"><div class="legend-dot" style="background:#f3f4f6;border:1px solid #e5e7eb;"></div> N/A / Weekend</div>
                <div class="legend-item"><div class="legend-dot" style="background:#fff8f5;border:2px solid #FF6B35;"></div> Today</div>
            </div>
        </div><!-- /cal-card -->

    </main>
</div>
</body>
</html>
