<?php declare( strict_types=1 );

namespace CodeSoup\Pumpkin\ACF;

use CodeSoup\Pumpkin\Utils\AssetLoader;
use CodeSoup\Pumpkin\Utils\ScriptLoader;
use CodeSoup\Pumpkin\Utils\TemplateUtilities;
use InvalidArgumentException;
use RuntimeException;

/**
 * Sections Class
 *
 * Handles the rendering and management of ACF flexible content sections.
 *
 * @since 1.0.0
 */
final class Sections {
	use TemplateUtilities;
	use AssetLoader;
	use ScriptLoader;

	/**
	 * Path constants
	 */
	private const SHARED_SECTIONS_PATH = 'templates/shared/sections';
	private const COMPONENTS_PATH      = 'templates/shared/components';
	private const POST_TYPE_PATH       = 'templates/post-type';

	/**
	 * Configuration constants
	 */
	private const ACF_FIELD_NAME_DEFAULT  = 'sections';
	private const POST_TYPE_DEFAULT       = 'page';
	private const REUSABLE_SECTION_LAYOUT = 'reusable_section';

	/**
	 * Cache for template existence checks
	 *
	 * @var array<string, bool>
	 */
	private static array $template_cache = [];

	/**
	 * Post ID
	 */
	private int $post_id;

	/**
	 * Arguments
	 *
	 * @var array<string, mixed>
	 */
	private array $args = [];

	/**
	 * Sections data (lazy loaded)
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $sections = null;

	/**
	 * Post type
	 */
	private string $post_type;

	/**
	 * Template name
	 */
	private string $template_name = '';

	/**
	 * Template path
	 */
	private string $template_path = '';

	/**
	 * Template URI
	 */
	private string $template_uri = '';

	/**
	 * Render parameters
	 *
	 * @var array<string, mixed>
	 */
	private array $render_params = [];

	/**
	 * Constructor
	 *
	 * @param int|null $post_id Post ID
	 * @param array<string, mixed> $args Additional arguments
	 * @throws RuntimeException If ACF PRO is not available
	 * @throws InvalidArgumentException If post ID is invalid
	 */
	public function __construct( ?int $post_id = null, array $args = [] ) {
		$this->validate_dependencies();
		$this->initialize( $post_id, $args );
	}

	/**
	 * Validate required dependencies
	 *
	 * @throws RuntimeException If dependencies are not met
	 */
	private function validate_dependencies(): void {
		if ( ! function_exists( 'get_field' ) ) {
			throw new RuntimeException(
				'Advanced Custom Fields PRO plugin is required for Sections functionality.'
			);
		}
	}

	/**
	 * Initialize the sections instance
	 *
	 * @param int|null $post_id Post ID
	 * @param array<string, mixed> $args Additional arguments
	 */
	private function initialize( ?int $post_id, array $args ): void {
		$this->set_post_id( $post_id );

        if ( empty($this->post_id) )
        {
            return;
        }

		$this->set_args( $args );
		$this->set_post_type( get_post_type( $this->post_id ) ?: self::POST_TYPE_DEFAULT );
		$this->set_paths();
	}

	/**
	 * Factory method to create instance for specific post
	 *
	 * @param int $post_id Post ID
	 * @param array<string, mixed> $args Additional arguments
	 * @return self
	 */
	public static function create_for_post( int $post_id, array $args = [] ): self {
		return new self( $post_id, $args );
	}

	/**
	 * Factory method to create instance for current post
	 *
	 * @param array<string, mixed> $args Additional arguments
	 * @return self
	 */
	public static function create_for_current_post( array $args = [] ): self {
		return new self( null, $args );
	}

