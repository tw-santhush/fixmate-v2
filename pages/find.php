<?php
include '../includes/config.php';
include '../includes/header.php';

// Filter values
$trade_id    = $_POST['trade']  ?? ($_GET['trade']  ?? ($_GET['service'] ?? ''));
$district_id = $_POST['area'] ?? $_POST['district'] ?? $_GET['district'] ?? $_GET['area'] ?? '';
$city_name = $_POST['area_name'] ?? $_GET['area_name'] ?? '';
$min_rating  = $_POST['rating'] ?? ($_GET['rating'] ?? 'all');

// Build query
$where = ["t.status = 'verified'"];

if ($trade_id != '' && $trade_id != 'all') {
    $where[] = "t.service_id = '" . (int)$trade_id . "'";
}
if ($district_id != '' && $district_id != 'all') {
    $where[] = "t.district_id = '" . (int)$district_id . "'";
}
if ($min_rating != '' && $min_rating != 'all') {
    $min_rating_sql = mysqli_real_escape_string($conn, $min_rating);
    $where[] = "t.rating >= '$min_rating_sql'";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT t.*, s.service_name, d.district_name
        FROM technicians t
        JOIN services  s ON t.service_id  = s.id
        JOIN districts d ON t.district_id = d.id
        $where_sql
        ORDER BY t.rating DESC, t.created_at DESC";

$query_result = mysqli_query($conn, $sql);
$results = [];
while ($row = mysqli_fetch_assoc($query_result)) { $results[] = $row; }

$total_found = count($results);

// Pending count for context
$pending_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM technicians WHERE status = 'pending'"))['c'];

// Label for the city pill
$city_label = 'All cities';
if (!empty($city_name)) {
    // Prefer the exact city name the user picked
    $city_label = $city_name;
} elseif ($district_id != '' && $district_id != 'all') {
    $dname = mysqli_fetch_assoc(mysqli_query($conn, "SELECT district_name FROM districts WHERE id = '" . (int)$district_id . "'"));
    if ($dname) $city_label = $dname['district_name'];
}
?>

<section class="find-page">
    <div class="container">

        <!-- ============ HERO ============ -->
        <div class="find-hero">
            <h1>Compare verified pros in your area</h1>
        </div>

        <!-- ============ FILTER BAR ============ -->
        <form method="POST" class="find-filter-card">
            <div class="find-filters">

                <!-- Trade -->
                <div class="find-filter">
                    <label>
                        <span>Primary Trade</span>
                    </label>
                    <div class="find-select-wrap">
                        <svg class="find-select-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <select name="trade">
                            <option value="all">All Registered Trades</option>
                            <?php
                            $svc = mysqli_query($conn, "SELECT id, service_name FROM services ORDER BY service_name");
                            while ($s = mysqli_fetch_assoc($svc)) {
                                $sel = ($trade_id == $s['id']) ? 'selected' : '';
                                echo "<option value='{$s['id']}' $sel>" . htmlspecialchars($s['service_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- City / District picker -->
                <div class="find-filter">
                    <label>
                        <span>City / District</span>
                    </label>

                    <div class="find-select-wrap find-city-wrap">
                        <input type="hidden" name="area" id="findCityHidden" value="<?php echo htmlspecialchars($district_id); ?>">
                        <input type="hidden" name="area_name" id="findCityNameHidden" value="<?php echo htmlspecialchars($city_name); ?>">

                        <button type="button" class="find-city-pill" id="findCityPill" onclick="toggleFindCity(event)">
                            <svg class="find-select-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span id="findCityLabel"><?php echo htmlspecialchars($city_label); ?></span>
                            <svg class="find-city-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div class="find-city-dropdown" id="findCityDropdown">
                            <div class="search-dropdown-input">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.35-4.35"/>
                                </svg>
                                <input type="text" id="findCitySearch" placeholder="Search city..." oninput="filterFindCities()" autocomplete="off">
                            </div>

                            <div class="search-dropdown-list" id="findCityList">
                                <button type="button" class="search-dropdown-item" data-name="All cities" onclick="pickFindCity('', 'All cities')">
                                    All cities
                                </button>
                                <?php
                                $cq = mysqli_query($conn,
                                    "SELECT c.name, c.district_id, d.district_name
                                     FROM cities c
                                     JOIN districts d ON c.district_id = d.id
                                     ORDER BY d.district_name ASC, c.name ASC");
                                $current_d = '';
                                while ($c = mysqli_fetch_assoc($cq)) {
                                    if ($c['district_name'] !== $current_d) {
                                        $current_d = $c['district_name'];
                                        echo '<div class="search-dropdown-group">' . htmlspecialchars($current_d) . '</div>';
                                    }
                                    echo '<button type="button" class="search-dropdown-item" '
                                       . 'data-name="' . htmlspecialchars($c['name']) . '" '
                                       . 'onclick="pickFindCity(' . (int)$c['district_id'] . ', \'' . addslashes($c['name']) . '\')">'
                                       . htmlspecialchars($c['name'])
                                       . '</button>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rating -->
                <div class="find-filter">
                    <label>
                        <span>Customer Rating</span>
                    </label>
                    <div class="find-select-wrap">
                        <svg class="find-select-icon amber" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <select name="rating">
                            <option value="4.5" <?php if ($min_rating == '4.5') echo 'selected'; ?>>★ 4.5 &amp; up (Top Rated)</option>
                            <option value="4.8" <?php if ($min_rating == '4.8') echo 'selected'; ?>>★ 4.8 &amp; up (Elite)</option>
                            <option value="4.0" <?php if ($min_rating == '4.0') echo 'selected'; ?>>★ 4.0 &amp; up (Standard)</option>
                            <option value="all" <?php if ($min_rating == 'all') echo 'selected'; ?>>Any Rating</option>
                        </select>
                    </div>
                </div>

                <!-- Submit -->
                <div class="find-submit">
                    <button type="submit" class="find-search-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Search Tradesmen
                    </button>
                </div>

            </div>
        </form>

        <!-- ============ RESULTS ============ -->
        <?php if ($total_found > 0) { ?>
            <div class="find-list">

                <?php foreach ($results as $tech) {
                    $parts = explode(' ', $tech['name']);
                    $initials = '';
                    foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                    $initials = substr($initials, 0, 2);

                    $phone_clean = preg_replace('/[^0-9]/', '', $tech['phone']);
                    if (substr($phone_clean, 0, 2) == '94')      $phone_intl = $phone_clean;
                    elseif (substr($phone_clean, 0, 1) == '0')   $phone_intl = '94' . substr($phone_clean, 1);
                    else                                          $phone_intl = '94' . $phone_clean;

                    $is_verified  = ($tech['status'] == 'verified');
                    $is_available = isset($tech['available']) ? (int)$tech['available'] : 1;
                    $dot_class    = $is_verified ? ($is_available ? 'live' : 'offline') : 'pending';
                ?>

                    <div class="find-ticket <?php echo $is_verified ? '' : 'pending'; ?>">

                        <div class="find-ticket-left">
                            <div class="find-avatar">
                                <?php if (!empty($tech['profile_picture'])) { ?>
                                    <img src="../<?php echo htmlspecialchars($tech['profile_picture']); ?>" alt="">
                                <?php } else { ?>
                                    <span><?php echo $initials; ?></span>
                                <?php } ?>
                                <span class="find-avatar-dot <?php echo $dot_class; ?>"></span>
                            </div>

                            <div class="find-ticket-info">
                                <div class="find-ticket-name-row">
                                    <h3><?php echo htmlspecialchars($tech['name']); ?></h3>
                                    <?php if ($is_verified) { ?>
                                        <span class="pill pill-verify">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 6 9 17l-5-5"/>
                                            </svg>
                                            VERIFIED
                                        </span>
                                    <?php } else { ?>
                                        <span class="pill pill-pending">PENDING VERIFICATION</span>
                                    <?php } ?>
                                </div>

                                <p class="find-ticket-trade">
                                    <?php echo htmlspecialchars($tech['service_name']); ?>
                                    · <?php echo htmlspecialchars($tech['district_name']); ?>
                                    <?php if (!empty($tech['city'])) { ?>, <?php echo htmlspecialchars($tech['city']); ?><?php } ?>
                                </p>

                                <div class="find-ticket-stats">
                                    <span class="find-ticket-rating">
                                        ⭐ <?php echo number_format($tech['rating'], 1); ?>
                                        <?php
                                        $rev_count = mysqli_fetch_assoc(mysqli_query($conn,
                                            "SELECT COUNT(*) as c FROM ratings WHERE technician_id = " . $tech['id']))['c'];
                                        ?>
                                        <span class="muted">(<?php echo $rev_count; ?> reviews)</span>
                                    </span>
                                    <span class="sep">·</span>
                                    <span><?php echo $tech['experience']; ?> yrs experience</span>
                                    <?php if ($is_verified) { ?>
                                        <span class="sep">·</span>
                                        <span style="font-weight: 600; color: <?php echo $is_available ? 'var(--teal)' : 'var(--danger)'; ?>;">
                                            <?php echo $is_available ? '● Available now' : '○ Currently busy'; ?>
                                        </span>
                                    <?php } ?>
                                    <?php if (!empty($tech['bio'])) { ?>
                                        <span class="sep">·</span>
                                        <span class="teal-text"><?php echo htmlspecialchars(substr($tech['bio'], 0, 60)); ?></span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="find-perf">
                            <span class="find-perf-notch top"></span>
                            <span class="find-perf-line"></span>
                            <span class="find-perf-notch bottom"></span>
                        </div>

                        <div class="find-ticket-right">
                            <div class="find-ticket-price">
                                <span class="find-ticket-price-label">
                                    <?php echo $is_verified ? 'Call-out Fee' : 'Estimated Callout'; ?>
                                </span>
                                <div class="find-ticket-price-value">
                                    <span class="amount"><?php echo !empty($tech['callout_fee']) ? htmlspecialchars($tech['callout_fee']) : 'Rs. 2,000'; ?></span>
                                    <span class="per">/ visit</span>
                                </div>
                                <?php if (!$is_verified) { ?>
                                    <span class="find-ticket-price-note">
                                        Unverified — bookings paused
                                    </span>
                                <?php } ?>
                            </div>

                            <div class="find-ticket-actions">
                                <a href="view_reviews.php?tech_id=<?php echo $tech['id']; ?>" class="find-btn-ghost">View Profile</a>

                                <?php if ($is_verified) { ?>
                                    <a href="book.php?tech_id=<?php echo $tech['id']; ?>" class="find-btn-request">
                                        Request
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php } else { ?>
                                    <button type="button" class="find-btn-disabled" disabled>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 6v6l4 2"/>
                                        </svg>
                                        In Review
                                    </button>
                                <?php } ?>
                            </div>
                        </div>

                    </div>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="find-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#8899A6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <h3>No tradesmen match this filter</h3>
                <p>Try widening the district or removing the rating filter.</p>
                <a href="find.php" class="btn btn-amber">Reset filters</a>
            </div>

        <?php } ?>

    </div>
</section>

<script>
function toggleFindCity(e) {
    e.stopPropagation();
    var dd = document.getElementById('findCityDropdown');
    var btn = document.getElementById('findCityPill');
    dd.classList.toggle('open');
    if (btn) btn.classList.toggle('open', dd.classList.contains('open'));
    if (dd.classList.contains('open')) {
        var inp = document.getElementById('findCitySearch');
        inp.value = '';
        inp.focus();
        filterFindCities();
    }
}

function filterFindCities() {
    var q = document.getElementById('findCitySearch').value.toLowerCase();
    var list = document.getElementById('findCityList');
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

function pickFindCity(districtId, cityName) {
    document.getElementById('findCityHidden').value = districtId;
    if (districtId === '' || districtId === 'all') {
        document.getElementById('findCityNameHidden').value = '';
    } else {
        document.getElementById('findCityNameHidden').value = cityName;
    }
    document.getElementById('findCityLabel').textContent = cityName;
    var dd = document.getElementById('findCityDropdown');
    dd.classList.remove('open');
    var btn = document.getElementById('findCityPill');
    if (btn) btn.classList.remove('open');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#findCityPill') && !e.target.closest('#findCityDropdown')) {
        var dd = document.getElementById('findCityDropdown');
        if (dd) dd.classList.remove('open');
        var btn = document.getElementById('findCityPill');
        if (btn) btn.classList.remove('open');
    }
});
</script>

<?php include '../includes/footer.php'; ?>