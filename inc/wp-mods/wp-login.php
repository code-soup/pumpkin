<?php if (!defined('ABSPATH')) exit;



/**
 * Change admin footer text
 */
add_filter('admin_footer_text', function () {
	echo get_bloginfo( 'site_title' );
});



/**
 * WP Login Logo URL
 */
add_filter('login_headerurl', function () {
	return get_bloginfo( 'url' );
});



/**
 * WP Login Logo title
 */
add_filter('login_headertitle', function () {
	return get_bloginfo( 'title' );
});


/**
 * Repace WP Login style
 */
add_action( 'login_enqueue_scripts', function () {

	$logo = CS\Components\Functions::get_logo(true); ?>

	<style type="text/css">
		body.login {
			background: #efefef;
		}

		#loginform {
			box-shadow: 0px 2px 10px 2px rgba(black, 0.15);
			border-radius: 5px;
		}

		.login #nav, .login #backtoblog {
			margin: 10px 0;
			padding: 0
		}

		.login #nav {
			float: right;
		}

		.login #backtoblog {
			display: none;
		}

		body.login div#login h1 a {
			width: calc(100% - 20px);
			height: 120px;
			margin: 0 auto;
			display: block;
			padding: 10px 10px 30px;
			background-size: contain;
			<?php if($logo): ?>
			background-image: url('<?= $logo; ?>');
			<?php endif; ?>
		}
	</style>
<?php });