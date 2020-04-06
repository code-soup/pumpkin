<?php

namespace CS\Mods\Plugins;

if (!defined('ABSPATH'))
	exit;


class GravityForms {

	function register() {

		if ( ! class_exists('GFForms') )
			return;

		/**
		 * Put inline gforms scripts in footer
		 *
		 * @link https://discourse.roots.io/t/how-are-you-making-forms-with-sage-9/10623/11
		 */
		add_filter('gform_init_scripts_footer', '__return_true');

		add_filter('gform_cdata_open', function ($content = '') {
			$content = 'document.addEventListener( "DOMContentLoaded", function() { ';
			return $content;
		});

		add_filter('gform_cdata_close', function ($content = '') {
			$content = ' }, false );';
			return $content;
		});
	}
}
