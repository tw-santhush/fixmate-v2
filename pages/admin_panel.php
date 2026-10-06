<?php
include '../includes/config.php';

// Only admins
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// ============================================================
// HANDLE APPROVE / REJECT ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $tech_id = (int)$_POST['tech_id'];

    if ($_POST['action'] == 'approve') {
        mysqli_query($conn, "UPDATE technicians 
                            SET status = 'verified', verified_at = NOW() 
                            WHERE id = '$tech_id'");
        header('Location: admin_panel.php?msg=approved');
        exit();
    }

    if ($_POST['action'] == 'reject') {
        mysqli_query($conn, "UPDATE technicians SET status = 'rejected' WHERE id = '$tech_id'");
        header('Location: admin_panel.php?msg=rejected');
        exit();
    }

    if ($_POST['action'] == 'suspend') {
        mysqli_query($conn, "UPDATE technicians SET status = 'suspended' WHERE id = '$tech_id'");
        header('Location: admin_panel.php?msg=suspended');
        exit();
    }
}

// ============================================================
// GET FILTER VALUES (from URL)
// ============================================================
$status_filter   = $_GET['status']   ?? 'all';       // all | pending | verified | rejected
$district_filter = $_GET['district'] ?? '';
$service_filter  = $_GET['service']  ?? '';
$search          = $_GET['search']   ?? '';

// Preserve current filters in queue tab links
$filter_qs = '';
if ($search != '')          $filter_qs .= '&search=' . urlencode($search);
if ($district_filter != '') $filter_qs .= '&district=' . urlencode($district_filter);
if ($service_filter != '')  $filter_qs .= '&service=' . urlencode($service_filter);

// Label for the city pill
$city_label = 'All cities';
if ($district_filter != '' && $district_filter != 'all') {
    $dname = mysqli_fetch_assoc(mysqli_query($conn, "SELECT district_name FROM districts WHERE id = '" . (int)$district_filter . "'"));
    if ($dname) $city_label = $dname['district_name'];
}

// ============================================================
// STATS
// ============================================================
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$total_techs     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM technicians"))['c'];
$pending_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM technicians WHERE status = 'pending'"))['c'];
$verified_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM technicians WHERE status = 'verified'"))['c'];
$rejected_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM technicians WHERE status = 'rejected'"))['c'];

// ============================================================
// REAL BOOKING STATS
// ============================================================
$total_bookings     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings"))['c'];
$pending_bookings   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'"))['c'];
$completed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings WHERE status = 'completed'"))['c'];

// ============================================================
// BUILD THE VERIFICATION QUEUE QUERY
// ============================================================
$where = [];

if ($status_filter == 'pending')    $where[] = "t.status = 'pending'";
if ($status_filter == 'verified')   $where[] = "t.status = 'verified'";
if ($status_filter == 'rejected')   $where[] = "t.status = 'rejected'";

if ($district_filter != '')         $where[] = "t.district_id = '" . (int)$district_filter . "'";
if ($service_filter != '')          $where[] = "t.service_id = '" . (int)$service_filter . "'";