	/**
	 * Initialize the sections functionality
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_sections_post_types' ] );
		add_filter( 'theme_page_templates', [ self::class, 'register_sections_template' ] );
		add_action( 'after_setup_theme', [ self::class, 'add_theme_capabilities' ] );
		add_action( 'acf/save_post', [ self::class, 'save_post_cache' ] );
		add_action( 'acf/render_field', [ self::class, 'append_reusable_edit_link' ] );
		add_filter( 'acf/validate_field', [ self::class, 'filter_reusable_select' ], 100 );
		add_filter( 'acf/location/rule_values', [ self::class, 'append_reusable_select' ], 100 );
	}

	/**
	 * Register sections template
	 *
	 * @param array<string, string> $templates Existing templates
	 * @return array<string, string> Modified templates
	 */
	public static function register_sections_template( array $templates ): array {
		$templates['template-sections'] = 'Sections';
		return $templates;
	}

	/**
	 * Enable adding ACF fields to Reusable Sections post type
	 *
	 * @param array<string, string> $values Location rule values
	 * @return array<string, string> Modified values
	 */
	public static function append_reusable_select( array $values ): array {
		if ( isset( $values['pumpkin_sections'] ) ) {
			$values['pumpkin_reusable'] = __( 'Reusable Sections', 'pumpkin' );
		}

		return $values;
	}

	/**
	 * Remove option to select reusable field inside of a Reusable post type
	 *
	 * @param array<string, mixed> $field ACF field configuration
	 * @return array<string, mixed> Modified field configuration
	 */
	public static function filter_reusable_select( array $field ): array {
		if ( 'flexible_content' === $field['type'] && 'pumpkin_reusable' === get_post_type() ) {
			$field['layouts'] = array_filter(
				$field['layouts'],
				fn( $item ) => $item['name'] !== self::REUSABLE_SECTION_LAYOUT
			);
		}

		return $field;
	}

	/**
	 * Add edit link to reusable sections
	 *
	 * @param array<string, mixed> $field ACF field configuration
	 * @return array<string, mixed> Field configuration
	 */
	public static function append_reusable_edit_link( array $field ): array {
		if ( empty( $field['type'] ) || $field['type'] !== 'post_object' || empty( $field['value'] ) ) {
			return $field;
		}

		$edit_link = get_edit_post_link( $field['value'] );
		if ( $edit_link ) {
			printf(
				'<a href="%s" style="margin-top: 10px;" class="button">%s</a>',
				esc_url( $edit_link ),
				esc_html__( 'Edit Content', 'pumpkin' )
			);
		}

		return $field;
	}

	/**
	 * Register both sections post types
	 */
	public static function register_sections_post_types(): void {
		self::register_hidden_sections_post_type();
		self::register_reusable_sections_post_type();
	}

