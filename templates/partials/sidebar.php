<?php if ( ! defined( 'ABSPATH' ) || ! NF()->get_sidebar() ) exit;

dynamic_sidebar( NF()->get_sidebar() );