<?php
include '../includes/config.php';
include '../includes/header.php';

// Get technician ID from URL
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
// SORT OPTION
// ============================================================
$sort = $_GET['sort'] ?? 'newest';
$order_by = "r.created_at DESC";   // default

if ($sort == 'highest')  $order_by = "r.rating DESC, r.created_at DESC";
if ($sort == 'lowest')   $order_by = "r.rating ASC, r.created_at ASC";

// ============================================================
// FETCH REVIEWS
// ============================================================
$reviews_sql = "SELECT r.*, u.name AS user_name, u.city AS user_city, d.district_name AS user_district
                FROM ratings r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN districts d ON u.district_id = d.id
                WHERE r.technician_id = '$tech_id'
                ORDER BY $order_by";
$reviews_query = mysqli_query($conn, $reviews_sql);

$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_query)) {
    $reviews[] = $row;
}

$total_reviews = count($reviews);

// ============================================================
// STAR BREAKDOWN (count of 5★, 4★, etc.)
// ============================================================
$breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rev) {
    $breakdown[$rev['rating']]++;
}

// Percentages
$percentages = [];
foreach ($breakdown as $star => $count) {
    $percentages[$star] = ($total_reviews > 0) ? round(($count / $total_reviews) * 100) : 0;
}

// Phone for Book button
$phone_clean = preg_replace('/[^0-9]/', '', $tech['phone']);
if (substr($phone_clean, 0, 2) == '94')      $phone_intl = $phone_clean;
elseif (substr($phone_clean, 0, 1) == '0')   $phone_intl = '94' . substr($phone_clean, 1);
else                                          $phone_intl = '94' . $phone_clean;

?>

