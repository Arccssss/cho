<?php
require_once '../../cho-apt/CHO/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();
$success = '';
$error = '';

// Filters
$date_filter = isset($_GET['date'])   ? $_GET['date']   : '';
$search      = isset($_GET['search']) ? trim($_GET['search']) : '';
$time_filter = isset($_GET['time'])   ? $_GET['time']   : 'all';

$where  = ["pe.purpose_of_visit LIKE '%[Ref: CHO-%'"];
$params = [];
$types  = "";

if (!empty($date_filter)) {
    $where[] = "pe.date_of_consultation = ?";
    $params[] = $date_filter;
    $types .= "s";
}
if ($time_filter !== 'all') {
    $where[] = "pe.consultation_time = ?";
    $params[] = $time_filter;
    $types .= "s";
}
if (!empty($search)) {
    $where[] = "(pe.first_name LIKE ? OR pe.last_name LIKE ? OR pe.contact_number LIKE ? OR pe.purpose_of_visit LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$sql = "SELECT pe.* FROM patient_enrollment pe
        WHERE " . implode(" AND ", $where) . "
        ORDER BY pe.date_of_consultation DESC, pe.created_at DESC";

$enrollments = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
if ($result) {
    while ($row = $result->fetch_assoc()) $enrollments[] = $row;
}

// Summary counts
$total_sql = "SELECT COUNT(*) as cnt FROM patient_enrollment WHERE purpose_of_visit LIKE '%[Ref: CHO-%'";
$total_row = $conn->query($total_sql)->fetch_assoc();
$total = (int)($total_row['cnt'] ?? 0);

$today_sql = "SELECT COUNT(*) as cnt FROM patient_enrollment WHERE purpose_of_visit LIKE '%[Ref: CHO-%' AND date_of_consultation = CURDATE()";
$today_row = $conn->query($today_sql)->fetch_assoc();
$today_count = (int)($today_row['cnt'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Enrollment (Online Bookings) - CHO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }

        .page-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            padding: 22px 32px;
            border-radius: 0 0 18px 18px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(13,110,253,0.3);
        }
        .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; }
        .page-header p  { margin: 4px 0 0; font-size: 0.85rem; opacity: 0.85; }
        .back-btn {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(255,255,255,0.18); color: #fff;
            text-decoration: none; padding: 8px 18px;
            border-radius: 50px; font-size: 0.85rem; font-weight: 500;
            border: 1.5px solid rgba(255,255,255,0.4);
            transition: background 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.3); color: #fff; }

        .stat-card {
            background: #fff; border-radius: 14px;
            padding: 20px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.78rem; color: #888; margin-top: 3px; }

        .filter-card {
            background: #fff; border-radius: 14px;
            padding: 18px 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            margin-bottom: 20px;
        }
        .filter-card .form-control, .filter-card .form-select {
            border-radius: 10px; border: 1.5px solid #dee2e6; font-size: 0.88rem;
        }

        .enroll-table { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .enroll-table table { margin: 0; }
        .enroll-table thead th {
            background: #f8f9fa; font-size: 0.78rem; text-transform: uppercase;
            letter-spacing: 0.5px; color: #666; border-bottom: 2px solid #e9ecef;
            padding: 12px 16px;
        }
        .enroll-table tbody td { padding: 13px 16px; font-size: 0.88rem; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        .enroll-table tbody tr:last-child td { border-bottom: none; }
        .enroll-table tbody tr:hover { background: #fafbff; }

        .source-badge { background: #e8f0fe; color: #0d6efd; border-radius: 20px; padding: 3px 10px; font-size: 0.72rem; font-weight: 600; }
        .time-badge-am { background: #fff3cd; color: #856404; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 600; }
        .time-badge-pm { background: #d1ecf1; color: #0c5460; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 600; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
    </style>
</head>
<body>

<div class="page-header">
    <div>
        <h2><i class="fas fa-file-medical me-2"></i>Patient Enrollment — Online Bookings</h2>
        <p>Auto-generated enrollment records from AppointmentCHO online bookings</p>
    </div>
    <a href="../../cho-apt/CHO/admin_dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Dashboard
    </a>
</div>

<div class="container-fluid px-4">

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f0fe;color:#0d6efd;"><i class="fas fa-file-medical"></i></div>
                <div>
                    <div class="stat-value"><?= $total ?></div>
                    <div class="stat-label">Total Online Enrollments</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1f5e0;color:#155724;"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <div class="stat-value"><?= $today_count ?></div>
                    <div class="stat-label">Today's Enrollments</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-sun"></i></div>
                <div>
                    <div class="stat-value"><?= count(array_filter($enrollments, fn($e) => $e['consultation_time'] === 'AM')) ?></div>
                    <div class="stat-label">AM (Filtered)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1ecf1;color:#0c5460;"><i class="fas fa-moon"></i></div>
                <div>
                    <div class="stat-value"><?= count(array_filter($enrollments, fn($e) => $e['consultation_time'] === 'PM')) ?></div>
                    <div class="stat-label">PM (Filtered)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, contact, purpose..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Time</label>
                <select name="time" class="form-select">
                    <option value="all" <?= $time_filter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="AM"  <?= $time_filter === 'AM'  ? 'selected' : '' ?>>Morning (AM)</option>
                    <option value="PM"  <?= $time_filter === 'PM'  ? 'selected' : '' ?>>Afternoon (PM)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold" style="font-size:0.82rem;">Visit Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" style="border-radius:10px;">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-1">
                <a href="create_patient_enrollment.php" class="btn btn-outline-secondary w-100" style="border-radius:10px;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Enrollment Table -->
    <div class="enroll-table">
        <?php if (empty($enrollments)): ?>
            <div class="empty-state">
                <i class="fas fa-file-medical-alt"></i>
                <p>No enrollment records found.</p>
                <small>Records are automatically created when clients complete an online booking.</small>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Age / Sex</th>
                        <th>Civil Status</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>PhilHealth No.</th>
                        <th>Purpose of Visit</th>
                        <th>Visit Date</th>
                        <th>Time</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrollments as $e): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name'] . ($e['middle_name'] ? ' ' . $e['middle_name'] : '')) ?></strong>
                            <?php if ($e['suffix']): ?>
                                <span class="text-muted" style="font-size:0.78rem;"> <?= htmlspecialchars($e['suffix']) ?></span>
                            <?php endif; ?>
                            <?php if ($e['maiden_name']): ?>
                                <br><small class="text-muted">Maiden: <?= htmlspecialchars($e['maiden_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($e['age']) ?> yrs<br>
                            <small class="text-muted"><?= htmlspecialchars(ucfirst($e['sex'])) ?></small>
                        </td>
                        <td><?= htmlspecialchars(ucfirst($e['civil_status'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars($e['residential_address'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($e['contact_number'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($e['philhealth_no'] ?: '—') ?></td>
                        <td style="max-width:180px;">
                            <?php
                                // Strip the [Ref: ...] part for display
                                $display_purpose = preg_replace('/\s*\[Ref:[^\]]+\]/', '', $e['purpose_of_visit']);
                                echo htmlspecialchars($display_purpose);
                            ?>
                        </td>
                        <td>
                            <?= $e['date_of_consultation'] ? date('M d, Y', strtotime($e['date_of_consultation'])) : '—' ?>
                        </td>
                        <td>
                            <?php if ($e['consultation_time'] === 'AM'): ?>
                                <span class="time-badge-am">Morning</span>
                            <?php elseif ($e['consultation_time'] === 'PM'): ?>
                                <span class="time-badge-pm">Afternoon</span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><span class="source-badge">Online</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <p class="text-muted mt-3" style="font-size:0.8rem;">
        <i class="fas fa-info-circle me-1"></i>
        These records are automatically inserted into <code>patient_enrollment</code> when a client completes an online booking at AppointmentCHO.
        The remaining clinical fields (vitals, diagnosis, medication, etc.) can be filled in by the healthcare provider during the actual visit.
    </p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
