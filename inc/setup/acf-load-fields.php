<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Populate Gravity Forms 'Select Form' ACF custom field
 */
add_filter('acf/load_field/name=gform_id', function ( $field ) {

	global $wpdb;


	$field['choices'] = [];

	$forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rg_form" );

	if ( ! $forms ) :
		foreach ( $forms as $k => $v ) {
			$field['choices'][ $v->id ] = apply_filters('the_title', $v->title);
		}
	endif;

	return $field;
});




/**
 * Populate 'Select Sidebar'
 */
add_filter('acf/load_field/name=select_sidebar', function ( $field ) {

	$field['choices'] = [];

	if ( get_key('cs_sidebars') ) :
		foreach ( get_key('cs_sidebars') as $sidebar ) :

			$id = sanitize_title( $sidebar['sidebar_name'] );
			$field['choices'][$id] = $sidebar['sidebar_name'];

		endforeach;
	endif;

	return $field;
});