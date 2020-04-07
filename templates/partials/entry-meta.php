<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<time
    class="entry-date"
    datetime="<?php echo get_post_time('c', true); ?>"
    >
        <?php echo get_the_date(); ?>
</time>
<p class="byline"><?php echo __('By', 'pumpkin'); ?>
	<a
        href="<?php echo get_author_posts_url( get_the_author_meta('ID') ); ?>"
        rel="author"
        class="entry-author"
    >
		<?php echo get_the_author(); ?>
	</a>
</p>
