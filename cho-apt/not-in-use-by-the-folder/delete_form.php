<?php
require_once 'config/helpers.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?role=user');
}

// Get form ID from URL
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'No form ID provided.');
    redirect('user_dashboard.php');
}

$form_id = intval($_GET['id']);
$conn = getDBConnection();

// Get form details to verify ownership
if (isAdmin()) {
    // Admin can delete any form
    $stmt = $conn->prepare("SELECT * FROM consent_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
} else {
    // Regular users can only delete their own forms
    $stmt = $conn->prepare("SELECT * FROM consent_forms WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $form_id, $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Form not found or access denied.');
    redirect(isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php');
}

$form = $result->fetch_assoc();
$stmt->close();

// Delete the form
$stmt = $conn->prepare("DELETE FROM consent_forms WHERE id = ?");
$stmt->bind_param("i", $form_id);

if ($stmt->execute()) {
    
    // Delete associated files (photo and signature)
    if (!empty($form['patient_photo']) && file_exists($form['patient_photo'])) {
        unlink($form['patient_photo']);
    }
    if (!empty($form['signature_data']) && file_exists($form['signature_data'])) {
        unlink($form['signature_data']);
    }
    
    setFlashMessage('success', 'Consent form deleted successfully.');
} else {
    setFlashMessage('error', 'Failed to delete the form. Please try again.');
}

$stmt->close();
$conn->close();

// Redirect back to dashboard
redirect(isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php');
?>
