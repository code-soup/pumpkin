<?php

namespace CS\Setup;

use CS\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * NoFramework Environemnt class
 *
 *
 * @author  Vlado Bosnjak
 * @link    https://www.bobz.co
 * @version 1.2
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CS {

	public $cs;

	private $sidebars;


	public static function init() {
		$class = __CLASS__;
		new $class;
	}

	/**
	 * Constructor on WP init hook
	 */
	public function __construct() {


		/**
		 * Load Settings and Sidebars
		 */
		if ( function_exists('get_fields') ) :

			$this->load_options();
			$this->register_sidebars();
			$this->set_sidebar();
			$this->user_custom_scripts();
		endif;


		/**
		 * Actions
		 */

		// Add Favicon to head
		add_action('wp_head', [ $this, 'set_favicon']);


		// Load Theme Scripts
		add_action('wp_enqueue_scripts', [$this, 'scripts_frontend'] , 100);
		add_action('admin_enqueue_scripts', [$this, 'scripts_backend'] , 100);



		/**
		 * Filters
		 */
		add_filter('body_class', [$this, 'body_class'], 20);
	}



	/**
	 *
	 *
	 * T H E M E   O P T I O N S
	 *
	 *
	 */


	/**
	 * Load Theme Options
	 *
	 * This function loads theme options and saves it as a transient.
	 * Every time Theme options are saved, cache is flushed and re-generated to get new data
	 * This will prevent to many DB Queries made by ACF
	 */
	private function load_options() {

		global $cs;

		/**
		 * Delete Options Transient when Options page is saved
		 */
		add_action('acf/save_post', function ($post_id) {

			if ( $post_id == 'options' )
				delete_transient( 'cs_options' );

		}, 1);




		/**
		 * Load Options as transient
		 */
		$cs = get_transient('cs_options');

		if ( $cs === false ) {

			$cs = get_fields('options');
			set_transient('cs_options', $cs, 3600 * 24);


			$css = get_key('cs_custom_css', $cs);

			if ( $css ) :

				$custom_css = fopen( get_theme_file_path('custom.css') , 'w');

				fwrite($custom_css, $css);
				fclose($custom_css);
			endif;
		}
	}









	/**
	 * Load and register Sidebars from options page
	 */
	private function register_sidebars() {


		if ( get_key('cs_sidebars') ) :
			foreach ( get_key('cs_sidebars') as $sidebar ) :

				register_sidebar([
					'name'          => $sidebar['sidebar_name'],
					'id'            => sanitize_title( $sidebar['sidebar_name'] ),
					'before_widget' => '<section class="widget %s %s">',
					'after_widget'  => '</section>',
					'before_title'  => '<h2 class="entry-title">',
					'after_title'   => '</h2>',
				]);

			endforeach;
		endif;

	}





	/**
	 * Get Selected sidebar for current template
	 */
	public function set_sidebar() {


		add_action( 'the_post', function ($post) {

			if ( ! function_exists('get_field') || is_admin() )
				return;


			/**
			 * Set Sidebar per post type
			 */
			switch ( get_key('post_type', $post) ) :

				case 'page':

					$post->sidebar = get_post_meta($post->ID, 'select_sidebar', true);
				break;
			endswitch;

		}, 10, 1);
	}









	/**
	 *
	 *
	 * A C T I O N S
	 *
	 *
	 */



	/**
	 * Theme options and settings
	 */
	public static function setup() {

		load_theme_textdomain('cs', get_template_directory() . '/lang');

		// Enable plugins to manage the document title
		// http://codex.wordpress.org/Function_Reference/add_theme_support#Title_Tag
		add_theme_support('title-tag');

		// Register wp_nav_menu() menus
		// http://codex.wordpress.org/Function_Reference/register_nav_menus
		register_nav_menus([
			'nav_primary'   => __('Primary Navigation', 'cs'),
			'nav_offcanvas' => __('Mobile Navigation', 'cs'),
		]);

		// Enable post thumbnails
		// http://codex.wordpress.org/Post_Thumbnails
		// http://codex.wordpress.org/Function_Reference/set_post_thumbnail_size
		// http://codex.wordpress.org/Function_Reference/add_image_size
		add_theme_support('post-thumbnails');


		// Enable HTML5 markup support
		// http://codex.wordpress.org/Function_Reference/add_theme_support#HTML5
		add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);


		// Soli Cleanup plugin
		add_theme_support('soil-clean-up');
		add_theme_support('soil-disable-asset-versioning');
		add_theme_support('soil-disable-trackbacks');
		add_theme_support('soil-jquery-cdn');
		add_theme_support('soil-nav-walker');
		add_theme_support('soil-nice-search');
		add_theme_support('soil-relative-urls');

		// Theme functions
		add_theme_support( 'woocommerce' );
		add_theme_support( 'cs_sidebars' );


		remove_theme_support( 'starter-content' );
	}










	/**
	 * Load Frontend scripts and styles
	 */
	public function scripts_frontend() {

		/**
		 * Include Google Fonts WP way
		 */
		$family = [
			'family' => 'Open+Sans:400,600,700|Sanchez'
		];
		//wp_enqueue_style( 'cs/font', add_query_arg( $family, "//fonts.googleapis.com/css" ), [], null );


		/**
         * Include Typekit fonts from Theme Options WP way
         */
        $typekit = get_key('cs_typekit');

        if ( strpos( $typekit, 'css' ) )
        {
            wp_enqueue_style('cs/typekit', trim( $typekit ), false, null);
        }
        elseif ( strpos( $typekit, 'js' ) )
        {
            wp_enqueue_script( 'cs/typekit', $typekit, [], null );
            wp_add_inline_script( 'cs/typekit', 'try{Typekit.load({ async: true });}catch(e){}' );
        }

		// Theme CSS
		wp_enqueue_style('cs/css', Assets\asset_path('styles/main.css'), false, null);

		// Custom CSS from Theme Options
		if( file_exists( get_stylesheet_directory() . '/custom.css') && get_key('cs_custom_css') )
        {
			wp_enqueue_style('cs/custom_css', $this->theme_uri('/custom.css'), false, null);
		}


		// Scripts
		if ( is_single() && comments_open() && get_option('thread_comments'))
			wp_enqueue_script('comment-reply');

		wp_enqueue_script('cs/js', Assets\asset_path('scripts/main.js'), ['jquery'], null, true);


		/**
		 * Conditionally include JS based on ACF widget used on page

		$meta = get_post_meta( get_the_ID(), 'page_widgets', true);

		if ( is_array($meta) ) :

			if ( in_array('wgt_form', $meta) ) {
				wp_enqueue_script('js/forms');
			}
		endif; */


		wp_localize_script( 'cs/js', 'cs', [
			'nonce'    => wp_create_nonce('nonce'),
			'ajax_url' => admin_url('admin-ajax.php'),
			'assets'   => $this->theme_uri('dist/')
		]);
	}






	/**
	 * Load backend scripts and styles
	 */
	public function scripts_backend() {

		wp_enqueue_script('cs/wp-js', Assets\asset_path('scripts/admin.js'), ['jquery'], null, true);
		wp_enqueue_style('cs/wp-css', Assets\asset_path('styles/admin.css'), false, null);
	}




	public function theme_uri( $file ){

		return trailingslashit( get_stylesheet_directory_uri() ) .  $file;
	}













	/**
	 *
	 *
	 * F I L T E R S
	 *
	 *
	 */
	public function user_custom_scripts() {

		if ( get_key('cs_head') ) {

			add_action('wp_head', function() {
				the_key('cs_head');
			});
		}


		if ( get_key('cs_body_open') ) {

			add_action('get_header', function() {
				the_key('cs_body_open');
			});
		}


		if ( get_key('cs_body_close') ) {

			add_action('wp_footer', function() {
				the_key('cs_body_close');
			});
		}
	}



	/**
	 * Filter body class
	 */
	public function body_class( $classes ) {

		if ( get_key('sidebar') ) {

			$classes[] = 'has-sidebar';
			$classes[] = get_key('sidebar');
		}

		return array_unique($classes);
	}






	/**
	 * Add favicon in head
	 */
	public function set_favicon() {

		$src = false;
		$img = get_key('cs_favicon');


		if ( is_numeric($img) ) :

			$src = wp_get_attachment_image_src( $img, 'full');
			$src = $src[0];

		elseif ( get_key('url', $img) ) :

			$src = $img['url'];

		elseif ( is_string($img) ) :

			$src = $img;
		endif;


		if ( ! $src )
			return; ?>

		<link rel="icon" type="image/png" href="<?php echo $src; ?>"/>

	<?php }
}
add_filter('init', [__NAMESPACE__ . '\\CS', 'init']);
add_action('after_setup_theme', [ __NAMESPACE__ . '\\CS', 'setup']);