if ($search != '') {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where[] = "(t.name LIKE '%$search_safe%' OR t.email LIKE '%$search_safe%' OR t.phone LIKE '%$search_safe%')";
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$queue_sql = "SELECT t.*, s.service_name, d.district_name
              FROM technicians t
              JOIN services  s ON t.service_id  = s.id
              JOIN districts d ON t.district_id = d.id
              $where_sql
              ORDER BY 
                CASE t.status
                    WHEN 'pending'  THEN 1
                    WHEN 'verified' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4
                END,
                t.created_at DESC";

$queue = mysqli_query($conn, $queue_sql);
$queue_rows = [];
while ($r = mysqli_fetch_assoc($queue)) { $queue_rows[] = $r; }

// ============================================================
// REAL RECENT BOOKINGS
// ============================================================
$feed_sql = "SELECT b.id, b.status, b.created_at, b.preferred_time,
                    u.name AS customer_name,
                    t.name AS tech_name,
                    s.service_name, d.district_name
             FROM bookings b
             JOIN users u       ON b.user_id = u.id
             JOIN technicians t ON b.technician_id = t.id
             JOIN services s    ON t.service_id = s.id
             JOIN districts d   ON t.district_id = d.id
             ORDER BY b.created_at DESC
             LIMIT 10";
$feed_query = mysqli_query($conn, $feed_sql);
$audit_feed = [];
while ($row = mysqli_fetch_assoc($feed_query)) { $audit_feed[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — FixMate</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- ============ MINIMAL ADMIN HEADER (replaces site header) ============ -->
<header class="admin-topbar">
    <div class="admin-topbar-inner">

        <a href="admin_panel.php" class="logo">
            <span class="logo-mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </span>
            <span class="logo-text">
                <span class="name">FixMate</span>
                <span class="tag">Admin Console</span>
            </span>
        </a>

        <nav class="admin-nav">
            <a href="admin_panel.php?status=pending" class="<?php echo ($status_filter == 'pending') ? 'active' : ''; ?>">Verification</a>
            <a href="admin_panel.php?status=all" class="<?php echo ($status_filter == 'all') ? 'active' : ''; ?>">Technicians</a>
            <a href="home.php">View Site</a>
        </nav>

        <div class="admin-topbar-actions">
            <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
        </div>

    </div>
</header>


<!-- ============ ADMIN PAGE ============ -->
<section class="admin-page">
    <div class="container">

        <!-- ============ MASTHEAD ============ -->
        <div class="admin-masthead">
            <div>
                <h1>FixMate Sri Lanka — Administration</h1>
            </div>
        </div>

        <!-- ============ 4 STAT BOXES ============ -->
        <div class="admin-stats">

            <div class="astat">
                <div class="astat-top">
                    <span>Total Customers</span>
                    <span class="astat-icon icon-teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                </div>
                <div class="astat-number"><?php echo number_format($total_customers); ?></div>
            </div>

            <div class="astat">
                <div class="astat-top">
                    <span>Total Technicians</span>
                    <span class="astat-icon icon-teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </span>
                </div>
                <div class="astat-number"><?php echo number_format($total_techs); ?></div>
            </div>

            <div class="astat astat-highlight">
                <div class="astat-top">
                    <span class="amber-text">Pending Verifications</span>
                    <span class="astat-icon icon-amber">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </span>
                </div>
                <div class="astat-number amber"><?php echo $pending_count; ?></div>
            </div>

            <div class="astat">
                <div class="astat-top">
                    <span>Total Bookings</span>
                    <span class="astat-icon icon-teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z"/>
                            <path d="M8 7h8M8 11h8M8 15h5"/>
                        </svg>
                    </span>
                </div>
                <div class="astat-number"><?php echo number_format($total_bookings); ?></div>
            </div>

        </div>

        <!-- ============ VERIFICATION QUEUE ============ -->
        <section class="admin-card">

            <div class="admin-card-head">
                <div>
                    <div class="admin-card-title">
                        <h2>Technician Verification Queue</h2>
                        <span class="queue-badge"><?php echo $pending_count; ?> Actionable</span>
                    </div>
                    <p>Review new technician applications and verify their NIC documents.</p>
                </div>

                <div class="admin-tabs">
                    <a href="?status=all<?php echo $filter_qs; ?>"      class="<?php echo ($status_filter == 'all')      ? 'active' : ''; ?>">All (<?php echo $total_techs; ?>)</a>
                    <a href="?status=pending<?php echo $filter_qs; ?>" class="<?php echo ($status_filter == 'pending')  ? 'active' : ''; ?>">Pending (<?php echo $pending_count; ?>)</a>
                    <a href="?status=verified<?php echo $filter_qs; ?>" class="<?php echo ($status_filter == 'verified') ? 'active' : ''; ?>">Verified (<?php echo $verified_count; ?>)</a>
                    <a href="?status=rejected<?php echo $filter_qs; ?>" class="<?php echo ($status_filter == 'rejected') ? 'active' : ''; ?>">Rejected (<?php echo $rejected_count; ?>)</a>
                </div>
            </div>

            <!-- Filter bar -->
            <form method="GET" class="admin-filter-bar">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">

                <div class="admin-search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search by name, email, or phone..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="admin-city-picker" id="adminCityWrap">
                    <input type="hidden" name="district" id="findCityHidden" value="<?php echo htmlspecialchars($district_filter); ?>">

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

                <select name="service">
                    <option value="">All Services</option>
                    <?php
                    $services = mysqli_query($conn, "SELECT id, service_name FROM services ORDER BY service_name");
                    while ($s = mysqli_fetch_assoc($services)) {
                        $sel = ($service_filter == $s['id']) ? 'selected' : '';
                        echo "<option value='{$s['id']}' $sel>" . htmlspecialchars($s['service_name']) . "</option>";
                    }
                    ?>
                </select>

                <button type="submit" class="btn btn-teal btn-sm">Apply</button>

                <?php if ($search || $district_filter || $service_filter) { ?>
                    <a href="?status=<?php echo htmlspecialchars($status_filter); ?>" class="admin-clear">Clear</a>
                <?php } ?>
            </form>

            <!-- Table -->
            <?php if (count($queue_rows) > 0) { ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Technician / NIC</th>
                                <th>Trade Specialization</th>
                                <th>Area &amp; District</th>
                                <th>Submitted Documents</th>
                                <th>Status</th>
                                <th class="right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queue_rows as $tech) {
                                $initials = '';
                                $parts = explode(' ', $tech['name']);
                                foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
                                $initials = substr($initials, 0, 2);
                            ?>
                                <tr>
                                    <td>
                                        <div class="admin-cell-person">
                                            <div class="admin-avatar">
                                                <?php if ($tech['status'] == 'verified') { echo $initials; } else { echo $initials; } ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($tech['name']); ?></strong>
                                                <span class="admin-cell-sub"><?php echo htmlspecialchars($tech['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($tech['service_name']); ?></td>
                                    <td>
                                        <span class="admin-cell-area">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <?php echo htmlspecialchars($tech['district_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="admin-doc">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                                            </svg>
                                            NIC documents
                                        </span>
                                    </td>
                                    <td>
                                        <span class="admin-status-pill <?php echo $tech['status']; ?>">
                                            <?php echo strtoupper($tech['status']); ?>
                                        </span>
                                    </td>
                                    <td class="right">
                                         <?php if ($tech['status'] == 'pending') { ?>
                                             <a href="admin_verify.php?tech_id=<?php echo $tech['id']; ?>" class="admin-text-btn">
                                                 Review
                                             </a>
                                         <?php } elseif ($tech['status'] == 'verified') { ?>
                                             <form method="POST" style="display:inline;">
                                                 <input type="hidden" name="tech_id" value="<?php echo $tech['id']; ?>">
                                                 <input type="hidden" name="action" value="suspend">
                                                 <button type="submit" class="admin-text-btn" onclick="return confirm('Suspend this technician?')">Suspend</button>
                                             </form>
                                         <?php } else { ?>
                                             <a href="admin_verify.php?tech_id=<?php echo $tech['id']; ?>" class="admin-text-btn">
                                                 Re-evaluate
                                             </a>
                                         <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="admin-empty">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#8899A6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                        <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                    </svg>
                    <h3>No technicians match this filter</h3>
                    <p>Try changing the status, district, or trade filter.</p>
                </div>
            <?php } ?>

        </section>

        <!-- ============ BOOKING AUDIT FEED ============ -->
        <section class="admin-card">

            <div class="admin-card-head">
                <div>
            <h2>Recent Bookings</h2>
            </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Technician Assigned</th>
                            <th>Trade</th>
                            <th>District / Area</th>
                            <th>Date &amp; Time</th>
                            <th class="right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_feed as $row) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></td>
                                <td class="teal-text"><?php echo htmlspecialchars($row['tech_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['district_name']); ?></td>
                                <td class="admin-time"><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></td>
                                <td class="right">
                                    <span class="admin-status-pill <?php echo $row['status']; ?>">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </section>

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

</body>
</html>