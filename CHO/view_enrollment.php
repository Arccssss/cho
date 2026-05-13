<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php?role=admin');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin_dashboard.php');
}

// Forward to create_patient_enrollment.php in view mode
redirect('create_patient_enrollment.php?view=' . intval($_GET['id']));
