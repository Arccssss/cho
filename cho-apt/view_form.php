<?php
require_once 'config/helpers.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('index.php?role=user');
}

if (!isset($_GET['id'])) {
    redirect(isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php');
}

$form_id = intval($_GET['id']);
$conn = getDBConnection();

if (isAdmin()) {
    $stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                           FROM consent_forms cf 
                           JOIN users u ON cf.user_id = u.id 
                           WHERE cf.id = ?");
    $stmt->bind_param("i", $form_id);
} else {
    $stmt = $conn->prepare("SELECT cf.*, u.full_name as creator_name, u.email as creator_email 
                           FROM consent_forms cf 
                           JOIN users u ON cf.user_id = u.id 
                           WHERE cf.id = ? AND cf.user_id = ?");
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
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Consent Form - CHO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: "Times New Roman", Times, serif;
            min-height: 100vh;
            padding: 30px 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: url('images/ngc.jpg') center center / cover no-repeat;
            filter: blur(6px) brightness(0.9);
            transform: scale(1.05);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: -1;
        }

        .no-print-nav {
            max-width: 8.5in;
            margin: 0 auto 16px auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            gap: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
        .btn-primary { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .btn-secondary { background: rgba(255,255,255,0.95); color: #FF6B35; border: 2px solid #FF6B35; }

        .printable-sheet {
            background: white;
            width: 8.5in;
            margin: 0 auto;
            padding: 0.2in 0.5in 0.5in 0.5in;
            position: relative;
            box-shadow: 0 8px 40px rgba(0,0,0,0.25);
            border-radius: 4px;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            body::before,
            body::after {
                display: none !important;
                content: none !important;
            }

            .no-print-nav { display: none !important; }

            .printable-sheet {
                width: 100% !important;
                margin: 0 !important;
                padding: 0.15in 0.5in 0.3in 0.5in !important;
                box-shadow: none !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
            }
            
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }

        /* HEADER */
        .form-header {
            display: grid;
            grid-template-columns: 100px 1fr 180px;
            align-items: start;
            margin-bottom: 8px;
        }
        .logo-box img { width: 80px; height: auto; }
        .center-titles { text-align: center; }
        .center-titles p { font-size: 11px; margin-bottom: 1px; }
        .center-titles h1 { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 1px; }
        .center-titles .main-label { 
            font-size: 16px; 
            margin-top: 5px; 
            font-weight: bold; 
            display: inline-block;
            border-bottom: 2px solid #000;
        }
        .services-list { font-size: 8.5px; line-height: 1.1; }
        .service-row { display: flex; align-items: center; gap: 4px; margin-bottom: 1px; }

        /* BODY */
        .form-content { margin-top: 10px; overflow: hidden; }
        .patient-photo-frame {
            width: 1.2in;
            height: 1.4in;
            border: 1.5px solid #000;
            float: right;
            margin-left: 15px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .patient-photo-frame img { width: 100%; height: 100%; object-fit: cover; }
        .consent-paragraph {
            text-align: justify;
            font-size: 13px;
            line-height: 1.4;
            text-indent: 0.4in;
        }

        /* FOOTER */
        .signatures-container {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 50px;
            margin-bottom: 30px;
        }
        .sig-entry { 
            flex: 1; 
            text-align: center; 
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .signature-wrapper {
            position: relative;
            height: 85px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
        }
        .sig-image {
            max-height: 80px; 
            width: auto;
            position: absolute;
            bottom: 8px; /* Tighter to the name */
            z-index: 1;
        }
        .user-name-text {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
            position: relative;
        }
        .sig-line {
            border-top: 1.5px solid #000;
            width: 100%;
            padding-top: 3px;
        }
        .sig-label { 
            font-size: 10px; 
            font-weight: normal; 
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* CUTTING GUIDE */
        .cutting-guide {
            margin-top: 40px;
            border-top: 1px dashed #999;
            position: relative;
            width: 100%;
            height: 1px;
        }
        .cutting-guide::after {
            content: "\f0c4"; /* FontAwesome Scissor Icon */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: -10px;
            right: 10px;
            background: white;
            padding: 0 5px;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="no-print-nav">
        <a href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Form
        </button>
    </div>

    <div class="printable-sheet">
        <div class="form-header">
            <div class="logo-box"><img src="images/bcd.jpg" alt="CHO Logo"></div>
            <div class="center-titles">
                <p>Republic of the Philippines</p>
                <h1>City Health Office</h1>
                <p>Bacolod City</p>
                <div class="main-label">PATIENT CONSENT FORM</div>
            </div>
            <div class="services-list">
                <?php
                $selected = array_map('trim', explode(',', $form['service_type']));
                $options = ['ABTC', 'PEDIA CONSULTATION', 'ADULT CONSULTATION', 'DENTAL', 'SOCIAL HYGIENE', 'LABORATORY', 'X-RAY/ULTRASOUND', 'TB SECTION', 'PRENATAL'];
                foreach ($options as $opt):
                    $checked = in_array($opt, array_map('strtoupper', $selected));
                ?>
                    <div class="service-row">
                        <input type="checkbox" <?php echo $checked ? 'checked' : ''; ?> disabled>
                        <span><?php echo $opt; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-content">
            <div class="patient-photo-frame">
                <?php if (!empty($form['patient_photo']) && file_exists($form['patient_photo'])): ?>
                    <img src="<?php echo $form['patient_photo']; ?>" alt="Patient">
                <?php else: ?>
                    <span style="font-size: 10px; color: #888;">PHOTO</span>
                <?php endif; ?>
            </div>
            <p class="consent-paragraph">
                By affixing my signature or that of my authorized representative/guardian, I agree to allow the Bacolod City Health Office to collect, record and store my personal & health information, including photographs or electronic images obtained in the course of providing primary health care services, risk assessment or screening, basic laboratory and imaging services and dispensing of medicines and vaccines, other services (<strong><?php echo htmlspecialchars($form['service_type']); ?></strong>) for the purpose of medical record keeping, as well as reimbursement, monitoring and evaluation by its partner agencies (i.e. Philhealth, DOH), in accordance with the Data Privacy Act and in compliance to Philhealth Circular 2024-0013 Annex F.
            </p>
        </div>

        <div class="signatures-container">
            <div class="sig-entry">
                <div class="signature-wrapper">
                    <?php if (!empty($form['signature_data']) && $form['signature_data'] !== 'Signed with ballpen' && file_exists($form['signature_data'])): ?>
                        <img src="<?php echo $form['signature_data']; ?>" class="sig-image">
                    <?php endif; ?>
                    <div class="user-name-text"><?php echo htmlspecialchars($form['patient_name']); ?></div>
                </div>
                <div class="sig-line"></div>
                <div class="sig-label">Full Name & Signature</div>
            </div>

            <div class="sig-entry">
                <div class="signature-wrapper">
                    <div class="user-name-text" style="text-transform: none;">
                        <?php echo date('F d, Y', strtotime($form['form_date'])); ?>
                    </div>
                </div>
                <div class="sig-line"></div>
                <div class="sig-label">Date</div>
            </div>
        </div>

        <div class="cutting-guide"></div>

    </div>
</body>
</html>