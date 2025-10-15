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
	 * Initialize the bootstrap
	 */
	private function init(): void {
		// Register custom autoloader for components
		\CodeSoup\Pumpkin\Core\Component::register_autoloader();
        

		// Initialize core components
		$this->hooker = new Hooker();

		// Initialize other components
		\CodeSoup\Pumpkin\WpMods\TemplateLoader::get_instance();
		new \CodeSoup\Pumpkin\WpMods\ScriptLoader( $this->hooker );
		new \CodeSoup\Pumpkin\WpMods\ThemeSetup( $this->hooker );


		// Initialize ACF configuration
		\CodeSoup\Pumpkin\ACF\Setup::get_instance()->init();
        \CodeSoup\Pumpkin\ACF\Options::get_instance()->init();

		// Run all hooks here
		$this->hooker->run();
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
