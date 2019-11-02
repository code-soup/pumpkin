<?php get_template_part('templates/partials/page', 'header'); ?>


<div class="error404-content">
	<div class="container">

		<div class="alert alert-warning">
			<?php _e('Sorry, but the page you were trying to view does not exist.', 'pumpkin'); ?>
			<a href="<?php echo home_url('/') ?>" class="btn">Go Home</a>
		</div>

		<?php get_search_form(); ?>

	</div>
</div>