-- Migration: Add dynamic slot tracking to appointment_time_slots table
-- This will help track current availability in real-time

-- Add columns to track current slot counts
ALTER TABLE `appointment_time_slots` 
ADD COLUMN `current_booked` int(11) DEFAULT 0 COMMENT 'Current number of booked appointments',
ADD COLUMN `available_slots` int(11) GENERATED ALWAYS AS (max_appointments - current_booked) STORED COMMENT 'Available slots calculated dynamically',
ADD COLUMN `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last time slot count was updated';

-- Create indexes for better performance
CREATE INDEX idx_appointment_time_slots_lookup ON `appointment_time_slots` (day_of_week, time_period, is_active);
CREATE INDEX idx_appointments_date_period ON `appointments` (appointment_date, time_period, status);

-- Create a trigger to automatically update current_booked when appointments change
DELIMITER //

CREATE TRIGGER `tr_appointment_insert_update_slots`
AFTER INSERT ON `appointments`
FOR EACH ROW
BEGIN
    IF NEW.status NOT IN ('cancelled', 'no_show') THEN
        UPDATE appointment_time_slots 
        SET current_booked = current_booked + 1,
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    END IF;
END//

CREATE TRIGGER `tr_appointment_update_slots`
AFTER UPDATE ON `appointments`
FOR EACH ROW
BEGIN
    -- Handle status changes that affect slot counts
    IF OLD.status IN ('cancelled', 'no_show') AND NEW.status NOT IN ('cancelled', 'no_show') THEN
        -- Appointment was cancelled/no-show and is now active
        UPDATE appointment_time_slots 
        SET current_booked = current_booked + 1,
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    ELSEIF OLD.status NOT IN ('cancelled', 'no_show') AND NEW.status IN ('cancelled', 'no_show') THEN
        -- Appointment was active and is now cancelled/no-show
        UPDATE appointment_time_slots 
        SET current_booked = GREATEST(0, current_booked - 1),
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(NEW.appointment_date)) 
        AND time_period = NEW.time_period;
    END IF;
END//

CREATE TRIGGER `tr_appointment_delete_update_slots`
AFTER DELETE ON `appointments`
FOR EACH ROW
BEGIN
    IF OLD.status NOT IN ('cancelled', 'no_show') THEN
        UPDATE appointment_time_slots 
        SET current_booked = GREATEST(0, current_booked - 1),
            last_updated = NOW()
        WHERE day_of_week = LOWER(DAYNAME(OLD.appointment_date)) 
        AND time_period = OLD.time_period;
    END IF;
END//

DELIMITER ;

-- Update current_booked counts for existing data
UPDATE appointment_time_slots ats
SET current_booked = (
    SELECT COUNT(*) 
    FROM appointments a 
    WHERE a.status NOT IN ('cancelled', 'no_show')
    AND LOWER(DAYNAME(a.appointment_date)) = ats.day_of_week
    AND a.time_period = ats.time_period
);
