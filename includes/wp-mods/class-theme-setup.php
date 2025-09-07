<?php

namespace CodeSoup\Pumpkin\WpMods;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

/**
 * Theme Setup Class
 *
 * Handles WordPress theme setup including menu registration,
 * theme support features, and other core configurations.
 *
 * @since 1.0.0
 */
class ThemeSetup {

	/**
	 * Menu locations
	 *
	 * @var array<string, string>
	 */
	private const MENU_LOCATIONS = [
		'mobile'  => 'Mobile Navigation',
		'primary' => 'Primary Navigation',
	];

	/**
	 * Theme support features
	 *
	 * @var array<string, mixed>
	 */
	private const THEME_SUPPORTS = [
		'post-thumbnails',
		'title-tag',
		'html5' => [
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		],
		'customize-selective-refresh-widgets',
		'align-wide',
		'responsive-embeds',
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'after_setup_theme', [ $this, 'setup_theme_features' ] );
		add_action( 'init', [ $this, 'register_menus' ] );
		add_action( 'widgets_init', [ $this, 'register_sidebars' ] );
		add_filter( 'additional_capabilities_display', '__return_false' );
		add_filter( 'upload_mimes', [ $this, 'upload_mimes' ] );

		add_shortcode( 'sport', [ $this, 'shortcode_sport' ] );
	}

	/**
	 * Setup theme features and support
	 *
	 * @return void
	 */
	public function setup_theme_features(): void {
		// Add theme support for various features
		foreach ( self::THEME_SUPPORTS as $feature => $args ) {
			if ( is_numeric( $feature ) ) {
				add_theme_support( $args );
			} else {
				add_theme_support( $feature, $args );
			}
		}

		// Set content width
		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 1200;
		}
	}

	/**
	 * Register navigation menus
	 *
	 * @return void
	 */
	public function register_menus(): void {
		register_nav_menus( self::MENU_LOCATIONS );

		remove_post_type_support( 'post', 'editor' );
		remove_post_type_support( 'page', 'editor' );
		remove_post_type_support( 'page', 'thumbnail' );
	}


	/**
	 * Register sidebars
	 *
	 * @return void
	 */
	public function register_sidebars(): void {
		
		register_sidebar( array(
			'name' => __( 'Main Sidebar', 'pumpkin' ),
			'id'   => 'sidebar-main',
		));

		register_sidebar( array(
			'name' => __( 'Footer 01', 'pumpkin' ),
			'id'   => 'footer-01',
		));

		register_sidebar( array(
			'name' => __( 'Footer 02', 'pumpkin' ),
			'id'   => 'footer-02',
		));

		register_sidebar( array(
			'name' => __( 'Footer 03', 'pumpkin' ),
			'id'   => 'footer-03',
		));

		register_sidebar( array(
			'name' => __( 'Footer 04', 'pumpkin' ),
			'id'   => 'footer-04',
		));
	}


	public function shortcode_sport() {

		return get_the_title();
	}

	/**
	 * Enable SVG files upload
	 */
	public function upload_mimes( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Get registered menu locations
	 *
	 * @return array<string, string>
	 */
	public static function get_menu_locations(): array {
		return self::MENU_LOCATIONS;
	}

	/**
	 * Get theme support features
	 *
	 * @return array<string, mixed>
	 */
	public static function get_theme_supports(): array {
		return self::THEME_SUPPORTS;
	}
}