<section class="reviews-page">
    <div class="container reviews-narrow">

        <!-- ============ BREADCRUMB ============ -->
        <div class="reviews-breadcrumb">
            <a href="find.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to search
            </a>
        </div>

        <!-- ============ HEADER CARD ============ -->
        <div class="reviews-header-card">

            <div class="reviews-header-left">
                <div class="reviews-tech-avatar">
                    <?php if (!empty($tech['profile_picture'])) { ?>
                        <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                    <?php } else { ?>
                        <?php echo strtoupper(substr($tech['name'], 0, 1)); ?>
                    <?php } ?>
                    <?php if ($tech['status'] == 'verified') { ?>
                        <span class="reviews-tech-check">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"/>
                            </svg>
                        </span>
                    <?php } ?>
                </div>

                <div>
                    <div class="reviews-tech-name-row">
                        <h1><?php echo htmlspecialchars($tech['name']); ?></h1>
                        <?php if ($tech['status'] == 'verified') { ?>
                            <span class="pill pill-verify">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                                VERIFIED
                            </span>
                        <?php } ?>
                    </div>

                    <p class="reviews-tech-trade">
                        <?php echo htmlspecialchars($tech['service_name']); ?>
                        · <?php echo htmlspecialchars($tech['district_name']); ?>
                    </p>

                    <div class="reviews-tech-stats">
                        <span class="reviews-stat-star">
                            ⭐ <?php echo number_format($tech['rating'], 1); ?>
                        </span>
                        <span class="sep">·</span>
                        <span><?php echo $total_reviews; ?> reviews</span>
                        <span class="sep">·</span>
                        <span><?php echo $tech['jobs_completed']; ?> jobs completed</span>
                        <?php if (!empty($tech['callout_fee'])) { ?>
                            <span class="sep">·</span>
                            <span class="reviews-rate-badge"><?php echo htmlspecialchars($tech['callout_fee']); ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <?php if (!isset($_SESSION['tech_id']) || $_SESSION['tech_id'] != $tech_id) { ?>
            <a href="book.php?tech_id=<?php echo $tech_id; ?>" class="btn btn-amber">
                Book <?php echo htmlspecialchars(explode(' ', $tech['name'])[0]); ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
            <?php } ?>

        </div>

        <!-- ============ SCORE BREAKDOWN ============ -->
        <?php if ($total_reviews > 0) { ?>
            <div class="reviews-breakdown">

                <div class="reviews-score">
                    <span class="reviews-score-label">AUTHENTIC COMMUNITY SCORE</span>
                    <div class="reviews-score-value">
                        <span class="big"><?php echo number_format($tech['rating'], 1); ?></span>
                        <span class="small">/ 5.0</span>
                    </div>
                    <div class="reviews-score-stars">
                        <?php for ($i = 1; $i <= 5; $i++) {
                            $fill = ($i <= round($tech['rating'])) ? '#FFB020' : '#E3E8EC';
                        ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $fill; ?>" stroke="<?php echo $fill; ?>" stroke-width="1">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        <?php } ?>
                    </div>
                    <p class="reviews-score-note">
                        Based on <?php echo $total_reviews; ?> verified customer reviews.
                    </p>
                </div>

                <div class="reviews-bars">
                    <?php foreach ([5, 4, 3, 2, 1] as $star) { ?>
                        <div class="reviews-bar-row">
                            <span class="reviews-bar-label"><?php echo $star; ?> ★</span>
                            <div class="reviews-bar-track">
                                <div class="reviews-bar-fill" style="width: <?php echo $percentages[$star]; ?>%;"></div>
                            </div>
                            <span class="reviews-bar-percent"><?php echo $percentages[$star]; ?>%</span>
                        </div>
                    <?php } ?>
                </div>

            </div>
        <?php } ?>

        <!-- ============ SORT BAR ============ -->
        <div class="reviews-sort-bar">
            <div class="reviews-sort-left">
                <h2>Reviews</h2>
                <span class="reviews-count-badge"><?php echo $total_reviews; ?></span>
            </div>

            <form method="GET" class="reviews-sort-right">
                <input type="hidden" name="tech_id" value="<?php echo $tech_id; ?>">
                <label>Sort by:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest"  <?php echo ($sort == 'newest')  ? 'selected' : ''; ?>>Newest</option>
                    <option value="highest" <?php echo ($sort == 'highest') ? 'selected' : ''; ?>>Highest Rating</option>
                    <option value="lowest"  <?php echo ($sort == 'lowest')  ? 'selected' : ''; ?>>Lowest Rating</option>
                </select>
            </form>
        </div>

        <!-- ============ REVIEW CARDS ============ -->
        <?php if ($total_reviews > 0) { ?>

            <div class="reviews-list">

                <?php foreach ($reviews as $rev) {
                    // Build initials
                    $parts = explode(' ', $rev['user_name']);
                    $initials = '';
                    foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                    $initials = substr($initials, 0, 2);

                    // Location label
                    $loc = $rev['user_city'] ?: ($rev['user_district'] ?: 'Sri Lanka');
                ?>

                    <article class="review-card-new">

                        <div class="review-avatar">
                            <?php echo $initials; ?>
                        </div>

                        <div class="review-body">

                            <div class="review-head">
                                <div class="review-author">
                                    <strong><?php echo htmlspecialchars($rev['user_name']); ?></strong>
                                    <span class="pill pill-verify review-verified-pill">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                        Verified Booking
                                    </span>
                                </div>
                                <span class="review-time">
                                    <?php echo date('M d, Y', strtotime($rev['created_at'])); ?> · <?php echo htmlspecialchars($loc); ?>
                                </span>
                            </div>

                            <!-- Stars -->
                            <div class="review-stars-row">
                                <?php for ($i = 1; $i <= 5; $i++) {
                                    $fill = ($i <= $rev['rating']) ? '#FFB020' : '#E3E8EC';
                                ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $fill; ?>" stroke="<?php echo $fill; ?>" stroke-width="1">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                <?php } ?>
                            </div>

                            <?php if (!empty($rev['review'])) { ?>
                                <p class="review-text-new"><?php echo htmlspecialchars($rev['review']); ?></p>
                            <?php } ?>

                            <div class="review-footer">
                                <button type="button" class="review-action">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3z"/>
                                    </svg>
                                    Helpful
                                </button>
                                <span class="review-footer-sep">·</span>
                                <button type="button" class="review-action">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                    </svg>
                                    Report
                                </button>
                            </div>

                        </div>
                    </article>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="reviews-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#8899A6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <h3>No reviews yet</h3>
                <p>Be the first to review this professional!</p>
                <a href="add_review.php?tech_id=<?php echo $tech_id; ?>" class="btn btn-amber">
                    Write a Review
                </a>
            </div>

        <?php } ?>

        <!-- ============ LOAD MORE ============ -->
        <?php if ($total_reviews > 5) { ?>
            <div class="reviews-load-more">
                <button type="button" class="btn btn-ghost">
                    Load more reviews
                </button>
            </div>
        <?php } ?>

    </div>
</section>

<?php include '../includes/footer.php'; ?>