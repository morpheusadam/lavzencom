<?php
/**
 * Base class for feature modules.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules;

use Lavzen\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the common Module behavior: an option-driven is_active() toggle and
 * terse hook helpers. Subclasses implement id() and boot().
 */
abstract class Abstract_Module implements Module {

	/**
	 * Machine id (e.g. 'seo').
	 */
	abstract public function id(): string;

	/**
	 * Register hooks/assets/admin. Called only when is_active().
	 */
	abstract public function boot(): void;

	/**
	 * Active unless explicitly disabled. Filterable per module:
	 * `lavzen/module/{id}/active`.
	 */
	public function is_active(): bool {
		$default = true;
		return (bool) apply_filters( "lavzen/module/{$this->id()}/active", $default );
	}

	/**
	 * add_action shorthand.
	 */
	protected function on( string $hook, callable $callback, int $priority = 10, int $args = 1 ): void {
		add_action( $hook, $callback, $priority, $args );
	}

	/**
	 * add_filter shorthand.
	 */
	protected function filter( string $hook, callable $callback, int $priority = 10, int $args = 1 ): void {
		add_filter( $hook, $callback, $priority, $args );
	}
}
