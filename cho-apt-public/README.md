# BCHO Public Appointment & Consent Portal

## System Overview
The public-facing Patient Portal and Booking Engine for the Bacolod City Health Office (BCHO). This application allows citizens of Bacolod to securely schedule health services, manage their appointments, submit required demographic data, and digitally sign medical consent forms prior to their visit.

---

## Technology Stack
* **Backend:** PHP (8.1+)
* **Database:** MariaDB (10.4+) / MySQL (Shared with BCHO Admin System)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.1
* **Machine Learning:** TensorFlow.js (`@tensorflow/tfjs`) & COCO-SSD Model (Liveness Check)
* **Native Web APIs:** MediaDevices API, Canvas API, FileReader API

---

## Core Features & Modules

### 1. Smart Booking Engine & Real-Time Calendar
* **Dynamic Availability API:** The `get_month_bookings.php` endpoint acts as a lightweight API, returning real-time JSON data to the frontend calendar. It calculates available slots by subtracting booked appointments from the daily maximums.
* **Concurrency Protection:** The system inherently prevents double-booking by querying active SQL triggers and capacity limits at the exact moment of form submission.
* **Automated ITR Generation:** Upon a successful booking, the system automatically duplicates the patient's demographic data into the `patient_enrollment` (ITR) table, eliminating redundant data entry for clinic staff.

### 2. Administrative Tools
*Note: While this repository serves the public, it houses specific administrative modules secured by Role-Based Access Control.*
* **Slot Capacity Management:** Allows admins to set standard weekly limits for specific departments (e.g., Dental slots) and apply date-specific overrides (e.g., reducing capacity for holidays or staff shortages) which instantly update the public calendar.
* **Appointment Management:** A comprehensive dashboard for clinic staff to filter, search, and update appointment statuses (Confirm, Cancel, Complete, No-Show) with a detailed client information modal.

### 3. Patient Portal Dashboard
* **Authenticated Access:** Logged-in patients can view their appointment history, book new sessions (`book_appointment.php`), and track their medical records.
* **Integrated Check-in:** Generates downloadable QR codes (`qrcode.min.js`) upon successful booking to expedite physical processing at the clinic gates.

### 4. Advanced Digital Consent (ML Integration)
* **Facial Verification (Liveness Check):** Utilizes browser-based TensorFlow models to verify human presence before a medical consent form can be generated and submitted.
* **Digital Signature & Image Processing:** Leverages the HTML Canvas API to power a mobile-friendly digital signature pad and a custom blur-detection algorithm for patient photo uploads.

---

## Database Architecture Notes
This portal relies on the central `cho_consent_system` database managed by the Admin repository. It heavily interacts with:
* `appointments`: For storing bookings and statuses.
* `appointment_time_slots` & `date_slot_overrides`: For determining daily maximum capacities.
* `patient_enrollment`: For initializing the Individual Treatment Record (ITR).

---

## Installation & Setup

1. **Prerequisites:** A local web server stack (e.g., XAMPP, WAMP) running PHP 8.1+.
2. **File Placement:** Clone this repository into your local web server's root directory.
3. **Database Connection:** * Ensure the central `cho_consent_system` database has been fully imported from the Admin repository (including all SQL triggers).
   * Open `database.php` (or `database_connection.php`) and verify the database credentials match your local environment:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'cho_consent_system');
     ```
4. **Launch:** Start your Apache and MySQL modules, then access the portal via `http://localhost/cho-apt-public/index.php`.

---

## Security Notes
* **Shared Environment Risk:** Because admin files (`slot_management.php` and `manage_appointments.php`) exist in this public-facing folder, ensure that your `isAdmin()` session checks are rigorously maintained. 
* **Session Security:** The booking flow relies heavily on `$_SESSION['booking_info']` to pass data between steps. Ensure sessions are properly destroyed or unset upon successful booking to prevent data leakage between public users on shared devices.