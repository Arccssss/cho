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

    <script>
        // Tab switching
        function switchTab(tab) {
            document.getElementById('table-consent').style.display = tab === 'consent' ? 'block' : 'none';
            document.getElementById('table-itr').style.display    = tab === 'itr'     ? 'block' : 'none';
            document.getElementById('tab-consent').classList.toggle('active', tab === 'consent');
            document.getElementById('tab-itr').classList.toggle('active', tab === 'itr');
            // Update search placeholder
            const search = document.getElementById('globalSearch');
            search.value = '';
            search.placeholder = tab === 'consent'
                ? 'Search by patient name, service type, creator, or date...'
                : 'Search by patient name, sex, age, creator, or date...';
            // Re-run search logic reset
            document.querySelectorAll('#formsTableBody tr[data-searchable]').forEach(r => r.style.display = '');
            document.querySelectorAll('#itrTableBody tr[data-itr-searchable]').forEach(r => r.style.display = '');
        }

        // Delete ITR record
        function deleteITR(id) {
            if (confirm('Are you sure you want to delete this ITR record? This cannot be undone.')) {
                window.location.href = 'delete_enrollment.php?id=' + id;
            }
        }

        // Global Search Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('globalSearch');
            const clearButton = document.getElementById('clearSearch');
            const tableBody = document.getElementById('formsTableBody');
            const noDataRow = document.getElementById('noDataRow');
            const noSearchResultsRow = document.getElementById('noSearchResults');
            const allRows = Array.from(tableBody.querySelectorAll('tr[data-searchable="true"]'));

            // Enhanced search functionality will be added below

            // Get all searchable text from a row
            function getSearchableText(row) {
                const cells = row.querySelectorAll('td');
                let text = '';
                
                cells.forEach((cell, index) => {
                    // Skip the Actions column (index 6)
                    if (index === 6) return;
                    
                    // Get text from specific cells
                    if (cell.classList.contains('patient-name')) {
                        text += cell.textContent.toLowerCase() + ' ';
                    } else if (cell.classList.contains('service-type')) {
                        text += cell.textContent.toLowerCase() + ' ';
                    } else if (cell.classList.contains('creator-info')) {
                        const creatorName = cell.querySelector('.creator-name');
                        const creatorEmail = cell.querySelector('.creator-email');
                        if (creatorName) text += creatorName.textContent.toLowerCase() + ' ';
                        if (creatorEmail) text += creatorEmail.textContent.toLowerCase() + ' ';
                    } else if (cell.classList.contains('form-date')) {
                        text += cell.textContent.toLowerCase() + ' ';
                    } else if (cell.classList.contains('created-date')) {
                        text += cell.textContent.toLowerCase() + ' ';
                    } else {
                        // For ID column
                        text += cell.textContent.toLowerCase() + ' ';
                    }
                });
                
                return text;
            }

            // Highlight search term in text
            function highlightSearchTerm(row, term) {
                removeHighlights(row);
                
                if (!term) return;
                
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    // Skip the Actions column
                    if (index === 6) return;
                    
                    const textNodes = getTextNodes(cell);
                    textNodes.forEach(node => {
                        const text = node.textContent;
                        const regex = new RegExp(`(${escapeRegExp(term)})`, 'gi');
                        const matches = text.match(regex);
                        
                        if (matches) {
                            const span = document.createElement('span');
                            span.innerHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
                            node.parentNode.replaceChild(span, node);
                        }
                    });
                });
            }

            // Remove all highlights
            function removeHighlights(row) {
                const highlights = row.querySelectorAll('.search-highlight');
                highlights.forEach(highlight => {
                    const parent = highlight.parentNode;
                    parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                    parent.normalize();
                });
            }

            // Get text nodes from an element
            function getTextNodes(element) {
                const textNodes = [];
                const walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );
                
                let node;
                while (node = walker.nextNode()) {
                    if (node.textContent.trim()) {
                        textNodes.push(node);
                    }
                }
                
                return textNodes;
            }

            // Escape special characters for regex
            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            // Keyboard shortcuts
            searchInput.addEventListener('keydown', function(e) {
                // Escape key clears search
                if (e.key === 'Escape') {
                    clearButton.click();
                }
                // Ctrl/Cmd + K focuses search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.focus();
                    this.select();
                }
            });

            // Global keyboard shortcut
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k' && !e.target.matches('input, textarea')) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });

            // Service Card Quick Filter Functionality
            const serviceCards = document.querySelectorAll('.service-card');
            
            serviceCards.forEach(card => {
                card.addEventListener('click', function() {
                    const serviceType = this.dataset.service;
                    
                    // Update active state
                    serviceCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter the table
                    filterTableByService(serviceType);
                    
                    // Update search input to reflect filter
                    if (serviceType === 'all') {
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                    } else {
                        // Convert service type back to readable format
                        const serviceName = this.querySelector('.service-name').textContent;
                        searchInput.value = serviceName;
                        searchInput.dispatchEvent(new Event('input'));
                    }
                });
            });

            function filterTableByService(serviceType) {
                const allRows = Array.from(tableBody.querySelectorAll('tr[data-searchable="true"]'));
                let visibleCount = 0;
                
                allRows.forEach(row => {
                    if (serviceType === 'all') {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        const serviceCell = row.querySelector('.service-type');
                        const serviceText = serviceCell ? serviceCell.textContent : '';
                        
                        // Split comma-separated services and check if any match the filter
                        const individualServices = serviceText.split(',').map(s => s.trim().toLowerCase());
                        const filterService = serviceType.replace(/-/g, ' ').toLowerCase();
                        
                        if (individualServices.includes(filterService)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
                
                // Show appropriate message
                if (serviceType !== 'all' && visibleCount === 0) {
                    noDataRow.style.display = 'none';
                    noSearchResultsRow.style.display = '';
                } else {
                    noDataRow.style.display = '';
                    noSearchResultsRow.style.display = 'none';
                }
            }
        });

        // Live Date and Time Functionality
        function updateDateTime() {
            const now = new Date();
            
            // Format options
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };
            
            // Format the date and time
            const formattedDateTime = now.toLocaleDateString('en-US', options);
            
            // Update the display
            const dateTimeDisplay = document.getElementById('dateTimeDisplay');
            if (dateTimeDisplay) {
                dateTimeDisplay.textContent = formattedDateTime;
            }
        }

        // Initial update
        updateDateTime();
        
        // Update every second
        setInterval(updateDateTime, 1000);

        // Pagination and Table Management
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationText = document.getElementById('paginationText');
        const tableContainer = document.querySelector('.table-container');
        
        // Update pagination info based on visible rows
        function updatePaginationInfo() {
            const visibleRows = tableBody.querySelectorAll('tr[data-searchable="true"]:not([style*="display: none"])');
            const totalRows = tableBody.querySelectorAll('tr[data-searchable="true"]');
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm === '') {
                paginationText.textContent = `Showing ${visibleRows.length} most recent consent forms`;
            } else {
                paginationText.textContent = `Found ${visibleRows.length} of ${totalRows.length} forms matching "${searchTerm}"`;
            }
        }

        // Enhanced search functionality with pagination info update
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            // Show/hide clear button
            if (searchTerm) {
                clearButton.classList.add('show');
            } else {
                clearButton.classList.remove('show');
            }
            
            allRows.forEach(row => {
                const searchableText = row.textContent.toLowerCase();
                if (searchableText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                    
                    // Remove existing highlights
                    row.querySelectorAll('.search-highlight').forEach(span => {
                        const parent = span.parentNode;
                        parent.replaceChild(document.createTextNode(span.textContent), span);
                        parent.normalize();
                    });
                    
                    // Add highlights if search term is not empty
                    if (searchTerm !== '') {
                        const cells = row.querySelectorAll('td');
                        cells.forEach(cell => {
                            const text = cell.textContent;
                            if (text.toLowerCase().includes(searchTerm)) {
                                const regex = new RegExp(`(${searchTerm})`, 'gi');
                                const highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
                                cell.innerHTML = highlightedText;
                            }
                        });
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            // Also filter ITR table
            const itrRows = Array.from(document.querySelectorAll('#itrTableBody tr[data-itr-searchable]'));
            itrRows.forEach(row => {
                const searchableText = row.textContent.toLowerCase();
                row.style.display = searchableText.includes(searchTerm) ? '' : 'none';
            });
            
            // Show/hide no results message
            if (visibleCount === 0 && searchTerm !== '') {
                noDataRow.style.display = 'none';
                noSearchResultsRow.style.display = '';
            } else {
                noDataRow.style.display = '';
                noSearchResultsRow.style.display = 'none';
            }
            
            // Update pagination info
            updatePaginationInfo();
        });

        // Clear search functionality
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });

        // Update pagination info when service cards are clicked
        serviceCards.forEach(card => {
            card.addEventListener('click', function() {
                setTimeout(updatePaginationInfo, 100); // Small delay to ensure filtering is complete
            });
        });

        // Initial pagination info update
        updatePaginationInfo();

        // Dropdown Menu Functionality
        const quickActionsDropdown = document.getElementById('quickActionsDropdown');
        const quickActionsMenu = document.getElementById('quickActionsMenu');

        quickActionsDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            quickActionsMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            quickActionsDropdown.classList.remove('active');
            quickActionsMenu.classList.remove('show');
        });

        // Prevent dropdown from closing when clicking inside
        quickActionsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Notification System
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationMenu = document.getElementById('notificationMenu');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationList = document.getElementById('notificationList');

        // Toggle notification dropdown
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            notificationMenu.classList.toggle('show');
            
            // Close quick actions dropdown if open
            quickActionsDropdown.classList.remove('active');
            quickActionsMenu.classList.remove('show');
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function() {
            notificationDropdown.classList.remove('active');
            notificationMenu.classList.remove('show');
        });

        // Prevent dropdown from closing when clicking inside
        notificationMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Load notifications
        function loadNotifications() {
            // No user notifications in admin-only system
            const notifications = [];

            updateNotificationList(notifications);
            updateNotificationBadge(notifications.length);
        }

        // Update notification list
        function updateNotificationList(notifications) {
            if (notifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-user-plus"></i>
                        <p>No new patient registrations</p>
                    </div>
                `;
                return;
            }

            notificationList.innerHTML = notifications.map(notif => {
                const iconClass = getNotificationIconClass(notif.type);
                const timeAgo = getTimeAgo(notif.created_at);
                const linkUrl = getNotificationLink(notif.type, notif.id);
                
                return `
                    <a href="${linkUrl}" class="notification-item unread" onclick="markAsRead(this)">
                        <div class="notification-icon ${iconClass}">
                            <i class="fas ${getNotificationIcon(notif.type)}"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">New Patient Registration</div>
                            <div class="notification-message">${notif.full_name} • ${notif.email}</div>
                            <div class="notification-time">${timeAgo}</div>
                        </div>
                    </a>
                `;
            }).join('');
        }

        // Update notification badge
        function updateNotificationBadge(count) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = count > 0 ? 'block' : 'none';
        }

        // Get notification icon class
        function getNotificationIconClass(type) {
            switch(type) {
                case 'new_patient': return 'new-user';
                default: return 'system';
            }
        }

        // Get notification icon
        function getNotificationIcon(type) {
            return 'fa-info-circle';
        }

        // Get notification link
        function getNotificationLink(type, userId, formId) {
            return '#';
        }

        // Get time ago string
        function getTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
            return date.toLocaleDateString();
        }

        // Mark notification as read
        function markAsRead(element) {
            element.classList.remove('unread');
            const currentCount = parseInt(notificationBadge.textContent);
            if (currentCount > 0) {
                updateNotificationBadge(currentCount - 1);
            }
        }

        // Load notifications on page load
        loadNotifications();
    </script>

    <!-- Edit Form Modal -->
    <div id="editFormModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Consent Form</h3>
                <button class="close-btn" onclick="closeEditForm()">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($edit_error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $edit_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit_form">
                    <input type="hidden" name="form_id" id="editFormId">
                    
                    <div class="form-group">
                        <label for="editPatientName">Patient Name</label>
                        <input type="text" id="editPatientName" name="patient_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editServiceType">Service Type</label>
                        <input type="text" id="editServiceType" name="service_type" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editFormDate">Form Date</label>
                        <input type="date" id="editFormDate" name="form_date" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Form
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditForm()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>

    <script>
        function editForm(formId) {
            // Redirect to edit form
            window.location.href = 'admin_dashboard.php?edit_form=' + formId;
        }

        function deleteForm(formId) {
            if (confirm('Are you sure you want to delete this consent form? This action cannot be undone.')) {
                window.location.href = 'admin_dashboard.php?delete_form=' + formId;
            }
        }

        function closeEditForm() {
            document.getElementById('editFormModal').style.display = 'none';
        }

        // Auto-populate edit form if editing
        <?php if ($editing_form): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('editFormModal').style.display = 'flex';
                document.getElementById('editFormId').value = '<?php echo $editing_form['id']; ?>';
                document.getElementById('editPatientName').value = '<?php echo htmlspecialchars($editing_form['patient_name']); ?>';
                document.getElementById('editServiceType').value = '<?php echo htmlspecialchars($editing_form['service_type']); ?>';
                document.getElementById('editFormDate').value = '<?php echo $editing_form['form_date']; ?>';
            });
        <?php endif; ?>
    </script>
</body>
</html>
