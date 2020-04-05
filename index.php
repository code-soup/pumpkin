<?php if (!defined('ABSPATH')) exit;

get_template_part('templates/partials/page', 'header');

if ( ! have_posts() ) : ?>
	<div class="alert alert-warning">
    	<?php _e('Sorry, no results were found.', 'froots'); ?>
  	</div>
 	<?php get_search_form();
endif;

while (have_posts()) : the_post();
	get_template_part('templates/content', get_post_type() != 'post' ? get_post_type() : get_post_format());
endwhile;

CS\Components\Functions::paginate();