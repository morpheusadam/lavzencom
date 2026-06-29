<?php
/**
 * Builds Context objects from config and drives the current one.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Context;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Loads config/contexts.php into Context objects, then on the front end enqueues
 * assets and adds the body class for whichever context matches the request.
 * The Code Studio module (when active) layers DB overrides on top via the same
 * context ids.
 */
final class Context_Registry {

	use Singleton;

	/** @var Context[] */
	private array $contexts = array();

	/**
	 * Load contexts from config.
	 */
	protected function init(): void {
		$config = (array) require LAVZEN_DIR . 'config/contexts.php';
		foreach ( $config as $id => $cfg ) {
			$this->contexts[ $id ] = Context::from_config( (string) $id, (array) $cfg );
		}
	}

	/**
	 * Register front-end hooks.
	 */
	public function boot(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_current' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class_current' ) );
	}

	/**
	 * The first matching context for this request (or null).
	 */
	public function current(): ?Context {
		foreach ( $this->contexts as $context ) {
			if ( $context->matches() ) {
				return $context;
			}
		}
		return null;
	}

	/**
	 * Fetch a context by id (used by modules that inject into a context).
	 */
	public function get( string $id ): ?Context {
		return $this->contexts[ $id ] ?? null;
	}

	public function enqueue_current(): void {
		$this->current()?->enqueue();
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class_current( array $classes ): array {
		return $this->current()?->body_class( $classes ) ?? $classes;
	}
}
