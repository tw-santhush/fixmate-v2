<?php
include '../includes/config.php';
include '../includes/header.php';
?>

<section class="not-found-page">

    <!-- ============ MAIN CONTENT ============ -->
    <div class="nf-content">

        <!-- Circular stamp -->
        <div class="nf-stamp">
            <div class="nf-stamp-circle">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="nf-wrench">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
            </div>
        </div>

        <!-- Giant 404 -->
        <div class="nf-number-wrap">
            <div class="nf-number">404</div>
            <div class="nf-number-rule">
                <span class="nf-rule-line"></span>
                <span class="nf-rule-dot"></span>
                <span class="nf-rule-line"></span>
            </div>
        </div>

        <!-- Heading + description -->
        <div class="nf-copy">
            <h1>Page not found</h1>
            <p>
                This page doesn't exist or has been moved.
            </p>
        </div>

        <!-- Action buttons -->
        <div class="nf-actions">
            <a href="home.php" class="btn btn-teal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Back to home
            </a>
            <a href="find.php" class="btn btn-ghost">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Find a tradesman
            </a>
        </div>

        <!-- Support contact -->
        <div class="nf-support">
            <div class="nf-support-line">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Need help?
            </div>
            <a href="mailto:support@fixmate.lk" class="nf-support-link">
                CONTACT SUPPORT@FIXMATE.LK
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    </div>

    <!-- ============ MINI FOOTER (specific to 404) ============ -->
    <div class="nf-footer">
            <p>© 2026 FixMate Sri Lanka</p>
    </div>

</section>

</body>
</html>