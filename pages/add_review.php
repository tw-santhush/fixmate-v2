<?php
include '../includes/config.php';

// Only logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include '../includes/header.php';

$user_id = $_SESSION['user_id'];
$tech_id = isset($_GET['tech_id']) ? (int)$_GET['tech_id'] : 0;

if ($tech_id == 0) {
    header('Location: find.php');
    exit();
}

// ============================================================
// FETCH TECHNICIAN
// ============================================================
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

// ============================================================
// CHECK FOR EXISTING REVIEW
// ============================================================
$check = mysqli_query($conn, "SELECT * FROM ratings WHERE user_id = '$user_id' AND technician_id = '$tech_id'");
$existing = mysqli_fetch_assoc($check);

$check_booking = mysqli_query($conn, "SELECT id FROM bookings WHERE user_id = '$user_id' AND technician_id = '$tech_id' AND status = 'completed'");
if (mysqli_num_rows($check_booking) == 0) {
    header('Location: my_bookings.php');
    exit();
}

// ============================================================
// HANDLE SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = (int)$_POST['rating'];
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $tags   = mysqli_real_escape_string($conn, $_POST['tags'] ?? '');

    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    if ($existing) {
        // Update
        $sql_update = "UPDATE ratings SET 
                        rating = '$rating',
                        review = '$review',
                        tags = '$tags'
                        WHERE user_id = '$user_id' AND technician_id = '$tech_id'";
        mysqli_query($conn, $sql_update);
    } else {
        // Insert
        $sql_insert = "INSERT INTO ratings (user_id, technician_id, rating, review, tags)
                       VALUES ('$user_id', '$tech_id', '$rating', '$review', '$tags')";
        mysqli_query($conn, $sql_insert);
    }

    // Recalculate technician average rating
    $avg = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT AVG(rating) as a FROM ratings WHERE technician_id = '$tech_id'"))['a'];
    mysqli_query($conn, "UPDATE technicians SET rating = '$avg' WHERE id = '$tech_id'");

    header('Location: view_reviews.php?tech_id=' . $tech_id);
    exit();
}

// ============================================================
// PRE-FILL VALUES
// ============================================================
$current_rating = $existing ? (int)$existing['rating'] : 5;
$current_review = $existing ? $existing['review'] : '';
$current_tags   = $existing ? ($existing['tags'] ?? '') : 'Punctual Arrival,Fair & Transparent Pricing,High Quality Craft';
$selected_tags  = array_map('trim', explode(',', $current_tags));

// All available tags
$all_tags = [
    'Punctual Arrival'          => 'clock',
    'Fair & Transparent Pricing'=> 'banknote',
    'Clean Worksite'            => 'sparkles',
    'High Quality Craft'        => 'hammer',
    'Polite & Professional'     => 'smile',
    'Brought Proper Tools'      => 'tool',
];

// Rating descriptions
$rating_descriptions = [
      1 => 'Poor',
      2 => 'Below expectations',
      3 => 'Okay',
      4 => 'Good',
      5 => 'Excellent',
];

// Build initials
$initials = '';
$parts = explode(' ', $tech['name']);
foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
$initials = substr($initials, 0, 2);
?>

