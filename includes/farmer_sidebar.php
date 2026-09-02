<?php

/*
|--------------------------------------------------------------------------
| Farmer Dashboard Navigation
|--------------------------------------------------------------------------
*/

$current_page = basename($_SERVER['PHP_SELF']);

?>

<!-- =========================================================
     MOBILE DASHBOARD HEADER
     ========================================================= -->

<header class="farmer-mobile-header">

    <button
        type="button"
        class="farmer-menu-button"
        id="farmerMenuButton"
        aria-label="Open farmer navigation"
        aria-expanded="false"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>


    <a
        href="<?= e(FIH_BASE_URL) ?>/farmer/dashboard.php"
        class="farmer-mobile-brand"
    >
        <strong>FIH</strong>
        <span>Farmer</span>
    </a>


    <div class="farmer-mobile-spacer"></div>

</header>


<!-- =========================================================
     FARMER SIDEBAR
     ========================================================= -->

<aside
    class="farmer-sidebar"
    id="farmerSidebar"
>

    <!-- Sidebar header -->

    <div class="farmer-sidebar-header">

        <a
            href="<?= e(FIH_BASE_URL) ?>/farmer/dashboard.php"
            class="farmer-sidebar-brand"
        >

            <span class="farmer-brand-name">
                FIH
            </span>

            <span class="farmer-brand-label">
                Farmer Dashboard
            </span>

        </a>


        <!-- Mobile close button -->

        <button
            type="button"
            class="farmer-sidebar-close"
            id="farmerSidebarClose"
            aria-label="Close navigation"
        >
            &times;
        </button>

    </div>


    <!-- Navigation -->

    <nav
        class="farmer-sidebar-nav"
        aria-label="Farmer dashboard navigation"
    >

        <!-- Overview -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                Overview
            </span>


            <a
                href="<?= e(FIH_BASE_URL) ?>/farmer/dashboard.php"
                class="sidebar-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">🏠</span>

                <span>Dashboard</span>

            </a>

        </div>


        <!-- My Farm -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                My Farm
            </span>


            <a
                href="<?= e(FIH_BASE_URL) ?>/farmer/profile.php"
                class="sidebar-link <?= $current_page === 'profile.php' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">👤</span>

                <span>My Profile</span>

            </a>


            <a
                href="<?= e(FIH_BASE_URL) ?>/farmer/farms.php"
                class="sidebar-link <?= $current_page === 'farms.php' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">🌾</span>

                <span>My Farms</span>

            </a>
<a 
    href="<?= e(FIH_BASE_URL) ?>/farmer/crop-health.php"
    class="sidebar-link <?= $current_page === 'crop-health.php' ? 'active' : '' ?>"
>
    <span class="sidebar-icon">🌿</span>
    <span>Crop Health</span>
</a>
<a
    href="<?= e(FIH_BASE_URL) ?>/farmer/harvests.php"
    class="sidebar-link <?= $current_page === 'harvests.php' ? 'active' : '' ?>"
>
   <span class="sidebar-icon">🌾</span>
   <span>Harvest Reports</span>
</a>
        </div>


        <!-- Intelligence -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                Intelligence
            </span>


            <a
                href="<?= e(FIH_BASE_URL) ?>/farmer/weather.php"
                class="sidebar-link <?= $current_page === 'weather.php' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">🌦️</span>

                <span>Weather</span>

            </a>


            <a
                href="#"
                class="sidebar-link sidebar-coming-soon"
            >

                <span class="sidebar-icon">💧</span>

                <span>Water</span>

            </a>


<a
    href="<?= e(FIH_BASE_URL) ?>/farmer/crops.php"
    class="sidebar-link <?= $current_page === 'crops.php' ? 'active' : '' ?>"
>

    <span class="sidebar-icon">
        🌱
    </span>

    <span>
        Crops
    </span>

</a>


            <a
                href="#"
                class="sidebar-link sidebar-coming-soon"
            >

                <span class="sidebar-icon">🐄</span>

                <span>Livestock</span>

            </a>


            <a
                href="#"
                class="sidebar-link sidebar-coming-soon"
            >

                <span class="sidebar-icon">⚠️</span>

                <span>Alerts</span>

            </a>

        </div>


        <!-- Account -->

        <div class="sidebar-section">

            <span class="sidebar-section-title">
                Account
            </span>


            <a
                href="<?= e(FIH_BASE_URL) ?>/farmer/change-password.php"
                class="sidebar-link <?= $current_page === 'change-password.php' ? 'active' : '' ?>"
            >

                <span class="sidebar-icon">🔐</span>

                <span>Change Password</span>

            </a>

        </div>

    </nav>


    <!-- Logout -->

    <div class="farmer-sidebar-footer">

        <form
            method="POST"
            action="<?= e(FIH_BASE_URL) ?>/logout.php"
        >

            <?= csrf_field() ?>

            <button
                type="submit"
                class="sidebar-logout"
            >

                <span class="sidebar-icon">
                    🚪
                </span>

                <span>
                    Logout
                </span>

            </button>

        </form>

    </div>

</aside>


<!-- =========================================================
     MOBILE OVERLAY
     ========================================================= -->

<div
    class="farmer-sidebar-overlay"
    id="farmerSidebarOverlay"
>
</div>
<script
    src="<?= e(FIH_BASE_URL) ?>/assets/js/global.js"
></script>