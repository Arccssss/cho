<?php
require_once 'database.php';

// Create table for date-specific slot overrides
$sql = "CREATE TABLE IF NOT EXISTS `date_slot_overrides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `override_date` date NOT NULL,
  `am_capacity` int(11) NOT NULL DEFAULT 50,
  `pm_capacity` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`override_date`),
  KEY `idx_date` (`override_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

$conn = getDBConnection();

if ($conn->query($sql)) {
    echo "✅ Date slot overrides table created successfully!\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