<section class="add-review-page">
    <div class="container narrow">

        <!-- ============ BACK LINK ============ -->
        <div class="add-review-back">
            <a href="view_reviews.php?tech_id=<?php echo $tech_id; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to technician profile
            </a>
        </div>

        <!-- ============ MAIN CARD ============ -->
        <form method="POST" class="add-review-card">

            <!-- Top gradient accent -->
            <div class="add-review-accent"></div>

            <div class="add-review-body">

                <!-- Header -->
                <div class="add-review-header">
                    <h1>How was your experience?</h1>
                    <p>Help community members across <?php echo htmlspecialchars($tech['district_name']); ?> find trustworthy, certified trade craft.</p>
                </div>

                <!-- Technician Dossier Summary -->
                <div class="add-review-tech">
                    <div class="add-review-tech-left">
                        <div class="add-review-tech-avatar">
                            <?php if (!empty($tech['profile_picture'])) { ?>
                                <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                            <?php } else { ?>
                                <?php echo $initials; ?>
                            <?php } ?>
                            <?php if ($tech['status'] == 'verified') { ?>
                                <span class="add-review-tech-check">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6 9 17l-5-5"/>
                                    </svg>
                                </span>
                            <?php } ?>
                        </div>
                        <div>
                            <div class="add-review-tech-name">
                                <?php echo htmlspecialchars($tech['name']); ?>
                                <?php if ($tech['status'] == 'verified') { ?>
                                    <span class="pill pill-verify" style="font-size:9.5px;">Verified Pro</span>
                                <?php } ?>
                            </div>
                            <p class="add-review-tech-meta">
                                <?php echo htmlspecialchars($tech['service_name']); ?>
                                · <?php echo htmlspecialchars($tech['district_name']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="add-review-ref">
                        <span class="add-review-ref-id">Ref #LK-<?php echo str_pad($tech['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        <span class="add-review-ref-sub"><?php echo $existing ? 'Edit mode' : 'New review'; ?></span>
                    </div>
                </div>

                <!-- Service Rating -->
                <div class="add-review-rating">
                    <span class="add-review-rating-label">Service Quality Rating</span>

                    <div class="add-review-stars" id="starContainer">
                        <?php for ($i = 1; $i <= 5; $i++) { ?>
                            <button type="button" class="add-star-btn <?php echo ($i <= $current_rating) ? 'active' : ''; ?>" data-val="<?php echo $i; ?>" aria-label="<?php echo $i; ?> Stars">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </button>
                        <?php } ?>
                    </div>

                    <p class="add-review-rating-desc" id="ratingDesc">
                        <?php echo $rating_descriptions[$current_rating]; ?>
                    </p>

                    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $current_rating; ?>">
                </div>

                <!-- Tags -->
                <div class="add-review-tags">
                    <div class="add-review-tags-head">
                        <label>What stood out most?</label>

                    </div>

                    <div class="add-review-chips" id="tagChips">
                        <?php foreach ($all_tags as $tag => $icon) {
                            $is_selected = in_array($tag, $selected_tags);
                        ?>
                            <button type="button"
                                    class="add-chip <?php echo $is_selected ? 'active' : ''; ?>"
                                    data-tag="<?php echo htmlspecialchars($tag); ?>">
                                <?php if ($icon == 'clock') { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                                    </svg>
                                <?php } elseif ($icon == 'banknote') { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/>
                                    </svg>
                                <?php } elseif ($icon == 'sparkles') { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3l2 5 5 2-5 2-2 5-2-5-5-2 5-2z"/>
                                    </svg>
                                <?php } elseif ($icon == 'hammer') { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 12l-8.5 8.5a1.5 1.5 0 1 1-2-2L13 10"/>
                                        <path d="M17.6 15.9L21 12.5"/><path d="M14 6l4 4"/><rect x="15" y="3" width="6" height="3" rx="1"/>
                                    </svg>
                                <?php } elseif ($icon == 'smile') { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>
                                    </svg>
                                <?php } else { ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                    </svg>
                                <?php } ?>
                                <?php echo htmlspecialchars($tag); ?>
                            </button>
                        <?php } ?>
                    </div>

                    <input type="hidden" name="tags" id="tagsInput" value="<?php echo htmlspecialchars($current_tags); ?>">
                </div>

                <!-- Written Review -->
                <div class="add-review-text">
                    <div class="add-review-text-head">
                        <label for="reviewNotes">Your written review <span>(optional)</span></label>
                        <span class="add-review-lang">SINHALA / TAMIL / ENGLISH welcome</span>
                    </div>
                    <textarea id="reviewNotes" name="review" maxlength="500"
                              placeholder="Share details about the work quality, punctuality, fair billing, and whether you would recommend <?php echo htmlspecialchars(explode(' ', $tech['name'])[0]); ?> to your neighbors..."><?php echo htmlspecialchars($current_review); ?></textarea>
                    <div class="add-review-text-foot">
                        <span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="11" width="16" height="10" rx="2"/>
                                <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                            </svg>
                            Name masked on public register for privacy
                        </span>
                        <span id="charCount"><?php echo strlen($current_review); ?> / 500</span>
                    </div>
                </div>

                <!-- Verified Booking Checkbox -->
                <div class="add-review-verified">
                    <input type="checkbox" id="verifiedCheck" checked disabled>
                    <label for="verifiedCheck">
                        <strong>✓ Verified booking:</strong> I confirm this review is based on genuine service booked through FixMate.
                    </label>
                </div>

                <!-- Actions -->
                <div class="add-review-actions">
                    <a href="view_reviews.php?tech_id=<?php echo $tech_id; ?>" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-amber">
                        <?php echo $existing ? 'Update review' : 'Submit review'; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>
        </form>

    </div>
</section>


<script>
(function initReviewForm() {

    // =============== STARS ===============
    var stars = document.querySelectorAll('.add-star-btn');
    var ratingInput = document.getElementById('ratingInput');
    var ratingDesc = document.getElementById('ratingDesc');
    var currentRating = <?php echo $current_rating; ?>;

    var descriptions = {
         1: 'Poor',
         2: 'Below expectations',
         3: 'Okay',
         4: 'Good',
         5: 'Excellent'
    };

    function paintStars(val) {
        stars.forEach(function(btn) {
            var v = parseInt(btn.getAttribute('data-val'), 10);
            if (v <= val) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        ratingDesc.textContent = descriptions[val] || '';
    }

    stars.forEach(function(btn) {
        btn.addEventListener('mouseenter', function() {
            paintStars(parseInt(btn.getAttribute('data-val'), 10));
        });
        btn.addEventListener('click', function() {
            currentRating = parseInt(btn.getAttribute('data-val'), 10);
            ratingInput.value = currentRating;
            paintStars(currentRating);
        });
    });

    document.getElementById('starContainer').addEventListener('mouseleave', function() {
        paintStars(currentRating);
    });

    // =============== TAGS ===============
    var chipContainer = document.getElementById('tagChips');
    var tagsInput = document.getElementById('tagsInput');

    function refreshTags() {
        var active = [];
        document.querySelectorAll('.add-chip.active').forEach(function(c) {
            active.push(c.getAttribute('data-tag'));
        });
        tagsInput.value = active.join(',');
    }

    document.querySelectorAll('.add-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            chip.classList.toggle('active');
            refreshTags();
        });
    });

    // =============== CHAR COUNTER ===============
    var textarea = document.getElementById('reviewNotes');
    var counter = document.getElementById('charCount');

    textarea.addEventListener('input', function() {
        var len = textarea.value.length;
        counter.textContent = len + ' / 500';
        if (len > 450) {
            counter.classList.add('warning');
        } else {
            counter.classList.remove('warning');
        }
    });

})();
</script>

<?php include '../includes/footer.php'; ?>