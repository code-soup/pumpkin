<?php

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

do_action( 'pumpkin_page_config_common' );
do_action( 'pumpkin_page_config_specific' ); ?>

<!doctype html>
<html <?php language_attributes(); ?> class="no-js">
    <?php do_action( 'pumpkin_page_head' ); ?>
    <body <?php body_class(); ?>>
        <?php
            do_action( 'get_header' );
            do_action( 'wp_body_open' );
        
            do_action( 'pumpkin_page_header' ); ?>

        <article class="page-grid">
            <main class="main">
                <?php do_action( 'pumpkin_page_main' ); ?>
            </main>

            <aside class="sidebar">
                <?php do_action( 'pumpkin_page_sidebar' ); ?>
            </aside>
        </article>

        <?php
        
        // Theme custom footer
        do_action( 'pumpkin_page_footer' );

        // Footer
        wp_footer(); ?>
    </body>
</html>