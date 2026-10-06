<?php
include '../includes/config.php';

if (!isset($_SESSION['tech_id'])) {
    header('Location: login.php');
    exit();
}

$tech_id = $_SESSION['tech_id'];
$error   = '';
$success = '';
$pic_warning = '';

$sql = "SELECT t.*, s.service_name, d.district_name
        FROM technicians t
        JOIN services  s ON t.service_id  = s.id
        JOIN districts d ON t.district_id = d.id
        WHERE t.id = '$tech_id'";
$tech = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$tech) {
    header('Location: logout.php');
    exit();
}

// Safe defaults for optional columns
$tech_city    = $tech['city']         ?? '';
$tech_fee     = $tech['callout_fee']  ?? 'Rs. 2,000';
$tech_bio     = $tech['bio']          ?? '';
$tech_phone   = $tech['phone']        ?? '';
$tech_pic     = $tech['profile_picture'] ?? '';
$tech_status  = $tech['status']       ?? 'pending';

// ============================================================
// HANDLE FORM SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $phone       = mysqli_real_escape_string($conn, $_POST['phone']);
    $district_id = mysqli_real_escape_string($conn, $_POST['district_id']);
    $city        = mysqli_real_escape_string($conn, $_POST['city']);
    $service_id  = mysqli_real_escape_string($conn, $_POST['service_id']);
    $experience  = mysqli_real_escape_string($conn, $_POST['experience']);
    if (($_POST['callout_fee_select'] ?? '') === '__custom__') {
        $fee = mysqli_real_escape_string($conn, $_POST['callout_fee_custom']);
    } else {
        $fee = mysqli_real_escape_string($conn, $_POST['callout_fee_select']);
    }
    $bio         = mysqli_real_escape_string($conn, $_POST['bio']);

    $update = "UPDATE technicians SET 
                name = '$name',
                phone = '$phone',
                district_id = '$district_id',
                city = '$city',
                service_id = '$service_id',
                experience = '$experience',
                callout_fee = '$fee',
                bio = '$bio'
                WHERE id = '$tech_id'";

    if (mysqli_query($conn, $update)) {
        $_SESSION['tech_name'] = $name;

        // Handle profile picture upload (optional)
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $pic = $_FILES['profile_picture'];
            if ($pic['size'] > 2 * 1024 * 1024) {
                $pic_warning = 'file exceeds the 2 MB limit.';
            } else {
                $mime = mime_content_type($pic['tmp_name']);
                $ext  = strtolower(pathinfo($pic['name'], PATHINFO_EXTENSION));
                if (in_array($mime, ['image/jpeg', 'image/png']) && in_array($ext, ['jpg','jpeg','png'])) {
                    $upload_dir = '../uploads/avatars/';
                    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0755, true); }
                    $new_name = 'tech_' . $tech_id . '_avatar_' . time() . '.' . $ext;
                    if (move_uploaded_file($pic['tmp_name'], $upload_dir . $new_name)) {
                        $pic_path = mysqli_real_escape_string($conn, 'uploads/avatars/' . $new_name);
                        mysqli_query($conn, "UPDATE technicians SET profile_picture = '$pic_path' WHERE id = '$tech_id'");
                    } else {
                        $pic_warning = 'upload failed. Please try again.';
                    }
                } else {
                    $pic_warning = 'only JPG and PNG files are allowed.';
                }
            }
        } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] != 0 && $_FILES['profile_picture']['error'] != 4) {
            $pic_warning = 'an upload error occurred.';
        }

        // Password change (optional)
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current != '' && $new != '' && $confirm != '') {
            if (!password_verify($current, $tech['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($new != $confirm) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE technicians SET password = '$hashed' WHERE id = '$tech_id'");
                $success = 'Profile and password updated successfully!';
            }
        } else {
            $success = 'Profile updated successfully!';
        }

        if (!empty($pic_warning)) {
            // Merge messages: turn the success into a combined warning
            $success = '';
            $pic_warning = 'Profile saved, but the picture was not updated: ' . $pic_warning;
            $pic_warning = trim($pic_warning);
        }

        // Re-fetch fresh data
        $tech = mysqli_fetch_assoc(mysqli_query($conn, $sql));
        $tech_city   = $tech['city']        ?? '';
        $tech_fee    = $tech['callout_fee'] ?? 'Rs. 2,000';
        $tech_bio    = $tech['bio']         ?? '';
        $tech_status = $tech['status']      ?? 'pending';
    } else {
        $error = 'Error updating profile. Please try again.';
    }
}

$total_reviews = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM ratings WHERE technician_id = '$tech_id'"))['c'];
?>
<?php include '../includes/header.php'; ?>

