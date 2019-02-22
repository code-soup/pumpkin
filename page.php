<?php while (have_posts()) : the_post();
	get_template_part('templates/partials/page', 'header');
	get_template_part('templates/content', 'page');
endwhile;