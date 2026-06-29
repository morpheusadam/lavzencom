<?php
/**
 * Performance module — non-visual front-end hardening.
 *
 * Ported 1:1 from the legacy inc/performance.php (behavior preserved), wrapped as
 * a toggleable module: trims default <head> cruft (emoji, RSD/WLW/shortlink/
 * generator/adjacency), defers theme JS, async-loads fonts with preconnect,
 * emits a data-URI favicon + security headers, pushes jQuery/EDD to the footer,
 * trims EDD's render-blocking CSS/JS on the front page, and lazily loads images
 * (LCP-safe). None of it changes a rendered pixel.
 *
 * NOTE (audit): the full-page lazy-load output buffer is a faithful port of the
 * legacy behavior for parity; switching to core's wp_get_loading_optimization_attributes
 * is a later optimization, gated by the `lavzen/perf/auto_lazyload` filter.
 *
 * @package Lavzen
 */

declare( strict_types=1 );

namespace Lavzen\Modules\Performance;

use Lavzen\Modules\Abstract_Module;

defined( 'ABSPATH' ) || exit;

final class Performance_Module extends Abstract_Module {

	public function id(): string {
		return 'performance';
	}

	public function boot(): void {
		add_action( 'init', array( $this, 'disable_emojis' ) );
		add_action( 'init', array( $this, 'clean_head' ) );
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 2 );
		add_filter( 'wp_resource_hints', array( $this, 'font_preconnect' ), 10, 2 );
		add_filter( 'style_loader_tag', array( $this, 'async_fonts' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'favicon' ), 2 );
		add_action( 'send_headers', array( $this, 'security_headers' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_to_footer' ), 100 );
		add_filter( 'lavzen/perf/defer_handles', array( $this, 'defer_edd' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'trim_edd_css_frontpage' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'trim_frontpage_js' ), 100 );
		add_action( 'template_redirect', array( $this, 'lazy_buffer' ), 1 );
	}

	/** Remove the emoji polyfill. */
	public function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_dns' ), 10, 2 );
	}

	/**
	 * @param array $plugins TinyMCE plugins.
	 * @return array
	 */
	public function disable_emojis_tinymce( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	}

