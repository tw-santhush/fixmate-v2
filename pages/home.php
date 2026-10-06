<?php
include '../includes/config.php';

// Live stats from the database
$tech_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM technicians WHERE status = 'verified'"))['c'];
$dist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM districts"))['c'];


$services = [
    1  => ['name' => 'Plumber',            'image' => 'Plumber.jpg',             'bg' => '#E0F7F4'],
    2  => ['name' => 'Electrician',        'image' => 'Electrician.jpg',         'bg' => '#FFF3D6'],
    3  => ['name' => 'Carpenter',          'image' => 'Carpenter.jpg',           'bg' => '#FED7AA'],
    4  => ['name' => 'Mechanic',           'image' => 'Mechanic.jpg',            'bg' => '#E0F7F4'],
    5  => ['name' => 'Painter',            'image' => 'Painter.jpg',             'bg' => '#A7F3D0'],
    6  => ['name' => 'Welder',             'image' => 'Welder.jpg',              'bg' => '#FDE68A'],
    7  => ['name' => 'Mason',              'image' => 'Mason.jpg',               'bg' => '#99F6E4'],
    8  => ['name' => 'Locksmith',          'image' => 'Locksmith.jpg',           'bg' => '#FEF3C7'],
    9  => ['name' => 'Appliance Repair',   'image' => 'Appliance_Repair.jpg',    'bg' => '#BAE6FD'],
    10 => ['name' => 'AC Technician',      'image' => 'AC_Technician.jpg',       'bg' => '#FDE68A'],
    11 => ['name' => 'Phone Repair',       'image' => 'Phone_Repair.jpg',        'bg' => '#BBF7D0'],
    12 => ['name' => 'Computer Tech',      'image' => 'Computer_Tech.jpg',       'bg' => '#E0F2FE'],
    13 => ['name' => 'Plumbing & Gas',     'image' => 'Plumbing_Gas.jpg',        'bg' => '#A5F3FC'],
    14 => ['name' => 'Electrical & Solar', 'image' => 'Electrical_&_Solar.jpg',  'bg' => '#FED7AA'],
    15 => ['name' => 'General Repairs',    'image' => 'General_Repairs.jpg',     'bg' => '#CCFBF1'],
];

include '../includes/header.php';
?>

