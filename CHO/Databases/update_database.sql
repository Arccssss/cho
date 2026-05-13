-- SQL to add new columns to existing consent_forms table
-- Run this script to update your database structure

USE cho_consent_system;

-- Add new columns to consent_forms table
ALTER TABLE consent_forms ADD COLUMN date_of_birth DATE NOT NULL AFTER patient_photo;
ALTER TABLE consent_forms ADD COLUMN age INT NOT NULL AFTER date_of_birth;
ALTER TABLE consent_forms ADD COLUMN sex ENUM('Male', 'Female', 'Other') NOT NULL AFTER age;

-- Verify the table structure
DESCRIBE consent_forms;
