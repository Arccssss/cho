-- Comprehensive Database Setup for AppointmentCHO System
-- This script ensures all necessary tables and columns exist

-- Create appointments table if it doesn't exist
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    patient_name VARCHAR(255) NOT NULL,
    philhealth_no VARCHAR(50),
    last_name VARCHAR(100),
    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    suffix VARCHAR(20),
    date_of_birth DATE,
    sex ENUM('Male', 'Female', 'Other'),
    civil_status VARCHAR(50),
    barangay VARCHAR(100),
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    time_period ENUM('AM', 'PM'),
    purpose VARCHAR(255) NOT NULL,
    service_type VARCHAR(255),
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_appointment_datetime (appointment_date, appointment_time),
    INDEX idx_appointment_search (appointment_date, time_period, status)
);

-- Add new columns if they don't exist (safe ALTER TABLE)
ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS philhealth_no VARCHAR(50) AFTER patient_name,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER philhealth_no,
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER last_name,
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS suffix VARCHAR(20) AFTER middle_name,
ADD COLUMN IF NOT EXISTS date_of_birth DATE AFTER suffix,
ADD COLUMN IF NOT EXISTS sex ENUM('Male', 'Female', 'Other') AFTER date_of_birth,
ADD COLUMN IF NOT EXISTS civil_status VARCHAR(50) AFTER sex,
ADD COLUMN IF NOT EXISTS barangay VARCHAR(100) AFTER civil_status,
ADD COLUMN IF NOT EXISTS time_period ENUM('AM', 'PM') AFTER appointment_time;

-- Create appointment_time_slots table for AM/PM periods
CREATE TABLE IF NOT EXISTS appointment_time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    time_period ENUM('AM', 'PM') NOT NULL,
    max_appointments INT DEFAULT 50,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_day_period (day_of_week, time_period)
);

-- Insert default AM/PM time slots if they don't exist
INSERT IGNORE INTO appointment_time_slots (day_of_week, time_period, max_appointments) VALUES
('monday', 'AM', 50),
('monday', 'PM', 50),
('tuesday', 'AM', 50),
('tuesday', 'PM', 50),
('wednesday', 'AM', 50),
('wednesday', 'PM', 50),
('thursday', 'AM', 50),
('thursday', 'PM', 50),
('friday', 'AM', 50),
('friday', 'PM', 50);

-- Update existing records to populate name fields (if needed)
UPDATE appointments 
SET 
    last_name = SUBSTRING_INDEX(patient_name, ' ', -1),
    first_name = SUBSTRING_INDEX(SUBSTRING_INDEX(patient_name, ' ', 1), ' ', -1)
WHERE patient_name IS NOT NULL 
AND last_name IS NULL 
AND first_name IS NULL;

-- Set default time periods based on appointment time
UPDATE appointments 
SET time_period = CASE 
    WHEN TIME(appointment_time) < '12:00:00' THEN 'AM'
    ELSE 'PM'
END
WHERE time_period IS NULL;

-- Ensure users table exists for compatibility
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Show final table structure
DESCRIBE appointments;
DESCRIBE appointment_time_slots;
