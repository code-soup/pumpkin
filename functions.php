<?php
// functions.php

// Don't allow direct access to file
defined('ABSPATH') || die;

// Load Composer's autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    add_filter( 'acf_admin_categories_plugin_dir_url', function( $base_url ) {

        return sprintf(
            '%s/vendor/acf-admin-categories',
            get_stylesheet_directory_uri()
        );
    });

    require_once __DIR__ . '/vendor/acf-admin-categories/index.php';
}

\CodeSoup\Pumpkin\Core\Bootstrap::get_instance();

/**
 * Centralized initialization function for the theme
 * 
 * Handles the proper initialization sequence:
 * 1. Load page config first
 * 2. Then initialize Bootstrap
 * 
 * This prevents multiple initializations and ensures proper configuration order
 */

// Run initialization on template_redirect (after WordPress has determined the template)
add_action('template_redirect', function() {
    // Use static variable to track initialization state
    static $initialized = false;
    
    // Only run once
    if ($initialized) {
        return;
    }
    
    // Only run in frontend
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    
    // Skip in autosave or cron
    if (defined('DOING_AUTOSAVE') || defined('DOING_CRON')) {
        return;
    }
    
    // First, load page configuration
    $template_loader = \CodeSoup\Pumpkin\WpMods\TemplateLoader::get_instance();
    
    // Load page config using template loader's method 
    $template_loader->set_page_config();
    
    // Then initialize Bootstrap (which will use the config we just loaded)
    \CodeSoup\Pumpkin\Core\Bootstrap::get_instance();
    
    // Mark as initialized
    $initialized = true;
}, 5);

// add_filter( 'auto_update_plugin', '__return_false' );

// Include plugin mods for admin 
include 'includes/plugin-mods/index.php';