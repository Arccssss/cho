<?php
// models/FormModel.php

class FormModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Get a single form for editing
    public function getConsentFormById($form_id) {
        $stmt = $this->conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                                      FROM consent_forms cf 
                                      JOIN users u ON cf.user_id = u.id 
                                      WHERE cf.id = ?");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $form = $result->num_rows === 1 ? $result->fetch_assoc() : null;
        $stmt->close();
        
        return $form;
    }

    // Update an existing form
    public function updateConsentForm($form_id, $patient_name, $service_type, $form_date) {
        $stmt = $this->conn->prepare("UPDATE consent_forms SET patient_name = ?, service_type = ?, form_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $patient_name, $service_type, $form_date, $form_id);
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    // Delete a form and its associated files
    public function deleteConsentForm($form_id) {
        // First, get the form details to find the files
        $stmt = $this->conn->prepare("SELECT patient_photo, signature_data FROM consent_forms WHERE id = ?");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $form = $result->fetch_assoc();
            
            // Delete associated files from the server
            if ($form['patient_photo'] && file_exists($form['patient_photo'])) {
                unlink($form['patient_photo']);
            }
            if ($form['signature_data'] && file_exists($form['signature_data']) && $form['signature_data'] !== 'Signed with ballpen') {
                unlink($form['signature_data']);
            }
            
            // Now delete the record from the database
            $deleteStmt = $this->conn->prepare("DELETE FROM consent_forms WHERE id = ?");
            $deleteStmt->bind_param("i", $form_id);
            $success = $deleteStmt->execute();
            $deleteStmt->close();
            $stmt->close();
            
            return $success;
        }
        
        $stmt->close();
        return false;
    }

    public function getConsentFormWithCreator($form_id) {
        $stmt = $this->conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email FROM consent_forms cf JOIN users u ON cf.user_id = u.id WHERE cf.id = ?");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = null;
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $data;
    }

    // Create a new consent form and return the inserted ID
    public function createConsentForm($user_id, $patient_name, $photo_path, $date_of_birth, $age, $sex, $service_type, $form_date, $signature_path) {
        $stmt = $this->conn->prepare("INSERT INTO consent_forms (user_id, patient_name, patient_photo, date_of_birth, age, sex, service_type, form_date, signature_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissss", $user_id, $patient_name, $photo_path, $date_of_birth, $age, $sex, $service_type, $form_date, $signature_path);
        
        if ($stmt->execute()) {
            $insert_id = $stmt->insert_id;
            $stmt->close();
            return $insert_id;
        }
        
        $stmt->close();
        return false;
    }
}
?>