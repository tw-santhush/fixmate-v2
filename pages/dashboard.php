<?php
include '../includes/config.php';

// Only logged-in technicians
if (!isset($_SESSION['tech_id'])) {
    header('Location: login.php');
    exit();
}

$tech_id = $_SESSION['tech_id'];

// ============================================================
// TOGGLE AVAILABILITY
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_available'])) {
    $current = (int)$_POST['current_available'];
    $new = $current ? 0 : 1;
    mysqli_query($conn, "UPDATE technicians SET available = '$new' WHERE id = '$tech_id'");
    header('Location: dashboard.php');
    exit();
}

// ============================================================
// HANDLE ACCEPT / DECLINE / COMPLETE ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Security: verify the booking belongs to THIS technician
    $check = mysqli_query($conn,
        "SELECT id FROM bookings WHERE id = '$booking_id' AND technician_id = '$tech_id'");
    if (mysqli_num_rows($check) == 0) {
        header('Location: dashboard.php');
        exit();
    }

    if ($_POST['action'] == 'accept') {
        mysqli_query($conn, "UPDATE bookings SET status = 'accepted' WHERE id = '$booking_id'");
    } elseif ($_POST['action'] == 'decline') {
        mysqli_query($conn, "UPDATE bookings SET status = 'declined' WHERE id = '$booking_id'");
    } elseif ($_POST['action'] == 'complete') {
        // Mark job complete + increment jobs_completed counter (only once, from 'accepted')
        mysqli_query($conn, "UPDATE bookings SET status = 'completed' WHERE id = '$booking_id' AND status = 'accepted'");
        if (mysqli_affected_rows($conn) > 0) {
            mysqli_query($conn, "UPDATE technicians SET jobs_completed = jobs_completed + 1 WHERE id = '$tech_id'");
        }
    }

    header('Location: dashboard.php');
    exit();
}

include '../includes/header.php';

// ============================================================
// FETCH TECHNICIAN DATA
// ============================================================
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

// ============================================================
// REAL STATS
// ============================================================
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM ratings WHERE technician_id = '$tech_id'"))['c'];

$pending_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM bookings WHERE technician_id = '$tech_id' AND status = 'pending'"))['c'];

$accepted_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM bookings WHERE technician_id = '$tech_id' AND status = 'accepted'"))['c'];

$completed_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM bookings WHERE technician_id = '$tech_id' AND status = 'completed'"))['c'];

// ============================================================
// FETCH PENDING BOOKINGS (customer info joined)
// ============================================================
$pending_sql = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone,
                       d.district_name AS customer_district, u.city AS customer_city
                FROM bookings b
                JOIN users u     ON b.user_id = u.id
                LEFT JOIN districts d ON u.district_id = d.id
                WHERE b.technician_id = '$tech_id' AND b.status = 'pending'
                ORDER BY b.created_at ASC";
$pending_query = mysqli_query($conn, $pending_sql);
$pending_bookings = [];
while ($row = mysqli_fetch_assoc($pending_query)) { $pending_bookings[] = $row; }

// ============================================================
// FETCH ACCEPTED (upcoming jobs)
// ============================================================
$accepted_sql = "SELECT b.*, u.name AS customer_name, u.phone AS customer_phone,
                        d.district_name AS customer_district
                 FROM bookings b
                 JOIN users u     ON b.user_id = u.id
                 LEFT JOIN districts d ON u.district_id = d.id
                 WHERE b.technician_id = '$tech_id' AND b.status = 'accepted'
                 ORDER BY b.created_at ASC";
$accepted_query = mysqli_query($conn, $accepted_sql);
$accepted_bookings = [];
while ($row = mysqli_fetch_assoc($accepted_query)) { $accepted_bookings[] = $row; }

// ============================================================
// RECENT REVIEWS
// ============================================================
$recent_reviews_sql = "SELECT r.rating, r.review, r.created_at, u.name AS user_name
                       FROM ratings r
                       JOIN users u ON r.user_id = u.id
                       WHERE r.technician_id = '$tech_id'
                       AND r.review IS NOT NULL AND r.review != ''
                       ORDER BY r.created_at DESC
                       LIMIT 3";
