<?php
// models/CalendarModel.php

class CalendarModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 1. Ensure Dental Table Exists (Safety Check)
    public function setupDentalTable() {
        $this->conn->query("CREATE TABLE IF NOT EXISTS `dental_slots` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
            `max_appointments` int(11) NOT NULL DEFAULT 10,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_day` (`day_of_week`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->conn->query("INSERT IGNORE INTO dental_slots (day_of_week, max_appointments) VALUES
            ('monday',10),('tuesday',10),('wednesday',10),('thursday',10),('friday',10),('saturday',0),('sunday',0)");
    }

    // 2. Load regular weekday capacities
    public function getWeekdayCapacities() {
        $cap = [];
        $sl = $this->conn->query("SELECT day_of_week, time_period, max_appointments FROM appointment_am_pm_slots WHERE is_active = 1");
        if ($sl) {
            while ($r = $sl->fetch_assoc()) {
                $cap[$r['day_of_week']][$r['time_period']] = (int)$r['max_appointments'];
            }
        }
        return $cap;
    }

    // 3. Load date-specific overrides
    public function getOverrides($month, $year) {
        $overrides = [];
        $ov = $this->conn->prepare("SELECT override_date, am_capacity, pm_capacity FROM date_slot_overrides WHERE MONTH(override_date)=? AND YEAR(override_date)=?");
        $ov->bind_param("ii", $month, $year);
        $ov->execute();
        $ovr = $ov->get_result();
        while ($r = $ovr->fetch_assoc()) {
            $overrides[$r['override_date']] = ['AM' => (int)$r['am_capacity'], 'PM' => (int)$r['pm_capacity']];
        }
        $ov->close();
        return $overrides;
    }

    // 4. Count booked general appointments
    public function getBookedAppointments($month, $year) {
        $booked = [];
        $bq = $this->conn->prepare("SELECT appointment_date, time_period, COUNT(*) AS n FROM appointments WHERE MONTH(appointment_date)=? AND YEAR(appointment_date)=? AND status IN ('pending','confirmed','completed') GROUP BY appointment_date, time_period");
        $bq->bind_param("ii", $month, $year);
        $bq->execute();
        $bqr = $bq->get_result();
        while ($r = $bqr->fetch_assoc()) {
            $booked[$r['appointment_date']][$r['time_period']] = (int)$r['n'];
        }
        $bq->close();
        return $booked;
    }

    // 5. Load dental capacities
    public function getDentalCapacities() {
        $dental_cap = [];
        $ds = $this->conn->query("SELECT day_of_week, max_appointments FROM dental_slots WHERE is_active = 1");
        if ($ds) {
            while ($r = $ds->fetch_assoc()) {
                $dental_cap[$r['day_of_week']] = (int)$r['max_appointments'];
            }
        }
        return $dental_cap;
    }

    // 6. Count booked dental appointments
    public function getBookedDentalAppointments($month, $year) {
        $dental_booked = [];
        $dbq = $this->conn->prepare("SELECT appointment_date, COUNT(*) AS n FROM appointments WHERE MONTH(appointment_date)=? AND YEAR(appointment_date)=? AND status IN ('pending','confirmed','completed') AND purpose LIKE '%Dental%' GROUP BY appointment_date");
        $dbq->bind_param("ii", $month, $year);
        $dbq->execute();
        $dbqr = $dbq->get_result();
        while ($r = $dbqr->fetch_assoc()) {
            $dental_booked[$r['appointment_date']] = (int)$r['n'];
        }
        $dbq->close();
        return $dental_booked;
    }
}
?>