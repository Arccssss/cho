-- Simple approach to add columns
-- Run each line separately in phpMyAdmin

USE cho_consent_system;

-- First check current table structure
SHOW COLUMNS FROM consent_forms;

-- Add columns one by one (run each separately)
ALTER TABLE consent_forms ADD COLUMN date_of_birth DATE NULL;
ALTER TABLE consent_forms ADD COLUMN age INT NULL;
ALTER TABLE consent_forms ADD COLUMN sex VARCHAR(10) NULL;

-- Check final structure
SHOW COLUMNS FROM consent_forms;