$recent_reviews = mysqli_query($conn, $recent_reviews_sql);
?>

<section class="dashboard">
    <div class="container">

        <!-- ============ WELCOME HEADER ============ -->
        <div class="dash-welcome">
            <div class="dash-welcome-info">
                <div class="dash-avatar">
                    <?php if (!empty($tech['profile_picture'])) { ?>
                        <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                    <?php } else { ?>
                        <?php echo strtoupper(substr($tech['name'], 0, 1)); ?>
                    <?php } ?>
                    <span class="dash-avatar-dot"></span>
                </div>

                <div>
                    <h1>Welcome back, <?php echo explode(' ', $tech['name'])[0]; ?></h1>
                    <p class="dash-meta">
                        <span>⚡</span>
                        <?php echo htmlspecialchars($tech['service_name']); ?>
                        <span class="sep">•</span>
                        <span>📍</span>
                        <?php echo htmlspecialchars($tech['district_name']); ?>
                    </p>
                    <p class="dash-sub">Here's what's happening with your work today.</p>
                </div>
            </div>

            <div class="dash-welcome-actions">
                <?php $is_available = isset($tech['available']) ? (int)$tech['available'] : 1; ?>

                <form method="POST" style="display:inline; margin: 0;">
                    <input type="hidden" name="toggle_available" value="1">
                    <input type="hidden" name="current_available" value="<?php echo $is_available; ?>">
                    <button type="submit" class="dash-toggle <?php echo $is_available ? '' : 'off'; ?>" title="Click to toggle">
                        <span class="dash-toggle-dot"></span>
                        <span class="dash-toggle-label"><?php echo $is_available ? 'Available for jobs' : 'Busy / On-site'; ?></span>
                    </button>
                </form>

                <a href="edit_profile.php" class="dash-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/>
                    </svg>
                    Edit Profile
                </a>

                <a href="logout.php" class="dash-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>

        <!-- ============ 4 STAT BOXES ============ -->
        <div class="dash-stats">

            <div class="dash-stat">
                <div class="dash-stat-top">
                    <span>Total Reviews</span>
                    <span class="dash-stat-icon icon-amber">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                        </svg>
                    </span>
                </div>
                <div class="dash-stat-number"><?php echo $total_reviews; ?></div>
                <div class="dash-stat-sub">⭐ <?php echo number_format($tech['rating'], 1); ?> / 5.0 Rating</div>
            </div>

            <div class="dash-stat">
                <div class="dash-stat-top">
                    <span>Jobs Completed</span>
                    <span class="dash-stat-icon icon-green">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </span>
                </div>
                <div class="dash-stat-number"><?php echo $tech['jobs_completed']; ?></div>
                <div class="dash-stat-sub">Lifetime total</div>
            </div>

            <div class="dash-stat">
                <div class="dash-stat-top">
                    <span>Accepted Jobs</span>
                    <span class="dash-stat-icon icon-teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                    </span>
                </div>
                <div class="dash-stat-number"><?php echo $accepted_count; ?></div>
                <div class="dash-stat-sub teal">Waiting to be done</div>
            </div>

            <div class="dash-stat dash-stat-highlight">
                <div class="dash-stat-top">
                    <span>Pending Requests</span>
                    <span class="dash-stat-icon icon-amber-soft">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                    </span>
                </div>
                <div class="dash-stat-number amber"><?php echo $pending_count; ?></div>
                <div class="dash-stat-sub <?php echo $pending_count > 0 ? 'amber-bg' : ''; ?>">
                    <?php echo $pending_count > 0 ? 'Requires response today' : 'All caught up'; ?>
                </div>
            </div>

        </div>

        <!-- ============ 2-COLUMN WORKSPACE ============ -->
        <div class="dash-grid">

            <!-- ============ LEFT: PENDING + ACCEPTED ============ -->
            <div class="dash-col-left">

                <!-- Pending section header -->
                <div class="dash-request-bar">
                    <div class="dash-request-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                        </svg>
                        Incoming Service Requests
                        <span>(<?php echo $pending_count; ?>)</span>
                    </div>
                </div>

                <!-- Pending bookings -->
                <?php if (count($pending_bookings) > 0) { ?>
                    <?php foreach ($pending_bookings as $b) {
                        // Initials
                        $initials = '';
                        foreach (explode(' ', $b['customer_name']) as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                        $initials = substr($initials, 0, 2);

                        // Location label
                        $loc = $b['customer_city'] ?: ($b['customer_district'] ?: 'Sri Lanka');
                        $phone = $b['customer_phone'] ?: '';

                        // Time ago
                        $mins = floor((time() - strtotime($b['created_at'])) / 60);
                        if ($mins < 60)      $ago = $mins . ' min ago';
                        elseif ($mins < 1440) $ago = floor($mins / 60) . ' hr ago';
                        else                  $ago = floor($mins / 1440) . ' days ago';
                    ?>
                        <div class="request-card">

                            <div class="request-head">
                                <div class="request-person">
                                    <div class="request-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <h3><?php echo htmlspecialchars($b['customer_name']); ?></h3>
                                        <div class="request-loc">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <?php echo htmlspecialchars($loc); ?>
                                            <span class="sep">•</span>
                                            <span class="request-time"><?php echo $ago; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <span class="request-priority scheduled">
                                    <?php echo $b['status'] == 'pending' ? 'AWAITING' : strtoupper($b['status']); ?>
                                </span>
                            </div>

                            <div class="request-quote">
                                "<?php echo htmlspecialchars($b['job_description']); ?>"
                            </div>

                            <div class="request-meta">
                                <div class="request-meta-left">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M12 6v6l4 2"/>
                                    </svg>
                                    <strong>Preferred:</strong>
                                    <span><?php echo htmlspecialchars($b['preferred_time']); ?></span>
                                </div>
                            </div>

                            <div class="request-actions">

                                <?php if ($phone) { ?>
                                    <a href="tel:<?php echo htmlspecialchars($phone); ?>" class="request-btn call">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        Call Customer
                                    </a>
                                <?php } ?>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="request-btn decline" onclick="return confirm('Decline this request?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18M6 6l12 12"/>
                                        </svg>
                                        Decline
                                    </button>
                                </form>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="request-btn accept">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                        Accept Job
                                    </button>
                                </form>

                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="request-card" style="text-align:center; padding: 40px 20px;">
                        <p style="color: var(--ink-soft); margin: 0;">No pending requests right now. You're all caught up! ✅</p>
                    </div>
                <?php } ?>

                <!-- ============ ACCEPTED (UPCOMING) ============ -->
                <?php if ($accepted_count > 0) { ?>
                    <div class="dash-request-bar" style="margin-top: 24px;">
                        <div class="dash-request-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            Upcoming Jobs
                            <span>(<?php echo $accepted_count; ?>)</span>
                        </div>
                    </div>

                    <?php foreach ($accepted_bookings as $b) {
                        $initials = '';
                        foreach (explode(' ', $b['customer_name']) as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                        $initials = substr($initials, 0, 2);
                        $loc = $b['customer_district'] ?: 'Sri Lanka';
                    ?>
                        <div class="request-card accepted-card">
                            <div class="request-head">
                                <div class="request-person">
                                    <div class="request-avatar" style="background-color: var(--verify-soft); color: var(--verify);"><?php echo $initials; ?></div>
                                    <div>
                                        <h3><?php echo htmlspecialchars($b['customer_name']); ?></h3>
                                        <div class="request-loc">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <?php echo htmlspecialchars($loc); ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="request-priority" style="background-color: var(--verify-soft); color: var(--verify);">
                                    ACCEPTED
                                </span>
                            </div>

                            <div class="request-quote">"<?php echo htmlspecialchars($b['job_description']); ?>"</div>

                            <div class="request-meta">
                                <div class="request-meta-left">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M12 6v6l4 2"/>
                                    </svg>
                                    <strong>Preferred:</strong>
                                    <span><?php echo htmlspecialchars($b['preferred_time']); ?></span>
                                </div>
                            </div>

                            <div class="request-actions">
                                <?php if ($b['customer_phone']) { ?>
                                    <a href="tel:<?php echo htmlspecialchars($b['customer_phone']); ?>" class="request-btn call">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        Call Customer
                                    </a>
                                <?php } ?>

                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="request-btn accept" onclick="return confirm('Mark this job as completed?')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                        Mark Completed
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>

            </div>

            <!-- ============ RIGHT: VERIFICATION + REVIEWS ============ -->
            <div class="dash-col-right">

                <div class="verify-card">
                    <?php if ($tech['status'] == 'verified') { ?>
                    <div class="verify-stamp">
                        <div class="verify-stamp-inner">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <span class="verify-stamp-text">VERIFIED</span>
                            <span class="verify-stamp-sub">FIXMATE SL</span>
                        </div>
                    </div>
                    <?php } ?>


                 <div class="verify-head">
                     <h3>
                         <?php
                         if ($tech['status'] == 'verified')      echo 'Fully Verified Technician';
                             elseif ($tech['status'] == 'pending')   echo 'Verification Pending';
                             elseif ($tech['status'] == 'rejected')  echo 'Verification Rejected';
                             else                                     echo 'Account Suspended';
                             ?>
                         </h3>
                         <p>
                             <?php if ($tech['status'] == 'verified') { ?>
                                 Your NIC has been verified.
                             <?php } elseif ($tech['status'] == 'pending') { ?>
                                 Your NIC images are being reviewed. This usually takes 24-48 hours.
                             <?php } elseif ($tech['status'] == 'rejected') { ?>
                                 <?php echo htmlspecialchars($tech['rejection_reason'] ?: 'Your documents were not accepted. Please resubmit.'); ?>
                             <?php } else { ?>
                                 Your account has been temporarily suspended. Contact support.
                             <?php } ?>
                         </p>

                         <?php if ($tech['status'] == 'rejected') { ?>
                             <a href="resubmit_nic.php" class="btn btn-amber" style="margin-top: 14px; min-height: 42px; padding: 0 20px; font-size: 13px;">
                                 Re-submit NIC Images
                             </a>
                         <?php } ?>
                     </div>

                     <?php if (!empty($tech['nic_front']) || !empty($tech['nic_back'])) { ?>
                     <ul class="verify-list">
                         <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <path d="M22 4 12 14.01l-3-3"/>
                            </svg>
                            <div>
                                <strong>National Identity Card</strong>
                                <span>Verified</span>
                            </div>
                         </li>
                     </ul>
                     <?php } ?>

                </div>

                <div class="reviews-card">
                    <div class="reviews-head">
                        <h3>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            Recent Customer Reviews
                        </h3>
                        <a href="view_reviews.php?tech_id=<?php echo $tech['id']; ?>">View All</a>
                    </div>

                    <?php if (mysqli_num_rows($recent_reviews) > 0) { ?>
                        <?php while ($rev = mysqli_fetch_assoc($recent_reviews)) { ?>
                            <div class="review-item">
                                <div class="review-top">
                                    <div class="review-person">
                                        <div class="review-avatar"><?php echo strtoupper(substr($rev['user_name'], 0, 1)); ?></div>
                                        <div>
                                            <h4><?php echo htmlspecialchars($rev['user_name']); ?></h4>
                                            <span class="review-time"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="review-stars">
                                        <?php for ($i = 0; $i < $rev['rating']; $i++) { ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="#FFB020" stroke="#FFB020" stroke-width="1">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                            </svg>
                                        <?php } ?>
                                        <span><?php echo number_format($rev['rating'], 1); ?></span>
                                    </div>
                                </div>
                                <p class="review-text"><?php echo htmlspecialchars($rev['review']); ?></p>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="review-empty">
                            <p>No reviews yet. Complete jobs to start receiving feedback.</p>
                        </div>
                    <?php } ?>
                </div>

            </div>

        </div>

    </div>
</section>



<?php include '../includes/footer.php'; ?>