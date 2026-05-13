<?php
require_once 'database.php';

// Set headers to prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Get year and month from GET parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Validate year and month
if ($year < 2020 || $year > 2030) {
    $year = date('Y');
}
if ($month < 1 || $month > 12) {
    $month = date('n');
}

// Connect to database
$conn = getDBConnection();

// Get the maximum appointments for each day of the week for AM and PM
$max_slots_sql = "SELECT day_of_week, time_period, max_appointments 
                   FROM appointment_am_pm_slots 
                   WHERE is_active = 1";
$max_stmt = $conn->prepare($max_slots_sql);
$max_stmt->execute();
$max_result = $max_stmt->get_result();

// Get date-specific overrides for the current month (with error handling)
    $date_overrides = [];
    try {
        $overrides_sql = "SELECT override_date, am_capacity, pm_capacity 
                         FROM date_slot_overrides 
                         WHERE override_date BETWEEN ? AND LAST_DAY(?)";
        $overrides_stmt = $conn->prepare($overrides_sql);
        $month_start = "$year-$month-01";
        $month_end = "$year-$month-31";
        $overrides_stmt->bind_param("ss", $month_start, $month_end);
        $overrides_stmt->execute();
        $overrides_result = $overrides_stmt->get_result();
        
        while ($row = $overrides_result->fetch_assoc()) {
            $date_overrides[$row['override_date']] = [
                'am_capacity' => $row['am_capacity'],
                'pm_capacity' => $row['pm_capacity']
            ];
        }
    } catch (Exception $e) {
        // Table doesn't exist or other error, continue with empty overrides
        $date_overrides = [];
    }

// Default max appointments if not found in database
$default_max_am = 50;
$default_max_pm = 50;

// Create arrays to store max appointments for each day
$max_appointments = [];
while ($row = $max_result->fetch_assoc()) {
    $day_of_week = $row['day_of_week'];
    $time_period = $row['time_period'];
    $max_appointments[$day_of_week][$time_period] = $row['max_appointments'];
}

$bookings = [];

// Get the number of days in the month
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Process each day of the month
for ($day = 1; $day <= $days_in_month; $day++) {
    // Create date string in YYYY-MM-DD format
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    
    // Get day of week as lowercase string
    $day_of_week = strtolower(date('l', strtotime($date)));
    
    // Skip weekends
    if ($day_of_week === 'sunday' || $day_of_week === 'saturday') {
        continue;
    }
    
    // Get max appointments for this day of week
    $max_am = isset($max_appointments[$day_of_week]['AM']) ? $max_appointments[$day_of_week]['AM'] : $default_max_am;
    $max_pm = isset($max_appointments[$day_of_week]['PM']) ? $max_appointments[$day_of_week]['PM'] : $default_max_pm;
    
    // Check for date-specific override (priority over weekday settings)
    if (isset($date_overrides[$date])) {
        $max_am = $date_overrides[$date]['am_capacity'];
        $max_pm = $date_overrides[$date]['pm_capacity'];
    }
    
    // Count existing appointments for this date and time period
    $count_sql = "SELECT time_period, COUNT(*) as booked_count 
                  FROM appointments 
                  WHERE appointment_date = ? AND status IN ('pending', 'confirmed', 'completed')
                  AND status NOT IN ('cancelled', 'no_show')
                  GROUP BY time_period";
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $date);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    
    // Initialize booked counts
    $booked_am = 0;
    $booked_pm = 0;
    
    // Process counting result
    while ($row = $count_result->fetch_assoc()) {
        if ($row['time_period'] === 'AM') {
            $booked_am = $row['booked_count'];
        } elseif ($row['time_period'] === 'PM') {
            $booked_pm = $row['booked_count'];
        }
    }
    
    // Calculate available slots
    $available_am = max(0, $max_am - $booked_am);
    $available_pm = max(0, $max_pm - $booked_pm);
    
    // Store booking data
    $bookings[$date] = [
        'AM' => $available_am,
        'PM' => $available_pm
    ];
    
    $count_stmt->close();
}

// Prepare response
$response = [
    'year' => $year,
    'month' => $month,
    'bookings' => $bookings
];

// Send JSON response
echo json_encode($response);

$conn->close();
?>
