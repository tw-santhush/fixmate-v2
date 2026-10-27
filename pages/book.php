<?php
include '../includes/config.php';

// Must be logged in as a normal user
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$tech_id = (int)($_GET['tech_id'] ?? 0);

if ($tech_id == 0) {
    header('Location: find.php');
    exit();
}

// Fetch the technician (only verified ones)
$sql = "SELECT t.*, s.service_name, d.district_name
        FROM technicians t
        JOIN services  s ON t.service_id  = s.id
        JOIN districts d ON t.district_id = d.id
        WHERE t.id = '$tech_id' AND t.status = 'verified'";
$tech = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$tech) {
    header('Location: find.php');
    exit();
}

$error = '';

// Handle submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job       = mysqli_real_escape_string($conn, $_POST['job_description']);
    $preferred = mysqli_real_escape_string($conn, $_POST['preferred_time']);

    if (empty($job) || empty($preferred)) {
        $error = 'Please fill in both fields.';
    } elseif (strlen($job) < 10) {
        $error = 'Please describe the job in a bit more detail.';
    } else {
        $insert = "INSERT INTO bookings (user_id, technician_id, job_description, preferred_time)
                   VALUES ('$user_id', '$tech_id', '$job', '$preferred')";
        if (mysqli_query($conn, $insert)) {
            header('Location: my_bookings.php?created=1');
            exit();
        } else {
            $error = 'Error creating booking. Please try again.';
        }
    }
}

include '../includes/header.php';

// Initials
$initials = '';
foreach (explode(' ', $tech['name']) as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
$initials = substr($initials, 0, 2);

// Phone for the "call directly" fallback
$phone_clean = preg_replace('/[^0-9]/', '', $tech['phone']);
if (substr($phone_clean, 0, 2) == '94')      $phone_intl = $phone_clean;
elseif (substr($phone_clean, 0, 1) == '0')   $phone_intl = '94' . substr($phone_clean, 1);
else                                          $phone_intl = '94' . $phone_clean;
?>

<section class="book-page">
    <div class="container narrow">

        <div class="book-back">
            <a href="find.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to search
            </a>
        </div>

        <form method="POST" class="book-card">

            <div class="book-card-accent"></div>

            <div class="book-body">

                <div class="book-header">
                                <h1>Request a visit</h1>
                    <p>Describe your job and when you'd like the technician to come. They'll confirm shortly.</p>
                </div>

                <?php if ($error) { ?>
                    <div class="auth-error" style="margin-bottom: 20px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <!-- Technician summary -->
                <div class="book-tech">
                    <div class="book-tech-avatar">
                        <?php if (!empty($tech['profile_picture'])) { ?>
                            <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                        <?php } else { ?>
                            <?php echo $initials; ?>
                        <?php } ?>
                    </div>
                    <div>
                        <div class="book-tech-name"><?php echo htmlspecialchars($tech['name']); ?></div>
                        <p class="book-tech-meta">
                            <?php echo htmlspecialchars($tech['service_name']); ?>
                            · <?php echo htmlspecialchars($tech['district_name']); ?>
                        </p>
                    </div>
                    <a href="tel:+<?php echo $phone_intl; ?>" class="book-call">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        Call Direct
                    </a>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="job_description">Describe the job</label>
                    </div>
                    <textarea name="job_description" id="job_description" rows="4" required
                              placeholder="e.g. Kitchen sink has been leaking for 2 days. Pipe under the cabinet needs replacing."><?php echo htmlspecialchars($_POST['job_description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="preferred_time">Preferred time</label>
                    </div>
                    <input type="text" name="preferred_time" id="preferred_time" required
                           placeholder="e.g. Tomorrow morning 9–11 AM"
                            value="<?php echo htmlspecialchars($_POST['preferred_time'] ?? ''); ?>">
                </div>

                <p class="book-hint">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4M12 8h.01"/>
                    </svg>
                    The technician will accept or decline within a few hours. You'll see the update in your bookings.
                </p>

                <div class="book-actions">
                    <a href="find.php" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-amber">
                        Send Request
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>
        </form>

    </div>
</section>

<?php include '../includes/footer.php'; ?>