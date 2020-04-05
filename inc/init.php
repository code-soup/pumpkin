<?php

namespace CS;

final class init {

	/**
	 * Store all the classes inside an arraty
	 * @return array Full list of classes
	 */
	public static function get_services() {
		return[
			Setup\Setup::class,
			Setup\ACF\Init::class,
			Setup\ACF\Widget::class,
			Setup\ACF\Options::class,
			Setup\ACF\LoadField::class,
			Utils\Wrapper::class
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