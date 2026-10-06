<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    // Security: ensure the booking belongs to this user and is not already completed/cancelled
    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE id = '$booking_id' AND user_id = '$user_id' AND status IN ('pending', 'accepted')");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE id = '$booking_id'");
    }
    header('Location: my_bookings.php?cancelled=1');
    exit();
}

include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Success banner after creating
$just_created = isset($_GET['created']);
$just_cancelled = isset($_GET['cancelled']);

// Fetch all bookings for this user
$sql = "SELECT b.*, t.name AS tech_name, t.phone AS tech_phone,
               s.service_name, d.district_name
        FROM bookings b
        JOIN technicians t ON b.technician_id = t.id
        JOIN services   s ON t.service_id  = s.id
        JOIN districts  d ON t.district_id = d.id
        WHERE b.user_id = '$user_id'
        ORDER BY b.created_at DESC";
$query = mysqli_query($conn, $sql);

$bookings = [];
while ($row = mysqli_fetch_assoc($query)) { $bookings[] = $row; }

// Count by status
$counts = ['pending' => 0, 'accepted' => 0, 'completed' => 0, 'declined' => 0];
foreach ($bookings as $b) {
    if (isset($counts[$b['status']])) $counts[$b['status']]++;
}

// Status label helper
function status_label($status) {
    $map = [
        'pending'   => 'Pending',
        'accepted'  => 'Accepted',
        'declined'  => 'Declined',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst($status);
}
?>

<section class="my-bookings-page">
    <div class="container">

        <div class="my-bookings-head">
            <div>
                <h1>My Bookings</h1>
                <p class="lede">All your service requests in one place.</p>
            </div>
            <a href="find.php" class="btn btn-amber">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                Book another pro
            </a>
        </div>

        <?php if ($just_created) { ?>
            <div class="auth-success" style="margin-bottom: 24px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="M22 4 12 14.01l-3-3"/>
                </svg>
                Request sent! The technician will respond shortly.
            </div>
        <?php } ?>

        <?php if ($just_cancelled) { ?>
            <div class="auth-success" style="margin-bottom: 24px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="M22 4 12 14.01l-3-3"/>
                </svg>
                Booking cancelled successfully.
            </div>
        <?php } ?>

        <!-- Status summary -->
        <?php if (count($bookings) > 0) { ?>
            <div class="my-bookings-stats">
                <div class="mb-stat">
                    <span class="mb-stat-label">Total</span>
                    <span class="mb-stat-number"><?php echo count($bookings); ?></span>
                </div>
                <div class="mb-stat">
                    <span class="mb-stat-label">Pending</span>
                    <span class="mb-stat-number amber"><?php echo $counts['pending']; ?></span>
                </div>
                <div class="mb-stat">
                    <span class="mb-stat-label">Accepted</span>
                    <span class="mb-stat-number teal"><?php echo $counts['accepted']; ?></span>
                </div>
                <div class="mb-stat">
                    <span class="mb-stat-label">Completed</span>
                    <span class="mb-stat-number green"><?php echo $counts['completed']; ?></span>
                </div>
            </div>
        <?php } ?>

        <?php if (count($bookings) > 0) { ?>
            <div class="my-bookings-list">

                <?php foreach ($bookings as $b) {
                    $initials = '';
                    foreach (explode(' ', $b['tech_name']) as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                    $initials = substr($initials, 0, 2);
                ?>
                    <div class="booking-card">

                        <div class="booking-head">
                            <div class="booking-tech">
                                <div class="booking-avatar"><?php echo $initials; ?></div>
                                <div>
                                    <h3><?php echo htmlspecialchars($b['tech_name']); ?></h3>
                                    <p class="booking-trade">
                                        <?php echo htmlspecialchars($b['service_name']); ?>
                                        · <?php echo htmlspecialchars($b['district_name']); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="booking-status <?php echo $b['status']; ?>">
                                <?php echo strtoupper(status_label($b['status'])); ?>
                            </span>
                        </div>

                        <div class="booking-job">
                            "<?php echo htmlspecialchars($b['job_description']); ?>"
                        </div>

                        <div class="booking-meta">
                            <div class="booking-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                                <?php echo htmlspecialchars($b['preferred_time']); ?>
                            </div>
                            <div class="booking-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                                Requested <?php echo date('M d, Y', strtotime($b['created_at'])); ?>
                            </div>
                        </div>

                        <?php if ($b['status'] == 'accepted') { ?>
                            <div class="booking-actions">
                                <a href="tel:+<?php
                                    $pc = preg_replace('/[^0-9]/', '', $b['tech_phone']);
                                    if (substr($pc, 0, 2) == '94')    echo $pc;
                                    elseif (substr($pc, 0, 1) == '0') echo '94' . substr($pc, 1);
                                    else                              echo '94' . $pc;
                                ?>" class="btn btn-teal btn-sm">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                    </svg>
                                    Call Technician
                                </a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="cancel_booking" value="1">
                                    <button type="submit" class="btn btn-ghost btn-sm">Cancel Booking</button>
                                </form>
                            </div>
                        <?php } ?>

                        <?php if ($b['status'] == 'pending') { ?>
                            <div class="booking-actions">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="cancel_booking" value="1">
                                    <button type="submit" class="btn btn-ghost btn-sm">Cancel Booking</button>
                                </form>
                            </div>
                        <?php } ?>

                        <?php if ($b['status'] == 'completed') { ?>
                            <div class="booking-actions">
                                <a href="add_review.php?tech_id=<?php echo $b['technician_id']; ?>" class="btn btn-ghost btn-sm">
                                    Leave Review
                                </a>
                            </div>
                        <?php } ?>

                    </div>
                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="my-bookings-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#8899A6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                <h3>No bookings yet</h3>
                <p>Find a verified technician and request their service. Your bookings will appear here.</p>
                <a href="find.php" class="btn btn-amber">Find a Tradesman</a>
            </div>

        <?php } ?>

    </div>
</section>

<?php include '../includes/footer.php'; ?>