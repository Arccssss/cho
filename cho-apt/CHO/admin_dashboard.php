<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();

// Get statistics
$stats = [];


// Total forms
$result = $conn->query("SELECT COUNT(*) as total FROM consent_forms");
$stats['total_forms'] = $result->fetch_assoc()['total'];

// Forms today
$result = $conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE DATE(created_at) = CURDATE()");
$stats['forms_today'] = $result->fetch_assoc()['total'];

// Forms this week
$result = $conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
$stats['forms_week'] = $result->fetch_assoc()['total'];

// Forms this month
$result = $conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
$stats['forms_month'] = $result->fetch_assoc()['total'];

// Recent forms with user info
$stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                       FROM consent_forms cf 
                       JOIN users u ON cf.user_id = u.id 
                       ORDER BY cf.created_at DESC LIMIT 50");
$stmt->execute();
$recent_forms = $stmt->get_result();
$stmt->close();

// Recent ITR (patient enrollment) records
$stmt = $conn->prepare("SELECT pe.*, u.full_name as creator_name, u.email as creator_email 
                       FROM patient_enrollment pe 
                       JOIN users u ON pe.user_id = u.id 
                       ORDER BY pe.created_at DESC LIMIT 50");
$stmt->execute();
$recent_itr = $stmt->get_result();
$stmt->close();

// Get service type analytics with atomic counting - TODAY only
$stmt = $conn->prepare("SELECT service_type FROM consent_forms WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$all_service_types = $stmt->get_result();
$stmt->close();

// Atomic counting for comma-separated service types
$service_counts = array();
$total_forms = 0;

while ($row = $all_service_types->fetch_assoc()) {
    $total_forms++;
    $service_types = $row['service_type'];
    
    // Split comma-separated service types and trim whitespace
    $individual_services = array_map('trim', explode(',', $service_types));
    
    // Count each individual service
    foreach ($individual_services as $service) {
        if (!empty($service)) {
            $service_counts[$service] = isset($service_counts[$service]) ? $service_counts[$service] + 1 : 1;
        }
    }
}

// Also count today's ITR (patient enrollment) purpose_of_visit
$stmt = $conn->prepare("SELECT purpose_of_visit FROM patient_enrollment WHERE DATE(created_at) = CURDATE() AND purpose_of_visit IS NOT NULL AND purpose_of_visit != ''");
$stmt->execute();
$itr_visits = $stmt->get_result();
$stmt->close();

while ($row = $itr_visits->fetch_assoc()) {
    $total_forms++;
    // Strip any [Ref: ...] junk and split comma-separated values
    $cleaned = preg_replace('/\s*\[Ref:[^\]]*\]/', '', $row['purpose_of_visit']);
    $individual_services = array_map('trim', explode(',', $cleaned));
    foreach ($individual_services as $service) {
        if (!empty($service)) {
            $service_counts[$service] = isset($service_counts[$service]) ? $service_counts[$service] + 1 : 1;
        }
    }
}

// Sort services by count (descending)
arsort($service_counts);


// Handle form editing
$editing_form = null;
$edit_error = '';

if (isset($_GET['edit_form']) && is_numeric($_GET['edit_form'])) {
    $form_id = intval($_GET['edit_form']);
    
    // Get form details for editing
    $stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email FROM consent_forms cf JOIN users u ON cf.user_id = u.id WHERE cf.id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $editing_form = $result->fetch_assoc();
    } else {
        setFlashMessage('error', 'Form not found.');
        redirect('admin_dashboard.php');
    }
    $stmt->close();
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
        // Update the form
        $stmt = $conn->prepare("UPDATE consent_forms SET patient_name = ?, service_type = ?, form_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $patient_name, $service_type, $form_date, $form_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Consent form updated successfully.');
            redirect('admin_dashboard.php');
        } else {
            $edit_error = 'Failed to update the form. Please try again.';
        }
        $stmt->close();
    }
}

