<?php
require_once '../CHO/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?role=user');
    exit();
}

$conn = getDBConnection();

// Get all appointments with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build WHERE conditions
$where_conditions = [];
$params = [];
$types = "";

// Filter by status
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = "status = ?";
    $params[] = sanitize($_GET['status']);
    $types .= "s";
}

// Filter by date
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $where_conditions[] = "appointment_date = ?";
    $params[] = sanitize($_GET['date']);
    $types .= "s";
}

// Search functionality (compatible with both old and new structures)
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(patient_name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR purpose LIKE ?)";
    $search_param = "%" . sanitize($_GET['search']) . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

// Build WHERE clause
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM appointments $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Get appointments with patient name handling (compatible with both old and new structures)
$query = "SELECT a.*, 
              CASE 
                  WHEN a.first_name IS NOT NULL AND a.last_name IS NOT NULL THEN 
                      CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.middle_name, ''), ' ', COALESCE(a.last_name, ''), ' ', COALESCE(a.suffix, ''))
                  WHEN a.patient_name IS NOT NULL AND a.patient_name != '' THEN a.patient_name
                  ELSE 'Unknown Client'
              END as patient_display_name,
              'Whole Day (8AM-5PM)' as display_time
          FROM appointments a 
          $where_clause 
          ORDER BY a.appointment_date ASC, a.appointment_time ASC 
          LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();

// Calculate pagination
$total_pages = ceil($total_records / $limit);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment List - CHO System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .back-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-group input {
            width: 200px;
        }

        .filter-group select {
            width: 150px;
        }

        .search-input {
            width: 300px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #FF6B35;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .table-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .results-count {
            color: #6c757d;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-no-show { background: #e5e7eb; color: #374151; }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #e9ecef;
            color: #FF6B35;
        }

        .pagination .current {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-arrive {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-no-show {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group input,
            .filter-group select {
                width: 100%;
            }
            
            .search-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="nav-brand">
                    <i class="fas fa-heartbeat"></i>
                    <span>CHO SYSTEM</span>
                </div>
                <a href="user_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
            <div class="header-right">
                <p>Welcome,</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" name="search" class="search-input" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           placeholder="Search by name, purpose...">
                </div>
                
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo (isset($_GET['status']) && $_GET['status'] === 'no_show') ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" 
                           value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="appointment_list.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Appointment List</h2>
                <span class="results-count"><?php echo $total_records; ?> appointments found</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Date & Time</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Arrived</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($appointment['patient_display_name']);
                                    ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    <br><strong><?php echo $appointment['display_time']; ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $arrived_status = $appointment['arrived_status'] ?? 'not_arrived';
                                    if ($arrived_status === 'arrived') {
                                        echo '<span class="status-badge status-confirmed"><i class="fas fa-check"></i> Arrived</span>';
                                    } elseif ($arrived_status === 'cancelled_by_client') {
                                        echo '<span class="status-badge status-cancelled"><i class="fas fa-times"></i> Cancelled</span>';
                                    } else {
                                        echo '<span class="status-badge status-pending">Not Arrived</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($appointment['status'] === 'confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="mark_arrived">
                                                <button type="submit" class="btn-arrive" onclick="return confirm('Mark this client as arrived?')">✓</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="mark_no_show">
                                                <button type="submit" class="btn-no-show" onclick="return confirm('Mark this client as no show?')">✗</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-calendar-times" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                                No appointments found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['date']) ? '&date=' . urlencode($_GET['date']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
