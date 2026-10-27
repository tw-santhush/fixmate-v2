<?php
include '../includes/config.php';

// Only logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ============================================================
// Fetch user info + district name
// ============================================================
$sql = "SELECT u.*, d.district_name
        FROM users u
        LEFT JOIN districts d ON u.district_id = d.id
        WHERE u.id = '$user_id'";
$user = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$user) {
    header('Location: logout.php');
    exit();
}

include '../includes/header.php';

// ============================================================
// Stats — real from database
// ============================================================

// Total reviews this user has written
$reviews_written = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM ratings WHERE user_id = '$user_id'"))['c'];

// Average rating this user has given
$avg_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT AVG(rating) as avg_r FROM ratings WHERE user_id = '$user_id'"));
$avg_given = $avg_row['avg_r'] ? number_format($avg_row['avg_r'], 1) : '—';

// Member since (format: Sep 2026)
$member_since = date('M Y', strtotime($user['created_at']));

// ============================================================
// Real bookings stats (from bookings table)
// ============================================================

// Active = pending + accepted (not yet completed/declined/cancelled)
$active_bookings = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM bookings 
     WHERE user_id = '$user_id' 
     AND status IN ('pending', 'accepted')"))['c'];

// Upcoming = accepted (technician confirmed, work not done)
$upcoming_bookings = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM bookings 
     WHERE user_id = '$user_id' 
     AND status = 'accepted'"))['c'];

// Extract first name only for greeting
$first_name = explode(' ', $user['name'])[0];
?>

<!-- ============================================================
     USER PROFILE
     ============================================================ -->
<section class="profile-page">
    <div class="container">

        <!-- ============ WELCOME HEADER ============ -->
        <div class="profile-head">

            <div>

                <h1>
                    Welcome back, <span class="teal-text"><?php echo htmlspecialchars($first_name); ?></span>
                </h1>
            </div>

            <a href="logout.php" class="profile-signout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign Out
            </a>

        </div>

        <!-- ============ 4 STAT BOXES ============ -->
        <div class="profile-stats">

            <div class="pstat">
                <span class="pstat-label">Active Bookings</span>
                <div class="pstat-row">
                    <span class="pstat-number"><?php echo $active_bookings; ?></span>
                    <span class="pstat-note">+<?php echo $upcoming_bookings; ?> upcoming</span>
                </div>
                <span class="pstat-icon">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </span>
            </div>

            <div class="pstat">
                <span class="pstat-label">Reviews Written</span>
                <div class="pstat-row">
                    <span class="pstat-number"><?php echo $reviews_written; ?></span>
                </div>
                <span class="pstat-icon">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3z"/>
                    </svg>
                </span>
            </div>

            <div class="pstat">
                <span class="pstat-label">Avg Rating Given</span>
                <div class="pstat-row">
                    <span class="pstat-number"><?php echo $avg_given; ?></span>
                    <?php if ($avg_given != '—') { ?>
                        <span class="pstat-star">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFB020" stroke="#FFB020" stroke-width="1">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </span>
                    <?php } ?>
                </div>
                <span class="pstat-icon">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </span>
            </div>

            <div class="pstat">
                <span class="pstat-label">Member Since</span>
                <div class="pstat-row">
                    <span class="pstat-number small"><?php echo $member_since; ?></span>
                </div>
                <span class="pstat-icon">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
            </div>

        </div>

        <!-- ============ MAIN PROFILE CARD ============ -->
        <div class="profile-card">

            <!-- Top row: avatar + name + ID code -->
            <div class="profile-card-head">

                <div class="profile-identity">

                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar">
                            <?php if (!empty($user['profile_picture'])) { ?>
                                <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="">
                            <?php } else { ?>
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            <?php } ?>
                        </div>
                        <span class="profile-avatar-check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </span>
                    </div>

                    <div>
                        <div class="profile-name-row">
                            <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                            <span class="pill pill-teal">
                                Customer<?php echo $user['district_name'] ? ' · ' . $user['district_name'] : ''; ?>
                            </span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Divider -->
            <div class="profile-divider"></div>

            <!-- Info grid -->
            <div class="profile-info-grid">

                <div class="pinfo">
                    <span class="pinfo-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"/>
                            <path d="m2 6 10 7 10-7"/>
                        </svg>
                    </span>
                    <div>
                        <span class="pinfo-label">Email Address</span>
                        <span class="pinfo-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                </div>

                <div class="pinfo">
                    <span class="pinfo-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2"/>
                            <path d="M12 18h.01"/>
                        </svg>
                    </span>
                    <div>
                        <span class="pinfo-label">Phone Number</span>
                        <span class="pinfo-value"><?php echo htmlspecialchars($user['phone'] ? $user['phone'] : 'Not provided'); ?></span>
                    </div>
                </div>

                <div class="pinfo">
                    <span class="pinfo-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                    </span>
                    <div>
                        <span class="pinfo-label">Account Registered</span>
                        <span class="pinfo-value"><?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <div class="pinfo">
                    <span class="pinfo-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </span>
                    <div>
                        <span class="pinfo-label">Residential District</span>
                        <span class="pinfo-value">
                            <?php echo $user['district_name'] ? $user['district_name'] . ', Sri Lanka' : 'Not specified'; ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Action buttons -->
            <div class="profile-actions">
                <a href="edit_user_profile.php" class="btn btn-amber">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                    </svg>
                    Edit Profile
                </a>

                <a href="my_bookings.php" class="btn btn-ghost">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                    My Bookings
                </a>
                
                <a href="edit_user_profile.php#password" class="btn btn-ghost">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="11" width="16" height="10" rx="2"/>
                        <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                    </svg>
                    Change Password
                </a>
            </div>

        </div>

    </div>
</section>

<?php include '../includes/footer.php'; ?>