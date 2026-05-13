-- Dental slot capacity table (separate from general appointment slots)
CREATE TABLE IF NOT EXISTS `dental_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `max_appointments` int(11) NOT NULL DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default dental slots: 10 per weekday
INSERT INTO `dental_slots` (`day_of_week`, `max_appointments`) VALUES
('monday',    10),
('tuesday',   10),
('wednesday', 10),
('thursday',  10),
('friday',    10),
('saturday',  0),
('sunday',    0)
ON DUPLICATE KEY UPDATE `max_appointments` = VALUES(`max_appointments`);
