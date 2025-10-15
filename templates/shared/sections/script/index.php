<?php

defined( 'ABSPATH' ) || exit;

$content = $data['script'] ?? '';

if ( empty( $content ) ) {
    return;
}

printf(
    '<h1 class="page-score-title">Livescore %s, Rezultati uživo</h1>',
    get_the_title()
);

echo $content;