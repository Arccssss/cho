# BCHO Admin Portal & Patient Management System

## System Overview
The Administrative Portal for the Bacolod City Health Office (BCHO) Patient Consent and Appointment System. This secure backend environment is designed for healthcare staff and administrators to digitize patient medical records, manage electronic consent forms, and oversee daily clinic operations.
   

---

## Technology Stack
* **Backend:** PHP 8.1+
* **Database:** MariaDB 10.4+ / MySQL (mysqli extension)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript
* **Icons & UI:** FontAwesome 6.4.0, Custom CSS styling

---

## Core Features & Modules

### 1. Advanced Appointment & Capacity Management
* **Trigger-Based Slot Allocation:** The database utilizes active SQL triggers (`AFTER INSERT`, `AFTER UPDATE`, `AFTER DELETE`) to automatically calculate and adjust available clinic slots in real-time based on appointment statuses (e.g., automatically freeing up a slot if a patient is marked as 'cancelled' or 'no_show').
* **Dynamic Overrides:** Administrators can set date-specific capacity overrides or manage specialized service limits independently.
* **Cross-Environment Integration:** Directly links to the public-facing portal for seamless slot management.

### 2. Comprehensive Patient Records (ITR)
* **Digital Patient Enrollment:** Full digital capture of the Individual Treatment Record (ITR), including extensive demographics, PhilHealth categorization (Member/Dependent, NHTS, 4Ps), vitals, chief complaints, and diagnoses.
* **Print-Optimized Generation:** Dual-interface design utilizing CSS ensures that digital records map perfectly onto physical BCHO paper forms when printed.

### 3. Electronic Consent Forms
* **Digital Capture:** Secure upload and storage of patient photos and electronic signatures.
* **Service Tracking:** Categorizes consent by specific medical services (ABTC, Dental, Prenatal, Social Hygiene, etc.).

### 4. Real-Time Analytics Dashboard
* **Atomic Counting:** Utilizes server-side counting for daily, weekly, and monthly metrics to prevent database overloading during peak clinic hours.
* **Service Breakdown:** Automatically parses and tallies comma-separated medical services to show exactly what departments are busiest on any given day.

### 5. High-Performance UI/UX
* **Instant Global Search:** Custom Vanilla JavaScript regex search allows staff to instantly filter through massive data tables (by name, service, date, or creator) without page reloads.
* **Tabbed Data Views:** single-page interface switching between recent Consent Forms and ITRs.
* **Flash Messaging:** Integrated session-based flash messages for immediate user feedback on CRUD operations.

---

## Database Architecture

The system relies on a highly relational structure centered around the `users` table. Key tables include:

* `users`: Handles authentication and Role-Based Access Control.
* `appointments`: Stores booking data, generated QR strings, patient demographics, and arrival statuses.
* `appointment_time_slots` & `appointment_am_pm_slots`: Manages the standard weekly schedule and maximum capacities.
* `patient_enrollment`: The core ITR table storing comprehensive medical and socio-economic data.
* `consent_forms`: Links patients to specific services, storing paths to generated signature and photo assets.

---

## Installation & Setup

### Prerequisites
* A local web server stack (e.g., XAMPP, WAMP, LAMP) running PHP 8.1 or higher.
* MySQL or MariaDB.

### Step-by-Step Guide
1. **Clone the Repository:**
   Clone this repository into your local web server's root directory (e.g., `htdocs` or `www`).
   ```bash
   git clone [your-repository-url] cho-apt

2. **Database Initialization:**

   Open phpMyAdmin or your preferred SQL client.
   Create a new database named cho_consent_system.
   Import the cho_consent_system.sql file located in the folder. Note: Ensure the SQL triggers are successfully imported.

3. **Configure Environment Connections:**

   Open config.php in the root directory.
   Verify and update the database credentials to match your local environment:

   define('DB_HOST', 'localhost');
   define('DB_USER', 'root'); // Your MySQL username
   define('DB_PASS', '');     // Your MySQL password
   define('DB_NAME', 'cho_consent_system');

4. **Directory Permissions:**

   Ensure the uploads/ directory has the correct write permissions (755 or 777 depending on your local setup) so the system can save patient photos and signatures.

5. **Launch:**
   Start your Apache and MySQL modules.
   Access the dashboard via your browser
