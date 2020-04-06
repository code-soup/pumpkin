<?php if ( ! defined('ABSPATH') ) exit;


/**
 * Set WordPress environment to production if not otherwise defined
 * WP_ENV is usually defined in the wp-config.php file located in the wordpress root
 * This will affect some development settings
 * i.e. weather the Custom Fields menu item will show up in backend or not (will not show up in production environment)
 */
if ( ! defined('WP_ENV') )
    define('WP_ENV', 'production');


/**
 * Load Composer
 * for Namespacing and Autoloading functionality
 */
if ( locate_template('vendor/autoload.php') )
	require_once locate_template('vendor/autoload.php');


/**
 * Theme includes
 * Register all required classes from namespace
 */
if ( class_exists('CS\Init') ) {
	CS\init::register_services();
}


/**
 * Set Custom Post Types file location using Sober/models
 * WordPress plugin to create custom post types and taxonomies using JSON, YAML or PHP files
 * Theme uses models.json file located in the directory set in the filter below
 *
 * @link( https://github.com/soberwp/models, documentation )
 */
add_filter('sober/models/path', function() {

    return trailingslashit( get_stylesheet_directory() ) . 'inc/models';
});


/**
 * Debug
 * print_r with <pre> tags for readable output
 */
if ( ! function_exists('printaj')) :

	function printaj( $var, $return = false ) {
		print_r('<pre>');
		print_r($var, $return);
		print_r('</pre>');
	}

endif;


/**
 * Debug
 * var_dump with <pre> tags for readable output
 */
if ( ! function_exists('dumpaj')) :

	function dumpaj( $var, $return = false ) {
		var_dump('<pre>');
		var_dump($var, $return);
		var_dump('</pre>');
	}
endif;