<!-- ============ HERO SECTION ============ -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">

            <!-- LEFT: Headline + Search -->
            <div>
                <h1>
                    The <span class="teal">Smart</span> Way to Find a
                    <span class="amber-pill">Service Pro</span>
                </h1>

                <p class="lede">
                    Browse verified local plumbers, electricians, carpenters &amp; technicians — with direct phone numbers, transparent call-out rates &amp; genuine community ratings across Sri Lanka.
                </p>

                <!-- Mad-libs search -->
                <div class="search-card">

                    <div class="search-sentence">
                        <span class="search-word">I need a</span>

                        <span class="search-pill-wrap" data-label="Service">
                            <select id="homeTrade" class="search-select amber">
                                <option value="1">Plumber</option>
                                <option value="2">Electrician</option>
                                <option value="3">Carpenter</option>
                                <option value="4">Mechanic</option>
                                <option value="5">Painter</option>
                                <option value="6">Welder</option>
                                <option value="7">Mason</option>
                                <option value="8">Locksmith</option>
                                <option value="9">Appliance Repair</option>
                                <option value="10">AC Technician</option>
                                <option value="11">Phone Repair</option>
                                <option value="12">Computer Tech</option>
                                <option value="13">Plumbing &amp; Gas</option>
                                <option value="14">Electrical &amp; Solar</option>
                                <option value="15">General Repairs</option>
                            </select>
                        </span>

                        <span class="search-word">in</span>

                        <!-- Searchable city picker -->
                        <span class="search-pill-wrap" id="districtPicker" data-label="City / District">
                            <button type="button" class="search-pill-btn teal" onclick="toggleDistrictPanel(event)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span id="districtLabel">Any city</span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>

                            <input type="hidden" id="selectedDistrictId" value="">
                            <input type="hidden" id="selectedCityName" value="">

                            <div class="search-dropdown" id="districtDropdown">
                                <div class="search-dropdown-input">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                    </svg>
                                    <input type="text" id="districtSearchInput" placeholder="Search city or town..." oninput="filterDistricts()" autocomplete="off">
                                </div>

                                <div class="search-dropdown-list" id="districtList">
                                    <button type="button" class="search-dropdown-item" data-name="Any city" onclick="pickCity('', 'Any city')">
                                        Any city
                                    </button>

                                    <?php
                                    $cities_query = mysqli_query($conn,
                                        "SELECT c.id, c.name, c.district_id, d.district_name
                                        FROM cities c
                                        JOIN districts d ON c.district_id = d.id
                                        ORDER BY d.district_name ASC, c.name ASC");

                                    $current_district = '';
                                    while ($c = mysqli_fetch_assoc($cities_query)) {
                                        if ($c['district_name'] !== $current_district) {
                                            $current_district = $c['district_name'];
                                            echo '<div class="search-dropdown-group">' . htmlspecialchars($current_district) . '</div>';
                                        }
                                        echo '<button type="button" class="search-dropdown-item" '
                                        . 'data-name="' . htmlspecialchars($c['name']) . '" '
                                        . 'onclick="pickCity(' . (int)$c['district_id'] . ', \'' . addslashes($c['name']) . '\')">'
                                        . htmlspecialchars($c['name'])
                                        . '</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </span>

                        <span class="search-word">today.</span>
                    </div>

                    <div class="search-actions">
                        <span class="search-hint">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2F8F5B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            Direct quotes · No agency fee
                        </span>

                        <button type="button" onclick="goSearch()" class="btn btn-ink">
                            Search Directory
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>

            <!-- RIGHT: Photo collage -->
            <div class="hero-photos">
                <img class="tall"
                     src="../images/hero-tall.jpg"
                     alt="Sri Lankan tradesman at work">
                <img class="short"
                     src="../images/hero-short-1.jpg"
                     alt="Electrical repair work">
                <img class="short"
                     src="../images/hero-short-2.jpg"
                     alt="Carpenter at work">
            </div>

        </div>
    </div>
</section>


<!-- ============ BROWSE SERVICES ============ -->
<section class="section browse" id="services">
    <div class="container">

        <div class="section-head">
            <h2>Browse Services by Category</h2>
            <p>Find certified local tradesmen for household repairs, commercial fitting &amp; urgent maintenance.</p>
        </div>

        <div class="services-grid">
            <?php
            $number = 1;
            foreach ($services as $id => $svc) {
                $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM technicians WHERE service_id = $id AND status = 'verified'"))['c'];
            ?>
                <a href="find.php?service=<?php echo $id; ?>" class="service-tile">
                    <span class="number"><?php echo str_pad($number, 2, '0', STR_PAD_LEFT); ?></span>

                    <div class="service-image">
                        <img src="../images/<?php echo htmlspecialchars($svc['image']); ?>" alt="<?php echo htmlspecialchars($svc['name']); ?>" loading="lazy">
                    </div>

                    <div class="service-tile-body">
                        <h3><?php echo $svc['name']; ?></h3>
                        <div class="count"><?php echo $count; ?> pros listed</div>
                    </div>
                </a>
            <?php
                $number++;
            }
            ?>
        </div>

    </div>
</section>


<!-- ============ HOW IT WORKS ============ -->
<section class="section" id="how-it-works" style="background-color: var(--bg-soft);">
    <div class="container">

        <div class="section-head">
            <h2>How FixMate Works</h2>
            <p>Connecting you with verified Sri Lankan craftsmen in four seamless steps.</p>
        </div>

        <div class="how-grid">

            <div class="how-card step-1">
                <span class="step-tag">Step 01 / 04</span>
                <div class="how-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <h3>Choose Your Town</h3>
                <p>Filter across <?php echo $dist_count; ?> Sri Lankan districts — from Colombo to Jaffna.</p>
            </div>

            <div class="how-card step-2">
                <span class="step-tag">Step 02 / 04</span>
                <div class="how-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <h3>Compare &amp; Select</h3>
                <p>Browse transparent rates, past portfolios, and verified NIC credentials.</p>
            </div>

            <div class="how-card step-3">
                <span class="step-tag">Step 03 / 04</span>
                <div class="how-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <h3>Direct Connection</h3>
                <p>Call or WhatsApp the craftsman directly. Zero middleman commissions.</p>
            </div>

            <div class="how-card step-4">
                <span class="step-tag">Step 04 / 04</span>
                <div class="how-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </div>
                <h3>Rate &amp; Support</h3>
                <p>Pay directly, then share your honest review to help fellow homeowners.</p>
            </div>

        </div>
    </div>
</section>

<!-- ============ ABOUT SECTION ============ -->
<section class="section about" id="about">
    <div class="container">

        <div class="about-grid">

            <!-- Left: Text -->
            <div class="about-text">
                <h2>Bringing Sri Lanka's Handymen Online</h2>

                <p>
                    In Sri Lanka, most local tradesmen still rely on word-of-mouth and paper posters
                    pasted on walls and telephone poles. Customers struggle to find trusted local
                    services. FixMate is a simple digital directory that changes that — for free.
                </p>

                <p>
                    No booking fees, no commissions, no accounts needed. Just a searchable list of
                    real tradesmen with their phone numbers and community ratings.
                </p>

                <!-- Stats row -->
                <div class="about-stats">
                    <div>
                        <div class="about-stat-number"><?php echo $tech_count; ?>+</div>
                        <div class="about-stat-label">Registered Pros</div>
                    </div>
                    <div>
                        <div class="about-stat-number"><?php echo $dist_count; ?></div>
                        <div class="about-stat-label">Districts Covered</div>
                    </div>
                    <div>
                        <div class="about-stat-number">100%</div>
                        <div class="about-stat-label">Free to Use</div>
                    </div>
                </div>
            </div>

            <!-- Right: Image -->
            <div class="about-image">
                <img src="../images/handyman.jpg" alt="Sri Lankan handyman at work">
            </div>

        </div>

    </div>
</section>

<!-- ============ GROW YOUR BUSINESS ============ -->
<section class="grow" id="grow">
    <div class="container">

        <h2>Grow Your Business with FixMate</h2>
        <p class="intro">
            Join Sri Lanka's most respected craftsman directory. Get discovered by homeowners, offices, and landlords without giving away commissions.
        </p>

        <div class="grow-cards">

            <div class="grow-card">
                <div class="icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                        <circle cx="12" cy="12" r="2"/>
                    </svg>
                </div>
                <h3>Get Paid Directly</h3>
                <p>Customers settle with you directly via cash or bank transfer on job completion. We take 0% of your earnings.</p>
                <div class="tag">Zero Platform Cut</div>
            </div>

            <div class="grow-card">
                <div class="icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <h3>Jobs in Your Area</h3>
                <p>Set your active radius. Don't waste fuel travelling across town — take repair calls within your neighborhood.</p>
                </div>

            <div class="grow-card">
                <div class="icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <h3>Verified Reputation</h3>
                <p>Get your NIC verified to earn the FixMate verified badge on your profile.</p>
                </div>

        </div>

        <a href="register_technician.php" class="btn btn-amber" style="min-height: 56px; padding: 0 40px; font-size: 16px;">
            Register as a Craftsman — It's Free
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </a>

    </div>
</section>


<?php include '../includes/footer.php'; ?>

<script>
function goSearch() {
    var trade = document.getElementById('homeTrade').value;
    var district = document.getElementById('selectedDistrictId').value;
    var cityName = document.getElementById('selectedCityName').value;

    var url = 'find.php?service=' + trade;
    if (district) url += '&district=' + district;
    if (cityName) url += '&area_name=' + encodeURIComponent(cityName);
    window.location.href = url;
}

function toggleDistrictPanel(e) {
    e.stopPropagation();
    var dropdown = document.getElementById('districtDropdown');
    dropdown.classList.toggle('open');

    if (dropdown.classList.contains('open')) {
        document.getElementById('districtSearchInput').focus();
        document.getElementById('districtSearchInput').value = '';
        filterDistricts();
    }
}

function filterDistricts() {
    var q = document.getElementById('districtSearchInput').value.toLowerCase();
    var list = document.getElementById('districtList');
    var items = list.querySelectorAll('.search-dropdown-item');

    // Show/hide items
    items.forEach(function (item) {
        var name = item.getAttribute('data-name').toLowerCase();
        item.style.display = name.includes(q) ? '' : 'none';
    });

    // Hide group headers whose items are all hidden
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

    // Reset scroll so top results are visible
    list.scrollTop = 0;
}

function pickCity(districtId, cityName) {
    document.getElementById('selectedDistrictId').value = districtId;
    document.getElementById('districtLabel').textContent = cityName;
    if (districtId === '' || districtId === 'all' || cityName === 'Any city') {
        document.getElementById('selectedCityName').value = '';
    } else {
        document.getElementById('selectedCityName').value = cityName;
    }
    document.getElementById('districtDropdown').classList.remove('open');
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    if (!e.target.closest('#districtPicker')) {
        document.getElementById('districtDropdown').classList.remove('open');
    }
});
</script>