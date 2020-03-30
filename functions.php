<?php if ( ! defined('ABSPATH') ) exit;


if ( ! defined('WP_ENV') )
    define('WP_ENV', 'production');


define( 'CS_ACF_PATH',  'wp-content/plugins/advanced-custom-fields-pro/' );
define( 'CS_ACF_ABSPATH', trailingslashit( get_template_directory() ) . CS_ACF_PATH );

/**
 * Include ACF
 */
if ( ! class_exists('ACF') && file_exists( CS_ACF_ABSPATH . 'acf.php' ) ):

    require_once CS_ACF_ABSPATH . 'acf.php';

    // Tweak paths
    add_filter('acf/settings/dir', function ($dir) {
        return trailingslashit( get_template_directory_uri() ) . CS_ACF_PATH;
    });

    add_filter('acf/settings/path', function ($path) {
        return CS_ACF_ABSPATH;
    });

    // Disable ACF menu if not in a development env
    if (WP_ENV !== 'development')
        add_filter('acf/settings/show_admin', '__return_false');

endif;


/**
 * Load Composer
 */
if ( locate_template('vendor/autoload.php') )
	require_once locate_template('vendor/autoload.php');



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
    'utils/helpers',
	'utils/assets',
	'utils/wrapper',
	'setup/class.cs',
	'setup/acf-options',
	'setup/acf-load-fields',
	'setup/acf-widget',
    'wp-mods/wp-admin',
    'wp-mods/wp-cleanup',
    'wp-mods/wp-core',
    'wp-mods/wp-login',
	// 'components/class.functions',
	'components/class.ajax',
	'components/class.user',
	'plugin-mods/gravity-forms',
];


// Woocommerce
if ( function_exists('is_woocommerce') )
	$includes[] = 'plugin-mods/woocommerce/tweaks';



foreach ( $includes as $file ) {

    if ( locate_template('inc/' . $file . '.php') ) {
	   require_once locate_template('inc/' . $file . '.php');
    }
    else {
        echo $file;
    }
}
