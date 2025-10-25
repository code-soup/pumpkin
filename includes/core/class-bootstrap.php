<?php

namespace CodeSoup\Pumpkin\Core;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

/**
 * Bootstrap Class
 *
 * Manages core theme components and provides access to shared instances
 *
 * Implements Singleton pattern to ensure only one instance exists throughout the application
 */
class Bootstrap {
	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Hooker instance
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

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
		// Initialize core components
		$this->hooker = new Hooker();
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
	 * Initialize the bootstrap
	 */
	public function init(): void {
		// Register custom autoloader for components
		\CodeSoup\Pumpkin\Core\Component::register_autoloader();

		// Initialize core components (not template-dependent)
		new \CodeSoup\Pumpkin\WpMods\ScriptLoader( $this->hooker );
		new \CodeSoup\Pumpkin\WpMods\ThemeSetup( $this->hooker );

		// Initialize ACF configuration
		\CodeSoup\Pumpkin\ACF\Setup::get_instance()->init();
        \CodeSoup\Pumpkin\ACF\Options::get_instance()->init();

		// Initialize admin-specific components
		if ( is_admin() ) {
			\CodeSoup\Pumpkin\WpMods\TemplateAdmin::get_instance( $this->hooker )->init();
			\CodeSoup\Pumpkin\WpMods\AdminCustomizations::get_instance( $this->hooker )->init();
		}

		// Run all hooks here
		$this->hooker->run();
	}


	/**
	 * Initialize TemplateLoader on template_redirect for WooCommerce compatibility
	 */
	public function init_template_redirect(): void {

		add_action('template_redirect', function() {

			static $template_initialized = false;

			// Only run once
			if ($template_initialized) {
				return;
			}

			// Only run in frontend
			if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
				return;
			}

			// Skip in autosave or cron
			if (defined('DOING_AUTOSAVE') || defined('DOING_CRON')) {
				return;
			}

			// Initialize TemplateLoader and setup template wrapper
			$template_loader = \CodeSoup\Pumpkin\WpMods\TemplateLoader::get_instance();
			$template_loader->init_template_wrapper();

			$template_initialized = true;
		}, 5);
	}

	/**
	 * Get the Hooker instance
	 *
	 * @return Hooker
	 */
	public function get_hooker(): Hooker {
		return $this->hooker;
	}

	/**
	 * Get the ScriptLoader instance
	 *
	 * @return ScriptLoader
	 */
	public function get_script_loader(): ScriptLoader {
		return $this->script_loader;
	}
}
