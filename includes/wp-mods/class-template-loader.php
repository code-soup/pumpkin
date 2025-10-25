<?php

namespace CodeSoup\Pumpkin\WpMods;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

use CodeSoup\Pumpkin\ACF\Options;
use CodeSoup\Pumpkin\Utils\TemplateUtilities;


/**
 * Template Loader Class
 *
 * Handles WordPress template integration including:
 * - Template selection in admin UI
 * - Template hierarchy filters
 * - Template part loading
 * - Custom template discovery
 * - Asset loading based on template
 *
 * Implements Singleton pattern to ensure only one instance exists throughout the application
 */
class TemplateLoader {
	use TemplateUtilities;

	/**
	 * Currently queried object
	 *
	 * @var \WP_Post|\WP_Term|\WP_User|null
	 */
	private $queried_object;

	/**
	 * Current template name
	 *
	 * @var string|null
	 */
	private $template_name;

	/**
	 * Cached template meta value
	 *
	 * @var string|null
	 */
	private $template_meta;

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Template path cache (in-memory for current request)
	 *
	 * @var array
	 */
	private static $template_path_cache = [];

	/**
	 * Cache expiration time for transients
	 *
	 * @var int
	 */
	private $cache_expiration = HOUR_IN_SECONDS;

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
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		$this->init();
		$this->init_cache_invalidation();
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing of the instance
	 *
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Initialize the template loader
	 */
	private function init() {
		// Set the queried object
		$this->queried_object = get_queried_object();

		// Add actions for template parts.
		$this->init_template_part_actions();
	}

	/**
	 * Initialize template wrapper filter
	 */
	public function init_template_wrapper(): void {
		add_filter( 'template_include', [ $this, 'template_wrapper' ], 50 );
	}

	/**
	 * Get current page template from meta (static method for global access)
	 *
	 * @param int|null $post_id Optional post ID. If not provided, uses current post
	 * @return string|null Template name without 'template-' prefix and '.php' extension, or null if not set
	 */
	public static function get_current_template_meta( ?int $post_id = null ): ?string {
		// Get post ID - use provided ID or current post
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		// Return null if no valid post ID
		if ( ! $post_id ) {
			return null;
		}

		// Get post object
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		// Get template from post meta
		$template = str_replace( 'template-', '', get_post_meta( $post->ID, '_wp_page_template', true ) );

		// Return null if empty or default
		return empty( $template ) || $template === 'default' ? null : $template;
	}

	/**
	 * Get current template name being used for rendering (static method for global access)
	 *
	 * @return string|null Current template name or null if TemplateLoader not initialized
	 */
	public static function get_current_template_name(): ?string {
		// Try to get from existing instance
		if ( null !== self::$instance ) {
			return self::$instance->get_template_name();
		}

		// If no instance exists, try to determine from WordPress context
		if ( is_front_page() ) {
			return 'front-page';
		} elseif ( is_home() ) {
			return 'home';
		} elseif ( is_single() ) {
			return 'single';
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_archive() ) {
			return 'archive';
		} elseif ( is_search() ) {
			return 'search';
		} elseif ( is_404() ) {
			return '404';
		}

		return 'index';
	}

	/**
	 * Get the current queried object, refreshing if needed
	 *
	 * @param bool $refresh Whether to refresh the queried object
	 * @return mixed The queried object
	 */
	private function get_current_object( bool $refresh = false ): ?object {
		if ( $refresh || null === $this->queried_object ) {
			$this->queried_object = get_queried_object();
		}
		return $this->queried_object;
	}

	/**
	 * Always use base.php as the main wrapper template
	 *
	 * @param string $template The template to include
	 * @return string Path to the base template
	 */
	public function template_wrapper( $template ) {
		$base_template = $this->build_template_path( 'base.php' );
		return $this->template_exists( $base_template ) ? $base_template : $template;
	}


	/**
	 * Get template from post meta.
	 *
	 * @param \WP_Post|null $post Post object or null for current post
	 * @return string|null Template name or null if not set
	 */
	private function get_template_from_meta( ?\WP_Post $post = null ): ?string {
		// Use cached value if available and no specific post requested
		if ( $post === null && $this->template_meta !== null ) {
			return $this->template_meta;
		}

		// Get post object
		$post = $post ?? $this->get_current_object();

		// Early return if not a valid post
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return null;
		}

		// Get template from post meta
		$template = str_replace( 'template-', '', get_post_meta( $post->ID, '_wp_page_template', true ) );
		$result = empty( $template ) || $template === 'default' ? null : $template;

		// Cache result if this is for the current post
		if ( $post === $this->get_current_object() ) {
			$this->template_meta = $result;
		}

