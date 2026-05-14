<?php
// models/SlotModel.php

class SlotModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 1. Ensure tables exist
    public function setupTables() {
        // Dental Slots Table
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

        // Overrides Table
        $this->conn->query("CREATE TABLE IF NOT EXISTS `date_slot_overrides` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `override_date` date NOT NULL,
            `am_capacity` int(11) NOT NULL DEFAULT 50,
            `pm_capacity` int(11) NOT NULL DEFAULT 50,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_date` (`override_date`),
            KEY `idx_date` (`override_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    // 2. Update Dental Slots
    public function updateDentalSlots($days_data) {
        $stmt = $this->conn->prepare("UPDATE dental_slots SET max_appointments = ?, updated_at = NOW() WHERE day_of_week = ?");
        foreach ($days_data as $day => $cap) {
            $stmt->bind_param("is", $cap, $day);
            $stmt->execute();
        }
        $stmt->close();
    }

    // 3. Set Date-Specific Override
    public function setDailyOverride($selected_date, $am_capacity, $pm_capacity) {
        $check_stmt = $this->conn->prepare("SELECT id FROM date_slot_overrides WHERE override_date = ?");
        $check_stmt->bind_param("s", $selected_date);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;
        $check_stmt->close();

        if (!$exists) {
            $stmt = $this->conn->prepare("INSERT INTO date_slot_overrides (override_date, am_capacity, pm_capacity, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sii", $selected_date, $am_capacity, $pm_capacity);
        } else {
            $stmt = $this->conn->prepare("UPDATE date_slot_overrides SET am_capacity = ?, pm_capacity = ?, updated_at = NOW() WHERE override_date = ?");
            $stmt->bind_param("iis", $am_capacity, $pm_capacity, $selected_date);
        }
        $stmt->execute();
        $stmt->close();
    }

    // 4. Fetch Current Dental Slots
    public function getDentalSlots() {
        $dental_slots = [];
        $result = $this->conn->query("SELECT day_of_week, max_appointments FROM dental_slots ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $dental_slots[$row['day_of_week']] = (int)$row['max_appointments'];
            }
        }
        return $dental_slots;
    }
}
?>