<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cho_consent_system');

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// 1. OPTIMIZATION: Singleton Database Connection
function getDBConnection() {
    // Static variable remembers the connection across multiple function calls
    static $conn = null;
    
    // Only connect if we haven't connected yet
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            // Log the real error to the server, show a safe message to the user
            error_log("Connection failed: " . $conn->connect_error);
            die("A system error occurred. Please try again later.");
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Close database connection (Only call this at the VERY END of your page script)
function closeDBConnection() {
    $conn = getDBConnection();
    if ($conn) {
        $conn->close();
    }
}

// 2. SECURITY FIX: Proper Sanitization (No HTML encoding before DB insertion)
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data); // Safely handle arrays
    }
    $data = trim($data);
    $data = stripslashes($data);
    // Note: htmlspecialchars() removed. Only use it when echoing data in HTML.
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
    
    if ($appointment_date < $today) return false;
    
    // Check if date is weekend (0 = Sunday, 6 = Saturday)
    $day_of_week = $appointment_date->format('w');
    if ($day_of_week == 0 || $day_of_week == 6) return false;
    
    return true;
}

// 3. OPTIMIZATION: Removed premature connection closing
function getAvailableSlots($date, $time_period) {
    $conn = getDBConnection();
    
    $day_of_week = date('w', strtotime($date));
    $max_appointments = 50; // Default fallback
    
    // Get max appointments
    $sql_max = "SELECT max_appointments FROM appointment_time_slots WHERE day_of_week = ? AND time_period = ?";
    if ($stmt = $conn->prepare($sql_max)) {
        $stmt->bind_param("is", $day_of_week, $time_period);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $max_appointments = $row['max_appointments'];
        }
        $stmt->close();
    }
    
    // Count current bookings
    $sql_booked = "SELECT COUNT(*) as booked FROM appointments 
                   WHERE appointment_date = ? AND time_period = ? 
                   AND status IN ('pending', 'confirmed', 'completed')";
    if ($stmt = $conn->prepare($sql_booked)) {
        $stmt->bind_param("ss", $date, $time_period);
        $stmt->execute();
        $booked = $stmt->get_result()->fetch_assoc()['booked'] ?? 0;
        $stmt->close();
    } else {
        $booked = 0;
    }
    
    // DO NOT CLOSE $conn here! It will break other functions that need it.
    
    return max(0, $max_appointments - $booked);
}

// 4. OPTIMIZATION: Combined 3 Queries into 1
function getAppointmentStatistics() {
    $conn = getDBConnection();
    
    $stats = [
        'total' => 0,
        'today' => 0,
        'pending' => 0,
        'by_status' => []
    ];
    
    // Single query to get total, today, and pending using conditional aggregation
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM appointments";
        
    if ($result = $conn->query($query)) {
        $row = $result->fetch_assoc();
        $stats['total'] = (int)$row['total'];
        $stats['today'] = (int)$row['today'];
        $stats['pending'] = (int)$row['pending'];
    }
    
    // Status breakdown
    $status_result = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    if ($status_result) {
        while ($row = $status_result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
    }
    
    return $stats;
}

// Log activity (for audit trail)
function logActivity($action, $user_id = null, $details = '') {
    $conn = getDBConnection();
    
    if ($stmt = $conn->prepare("INSERT INTO activity_log (action, user_id, details, created_at) VALUES (?, ?, ?, NOW())")) {
        $stmt->bind_param("sis", $action, $user_id, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Error handler for database errors
function handleDBError($error) {
    error_log("Database Error: " . $error);
    
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        return "Database Error: " . $error;
    }
    return "A system error occurred. Please try again later.";
}
?>