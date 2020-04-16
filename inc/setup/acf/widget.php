<?php

namespace CS\Setup\ACF;

if ( ! defined( 'ABSPATH' ) )
	exit;

use CS\utils\Helpers;

class Widget {


	function register () {
		add_action('init', [$this, 'init'], 456 );
		add_filter('body_class', [$this, 'body_class_setup'], 99, 1);
	}


	public static function init( ) {
		$class = __CLASS__;
		new $class;
	}


	public function __construct() {

		if ( ! function_exists('get_fields') )
			return;

		// Post Type
		$this->register_cpt();




		/**
		 * Hook into post and get post meta info
		 */
		add_action( 'the_post', function ($post) {

			if ( ! function_exists('get_field') || is_admin() )
				return;


			if ( is_singular(['page']) )
				$post->meta = get_field('page_widgets');

		}, 10, 1);
	}







	/**
	 * Register Page widgets post type
	 */
	public function register_cpt() {

		$args = [
			'label '             => 'ACF Page Widget',
			'description'        => __( 'Description.', 'pumpkin' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => array( 'slug' => 'page-widget' ),
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => true,
			'menu_position'      => -1,
			'supports'           => ['title']
		];

		register_post_type( 'page_widget', $args );
	}



	/**
	 * Add widget slug as a body class, this is used to target loading of javascript files based on body class
	 */
	public static function body_class_setup( $classes ) {

		$meta = get_post_meta( get_the_ID(), 'page_widgets', true );

        if ( ! $meta )
            return array_unique($classes);

        foreach ( $meta as $wgt )
        {
            $classes[] = sanitize_title( str_replace('wgt', 'pw', $wgt) );
		}

		return array_unique($classes);
	}





	/**
	 * Get widget data for custom page
	 */
	public static function get_field( $page_id = false ) {

		if ( ! function_exists('get_field') || ! $page_id )
			return;

		return get_field('page_widgets', $page_id);
	}





	/**
	 * Get Slug
	 */
	private static function get_slug( $wgt ) {

		if ( Helpers::get_key('acf_fc_layout', $wgt) )
        {
			return str_replace('_', '-', sanitize_title( $wgt['acf_fc_layout'] ));
		}

		if ( Helpers::get_key('name', $wgt) )
        {
			return $wgt['name'];
        }
	}






	/**
	 * Get widget CSS class
	 */
	private static function get_class( $wgt ) {

		$class   = ['wgt'];
		$class[] = self::get_slug($wgt);
		$class[] = Helpers::get_key('bg_color', $wgt);

		if ( Helpers::get_key('bg_color', $wgt) )
			$class[] = 'bg-color';

		if ( Helpers::get_key('bg_image', $wgt) )
			$class[] = 'bg-image';

		if ( Helpers::get_key('wgt_type', $wgt) )
			$class[] = 'type-' . Helpers::get_key('wgt_type', $wgt);

		if ( Helpers::get_key('wgt_style', $wgt) )
			$class[] = 'style-' . Helpers::get_key('wgt_style', $wgt);


		return implode(' ', array_filter($class));
	}





	/**
	 * Get single widget
	 */
	public static function get_widget( $params = [], $page_id = false, $type = 'page' ) {

		global $widget, $post;

		/**
		 * Default Widget Settings
		 *
		 * @var name string get specific widget only
		 * @var fields get specific widget and set default values
		 * @var include include only specific widgets
		 * @var exclude specific widgets
		 *
		 */
		$defaults = [
			'name'      => '',
			'fields'    => [],
			'include'   => [],
			'exclude'   => [],
		];

		$content = false;
		$params  = wp_parse_args($params, $defaults);
		$params  = array_filter($params);
		$data    = Helpers::get_key('meta', $post);


		/**
		 * Get values from specific page
		 */
		if ( $page_id ) {
			$data = self::get_field($page_id, $type);
		}



		/**
		 * If no content ...
		 */
		if ( ! $data && empty($params['name']) )
			return;



		/**
		 * Display specific widget
		 */
		if (Helpers::get_key('name', $params)) :

			// Custom widget data
			if ( Helpers::get_key('fields', $params) ) :

				$wgt         = $params['fields'];
				$wgt['name'] = $params['name'];
				$content[]   = $wgt;

			else :

				foreach ( $data as $key => $wgt ) :

					$slug = self::get_slug($wgt);

					if ( $slug == $params['name'] ) :
						$content[] = $data[$key];
					endif;
				endforeach;
			endif;



		/**
		 * Display all widgets
		 */
		else :

			/**
			 * Include specific widgets
			 */
			if ( Helpers::get_key('include', $params) ) :
				foreach ($data as $key => $wgt) :

					$slug = self::get_slug($wgt);

					if (in_array($slug, $params['include'])) :
						$content[] = $wgt;
					endif;
				endforeach;



			/**
			 * Exclude specific widgets
			 */
			elseif (Helpers::get_key('exclude', $params)) :

				foreach ($data as $key => $wgt) :

					$slug = self::get_slug($wgt);

					if (in_array($slug, $params['exclude'])) :
						unset($data[$key]);
					endif;
				endforeach;

				$content = $data;

			else :

				$content = $data;
			endif;
		endif;



		if ( ! $content )
			return;



		/**
		 * Display widget
		 */
		ob_start();

			if (count($content)) :
				foreach ($content as $key => $wgt) :

					$slug            = self::get_slug($wgt);
					$widget          = $wgt;
					$widget['name']  = $slug;
					$widget['class'] = self::get_class($wgt);

					ob_start();
						get_template_part( "templates/widgets/{$slug}" );
					echo ob_get_clean();
				endforeach;
			endif;

		echo ob_get_clean();
	}




	/**
	 * Debug page widgets
	 * Shows print_r of post object for current view
	 */
	private function debug_page_widgets() {

		if ( isset($_GET['debug']) && $_GET['debug'] == 'acf-widget' ) :

			add_filter( 'wp_footer', function() {
				global $post;

				printaj($post);
			});
		endif;
	}
}
