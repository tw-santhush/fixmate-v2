<?php
include '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']))    { header('Location: home.php');         exit(); }
if (isset($_SESSION['tech_id']))    { header('Location: dashboard.php');     exit(); }
if (isset($_SESSION['admin_id']))   { header('Location: admin_panel.php');   exit(); }

$error       = '';
$success     = '';
$active_view = $_GET['view'] ?? 'login';   // login | customer | pro

// ============================================================
// 1. SIGN IN — auto-detects user type by email
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'login') {
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $email_sql = mysqli_real_escape_string($conn, $email);

    // Check admins first
    $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, password FROM admins WHERE email = '$email_sql'"));
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: admin_panel.php');
        exit();
    }

    // Check technicians
    $tech = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, password FROM technicians WHERE email = '$email_sql'"));
    if ($tech && password_verify($password, $tech['password'])) {
        $_SESSION['tech_id']   = $tech['id'];
        $_SESSION['tech_name'] = $tech['name'];
        header('Location: dashboard.php');
        exit();
    }

    // Check normal users
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, password FROM users WHERE email = '$email_sql'"));
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: home.php');
        exit();
    }

    $error = 'Invalid email or password.';
    $active_view = 'login';
}

// ============================================================
// 2. CUSTOMER SIGNUP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'customer') {
    $name        = $_POST['name'];
    $email       = $_POST['email'];
    $district_id = $_POST['district_id'] ?? '';
    $city        = $_POST['city'] ?? '';
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];
    $name_sql        = mysqli_real_escape_string($conn, $name);
    $email_sql       = mysqli_real_escape_string($conn, $email);
    $district_id_int = (int)$district_id;

    if (empty($district_id) || empty($city)) {
        $error = 'Please select your city / town.';
    } elseif ($password != $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_sql'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'That email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $city_escaped = mysqli_real_escape_string($conn, $city);
            $insert = "INSERT INTO users (name, email, password, district_id, city)
                       VALUES ('$name_sql', '$email_sql', '$hashed', '$district_id_int', '$city_escaped')";
            if (mysqli_query($conn, $insert)) {
                $_SESSION['user_id']   = mysqli_insert_id($conn);
                $_SESSION['user_name'] = $name;
                header('Location: home.php');
                exit();
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    }
    $active_view = 'customer';
}

