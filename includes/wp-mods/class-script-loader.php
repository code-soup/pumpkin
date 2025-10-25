<?php

namespace CodeSoup\Pumpkin\WpMods;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die();

use CodeSoup\Pumpkin\Core\PageConfig;
use CodeSoup\Pumpkin\Utils\ScriptLoader as ScriptLoaderTrait;
use CodeSoup\Pumpkin\WpMods\TemplateLoader;

/**
 * Script Loader Class
 *
 * Handles WordPress script and style loading including:
 * - Template-specific asset loading
 * - Async loading of assets
 * - Asset versioning
 */
final class ScriptLoader {
	use ScriptLoaderTrait;

	/**
	 * Hooker instance
	 *
	 * @var Hooker
	 */
	private \CodeSoup\Pumpkin\Core\Hooker $hooker;

	/**
	 * Scripts to be deferred
	 *
	 * @var array<string>
	 */
	private array $defer_scripts = [
		'gform_json',
		'gform_gravityforms',
		'gform_placeholder',
		'wp-block-library',
		'regenerator-runtime',
	];

	/**
	 * Scripts to be removed
	 *
	 * @var array<string>
	 */
	private array $remove_scripts = [
		'wp-block-library',
		'regenerator-runtime',
	];

	/**
	 * jQuery related scripts
	 *
	 * @var array<string>
	 */
	private array $jquery_scripts = [ 'jquery', 'jquery-core', 'jquery-migrate' ];

	/**
	 * Constructor
	 *
	 * @param Hooker $hooker Hooker instance for registering hooks
	 */
	public function __construct( \CodeSoup\Pumpkin\Core\Hooker $hooker ) {
		$this->hooker = $hooker;
		$this->init();
	}

	/**
	 * Initialize the script loader
	 */
	private function init(): void {
		// Apply filter to defer scripts array
		$this->defer_scripts = apply_filters(
			'pumpkin_defer_scripts',
			$this->defer_scripts,
		);

		// Apply filter to remove scripts array
		$this->remove_scripts = apply_filters(
			'pumpkin_remove_scripts',
			$this->remove_scripts,
		);

		// Register hooks
		$this->register_hooks();
	}

	/**
	 * Register all hooks
	 */
	private function register_hooks(): void {
		// Frontend hooks
		$this->hooker->add_action( 'wp_enqueue_scripts', $this );

		// Backend hooks
		$this->hooker->add_action( 'admin_enqueue_scripts', $this );

		// Cleanup
		$this->hooker->add_filter(
			'script_loader_tag',
			$this,
			'defer_scripts_load',
			10,
			3,
		);
		$this->hooker->add_filter(
			'style_loader_tag',
			$this,
			'defer_scripts_load',
			10,
			3,
		);
	}

	/**
	 * Load Frontend scripts and styles
	 */
	public function wp_enqueue_scripts(): void {
		// Get template name - use fallback if TemplateLoader not ready yet
		$template_name = $this->get_current_template_name();

		// Check if jQuery is enabled at runtime
		$is_jquery_enabled = PageConfig::getConfig( 'ENABLE_JQUERY' );

		// Filter scripts to remove based on jQuery configuration
		$scripts_to_remove = $this->remove_scripts;
		if ( ! $is_jquery_enabled ) {
			$scripts_to_remove = array_merge(
				$scripts_to_remove,
				$this->jquery_scripts,
			);
		}


		// Remove scripts that should be dequeued
		if ( ! empty( $scripts_to_remove ) && ! is_admin() ) {
			foreach ( $scripts_to_remove as $script ) {
				wp_dequeue_script( $script );
				wp_deregister_script( $script );
			}
		}

		// Enqueue common JS
		$runtime_js = self::get_asset( 'runtime.js' );
		$vendor_js  = self::get_asset( 'vendor-libs.js' );
		$common_js  = self::get_asset( 'common.js' );

		if ( $common_js ) {
			wp_enqueue_script( 'pumpkin-runtime-js', $runtime_js, [], null, true );
			wp_enqueue_script( 'pumpkin-vendor-js', $vendor_js, ['pumpkin-runtime-js'], null, true );
			wp_enqueue_script( 'pumpkin-common-js', $common_js, [ 'pumpkin-runtime-js', 'pumpkin-vendor-js', 'jquery' ], null, true );

			wp_localize_script( 'pumpkin-common-js', 'pumpkin', [
				'ajax_url' => wp_make_link_relative( admin_url( 'admin-ajax.php' ) ),
				'nonce'    => wp_create_nonce( 'pumpkin-frontend' ),
				'page_id'  => get_the_ID(),
			] );
		}

		// Enqueue vendor CSS
		$vendor_css = self::get_asset( 'vendor-libs.css' );
		if ( $vendor_css ) {
			wp_enqueue_style( 'pumpkin-vendor-css', $vendor_css, [], null );
		}

		// Enqueue common CSS
		$common_css = self::get_asset( 'common.css' );
		if ( $common_css ) {
			wp_enqueue_style( 'pumpkin-common-css', $common_css, ['pumpkin-vendor-css'], null );
		}

		// Enqueue template specific JS if exists
		$template_js = self::get_asset( $template_name . '.js' );
		if ( $template_js ) {
			wp_enqueue_script(
				'pumpkin-template-' . $template_name,
				$template_js,
				['jquery'],
				null,
				true,
			);
		}

		// Enqueue template specific CSS if exists
		$template_css = self::get_asset( $template_name . '.css' );
		if ( $template_css ) {
			wp_enqueue_style(
				'pumpkin-template-' . $template_name,
				$template_css,
				[],
				null,
			);
		}

		// Enqueue async template specific JS if exists
		$template_async_js = self::get_asset( $template_name . '-async.js' );
		if ( $template_async_js ) {
			wp_enqueue_script(
				'pumpkin-template-' . $template_name . '-async',
				$template_async_js,
				['jquery'],
				null,
				true,
			);
		}

		// Enqueue async template specific CSS if exists
		$template_async_css = self::get_asset( $template_name . '-async.css' );
		if ( $template_async_css ) {
			wp_enqueue_style(
				'pumpkin/template-' . $template_name . '-async',
				$template_async_css,
				[],
				null,
			);
		}
	}

