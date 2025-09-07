<?php
/**
 * ACF Configuration and Integration File
 *
 * This file handles the Advanced Custom Fields (ACF) plugin integration and configuration
 * for the Pumpkin theme. It sets up paths, URLs, and JSON save/load locations for ACF.
 *
 * @package CodeSoup\Pumpkin
 * @subpackage ACF
 */

namespace CodeSoup\Pumpkin\ACF;

use CodeSoup\Pumpkin\Utils\TemplateUtilities;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

/**
 * ACF Configuration Manager
 *
 * Handles all ACF-related configuration and initialization using singleton pattern.
 */
class Setup {
	use TemplateUtilities;

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * ACF relative path
	 *
	 * @var string
	 */
	private string $acf_rel_path = '/vendor/advanced-custom-fields-pro';

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		// Theme root is now handled by TemplateUtilities trait
	}

	/**
	 * Gets the singleton instance
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
	 * Initializes ACF configuration
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! $this->validate_acf_installation() ) {
			return;
		}

		$this->define_constants();
		$this->register_hooks();
		$this->initialize_sections();
	}

	/**
	 * Validates ACF installation
	 *
	 * @return bool
	 */
	private function validate_acf_installation(): bool {
		$acf_path = $this->join_paths( $this->get_theme_directory(), $this->acf_rel_path, 'acf.php' );
		return $this->template_exists( $acf_path );
	}

	/**
	 * Defines ACF-related constants for paths and URLs
	 *
	 * Sets up the necessary constants for ACF plugin integration:
	 * - Requires the main ACF plugin file
	 * - Defines PUMPKIN_ACF_PATH for filesystem path to ACF
	 * - Defines PUMPKIN_ACF_URL for URL path to ACF
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$acf_path = $this->join_paths( $this->get_theme_directory(), $this->acf_rel_path, 'acf.php' );

		require_once $acf_path;

		define( 'PUMPKIN_ACF_PATH', $this->join_paths( $this->get_theme_directory(), $this->acf_rel_path ) . '/' );
		define( 'PUMPKIN_ACF_URL', get_stylesheet_directory_uri() . $this->acf_rel_path . '/' );
	}

	/**
	 * Registers all ACF related hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
        
		add_filter( 'acf/settings/url', [ $this, 'filter_acf_url' ] );
		add_filter( 'acf/settings/dir', [ $this, 'filter_acf_dir' ] );
		add_filter( 'acf/settings/path', [ $this, 'filter_acf_path' ] );
		add_filter( 'acf/settings/save_json', [ $this, 'set_json_save_path' ] );
		add_filter( 'acf/settings/load_json', [ $this, 'set_json_load_path' ] );
		add_filter( 'acf/location/rule_values', [ $this, 'filter_rule_values' ], 20, 2 );
		add_filter( 'acf/settings/show_admin', [ $this, 'hide_acf_admin' ] );
	}

	/**
	 * Initializes ACF sections
	 *
	 * @return void
	 */
	private function initialize_sections(): void {
		Sections::init();
	}

	/**
	 * Filters ACF plugin URL
	 *
	 * @param string $url The original ACF plugin URL
	 * @return string
	 */
	public function filter_acf_url( string $url ): string {
		return PUMPKIN_ACF_URL;
	}

	/**
	 * Filters ACF plugin directory
	 *
	 * @param string $dir The original ACF plugin directory
	 * @return string
	 */
	public function filter_acf_dir( string $dir ): string {
		return PUMPKIN_ACF_URL;
	}

	/**
	 * Filters ACF plugin path
	 *
	 * @param string $path The original ACF plugin path
	 * @return string
	 */
	public function filter_acf_path( string $path ): string {
		return PUMPKIN_ACF_PATH;
	}

	/**
	 * Sets custom JSON save location for ACF field groups
	 *
	 * @return string
	 */
	public function set_json_save_path(): string {
		return $this->join_paths( $this->get_theme_directory(), 'includes', 'acf', 'json' );
	}

	/**
	 * Sets custom JSON load location for ACF field groups
	 *
	 * @param array $paths Array of paths where ACF will look for JSON files
	 * @return array
	 */
	public function set_json_load_path( array $paths ): array {
		unset( $paths[0] );
		$paths[] = $this->join_paths( $this->get_theme_directory(), 'includes', 'acf', 'json' );
		return $paths;
	}

	/**
	 * Filters ACF location rule values
	 * Hide reusable section from location select
	 *
	 * @param array $values The original rule values
	 * @param array $rule The current rule being processed
	 * @return array
	 */
	public function filter_rule_values( array $values, array $rule ): array {
		if ( isset( $values['pumpkin_reusable'] ) ) {
			unset( $values['pumpkin_reusable'] );
		}

		return $values;
	}

	/**
	 * Hide ACF admin menu if not development environment
	 *
	 * @param bool $show_admin Whether to show ACF admin
	 * @return bool
	 */
	public function hide_acf_admin( bool $show_admin ): bool {
		return ('local' === wp_get_environment_type());
	}
}
