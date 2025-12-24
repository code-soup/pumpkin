<?php

defined( 'ABSPATH' ) || exit;

$content = $data['text'] ?? '';

if ( empty( $content ) ) {
    return;
} ?>

<section class="section-text-block">
    <div class="container">
        <h2><?php echo $data['title']; ?></h2>
        <?php echo do_shortcode( wpautop( $content ) ); ?>
    </div>
</section>