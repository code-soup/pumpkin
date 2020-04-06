<?php

namespace CS\Mods\WP;

if (!defined('ABSPATH'))
	exit;


class Core {

	function register() {

		/**
		 * Enables the HTTP Strict Transport Security (HSTS) header.
		 * Should be enabled only on site with installed SSL
		 *
		 * @link ( Resource, https://thomasgriffin.io/enable-http-strict-transport-security-hsts-wordpress )
		 * @link ( Test headers, https://securityheaders.com )
		 *
		 * @since 1.0.0
		 */
		add_action( 'send_headers', function () {

			header( 'X-UA-Compatible: IE=edge,chrome=1' );
			header( 'Strict-Transport-Security: max-age=10886400; includeSubDomains; preload' );
			header( 'x-frame-options: SAMEORIGIN' );
			header( 'X-XSS-Protection: 1; mode=block' );
			header( 'X-Content-Type-Options: nosniff' );
		});







		/**
		 * Enable SVG files upload
		 */
		add_filter('upload_mimes', function ($mimes) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		});




		/**
		 * Enable SVG uploads in WP > 4.7
		 */
		add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
			$wp_filetype = wp_check_filetype( $filename, $mimes );

			$ext             = $wp_filetype['ext'];
			$type            = $wp_filetype['type'];
			$proper_filename = $data['proper_filename'];

			return compact( 'ext', 'type', 'proper_filename' );
		}, 10, 4 );
	}
}