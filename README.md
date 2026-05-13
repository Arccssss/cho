# Bacolod City Health Office (BCHO) Patient Consent and Appointment System
## System Documentation

### 1. Introduction / System Overview
The BCHO Patient Consent and Appointment System is a digital management platform designed specifically for the Bacolod City Health Office. Its primary purpose is to manage electronic consent forms, and streamline the scheduling of health services across both public-facing and administrative portals. By moving away from physical paper trails, the system enhances data security, reduces redundancy, and accelerates patient processing.

### 2. Technology Stack & Architecture
The application is built on a reliable, traditional server-side rendering architecture with robust client-side interactivity, avoiding the overhead of heavy Single Page Application (SPA) frameworks.

**Backend & Database:**
* **PHP (8.1.25):** Core server-side language handling HTTP requests, routing, session management, and database interactions.
* **MariaDB (10.4.32) / MySQL:** Relational database management system storing patient demographics, appointments, and consent logs.

**Frontend & User Interface:**
* **HTML5 / CSS3 / Vanilla JavaScript:** Standard web technologies for structure, styling, and DOM manipulation.
* **Bootstrap 5 (5.1.3):** Integrated via CDN for responsive grid layouts, styling, and interactive components (like modals).
* **FontAwesome (6.4.0):** Vector icon library used for UI iconography.

**Machine Learning & Client-Side Verification:**
* **TensorFlow.js Core (`@tensorflow/tfjs@4.0.0`):** Enables running machine learning models directly in the user's web browser.
* **TensorFlow COCO-SSD Model (`@tensorflow-models/coco-ssd@2.2.3`):** Pre-trained object detection model utilized to verify human presence (liveness check) during consent form creation.

**Native Web APIs:**
* **MediaDevices API:** Requests webcam permissions and streams live video feeds for patient verification.
* **Canvas API:** Captures static video frames, extracts image data for custom blur detection (Laplacian variance), and powers the digital signature pad.
* **FileReader API:** Handles local file uploads, converting images to base64 Data URLs for immediate browser preview.

### 3. Installation & Setup Guide
Follow these instructions to deploy the system in a local development environment.

**Prerequisites:**
* A local web server stack that supports PHP and MySQL (e.g., XAMPP, WAMP, or LAMP).

**Step-by-Step Deployment:**
1.  **File Placement:** Extract the source code repository. Place the main application folders (`CHO` and `AppointmentCHO`) into your web server's document root (e.g., the `htdocs` folder for XAMPP).
2.  **Database Initialization:**
    * Open your database management interface (e.g., phpMyAdmin).
    * Create a new, empty database named `cho_consent_system`.
    * Import the provided SQL dump files (located in `CHO/Databases/cho_consent_system.sql` or `AppointmentCHO/databases/setup_database.sql`) to generate the required tables and constraints.
3.  **Environment Configuration:**
    * Navigate to `CHO/config.php` and `AppointmentCHO/database.php`.
    * Verify the database credentials match your environment (Default: Host=`localhost`, User=`root`, Password=`[empty]`, DB Name=`cho_consent_system`).
4.  **Launch:** Start the Apache and MySQL services. Access the admin dashboard at `http://localhost/CHO/` and the appointment portal at `http://localhost/AppointmentCHO/`.

### 4. User Guide / System Modules
The system is segmented into intuitive modules designed for specific operational tasks.

**Admin Dashboard (`admin_dashboard.php`):**
* **Real-time Metrics:** Utilizes atomic counting to provide up-to-date statistics on consent forms submitted and service types requested (e.g., Dental, Adult Consultation).
* **Global Search:** Features a custom JavaScript regex search that dynamically highlights matching text across Consent and ITR data tables without page reloads.

**Appointment Management (`manage_appointments.php`):**
* **CRUD Interface:** Allows administrators to filter and manage the daily schedule.
* **Status Workflows:** Uses Bootstrap modals to update appointment statuses (Pending, Confirmed, Completed, No Show), which subsequently triggers the notification pipeline for patients.

**Patient Enrollment (`create_patient_enrollment.php`):**
* **Comprehensive Data Collection:** Handles extensive demographic and medical history entry (e.g., OB-GYNE details).
* **"Pull from Appointment" Feature:** A workflow optimization that reads HTML dataset attributes from pending appointments to instantly pre-fill the enrollment form, drastically reducing manual data entry time.

### 5. Technical Reference (Developer Notes)
This section outlines the logic behind custom technical implementations to aid future maintenance.

**Facial Verification & Liveness Logic:**
During the creation of a consent form (`create_consent_form.php`), the system mandates a security check. Rather than uploading photos to a server for processing, the system loads the TensorFlow COCO-SSD model via JavaScript. It actively scans the `MediaDevices` video stream. The form submission is strictly disabled until the model returns a detection object of class `person` with a confidence score exceeding the designated threshold.

**Image Quality Control (Blur Detection):**
To ensure the captured patient photos are legible, the Canvas API extracts grayscale pixel arrays from the video frame. A custom Laplacian variance algorithm is applied to these arrays. If the variance is too low (indicating soft, unfocused edges), the system rejects the capture, prompting the user to retake the photo.

**Medical Record Printing (`patient_medical_record.php`):**
The record page implements a dual-interface design. While it functions as a standard digital dashboard on screens, the stylesheet relies heavily on specific `@media print` rules. When printed, these rules hide all digital UI elements (buttons, navbars) and restructure the data into absolute-positioned tables (`#bcho-hardcopy`). This ensures the printed output aligns perfectly with the physical BCHO Individual Treatment Record layout.
