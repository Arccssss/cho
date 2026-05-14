<?php
// models/DashboardModel.php

class DashboardModel {
    private $conn;

    // We pass the database connection into the model when we create it
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

public function getOverviewStats() {
        // 1. Set safe default values so the HTML never breaks
        $stats = [
            'total_forms' => 0,
            'forms_today' => 0,
            'forms_week'  => 0,
            'forms_month' => 0
        ];

        // 2. Safely run queries and only update the stats if the query succeeds
        $res1 = $this->conn->query("SELECT COUNT(*) as total FROM consent_forms");
        if ($res1) $stats['total_forms'] = $res1->fetch_assoc()['total'];

        $res2 = $this->conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE DATE(created_at) = CURDATE()");
        if ($res2) $stats['forms_today'] = $res2->fetch_assoc()['total'];

        $res3 = $this->conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
        if ($res3) $stats['forms_week'] = $res3->fetch_assoc()['total'];

        $res4 = $this->conn->query("SELECT COUNT(*) as total FROM consent_forms WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
        if ($res4) $stats['forms_month'] = $res4->fetch_assoc()['total'];

        return $stats;
    }

    public function getRecentConsentForms($limit = 50) {
        $stmt = $this->conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                                      FROM consent_forms cf 
                                      JOIN users u ON cf.user_id = u.id 
                                      ORDER BY cf.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function getRecentITRForms($limit = 50) {
        $stmt = $this->conn->prepare("SELECT pe.*, u.full_name as creator_name, u.email as creator_email 
                                      FROM patient_enrollment pe 
                                      JOIN users u ON pe.user_id = u.id 
                                      ORDER BY pe.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function getTodayServiceAnalytics(&$total_forms) {
        $service_counts = [];
        $total_forms = 0;

        // Count Consent Forms
        $result = $this->conn->query("SELECT service_type FROM consent_forms WHERE DATE(created_at) = CURDATE()");
        while ($row = $result->fetch_assoc()) {
            $total_forms++;
            $services = array_map('trim', explode(',', $row['service_type']));
            foreach ($services as $service) {
                if (!empty($service)) {
                    $service_counts[$service] = isset($service_counts[$service]) ? $service_counts[$service] + 1 : 1;
                }
            }
        }

        // Count ITR Enrollments
        $result = $this->conn->query("SELECT purpose_of_visit FROM patient_enrollment WHERE DATE(created_at) = CURDATE() AND purpose_of_visit IS NOT NULL AND purpose_of_visit != ''");
        while ($row = $result->fetch_assoc()) {
            $total_forms++;
            $cleaned = preg_replace('/\s*\[Ref:[^\]]*\]/', '', $row['purpose_of_visit']);
            $services = array_map('trim', explode(',', $cleaned));
            foreach ($services as $service) {
                if (!empty($service)) {
                    $service_counts[$service] = isset($service_counts[$service]) ? $service_counts[$service] + 1 : 1;
                }
            }
        }

        arsort($service_counts);
        return $service_counts;
    }
}
?>