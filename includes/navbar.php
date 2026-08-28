<link rel="stylesheet" href="<?= e(FIH_BASE_URL) ?>/assets/css/style.css">
    <header class="site-header">

    <nav class="site-navbar">

        <!-- FIH Brand -->
        <a
            href="<?= e(FIH_BASE_URL) ?>/index.php"
            class="navbar-brand"
        >
            <span class="brand-name">FIH</span>
            <span class="brand-tagline">
                Food &amp; Water Intelligence
            </span>
        </a>


        <!-- Mobile menu button -->
        <button
            type="button"
            class="navbar-toggle"
            id="navbarToggle"
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>


        <!-- Navigation -->
        <div
            class="navbar-menu"
            id="navbarMenu"
        >

            <a
                href="<?= e(FIH_BASE_URL) ?>/login.php"
                class="navbar-link"
            >
                Login
            </a>


            <a
                href="<?= e(FIH_BASE_URL) ?>/register.php"
                class="navbar-button"
            >
                Create Account
            </a>

        </div>

    </nav>

</header>