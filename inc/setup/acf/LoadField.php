<?php

namespace CS\setup\acf;

if ( ! defined( 'ABSPATH' ) )
	exit;

use CS\utils\Helpers;

class LoadField {

	function register() {

		/**
		 * Populate Gravity Forms 'Select Form' ACF custom field
		 */
		add_filter('acf/load_field/name=gform_id', function ( $field ) {

			global $wpdb;

			if ( ! function_exists('gravity_form') )
				return $field;

			$forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gf_form" );

			if ( ! $forms )
				return $field;

			$field['choices'] = [];

			foreach ( $forms as $k => $v )
			{
				$field['choices'][ $v->id ] = apply_filters('the_title', $v->title);
			}

			return $field;
		});



		/**
		 * Populate 'Select Sidebar'
		 */
		add_filter('acf/load_field/name=select_sidebar', function ( $field ) {

			if ( ! Helpers::get_key('cs_sidebars') )
				return $field;

			$field['choices'] = [];

			foreach (Helpers:: get_key('cs_sidebars') as $sidebar )
			{
				$id = sanitize_title( $sidebar['sidebar_name'] );
				$field['choices'][$id] = $sidebar['sidebar_name'];
			}

			return $field;
		});
	}
}