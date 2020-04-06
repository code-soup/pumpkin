<?php

namespace CS\Mods\WP;

if (!defined('ABSPATH'))
	exit;


class Admin {

	function register() {

		add_action( 'init', function () {

			remove_post_type_support('page', 'editor');
			remove_post_type_support('page', 'thumbnail');
			remove_post_type_support('page', 'comments');
			remove_post_type_support('page', 'trackbacks');
			remove_post_type_support('page', 'custom-fields');

			// remove_post_type_support('post', 'excerpt');
			remove_post_type_support('post', 'trackbacks');
			remove_post_type_support('post', 'custom-fields');
			remove_post_type_support('post', 'post-formats');
		}, 10 );




		/**
		 * Remove some post meta
		 */
		add_action( 'admin_menu', function () {

			remove_meta_box( 'thumbnaildiv', 'page', 'side' );
			remove_meta_box( 'commentsdiv', 'page', 'normal' );
			remove_meta_box( 'commentstatusdiv', 'page', 'normal' );

			remove_meta_box( 'slugdiv', 'post', 'normal' );
		});



		/**
		 * Cleanup wp admin bar
		 */
		add_action( 'wp_before_admin_bar_render', function () {

			global $wp_admin_bar;

			$wp_admin_bar->remove_menu('wp-logo');
			$wp_admin_bar->remove_menu('about');
			$wp_admin_bar->remove_menu('wporg');
			$wp_admin_bar->remove_menu('documentation');
			$wp_admin_bar->remove_menu('support-forums');
			$wp_admin_bar->remove_menu('feedback');
			$wp_admin_bar->remove_menu('customize');
			$wp_admin_bar->remove_menu('updates');
			$wp_admin_bar->remove_menu('comments');
			$wp_admin_bar->remove_menu('new-post');
			$wp_admin_bar->remove_menu('new-media');
			$wp_admin_bar->remove_menu('new-user');
			$wp_admin_bar->remove_menu('w3tc');
			$wp_admin_bar->remove_menu('search');
		});



		/**
		 * Cleanup admin dashboard boxes
		 */
		add_action('wp_dashboard_setup', function () {

			global $wp_meta_boxes;

			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['pmpro_db_widget']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
			unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
			unset($wp_meta_boxes['dashboard']['side']['high']['redux_dashboard_widget']);

			unset($wp_meta_boxes['nav-menus']['side']['low']['mega_nav_link']);
			unset($wp_meta_boxes['vc_grid_item']['normal']['high']['wpb_visual_composer']);

			unset($wp_meta_boxes['page']['side']['default']['pmpro_page_meta']);
			unset($wp_meta_boxes['post']['side']['default']['pmpro_page_meta']);

			unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['woocommerce_dashboard_status']);
			unset($wp_meta_boxes['dashboard']['normal']['core']['woocommerce_dashboard_recent_reviews']);

		}, 1001);

	}
}