	/**
	 * Register the hidden post type for ACF sections
	 */
	private static function register_hidden_sections_post_type(): void {
		register_post_type( 'pumpkin_sections', [
			'label'              => __( 'Hidden Field Groups', 'pumpkin' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'can_export'         => false,
			'supports'           => [],
			'delete_with_user'   => false,
		] );
	}

	/**
	 * Register the reusable sections post type
	 */
	private static function register_reusable_sections_post_type(): void {
		register_post_type( 'pumpkin_reusable', [
			'labels'             => self::get_reusable_sections_labels(),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-schedule',
			'show_in_nav_menus'  => false,
			'show_in_admin_bar'  => false,
			'show_in_rest'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => true,
			'can_export'         => false,
			'supports'           => ['title'],
			'delete_with_user'   => false,
		] );
	}

	/**
	 * Get labels for reusable sections post type
	 *
	 * @return array<string, string> Post type labels
	 */
	private static function get_reusable_sections_labels(): array {
		return [
			'name'                  => _x( 'Sections', 'Post type general name', 'pumpkin' ),
			'singular_name'         => _x( 'Section', 'Post type singular name', 'pumpkin' ),
			'menu_name'             => _x( 'Sections', 'Admin Menu text', 'pumpkin' ),
			'name_admin_bar'        => _x( 'Section', 'Add New on Toolbar', 'pumpkin' ),
			'add_new'               => __( 'Add New', 'pumpkin' ),
			'add_new_item'          => __( 'Add New Section', 'pumpkin' ),
			'new_item'              => __( 'New Section', 'pumpkin' ),
			'edit_item'             => __( 'Edit Section', 'pumpkin' ),
			'view_item'             => __( 'View Section', 'pumpkin' ),
			'all_items'             => __( 'All Sections', 'pumpkin' ),
			'search_items'          => __( 'Search Sections', 'pumpkin' ),
			'parent_item_colon'     => __( 'Parent Sections:', 'pumpkin' ),
			'not_found'             => __( 'No Sections found.', 'pumpkin' ),
			'not_found_in_trash'    => __( 'No Sections found in Trash.', 'pumpkin' ),
			'featured_image'        => _x( 'Section Cover Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'pumpkin' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'pumpkin' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'pumpkin' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'pumpkin' ),
			'archives'              => _x( 'Section archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'pumpkin' ),
			'insert_into_item'      => _x( 'Insert into Section', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'pumpkin' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this Section', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post). Added in 4.4', 'pumpkin' ),
			'filter_items_list'     => _x( 'Filter Sections list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'pumpkin' ),
			'items_list_navigation' => _x( 'Sections list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'pumpkin' ),
			'items_list'            => _x( 'Sections list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'pumpkin' ),
		];
	}

	/**
	 * Add capabilities to administrator role on theme activation
	 */
	public static function add_theme_capabilities(): void {
		$roles = [
			get_role( 'author' ),
			get_role( 'editor' ),
			get_role( 'administrator' )
		];

		$capabilities = [
			'create_reusable_sections',
			'delete_others_reusable_sections',
			'delete_reusable_sections',
			'delete_private_reusable_sections',
			'delete_published_reusable_sections',
			'edit_others_reusable_sections',
			'edit_reusable_sections',
			'edit_private_reusable_sections',
			'edit_published_reusable_sections',
			'publish_reusable_sections',
			'read_private_reusable_sections',
		];

		foreach ( $roles as $role ) {
			if ( $role ) {
				foreach ( $capabilities as $capability ) {
					$role->add_cap( $capability );
				}
			}
		}
	}

	/**
	 * Save options as JSON in post_content
	 *
	 * @param int $post_id Post ID
	 * @return void
	 */
	public static function save_post_cache( $post_id ) {

		if ( ! in_array( get_post_type(), [ 'case_study', 'post', 'page' ] ) )
			return;

		ob_start();

		$sections = new \CodeSoup\Pumpkin\ACF\Sections;
		$sections->render_sections();

		$content = ob_get_clean();

		wp_update_post( [
			'ID'           => $post_id,
            'post_type'    => get_post_type(),
			'post_content' => strip_tags( $content, '<a><img><ul><ol><li><p><h1><h2><h3><h4><h5>' ),
		], true );
	}

	/**
	 * Render sections based on provided arguments
	 *
	 * @param array<string, mixed> $args Rendering arguments
	 */
	public function render_sections( array $args = [] ): void {
		$sections = $this->get_sections();
		if ( empty( $sections ) ) {
			return;
		}

		$this->parse_render_args( $args );

		foreach ( $sections as $data ) {
			$this->render_single_section( $data );
		}
	}

	/**
	 * Parse render arguments with defaults
	 *
	 * @param array<string, mixed> $args Render arguments
	 */
	private function parse_render_args( array $args ): void {
		$this->render_params = wp_parse_args( $args, [
			'exclude' => [],
			'include' => [],
		] );
	}

	/**
	 * Render a single section
	 *
	 * @param string|int $name Section name/ID
	 * @param array<string, mixed> $data Section data
	 */
	private function render_single_section( $data ): void {

		static $index = 1;

		if ( is_string( $data ) ) {
			error_log( 'Error loading section, most likely empty' . print_r( $data, true ) );
			return;
		}

		$slug = apply_filters(
			'pumpkin_section_slug',
			$this->normalize_slug( (string) $data['acf_fc_layout'] )
		);

		if ( ! $this->should_render_section( $slug ) ) {
			return;
		}

		if ( empty( $data ) && ! $this->is_skippable_section( $slug ) ) {
			return;
		}

		$template = $this->join_paths(
			$this->get_template_path(),
			'sections',
			$slug
		);

		$section_args = apply_filters( 'pumpkin_section_args', [
			'data'       => $this->get_nested_data( $data ),
			'section_id' => $slug,
			'component'  => isset($this->component) ? $this->component : $this,
		] );

		extract( $section_args );

		$template_file = "$template.php";
		$fallback_file = sprintf(
			'%s/%s/%s/index.php',
			$this->get_theme_directory(),
			self::SHARED_SECTIONS_PATH,
			$slug
		);

		// Try to include the template 
		if ( $this->template_exists_cached( $template_file ) ) {
			include $template_file;
		} elseif ( $this->template_exists( $template_file ) ) {
			include $template_file;
		} elseif ( $this->template_exists( $fallback_file ) ) {
			include $fallback_file;
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					"Section template not found. Expected locations:\nDefault: %s\nFallback: %s",
					$template_file,
					$fallback_file
				)
			);
		}

		$index++;
	}

	private function process_section_data( array $sections ): array {

        return $sections;
	}

    private function get_nested_data( array $data ) : array {

        $nested_data = $data;

        if ( isset($nested_data['clone']) )
        {
            unset( $nested_data['clone']);
            $nested_data = array_merge( $nested_data, $data['clone'] );
        }

        return $nested_data;
    }

	/**
	 * Check if section should be rendered based on parameters
	 *
	 * @param string $slug Section slug
	 * @return bool Whether section should be rendered
	 */
	private function should_render_section( string $slug ): bool {
		if ( ! empty( $this->render_params['exclude'] ) && in_array( $slug, $this->render_params['exclude'], true ) ) {
			return false;
		}

		if ( ! empty( $this->render_params['include'] ) && ! in_array( $slug, $this->render_params['include'], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if section can be skipped when empty
	 *
	 * @param string $slug Section slug
	 * @return bool Whether section can be skipped
	 */
	private function is_skippable_section( string $slug ): bool {
		$skip = apply_filters( 'pumpkin_skip_render_section', [] );
		return in_array( $slug, $skip, true );
	}

	/**
	 * Check if template exists with caching
	 *
	 * @param string $template_path Template path
	 * @return bool Whether template exists
	 */
	private function template_exists_cached( string $template_path ): bool {
		if ( ! isset( self::$template_cache[ $template_path ] ) ) {
			self::$template_cache[ $template_path ] = $this->template_exists( $template_path );
		}

		return self::$template_cache[ $template_path ];
	}

	/**
	 * Set template paths
	 */
	private function set_paths(): void {
		$post_type = $this->get_post_type();
		$theme_uri = untrailingslashit( get_stylesheet_directory_uri() );

		// Get template name
		$template = str_replace(
			[ 'template-', '.php' ],
			'',
			get_post_meta( $this->get_post_id(), '_wp_page_template', true )
		);

		$this->set_template_name( $template );

		// Set template path
		$template_path = $this->normalize_path(
			sprintf( '%s/%s/%s', self::POST_TYPE_PATH, $post_type, $template )
		);

		$this->set_template_path(
			apply_filters( 'pumpkin_section_template_path', $template_path )
		);

		// Set template URI
		$template_uri = wp_make_link_relative(
			sprintf(
				'%s/%s/%s/%s',
				$theme_uri,
				self::POST_TYPE_PATH,
				$post_type,
				$template
			)
		);

		$this->set_template_uri(
			apply_filters( 'pumpkin_section_template_uri', $template_uri )
		);
	}

	/**
	 * Get template path
	 *
	 * Required by AssetLoader trait
	 *
	 * @return string Template path
	 */
	protected function get_template_path(): string {
		return $this->join_paths(
			$this->get_theme_directory(),
			self::POST_TYPE_PATH,
			$this->get_post_type(),
			$this->get_template_name()
		);
	}

	/**
	 * Get template URI
	 *
	 * Required by AssetLoader trait
	 *
	 * @return string Template URI
	 */
	protected function get_template_uri(): string {
		return $this->join_paths(
			untrailingslashit( get_stylesheet_directory_uri() ),
			self::POST_TYPE_PATH,
			str_replace( '_', '-', $this->get_post_type() ),
			$this->get_template_name()
		);
	}

	/**
	 * Load sections data from ACF (lazy loading)
	 */
	private function load_sections_data(): void {
		$sections = get_field(
			$this->get_arg( 'field_name' ),
			$this->get_post_id()
		);

		if ( ! is_array( $sections ) ) {
			$sections = [];
		}

		if ( empty( $sections ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'No ACF postmeta data found for post_id "%d", and field group "%s"',
					$this->get_post_id(),
					$this->get_arg( 'field_name' )
				)
			);
		}

		$this->sections = $this->process_section_data( $sections );
	}

	// Property setters with validation
	private function set_post_id( ?int $post_id ): void {
        $qo = get_queried_object();
		$resolved_id = $post_id === null || $post_id === 0 ? get_the_ID() : $post_id;
        
		if ( ! $resolved_id || ! get_post( $resolved_id ) ) {
            $this->post_id = 0;
            return;
		}

		$this->post_id = $resolved_id;
	}

	private function set_args( array $args ): void {
		$this->args = wp_parse_args( $args, [
			'field_name' => self::ACF_FIELD_NAME_DEFAULT,
		] );
	}

	private function set_sections( array $sections ): void {
		$this->sections = $sections;
	}

	private function set_post_type( string $post_type ): void {
		$this->post_type = sanitize_key( $post_type );
	}

	private function set_template_name( string $template_name ): void {
		$this->template_name = sanitize_file_name( $template_name );
	}

	private function set_template_path( string $template_path ): void {
		$this->template_path = $template_path;
	}

	private function set_template_uri( string $template_uri ): void {
		$this->template_uri = $template_uri;
	}

	// Property getters
	public function get_post_id(): int {
		return $this->post_id;
	}

	public function get_args(): array {
		return $this->args;
	}

    public function get_render_params() {
        return $this->render_params;
    }

	public function get_arg( string $key ): mixed {
		return $this->args[ $key ] ?? null;
	}

	public function get_sections(): array {
		if ( $this->sections === null ) {
			$this->load_sections_data();
		}

		return $this->sections;
	}

	public function get_post_type(): string {
		return $this->post_type;
	}

	public function get_template_name(): string {
		return $this->template_name;
	}

	/**
	 * Get debug information about the sections instance
	 *
	 * @return array<string, mixed> Debug information
	 */
	public function get_debug_info(): array {
		return [
			'post_id'        => $this->post_id,
			'post_type'      => $this->post_type,
			'template_name'  => $this->template_name,
			'sections_count' => count( $this->get_sections() ),
			'template_path'  => $this->template_path,
			'template_uri'   => $this->template_uri,
			'args'           => $this->args,
		];
	}

	/**
	 * Get section slug
	 *
	 * @param string $name Section name
	 * @return string Normalized slug
	 */
	private function normalize_slug( string $name ): string {
		$slug = str_replace( ['section'], '', $name );
		$slug = str_replace( '_', '-', sanitize_title( $slug ) );

		return trim( $slug, '-' );
	}

	/**
	 * Get asset from current template
	 * 
	 *
	 * @param string $filename Asset filename
	 * @param array<string, mixed> $args Asset arguments
	 * @return string|null Asset HTML or null if file doesn't exist
	 */
	public function get_asset( string $filename, array $args = [] ): ?string {
		return $this->get_template_asset( $filename, $args );
	}

	/**
	 * Get asset URI
	 *
	 * @param string $filename Asset filename
	 * @return string|null Asset URI or null if file doesn't exist
	 */
	public function get_asset_uri( string $filename ): ?string {
		return $this->get_template_asset_uri( $filename );
	}

	/**
	 * Calculate reading time in minutes based on character count
	 *
	 * @param string $content The content to analyze
	 * @param int    $wpm     Words per minute reading speed (default: 200)
	 * @return int Reading time in minutes (minimum 1)
	 */
	public function get_reading_time_minutes( string $content, int $wpm = 200 ): int {
		$char_count = mb_strlen( wp_strip_all_tags( $content ) );
		$word_count = ceil( $char_count / 5.5 );
		$minutes    = (int) ceil( $word_count / $wpm );

		return max( 1, $minutes );
	}
}
