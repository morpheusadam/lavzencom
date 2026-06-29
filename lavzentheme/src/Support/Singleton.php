<?php
/**
 * Singleton trait.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Late-static-bound singleton (XTS-style): each class that uses the trait gets
 * its own single instance, and its hook registration lives in init(), which is
 * called once right after construction.
 *
 * Use for cross-cutting services (Theme, Setup, Assets, registries) — NOT for
 * feature modules, which are plain instantiable classes managed by Module_Manager.
 */
trait Singleton {

	/** @var array<class-string, static> */
	private static array $instances = array();

	/**
	 * Get (and lazily create) the single instance for the calling class.
	 */
	final public static function instance(): static {
		$class = static::class;
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
			self::$instances[ $class ]->init();
		}
		return self::$instances[ $class ];
	}

	/**
	 * Register hooks here. Override in the using class.
	 */
	protected function init(): void {}

	private function __construct() {}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \RuntimeException( 'Cannot unserialize a singleton.' );
	}
}
