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
	 * Hooker instance
	 *
	 * @var \CodeSoup\Pumpkin\Core\Hooker
	 */
	private \CodeSoup\Pumpkin\Core\Hooker $hooker;

	/**
	 * Menu locations
	 *
	 * @var array<string, string>
	 */
	private const MENU_LOCATIONS = [
		'primary' => 'Primary Navigation',
	];

	/**
	 * Sidebar locations
	 *
	 * @var array<string, string>
	 */
	private const SIDEBAR_LOCATIONS = [];

	/**
	 * Template options pages
	 *
	 * @var array<string, string>
	 */
	private const TEMPLATE_OPTIONS = [
		'footer' => 'Footer',
		'general' => 'General Options'
	];

	/**
	 * Theme support features
	 *
	 * @var array<string, mixed>
	 */
	private const THEME_SUPPORTS = [
		'post-thumbnails',
		'title-tag',
		'pumpkin-template-caching',

		'customize-selective-refresh-widgets',
		'align-wide',
		'responsive-embeds',
		'woocommerce'
	];

	/**
	 * Constructor
	 *
	 * @param \CodeSoup\Pumpkin\Core\Hooker $hooker Hooker instance for registering hooks
	 */
	public function __construct( \CodeSoup\Pumpkin\Core\Hooker $hooker ) {
		$this->hooker = $hooker;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		$this->hooker->add_action( 'after_setup_theme', $this, 'setup_theme_features' );
		$this->hooker->add_action( 'init', $this, 'register_menus' );
		$this->hooker->add_action( 'init', $this, 'register_template_options' );
		$this->hooker->add_action( 'widgets_init', $this, 'register_sidebars' );
		$this->hooker->add_filter( 'additional_capabilities_display', null, '__return_false' );
		$this->hooker->add_filter( 'upload_mimes', $this, 'upload_mimes' );

		// Simplify menu classes
		$this->hooker->add_filter( 'nav_menu_css_class', $this, 'simplify_menu_classes', 10, 4 );
		$this->hooker->add_filter( 'nav_menu_item_id', null, '__return_empty_string' );

		// Simplify body classes
		$this->hooker->add_filter( 'body_class', $this, 'simplify_body_classes' );
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

		remove_post_type_support( 'page', 'editor' );
		remove_post_type_support( 'page', 'thumbnail' );
	}

	/**
	 * Register template options pages
	 *
	 * @return void
	 */
	public function register_template_options(): void {
		// Expose template options via filter for ACF location rules
		add_filter( 'pumpkin_template_options', fn() => self::TEMPLATE_OPTIONS );

		foreach ( self::TEMPLATE_OPTIONS as $template_name => $display_name ) {
			\CodeSoup\Pumpkin\ACF\Options::register_template_options( $template_name, $display_name );
		}
	}


	/**
	 * Register sidebars
	 *
	 * @return void
	 */
	public function register_sidebars(): void {
		foreach ( self::SIDEBAR_LOCATIONS as $id => $name ) {
			register_sidebar( [
				'name' => $name,
				'id'   => $id,
			] );
		}
	}

	/**
	 * Enable SVG files upload
	 */
	public function upload_mimes( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Simplify menu item classes
	 *
	 * Removes all default WordPress menu classes and keeps only 'active' for current items
	 *
	 * @param array    $classes The CSS classes that are applied to the menu item's <li> element.
	 * @param WP_Post  $item    The current menu item.
	 * @param stdClass $args    An object of wp_nav_menu() arguments.
	 * @param int      $depth   Depth of menu item. Used for padding.
	 * @return array Modified classes array
	 */
	public function simplify_menu_classes( $classes, $item, $args, $depth ) {
		$new_classes = [];

		// Add 'active' class for current menu items
		if ( in_array( 'current-menu-item', $classes, true ) ||
		     in_array( 'current-menu-ancestor', $classes, true ) ||
		     in_array( 'current-menu-parent', $classes, true ) ||
		     in_array( 'current_page_item', $classes, true ) ||
		     in_array( 'current_page_parent', $classes, true ) ||
		     in_array( 'current_page_ancestor', $classes, true ) ) {
			$new_classes[] = 'active';
		}

		return $new_classes;
	}

	/**
	 * Simplify body classes
	 *
	 * Removes unnecessary WordPress body classes and keeps only essential ones
	 *
	 * @param array $classes The CSS classes applied to the body element
	 * @return array Modified classes array
	 */
	public function simplify_body_classes( $classes ) {

		$body_class = [];

		if ( is_singular() )
		{
			$body_class = [
				'single-' . get_post_type()
			];
		}
		

		return $body_class;
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
	 * Get registered sidebar locations
	 *
	 * @return array<string, string>
	 */
	public static function get_sidebar_locations(): array {
		return self::SIDEBAR_LOCATIONS;
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
