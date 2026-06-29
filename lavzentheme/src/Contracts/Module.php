<?php
/**
 * Module contract.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * A feature module behaves like a self-contained sub-plugin: it declares an id,
 * decides whether it is active (honoring an option/toggle), and wires its hooks
 * in boot(). Modules are discovered + booted by Lavzen\Module_Manager.
 */
interface Module {

	/**
	 * Stable machine id, e.g. 'code_studio', 'seo', 'edd'.
	 */
	public function id(): string;

	/**
	 * Whether the module should boot on this request.
	 */
	public function is_active(): bool;

	/**
	 * Register hooks, assets and admin screens. Called only when is_active().
	 */
	public function boot(): void;
}