// Handle form deletion
if (isset($_GET['delete_form']) && is_numeric($_GET['delete_form'])) {
    $form_id = intval($_GET['delete_form']);
    
    // Get form details to delete associated files
    $stmt = $conn->prepare("SELECT patient_photo, signature_data FROM consent_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $form = $result->fetch_assoc();
        
        // Delete associated files if they exist
        if ($form['patient_photo'] && file_exists($form['patient_photo'])) {
            unlink($form['patient_photo']);
        }
        if ($form['signature_data'] && file_exists($form['signature_data']) && $form['signature_data'] !== 'Signed with ballpen') {
            unlink($form['signature_data']);
        }
        
        // Delete the form from database
        $stmt = $conn->prepare("DELETE FROM consent_forms WHERE id = ?");
        $stmt->bind_param("i", $form_id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Consent form deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete the form. Please try again.');
        }
        $stmt->close();
    } else {
        setFlashMessage('error', 'Form not found.');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('images/ngc.jpg') center center / cover no-repeat;
            filter: blur(6px) brightness(0.9);
            transform: scale(1.05);
            z-index: -2;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.35);
            z-index: -1;
            pointer-events: none;
        }



        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF9F5 100%);
            padding: 35px 40px;
            border-radius: 24px;
            margin-bottom: 45px;
            box-shadow: 0 15px 40px rgba(255, 107, 53, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            border: 2px solid rgba(255, 107, 53, 0.08);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 107, 53, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 25px;
            flex: 1;
        }

        .logo-box {
            width: 85px;
            height: 85px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            transition: all 0.3s ease;
            position: relative;
        }

        .logo-box:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }

        .header-title h2 {
            font-size: 32px;
            font-weight: 900;
            color: #FF6B35;
            margin: 0;
            letter-spacing: 0.8px;
            text-shadow: 0 2px 4px rgba(255, 107, 53, 0.1);
            position: relative;
        }

        .header-title p {
            font-size: 14px;
            color: #F7931E;
            margin: 12px 0 0 0;
            letter-spacing: 0.4px;
            font-weight: 500;
            opacity: 0.9;
        }

        .header-right {
            text-align: right;
            color: #333;
            background: linear-gradient(135deg, #FFF9F5 0%, #FFFFFF 100%);
            padding: 20px 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 107, 53, 0.1);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.08);
            transition: all 0.3s ease;
        }

        .header-right:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.12);
        }

        .header-right p {
            font-size: 12px;
            color: #888;
            margin: 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .header-right strong {
            font-size: 18px;
            color: #FF6B35;
            display: block;
            margin-top: 8px;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(255, 107, 53, 0.1);
        }

        .live-datetime {
            font-size: 13px;
            color: #666;
            margin-top: 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(255, 107, 53, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }

        .live-datetime i {
            color: #FF6B35;
            font-size: 12px;
        }

        /* Modern Navigation Bar */
        .modern-navbar {
            background: white;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            overflow: hidden;
            position: relative;
        }

        .navbar-left {
            flex: 0 0 auto;
            padding: 0 20px;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #FF6B35;
            padding: 15px 0;
        }

        .nav-brand i {
            font-size: 20px;
        }

        .navbar-center {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            gap: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 20px 25px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            position: relative;
            border-right: 1px solid #f0f0f0;
        }

        .nav-link:last-child {
            border-right: none;
        }

        .nav-link:hover {
            color: #FF6B35;
            background: rgba(255, 107, 53, 0.05);
        }

        .nav-link.active {
            color: #FF6B35;
            background: rgba(255, 107, 53, 0.1);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
        }

        .navbar-right {
            flex: 0 0 auto;
            padding: 0 20px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Notification System */
        .notification-dropdown {
            position: relative;
        }

        .notification-toggle {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 107, 53, 0.1);
            border: none;
            border-radius: 10px;
            color: #FF6B35;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .notification-toggle:hover {
            background: rgba(255, 107, 53, 0.2);
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
            border: 2px solid white;
        }

        .notification-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 320px;
            max-width: 400px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            margin-top: 8px;
            overflow: hidden;
        }

        .notification-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            background: rgba(255, 107, 53, 0.02);
        }

        .notification-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }

        .view-all {
            color: #FF6B35;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            color: #F7931E;
            text-decoration: underline;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: rgba(255, 107, 53, 0.05);
        }

        .notification-item.unread {
            background: rgba(255, 107, 53, 0.02);
            border-left: 3px solid #FF6B35;
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .notification-icon.new-user {
            background: rgba(34, 197, 94, 0.1);
            color: #22C55E;
        }

        .notification-icon.new-form {
            background: rgba(255, 107, 53, 0.1);
            color: #FF6B35;
        }

        .notification-icon.system {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: #333;
            line-height: 1.3;
        }

        .notification-message {
            font-size: 12px;
            color: #666;
            margin: 0 0 4px 0;
            line-height: 1.4;
        }

        .notification-time {
            font-size: 11px;
            color: #999;
            margin: 0;
        }

        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .notification-empty i {
            font-size: 32px;
            color: #ddd;
            margin-bottom: 10px;
            display: block;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #EF4444;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
        }

        .logout-btn:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }

        .action-dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.2);
        }

        .dropdown-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
        }

        .dropdown-toggle i:last-child {
            font-size: 12px;
            transition: transform 0.3s ease;
        }

        .dropdown-toggle.active i:last-child {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            margin-top: 8px;
            overflow: hidden;
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(255, 107, 53, 0.05);
            color: #FF6B35;
        }

        .dropdown-item.logout {
            color: #EF4444;
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.05);
            color: #DC2626;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            height: 1px;
            background: #f0f0f0;
            margin: 5px 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 2px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 107, 53, 0.2);
        }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 800;
            color: #FF6B35;
            margin: 0 0 10px 0;
        }

        .stat-card p {
            font-size: 14px;
            color: #666;
            margin: 0;
        }

        .stat-card i {
            color: #F7931E;
            margin-right: 8px;
        }

        /* Analytics Card Styles */
        .analytics-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: all 0.3s ease;
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 2px;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 107, 53, 0.2);
        }

        .analytics-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }

        .analytics-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 5px 0;
        }

        .analytics-header p {
            font-size: 12px;
            color: #999;
            margin: 0;
        }

        .analytics-header i {
            color: #F7931E;
            margin-right: 8px;
        }

        .analytics-metrics {
            display: flex;
            justify-content: space-around;
            align-items: center;
        }

        .metric-item {
            text-align: center;
            flex: 1;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 800;
            color: #FF6B35;
            margin: 0 0 5px 0;
            display: block;
        }

        .metric-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        /* Alert */
        .alert {
            background: white;
            border-left: 5px solid #FF6B35;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert i {
            color: #FF6B35;
            margin-right: 10px;
        }

        /* Section Title */
        .section-title {
            font-size: 24px;
            font-weight: 800;
            color: #FF6B35;
            margin-bottom: 20px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title i {
            margin-right: 10px;
            color: #F7931E;
        }

        /* Table */
        .table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }

        .table-container {
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }

        .table-container::-webkit-scrollbar {
            width: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #F7931E 0%, #FF6B35 100%);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border-bottom: 2px solid rgba(255, 107, 53, 0.3);
        }

        table thead th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        table thead th:last-child {
            border-right: none;
        }

        table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            min-width: 300px;
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
        }

        .btn {
            padding: 8px 15px;
            font-size: 11px;
            white-space: nowrap;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-warning {
            background: #FDB833;
            color: white;
        }

        .btn-warning:hover {
            background: #F7931E;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #EF4444;
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data i {
            font-size: 40px;
            color: #ddd;
            margin-bottom: 10px;
            display: block;
        }

        /* Search Styles */
        .forms-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
        }

        .forms-tabs {
            display: flex;
            gap: 8px;
        }

        .forms-tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .forms-tab:hover {
            border-color: #FF6B35;
            color: #FF6B35;
            background: rgba(255, 107, 53, 0.05);
        }

        .forms-tab.active {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            border-color: #FF6B35;
            color: white;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }

        .search-container {
            flex: 1;
            max-width: 400px;
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            color: #FF6B35;
            z-index: 1;
        }

        #globalSearch {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        #globalSearch:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        #globalSearch::placeholder {
            color: #999;
        }

        .btn-clear {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: none;
        }

        .btn-clear:hover {
            background: #f0f0f0;
            color: #666;
        }

        .btn-clear.show {
            display: block;
        }

        .search-highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .no-search-results {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }

        .pagination-info {
            background: rgba(255, 107, 53, 0.05);
            border: 1px solid rgba(255, 107, 53, 0.1);
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 12px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .pagination-info i {
            color: #FF6B35;
        }

        /* Service Analytics Cards */
        .service-analytics {
            margin-bottom: 30px;
        }

        /* CTA Section */
        .cta-section {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .btn-create {
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.2);
        }

        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.3);
            background: linear-gradient(135deg, #F7931E 0%, #FF6B35 100%);
        }

        .patient-enrollment-btn {
            background: linear-gradient(135deg, #1e4d8c 0%, #2d5f9e 100%);
            box-shadow: 0 5px 15px rgba(30, 77, 140, 0.2);
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .patient-enrollment-btn:hover {
            background: linear-gradient(135deg, #2d5f9e 0%, #1e4d8c 100%);
            box-shadow: 0 10px 25px rgba(30, 77, 140, 0.3);
        }

        .btn-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: white;
            padding: 2px;
        }

        .service-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .service-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            transition: height 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.2);
            border-color: #FF6B35;
        }

        .service-card:hover::before {
            height: 6px;
        }

        .service-card.active {
            border-color: #FF6B35;
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.05) 0%, rgba(247, 147, 30, 0.05) 100%);
        }

        .service-card.active::before {
            height: 6px;
        }

        .service-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .service-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .service-count {
            font-size: 24px;
            font-weight: 800;
            color: #FF6B35;
            margin-bottom: 5px;
        }

        .service-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .service-card.all-services {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        .service-card.all-services .service-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .service-card.all-services .service-name,
        .service-card.all-services .service-count,
        .service-card.all-services .service-label {
            color: white;
        }

        .service-card.all-services::before {
            background: rgba(255, 255, 255, 0.3);
        }

        @media (max-width: 768px) {
            .service-cards-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
            }

            .service-card {
                padding: 15px;
            }

            .service-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
                margin-bottom: 10px;
            }

            .service-name {
                font-size: 13px;
            }

            .service-count {
                font-size: 20px;
            }
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            margin-bottom: 40px;
        }

        .quick-actions a {
            padding: 15px 35px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: white;
            color: #FF6B35;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .quick-actions a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
        }

        /* Notifications */
        .notification-item {
            padding: 15px;
            border-left: 5px solid #F7931E;
            background: #fafafa;
            margin-bottom: 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .notification-item:hover {
            background: #f0f0f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-item.updated {
            border-left-color: #FDB833;
        }

        .notification-item.deleted {
            border-left-color: #EF4444;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .notification-user {
            font-weight: 700;
            color: #FF6B35;
        }

        .notification-type {
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .notification-type.updated {
            background: #FEF3C7;
            color: #92400E;
        }

        .notification-type.deleted {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .notification-time {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .analytics-metrics {
                flex-direction: column;
                gap: 20px;
            }

            .metric-item {
                padding: 15px;
                background: #f9f9f9;
                border-radius: 10px;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .header-left {
                flex-direction: column;
            }

            .live-datetime {
                font-size: 12px;
                padding: 6px 10px;
                margin-top: 8px;
            }

            .modern-navbar {
                flex-direction: column;
                gap: 0;
            }

            .navbar-left,
            .navbar-right {
                padding: 15px 20px;
            }

            .navbar-center {
                order: -1;
                width: 100%;
            }

            .nav-links {
                width: 100%;
                justify-content: space-around;
            }

            .nav-link {
                flex: 1;
                padding: 15px 10px;
                font-size: 13px;
                justify-content: center;
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
            }

            .nav-link:last-child {
                border-bottom: none;
            }

            .nav-link span {
                display: none;
            }

            .dropdown-toggle {
                padding: 10px 15px;
                font-size: 13px;
            }

            .dropdown-toggle span:not(:last-child) {
                display: none;
            }

            .logout-btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .logout-btn span {
                display: none;
            }

            .notification-toggle {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .notification-badge {
                top: -4px;
                right: -4px;
                font-size: 9px;
                min-width: 16px;
                padding: 1px 4px;
            }

            .notification-dropdown-menu {
                min-width: 280px;
                max-width: 320px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
            }

            .modern-navbar {
                margin-bottom: 20px;
            }

            .nav-brand {
                font-size: 16px;
            }

            .nav-link {
                padding: 12px 8px;
                font-size: 12px;
            }

            .dropdown-toggle {
                padding: 8px 12px;
                font-size: 12px;
            }

            .logout-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .notification-toggle {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }

            .notification-badge {
                top: -3px;
                right: -3px;
                font-size: 8px;
                min-width: 14px;
                padding: 1px 3px;
            }

            .notification-dropdown-menu {
                min-width: 260px;
                max-width: 300px;
                right: -10px;
            }

            table {
                font-size: 12px;
            }

            table thead th,
            table tbody td {
                padding: 10px;
            }

            /* Mobile search adjustments */
            .forms-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .search-container {
                max-width: 100%;
            }

            #globalSearch {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 15px 45px 15px 45px;
            }

            .quick-actions {
                flex-direction: column;
                gap: 10px;
            }

            /* Service analytics mobile adjustments */
            .service-cards-container {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 8px;
            }

            .service-card {
                padding: 12px;
            }

            .service-icon {
                width: 30px;
                height: 30px;
                font-size: 14px;
                margin-bottom: 8px;
            }

            .service-name {
                font-size: 12px;
                margin-bottom: 6px;
            }

            .service-count {
                font-size: 18px;
                margin-bottom: 4px;
            }

            .service-label {
                font-size: 10px;
            }
        }
    </style>
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
                    <a href="../../cho-apt-public/AppointmentCHO/manage_appointments.php" class="nav-link">
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
                            <a href="../../cho-apt-public/AppointmentCHO/slot_management.php" class="dropdown-item">
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