	/**
	 * Modifies script and style tags to implement deferred loading strategy
	 *
	 * @param string $tag    The original HTML tag
	 * @param string $handle The script/style handle
	 * @param string $src    The script/style source URL
	 *
	 * @return string Modified HTML tag
	 */
	public function defer_scripts_load(
		string $tag,
		string $handle,
		string $src,
	): string {
		// Early return for admin users
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return $tag;
		}

		// Check if script should be deferred
		$should_defer =
			in_array( $handle, $this->defer_scripts, true )
			|| str_contains( $src, '-async' )
			|| str_contains( $handle, 'defer' );

		if ( $should_defer ) {
			$tag = str_replace( ' src', ' defer src', $tag ); // For scripts
			$tag = str_replace( ' href', ' defer href', $tag ); // For styles
		}

		// Add noscript fallback for print stylesheets
		if ( str_contains( $tag, 'print' ) && ! str_contains( $tag, '</noscript>' ) ) {
			$tag .= sprintf(
				'<noscript>%s</noscript>',
				str_replace( 'print', 'all', $tag ),
			);
		}

		return str_replace( 'defer defer', 'defer', $tag );
	}

	/**
	 * Load backend scripts and styles
	 */
	public function admin_enqueue_scripts(): void {

        $runtime_js = self::get_asset( 'runtime.js' );
		$vendor_js  = self::get_asset( 'vendor-libs.js' );
		$admin_js  = self::get_asset( 'admin.js' );
		$admin_css = self::get_asset( 'admin.css' );

		if ( $admin_js ) {
            wp_enqueue_script( 'admin-runtime-js', $runtime_js, [], null, true );
            wp_enqueue_script( 'admin-vendor-js', $vendor_js, ['admin-runtime-js'], null, true );
			wp_enqueue_script( 'pumpkin-wp-js', $admin_js, ['admin-runtime-js', 'admin-vendor-js', 'jquery'], null, true );

			wp_localize_script( 'pumpkin-wp-js', 'pumpkin', [
				'page_id'  => get_the_ID(),
				'nonce'    => wp_create_nonce( 'pumpkin-wp' ),
				'ajax_url' => wp_make_link_relative(
					admin_url( 'admin-ajax.php' ),
				),
                'post_type' => get_post_type(),
			] );
		}

		if ( $admin_css ) {
			wp_enqueue_style( 'pumpkin-wp-css', $admin_css, false, null );
		}
	}

	/**
	 * Get current template name safely
	 *
	 * @return string
	 */
	private function get_current_template_name(): string {
		// Try to get from TemplateLoader if it exists and is initialized
		if (class_exists('\CodeSoup\Pumpkin\WpMods\TemplateLoader')) {
			try {
				$template_loader = TemplateLoader::get_instance();
				$template_name = $template_loader->get_template_name();
				if ($template_name) {
					return $template_name;
				}
			} catch (Exception $e) {
				// TemplateLoader not ready yet, fall back to WordPress detection
			}
		}

		// Fallback: detect template from WordPress globals
		if (is_front_page()) {
			return 'front-page';
		} elseif (is_home()) {
			return 'home';
		} elseif (is_single()) {
			return 'single';
		} elseif (is_page()) {
			return 'page';
		} elseif (is_archive()) {
			return 'archive';
		} elseif (is_search()) {
			return 'search';
		} elseif (is_404()) {
			return '404';
		}

		return 'index';
	}
}
