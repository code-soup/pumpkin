<?php

namespace CodeSoup\Pumpkin\WpMods;

defined( 'ABSPATH' ) || die;

/**
 * Admin Customizations Class
 *
 * Handles WordPress admin customizations and modifications.
 * Only loads in admin context for performance.
 *
 * @since 1.0.0
 */
final class AdminCustomizations {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Hooker instance for managing hooks
	 *
	 * @var \CodeSoup\Pumpkin\Core\Hooker|null
	 */
	private $hooker;

	/**
	 * Get singleton instance
	 *
	 * @param \CodeSoup\Pumpkin\Core\Hooker|null $hooker Hooker instance
	 * @return self
	 */
	public static function get_instance( $hooker = null ): self {
		if ( null === self::$instance ) {
			self::$instance = new self( $hooker );
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @param \CodeSoup\Pumpkin\Core\Hooker|null $hooker Hooker instance for registering hooks
	 */
	private function __construct( $hooker = null ) {
		$this->hooker = $hooker;
	}

	/**
	 * Initialize admin customizations
	 */
	public function init(): void {
		$this->register_hooks();
	}

	/**
	 * Register admin hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		$this->hooker->add_action( 'admin_init', $this );
		$this->hooker->add_action( 'admin_head', $this );
		$this->hooker->add_action( 'admin_bar_menu', $this, 'admin_bar_menu', 999 );
		$this->hooker->add_action( 'wp_dashboard_setup', $this );

		$this->hooker->add_filter( 'admin_footer_text', $this );
		$this->hooker->add_filter( 'update_footer', $this );
	}

	/**
	 * Initialize admin customizations
	 *
	 * @return void
	 */
	public function admin_init(): void {
		// Remove unnecessary meta boxes
		$this->remove_meta_boxes();
		
		// Remove plugin update notices for non-admin users
		if ( ! current_user_can( 'update_plugins' ) ) {
			remove_all_actions( 'admin_notices' );
		}
	}

	/**
	 * Customize admin menu
	 *
	 * @return void
	 */
	public function admin_head(): void {
		// Remove unnecessary menu items
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'link-manager.php' );

		// Remove customizer, patterns, and theme editor
		remove_submenu_page( 'themes.php', 'customize.php?return=' . urlencode($_SERVER['REQUEST_URI']) );
		remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );
		remove_submenu_page( 'themes.php', 'theme-editor.php' );
	}

	/**
	 * Customize admin bar
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
	 * @return void
	 */
	public function admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		// Remove WordPress logo
		$wp_admin_bar->remove_node( 'wp-logo' );
		
		// Remove comments
		$wp_admin_bar->remove_node( 'comments' );
	}


	/**
	 * Customize dashboard
	 *
	 * @return void
	 */
	public function wp_dashboard_setup(): void {
		// Remove default dashboard widgets
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		remove_meta_box( 'rg_forms_dashboard', 'dashboard', 'normal' );
	}

	/**
	 * Customize admin footer text
	 *
	 * @return string
	 */
	public function admin_footer_text(): string {
		return '';
	}

	/**
	 * Customize update footer text
	 *
	 * @return string
	 */
	public function update_footer(): string {
		return '';
	}

	/**
	 * Remove unnecessary meta boxes
	 *
	 * @return void
	 */
	private function remove_meta_boxes(): void {
		// Remove from posts
		remove_meta_box( 'commentsdiv', 'post', 'normal' );
		remove_meta_box( 'trackbacksdiv', 'post', 'normal' );
		remove_meta_box( 'postcustom', 'post', 'normal' );
		
		// Remove from pages
		remove_meta_box( 'commentsdiv', 'page', 'normal' );
		remove_meta_box( 'trackbacksdiv', 'page', 'normal' );
		remove_meta_box( 'postcustom', 'page', 'normal' );
	}
}
