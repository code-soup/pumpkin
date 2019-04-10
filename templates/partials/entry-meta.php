<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<time
    class="entry-date"
    datetime="<?= get_post_time('c', true); ?>"
    >
        <?= get_the_date(); ?>
</time>
<p class="byline"><?= __('By', 'pumpkin'); ?>
	<a
        href="<?= get_author_posts_url( get_the_author_meta('ID') ); ?>"
        rel="author"
        class="entry-author"
    >
		<?= get_the_author(); ?>
	</a>
</p>
