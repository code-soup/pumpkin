<header class="banner">

	<?php if ( has_nav_menu('nav_primary') ) : ?>
	    <nav class="navbar-main">
	    	<?php wp_nav_menu( ['theme_location' => 'nav_primary', 'menu_class' => 'nav nav-main', 'container' => false] ); ?>
	    </nav>
	<?php endif; ?>

	<?php if ( has_nav_menu('nav_offcanvas') ) : ?>
		<button type="button" class="navbar-toggle" data-toggle="offcanvas">
			<span class="hamburger"></span>
			<span class="sr-only">Toggle navigation</span>
		</button>
	<?php endif; ?>

</header>