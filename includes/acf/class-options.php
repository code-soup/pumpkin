<?php

namespace CodeSoup\Pumpkin\ACF;

use CodeSoup\Pumpkin\Utils\AssetLoader;
use CodeSoup\Pumpkin\Utils\TemplateUtilities;
use InvalidArgumentException;
use WP_Error;

// Include the location rule class
require_once __DIR__ . '/class-codesoup-acf-options-location.php';

/**
 * Options Class
 *
 * Manages theme options using ACF Pro and custom post type.
 *
 * @since 1.0.0
 */
class Options {

	private const POST_TYPE = 'pumpkin_options';

	private static $instance = null;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize ACF configuration
	 * 
	 * @return void
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register the hidden post type for theme options
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		register_post_type( self::POST_TYPE, [
			'label'              => __( 'Theme Options', 'pumpkin' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'menu_icon'          => 'dashicons-admin-generic',
			'menu_position'      => 99,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => true,
			'can_export'         => false,
			'supports'           => [ 'title' ],
			'delete_with_user'   => false,
		] );
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'after_switch_theme', [ $this, 'add_theme_capabilities' ] );
		add_action( 'acf/init', [ $this, 'register_location_type' ] );
		add_action( 'acf/save_post', [ $this, 'save_options' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_post_name_meta_box' ] );

		add_filter( 'quick_edit_enabled_for_post_type', [ $this, 'disable_quick_view' ], 10, 2 );
		add_action( 'admin_menu', [ $this, 'manage_submenu' ] );
	}

	/**
	 * Register ACF location type for pumpkin options
	 */
	public function register_location_type(): void {
		if ( function_exists( 'acf_register_location_type' ) ) {
			acf_register_location_type( 'CodeSoup_ACF_Options_Location' );
		}
	}

	/**
	 * Add capabilities to administrator role on theme activation
	 *
	 * @return void
	 */
	public function add_theme_capabilities(): void {
		$role = get_role( 'administrator' );

		if ( $role ) {
			// Pumpkin Options capabilities
			$role->add_cap( 'create_pumpkin_options' );
			$role->add_cap( 'delete_others_pumpkin_options' );
			$role->add_cap( 'delete_pumpkin_options' );
			$role->add_cap( 'delete_private_pumpkin_options' );
			$role->add_cap( 'delete_published_pumpkin_options' );
			$role->add_cap( 'edit_others_pumpkin_options' );
			$role->add_cap( 'edit_pumpkin_options' );
			$role->add_cap( 'edit_private_pumpkin_options' );
			$role->add_cap( 'edit_published_pumpkin_options' );
			$role->add_cap( 'publish_pumpkin_options' );
			$role->add_cap( 'read_private_pumpkin_options' );

		}
	}


	/**
	 * Disable "Quick Edit"
	 */
	public function disable_quick_view( $action, $post_type ) {

		if ( $post_type == self::POST_TYPE ) {
			$action = false;
		}

		return $action;
	}

	/**
	 * Remove "Add New" submenu for pumpkin_options post type
	 */
	public function manage_submenu(): void {
		remove_submenu_page(
			'edit.php?post_type=' . self::POST_TYPE,
			'post-new.php?post_type=' . self::POST_TYPE
		);
	}


	/**
	 * Save options as JSON in post_content
	 *
	 * @param int $post_id Post ID
	 * @return void
	 */
	public function save_options( $post_id ) {

		if ( get_post_type() !== self::POST_TYPE )
			return;
 
		$fields = get_fields( $post_id );
		if ( false === $fields ) {
			// error_log( 'Failed to get ACF fields for post ID: ' . $post_id );
			return;
		}

		$serialized = serialize( $fields );
		if ( false === $serialized ) {
			// error_log( 'Failed to serialize options' );
			return;
		}

		$post = get_post( $post_id );
		$post_name = 'pumpkin-options-' . sanitize_title( $post->post_title );

		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $serialized,
			'post_name'    => $post_name,
		], true );
	}

	/**
	 * Add meta box for post name
	 *
	 * @return void
	 */
	public function add_post_name_meta_box() {
		add_meta_box(
			'options-post-name',
			'Options Identifier',
			[ $this, 'display_post_name_meta_box' ],
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Display post name meta box
	 *
	 * @param WP_Post $post Post object
	 * @return void
	 */
	public function display_post_name_meta_box( $post ) {
		$post_name = $post->post_name ?: __( 'Not set yet', 'pumpkin' );
		$info      = __( 'Use this identifier to retrieve options object in your code', 'pumpkin' );

		printf(
			'%s<input type="text" value="%s" readonly onclick="this.select()" class="regular-text" style="max-width:100%%;" />',
			wpautop( $info ),
			str_replace( 'pumpkin-options-', '', esc_attr( $post_name ) )
		);
	}

	/**
	 * Load options from database
	 *
	 * @return void
	 */
	public function load_options(): void {
		// if ( null === self::$post_id ) {
		// 	return;
		// }

		// $post = get_post( self::$post_id );
		// if ( ! $post ) {
		// 	return;
		// }
	}

	/**
	 * Get ACF field value
	 *
	 * @param string|null $field_name Field name
	 * @return mixed Field value or null if not found
	 */
	/**
	 * Get options by post name
	 *
	 * @param string $post_name Post name (with or without pumpkin-options prefix)
	 * @return array|null Options array or null if not found
	 */
	public function get( string $post_name ): ?array {
		// Auto prepend prefix if not present
		if ( ! str_starts_with( $post_name, 'pumpkin-options-' ) ) {
			$post_name = 'pumpkin-options-' . $post_name;
		}

		$post = get_page_by_path( $post_name, OBJECT, self::POST_TYPE );

		if ( ! $post ) {
			return [];
		}

		$content = $post->post_content;
		if ( empty( $content ) ) {
			return [];
		}

		$options = unserialize( $content );

		return is_array( $options ) ? $options : [];
	}

	public function get_field( ?string $field_name = null ): mixed {
		if ( null === self::$post_id ) {
			return null;
		}

		return get_field( $field_name, self::$post_id );
	}

	/**
	 * Get all ACF fields
	 *
	 * @return array<string, mixed> All ACF fields
	 */
	public function get_all_fields(): array {
		if ( null === self::$post_id ) {
			return [];
		}

		return get_fields( self::$post_id );
	}

	/**
	 * Get the theme options post ID
	 *
	 * @return int|null Post ID or null if not found
	 */
	public function get_post_id(): ?int {
		return self::$post_id;
	}

	/**
	 * Register a template options page
	 *
	 * @param string $template_name Template name/slug (e.g., 'landing-page')
	 * @param string $display_name Display name for the options page (e.g., 'Landing Page Options')
	 * @return int|WP_Error Post ID on success, WP_Error on failure
	 */
	public static function register_template_options( string $template_name, string $display_name ) {
		// Sanitize inputs
		$template_name = sanitize_key( $template_name );
		$display_name = sanitize_text_field( $display_name );

		if ( empty( $template_name ) || empty( $display_name ) ) {
			return new WP_Error( 'invalid_args', 'Template name and display name are required' );
		}

		// Check if options page already exists for this template
		$existing = get_posts( [
			'post_type'      => self::POST_TYPE,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'   => '_pumpkin_template_name',
					'value' => $template_name,
				],
			],
		] );

		if ( ! empty( $existing ) ) {
			return $existing[0]->ID;
		}

		// Create new pumpkin_options post
		$post_id = wp_insert_post( [
			'post_type'   => self::POST_TYPE,
			'post_title'  => $display_name,
			'post_status' => 'publish',
			'post_name'   => 'pumpkin-options-' . $template_name,
		], true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Store template name in post meta for portable reference
		update_post_meta( $post_id, '_pumpkin_template_name', $template_name );

		return $post_id;
	}
}
