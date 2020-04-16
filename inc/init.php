<?php

namespace CS;

final class Init {

	/**
	 * Store all the classes inside an arraty
	 * @return array Full list of classes
	 * The order will determine the load order
	 */
	public static function get_services() {
		return[
			setup\acf\Init::class,
			setup\acf\Widget::class,
			setup\acf\Options::class,
			setup\acf\LoadField::class,
			setup\Setup::class,
			utils\Wrapper::class,
			mods\wp\Admin::class,
			mods\wp\Cleanup::class,
			mods\wp\Core::class,
			mods\wp\Login::class,
			mods\plugins\GravityForms::class,
			mods\plugins\WooCommerce::class,
		];
	}

	/**
	 * Loop trough the classes, initialize them, and call the register() method if it exists
	 */
	public static function register_services() {
		foreach ( self::get_services() as $class) {
			$service = self::instantiate( $class );
			if( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param class $class class from the service array
	 * @return class instance new instance of the class
	 */
	private static function instantiate( $class ) {
		return new $class();
	}
}