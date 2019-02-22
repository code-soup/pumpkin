<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $widget;

if ( empty( get_key('editor') ) )
	return; ?>

<section class="<?= $widget['class']; ?>">
	<div class="container">
		<div class="entry-content">
			<?= wpautop( $widget['editor'] ); ?>
		</div>
	</div>
</section>

<?php $widget = null;