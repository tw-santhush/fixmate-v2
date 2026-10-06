<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$error   = '';
$success = '';
$pic_warning = '';

// Fetch user info
$sql = "SELECT u.*, d.district_name
        FROM users u
        LEFT JOIN districts d ON u.district_id = d.id
        WHERE u.id = '$user_id'";
$user = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$user) {
    header('Location: logout.php');
    exit();
}

// ============================================================
// HANDLE FORM SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = $_POST['name'];
    $phone       = $_POST['phone'];
    $district_id = $_POST['district_id'];
    $city        = $_POST['city'];

    // Update basic info
    $name_sql        = mysqli_real_escape_string($conn, $name);
    $phone_sql       = mysqli_real_escape_string($conn, $phone);
    $city_sql        = mysqli_real_escape_string($conn, $city);
    $district_id_int = (int)$district_id;
    $update = "UPDATE users SET 
                name = '$name_sql',
                phone = '$phone_sql',
                district_id = '$district_id_int',
                city = '$city_sql'
                WHERE id = '$user_id'";

    if (mysqli_query($conn, $update)) {
        $_SESSION['user_name'] = $name;

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
                    $new_name = 'user_' . $user_id . '_avatar_' . time() . '.' . $ext;
                    if (move_uploaded_file($pic['tmp_name'], $upload_dir . $new_name)) {
                        $pic_path = mysqli_real_escape_string($conn, 'uploads/avatars/' . $new_name);
                        mysqli_query($conn, "UPDATE users SET profile_picture = '$pic_path' WHERE id = '$user_id'");
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

        // Handle password change (optional)
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current != '' && $new != '' && $confirm != '') {
            if (!password_verify($current, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($new != $confirm) {
                $error = 'New passwords do not match.';
            } elseif (strlen($new) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = '$user_id'");
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
        $user = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    } else {
        $error = 'Error updating profile. Please try again.';
    }
}

// Stats for the side card
$reviews_written = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM ratings WHERE user_id = '$user_id'"))['c'];

$avg_row  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(rating) as a FROM ratings WHERE user_id = '$user_id'"));
$avg_given = $avg_row['a'] ? number_format($avg_row['a'], 1) : '—';

$first_name = explode(' ', $user['name'])[0];
?>
<?php include '../includes/header.php'; ?>

<section class="edit-page">
    <div class="container">

        <!-- ============ BREADCRUMB ============ -->
        <div class="edit-breadcrumb">
            <a href="user_profile.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to profile
            </a>
        </div>

        <!-- ============ 2-COLUMN LAYOUT ============ -->
        <div class="edit-grid">

            <!-- ============ LEFT: PROFILE CARD ============ -->
            <div class="edit-side">

                <div class="edit-card">

                    <!-- Corner notch -->
                    <span class="edit-card-corner">SL</span>

                    <!-- Avatar -->
                    <div class="edit-avatar-wrap">
                        <div class="edit-avatar">
                            <?php if (!empty($user['profile_picture'])) { ?>
                                <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="">
                            <?php } else { ?>
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            <?php } ?>
                        </div>
                        <span class="edit-avatar-edit">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                        </span>
                    </div>

                    <h2 class="edit-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="edit-meta">
                        Customer<?php echo $user['district_name'] ? ' · ' . $user['district_name'] : ''; ?>
                    </p>

                    <div class="edit-stats-mini">
                        <span>⭐ <?php echo $avg_given; ?></span>
                        <span class="dot"></span>
                        <span><?php echo $reviews_written; ?> reviews written</span>
                    </div>

                </div>

            </div>

            <!-- ============ RIGHT: EDIT FORM ============ -->
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
                            <?php if (!empty($user['profile_picture'])) { ?>
                                <span class="field-hint">Currently using: <?php echo htmlspecialchars(basename($user['profile_picture'])); ?> — leave blank to keep it</span>
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
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Registered Email 🔒</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <span class="field-hint">Cannot be changed for security</span>
                            </div>
                            <div class="form-group">
                                <label>Mobile Phone (Sri Lanka)</label>
                                <div class="input-wrap with-prefix">
                                    <span class="input-prefix">🇱🇰 +94</span>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="77 123 4567">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>District</label>
                                <select name="district_id" required>
                                    <option value="">Select District</option>
                                    <?php
                                    $districts = mysqli_query($conn, "SELECT id, district_name FROM districts ORDER BY district_name");
                                    while ($d = mysqli_fetch_assoc($districts)) {
                                        $sel = ($user['district_id'] == $d['id']) ? 'selected' : '';
                                        echo "<option value='{$d['id']}' $sel>{$d['district_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Local Town / Ward</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="e.g. Nawala, Rajagiriya">
                            </div>
                        </div>

                    </div>

                    <!-- SECTION 2: CHANGE PASSWORD -->
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

                    <!-- ACTIONS -->
                    <div class="edit-actions">
                        <a href="user_profile.php" class="btn btn-ghost">Cancel</a>
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

<?php include '../includes/footer.php'; ?>