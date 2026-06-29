<?php
/**
 * A single front-end context (single post, shop, blog, 404, …).
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Context;

defined( 'ABSPATH' ) || exit;

/**
 * One configurable class for every context. What differs between contexts is
 * DATA (a conditional tag, which asset handles, a body class), not behavior —
 * so this single class, instantiated once per row of config/contexts.php,
 * replaces the ~8 near-identical per-context files.
 */
final class Context {

	/**
	 * @param string          $id         Machine id ('single', '404', …).
	 * @param callable|string $condition  Conditional tag / callable deciding if this context is current.
	 * @param string[]        $css        Asset handles to enqueue (assets/dist/css/<h>.css).
	 * @param string[]        $js         Asset handles to enqueue (assets/dist/js/<h>.js).
	 * @param string          $body_class Body class to add.
	 * @param string[]        $deps       Class/function names that must exist (e.g. 'Easy_Digital_Downloads').
	 */
	public function __construct(
		private string $id,
		private $condition,
		private array $css = array(),
		private array $js = array(),
		private string $body_class = '',
		private array $deps = array()
	) {}

	/**
	 * Build from a config row.
	 *
	 * @param string               $id  Context id.
	 * @param array<string, mixed> $cfg Config row.
	 */
	public static function from_config( string $id, array $cfg ): self {
		return new self(
			$id,
			$cfg['when'] ?? '__return_false',
			(array) ( $cfg['css'] ?? array() ),
			(array) ( $cfg['js'] ?? array() ),
			(string) ( $cfg['body_class'] ?? '' ),
			(array) ( $cfg['deps'] ?? array() )
		);
	}

	/**
	 * Is this context the current request? Dependencies must exist AND the
	 * conditional must pass. Missing dependency or unknown conditional = no match
	 * (never fatals).
	 */
	public function matches(): bool {
		foreach ( $this->deps as $dep ) {
			if ( ! class_exists( $dep ) && ! function_exists( $dep ) ) {
				return false;
			}
		}
		return is_callable( $this->condition ) && (bool) call_user_func( $this->condition );
	}

	/**
	 * Enqueue this context's CSS/JS.
	 */
	public function enqueue(): void {
		foreach ( $this->css as $handle ) {
			wp_enqueue_style(
				"lavzen-ctx-{$handle}",
				LAVZEN_URI . "assets/dist/css/{$handle}.css",
				array(),
				LAVZEN_VERSION
			);
		}
		foreach ( $this->js as $handle ) {
			wp_enqueue_script(
				"lavzen-ctx-{$handle}",
				LAVZEN_URI . "assets/dist/js/{$handle}.js",
				array(),
				LAVZEN_VERSION,
				true
			);
		}
	}

	/**
	 * Contribute the body class.
	 *
	 * @param string[] $classes Existing classes.
	 * @return string[]
	 */
	public function body_class( array $classes ): array {
		if ( '' !== $this->body_class ) {
			$classes[] = $this->body_class;
		}
		return $classes;
	}

	public function id(): string {
		return $this->id;
	}
}
