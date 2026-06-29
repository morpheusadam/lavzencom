<?php
/**
 * Module discovery + boot.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen;

use Lavzen\Support\Singleton;
use Lavzen\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the module registry (config/modules.php, filterable via `lavzen/modules`),
 * instantiates each Module, and boots the active ones. Deterministic order =
 * registry order, which is the fastest, most predictable choice on shared hosting.
 */
final class Module_Manager {

	use Singleton;

	/** @var Module[] */
	private array $modules = array();

	/**
	 * Instantiate every registered module class.
	 */
	public function discover(): self {
		$classes = (array) apply_filters(
			'lavzen/modules',
			(array) require LAVZEN_DIR . 'config/modules.php'
		);

		foreach ( $classes as $class ) {
			if ( is_string( $class ) && class_exists( $class ) && is_subclass_of( $class, Module::class ) ) {
				$this->modules[] = new $class();
			}
		}

		return $this;
	}

	/**
	 * Boot every active module.
	 */
	public function boot_active(): void {
		foreach ( $this->modules as $module ) {
			if ( $module->is_active() ) {
				$module->boot();
				do_action( "lavzen/module/{$module->id()}/booted", $module );
			}
		}
	}

	/**
	 * Fetch a booted module by id (for inter-module access).
	 */
	public function get( string $id ): ?Module {
		foreach ( $this->modules as $module ) {
			if ( $module->id() === $id ) {
				return $module;
			}
		}
		return null;
	}
}
