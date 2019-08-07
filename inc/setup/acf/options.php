<?php

namespace cs\acf\options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Options page
 */
if ( ! function_exists('acf_add_options_page') ) {
    return;
}

acf_add_options_page([
	'page_title' => 'Theme Options',
	'menu_title' => 'Theme Options',
	'menu_slug'  => 'theme-options',
	'capability' => 'manage_options',
	'redirect'   => true
]);

acf_add_options_sub_page([
	'title'  => 'Settings',
	'parent' => 'theme-options',
]);