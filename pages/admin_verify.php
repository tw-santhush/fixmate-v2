<?php
include '../includes/config.php';

// Admin-only
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get tech_id from GET
$tech_id = (int)($_GET['tech_id'] ?? 0);
if ($tech_id === 0) {
    header('Location: admin_panel.php');
    exit();
}

// Fetch technician with joins
$sql = "SELECT t.*, s.service_name, d.district_name
        FROM technicians t
        JOIN services s ON t.service_id = s.id
        JOIN districts d ON t.district_id = d.id
        WHERE t.id = '$tech_id'";
$tech = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$tech) {
    header('Location: admin_panel.php');
    exit();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'approve') {
            mysqli_query($conn, "UPDATE technicians SET status='verified', verified_at=NOW() WHERE id='$tech_id'");
            header('Location: admin_panel.php');
            exit();
        } elseif ($action === 'reject') {
            $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
            mysqli_query($conn, "UPDATE technicians SET status='rejected', rejection_reason='$reason' WHERE id='$tech_id'");
            header('Location: admin_panel.php');
            exit();
        }
    }
}

include '../includes/header.php';
?>

<section class="verify-page">
    <div class="container">

        <div class="verify-breadcrumb">
            <a href="admin_panel.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Admin Panel
            </a>
        </div>

        <!-- Identity card -->
        <div class="verify-id-card">
            <div class="verify-id-head">
                <div class="verify-id-avatar">
                    <?php if (!empty($tech['profile_picture'])) { ?>
                        <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                    <?php } else {
                        $parts = explode(' ', $tech['name']);
                        $initials = '';
                        foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                        echo substr($initials, 0, 2);
                    } ?>
                </div>

                <div class="verify-id-info">
                    <h1><?php echo htmlspecialchars($tech['name']); ?></h1>
                    <p><?php echo htmlspecialchars($tech['service_name']); ?> · <?php echo htmlspecialchars($tech['district_name']); ?></p>
                    <span class="verify-id-status <?php echo $tech['status']; ?>">
                        <?php echo strtoupper($tech['status']); ?>
                    </span>
                </div>
            </div>

            <div class="verify-id-grid">
                <div>
                    <span class="verify-id-field-label">Email</span>
                    <span class="verify-id-field-value"><?php echo htmlspecialchars($tech['email']); ?></span>
                </div>
                <div>
                    <span class="verify-id-field-label">Phone</span>
                    <span class="verify-id-field-value"><?php echo htmlspecialchars($tech['phone']); ?></span>
                </div>
                <div>
                    <span class="verify-id-field-label">Experience</span>
                    <span class="verify-id-field-value"><?php echo (int)$tech['experience']; ?> years</span>
                </div>
                <div>
                    <span class="verify-id-field-label">Applied</span>
                    <span class="verify-id-field-value"><?php echo date('M d, Y', strtotime($tech['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="verify-docs">
            <div class="verify-docs-head">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <h2>Submitted Documents</h2>
                <span>Click image to open full size</span>
            </div>

            <div class="verify-docs-grid">
                <?php
                $docs = [
                    'NIC Front' => $tech['nic_front'] ?? '',
                    'NIC Back'  => $tech['nic_back']  ?? '',
                ];
                foreach ($docs as $label => $path):
                    $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
                ?>
                    <div class="verify-doc-box">
                        <span class="verify-doc-label"><?php echo $label; ?></span>
                        <?php if (empty($path)): ?>
                            <div class="verify-doc-empty">Not uploaded</div>
                        <?php elseif ($ext == 'pdf'): ?>
                            <a href="../<?php echo htmlspecialchars($path); ?>" target="_blank" class="verify-doc-pdf">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2 2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                Open PDF in new tab
                            </a>
                        <?php else: ?>
                            <a href="../<?php echo htmlspecialchars($path); ?>" target="_blank" class="verify-doc-preview">
                                <img src="../<?php echo htmlspecialchars($path); ?>" alt="<?php echo $label; ?>">
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Actions -->
        <?php if ($tech['status'] != 'verified'): ?>
            <div class="verify-actions">
                <div class="verify-actions-grid">

                    <div class="verify-action-card approve">
                        <h3>Approve Verification</h3>
                        <p>Mark this technician as fully verified. They will appear in public search with the green verified badge.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="tech_id" value="<?php echo $tech['id']; ?>">
                            <button type="submit" class="verify-action-btn approve" onclick="return confirm('Approve this technician?')">
                                Approve
                            </button>
                        </form>
                    </div>

                    <div class="verify-action-card reject">
                        <h3>Reject Verification</h3>
                        <p>The technician will see your reason on their dashboard and can re-upload documents.</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="tech_id" value="<?php echo $tech['id']; ?>">
                            <textarea name="reason" placeholder="e.g. NIC front image is blurry. Please re-upload a clearer photo." required></textarea>
                            <button type="submit" class="verify-action-btn reject" onclick="return confirm('Reject this technician?')">
                                Reject
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include '../includes/footer.php'; ?>