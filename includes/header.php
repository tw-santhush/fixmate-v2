<?php
// Get the current page name for highlighting active nav link
$current_page = basename($_SERVER['PHP_SELF']);

// Compute the site base URL dynamically (works on any page including 404)
$script_path = $_SERVER['SCRIPT_NAME'];
$base_url    = rtrim(dirname(dirname($script_path)), '/');   // e.g. /fixmate-v2
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FixMate — Find Trusted Local Tradesmen in Sri Lanka</title>

    <!-- Base URL so all relative links work on every page including 404 -->
    <base href="<?php echo $base_url; ?>/pages/">

    <link rel="stylesheet" href="<?php echo $base_url; ?>/css/style.css">
</head>
<body>

<header class="header">
    <div class="header-inner">

        <!-- Logo -->
        <a href="home.php" class="logo">
            <span class="logo-mark">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </span>
            <span class="logo-text">
                <span class="name">FixMate</span>
                <span class="tag">Sri Lanka Trades Directory</span>
            </span>
        </a>

        <div class="header-menu" id="headerMenu">
        <!-- Main Navigation -->
        <nav class="nav">
            <a href="home.php"     class="<?php echo ($current_page == 'home.php') ? 'active' : ''; ?>">Home</a>
            <a href="find.php"     class="<?php echo ($current_page == 'find.php') ? 'active' : ''; ?>">Find a Pro</a>
            <a href="home.php#how-it-works">How It Works</a>
            <a href="home.php#about">About</a>
        </nav>

        <!-- Right-side actions -->
        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="my_bookings.php" class="header-link">My Bookings</a>
            <?php } elseif (isset($_SESSION['tech_id'])) { ?>
                <a href="dashboard.php" class="header-link">Dashboard</a>
            <?php } elseif (isset($_SESSION['admin_id'])) { ?>
                <a href="admin_panel.php" class="header-link">Admin Panel</a>
            <?php } else { ?>
                <a href="login.php" class="header-link">Login</a>
                <a href="register_technician.php" class="btn btn-amber btn-sm">Join as Pro</a>
            <?php } ?>
        </div>
        </div>

        <div class="header-right">
            <?php if (isset($_SESSION['user_id'])) {
                $hdr_uid = (int)$_SESSION['user_id'];
                $hdr_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, profile_picture FROM users WHERE id = '$hdr_uid'"));
                $hdr_name = $hdr_row['name'] ?? ($_SESSION['user_name'] ?? 'User');
                $hdr_pic  = $hdr_row['profile_picture'] ?? '';
                ?>
                <a href="user_profile.php" class="header-avatar" title="<?php echo htmlspecialchars($hdr_name); ?>">
                    <?php if (!empty($hdr_pic)) { ?>
                        <img src="../<?php echo htmlspecialchars($hdr_pic); ?>" alt="">
                    <?php } else {
                        $parts = explode(' ', trim($hdr_name));
                        $ini = ''; foreach ($parts as $p) { if ($p !== '') $ini .= strtoupper(substr($p, 0, 1)); }
                        echo substr($ini, 0, 2);
                    } ?>
                </a>
            <?php } elseif (isset($_SESSION['tech_id'])) {
                $hdr_tid = (int)$_SESSION['tech_id'];
                $hdr_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, profile_picture FROM technicians WHERE id = '$hdr_tid'"));
                $hdr_name = $hdr_row['name'] ?? ($_SESSION['tech_name'] ?? 'Technician');
                $hdr_pic  = $hdr_row['profile_picture'] ?? '';
                ?>
                <a href="dashboard.php" class="header-avatar" title="<?php echo htmlspecialchars($hdr_name); ?>">
                    <?php if (!empty($hdr_pic)) { ?>
                        <img src="../<?php echo htmlspecialchars($hdr_pic); ?>" alt="">
                    <?php } else {
                        $parts = explode(' ', trim($hdr_name));
                        $ini = ''; foreach ($parts as $p) { if ($p !== '') $ini .= strtoupper(substr($p, 0, 1)); }
                        echo substr($ini, 0, 2);
                    } ?>
                </a>
            <?php } elseif (isset($_SESSION['admin_id'])) { ?>
                <a href="admin_panel.php" class="header-avatar" title="Admin">A</a>
            <?php } ?>

            <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>
        </div>

    </div>
</header>

<script>
(function() {
    var toggle = document.getElementById('navToggle');
    var menu   = document.getElementById('headerMenu');
    var header = document.querySelector('.header');

    if (!toggle || !menu) return;

    // Toggle mobile menu open/close
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        toggle.classList.toggle('open');
        menu.classList.toggle('open');
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-inner')) {
            toggle.classList.remove('open');
            menu.classList.remove('open');
        }
    });

    // Close menu when clicking a nav link (so it doesn't stay open after navigation)
    menu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            toggle.classList.remove('open');
            menu.classList.remove('open');
        });
    });

    // Scroll-hide behavior: hide header when scrolling down, show when scrolling up
    var lastY = window.pageYOffset;
    var ticking = false;

    window.addEventListener('scroll', function() {
        if (ticking) return;
        window.requestAnimationFrame(function() {
            var y = window.pageYOffset;

            // Only apply scroll-hide on mobile (<768px)
            if (window.innerWidth <= 768) {
                if (y > lastY && y > 80) {
                    // Scrolling down past threshold → hide
                    header.classList.add('header-hidden');
                    // Also close the menu if open
                    toggle.classList.remove('open');
                    menu.classList.remove('open');
                } else {
                    // Scrolling up → show
                    header.classList.remove('header-hidden');
                }
            } else {
                // Desktop: always show
                header.classList.remove('header-hidden');
            }

            lastY = y;
            ticking = false;
        });
        ticking = true;
    });
})();
</script>
