<?php

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'CodeSoup_ACF_Options_Location' ) ) :

/**
 * ACF Location Rule for Pumpkin Options by Page Template
 *
 * Allows ACF field groups to be assigned to specific pumpkin_options posts
 * based on page template names (portable across domains).
 */
class CodeSoup_ACF_Options_Location extends ACF_Location {

	/**
	 * Initialize the location rule
	 */
	public function initialize() {
		$this->name        = 'pumpkin_options_template';
		$this->label       = __( 'Pumpkin Options - Page Template', 'pumpkin' );
		$this->category    = 'Pumpkin Options';
		$this->object_type = 'page';
	}

	/**
	 * Get available choices for this location rule
	 *
	 * @param array $rule The location rule.
	 * @return array
	 */
	public function get_values( $rule ) {
		// Get template options from ThemeSetup via filter
		$template_options = apply_filters( 'pumpkin_template_options', [] );

		if ( empty( $template_options ) ) {
			return [];
		}

		$choices = array();

		// Build choices from registered template options
		foreach ( $template_options as $template_name => $display_name ) {
			$choices[ $template_name ] = $display_name;
		}

		return $choices;
	}

	/**
	 * Match the location rule against the current screen
	 *
	 * @param array $rule The location rule.
	 * @param array $screen The current screen.
	 * @param array $field_group The field group.
	 * @return bool
	 */
	public function match( $rule, $screen, $field_group ) {

		// Only process rules for our location type
		if ( ! isset( $rule['param'] ) || $rule['param'] !== 'pumpkin_options_template' ) {
			return false;
		}

		// Ensure rule is properly formatted
		if ( ! is_array( $rule ) || ! isset( $rule['value'] ) || ! isset( $rule['operator'] ) ) {
			return false;
		}

		// Ensure screen is properly formatted
		if ( ! is_array( $screen ) ) {
			return false;
		}

		if ( ! isset( $screen['post_id'] ) && ! isset( $screen['post_type'] ) ) {
			return false;
		}

		if ( $screen['post_type'] != 'pumpkin_options' ) {
			return false;
		}

		// Get template name from post meta (portable across domains)
		$post_id = $screen['post_id'];
		$template_name = get_post_meta( $post_id, '_pumpkin_template_name', true );

		// Use template name if available, fallback to post ID for legacy posts
		$value = $template_name ?: $post_id;

		$rule  = wp_parse_args(
			$this->extract_location_rules( $field_group['location'] ),
			[
				'value' => '',
				'operator' => '',
			]
		);


		return $this->compare_to_rule( $value, $rule );
	}

	/**
	 * Get available operators for this location rule
	 *
	 * @param array $rule The location rule.
	 * @return array
	 */
	public static function get_operators( $rule ) {
		return array(
			'==' => __( 'is equal to', 'acf' ),
		);
	}

	/**
	 * Extract location rules from field group location array
	 *
	 * @param array $location_array The field group location array
	 * @return array
	 */
	public static function extract_location_rules( $location_array, $depth = 0 ) {
		if ( $depth > 6 ) {
			return [];
		}

		if ( isset( $location_array['param'] ) && isset( $location_array['value'] ) ) {
			return $location_array;
		}

		if ( is_array( $location_array ) ) {
			foreach ( $location_array as $item ) {
				$result = self::extract_location_rules( $item, $depth + 1 );
				if ( ! empty( $result ) ) {
					return $result;
				}
			}
		}

		return [];
	}
}

endif;
