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
	 * Custom templates configuration
	 *
	 * @var array
	 */
	private $custom_templates = [];

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
		$this->init();
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

		// Initialize custom templates
		$this->custom_templates = array_merge(
			$this->scan_post_type_templates(),
			[
				'pumpkin-options' => ['Settings: Footer']
			]
		);

		define( 'PUMPKIN_TEMPLATES', $this->custom_templates );

		// Add filters
		add_filter( 'template_include', [ $this, 'template_wrapper' ], 10 );
		add_filter( 'theme_templates', [ $this, 'populate_template_selector' ], 10, 4 );
		// add_filter( 'page_template', [ $this, 'load_template' ], 10, 2 );
		// add_filter( 'singular_template', [ $this, 'load_template' ], 10, 2 );

		// Add action to clear cache on permalink flush
		add_action( 'flush_rewrite_rules', [ $this, 'clear_all_template_caches' ] );

		// add_action('pumpkin_page_config_common', [$this, 'set_page_config']);
		// add_action( 'pumpkin_page_config_specific', [ $this, 'set_page_config' ] );

		// Add actions for template parts
		add_action( 'pumpkin_page_head', [ $this, 'set_head_template' ] );
		add_action( 'pumpkin_page_header', [ $this, 'set_header_template' ] );
		add_action( 'pumpkin_page_main', [ $this, 'set_main_template' ] );
		add_action( 'pumpkin_page_sidebar', [ $this, 'set_sidebar_template' ] );
		add_action( 'pumpkin_page_footer', [ $this, 'set_footer_template' ] );
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

		$base_template = $this->normalize_path( $this->get_theme_directory() . '/base.php' );
		return $this->template_exists( $base_template ) ? $base_template : $template;
	}

	/**
	 * Scan for post type templates in the specified directory
	 *
	 * @param string $path The relative path to the templates directory
	 * @return array An associative array of post types and their templates
	 */
	private function scan_post_type_templates( $path = '/templates/post-type' ): array {
		$templates = [];
		$dir_path  = $this->normalize_path( $this->get_theme_directory() . $path );

		// Get list of directories
		$directories = $this->get_directory_listing( $dir_path );

		if ( ! $directories ) {
			return [];
		}

		foreach ( $directories as $directory ) {
			if ( $directory['type'] !== 'd' || in_array( $directory['name'], [ '.', '..' ] ) ) {
				continue;
			}

			$post_type      = $directory['name'];
			$subdir_path    = $this->normalize_path( "{$dir_path}/{$post_type}" );
			$subdirectories = $this->get_directory_listing( $subdir_path );

			if ( ! $subdirectories ) {
				continue;
			}

			foreach ( $subdirectories as $subdirectory ) {
				if ( $this->is_excluded_directory( $subdirectory ) ) {
					continue;
				}

				$template_name             = ucwords( str_replace( [ '-', '_' ], ' ', $subdirectory['name'] ) );
				$templates[ $post_type ][] = $template_name;
			}
		}

		return $templates;
	}

	/**
	 * Populate 'Page Template' select item
	 *
	 * @param array $templates Array of available templates
	 * @param WP_Theme $theme Current theme object
	 * @param WP_Post $post Current post object
	 * @param string $post_type Current post type
	 * @return array Modified templates array
	 */
	public function populate_template_selector( $templates, $theme, $post, $post_type ): array {

		if ( isset( $this->custom_templates[ $post_type ] ) ) {
			$templates = array_merge(
				$templates,
				array_combine(
					array_map(
						fn( $value ) => sanitize_title( "template-{$value}" ),
						$this->custom_templates[ $post_type ]
					),
					$this->custom_templates[ $post_type ]
				)
			);
		}

		$templates = apply_filters( 'pumpkin_theme_templates', $templates );

		asort( $templates );
		return $templates;
	}

	/**
	 * Clean template name by removing prefix and extension
	 *
	 * @param string $template_name Template name to clean
	 * @return string Cleaned template name
	 */
	private function clean_template_name( string $template_name ): string {
		return str_replace(
			[ 'template-', '.php' ],
			'',
			basename( $template_name )
		);
	}

	/**
	 * Get template from post meta with object caching
	 *
	 * @param \WP_Post|null $post Post object or null for current post
	 * @return string|null Template name or null if not set
	 */
	private function get_template_from_meta( ?\WP_Post $post = null ): ?string {
		// Get post object
		$post = $post ?? $this->get_current_object();

		// Early return if not a valid post
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return NULL;
		}

		// Try to get from object cache first
		// $cache_key       = 'pumpkin_template_' . $post->ID;
		// $cached_template = wp_cache_get( $cache_key, 'pumpkin_templates' );

		// if ( false !== $cached_template ) {
		// 	return $cached_template ?: null;
		// }

		// // Get template from post meta
		$template = str_replace( 'template-', '', get_post_meta( $post->ID, '_wp_page_template', true ) );

		// // Early return if no template or default
		// if ( empty( $template ) || $template === 'default' ) {
		// 	wp_cache_set( $cache_key, '', 'pumpkin_templates', 3600 );
		// 	return null;
		// }

		// // Cache the result
		// wp_cache_set( $cache_key, $template, 'pumpkin_templates', 3600 );

		return $template;
	}

	/**
	 * Clear template cache for a post
	 *
	 * @param int $post_id Post ID
	 * @return void
	 */
	private function clear_template_cache( int $post_id ): void {
		wp_cache_delete( 'pumpkin_template_' . $post_id, 'pumpkin_templates' );
	}

	/**
	 * Load single post template
	 *
	 * @param string $template_path The path to the current template
	 * @param string $type The type of template being loaded
	 * @return string The path to the template to be used
	 */
	public function load_template( $template_path, $type ): string {
		$template  = $this->get_template_from_meta();
		$qobject   = $this->get_current_object();
		$post_type = $qobject->post_type;

		if ( 'default' === $template || empty( $template ) ) {
			$template_name = 'index.php';
		} else {
			$template_name = "{$template}/index.php";
		}

		// Build potential template paths
		$template_paths = [
			$this->normalize_path( $this->get_theme_directory() . "/templates/post-type/{$post_type}/{$template_name}" ),
			$this->normalize_path( $this->get_theme_directory() . "/templates/{$template_name}" )
		];

		// Return first existing template or fallback to default
		foreach ( $template_paths as $path ) {
			if ( $this->template_exists( $path ) ) {
				error_log( 'path' . $path );
				return $path;
			}
		}

		return $template_path;
	}

	/**
	 * Load a shared template part
	 *
	 * @param string $part The template part to load (head, header, sidebar, footer)
	 * @return void
	 */
	private function load_shared_template_part( string $part ): void {
		$template_path = $this->resolve_template( $part );

		if ( $template_path ) {
			include $template_path;
		}
	}

	/**
	 * Load the head template part
	 */
	public function set_head_template(): void {
		$this->load_shared_template_part( 'head' );
	}

	/**
	 * Load the header template part
	 */
	public function set_header_template(): void {
		$this->load_shared_template_part( 'header' );
	}

	/**
	 * Load the sidebar template part
	 */
	public function set_sidebar_template(): void {
		$this->load_shared_template_part( 'sidebar' );
	}

	/**
	 * Load the footer template part
	 */
	public function set_footer_template(): void {
		$this->load_shared_template_part( 'footer' );
	}

	/**
	 * Load the main content template based on post type
	 *
	 * This method loads the main content template using the template hierarchy
	 *
	 * @return void
	 */
	public function set_main_template(): void {
		$this->load_shared_template_part( 'index' );
	}

	/**
	 * Load the page config
	 *
	 * @return void
	 */
	public function set_page_config(): void {
		// $this->load_shared_template_part( 'page-config' );
	}

	/**
	 * Get template hierarchy for the current request
	 *
	 * @param string $filename The filename to look for
	 * @return array Template hierarchy
	 */
	private function get_template_hierarchy( string $filename ) {

		$template  = $this->get_template_from_meta();
		$qobject   = $this->get_current_object();
		$post_type = ( empty( $qobject ) )
			? 'page'
			: str_replace( '_', '-', $qobject->post_type );

		if ( $this->is_404( $qobject ) ) {
			$options  = Options::get_theme_options( 'options-general' );
			$template = $this->get_template_from_meta( $options['page_404'] );
		}

		// if ( ! empty( $_GET['preview'] ) && ! empty( $_GET['page_id'] ) ) {
		// 	$post      = get_post( $_GET['page_id'] );
		// 	$template  = get_post_meta( '_wp_page_template', $post->ID, true );
		// 	$post_type = $post->post_type;
		// }

		// error_log( print_r( , true ) );
		// error_log( print_r( $qobject, true ) );

		if ( 'default' === $template || empty( $template ) ) {
			$template_name = "$filename.php";
		} else {
			$template_name = "{$template}/$filename.php";
		}

		// Build potential template paths
		$templates = [
			$this->normalize_path( $this->get_theme_directory() . "/templates/post-type/{$post_type}/{$template_name}" ),
			$this->normalize_path( $this->get_theme_directory() . "/templates/{$template_name}" ),
			$this->normalize_path( $this->get_theme_directory() . "/templates/shared/parts/{$template_name}" ),
			$this->normalize_path( $this->get_theme_directory() . "/templates/shared/parts/{$filename}.php" ),
		];

		// error_log( print_r( $templates, true) );

		// Generate cache key
		// $cache_key = 'template_tree_' . md5( json_encode( [
		// 	'filename'          => $filename,
		// 	'queried_object_id' => get_queried_object_id(),
		// 	'is_404'            => is_404(),
		// 	'is_archive'        => is_archive(),
		// 	'is_author'         => is_author(),
		// 	'is_search'         => is_search(),
		// ] ) );

		// // Try to get from WP Object Cache
		// $cached = wp_cache_get( $cache_key, 'template_hierarchy' );
		// if ( false !== $cached ) {
		// 	// return $cached;
		// }

		// $templates = $this->generate_template_hierarchy( $filename );

		// Cache the result
		// wp_cache_set($cache_key, $templates, 'template_hierarchy', 3600);

		return $templates;
	}

	/**
	 * Generate template hierarchy based on current request
	 *
	 * @param string $filename The filename to look for
	 * @return array Template hierarchy
	 */
	private function generate_template_hierarchy( string $filename ): array {
		$templates = [];
		$object    = $this->get_current_object();

		if ( null === $object || is_404() ) {
			$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', '404', $filename ] );
		} elseif ( is_a( $object, 'WP_Term' ) ) {
			$templates = $this->get_taxonomy_templates( $object, $filename );
		} elseif ( is_a( $object, 'WP_Post_Type' ) ) {
			$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', $object->name, 'archive', $filename ] );
		} elseif ( is_a( $object, 'WP_User' ) ) {
			$templates = $this->get_author_templates( $object, $filename );
		} elseif ( is_a( $object, 'WP_Post' ) ) {
			$templates = $this->get_post_templates( $object, $filename );
		}

		// Add shared template as fallback
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'shared', 'parts', $filename ] );

		return array_unique( $templates );
	}

	/**
	 * Get taxonomy template paths
	 *
	 * @param \WP_Term $term The term object
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_taxonomy_templates( \WP_Term $term, string $filename ): array {
		$templates = [];
		$taxonomy  = $term->taxonomy;
		$term_slug = $term->slug;

		// Add term-specific template
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'taxonomy', $taxonomy, $term_slug, $filename ] );

		// Add taxonomy-specific template
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'taxonomy', $taxonomy, $filename ] );

		// Add general taxonomy template
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'taxonomy', $filename ] );

		return $templates;
	}

	/**
	 * Get author template paths
	 *
	 * @param \WP_User $author The author object
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_author_templates( \WP_User $author, string $filename ): array {
		$templates = [];

		// Add author-specific template
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'author', $author->user_nicename, $filename ] );

		// Add general author template
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'author', $filename ] );

		return $templates;
	}

	/**
	 * Get post template paths
	 *
	 * @param \WP_Post $post The post object
	 * @param string $filename The filename to look for
	 * @return array Template paths
	 */
	private function get_post_templates( \WP_Post $post, string $filename ): array {
		$templates = [];
		$post_type = $post->post_type;

		// Get the page template if one is set
		$template      = $this->get_template_from_meta( $post );
		$page_template = $template ? $this->clean_template_name( $template ) : '';

		if ( ! empty( $page_template ) ) {
			// Add post type + template specific path
			$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'post-type', $post_type, $page_template, $filename ] );
		}

		// Add post type general path
		$templates[] = $this->join_paths( [ $this->get_theme_directory(), 'templates', 'post-type', $post_type, $filename ] );

		return $templates;
	}

	/**
	 * Find and return the first existing template from the hierarchy
	 *
	 * @param string $filename The filename to look for
	 * @return string|null Path to the found template or null if not found
	 */
	private function resolve_template( string $filename ): ?string {
		$templates = $this->get_template_hierarchy( $filename );

		foreach ( $templates as $template ) {
			if ( $this->template_exists( $template ) ) {
				return $template;
			}
		}

		// Log if template was not found
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Template not found for %s in %s', $filename, $this->get_theme_directory() ) );
		}

		return null;
	}

	/**
	 * Get current template name from post meta
	 *
	 * @return string|null Template name without 'template-' prefix and '.php' extension, or null if not set
	 */
	public function get_template_name(): ?string {
		if ( $this->template_name === null ) {
			$template            = $this->get_template_from_meta();
			$this->template_name = $template ? $this->clean_template_name( $template ) : null;
		}

		return $this->template_name;
	}

	/**
	 * Clear all template caches
	 * Called when permalinks are flushed
	 *
	 * @return void
	 */
	public function clear_all_template_caches(): void {
		global $wpdb;

		// Get all post IDs
		$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts}" );

		// Clear individual post template caches
		foreach ( $post_ids as $post_id ) {
			$this->clear_template_cache( (int) $post_id );
		}

		// Clear template hierarchy cache group
		wp_cache_flush_group( 'template_hierarchy' );

		// Clear all template caches
		wp_cache_flush_group( 'pumpkin_templates' );
	}

	private function is_404( $queried_object ): bool {

        if ( is_404() )
            return true;

		if ( empty( $queried_object ) )
			return true;

		if ( is_a( $queried_object, 'WP_Post' ) ) {
			return ( 'private' === $queried_object->post_status && ! is_user_logged_in() );
		}

        return false;
	}
}
