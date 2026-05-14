<?php
// ari ang gn butang ko file para sa admin-dashboard nga appointments
session_start();
// 1. Require your main app configuration files
require_once 'config/helpers.php';
require_once 'config/database.php';
require_once 'models/AppointmentsModel.php'; // Ensure this points to where you put the model code

// 2. Use your central authentication checks
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();
$appointmentModel = new AppointmentModel($conn);

// 3. Handle appointment status updates via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $action = sanitize($_POST['action']); // Assuming sanitize is in your helpers.php
    
    $status_map = [
        'confirm' => 'confirmed',
        'cancel' => 'cancelled',
        'complete' => 'completed',
        'no_show' => 'no_show'
    ];

    if (array_key_exists($action, $status_map)) {
        $appointmentModel->updateAppointmentStatus($appointment_id, $status_map[$action]);
        $message = "Appointment updated successfully!";
        
        // Redirect to prevent form resubmission
        redirect("manage_appointments.php?message=" . urlencode($message));
    }
}

// 4. Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$time_filter = isset($_GET['time']) ? sanitize($_GET['time']) : 'all';
$view_type = isset($_GET['view']) ? sanitize($_GET['view']) : 'today';

if ($view_type === 'today' && empty($date_filter)) {
    $date_filter = date('Y-m-d');
}

// 5. Fetch Data from Model
$all_appointments = $appointmentModel->getAppointmentsList($view_type, $date_filter, $status_filter, $search, $time_filter);
$stats = $appointmentModel->getStatistics();

