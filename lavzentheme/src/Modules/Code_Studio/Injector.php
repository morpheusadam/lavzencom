<?php
/**
 * Injector — the single front-end injection point for Code Studio overrides.
 *
 * Replaces the ~8 near-identical per-context `*_head()` / `*_footer()` clone
 * functions of the legacy theme with ONE scope-aware injector wired to the
 * Context system (Phase 2). For the request's active scopes (always `global`,
 * plus the current context and/or page), it pulls overrides from Section_Store
 * and emits one inline <style> in the head and one inline <script> in the footer.
 *
 * When no overrides are stored (the default state), it emits nothing — so it is
 * safe to boot before any editing has happened.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Code_Studio;

use Lavzen\Context\Context_Registry;

defined( 'ABSPATH' ) || exit;

final class Injector {

	public function __construct( private Section_Store $store ) {}

	/**
	 * Register front-end hooks. Head priority 100 so overrides land after the
	 * Context base assets and win the cascade.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'inject_css' ), 100 );
		add_action( 'wp_footer', array( $this, 'inject_js' ), 100 );
	}

	/**
	 * The scopes that apply to this request, in injection order.
	 *
	 * @return string[]
	 */
	private function scopes(): array {
		$scopes = array( 'global' );
		$context = Context_Registry::instance()->current();
		if ( $context ) {
			$scopes[] = 'ctx:' . $context->id();
		}
		if ( is_page() ) {
			$scopes[] = 'page:' . (int) get_queried_object_id();
		}
		return $scopes;
	}

	/**
	 * Append an override value when it is a real (non-empty) override.
	 */
	private function append( ?string $value, string &$buffer, string $wrap_before = '', string $wrap_after = '' ): void {
		if ( null === $value ) {
			return; // no override — the Context base file already carries the default.
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return; // intentional clear — inject nothing.
		}
		$buffer .= $wrap_before . $value . $wrap_after . "\n";
	}

	/**
	 * Emit the combined CSS overrides for the active scopes.
	 */
	public function inject_css(): void {
		$css = '';

		// Global design tokens / base / background overrides.
		foreach ( array( 'root', 'css', 'bg' ) as $type ) {
			$this->append( $this->store->get( 'global', 'global', $type ), $css );
		}

		// Per-scope design overrides (+ a mobile layer).
		foreach ( $this->scopes() as $scope ) {
			if ( 'global' === $scope ) {
				continue;
			}
			$this->append( $this->store->get( $scope, 'design', 'css' ), $css );
			$this->append( $this->store->get( $scope, 'design', 'mcss' ), $css, '@media (max-width:640px){', '}' );
		}

		if ( '' !== trim( $css ) ) {
			// Values are sanitised on save (admin, manage_options).
			echo "<style id=\"lavzen-cs-css\">\n" . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Emit the combined JS overrides for the active scopes (each in its own IIFE).
	 */
	public function inject_js(): void {
		$js = '';

		$global = $this->store->get( 'global', 'global', 'js' );
		if ( null !== $global && '' !== trim( $global ) ) {
			$js .= trim( $global ) . "\n";
		}

		foreach ( $this->scopes() as $scope ) {
			if ( 'global' === $scope ) {
				continue;
			}
			$value = $this->store->get( $scope, 'design', 'js' );
			if ( null !== $value && '' !== trim( $value ) ) {
				$js .= '(function(){' . trim( $value ) . "})();\n";
			}
		}

		if ( '' !== trim( $js ) ) {
			// Admin-authored (manage_options); closing tag neutralised on save.
			echo "<script id=\"lavzen-cs-js\">\n(function(){\n" . $js . "})();\n</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
