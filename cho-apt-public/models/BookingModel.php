<?php
class BookingModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 1. Check if the date has available slots
    public function isDateAvailable($date) {
        $max_total = 0;
        
        // Check date overrides first
        $ov = $this->conn->prepare("SELECT am_capacity, pm_capacity FROM date_slot_overrides WHERE override_date = ?");
        $ov->bind_param("s", $date); 
        $ov->execute();
        $ovr = $ov->get_result();
        
        if ($ovr->num_rows > 0) {
            $row = $ovr->fetch_assoc();
            $max_total = (int)$row['am_capacity'] + (int)$row['pm_capacity'];
        } else {
            // Fallback to default weekday capacities
            $day_of_week = strtolower(date('l', strtotime($date)));
            $sl = $this->conn->prepare("SELECT time_period, max_appointments FROM appointment_am_pm_slots WHERE day_of_week = ? AND is_active = 1");
            $sl->bind_param("s", $day_of_week); 
            $sl->execute();
            $slr = $sl->get_result();
            $am = 50; $pm = 50;
            while ($r = $slr->fetch_assoc()) {
                if ($r['time_period'] === 'AM') $am = (int)$r['max_appointments'];
                if ($r['time_period'] === 'PM') $pm = (int)$r['max_appointments'];
            }
            $sl->close();
            $max_total = $am + $pm;
        }
        $ov->close();

        // Count current bookings
        $cnt = $this->conn->prepare("SELECT COUNT(*) AS c FROM appointments WHERE appointment_date = ? AND status IN ('pending','confirmed','completed')");
        $cnt->bind_param("s", $date); 
        $cnt->execute();
        $current = (int)$cnt->get_result()->fetch_assoc()['c'];
        $cnt->close();

        if ($max_total == 0) return ['available' => false, 'error' => 'This date is not available. Please choose another date.'];
        if ($current >= $max_total) return ['available' => false, 'error' => 'This date is fully booked. Please choose another date.'];
        
        return ['available' => true, 'error' => ''];
    }

    // 2. Process the actual booking
    public function createBooking($info, $appointment_date) {
        $appointment_time = 'AM';
        $time_period = 'AM';
        
        $client_name = trim("{$info['first_name']} {$info['middle_name']} {$info['last_name']} {$info['suffix']}");
        
        // A. Insert basic appointment
        $stmt = $this->conn->prepare("INSERT INTO appointments (client_name, contact_number, appointment_date, appointment_time, time_period, purpose, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssss", $client_name, $info['contact_number'], $appointment_date, $appointment_time, $time_period, $info['purpose']);
        $stmt->execute();
        $appointment_id = $stmt->insert_id;
        $reference_number = 'CHO-' . date('Y') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
        $stmt->close();

        // B. Update with full details
        $upd = $this->conn->prepare("UPDATE appointments SET philhealth_no=?, last_name=?, first_name=?, middle_name=?, suffix=?, date_of_birth=?, sex=?, civil_status=?, barangay=?, email=?, notes=?, reference_number=?, maiden_name=? WHERE id=?");
        $upd->bind_param("sssssssssssssi", 
            $info['philhealth_no'], $info['last_name'], $info['first_name'], $info['middle_name'], $info['suffix'], 
            $info['date_of_birth'], $info['sex'], $info['civil_status'], $info['barangay'], $info['email'], 
            $info['notes'], $reference_number, $info['maiden_name'], $appointment_id
        );
        $upd->execute(); 
        $upd->close();

        // C. Create Patient Enrollment Record
        try {
            $age = (int)date_diff(date_create($info['date_of_birth']), date_create('today'))->y;
            $system_uid = 3;
            $enroll_purpose = $info['purpose'] . ' [Ref: ' . $reference_number . ']';
            
            // Check if already enrolled to prevent duplicates
            $ec = $this->conn->prepare("SELECT id FROM patient_enrollment WHERE purpose_of_visit LIKE ? LIMIT 1");
            $rs = '%' . $reference_number . '%';
            $ec->bind_param("s", $rs); 
            $ec->execute();
            
            if ($ec->get_result()->num_rows === 0) {
                $es = $this->conn->prepare("INSERT INTO patient_enrollment (user_id,last_name,first_name,middle_name,suffix,maiden_name,age,sex,birth_date,contact_number,residential_address,civil_status,philhealth_no,philhealth_member,philhealth_status_type,primary_care_benefit,category,spouse_name,mother_name,educational_attainment,employment_status,dswd_nhts,four_ps_member,facility_household_no,household_no,co_habitation,family_member,date_of_consultation,consultation_time,purpose_of_visit,mode_of_transaction,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                $es->bind_param("isssssissssssssssssssssssssssss", 
                    $system_uid, $info['last_name'], $info['first_name'], $info['middle_name'], $info['suffix'], 
                    $info['maiden_name'], $age, $info['sex'], $info['date_of_birth'], $info['contact_number'], 
                    $info['barangay'], $info['civil_status'], $info['philhealth_no'], $info['philhealth_member'], 
                    $info['philhealth_status_type'], $info['primary_care_benefit'], $info['category'], 
                    $info['spouse_name'], $info['mother_name'], $info['educational_attainment'], $info['employment_status'], 
                    $info['dswd_nhts'], $info['four_ps_member'], $info['facility_household_no'], $info['household_no'], 
                    $info['co_habitation'], $info['family_member'], $appointment_date, $appointment_time, 
                    $enroll_purpose, $info['mode_of_transaction']
                );
                $es->execute(); 
                $es->close();
            }
            $ec->close();
        } catch (Exception $ee) {
            error_log('Enrollment failed: ' . $ee->getMessage());
        }

        return $reference_number;
    }

    // Fetch appointment by reference number
    public function getAppointmentByRef($reference_number) {
        $stmt = $this->conn->prepare("SELECT * FROM appointments WHERE reference_number = ?");
        $stmt->bind_param("s", $reference_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $appointment;
    }

    // Update QR Code data
    public function updateQRCodeData($id, $qr_data) {
        $stmt = $this->conn->prepare("UPDATE appointments SET qr_code_data = ? WHERE id = ?");
        $stmt->bind_param("si", $qr_data, $id);
        $stmt->execute();
        $stmt->close();
    }
}
?>