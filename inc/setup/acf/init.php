<?php

namespace cs\acf\init;

if ( ! defined( 'ABSPATH' ) )
    exit;


define( 'CS_ACF_PATH',  'wp-content/plugins/advanced-custom-fields-pro/' );
define( 'CS_ACF_ABSPATH', trailingslashit( get_template_directory() ) . CS_ACF_PATH );
define( 'CS_ACF_JSON_PATH',  get_stylesheet_directory() . '/inc/setup/acf/json' );

/**
 * Include ACF
 */
if ( ! class_exists('ACF') && file_exists( CS_ACF_ABSPATH . 'acf.php' ) ) :

    // Include ACF PRO from theme folder
    require_once CS_ACF_ABSPATH . 'acf.php';


    // Tweak paths
    add_filter('acf/settings/dir', function ($dir) {
        return trailingslashit( get_template_directory_uri() ) . CS_ACF_PATH;
    });

    add_filter('acf/settings/path', function ($path) {
        return CS_ACF_ABSPATH;
    });


    // Disable ACF menu if not in a development env
    if ( WP_ENV !== 'development' ) {
        add_filter('acf/settings/show_admin', '__return_false');
    }

endif;




/**
 * Google Maps ACF API
 */
add_action( 'acf/init', function () {

    $key = get_key('cs_gmaps_api');
    acf_update_setting('google_api_key', $key);
});




/**
 * Save / Load fields from JSON
 */
add_filter('acf/settings/save_json', function ( $path ) {

    return CS_ACF_JSON_PATH;
});



add_filter('acf/settings/load_json', function ( $paths ) {

    unset($paths[0]);

    $paths[] = CS_ACF_JSON_PATH;

    return $paths;
});
