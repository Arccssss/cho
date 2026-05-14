<?php
require_once 'config/helpers.php';
require_once 'config/database.php';
require_once 'models/DashboardModel.php';
require_once 'models/FormModel.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();

// 2. Initialize the model
$dashboardModel = new DashboardModel($conn);
$formModel = new FormModel($conn);

// 3. FETCH THE DATA (This is what is missing!)
$stats = $dashboardModel->getOverviewStats();
$recent_forms = $dashboardModel->getRecentConsentForms();
$recent_itr = $dashboardModel->getRecentITRForms();

$total_forms = 0;
$service_counts = $dashboardModel->getTodayServiceAnalytics($total_forms);


// Handle form editing
$editing_form = null;
$edit_error = '';

if (isset($_GET['edit_form']) && is_numeric($_GET['edit_form'])) {
    $form_id = intval($_GET['edit_form']);
    $editing_form = $formModel->getConsentFormById($form_id);
    
    if (!$editing_form) {
        setFlashMessage('error', 'Form not found.');
        redirect('admin_dashboard.php');
    }
}

// Handle form update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_form') {
    $form_id = intval($_POST['form_id']);
    $patient_name = sanitize($_POST['patient_name']);
    $service_type = sanitize($_POST['service_type']);
    $form_date = sanitize($_POST['form_date']);
    
    if (empty($patient_name) || empty($service_type) || empty($form_date)) {
        $edit_error = 'Please fill in all required fields';
    } else {
        if ($formModel->updateConsentForm($form_id, $patient_name, $service_type, $form_date)) {
            setFlashMessage('success', 'Consent form updated successfully.');
            redirect('admin_dashboard.php');
        } else {
            $edit_error = 'Failed to update the form. Please try again.';
        }
    }
}

// Handle form deletion
if (isset($_GET['delete_form']) && is_numeric($_GET['delete_form'])) {
    $form_id = intval($_GET['delete_form']);
    
    if ($formModel->deleteConsentForm($form_id)) {
        setFlashMessage('success', 'Consent form deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete the form or form not found.');
    }
    redirect('admin_dashboard.php');
}

$conn->close();

