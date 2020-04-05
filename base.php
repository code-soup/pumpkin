<?php


use CS\Setup;
use CS\Utils;
use CS\Components\Functions;

?>

<!doctype html>
<html <?php language_attributes(); ?>>
<?php get_template_part('templates/partials/head'); ?>
	<body <?php body_class(); ?>>

	<?php

	do_action('get_header');
	get_template_part('templates/partials/header');

	if ( has_nav_menu( 'nav_offcanvas' ) ) : ?>
		<nav class="sidebar-offcanvas">
			<div class="menu-wrap">

				<?php wp_nav_menu([
					'theme_location' => 'nav_offcanvas',
					'menu_class'     => 'nav nav-offcanvas'
				]); ?>

			</div>
		</nav>
	<?php endif; ?>

	<div class="row-offcanvas right">
		<div class="wrap">

			<main class="main">
				<?php include Utils\template_path(); ?>
			</main>

			<?php if ( Functions::get_sidebar() ) : ?>
				<aside class="sidebar">
					<?php get_template_part('templates/partials/sidebar'); ?>
				</aside>
			<?php endif; ?>
		</div>

		<?php get_template_part('templates/partials/footer'); ?>
	</div>

	<?php wp_footer(); ?>
	</body>
</html>