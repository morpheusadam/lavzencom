<?php
/**
 * Global (site-wide) asset enqueue.
 *
 * Enqueues the consolidated design system from assets/dist (one tree now — the
 * legacy /css + /assets/css split is merged): base CSS (main), app-shell chrome,
 * the Liquid-Glass layer (glass → ui → bg), display fonts, and behavior JS. Per-
 * context CSS/JS are handled by Lavzen\Context\Context_Registry; Code Studio
 * overrides are layered on top by the Injector.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Core;

use Lavzen\Support\Singleton;

defined( 'ABSPATH' ) || exit;

final class Assets {

	use Singleton;

	protected function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Cache-busting version from filemtime (falls back to theme version).
	 */
	private function ver( string $relative ): string {
		$file = LAVZEN_DIR . ltrim( $relative, '/' );
		return is_readable( $file ) ? (string) filemtime( $file ) : LAVZEN_VERSION;
	}

	private function css( string $handle, string $file, array $deps = array() ): void {
		$rel = 'assets/dist/css/' . $file;
		if ( is_readable( LAVZEN_DIR . $rel ) ) {
			wp_enqueue_style( $handle, LAVZEN_URI . $rel, $deps, $this->ver( $rel ) );
		}
	}

	private function js( string $handle, string $file, array $deps = array() ): void {
		$rel = 'assets/dist/js/' . $file;
		if ( is_readable( LAVZEN_DIR . $rel ) ) {
			wp_enqueue_script( $handle, LAVZEN_URI . $rel, $deps, $this->ver( $rel ), true );
		}
	}

	/**
	 * Whether the current request is an EDD purchase-flow page.
	 */
	private function is_edd_flow(): bool {
		if ( ! function_exists( 'edd_get_option' ) ) {
			return false;
		}
		if ( function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {
			return true;
		}
		if ( function_exists( 'edd_is_success_page' ) && edd_is_success_page() ) {
			return true;
		}
		$ids = array_filter( array_map( 'absint', array(
			edd_get_option( 'purchase_page', 0 ),
			edd_get_option( 'success_page', 0 ),
			edd_get_option( 'failure_page', 0 ),
			edd_get_option( 'purchase_history_page', 0 ),
		) ) );
		return ! empty( $ids ) && is_page( $ids );
	}

	public function enqueue(): void {
		// Display fonts (async-swapped + preconnected by the Performance module).
		wp_enqueue_style( 'lavzen-fonts', 'https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700,900&display=swap', array(), null );
		wp_enqueue_style( 'lavzen-gfonts', 'https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500&family=JetBrains+Mono:wght@500&display=swap', array(), null );

		// Base design system: main => chrome => glass => ui => bg (cascade order).
		$this->css( 'lavzen-main', 'main.css' );
		$this->css( 'lavzen-chrome', 'chrome.css', array( 'lavzen-main' ) );
		$this->css( 'lavzen-glass', 'lavzen-glass.css', array( 'lavzen-main' ) );
		$this->css( 'lavzen-ui', 'lavzen-ui.css', array( 'lavzen-glass' ) );
		$this->css( 'lavzen-bg', 'lavzen-bg.css', array( 'lavzen-ui' ) );

		// Behavior JS.
		$this->js( 'lavzen-glassjs', 'lavzen.js' );
		$this->js( 'lavzen-main', 'main.js' );

		// Front-page product grid styling.
		if ( is_front_page() ) {
			$this->css( 'lavzen-products', 'products.css', array( 'lavzen-main' ) );
		}

		// EDD purchase-flow styling.
		if ( $this->is_edd_flow() ) {
			$this->css( 'lavzen-checkout', 'checkout.css', array( 'lavzen-main' ) );
		}

		// Threaded comment-reply on singular views.
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
