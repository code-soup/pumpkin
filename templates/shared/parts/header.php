<header class="website-header">
    <div class="container">
        <div class="span-logo">
            <?php echo \CodeSoup\Pumpkin\Components\WebsiteLogo\Component::render(); ?>
        </div>

        <div class="span-nav">
            <input type="checkbox" id="mobile-menu-toggle" class="checkbox-menu-toggle" aria-label="Toggle mobile menu">

            <label for="mobile-menu-toggle" class="button-menu-toggle">
                <span class="menu">Menu</span>
                <span class="hamburger-icon">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </span>
            </label>

            <nav class="navbar-mobile">
                <?php wp_nav_menu([
                    'theme_location' => 'mobile',
                    'menu_class'     => 'nav nav-mobile',
                    'container'      => false,
                ]); ?>
            </nav>

            <nav class="navbar-main">
                <?php wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class'     => 'nav nav-main',
                    'container'      => false,
                ]); ?>
            </nav>
        </div>
    </div>
</header>