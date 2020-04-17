<?php

namespace CS\Setup\ACF;

if ( ! defined( 'ABSPATH' ) )
	exit;

use CS\Utils\Helpers;

class Options {

	function register () {

		/**
		 * Option pages
		 */
		if ( function_exists('acf_add_options_page') ) :

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

		endif;



		/**
		 * Google Maps ACF API
		 */
		add_action('acf/init', function () {

			$key = Helpers::get_key('cs_gmaps_api');
			acf_update_setting('google_api_key', $key);
		});




		/**
		 * Save / Load fields from JSON
		 */
		add_filter('acf/settings/save_json', function ( $path ) {

			return get_stylesheet_directory() . '/inc/setup/acf/json';
		});

		add_filter('acf/settings/load_json', function ( $paths ) {

			unset($paths[0]);

			$paths[] = get_stylesheet_directory() . '/inc/setup/acf/json';

			return $paths;
		});
	}
}