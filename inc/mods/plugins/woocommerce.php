<?php

namespace CS\mods\plugins;

if (!defined('ABSPATH'))
	exit;

class WooCommerce {

	function register() {

		if ( ! function_exists('is_woocommerce') )
			return;

		/**
		 * @link https://docs.woothemes.com/document/disable-the-default-stylesheet/
		 */
		add_filter('woocommerce_enqueue_styles', '__return_empty_array');

		/**
		 * Manage WooCommerce styles and scripts.
		 * @link http://gregrickaby.com/remove-woocommerce-styles-and-scripts/
		 */
		add_action('wp_enqueue_scripts', function () {

			// Remove the generator tag
			remove_action('wp_head', array($GLOBALS['woocommerce'], 'generator'));

			// Remove select2 scripts
			wp_dequeue_style('select2');
			wp_dequeue_style('woocommerce_chosen_styles');
			wp_deregister_style('select2');

			wp_dequeue_script('select2');
			wp_dequeue_script('wc-chosen');
			wp_deregister_script('select2');

			/**
			 * Unless we're in the store, remove all the cruft!
			 */
			if (!is_woocommerce() && !is_cart() && !is_checkout()):

				wp_dequeue_style('woocommerce_frontend_styles');
				wp_dequeue_style('woocommerce-general');
				wp_dequeue_style('woocommerce-layout');
				wp_dequeue_style('woocommerce-smallscreen');
				wp_dequeue_style('woocommerce_fancybox_styles');
				wp_dequeue_style('woocommerce_chosen_styles');
				wp_dequeue_style('woocommerce_prettyPhoto_css');
				wp_dequeue_style('select2');
				wp_dequeue_script('wc-add-payment-method');
				wp_dequeue_script('wc-lost-password');
				wp_dequeue_script('wc_price_slider');
				wp_dequeue_script('wc-single-product');
				wp_dequeue_script('wc-add-to-cart');
				wp_dequeue_script('wc-cart-fragments');
				wp_dequeue_script('wc-credit-card-form');
				wp_dequeue_script('wc-checkout');
				wp_dequeue_script('wc-add-to-cart-variation');
				wp_dequeue_script('wc-single-product');
				wp_dequeue_script('wc-cart');
				wp_dequeue_script('wc-chosen');
				wp_dequeue_script('woocommerce');
				wp_dequeue_script('prettyPhoto');
				wp_dequeue_script('prettyPhoto-init');
				wp_dequeue_script('jquery-blockui');
				wp_dequeue_script('jquery-placeholder');
				wp_dequeue_script('jquery-payment');
				wp_dequeue_script('fancybox');
				wp_dequeue_script('jqueryui');
			endif;
		}, 99);

		/**
		 * WooCommerce 3.0 gallery fix
		 */
		add_action('after_setup_theme', function () {
			add_theme_support('wc-product-gallery-zoom');
			add_theme_support('wc-product-gallery-lightbox');
			add_theme_support('wc-product-gallery-slider');
		});

		/**
		 * Removes the "shop" title on the main shop page
		 */
		add_filter('woocommerce_show_page_title', function () {
			return false;
		});
	}
}