// Close connection before rendering view
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - CHO Admin</title>
    <link rel="stylesheet" href="assets/css/manage_appointments_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="slot_management.php"><i class="fas fa-cogs"></i> Manage Slot Capacity</a></li>
                <li><a href="appointment_calendar.php"><i class="fas fa-calendar"></i> Calendar</a></li>
                <li><a href="all_forms.php"><i class="fas fa-file-medical"></i> Consent Forms</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h1>Appointment Management</h1>
                <p>Manage and monitor all patient appointments</p>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #10b981;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="icon"><i class="fas fa-calendar"></i></div>
                    <div class="number"><?php echo $stats['total']; ?></div>
                    <div class="label">Total Appointments</div>
                </div>
                <div class="stat-card pending">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <div class="number"><?php echo $stats['pending']; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card confirmed">
                    <div class="icon"><i class="fas fa-check"></i></div>
                    <div class="number"><?php echo $stats['confirmed']; ?></div>
                    <div class="label">Confirmed</div>
                </div>
                <div class="stat-card completed">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo $stats['completed']; ?></div>
                    <div class="label">Completed</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                    <div class="number"><?php echo $stats['cancelled']; ?></div>
                    <div class="label">Cancelled</div>
                </div>
                <div class="stat-card no-show">
                    <div class="icon"><i class="fas fa-user-slash"></i></div>
                    <div class="number"><?php echo $stats['no_show']; ?></div>
                    <div class="label">No Show</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <?php if ($status_filter !== 'all' || $time_filter !== 'all' || !empty($date_filter) || !empty($search)): ?>
                    <div class="active-filters" style="background: #e3f2fd; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #2196f3;">
                        <strong>Active Filters:</strong>
                        <?php if ($status_filter !== 'all'): ?>
                            <span class="filter-tag">Status: <?php echo ucfirst($status_filter); ?></span>
                        <?php endif; ?>
                        <?php if ($time_filter !== 'all'): ?>
                            <span class="filter-tag">Time: <?php echo $time_filter === 'AM' ? 'Morning (AM)' : 'Afternoon (PM)'; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($date_filter)): ?>
                            <span class="filter-tag">Date: <?php echo date('M d, Y', strtotime($date_filter)); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <span class="filter-tag">Search: "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                        <a href="?view=<?php echo $view_type; ?>" style="float: right; color: #2196f3; text-decoration: none; font-weight: 600;">✕ Clear All</a>
                    </div>
                <?php endif; ?>
                
                <form method="GET" class="filters-grid">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_type); ?>">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="no_show" <?php echo $status_filter === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date">
                            <?php echo $view_type === 'today' ? 'Browse Date' : 'Date (History)'; ?>
                        </label>
                        <input type="text" id="date" name="date"
                               placeholder="YYYY/MM/DD"
                               pattern="\d{4}/\d{2}/\d{2}"
                               maxlength="10"
                               value="<?php echo $date_filter ? date('Y/m/d', strtotime($date_filter)) : ''; ?>"
                               autocomplete="off"
                               style="letter-spacing:1px;">
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Patient name, contact, purpose..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="?view=<?php echo $view_type; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Appointments Tables -->
            <div class="appointments-section">
                <div class="view-header">
                    <div>
                        <h2 class="view-title">
                            <?php echo $view_type === 'today' ? 'Today\'s Appointments' : 'History of Appointments'; ?>
                        </h2>
                        <div class="view-subtitle">
                            <?php 
                            if ($view_type === 'today') {
                                $display_date = !empty($date_filter) ? $date_filter : date('Y-m-d');
                                $label = ($display_date === date('Y-m-d')) ? "Today" : date('F d, Y', strtotime($display_date));
                                echo $label . ' — ' . date('F d, Y', strtotime($display_date));
                            } else {
                                if (!empty($date_filter)) {
                                    echo 'Appointments on ' . date('F d, Y', strtotime($date_filter));
                                } else {
                                    echo 'All past appointments before today';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="view-toggle">
                        <a href="?view=today" class="view-btn <?php echo $view_type === 'today' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-day"></i> Today
                        </a>
                        <a href="?view=history" class="view-btn <?php echo $view_type === 'history' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i> History
                        </a>
                    </div>
                </div>
                
                <div class="tables-container" style="display:block;">
                    <!-- All Appointments Table -->
                    <div class="time-section" style="border-top: 4px solid #FF6B35;">
                        <div class="time-header">
                            <div class="time-title">
                                <i class="fas fa-calendar-day" style="color: #FF6B35;"></i>
                                <span>Whole Day Appointments</span>
                                <span class="time-badge-large" style="background: linear-gradient(135deg,#FF6B35,#F7931E); color:white; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600;">8:00 AM – 5:00 PM</span>
                            </div>
                            <span style="color: #666; font-weight: 600;"><?php echo count($all_appointments); ?> booking<?php echo count($all_appointments) !== 1 ? 's' : ''; ?></span>
                        </div>

                        <?php if (count($all_appointments) > 0): ?>
                            <div class="table-container">
                                <table class="appointments-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Patient Name</th>
                                            <th>Contact</th>
                                            <th>Date</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_appointments as $appointment): ?>
                                            <tr>
                                                <td>#<?php echo $appointment['id']; ?></td>
                                                <td>
                                                    <strong
                                                        class="patient-name-link"
                                                        onclick="showDetails(<?php echo $appointment['id']; ?>)"
                                                        title="View details"
                                                    ><?php echo htmlspecialchars($appointment['patient_display_name']); ?></strong>
                                                    <?php if (!empty($appointment['patient_email'])): ?>
                                                        <br><small style="color: #666;"><?php echo htmlspecialchars($appointment['patient_email']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['contact_number']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                    <br><small style="color:#FF6B35; font-weight:600;">Whole Day</small>
                                                </td>
                                                <td style="max-width:160px;">
                                                    <small><?php echo htmlspecialchars($appointment['purpose'] ?? '—'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                        <?php echo str_replace('_', ' ', $appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if ($appointment['status'] === 'pending'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                <input type="hidden" name="action" value="confirm">
                                                                <button type="submit" class="btn btn-action btn-confirm" title="Confirm">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                <input type="hidden" name="action" value="cancel">
                                                                <button type="submit" class="btn btn-action btn-cancel" title="Cancel">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($appointment['status'] === 'confirmed'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                <input type="hidden" name="action" value="complete">
                                                                <button type="submit" class="btn btn-action btn-complete" title="Mark Complete">
                                                                    <i class="fas fa-check-circle"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                                <input type="hidden" name="action" value="no_show">
                                                                <button type="submit" class="btn btn-action btn-no-show" title="No Show">
                                                                    <i class="fas fa-user-slash"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="text-align: center; padding: 60px 20px;">
                                <i class="fas fa-calendar-times" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                <h3>No Appointments Found</h3>
                                <p><?php echo $view_type === 'today' ? 'No appointments for the selected date.' : 'No appointments found for the selected period.'; ?></p>
                                <?php if ($view_type === 'today'): ?>
                                    <br><a href="?view=history" class="btn btn-primary" style="margin-top: 15px;">
                                        <i class="fas fa-history"></i> Browse Appointment History
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (count($all_appointments) === 0): ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

<!-- ── Client Details Modal ─────────────────────────────────────── -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:90%;max-width:620px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <!-- Modal header -->
        <div style="background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;padding:20px 24px;border-radius:16px 16px 0 0;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3 style="margin:0;font-size:1.1rem;font-weight:700;"><i class="fas fa-user-circle me-2"></i>Client Appointment Details</h3>
                <small id="modal-ref" style="opacity:.85;"></small>
            </div>
            <button onclick="closeDetails()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>
        <!-- Modal body -->
        <div style="padding:24px;" id="modal-body">
            <!-- filled by JS -->
        </div>
    </div>
</div>

<style>
.patient-name-link {
    cursor: pointer;
    color: #FF6B35;
    text-decoration: underline dotted;
    transition: color .15s;
}
.patient-name-link:hover { color: #c94e1a; }
.detail-section { margin-bottom: 18px; }
.detail-section-title {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #9ca3af;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
}
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.detail-item { display: flex; flex-direction: column; gap: 2px; }
.detail-item.full { grid-column: 1 / -1; }
.detail-label { font-size: .72rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.detail-value { font-size: .9rem; color: #111827; font-weight: 500; }
.detail-value.empty { color: #d1d5db; font-style: italic; }
</style>

<script>
const APPT_DATA = <?php
    $out = [];
    foreach ($all_appointments as $a) {
        $out[$a['id']] = [
            'id'               => $a['id'],
            'reference_number' => $a['reference_number'] ?? '',
            'first_name'       => $a['first_name'] ?? '',
            'middle_name'      => $a['middle_name'] ?? '',
            'last_name'        => $a['last_name'] ?? '',
            'suffix'           => $a['suffix'] ?? '',
            'patient_display_name' => $a['patient_display_name'] ?? '',
            'philhealth_no'    => $a['philhealth_no'] ?? '',
            'date_of_birth'    => $a['date_of_birth'] ?? '',
            'sex'              => $a['sex'] ?? '',
            'civil_status'     => $a['civil_status'] ?? '',
            'barangay'         => $a['barangay'] ?? '',
            'contact_number'   => $a['contact_number'] ?? '',
            'email'            => $a['email'] ?? '',
            'appointment_date' => $a['appointment_date'] ?? '',
            'time_period'      => $a['time_period'] ?? '',
            'purpose'          => $a['purpose'] ?? '',
            'notes'            => $a['notes'] ?? '',
            'status'           => $a['status'] ?? '',
            'created_at'       => $a['created_at'] ?? '',
        ];
    }
    echo json_encode($out);
?>;

function showDetails(id) {
    const a = APPT_DATA[id];
    if (!a) return;

    const name = a.patient_display_name ||
        [a.first_name, a.middle_name, a.last_name, a.suffix].filter(Boolean).join(' ') ||
        'Unknown Patient';

    const timePeriod = 'Whole Day (8:00 AM – 5:00 PM)';

    const fmtDate = v => {
        if (!v) return '—';
        const d = new Date(v + 'T00:00:00');
        return d.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
    };

    const val = v => v ? `<span class="detail-value">${v}</span>` : `<span class="detail-value empty">—</span>`;

    const statusColors = {
        pending:   '#fef3c7;color:#92400e',
        confirmed: '#dbeafe;color:#1e40af',
        completed: '#d1fae5;color:#065f46',
        cancelled: '#fee2e2;color:#991b1b',
        no_show:   '#e5e7eb;color:#374151',
    };
    const sc = statusColors[a.status] || '#f3f4f6;color:#374151';
    const statusBadge = `<span style="background:${sc.split(';')[0].replace('background:','')};${sc.split(';')[1]};padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase;">${a.status.replace('_',' ')}</span>`;

    document.getElementById('modal-ref').textContent = a.reference_number ? `Ref: ${a.reference_number}` : `#${a.id}`;

    document.getElementById('modal-body').innerHTML = `
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-user me-1"></i> Personal Information</div>
            <div class="detail-grid">
                <div class="detail-item full">
                    <span class="detail-label">Full Name</span>
                    ${val(name)}
                </div>
                <div class="detail-item">
                    <span class="detail-label">PhilHealth No.</span>
                    ${val(a.philhealth_no)}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date of Birth</span>
                    ${val(fmtDate(a.date_of_birth))}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Sex</span>
                    ${val(a.sex ? a.sex.charAt(0).toUpperCase() + a.sex.slice(1) : '')}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Civil Status</span>
                    ${val(a.civil_status ? a.civil_status.charAt(0).toUpperCase() + a.civil_status.slice(1) : '')}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Barangay</span>
                    ${val(a.barangay)}
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-phone me-1"></i> Contact</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Contact Number</span>
                    ${val(a.contact_number)}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    ${val(a.email)}
                </div>
            </div>
        </div>

        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-calendar-check me-1"></i> Appointment</div>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Date</span>
                    ${val(fmtDate(a.appointment_date))}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Time</span>
                    ${val(timePeriod)}
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">${statusBadge}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Booked On</span>
                    ${val(a.created_at ? new Date(a.created_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : '')}
                </div>
                <div class="detail-item full">
                    <span class="detail-label">Purpose of Visit</span>
                    ${val(a.purpose)}
                </div>
                ${a.notes ? `<div class="detail-item full"><span class="detail-label">Notes</span>${val(a.notes)}</div>` : ''}
            </div>
        </div>
    `;

    const modal = document.getElementById('detailModal');
    modal.style.display = 'flex';
}

function closeDetails() {
    document.getElementById('detailModal').style.display = 'none';
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetails();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetails(); });

// ── Date input: auto-format as YYYY/MM/DD and convert to YYYY-MM-DD on submit ──
(function() {
    const dateInput = document.getElementById('date');
    if (!dateInput) return;

    dateInput.addEventListener('input', function () {
        let v = this.value.replace(/[^\d]/g, '');
        if (v.length > 4)  v = v.slice(0,4) + '/' + v.slice(4);
        if (v.length > 7)  v = v.slice(0,7) + '/' + v.slice(7);
        if (v.length > 10) v = v.slice(0,10);
        this.value = v;
    });

    dateInput.closest('form').addEventListener('submit', function () {
        const val = dateInput.value.trim();
        if (val && val.includes('/')) {
            dateInput.value = val.replace(/\//g, '-');
        }
    });
})();
</script>

</body>
</html>
