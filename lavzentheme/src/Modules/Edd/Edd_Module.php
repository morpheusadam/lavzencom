<?php
/**
 * EDD / marketplace module.
 *
 * Loads the marketplace home helpers (inc/marketplace.php), warns when EDD is
 * inactive, busts the cached home data on download changes, and performs the
 * front-page asset swap (the marketplace home renders on a clean slate with its
 * own home.css/home.js + Clash Display fonts, replacing the base/glass layers —
 * the chrome stays). Ported from the legacy inc/edd.php + inc/home.php asset swap.
 *
 * The front-page.php marketplace layout + section template-parts are rendered by
 * standard get_template_part() and these helpers (the next sub-phase ports them).
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Edd;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Edd_Module extends Abstract_Module {

	public function id(): string {
		return 'edd';
	}

	/**
	 * Active when EDD (or at least the `download` post type) is present.
	 */
	public function is_active(): bool {
		$active = function_exists( 'EDD' ) || class_exists( 'Easy_Digital_Downloads' ) || post_type_exists( 'download' );
		return (bool) apply_filters( 'lavzen/module/edd/active', $active );
	}

	public function boot(): void {
		require_once LAVZEN_DIR . 'inc/marketplace.php';
		require_once LAVZEN_DIR . 'inc/product-meta.php';

		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'home_assets' ), 100 );
		add_action( 'save_post_download', array( $this, 'bust_cache' ) );
	}

	private function ver( string $relative ): string {
		$file = LAVZEN_DIR . ltrim( $relative, '/' );
		return is_readable( $file ) ? (string) filemtime( $file ) : LAVZEN_VERSION;
	}

	/** Admin notice when the download post type isn't registered yet. */
	public function admin_notice(): void {
		if ( post_type_exists( 'download' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Lavzen: Easy Digital Downloads is not active — the marketplace sections fall back to demo content until you activate it.', 'lavzentheme' ) . '</p></div>';
	}

	/** Bust cached home data on download changes. */
	public function bust_cache(): void {
		delete_transient( 'lavzen_home_stats' );
		delete_transient( 'lavzen_home_depts' );
	}

	/**
	 * Front-page asset swap: clean slate for the marketplace home design.
	 */
	public function home_assets(): void {
		if ( ! is_front_page() ) {
			return;
		}
		foreach ( array( 'lavzen-main', 'lavzen-products', 'lavzen-glass', 'lavzen-ui', 'lavzen-bg', 'lavzen-gfonts' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		wp_dequeue_script( 'lavzen-glassjs' );
		wp_dequeue_script( 'lavzen-main' );

		wp_enqueue_style( 'lavzen-home-fonts', 'https://api.fontshare.com/v2/css?f[]=clash-display@600,700&f[]=satoshi@400,500,700&display=swap', array(), null );
		wp_enqueue_style( 'lavzen-tokens', LAVZEN_URI . 'assets/dist/css/tokens.css', array(), $this->ver( 'assets/dist/css/tokens.css' ) );
		wp_enqueue_style( 'lavzen-home', LAVZEN_URI . 'assets/dist/css/home.css', array( 'lavzen-tokens', 'lavzen-home-fonts' ), $this->ver( 'assets/dist/css/home.css' ) );
		wp_enqueue_script( 'lavzen-home', LAVZEN_URI . 'assets/dist/js/home.js', array(), $this->ver( 'assets/dist/js/home.js' ), true );
		wp_enqueue_script( 'lavzen-chrome', LAVZEN_URI . 'assets/dist/js/chrome.js', array(), $this->ver( 'assets/dist/js/chrome.js' ), true );
	}
}
