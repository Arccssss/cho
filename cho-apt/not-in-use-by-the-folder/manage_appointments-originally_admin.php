

<?php
// this file was originally in cho-apt but idk kng gna gmit sya kay ang admin dashboard nga appointment button sa manage_appointment sa public ga redirect.
// di ko lg ni pag delete kay bsi gmiton

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_appointment'])) {
        $appointment_id = sanitize($_POST['appointment_id']);
        
        $delete_sql = "DELETE FROM appointments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $appointment_id);
        
        if ($delete_stmt->execute()) {
            setFlashMessage('success', 'Appointment deleted successfully.');
        } else {
            setFlashMessage('danger', 'Error deleting appointment.');
        }
    }
    
    if (isset($_POST['update_status'])) {
        $appointment_id = sanitize($_POST['appointment_id']);
        $new_status = sanitize($_POST['status']);
        
        $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $appointment_id);
        
        if ($update_stmt->execute()) {
            setFlashMessage('success', 'Appointment status updated successfully.');
        } else {
            setFlashMessage('danger', 'Error updating appointment status.');
        }
    }
}

// Get filters
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build the query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(a.appointment_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(a.client_name LIKE ? OR a.contact_number LIKE ? OR a.reference_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get appointments with user info (only if user_id exists)
$check_user_id = $conn->query("SHOW COLUMNS FROM appointments LIKE 'user_id'");
$has_user_id = $check_user_id->num_rows > 0;

if ($has_user_id) {
    $sql = "SELECT a.*, u.full_name as staff_name, u.email as staff_email 
            FROM appointments a 
            LEFT JOIN users u ON a.user_id = u.id 
            $where_clause
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
} else {
    $sql = "SELECT a.* 
            FROM appointments a 
            $where_clause
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments_result = $stmt->get_result();

// Get statistics for dashboard
$today_sql = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE() AND status NOT IN ('cancelled', 'no_show')";
$today_result = $conn->query($today_sql);
$today_count = $today_result->fetch_assoc()['count'];

$pending_sql = "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'";
$pending_result = $conn->query($pending_sql);
$pending_count = $pending_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - CHO Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        .appointment-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #1e3c72;
            transition: all 0.3s ease;
        }
        .appointment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-no_show { background: #e2e3e5; color: #383d41; }
        .btn-custom {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
        }
        .stats-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-hospital me-2"></i>CHO Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="manage_appointments.php">
                    <i class="fas fa-calendar-check me-1"></i>Appointments
                </a>
                <a class="nav-link" href="holiday_management.php">
                    <i class="fas fa-calendar-times me-1"></i>Holiday Management
                </a>
                <a class="nav-link" href="client_notifications.php">
                    <i class="fas fa-bell me-1"></i>Notifications
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?= $today_count ?></div>
                    <div class="stats-label">Today's Appointments</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-number"><?= $pending_count ?></div>
                    <div class="stats-label">Pending Appointments</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= $date_filter ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="no_show" <?= $status_filter === 'no_show' ? 'selected' : '' ?>>No Show</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?= $search ?>" placeholder="Name, Contact, or Reference">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-custom w-100">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Appointments</h5>
            </div>
            <div class="card-body">
                <?php if ($appointments_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $appointment['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($appointment['client_name'] ?? $appointment['patient_name'] ?? 'N/A') ?>
                                            <?php if (!empty($appointment['reference_number'])): ?>
                                                <br><small class="text-muted">Ref: <?= htmlspecialchars($appointment['reference_number']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($appointment['contact_number']) ?>
                                            <?php if (!empty($appointment['email'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($appointment['email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($appointment['appointment_date'])) ?><br>
                                            <?= date('g:i A', strtotime($appointment['appointment_time'])) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $appointment['status'] ?>">
                                                <?= ucfirst(str_replace('_', ' ', $appointment['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?= $appointment['id'] ?>" title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $appointment['id'] ?>" title="Edit Status">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                                <button type="submit" name="delete_appointment" class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this appointment?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No appointments found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <?php
    // Reset result pointer to loop again for modals
    $appointments_result->data_seek(0);
    while ($appointment = $appointments_result->fetch_assoc()):
    ?>
    <div class="modal fade" id="viewModal<?= $appointment['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="fas fa-eye me-2"></i>Appointment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Patient Information</h6>
                            <p><strong>Name:</strong> <?= htmlspecialchars($appointment['client_name'] ?? $appointment['patient_name'] ?? 'N/A') ?></p>
                            <?php if (!empty($appointment['reference_number'])): ?>
                                <p><strong>Reference Number:</strong> <?= htmlspecialchars($appointment['reference_number']) ?></p>
                            <?php endif; ?>
                            <p><strong>Contact Number:</strong> <?= htmlspecialchars($appointment['contact_number']) ?></p>
                            <?php if (!empty($appointment['email'])): ?>
                                <p><strong>Email:</strong> <?= htmlspecialchars($appointment['email']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Appointment Information</h6>
                            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($appointment['appointment_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($appointment['appointment_time'])) ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?= $appointment['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $appointment['status'])) ?>
                                </span>
                            </p>
                            <?php if (!empty($appointment['purpose'])): ?>
                                <p><strong>Purpose:</strong> <?= htmlspecialchars($appointment['purpose']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($appointment['notes']) || !empty($appointment['additional_info'])): ?>
                        <hr>
                        <h6 class="text-primary">Additional Information</h6>
                        <?php if (!empty($appointment['notes'])): ?>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($appointment['notes']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($appointment['additional_info'])): ?>
                            <p><strong>Additional Info:</strong> <?= htmlspecialchars($appointment['additional_info']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($has_user_id && !empty($appointment['staff_name'])): ?>
                        <hr>
                        <h6 class="text-primary">Staff Information</h6>
                        <p><strong>Assigned Staff:</strong> <?= htmlspecialchars($appointment['staff_name']) ?></p>
                        <?php if (!empty($appointment['staff_email'])): ?>
                            <p><strong>Staff Email:</strong> <?= htmlspecialchars($appointment['staff_email']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Status Modal -->
    <div class="modal fade" id="editModal<?= $appointment['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Update Appointment Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                        <div class="mb-3">
                            <label for="status<?= $appointment['id'] ?>" class="form-label">Status</label>
                            <select name="status" class="form-select" id="status<?= $appointment['id'] ?>">
                                <option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="completed" <?= $appointment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="no_show" <?= $appointment['status'] === 'no_show' ? 'selected' : '' ?>>No Show</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Updating the status will notify the patient if notifications are enabled.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
