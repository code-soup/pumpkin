<?php

use CodeSoup\Pumpkin\ACF\Options;

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

$options = Options::get_theme_options( 'options-general' );

if ( empty( $options['page_404'] ) )
	return;

$sections = new \CodeSoup\Pumpkin\ACF\Sections( $options['page_404']->ID );
$sections->render_sections();
