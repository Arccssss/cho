-- Patient Consent Form System Database Schema
-- For Bacolod City Health Office

-- Create database
CREATE DATABASE IF NOT EXISTS cho_consent_system;
USE cho_consent_system;

-- Users table (for both admin and regular users)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    contact_number VARCHAR(20) NOT NULL,
    password VARCHAR(258) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Consent forms table
CREATE TABLE IF NOT EXISTS consent_forms (
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

-- Insert default admin account
-- Email: admin_bcho@gov.ph
-- Password: admin123
INSERT INTO users (full_name, email, contact_number, password, role) VALUES 
('System Administrator', 'admin_bcho@gov.ph', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
