<?php if ( ! defined( 'ABSPATH' ) || ! CS()->get_sidebar() ) exit;

dynamic_sidebar( CS()->get_sidebar() );