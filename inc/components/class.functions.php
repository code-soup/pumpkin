<?php if ( ! defined( 'ABSPATH' ) ) exit;

class CS_Functions {

	public function __construct() {}




	/**
	 * Logo
	 * Returns HTML for logo set in options
	 */
	public function get_logo($url = false) {

		$src = false;
		$img = get_key('cs_logo');


		if ( is_numeric($img) ) :
			$src = wp_get_attachment_image_src( $img, 'large');
			$src = $src[0];
			$w   = $img[1];
			$h   = $img[2];

		elseif ( is_array($img)) :

			$w   = $img['sizes']['large-width'];
			$h   = $img['sizes']['large-height'];
			$src = $img['sizes']['large'];
		endif;

		if ( ! $src )
			return;

		if ($url)
			return $src; ?>

		<a href="<?php echo home_url('/'); ?>">

			<?php if ( strpos($src, '.svg') !== false ) : ?>
				<img src="<?php echo $src; ?>" alt="<?php echo get_bloginfo('name'); ?>" />
			<?php else : ?>
				<img src="<?php echo $src; ?>" width="<?php echo $w; ?>" height="<?php echo $h; ?>" alt="<?php echo get_bloginfo('name'); ?>" />
			<?php endif; ?>
		</a>

	<?php }












	public function get_button( $button, $return_url = false ) {

		if ( ! is_array($button) || get_key('btn_type', $button) == 1 )
			return;


		$url  = '#';
		$open = '_top';

		/**
		 * Button Type
		 */
		switch ( get_key('btn_type', $button) ) :

			case 2:

				$url = get_permalink( $button['btn_obj'] );
			break;

			case 3:

				$url  = get_key('btn_url', $button);
				$open = '_blank';
			break;

			case 4:

				$url  = get_author_posts_url( $button['btn_author']['ID'] );
			break;

			case 5:

				$url  = get_term_link( $button['btn_category'], 'category' );
			break;

			case 6:

				$url  = get_key('btn_file', $button);
			break;

			default:
				return false;
			break;
		endswitch;


		/**
		 * Return only URL
		 */
		if ($return_url) {
			return $url;
		}




		switch ( $button['btn_style']) {
			case 1:
				$class = 'btn-primary';
			break;

			case 2:
				$class = 'btn-plain';
			break;

			default:
				$class = 'btn-primary';
			break;
		}


		$btn  = '<p class="btn-wrap">';
		$btn .= '<a href="' . $url . '" target="'. $open.'" class="'. $class .'"';

		if ( $button['btn_type'] == 14 ) {

			$btn .= ' download="download"';
		}

		$btn .= '>';
		$btn .= $button['btn_title'];
		$btn .= '</a></p>';

		return $btn;
	}







	/**
	 * Social Icons
	 */
	public function social() {

		$social = get_key('cs_social_profiles');

		if ($social) : ?>
			<ul class="social">
				<?php foreach ($social as $s) : ?>
					<li>
						<a href="<?php echo $s['profile_url']; ?>" target="_blank" class="icon-social-<?php echo $s['network']; ?>">
							<?php svg_icon($s['network']); ?>
							<span class="sr-only"><?php echo $s['network']; ?></span>
						</a>
					</li>
				<?php endforeach; ?>
				<li class="label">Connect With Us</li>
			</ul>
		<?php endif;
	}





	/**
	 * Get sidebar for current page
	 */
	public function get_sidebar() {

		global $post;

		return get_key('sidebar', $post);
	}







	/**
	 * Breadcrumbs
	 */
	public function breadcrumbs() {

		if ( is_front_page() )
			return;


		global $post; ?>

		<div class="breadcrumb">
			<ol>
				<li>
					<a href="<?php echo home_url('/'); ?>">Home</a>
				</li>

				<?php if ( is_page() ) :

					if ($post->post_parent) : ?>
						<li>
							<a href="<?php echo get_permalink($post->post_parent); ?>">
								<?php echo get_the_title($post->post_parent); ?>
							</a>
						</li>
					<?php endif; ?>

					<li>
						<span><?php the_title(); ?></span>
					</li>

				<?php elseif ( is_search() ) : ?>

					<li>
						<span><?php echo __('Search Results', 'pumpkin'); ?></span>
					</li>

				<?php elseif ( is_404() ) : ?>

					<li>
						<span><?php echo __('Not Found', 'pumpkin'); ?></span>
					</li>
				<?php endif; ?>
			</ol>
		</div>

		<?php
	}









	/**
	 * Custom WP_Query pagination
	 */
	public function paginate( $query = null ) {

		global $wp_query;

		$query = $query ? $query : $wp_query;
		$big   = 999999999;

		$paginate = paginate_links( array(
			'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'type'      => 'array',
			'total'     => $query->max_num_pages,
			'format'    => '?paged=%#%',
			'current'   => max( 1, get_query_var('paged') ),
			'prev_text' => __('Prev', 'pumpkin'),
			'next_text' => __('Next', 'pumpkin'),
		));

		if ($query->max_num_pages > 1) : ?>
			<ul class="pagination">
				<?php foreach ( $paginate as $page ) : ?>
					<li><?php echo $page; ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif;
	}




	/**
	 * Pagination for custom SQL query
	 */
	public function paginate_sql( $total = 1, $perpage = 10, $current = 1) {

		$paginate = paginate_links( array(
			'base'      => add_query_arg( 'cpage', '%#%' ),
			'format'    => '',
			'prev_text' => __('Prev', 'pumpkin'),
			'next_text' => __('Next', 'pumpkin'),
			'total'     => ceil($total / $perpage),
			'current'   => $current,
			'type'      => 'array'
		));


		if ( $total > $perpage ) : ?>
			<ul class="pagination">
				<?php foreach ( $paginate as $page ) : ?>
					<li><?php echo $page; ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif;
	}
}

function CS() {
	return new CS_Functions();
}