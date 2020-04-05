<?php if ( ! defined('ABSPATH') ) exit;


if ( ! defined('WP_ENV') )
    define('WP_ENV', 'production');


/**
 * Load Composer
 */
if ( locate_template('vendor/autoload.php') )
	require_once locate_template('vendor/autoload.php');


/**
 * register all classes from namespace
 */
if ( class_exists('CS\Init') ) {
	CS\init::register_services();
}


/**
 * Custom Post Types folder using Sober/models
 *
 * @link( https://github.com/soberwp/models, documentation )
 */
add_filter('sober/models/path', function() {

    return trailingslashit( get_stylesheet_directory() ) . 'inc/models';
});


/**
 * Theme includes
 */
$includes = [
    // 'utils/helpers',
	// 'utils/assets',
	// 'utils/wrapper',
	// 'setup/class.cs',
	// 'setup/acf-options',
	// 'setup/acf-load-fields',
	// 'setup/acf-widget',
    'wp-mods/wp-admin', // only filters and actions
    'wp-mods/wp-cleanup', // only filters and actions
    'wp-mods/wp-core', // only filters and actions
    'wp-mods/wp-login', // only filters and actions
	// 'components/class.functions',
	'components/class.ajax', // to be removed?
	// 'components/class.user', // to be removed?
	'plugin-mods/gravity-forms', // only filters and actions
];



// Woocommerce
if ( function_exists('is_woocommerce') )
	$includes[] = 'plugin-mods/woocommerce/tweaks';



foreach ( $includes as $file ) {

    if ( locate_template('inc/' . $file . '.php') ) {
	   require_once locate_template('inc/' . $file . '.php');
    }
    else {
        echo $file . ' file is missing !';
    }
}
