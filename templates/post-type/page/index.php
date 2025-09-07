<?php

// Don't allow direct access to file
defined( 'ABSPATH' ) || die;

$sections = new \CodeSoup\Pumpkin\ACF\Sections();
$sections->render_sections();
