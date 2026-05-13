<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cho_consent_system');

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Get database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for full Unicode support
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Format date for display
function formatDate($date) {
    return date('F d, Y', strtotime($date));
}

// Format time for display
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

// Check if date is valid (not in the past, not weekend)
function isValidAppointmentDate($date) {
    $appointment_date = new DateTime($date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    // Check if date is in the past
    if ($appointment_date < $today) {
        return false;
    }
    
    // Check if date is weekend (0 = Sunday, 6 = Saturday)
    if ($appointment_date->format('w') == 0 || $appointment_date->format('w') == 6) {
        return false;
    }
    
    return true;
}

// Get available slots for a specific date
function getAvailableSlots($date, $time_period) {
    $conn = getDBConnection();
    
    // Get max appointments for this day of week and time period
    $day_of_week = date('w', strtotime($date));
    $sql = "SELECT max_appointments FROM appointment_time_slots 
            WHERE day_of_week = ? AND time_period = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $day_of_week, $time_period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $max_appointments = $row['max_appointments'];
    } else {
        // Default values if not found in database
        $max_appointments = 50;
    }
    $stmt->close();
    
    // Count current bookings
    $sql = "SELECT COUNT(*) as booked FROM appointments 
            WHERE appointment_date = ? AND time_period = ? 
            AND status IN ('pending', 'confirmed', 'completed')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $date, $time_period);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $booked = $row['booked'];
    $stmt->close();
    
    $conn->close();
    
    return max(0, $max_appointments - $booked);
}

// Get appointment statistics
function getAppointmentStatistics() {
    $conn = getDBConnection();
    
    $stats = [];
    
    // Total appointments
    $result = $conn->query("SELECT COUNT(*) as total FROM appointments");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // By status
    $result = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    $stats['by_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_status'][$row['status']] = $row['count'];
    }
    
    // Today's appointments
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $stats['today'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    // Pending appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending'] = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    $conn->close();
    
    return $stats;
}

// Log activity (for audit trail)
function logActivity($action, $user_id = null, $details = '') {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("INSERT INTO activity_log (action, user_id, details, created_at) 
                           VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sis", $action, $user_id, $details);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
}

// Error handler for database errors
function handleDBError($error) {
    // Log the error
    error_log("Database Error: " . $error);
    
    // In production, show a generic error message
    // In development, you might want to show the actual error
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        return "Database Error: " . $error;
    } else {
        return "A system error occurred. Please try again later.";
    }
}
?>