// Function to get service icon
function getServiceIcon($serviceType) {
    $icons = [
        'ABTC' => '<i class="fas fa-syringe"></i>',
        'PEDIA CONSULTATION' => '<i class="fas fa-baby"></i>',
        'ADULT CONSULTATION' => '<i class="fas fa-user-md"></i>',
        'DENTAL' => '<i class="fas fa-tooth"></i>',
        'SOCIAL HYGIENE' => '<i class="fas fa-shield-alt"></i>',
        'LABORATORY' => '<i class="fas fa-flask"></i>',
        'X-RAY/ULTRASOUND' => '<i class="fas fa-x-ray"></i>',
        'X-RAY' => '<i class="fas fa-x-ray"></i>',
        'ULTRASOUND' => '<i class="fas fa-x-ray"></i>',
        'TB SECTION' => '<i class="fas fa-lungs"></i>',
        'PRENATAL' => '<i class="fas fa-baby-carriage"></i>',
        'CONSULTATION' => '<i class="fas fa-stethoscope"></i>',
        'MEDICAL' => '<i class="fas fa-heartbeat"></i>',
        'HEALTH' => '<i class="fas fa-heart"></i>',
        'CHECKUP' => '<i class="fas fa-user-check"></i>',
        'VACCINATION' => '<i class="fas fa-syringe"></i>',
        'IMMUNIZATION' => '<i class="fas fa-syringe"></i>'
    ];
    
    return $icons[strtoupper(trim($serviceType))] ?? '<i class="fas fa-medical"></i>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/modal.css">
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>Bacolod City Health Office - Admin Portal</p>
                </div>
            </div>
            <div class="header-right">
                <p>Logged in as</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
                <div class="live-datetime" id="liveDateTime">
                    <i class="fas fa-clock"></i>
                    <span id="dateTimeDisplay">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Modern Navigation Bar -->
        <nav class="modern-navbar">
            <div class="navbar-left">
                <div class="nav-brand">
                    <i class="fas fa-heartbeat"></i>
                    <span>Admin Portal</span>
                </div>
            </div>
            <div class="navbar-center">
                <div class="nav-links">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="all_forms.php" class="nav-link">
                        <i class="fas fa-file-medical"></i>
                        <span>Forms</span>
                    </a>
                    <a href="manage_appointments_admin.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Appointments</span>
                    </a>
                                    </div>
            </div>
            <div class="navbar-right">
                <div class="nav-actions">
                    <div class="notification-dropdown">
                        <button class="notification-toggle" id="notificationDropdown">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationBadge">0</span>
                        </button>
                        <div class="notification-dropdown-menu" id="notificationMenu">
                            <div class="notification-header">
                                <h4>System Notifications</h4>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be populated here -->
                            </div>
                        </div>
                    </div>
                    <div class="action-dropdown">
                        <button class="dropdown-toggle" id="quickActionsDropdown">
                            <i class="fas fa-th-large"></i>
                            <span>Quick Actions</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="quickActionsMenu">
                            <a href="all_forms.php" class="dropdown-item">
                                <i class="fas fa-list"></i>
                                <span>View All Forms</span>
                            </a>
                            <a href="../cho-apt-public/slot_management.php" class="dropdown-item">
                                <i class="fas fa-cogs"></i>
                                <span>Manage Slot Capacity</span>
                            </a>
                        </div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Alert Messages -->
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="alert">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="analytics-card">
                <div class="analytics-header">
                    <h3><i class="fas fa-chart-line"></i> Forms Analytics</h3>
                    <p>Time-based consent form metrics</p>
                </div>
                <div class="analytics-metrics">
                    <div class="metric-item">
                        <span class="metric-value"><?php echo $stats['forms_today']; ?></span>
                        <p class="metric-label">Today</p>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value"><?php echo $stats['forms_week']; ?></span>
                        <p class="metric-label">This Week</p>
                    </div>
                    <div class="metric-item">
                        <span class="metric-value"><?php echo $stats['forms_month']; ?></span>
                        <p class="metric-label">This Month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Analytics Section -->
        <div class="service-analytics">
            <h3 class="section-title"><i class="fas fa-chart-bar"></i> Today's Service Type Analytics</h3>
            <div class="service-cards-container">
                <!-- All Services Card -->
                <div class="service-card all-services active" data-service="all">
                    <div class="service-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="service-name">All Services</div>
                    <div class="service-count"><?php echo $total_forms; ?></div>
                    <div class="service-label">Today's Forms</div>
                </div>
                
                <?php if (!empty($service_counts)): ?>
                    <?php foreach ($service_counts as $service_name => $count): ?>
                        <div class="service-card" data-service="<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $service_name))); ?>">
                            <div class="service-icon">
                                <?php echo getServiceIcon($service_name); ?>
                            </div>
                            <div class="service-name"><?php echo htmlspecialchars($service_name); ?></div>
                            <div class="service-count"><?php echo $count; ?></div>
                            <div class="service-label">Today</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- CTA Buttons -->
        <div class="cta-section">
            <a href="create_consent_form.php" class="btn-create">
                <i class="fas fa-plus-circle"></i> Create New Consent Form
            </a>
            
            <a href="create_patient_enrollment.php" class="btn-create patient-enrollment-btn">
                <img src="images/doh.png" alt="DOH Logo" class="btn-logo"> CREATE PATIENT ENROLLMENT FORM
            </a>
        </div>

        <!-- Recent Forms Section -->
        <div class="forms-header">
            <div class="forms-tabs">
                <button class="forms-tab active" id="tab-consent" onclick="switchTab('consent')">
                    <i class="fas fa-file-signature"></i> Recent Consent Forms
                </button>
                <button class="forms-tab" id="tab-itr" onclick="switchTab('itr')">
                    <i class="fas fa-file-medical"></i> Recent ITR Forms
                </button>
            </div>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search by patient name, service type, creator, or date...">
                    <button type="button" id="clearSearch" class="btn-clear" title="Clear search">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Consent Forms Table -->
        <div class="table-card" id="table-consent">
            <div class="pagination-info" id="paginationInfo">
                <i class="fas fa-info-circle"></i>
                <span id="paginationText">Showing 50 most recent consent forms</span>
            </div>
            <div class="table-container">
                <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-id-card"></i> Patient Name</th>
                        <th><i class="fas fa-stethoscope"></i> Service Type</th>
                        <th><i class="fas fa-user"></i> Created By</th>
                        <th><i class="fas fa-calendar"></i> Form Date</th>
                        <th><i class="fas fa-clock"></i> Created</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody id="formsTableBody">
                    <?php if ($recent_forms->num_rows > 0): ?>
                        <?php $form_index = 0; ?>
                        <?php while ($form = $recent_forms->fetch_assoc()): $form_index++; ?>
                            <tr data-searchable="true" data-index="<?php echo $form_index; ?>">
                                <td><strong>#<?php echo $form['id']; ?></strong></td>
                                <td class="patient-name"><?php echo htmlspecialchars($form['patient_name']); ?></td>
                                <td class="service-type"><strong><?php echo htmlspecialchars($form['service_type']); ?></strong></td>
                                <td class="creator-info">
                                    <span class="creator-name"><?php echo htmlspecialchars($form['creator_name']); ?></span><br>
                                    <small class="creator-email" style="color: #888;"><?php echo htmlspecialchars($form['creator_email']); ?></small>
                                </td>
                                <td class="form-date"><?php echo date('M d, Y', strtotime($form['form_date'])); ?></td>
                                <td class="created-date"><?php echo date('M d, Y h:i A', strtotime($form['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_form.php?id=<?php echo $form['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="#" class="btn btn-warning" onclick="editForm(<?php echo $form['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" class="btn btn-danger" onclick="deleteForm(<?php echo $form['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr id="noDataRow" class="no-data">
                            <td colspan="7">
                                <i class="fas fa-inbox"></i>
                                <p>No forms created yet.</p>
                            </td>
                        </tr>
                        <tr id="noSearchResults" class="no-search-results" style="display: none;">
                            <td colspan="7">
                                <i class="fas fa-search"></i>
                                <p>No forms found matching your search.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>

        <!-- ITR Forms Table -->
        <div class="table-card" id="table-itr" style="display:none;">
            <div class="pagination-info">
                <i class="fas fa-info-circle"></i>
                <span>Showing 50 most recent ITR forms</span>
            </div>
            <div class="table-container">
                <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-id-card"></i> Patient Name</th>
                        <th><i class="fas fa-venus-mars"></i> Sex</th>
                        <th><i class="fas fa-birthday-cake"></i> Age</th>
                        <th><i class="fas fa-user"></i> Created By</th>
                        <th><i class="fas fa-calendar"></i> Date of Consultation</th>
                        <th><i class="fas fa-clock"></i> Created</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody id="itrTableBody">
                    <?php if ($recent_itr->num_rows > 0): ?>
                        <?php $itr_index = 0; ?>
                        <?php while ($itr = $recent_itr->fetch_assoc()): $itr_index++; ?>
                            <tr data-itr-searchable="true" data-index="<?php echo $itr_index; ?>">
                                <td><strong>#<?php echo $itr['id']; ?></strong></td>
                                <td class="patient-name">
                                    <?php echo htmlspecialchars(trim($itr['last_name'] . ', ' . $itr['first_name'] . ' ' . $itr['middle_name'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($itr['sex']); ?></td>
                                <td><?php echo htmlspecialchars($itr['age']); ?></td>
                                <td class="creator-info">
                                    <span class="creator-name"><?php echo htmlspecialchars($itr['creator_name']); ?></span><br>
                                    <small class="creator-email" style="color: #888;"><?php echo htmlspecialchars($itr['creator_email']); ?></small>
                                </td>
                                <td class="form-date">
                                    <?php echo $itr['date_of_consultation'] ? date('M d, Y', strtotime($itr['date_of_consultation'])) : '—'; ?>
                                </td>
                                <td class="created-date"><?php echo date('M d, Y h:i A', strtotime($itr['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_enrollment.php?id=<?php echo $itr['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="create_patient_enrollment.php?edit=<?php echo $itr['id']; ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" class="btn btn-danger" onclick="deleteITR(<?php echo $itr['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-data">
                            <td colspan="8">
                                <i class="fas fa-inbox"></i>
                                <p>No ITR forms created yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>

        
    </div>

    
        <?php include 'includes/edit_form_modal.php'; ?>
        
    </div> 

    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>
