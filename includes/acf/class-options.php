<?php

namespace CodeSoup\Pumpkin\ACF;

use CodeSoup\Pumpkin\Utils\AssetLoader;
use CodeSoup\Pumpkin\Utils\TemplateUtilities;
use InvalidArgumentException;
use WP_Error;

/**
 * Options Class
 *
 * Manages theme options using ACF Pro and custom post type.
 *
 * @since 1.0.0
 */
class Options {

	private const POST_TYPE = 'pumpkin_options';

	/**
	 * Template definitions
	 *
	 * @var array<string, string>
	 */
	private const OPTIONS_PAGES = [
		'options-footer'  => 'Footer',
		'options-general' => 'General',
	];

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
			'supports'           => [ 'title', 'page_attributes' ],
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
		add_filter( 'theme_pumpkin_options_templates', [ $this, 'register_templates' ] );
		add_action( 'acf/save_post', [ $this, 'save_options' ] );
		add_action( 'after_switch_theme', [ $this, 'add_theme_capabilities' ] );
	}

	public static function register_templates( array $templates ): array {
		return array_merge( $templates, self::OPTIONS_PAGES );
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
	 * Get or create the theme options post
	 *
	 * @return int|null Post ID or null if creation fails
	 */
	public static function get_theme_options( string $options_page = '' ): ?array {

		$posts = get_posts( [
			'post_type'   => self::POST_TYPE,
			'numberposts' => 1,
			'meta_key'    => '_wp_page_template',
			'meta_value'  => $options_page,
		] );

		if ( empty( $posts ) ) {
			return [];
		}

		return (array) get_fields( $posts[0]->ID );
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
			error_log( 'Failed to get ACF fields for post ID: ' . $post_id );
			return;
		}

		$json = wp_json_encode( $fields );
		if ( false === $json ) {
			error_log( 'Failed to encode options to JSON' );
			return;
		}

		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $json,
		], true );
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
}
