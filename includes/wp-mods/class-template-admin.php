<?php

namespace CodeSoup\Pumpkin\WpMods;

use CodeSoup\Pumpkin\Utils\TemplateUtilities;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

/**
 * Template Admin functionality
 *
 * Handles WordPress admin-specific template functionality like
 * populating the template selector dropdown in the page/post editor.
 *
 * Only loads when needed in admin context.
 */
class TemplateAdmin {

	use TemplateUtilities;

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

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
		// Allow configuration of cache expiration via filter
		$this->cache_expiration = apply_filters( 'pumpkin_template_cache_expiration', WEEK_IN_SECONDS );
		$this->init();
	}

	/**
	 * Custom templates cache
	 *
	 * @var array
	 */
	private $custom_templates = [];

	/**
	 * Default template scan path
	 *
	 * @var string
	 */
	private $template_scan_path = '/templates/post-type';

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private $cache_expiration = WEEK_IN_SECONDS;

	/**
	 * Initialize admin functionality
	 */
	private function init(): void {
		// Add template selector filter - will only run when needed (lazy loading)
		add_filter( 'theme_templates', [ $this, 'populate_template_selector' ], 10, 4 );

		// Initialize cache invalidation hooks
		$this->init_cache_invalidation();
	}



	/**
	 * Populate the template selector dropdown in admin
	 *
	 * @param array $templates Array of page templates
	 * @param \WP_Theme $theme Current theme object
	 * @param \WP_Post $post Current post object
	 * @param string $post_type Current post type
	 * @return array Modified templates array
	 */
	public function populate_template_selector( $templates, $theme, $post, $post_type ): array {
		// Only run in admin and for post types that support page templates
		if ( ! is_admin() ) {
			return $templates;
		}

		// Lazy load templates when actually needed
		if ( empty( $this->custom_templates ) ) {
			$this->custom_templates = $this->get_cached_templates();
		}

		// Only add templates for the current post type
		if ( isset( $this->custom_templates[ $post_type ] ) ) {
			// Process templates with raw name as key and display name as value
			foreach ( $this->custom_templates[ $post_type ] as $raw_name => $display_name ) {
				$templates[ $raw_name ] = $display_name;
			}
		}

		$templates = apply_filters( 'pumpkin_theme_templates', $templates );

		asort( $templates );
		return $templates;
	}

	/**
	 * Get templates with smart caching
	 * - Skip caching in development environments
	 * - Use configurable transient expiration in production
	 * - Automatic cache invalidation
	 *
	 * @return array An associative array of post types and their templates
	 */
	private function get_cached_templates(): array {

		if ( $this->is_development_environment() ) {
			return $this->scan_post_type_templates();
		}

		$cache_key = $this->get_cache_key();
		$cached = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$templates = $this->scan_post_type_templates();

		// Only cache if we successfully got templates
		if ( is_array( $templates ) && ! empty( $templates ) ) {
			set_transient( $cache_key, $templates, $this->cache_expiration );
		}

		return $templates;
	}

	/**
	 * Generate cache key for template scanning
	 *
	 * @return string Cache key
	 */
	private function get_cache_key(): string {
		return 'pumpkin_template_scan_' . md5( $this->get_theme_directory() );
	}



	/**
	 * Scan post type templates directory for custom templates
	 *
	 * @param string|null $path Path to scan relative to theme directory (null uses default)
	 * @return array An associative array of post types and their templates
	 */
	private function scan_post_type_templates( ?string $path = null ): array {
		$templates = [];
		$scan_path = $path ?? $this->template_scan_path;
		$full_path = $this->get_theme_directory() . '/' . $scan_path;

		// Use TemplateUtilities to get directory listing
		$post_type_dirs = $this->get_directory_listing( $full_path );


		// Handle directory listing failure
		if ( false === $post_type_dirs || empty( $post_type_dirs ) ) {
			return $templates;
		}

		foreach ( $post_type_dirs as $directory ) {
			// Use TemplateUtilities exclusion logic
			if ( $this->is_excluded_directory( $directory ) ) {
				continue;
			}

			$post_type = $directory['name'];
			$post_type_path = $this->join_paths( $full_path, $post_type );

			// Get contents of this post type directory
			$post_type_contents = $this->get_directory_listing( $post_type_path );

			if ( false === $post_type_contents || empty( $post_type_contents ) ) {
				continue;
			}

			foreach ( $post_type_contents as $item ) {
				// Only process subdirectories (template variations)
				if ( $item['type'] === 'd' && ! $this->is_excluded_directory( $item ) ) {
					$raw_name = $this->sanitize_template_name( $item['name'], 'file' );
					$display_name = $this->sanitize_template_name( $item['name'], 'display' );

					if ( ! empty( $raw_name ) && ! empty( $display_name ) ) {
						$templates[ $post_type ][ $raw_name ] = $display_name;
					}
				}
			}
		}

		return $templates;
	}



	/**
	 * Initialize cache invalidation hooks
	 */
	private function init_cache_invalidation(): void {
		// Clear cache when theme/plugins are updated
		add_action( 'upgrader_process_complete', [ $this, 'maybe_clear_cache_on_update' ], 10, 2 );
	}

	/**
	 * Clear template cache
	 */
	public function clear_template_cache(): void {
		$cache_key = $this->get_cache_key();
		delete_transient( $cache_key );

		// Clear WordPress cache groups
		wp_cache_flush_group( 'template_hierarchy' );
		wp_cache_flush_group( 'pumpkin_templates' );

		// Reset internal cache
		$this->custom_templates = [];
	}

	/**
	 * Maybe clear cache on theme/plugin updates
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance
	 * @param array $hook_extra Extra data for the upgrade
	 */
	public function maybe_clear_cache_on_update( $upgrader, $hook_extra ): void {
		// Only clear cache for theme updates or plugin updates that might affect templates
		if ( isset( $hook_extra['type'] ) && in_array( $hook_extra['type'], [ 'theme', 'plugin' ], true ) ) {
			$this->clear_template_cache();
		}
	}




	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
