<?php
require_once 'database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Start session
session_start();

// Function to format appointment time display
function formatAppointmentTime($timeValue) {
    return 'Whole Day (8:00 AM – 5:00 PM)';
}

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../CHO/admin_dashboard.php');
}

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $action = sanitize($_POST['action']);
    
    $conn = getDBConnection();
    
    switch ($action) {
        case 'confirm':
            $stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $message = "Appointment confirmed successfully!";
            break;
            
        case 'cancel':
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $message = "Appointment cancelled successfully!";
            break;
            
        case 'complete':
            $stmt = $conn->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $message = "Appointment marked as completed!";
            break;
            
        case 'no_show':
            $stmt = $conn->prepare("UPDATE appointments SET status = 'no_show', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $message = "Appointment marked as no show!";
            break;
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect to prevent form resubmission
    header("Location: manage_appointments.php?message=" . urlencode($message));
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$time_filter = isset($_GET['time']) ? sanitize($_GET['time']) : 'all';
$view_type = isset($_GET['view']) ? sanitize($_GET['view']) : 'today'; // today or history

// Set default date filter for today's view
if ($view_type === 'today' && empty($date_filter)) {
    $date_filter = date('Y-m-d');
}

// Build query
$conn = getDBConnection();
$where_conditions = ["1=1"];
$params = [];
$types = "";

// Add date condition based on view type
if ($view_type === 'today') {
    // Show only the selected date (or today if no date chosen)
    $browse_date = !empty($date_filter) ? $date_filter : date('Y-m-d');
    $where_conditions[] = "a.appointment_date = ?";
    $params[] = $browse_date;
    $types .= "s";
} elseif ($view_type === 'history') {
    // History: past appointments only
    if (!empty($date_filter)) {
        $where_conditions[] = "a.appointment_date = ?";
        $params[] = $date_filter;
        $types .= "s";
    } else {
        $where_conditions[] = "a.appointment_date < ?";
        $params[] = date('Y-m-d');
        $types .= "s";
    }
}

if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.contact_number LIKE ? OR a.purpose LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if ($time_filter !== 'all') {
    $where_conditions[] = "a.time_period = ?";
    $params[] = $time_filter;
    $types .= "s";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get appointments - use a more robust query that handles different table structures
$query = "SELECT a.*, 
          CASE 
              WHEN a.first_name IS NOT NULL AND a.last_name IS NOT NULL THEN 
                  CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.middle_name, ''), ' ', COALESCE(a.last_name, ''), ' ', COALESCE(a.suffix, ''))
              WHEN u.full_name IS NOT NULL THEN u.full_name
              ELSE 'Unknown Patient'
          END as patient_display_name,
          u.email as patient_email,
          CASE 
              WHEN u.id IS NOT NULL THEN u.full_name
              ELSE 'Direct Booking'
          END as created_by
          FROM appointments a
          LEFT JOIN users u ON a.user_id = u.id
          $where_clause
          ORDER BY a.appointment_date ASC, a.time_period ASC, a.id ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

// Separate AM and PM appointments
$all_appointments = [];

while ($appointment = $appointments->fetch_assoc()) {
    $all_appointments[] = $appointment;
}

$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show
    FROM appointments";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$stats_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - CHO Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 30px;
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }

        .sidebar-nav a.active {
            background: rgba(255,255,255,0.3);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .content-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .content-header h1 {
            color: #FF6B35;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .content-header p {
            color: #666;
            font-size: 16px;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
        }

        .stat-card.total .icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stat-card.pending .icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .stat-card.confirmed .icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .stat-card.completed .icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .stat-card.cancelled .icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
        .stat-card.no-show .icon { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card .label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        /* Filters */
        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        /* Time Period Badges */
        .time-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 2px 0;
        }

        .time-badge.time-am {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .time-badge.time-pm {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            color: white;
        }

        /* Filter Tags */
        .filter-tag {
            display: inline-block;
            background: #2196f3;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin: 2px 5px 2px 0;
            font-weight: 500;
        }

        /* Two Table Layout */
        .tables-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .time-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .time-section.am-section {
            border-top: 4px solid #28a745;
        }

        .time-section.pm-section {
            border-top: 4px solid #007bff;
        }

        .time-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .time-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
        }

        .time-badge-large {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .time-badge-large.am {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .time-badge-large.pm {
            background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
            color: white;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        @media (max-width: 1200px) {
            .tables-container {
                grid-template-columns: 1fr;
            }
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 20px;
            width: fit-content;
        }

        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
        }

        .view-btn.active {
            background: white;
            color: #FF6B35;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .view-btn:hover:not(.active) {
            background: rgba(255,255,255,0.5);
        }

        .view-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .view-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .view-subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Appointments Table */
        .appointments-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }

        .appointments-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }

        .appointments-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .appointments-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-no-show { background: #e5e7eb; color: #374151; }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-confirm { background: #10b981; color: white; }
        .btn-complete { background: #3b82f6; color: white; }
        .btn-cancel { background: #ef4444; color: white; }
        .btn-no-show { background: #6b7280; color: white; }

        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: scroll;
            }
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
                <li><a href="../CHO/admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="slot_management.php"><i class="fas fa-cogs"></i> Manage Slot Capacity</a></li>
                <li><a href="appointment_calendar.php"><i class="fas fa-calendar"></i> Calendar</a></li>
                <li><a href="../CHO/all_forms.php"><i class="fas fa-file-medical"></i> Consent Forms</a></li>
                <li><a href="../CHO/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