	/**
	 * @param array  $urls          Resource hints.
	 * @param string $relation_type Hint type.
	 * @return array
	 */
	public function remove_emoji_dns( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$emoji = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
			$urls  = array_filter(
				$urls,
				static function ( $url ) use ( $emoji ) {
					$u = is_array( $url ) && isset( $url['href'] ) ? $url['href'] : $url;
					return is_string( $u ) ? false === strpos( $u, $emoji ) : true;
				}
			);
		}
		return $urls;
	}

	/** Strip legacy/unused <head> link tags (SEO-relevant tags kept). */
	public function clean_head(): void {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	/**
	 * Defer theme-owned, dependency-free scripts.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function defer_scripts( $tag, $handle ) {
		if ( is_admin() ) {
			return $tag;
		}
		$defer = (array) apply_filters( 'lavzen/perf/defer_handles', array( 'lavzen-single' ) );
		if ( in_array( $handle, $defer, true ) && false === strpos( $tag, ' defer' ) && false === strpos( $tag, ' async' ) ) {
			$tag = str_replace( ' src=', ' defer src=', $tag );
		}
		return $tag;
	}

	/**
	 * Preconnect to the Fontshare origins.
	 *
	 * @param array  $urls          Resource hints.
	 * @param string $relation_type Hint type.
	 * @return array
	 */
	public function font_preconnect( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = array( 'href' => 'https://api.fontshare.com', 'crossorigin' );
			$urls[] = array( 'href' => 'https://cdn.fontshare.com', 'crossorigin' );
		}
		return $urls;
	}

	/**
	 * Load the display-font stylesheets without blocking render (media=print swap).
	 *
	 * @param string $tag    Stylesheet tag.
	 * @param string $handle Style handle.
	 * @return string
	 */
	public function async_fonts( $tag, $handle ) {
		if ( is_admin() ) {
			return $tag;
		}
		$async = (array) apply_filters( 'lavzen/perf/async_style_handles', array( 'lavzen-fonts', 'lavzen-home-fonts' ) );
		if ( ! in_array( $handle, $async, true ) ) {
			return $tag;
		}
		$orig = $tag;
		if ( false !== strpos( $tag, "media='all'" ) ) {
			$tag = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
		} elseif ( false !== strpos( $tag, 'media="all"' ) ) {
			$tag = str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $tag );
		} else {
			$tag = str_replace( array( ' />', '>' ), array( " media='print' onload=\"this.media='all'\" />", " media='print' onload=\"this.media='all'\">" ), $tag );
		}
		return $tag . '<noscript>' . $orig . '</noscript>';
	}

	/** Emit a tiny inline-SVG favicon (skipped when a real Site Icon exists). */
	public function favicon(): void {
		if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
			return;
		}
		$svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 52 52'>"
			. "<rect width='52' height='52' rx='11' fill='#0B0907'/>"
			. "<path d='M26 8 42 17V35L26 44 10 35V17Z' fill='#F5F5F5'/>"
			. "<circle cx='26' cy='26' r='6' fill='none' stroke='#0B0907' stroke-width='3'/></svg>";
		echo '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,' . rawurlencode( $svg ) . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/** Hardening response headers on public front-end HTML only. */
	public function security_headers(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() || headers_sent() ) {
			return;
		}
		$headers = array(
			'Strict-Transport-Security'  => 'max-age=31536000; includeSubDomains; preload',
			'X-Content-Type-Options'     => 'nosniff',
			'X-Frame-Options'            => 'SAMEORIGIN',
			'Referrer-Policy'            => 'strict-origin-when-cross-origin',
			'Cross-Origin-Opener-Policy' => 'same-origin',
			'Permissions-Policy'         => 'geolocation=(), camera=(), microphone=(), browsing-topics=()',
		);
		$headers = (array) apply_filters( 'lavzen/security_headers', $headers );
		foreach ( $headers as $name => $value ) {
			if ( '' !== (string) $value ) {
				header( $name . ': ' . $value );
			}
		}
	}

	/** Move jQuery + EDD frontend scripts out of the render-blocking head. */
	public function scripts_to_footer(): void {
		if ( is_admin() ) {
			return;
		}
		$scripts = wp_scripts();
		$to_foot = (array) apply_filters( 'lavzen/perf/footer_handles', array( 'jquery', 'jquery-core', 'jquery-migrate', 'edd-ajax', 'edd-checkout-global' ) );
		foreach ( $to_foot as $handle ) {
			if ( isset( $scripts->registered[ $handle ] ) ) {
				$scripts->add_data( $handle, 'group', 1 );
			}
		}
	}

	/**
	 * @param array $handles Handles to defer.
	 * @return array
	 */
	public function defer_edd( $handles ) {
		return array_merge( (array) $handles, array( 'edd-ajax', 'edd-checkout-global' ) );
	}

	/** Drop EDD's render-blocking CSS on the front page. */
	public function trim_edd_css_frontpage(): void {
		if ( ! is_front_page() ) {
			return;
		}
		$styles = wp_styles();
		foreach ( (array) $styles->queue as $handle ) {
			$src = isset( $styles->registered[ $handle ] ) ? (string) $styles->registered[ $handle ]->src : '';
			if ( '' !== $src && ( false !== strpos( $src, 'easy-digital-downloads' ) || false !== strpos( $src, '/edd' ) ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}

	/** Drop jQuery + EDD scripts on the FRONT PAGE only. */
	public function trim_frontpage_js(): void {
		if ( ! is_front_page() || is_admin() ) {
			return;
		}
		if ( ! apply_filters( 'lavzen/perf/strip_frontpage_jquery', true ) ) {
			return;
		}
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate', 'edd-ajax', 'edd-checkout-global', 'edd-ajax-cart' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}

	/** Open an output buffer to lazy-load images (LCP-safe). */
	public function lazy_buffer(): void {
		if ( is_admin() || is_feed() || is_embed() || is_robots()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}
		ob_start( array( $this, 'lazy_filter' ) );
	}

	/**
	 * Add loading="lazy" + decoding="async" to eligible images (first real image
	 * stays eager for LCP). Faithful port of the legacy regex pass.
	 *
	 * @param string $html Buffered page HTML.
	 * @return string
	 */
	public function lazy_filter( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<img' ) ) {
			return $html;
		}
		if ( ! apply_filters( 'lavzen/perf/auto_lazyload', true ) ) {
			return $html;
		}
		$lcp_done = false;
		return (string) preg_replace_callback(
			'#<img\b[^>]*>#i',
			static function ( $m ) use ( &$lcp_done ) {
				$tag = $m[0];
				if ( ! preg_match( '/\sdecoding\s*=/i', $tag ) ) {
					$tag = preg_replace( '/<img\b/i', '<img decoding="async"', $tag, 1 );
				}
				if ( preg_match( '/\sloading\s*=/i', $tag )
					|| preg_match( '/fetchpriority\s*=\s*["\']?\s*high/i', $tag )
					|| false !== stripos( $tag, 'data-no-lazy' ) ) {
					return $tag;
				}
				$is_data = (bool) preg_match( '/\ssrc\s*=\s*["\']\s*data:/i', $tag );
				if ( ! $is_data && ! $lcp_done ) {
					$lcp_done = true;
					return $tag;
				}
				return preg_replace( '/<img\b/i', '<img loading="lazy"', $tag, 1 );
			},
			$html
		);
	}
}
