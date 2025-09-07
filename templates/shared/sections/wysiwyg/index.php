<?php

defined( 'ABSPATH' ) || exit;

$content = $data['content_editor'] ?? '';

if ( empty( $content ) ) {
    return;
}

if ( $component->get_arg('in_article') )
{
    echo '<div>' . do_shortcode( wpautop( $content ) ) . '</div>';
    return;
}

printf(
    '<div class="container entry-content">%s</div>',
    do_shortcode( wpautop( $content ) )
);