		return $result;
	}





	/**
	 * Initialize template part actions (performance optimized)
	 */
	private function init_template_part_actions(): void {
		add_action( 'pumpkin_page_head', [ $this, 'load_head_template' ] );
		add_action( 'pumpkin_page_header', [ $this, 'load_header_template' ] );
		add_action( 'pumpkin_page_main', [ $this, 'load_main_template' ] );
		add_action( 'pumpkin_page_sidebar', [ $this, 'load_sidebar_template' ] );
		add_action( 'pumpkin_page_footer', [ $this, 'load_footer_template' ] );
	}

	/**
	 * Load the head template part
	 */
	public function load_head_template(): void {
		$this->load_template_part( 'head' );
	}

	/**
	 * Load the header template part
	 */
	public function load_header_template(): void {
		$this->load_template_part( 'header' );
	}

	/**
	 * Load the main content template
	 */
	public function load_main_template(): void {
		$this->load_template_part( 'index' );
	}

	/**
	 * Load the sidebar template part
	 */
	public function load_sidebar_template(): void {
		$this->load_template_part( 'sidebar' );
	}

	/**
	 * Load the footer template part
	 */
	public function load_footer_template(): void {
		$this->load_template_part( 'footer' );
	}

	/**
	 * Load the page config
	 *
	 * @return void
	 */
	public function load_page_config(): void {
		$this->load_template_part( 'page-config' );
	}

	/**
	 * Load a template part
	 *
	 * @param string $part The template part to load
	 * @return void
	 */
	private function load_template_part( string $part ): void {
		$template_path = $this->resolve_template( $part );

		if ( $template_path ) {
			include $template_path;
		}
	}

	/**
	 * Get template hierarchy for the current request based on WordPress context
	 *
	 * @param string $filename The filename to look for
	 * @return array Template hierarchy
	 */
	private function get_template_hierarchy( string $filename ): array {

		error_log( print_r( $filename, true ) );
	
		/**
		 * Detect WordPress context and build appropriate hierarchy
		 * 
		 * TODO: Update checker, make a WP look-a-like
		 * https://github.com/WordPress/wordpress-develop/blob/6.8.3/src/wp-includes/template-loader.php#L104-L104
		 */
		if ( is_404() ) {
			return $this->get_404_templates( $filename );
		} elseif ( is_search() ) {
			return $this->get_search_templates( $filename );
		} elseif ( is_author() ) {
			return $this->get_author_templates( $this->get_current_object(), $filename );
		} elseif ( is_tax() || is_category() || is_tag() ) {
			return $this->get_taxonomy_templates( $this->get_current_object(), $filename );
		} elseif ( is_post_type_archive() ) {
			return $this->get_archive_templates( $filename );
		} elseif ( is_date() ) {
			return $this->get_date_templates( $filename );
		} elseif ( is_home() ) {
			return $this->get_home_templates( $filename );
		} elseif ( is_singular() ) {
			return $this->get_post_templates( $this->get_current_object(), $filename );
		}

		// Fallback to default templates
		return $this->get_default_templates( $filename );
	}



	/**
	 * Get taxonomy template paths (categories, tags, custom taxonomies)
	 *
	 * @param \WP_Term $term The term object
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_taxonomy_templates( \WP_Term $term, string $filename ): array {
		$taxonomy = $term->taxonomy;
		$term_slug = $term->slug;

		return [
			"templates/taxonomy/{$taxonomy}/{$term_slug}/{$filename}.php",
			"templates/taxonomy/{$taxonomy}/{$filename}.php",
			"templates/taxonomy/{$filename}.php",
			"templates/shared/parts/{$filename}.php"
		];
	}

	/**
	 * Get author template paths
	 *
	 * @param \WP_User $author The author object
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_author_templates( \WP_User $author, string $filename ): array {
		return [
			"templates/virtual/author/{$author->user_nicename}/{$filename}.php",
			"templates/virtual/author/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get post template paths (posts, pages, custom post types)
	 *
	 * @param \WP_Post|null $post The post object or null for current post
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_post_templates( ?\WP_Post $post, string $filename ): array {
		// Get current post if not provided
		if ( null === $post ) {
			$post = $this->get_current_object();
		}

		// Ensure we have a valid WP_Post object
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return [ "templates/shared/parts/{$filename}.php" ];
		}

		$post_type = str_replace( '_', '-', $post->post_type );
		$template = $this->get_template_from_meta( $post );
		$custom_template = $template ? $this->sanitize_template_name( $template, 'file' ) : '';

		if ( ! empty( $custom_template ) && 'default' !== $custom_template ) {
			return [
				"templates/post-type/{$post_type}/{$custom_template}/{$filename}.php",
				"templates/post-type/{$post_type}/{$filename}.php",
				"templates/shared/parts/{$filename}.php",
			];
		}

		return [
			"templates/post-type/{$post_type}/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get 404 error template paths
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_404_templates( string $filename ): array {

		return [
			"templates/post-type/page/404/{$filename}.php",
			"templates/virtual/404/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get search results template paths
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_search_templates( string $filename ): array {
		return [
			"templates/post-type/page/search/{$filename}.php",
			"templates/virtual/search/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get archive template paths (custom post type archives)
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_archive_templates( string $filename ): array {
		$post_type = get_query_var( 'post_type' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$post_type = str_replace( '_', '-', $post_type );

		return [
			"templates/{$post_type}/archive/{$filename}.php",
			"templates/virtual/archive/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get date archive template paths
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_date_templates( string $filename ): array {
		return [
			"templates/{$post_type}/date/{$filename}.php",
			"templates/virtual/date/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Get home/blog template paths
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_home_templates( string $filename ): array {
		return [
			"templates/post-type/page/home/{$filename}.php",
			"templates/post-type/page/homepage/{$filename}.php",
			"templates/virtual/home/{$filename}.php",
			"templates/virtual/homepage/{$filename}.php",
			"templates/shared/parts/{$filename}.php",
		];
	}


	/**
	 * Get default fallback template paths
	 *
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_default_templates( string $filename ): array {
		return [
			"templates/shared/parts/{$filename}.php",
		];
	}

	/**
	 * Find and return the first existing template from the hierarchy
	 *
	 * @param string $filename The filename to look for
	 * @return string|null Path to the found template or null if not found
	 */
	private function resolve_template( string $filename ): ?string {
		$templates = $this->get_template_hierarchy( $filename );

		// Use cached template resolution for better performance
		$found_template = $this->get_cached_template_path( $templates );

		// Convert to absolute normalized path only for the found template
		return $found_template
			? $this->normalize_path( $this->build_template_path( $found_template ) )
			: null;
	}

	/**
	 * Get current template name from post meta
	 *
	 * @return string|null Template name without 'template-' prefix and '.php' extension, or null if not set
	 */
	public function get_template_name(): ?string {
		if ( $this->template_name === null ) {
			$template            = $this->get_template_from_meta();
			$this->template_name = $template ? $this->sanitize_template_name( $template, 'file' ) : null;
		}

		return $this->template_name;
	}



	/**
	 * Check if current context should be treated as 404
	 *
	 * @param mixed $queried_object The queried object
	 * @return bool Whether this is a 404 context
	 */
	private function is_404( $queried_object ): bool {

        if ( is_404() )
            return true;

		if ( empty( $queried_object ) )
			return true;

		if ( is_a( $queried_object, 'WP_Post' ) ) {
			return ( 'publish' !== $queried_object->post_status && ! is_user_logged_in() );
		}

        return false;
	}

	/**
	 * Check if template caching should be enabled
	 *
	 * @return bool
	 */
	private function is_template_caching_enabled(): bool {
		// Allow explicit override via constant
		if ( defined( 'PUMPKIN_TEMPLATE_CACHING' ) ) {
			return (bool) PUMPKIN_TEMPLATE_CACHING;
		}

		// Check theme support
		if ( current_theme_supports( 'pumpkin-template-caching' ) ) {
			// Default: enable in production and staging only
			return in_array( wp_get_environment_type(), [ 'production', 'staging' ], true );
		}

		return false;
	}

	/**
	 * Get template path with caching
	 *
	 * @param array $template_paths Array of template paths to check
	 * @return string Found template path or empty string
	 */
	private function get_cached_template_path( array $template_paths ): string {
		// Create cache key from template paths
		$cache_key = 'template_' . md5( implode( '|', $template_paths ) );

		// Check in-memory cache first (fastest)
		if ( isset( self::$template_path_cache[ $cache_key ] ) ) {
			return self::$template_path_cache[ $cache_key ];
		}

		// Check WordPress transient cache (persistent) only if enabled
		if ( $this->is_template_caching_enabled() ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				self::$template_path_cache[ $cache_key ] = $cached;
				return $cached;
			}
		}

		// Resolve template path (expensive operation)
		$found_template = '';
		foreach ( $template_paths as $template ) {
			$absolute_path = $this->normalize_path( $this->build_template_path( $template ) );
			if ( $this->template_exists( $absolute_path ) ) {
				$found_template = $template;
				break;
			}
		}

		// Store in memory cache (always)
		self::$template_path_cache[ $cache_key ] = $found_template;

		// Store in persistent cache (only if enabled)
		if ( $this->is_template_caching_enabled() ) {
			set_transient( $cache_key, $found_template, $this->cache_expiration );
		}

		return $found_template;
	}

	/**
	 * Clear template path cache
	 */
	public function clear_template_cache(): void {
		// Clear in-memory cache
		self::$template_path_cache = [];

		// Clear transient cache
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_pumpkin_template_%'
			 OR option_name LIKE '_transient_timeout_pumpkin_template_%'"
		);
	}

	/**
	 * Initialize cache invalidation hooks
	 */
	private function init_cache_invalidation(): void {
		add_action( 'switch_theme', [ $this, 'clear_template_cache' ] );
		add_action( 'upgrader_process_complete', [ $this, 'clear_template_cache' ] );
	}
}