<section class="edit-page">
    <div class="container">

        <div class="edit-breadcrumb">
            <a href="dashboard.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to dashboard
            </a>
        </div>

        <div class="edit-grid">

            <!-- LEFT: Profile card -->
            <div class="edit-side">

                <div class="edit-card">
                    <span class="edit-card-corner">SL</span>

                    <div class="edit-avatar-wrap">
                        <div class="edit-avatar">
                            <?php if (!empty($tech_pic)) { ?>
                                <img src="../<?php echo htmlspecialchars($tech_pic); ?>" alt="">
                            <?php } else { ?>
                                <?php echo strtoupper(substr($tech['name'], 0, 1)); ?>
                            <?php } ?>
                        </div>
                        <span class="edit-avatar-edit">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                        </span>
                    </div>

                    <h2 class="edit-name"><?php echo htmlspecialchars($tech['name']); ?></h2>
                    <p class="edit-meta">
                        <?php echo htmlspecialchars($tech['service_name']); ?><?php echo htmlspecialchars($tech['district_name'] ? ' · ' . $tech['district_name'] : ''); ?>
                    </p>

                    <div class="edit-stats-mini">
                        <span>⭐ <?php echo number_format($tech['rating'], 1); ?></span>
                        <span class="dot"></span>
                        <span><?php echo $tech['jobs_completed']; ?> jobs done</span>
                    </div>

                    <?php if ($tech_status == 'verified') { ?>
                        <div class="edit-verify-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            Verified
                        </div>
                    <?php } else { ?>
                        <div class="edit-verify-badge pending">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>
                            Pending Verification
                        </div>
                    <?php } ?>

                    <div class="edit-craft-register">
                        <div class="edit-craft-head">
                            <span>Trade Details</span>
                        </div>
                        <div class="edit-craft-grid">
                            <div>
                                <span>MAIN TRADE</span>
                                <strong><?php echo htmlspecialchars($tech['service_name']); ?></strong>
                            </div>
                            <div>
                                <span>EXPERIENCE</span>
                                <strong><?php echo $tech['experience']; ?> years</strong>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- RIGHT: Edit form -->
            <div class="edit-main">

                <?php if ($error) { ?>
                    <div class="auth-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <div class="auth-success">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <path d="M22 4 12 14.01l-3-3"/>
                        </svg>
                        <?php echo $success; ?>
                    </div>
                <?php } ?>

                <?php if ($pic_warning) { ?>
                    <div class="auth-error" style="background-color: #FFF3D6; color: #8A5C00; border-left-color: #FFB020;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v4M12 17h.01"/>
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                        <?php echo htmlspecialchars($pic_warning); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="edit-form-card" enctype="multipart/form-data">

                    <div class="edit-section">
                        <div class="edit-section-head">
                            <span class="edit-section-mark"></span>
                            <h3>PROFILE PICTURE</h3>
                            <span class="edit-section-note">Optional</span>
                        </div>
                        <div class="form-group">
                            <div class="label-row">
                                <label for="profile_picture">Upload new picture</label>
                                <span class="label-note">JPG / PNG · MAX 2 MB</span>
                            </div>
                            <div class="file-input-wrap">
                                <input type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png">
                            </div>
                            <?php if (!empty($tech_pic)) { ?>
                                <span class="field-hint">Currently using: <?php echo htmlspecialchars(basename($tech_pic)); ?> — leave blank to keep it</span>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- SECTION 1: PERSONAL INFORMATION -->
                    <div class="edit-section">
                        <div class="edit-section-head">
                            <span class="edit-section-mark"></span>
                            <h3>PERSONAL INFORMATION</h3>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <label>Full Legal Name</label>
                                </div>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($tech['name']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Registered Email 🔒</label>
                                <input type="email" value="<?php echo htmlspecialchars($tech['email']); ?>" disabled>
                                <span class="field-hint">Cannot be changed for security</span>
                            </div>
                            <div class="form-group">
                                <label>Mobile Phone (Sri Lanka)</label>
                                <div class="input-wrap with-prefix">
                                    <span class="input-prefix">🇱🇰 +94</span>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($tech_phone); ?>" placeholder="77 123 4567">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>District</label>
                                <select name="district_id" required>
                                    <?php
                                    $districts = mysqli_query($conn, "SELECT id, district_name FROM districts ORDER BY district_name");
                                    while ($d = mysqli_fetch_assoc($districts)) {
                                        $sel = ($tech['district_id'] == $d['id']) ? 'selected' : '';
                                        echo "<option value='{$d['id']}' $sel>" . htmlspecialchars($d['district_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Local Town / Ward</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($tech_city); ?>" placeholder="e.g. Nawala, Rajagiriya">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: ARTISAN CREDENTIALS -->
                    <div class="edit-section highlight">
                        <div class="edit-section-head">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--teal);">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                            <h3>ARTISAN CREDENTIALS</h3>
                            <span class="pill pill-teal" style="margin-left:auto;">PUBLIC LISTING</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Primary Trade Specialization</label>
                                <select name="service_id" required>
                                    <?php
                                    $services = mysqli_query($conn, "SELECT id, service_name FROM services ORDER BY service_name");
                                    while ($s = mysqli_fetch_assoc($services)) {
                                        $sel = ($tech['service_id'] == $s['id']) ? 'selected' : '';
                                        echo "<option value='{$s['id']}' $sel>" . htmlspecialchars($s['service_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-row-2">
                                <div class="form-group">
                                    <label>Experience (years)</label>
                                    <input type="number" name="experience" value="<?php echo (int) $tech['experience']; ?>" min="0" max="70" required>
                                </div>
                                <div class="form-group">
                                    <label>Call-out Fee</label>
                                    <select name="callout_fee_select" id="callout_fee_select" onchange="toggleCustomFee()" required>
                                        <option value="Negotiable" <?php echo ($tech_fee == 'Negotiable') ? 'selected' : ''; ?>>Negotiable — discuss with customer</option>
                                        <option value="Free Estimate" <?php echo ($tech_fee == 'Free Estimate') ? 'selected' : ''; ?>>Free Estimate</option>
                                        <option value="Rs. 500" <?php echo ($tech_fee == 'Rs. 500') ? 'selected' : ''; ?>>Rs. 500</option>
                                        <option value="Rs. 1,000" <?php echo ($tech_fee == 'Rs. 1,000') ? 'selected' : ''; ?>>Rs. 1,000</option>
                                        <option value="Rs. 1,500" <?php echo ($tech_fee == 'Rs. 1,500') ? 'selected' : ''; ?>>Rs. 1,500</option>
                                        <option value="Rs. 2,000" <?php echo ($tech_fee == 'Rs. 2,000') ? 'selected' : ''; ?>>Rs. 2,000 (default)</option>
                                        <option value="Rs. 2,500" <?php echo ($tech_fee == 'Rs. 2,500') ? 'selected' : ''; ?>>Rs. 2,500</option>
                                        <option value="Rs. 3,000" <?php echo ($tech_fee == 'Rs. 3,000') ? 'selected' : ''; ?>>Rs. 3,000</option>
                                        <option value="Rs. 5,000" <?php echo ($tech_fee == 'Rs. 5,000') ? 'selected' : ''; ?>>Rs. 5,000</option>
                                        <option value="__custom__" <?php
                                            $presets = ['Negotiable','Free Estimate','Rs. 500','Rs. 1,000','Rs. 1,500','Rs. 2,000','Rs. 2,500','Rs. 3,000','Rs. 5,000'];
                                            echo (!in_array($tech_fee, $presets) && !empty($tech_fee)) ? 'selected' : '';
                                        ?>>Custom amount...</option>
                                    </select>

                                    <input type="text" name="callout_fee_custom" id="callout_fee_custom"
                                           value="<?php echo (!in_array($tech_fee, $presets) && !empty($tech_fee)) ? htmlspecialchars($tech_fee) : ''; ?>"
                                           placeholder="e.g. Rs. 1,750"
                                           style="margin-top: 10px; display: <?php echo (!in_array($tech_fee, $presets) && !empty($tech_fee)) ? 'block' : 'none'; ?>;">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <label>About you</label>
                                </div>
                            <textarea name="bio" rows="3" maxlength="300" placeholder="Specializing in residential wiring, safety checks..."><?php echo htmlspecialchars($tech_bio); ?></textarea>
                        </div>
                    </div>

                    <!-- SECTION 3: CHANGE PASSWORD -->
                    <div class="edit-section">
                        <div class="edit-section-head">
                            <span class="edit-section-mark grey"></span>
                            <h3>CHANGE PASSWORD (OPTIONAL)</h3>
                        </div>

                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="input-wrap">
                                <input type="password" name="current_password" id="current-pwd" placeholder="••••••••••••">
                                <button type="button" class="input-eye" onclick="togglePass('current-pwd')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="input-wrap">
                                    <input type="password" name="new_password" id="new-pwd" placeholder="Min. 8 characters">
                                    <button type="button" class="input-eye" onclick="togglePass('new-pwd')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="input-wrap">
                                    <input type="password" name="confirm_password" id="confirm-pwd" placeholder="Re-type new password">
                                    <button type="button" class="input-eye" onclick="togglePass('confirm-pwd')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="edit-hint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2"/>
                                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                            </svg>
                            Leave blank to keep current password unchanged.
                        </p>
                    </div>

                    <div class="edit-actions">
                        <a href="dashboard.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-amber">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</section>

<script>
function togglePass(id) {
    var input = document.getElementById(id);
    input.type = (input.type === 'password') ? 'text' : 'password';
}
</script>

<script>
function toggleCustomFee() {
    var sel = document.getElementById('callout_fee_select');
    var custom = document.getElementById('callout_fee_custom');
    if (sel.value === '__custom__') {
        custom.style.display = 'block';
        custom.required = true;
    } else {
        custom.style.display = 'none';
        custom.required = false;
    }
}
</script>

<?php include '../includes/footer.php'; ?>