// ============================================================
// 3. SERVICE PRO SIGNUP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'pro') {
    $name         = $_POST['name'];
    $phone_input  = $_POST['phone'];   // user types "77 123 4567"
    $phone        = '94' . preg_replace('/[^0-9]/', '', $phone_input);  // -> 94771234567
    $service_id   = $_POST['service_id'];
    $district_id  = $_POST['district_id'] ?? '';
    $city         = $_POST['city'] ?? '';
    $experience   = $_POST['experience'];
    $email        = $_POST['email'];
    $password     = $_POST['password'];
    $confirm      = $_POST['confirm_password'];
    $nic_consent  = isset($_POST['nic_consent']);
    $name_sql        = mysqli_real_escape_string($conn, $name);
    $email_sql       = mysqli_real_escape_string($conn, $email);
    $phone_sql       = mysqli_real_escape_string($conn, $phone);
    $service_id_int  = (int)$service_id;
    $district_id_int = (int)$district_id;
    $experience_int  = (int)$experience;

    // File inputs are available in $_FILES['nic_front'] and $_FILES['nic_back']

    // Helper function to handle NIC uploads
    function handle_nic_upload($file_key, $tech_id, $side) {
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

        $new_name = $tech_id . '_nic_' . $side . '_' . time() . '.' . $ext;
        $target = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return 'uploads/nic/' . $new_name;
        }
        return null;
    }

    if (empty($district_id) || empty($city)) {
        $error = 'Please select your primary work city / town.';
    } elseif (!$nic_consent) {
        $error = 'You must agree to the identity verification terms.';
    } elseif ($password != $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($_FILES['nic_front']['error'] != 0 || $_FILES['nic_back']['error'] != 0) {
        $error = 'Please upload both NIC images.';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM technicians WHERE email = '$email_sql'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'That email is already registered.';
        } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $city_escaped = mysqli_real_escape_string($conn, $city);
                $sql_cols = "name, email, phone, district_id, city, service_id, experience, password";
                $sql_vals = "'$name_sql', '$email_sql', '$phone_sql', '$district_id_int', '$city_escaped', '$service_id_int', '$experience_int', '$hashed'";
                $insert   = "INSERT INTO technicians ($sql_cols) VALUES ($sql_vals)";
                if (mysqli_query($conn, $insert)) {
                    $new_tech_id = mysqli_insert_id($conn);

                    // Handle NIC uploads
                    $nic_front_path = handle_nic_upload('nic_front', $new_tech_id, 'front');
                    $nic_back_path  = handle_nic_upload('nic_back',  $new_tech_id, 'back');

                     if ($nic_front_path === null || $nic_back_path === null) {
                         // Delete the technician we just created
                         mysqli_query($conn, "DELETE FROM technicians WHERE id = '" . (int)$new_tech_id . "'");
                         $error = 'Error uploading NIC images. Please try again.';
                     } else {
                        // Update the technician record with the file paths
                        $nic_front_sql = mysqli_real_escape_string($conn, $nic_front_path);
                        $nic_back_sql  = mysqli_real_escape_string($conn, $nic_back_path);
                        $update = "UPDATE technicians SET nic_front = '$nic_front_sql', nic_back = '$nic_back_sql' WHERE id = '" . (int)$new_tech_id . "'";
                        if (mysqli_query($conn, $update)) {
                            $_SESSION['tech_id']   = $new_tech_id;
                            $_SESSION['tech_name'] = $name;
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            // If update fails, delete the technician and show error
                            mysqli_query($conn, "DELETE FROM technicians WHERE id = '" . (int)$new_tech_id . "'");
                            $error = 'Error saving NIC information. Please try again.';
                        }
                    }
                } else {
                    $error = 'Error creating account. Please try again.';
                }
        }
    }
    $active_view = 'pro';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — FixMate</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- ============ TOP BAR ============ -->
        <div class="auth-topbar">
            <a href="home.php" class="auth-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Home
            </a>
            <a href="home.php" class="logo" style="padding: 0;">
                <span class="logo-mark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </span>
                <span class="logo-text">
                    <span class="name">FixMate</span>
                </span>
            </a>
        </div>

        <!-- ============ SPLIT BODY ============ -->
        <div class="auth-body">

            <!-- ===== LEFT: Promo panel ===== -->
            <div class="auth-promo">

                <!-- Tab switcher -->
                <div class="auth-tabs">
                    <button type="button" class="auth-tab <?php echo ($active_view == 'login')    ? 'active' : ''; ?>" onclick="switchView('login')">1. Sign In</button>
                    <button type="button" class="auth-tab <?php echo ($active_view == 'customer') ? 'active' : ''; ?>" onclick="switchView('customer')">2. Customer</button>
                    <button type="button" class="auth-tab <?php echo ($active_view == 'pro')      ? 'active' : ''; ?>" onclick="switchView('pro')">3. Service Pro</button>
                </div>

                <!-- Dynamic promo content -->
                <div class="auth-promo-content">
                    <h1 id="promo-heading">
                        <?php if ($active_view == 'customer') { ?>
                            One login.<br><span class="amber">Every household fix.</span>
                        <?php } elseif ($active_view == 'pro') { ?>
                            Sri Lanka's trades<br><span class="amber">directory.</span>
                        <?php } else { ?>
                            Got a skill?<br><span class="amber">Get found.</span>
                        <?php } ?>
                    </h1>

                    <p class="auth-promo-body" id="promo-body">
                        <?php if ($active_view == 'customer') { ?>
                            Direct access to verified technicians across Colombo, Kandy, Galle, and all 25 administrative districts.
                        <?php } elseif ($active_view == 'pro') { ?>
                            Build an authenticated digital track record backed by your National Identity Card and direct customer reviews.
                        <?php } else { ?>
                            Register your trade, get verified, and start receiving direct job requests from customers.
                        <?php } ?>
                    </p>

                    <!-- CTA changes by view -->
                    <div class="auth-promo-action" id="promo-action">
                        <?php if ($active_view == 'customer' || $active_view == 'pro') { ?>
                            <button type="button" class="btn btn-white" onclick="switchView('login')">
                                Have an account? Sign in
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-amber" onclick="switchView('pro')">
                                Register as a Pro
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </button>
                        <?php } ?>
                    </div>

                    <!-- Islandwide network card -->
                    <div class="auth-promo-card">
                        <div class="auth-promo-card-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="auth-promo-card-label">Islandwide Network</div>
                            <div class="auth-promo-card-text">Colombo • Kandy • Galle • Gampaha</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===== RIGHT: Forms ===== -->
            <div class="auth-forms">

                <?php if ($error) { ?>
                    <div class="auth-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <!-- ===== VIEW 1: SIGN IN ===== -->
                <div class="auth-view <?php echo ($active_view == 'login') ? 'active' : ''; ?>" id="view-login">
                    <h2>Welcome Back</h2>
                    <p class="auth-view-sub">Sign in to your FixMate account.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-wrap">
                                <span class="input-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                                        <path d="m2 6 10 7 10-7"/>
                                    </svg>
                                </span>
                                <input type="email" name="email" placeholder="name@example.lk" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="label-row">
                                <label>Password</label>
                                <a href="#" class="mini-link">Forgot password?</a>
                            </div>
                            <div class="input-wrap">
                                <span class="input-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="11" width="16" height="10" rx="2"/>
                                        <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                                    </svg>
                                </span>
                                <input type="password" name="password" id="login-pass" placeholder="••••••••" required>
                                <button type="button" class="input-eye" onclick="togglePass('login-pass', this)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <label class="checkbox-row">
                            <input type="checkbox" checked>
                            <span>Stay signed in on this device</span>
                        </label>

                        <button type="submit" class="btn btn-amber btn-block">
                            Sign in
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </form>

                    <p class="auth-switch">
                        <a href="#" onclick="switchView('customer'); return false;">Create a customer account</a>
                    </p>

                    <div class="auth-info-box">
                        <div class="auth-info-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Are you a skilled tradesman?</strong>
                            <p>Register your service free of charge with zero monthly commissions. <a href="#" onclick="switchView('pro'); return false;">Start here →</a></p>
                        </div>
                    </div>
                </div>

                <!-- ===== VIEW 2: CUSTOMER SIGNUP ===== -->
                <div class="auth-view <?php echo ($active_view == 'customer') ? 'active' : ''; ?>" id="view-customer">
                    <h2>Create Customer Account</h2>
                    <p class="auth-view-sub">Connect directly with vetted local electricians, plumbers, and technicians.</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="customer">

                        <div class="form-group">
                            <label>Full Legal Name</label>
                            <input type="text" name="name" placeholder="e.g. Ruwan Perera" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="ruwan@gmail.com" required>
                            </div>
                            <div class="form-group">
                                <label>Home City</label>
                                <div class="find-select-wrap find-city-wrap" id="cusCityWrap">
                                    <input type="hidden" name="district_id" id="cusDistrictId" value="">
                                    <input type="hidden" name="city" id="cusCityName" value="">

                                    <button type="button" class="find-city-pill" id="cusCityPill" onclick="toggleCusCity(event)">
                                        <svg class="find-select-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <span id="cusCityLabel">Select your city / town</span>
                                        <svg class="find-city-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>

                                    <div class="find-city-dropdown" id="cusCityDropdown">
                                        <div class="search-dropdown-input">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"/>
                                                <path d="m21 21-4.35-4.35"/>
                                            </svg>
                                            <input type="text" id="cusCitySearch" placeholder="Search city..." oninput="filterCusCities()" autocomplete="off">
                                        </div>

                                        <div class="search-dropdown-list" id="cusCityList">
                                            <?php
                                            $cus_cities = mysqli_query($conn,
                                                "SELECT c.name, c.district_id, d.district_name
                                                 FROM cities c
                                                 JOIN districts d ON c.district_id = d.id
                                                 ORDER BY d.district_name ASC, c.name ASC");
                                            $cus_current_d = '';
                                            while ($c = mysqli_fetch_assoc($cus_cities)) {
                                                if ($c['district_name'] !== $cus_current_d) {
                                                    $cus_current_d = $c['district_name'];
                                                    echo '<div class="search-dropdown-group">' . htmlspecialchars($cus_current_d) . '</div>';
                                                }
                                                echo '<button type="button" class="search-dropdown-item" '
                                                   . 'data-name="' . htmlspecialchars($c['name']) . '" '
                                                   . 'onclick="pickCusCity(' . (int)$c['district_id'] . ', \'' . addslashes($c['name']) . '\')">'
                                                   . htmlspecialchars($c['name'])
                                                   . '</button>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" placeholder="At least 8 chars" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>

                        <label class="checkbox-row">
                            <input type="checkbox" required>
                            <span>I agree to the FixMate <a href="#" class="inline-link">Terms of Service</a> &amp; verified privacy guidelines.</span>
                        </label>

                        <button type="submit" class="btn btn-teal btn-block">
                            Create customer account
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </form>

                    <p class="auth-switch">
                        Already have an account? <a href="#" onclick="switchView('login'); return false;">Sign in</a>
                    </p>
                </div>

                <!-- ===== VIEW 3: SERVICE PRO SIGNUP ===== -->
                <div class="auth-view <?php echo ($active_view == 'pro') ? 'active' : ''; ?>" id="view-pro">
                    <h2>Register as a Service Pro</h2>
                    <p class="auth-view-sub">List your trade, receive direct calls from homeowners, and build verified reputation.</p>

                     <form method="POST" enctype="multipart/form-data">
                         <input type="hidden" name="action" value="pro">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name (as in NIC)</label>
                                <input type="text" name="name" placeholder="e.g. Sunil Shantha Jayawardena" required>
                            </div>
                            <div class="form-group">
                                <label>Mobile Phone (Direct calls)</label>
                                <div class="input-wrap">
                                    <span class="input-prefix">+94</span>
                                    <input type="tel" name="phone" placeholder="77 123 4567" required class="input-with-prefix">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Primary Trade</label>
                                <select name="service_id" required>
                                    <option value="">Select Primary Skill</option>
                                    <option value="1">Plumber</option>
                                    <option value="2">Electrician</option>
                                    <option value="3">Carpenter</option>
                                    <option value="4">Mechanic</option>
                                    <option value="5">Painter</option>
                                    <option value="6">Welder</option>
                                    <option value="7">Mason</option>
                                    <option value="8">Locksmith</option>
                                    <option value="9">Appliance Repair</option>
                                    <option value="10">AC Technician</option>
                                    <option value="11">Phone Repair</option>
                                    <option value="12">Computer Tech</option>
                                    <option value="13">Plumbing &amp; Gas</option>
                                    <option value="14">Electrical &amp; Solar</option>
                                    <option value="15">General Repairs</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Primary Work City</label>
                                <div class="find-select-wrap find-city-wrap" id="proCityWrap">
                                    <input type="hidden" name="district_id" id="proDistrictId" value="">
                                    <input type="hidden" name="city" id="proCityName" value="">

                                    <button type="button" class="find-city-pill" id="proCityPill" onclick="toggleProCity(event)">
                                        <svg class="find-select-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <span id="proCityLabel">Select your city / town</span>
                                        <svg class="find-city-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>

                                    <div class="find-city-dropdown" id="proCityDropdown">
                                        <div class="search-dropdown-input">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="11" cy="11" r="8"/>
                                                <path d="m21 21-4.35-4.35"/>
                                            </svg>
                                            <input type="text" id="proCitySearch" placeholder="Search city..." oninput="filterProCities()" autocomplete="off">
                                        </div>

                                        <div class="search-dropdown-list" id="proCityList">
                                            <?php
                                            $pro_cities = mysqli_query($conn,
                                                "SELECT c.name, c.district_id, d.district_name
                                                 FROM cities c
                                                 JOIN districts d ON c.district_id = d.id
                                                 ORDER BY d.district_name ASC, c.name ASC");
                                            $pro_current_d = '';
                                            while ($c = mysqli_fetch_assoc($pro_cities)) {
                                                if ($c['district_name'] !== $pro_current_d) {
                                                    $pro_current_d = $c['district_name'];
                                                    echo '<div class="search-dropdown-group">' . htmlspecialchars($pro_current_d) . '</div>';
                                                }
                                                echo '<button type="button" class="search-dropdown-item" '
                                                   . 'data-name="' . htmlspecialchars($c['name']) . '" '
                                                   . 'onclick="pickProCity(' . (int)$c['district_id'] . ', \'' . addslashes($c['name']) . '\')">'
                                                   . htmlspecialchars($c['name'])
                                                   . '</button>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Experience Level</label>
                                <select name="experience" required>
                                    <option value="">Select Years in Trade</option>
                                    <option value="2">1 to 3 Years (Apprentice)</option>
                                    <option value="5">3 to 7 Years (Journeyman)</option>
                                    <option value="10">8+ Years (Master Craftsman)</option>
                                    <option value="18">15+ Years (Veteran / Lead)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="sunil.works@gmail.com" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Create Password</label>
                                <input type="password" name="password" placeholder="At least 8 chars" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                         </div>

    <div class="form-group">
        <div class="label-row">
            <label for="nic_front">NIC Front Image</label>
            <span class="label-note">JPG / PNG / PDF · Max 2MB</span>
        </div>
        <div class="file-input-wrap">
            <input type="file" name="nic_front" id="nic_front" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
    </div>

    <div class="form-group">
        <div class="label-row">
            <label for="nic_back">NIC Back Image</label>
            <span class="label-note">JPG / PNG / PDF · Max 2MB</span>
        </div>
        <div class="file-input-wrap">
            <input type="file" name="nic_back" id="nic_back" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
    </div>

                         <div class="auth-consent">
                            <input type="checkbox" name="nic_consent" id="nic-consent" required>
                            <label for="nic-consent">
                                I agree to upload a clear photo of my NIC so my account can be verified.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-amber btn-block">
                            Register as Service Pro
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </form>

                    <p class="auth-switch">
                        <a href="#" onclick="switchView('login'); return false;">Sign in to your dashboard</a>
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// Swap between the three views and update the left promo panel
function switchView(view) {
    // Hide all views, show the selected one
    document.querySelectorAll('.auth-view').forEach(function (v) { v.classList.remove('active'); });
    document.getElementById('view-' + view).classList.add('active');

    // Toggle active class on tab buttons
    document.querySelectorAll('.auth-tab').forEach(function (t) { t.classList.remove('active'); });
    event.currentTarget.classList.add('active');

    // Update left panel content
    var heading = document.getElementById('promo-heading');
    var body = document.getElementById('promo-body');
    var action = document.getElementById('promo-action');

    if (view === 'login') {
        heading.innerHTML = 'Got a skill?<br><span class="amber">Get found.</span>';
        body.textContent = 'Register your trade, get verified, and start receiving direct job requests from customers.';
        action.innerHTML = '<button type="button" class="btn btn-amber" onclick="switchView(\'pro\')">Register as a Pro →</button>';
    } else if (view === 'customer') {
        heading.innerHTML = 'One login.<br><span class="amber">Every household fix.</span>';
        body.textContent = 'Direct access to verified technicians across Colombo, Kandy, Galle, and all 25 administrative districts.';
        action.innerHTML = '<button type="button" class="btn btn-white" onclick="switchView(\'login\')">Have an account? Sign in →</button>';
    } else if (view === 'pro') {
        heading.innerHTML = 'Sri Lanka\'s trades<br><span class="amber">directory.</span>';
        body.textContent = 'Build an authenticated digital track record backed by your NIC and direct customer reviews.';
        action.innerHTML = '<button type="button" class="btn btn-white" onclick="switchView(\'login\')">Have an account? Sign in →</button>';
    }
}

// Toggle password visibility
function togglePass(id, btn) {
    var input = document.getElementById(id);
    input.type = (input.type === 'password') ? 'text' : 'password';
}

// Service Pro city picker
function toggleProCity(e) {
    e.stopPropagation();
    var dd = document.getElementById('proCityDropdown');
    var btn = document.getElementById('proCityPill');
    dd.classList.toggle('open');
    if (btn) btn.classList.toggle('open', dd.classList.contains('open'));
    if (dd.classList.contains('open')) {
        var inp = document.getElementById('proCitySearch');
        inp.value = '';
        inp.focus();
        filterProCities();
    }
}

function filterProCities() {
    var q = document.getElementById('proCitySearch').value.toLowerCase();
    var list = document.getElementById('proCityList');
    var items = list.querySelectorAll('.search-dropdown-item');

    items.forEach(function (item) {
        var name = item.getAttribute('data-name').toLowerCase();
        item.style.display = name.includes(q) ? '' : 'none';
    });

    var groups = list.querySelectorAll('.search-dropdown-group');
    groups.forEach(function (group) {
        var next = group.nextElementSibling;
        var hasVisible = false;
        while (next && !next.classList.contains('search-dropdown-group')) {
            if (next.classList.contains('search-dropdown-item') && next.style.display !== 'none') {
                hasVisible = true;
                break;
            }
            next = next.nextElementSibling;
        }
        group.style.display = hasVisible ? '' : 'none';
    });

    list.scrollTop = 0;
}

function pickProCity(districtId, cityName) {
    document.getElementById('proDistrictId').value = districtId;
    document.getElementById('proCityName').value = cityName;
    document.getElementById('proCityLabel').textContent = cityName;
    var dd = document.getElementById('proCityDropdown');
    dd.classList.remove('open');
    var btn = document.getElementById('proCityPill');
    if (btn) btn.classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#proCityPill') && !e.target.closest('#proCityDropdown')) {
        var dd = document.getElementById('proCityDropdown');
        if (dd) dd.classList.remove('open');
        var btn = document.getElementById('proCityPill');
        if (btn) btn.classList.remove('open');
    }
});

// Customer city picker
function toggleCusCity(e) {
    e.stopPropagation();
    var dd = document.getElementById('cusCityDropdown');
    var btn = document.getElementById('cusCityPill');
    dd.classList.toggle('open');
    if (btn) btn.classList.toggle('open', dd.classList.contains('open'));
    if (dd.classList.contains('open')) {
        var inp = document.getElementById('cusCitySearch');
        inp.value = '';
        inp.focus();
        filterCusCities();
    }
}

function filterCusCities() {
    var q = document.getElementById('cusCitySearch').value.toLowerCase();
    var list = document.getElementById('cusCityList');
    var items = list.querySelectorAll('.search-dropdown-item');

    items.forEach(function (item) {
        var name = item.getAttribute('data-name').toLowerCase();
        item.style.display = name.includes(q) ? '' : 'none';
    });

    var groups = list.querySelectorAll('.search-dropdown-group');
    groups.forEach(function (group) {
        var next = group.nextElementSibling;
        var hasVisible = false;
        while (next && !next.classList.contains('search-dropdown-group')) {
            if (next.classList.contains('search-dropdown-item') && next.style.display !== 'none') {
                hasVisible = true;
                break;
            }
            next = next.nextElementSibling;
        }
        group.style.display = hasVisible ? '' : 'none';
    });

    list.scrollTop = 0;
}

function pickCusCity(districtId, cityName) {
    document.getElementById('cusDistrictId').value = districtId;
    document.getElementById('cusCityName').value = cityName;
    document.getElementById('cusCityLabel').textContent = cityName;
    var dd = document.getElementById('cusCityDropdown');
    dd.classList.remove('open');
    var btn = document.getElementById('cusCityPill');
    if (btn) btn.classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#cusCityPill') && !e.target.closest('#cusCityDropdown')) {
        var dd = document.getElementById('cusCityDropdown');
        if (dd) dd.classList.remove('open');
        var btn = document.getElementById('cusCityPill');
        if (btn) btn.classList.remove('open');
    }
});
</script>

</body>
</html>