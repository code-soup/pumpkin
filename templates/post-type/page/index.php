<?php

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

$sections = \CodeSoup\Pumpkin\ACF\Sections::get_instance();
$sections->render_sections();
