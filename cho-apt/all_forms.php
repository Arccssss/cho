<?php
require_once 'config/database.php';
require_once 'config/helpers.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?role=admin');
}

$conn = getDBConnection();

// Get all forms with user info
$stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                       FROM consent_forms cf 
                       JOIN users u ON cf.user_id = u.id 
                       ORDER BY cf.created_at DESC");
$stmt->execute();
$forms = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Forms - CHO Patient Consent System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/all_forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo-box">
                    <img src="images/bcd.jpg" alt="CHO Logo">
                </div>
                <div class="header-title">
                    <h2>CHO SYSTEM</h2>
                    <p>All Consent Forms Records</p>
                </div>
            </div>
            <div class="header-right">
                <p>Logged in as</p>
                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-nav">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            </div>
        </nav>

        <!-- Alert Messages -->
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="alert">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <!-- Section Title -->
        <h3 class="section-title"><i class="fas fa-file-medical-alt"></i> All Consent Forms</h3>

        <!-- Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-id-card"></i> Patient Name</th>
                        <th><i class="fas fa-stethoscope"></i> Service Type</th>
                        <th><i class="fas fa-user"></i> Created By</th>
                        <th><i class="fas fa-calendar"></i> Form Date</th>
                        <th><i class="fas fa-clock"></i> Created</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($forms->num_rows > 0): ?>
                        <?php while ($form = $forms->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $form['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($form['patient_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($form['service_type']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($form['creator_name']); ?><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($form['creator_email']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($form['form_date'])); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($form['created_at'])); ?></td>
                                <td>
                                    <a href="view_form.php?id=<?php echo $form['id']; ?>" class="btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-inbox"></i>
                                <p>No forms submitted yet.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
