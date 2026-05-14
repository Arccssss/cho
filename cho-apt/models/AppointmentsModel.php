<?php
// Put this in models/AppointmentModel.php (or add to your existing DashboardModel)

class AppointmentModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function updateAppointmentStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getStatistics() {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show
            FROM appointments";
            
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getAppointmentsList($view_type, $date_filter, $status_filter, $search, $time_filter) {
        $where_conditions = ["1=1"];
        $params = [];
        $types = "";

        if ($view_type === 'today') {
            $browse_date = !empty($date_filter) ? $date_filter : date('Y-m-d');
            $where_conditions[] = "a.appointment_date = ?";
            $params[] = $browse_date;
            $types .= "s";
        } elseif ($view_type === 'history') {
            if (!empty($date_filter)) {
                $where_conditions[] = "a.appointment_date = ?";
                $params[] = $date_filter;
                $types .= "s";
            } else {
                $where_conditions[] = "a.appointment_date < ?";
                $params[] = date('Y-m-d');
                $types .= "s";
            }
        }

        if ($status_filter !== 'all') {
            $where_conditions[] = "a.status = ?";
            $params[] = $status_filter;
            $types .= "s";
        }

        if (!empty($search)) {
            $where_conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.contact_number LIKE ? OR a.purpose LIKE ?)";
            $search_param = "%$search%";
            array_push($params, $search_param, $search_param, $search_param, $search_param);
            $types .= "ssss";
        }

        if ($time_filter !== 'all') {
            $where_conditions[] = "a.time_period = ?";
            $params[] = $time_filter;
            $types .= "s";
        }

        $where_clause = "WHERE " . implode(" AND ", $where_conditions);

        $query = "SELECT a.*, 
                  CASE 
                      WHEN a.first_name IS NOT NULL AND a.last_name IS NOT NULL THEN 
                          CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.middle_name, ''), ' ', COALESCE(a.last_name, ''), ' ', COALESCE(a.suffix, ''))
                      WHEN u.full_name IS NOT NULL THEN u.full_name
                      ELSE 'Unknown Patient'
                  END as patient_display_name,
                  u.email as patient_email,
                  CASE 
                      WHEN u.id IS NOT NULL THEN u.full_name
                      ELSE 'Direct Booking'
                  END as created_by
                  FROM appointments a
                  LEFT JOIN users u ON a.user_id = u.id
                  $where_clause
                  ORDER BY a.appointment_date ASC, a.time_period ASC, a.id ASC";

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        $stmt->close();
        
        return $appointments;
    }
}
?>