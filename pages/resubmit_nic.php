<?php
include '../includes/config.php';

// Must be logged in as technician
if (!isset($_SESSION['tech_id'])) {
    header('Location: login.php');
    exit();
}

$tech_id = $_SESSION['tech_id'];
$error   = '';        // ← initialize BEFORE anything uses it
$success = '';

// Fetch this technician
$sql  = "SELECT * FROM technicians WHERE id = '$tech_id'";
$tech = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$tech) {
    header('Location: logout.php');
    exit();
}

// Only rejected techs should reach this page
if ($tech['status'] != 'rejected') {
    header('Location: dashboard.php');
    exit();
}

// ============================================================
// HANDLE FILE UPLOAD HELPER
// ============================================================
function handle_nic_resubmit($file_key, $tech_id, $side) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != 0) {
        return null;
    }
    $file = $_FILES[$file_key];

    if ($file['size'] > 2 * 1024 * 1024) return null;

    $mime = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($mime, $allowed)) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) return null;

    $upload_dir = '../uploads/nic/';
    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) return null;
    }

    $new_name = $tech_id . '_nic_' . $side . '_resubmit_' . time() . '.' . $ext;
    $target   = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return 'uploads/nic/' . $new_name;
    }
    return null;
}

// ============================================================
// HANDLE POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Both files required
    if (!isset($_FILES['nic_front']) || $_FILES['nic_front']['error'] != 0
     || !isset($_FILES['nic_back'])  || $_FILES['nic_back']['error']  != 0) {
        $error = 'Please upload both NIC images.';
    } else {
        $front_path = handle_nic_resubmit('nic_front', $tech_id, 'front');
        $back_path  = handle_nic_resubmit('nic_back',  $tech_id, 'back');

        if ($front_path === null || $back_path === null) {
            $error = 'Error uploading NIC images. Please check the file type (JPG / PNG / PDF) and size (max 2 MB).';
        } else {
            $update = "UPDATE technicians 
                       SET nic_front = '$front_path',
                           nic_back  = '$back_path',
                           status    = 'pending',
                           rejection_reason = NULL
                       WHERE id = '$tech_id'";

            if (mysqli_query($conn, $update)) {
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Error saving your documents. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="auth-section">
    <div class="auth-container auth-large">
        <div class="auth-box">

            <h1>Re-submit NIC Images</h1>
            <p>Upload clearer photos of your National Identity Card so the admin team can verify your account.</p>

            <?php if (!empty($tech['rejection_reason'])): ?>
                <div class="auth-error" style="margin-top: 16px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <span><strong>Rejection reason:</strong> <?php echo htmlspecialchars($tech['rejection_reason']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="auth-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="auth-form" style="margin-top: 20px;">

                <div class="form-group">
                    <div class="label-row">
                        <label for="nic_front">NIC Front Image</label>
                        <span class="label-note">JPG / PNG / PDF · MAX 2 MB</span>
                    </div>
                    <div class="file-input-wrap">
                        <input type="file" name="nic_front" id="nic_front" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="nic_back">NIC Back Image</label>
                        <span class="label-note">JPG / PNG / PDF · MAX 2 MB</span>
                    </div>
                    <div class="file-input-wrap">
                        <input type="file" name="nic_back" id="nic_back" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                </div>

                <div class="button-row" style="margin-top: 24px;">
                    <a href="dashboard.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="auth-button">
                        Re-submit for Review
                    </button>
                </div>

            </form>

            <p class="auth-footer" style="font-size: 12.5px; color: var(--ink-muted); margin-top: 20px;">
                Your documents will be reviewed within 24 hours. You'll see the update on your dashboard.
            </p>

        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>