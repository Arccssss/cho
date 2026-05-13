-- Backup existing data and recreate table
-- Use this if ALTER commands don't work

USE cho_consent_system;

-- Create backup of existing data
CREATE TABLE consent_forms_backup AS SELECT * FROM consent_forms;

-- Drop original table
DROP TABLE consent_forms;

-- Recreate with new structure
CREATE TABLE consent_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_photo VARCHAR(255) NOT NULL,
    date_of_birth DATE NOT NULL,
    age INT NOT NULL,
    sex ENUM('Male', 'Female', 'Other') NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    signature_data TEXT,
    form_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Restore data (use defaults for new fields)
INSERT INTO consent_forms (
    id, user_id, patient_name, patient_photo, service_type, 
    signature_data, form_date, created_at
)
SELECT 
    id, user_id, patient_name, patient_photo, service_type,
    signature_data, form_date, created_at
FROM consent_forms_backup;

-- Verify
SELECT COUNT(*) as total_forms FROM consent_forms;
