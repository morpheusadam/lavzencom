<?php
/**
 * Front-end performance hardening — non-visual only.
 *
 * Trims render-blocking and unused <head> output that ships with WordPress by
 * default (emoji polyfill, RSD/WLW/shortlink/generator/adjacency links) and
 * defers theme-owned, dependency-free scripts. None of this changes a single
 * pixel: native emoji still render, feeds stay enabled (SEO), and deferred
 * progressive scripts run after parse. Admin and the block editor are untouched.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove the emoji polyfill (wp-emoji-release.min.js + inline detection script
 * and CSS). Modern browsers render emoji natively, so this is purely dead weight.
 */
function lavtheme_perf_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'lavtheme_perf_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'lavtheme_perf_remove_emoji_dns', 10, 2 );
}
add_action( 'init', 'lavtheme_perf_disable_emojis' );

/**
 * Drop the emoji TinyMCE plugin.
 *
 * @param array $plugins TinyMCE plugins.
 * @return array
 */
function lavtheme_perf_disable_emojis_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

/**
 * Remove the s.w.org emoji DNS-prefetch hint.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Hint type.
 * @return array
 */
function lavtheme_perf_remove_emoji_dns( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
		$urls  = array_filter(
			$urls,
			function ( $url ) use ( $emoji ) {
				$u = is_array( $url ) && isset( $url['href'] ) ? $url['href'] : $url;
				return is_string( $u ) ? false === strpos( $u, $emoji ) : true;
			}
		);
	}
	return $urls;
}

/**
 * Strip legacy/unused <head> link tags. SEO-relevant tags (canonical, feeds,
 * pingback) are intentionally kept.
 */
function lavtheme_perf_clean_head() {
	remove_action( 'wp_head', 'rsd_link' );                      // Really Simple Discovery.
	remove_action( 'wp_head', 'wlwmanifest_link' );              // Windows Live Writer.
	remove_action( 'wp_head', 'wp_generator' );                  // WordPress version (also small hardening).
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );          // ?p= shortlink.
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' ); // prev/next rel (noise for non-paginated singles).
}
add_action( 'init', 'lavtheme_perf_clean_head' );

/**
 * Add defer to theme-owned, dependency-free front-end scripts so they never
 * block the parser. Scoped to known handles to avoid touching jQuery/Elementor.
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function lavtheme_perf_defer_scripts( $tag, $handle ) {
	if ( is_admin() ) {
		return $tag;
	}
	$defer = (array) apply_filters( 'lavtheme_perf_defer_handles', array( 'lavtheme-single' ) );
	if ( in_array( $handle, $defer, true ) && false === strpos( $tag, ' defer' ) && false === strpos( $tag, ' async' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'lavtheme_perf_defer_scripts', 10, 2 );

/* -------------------------------------------------------------------------
 * Security response headers — addresses Lighthouse "Trust and safety"
 * (HSTS, COOP, clickjacking, MIME-sniffing, referrer & permissions policy).
 * Only sent on front-end HTML responses; admin, REST, AJAX and feeds are left
 * untouched. A strict script-src CSP is intentionally omitted (the theme ships
 * inline bootstrap scripts); add one per-site via the filter when ready.
 * ---------------------------------------------------------------------- */

/**
 * Emit hardening headers for public front-end requests.
 */
function lavtheme_perf_security_headers() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() ) {
		return;
	}
	if ( headers_sent() ) {
		return;
	}

	$headers = array(
		// Force HTTPS for a year incl. subdomains; eligible for the preload list.
		'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
		// Block MIME-type sniffing.
		'X-Content-Type-Options'    => 'nosniff',
		// Clickjacking protection (kept alongside CSP frame-ancestors if added).
		'X-Frame-Options'           => 'SAMEORIGIN',
		// Trim referrer leakage to other origins.
		'Referrer-Policy'           => 'strict-origin-when-cross-origin',
		// Isolate the browsing context from cross-origin popups (COOP).
		'Cross-Origin-Opener-Policy' => 'same-origin',
		// Lock down powerful features the site does not use.
		'Permissions-Policy'        => 'geolocation=(), camera=(), microphone=(), browsing-topics=()',
	);

	/** Filter the full set of security headers (e.g. to add Content-Security-Policy). */
	$headers = (array) apply_filters( 'lavtheme_security_headers', $headers );

	foreach ( $headers as $name => $value ) {
		if ( '' !== (string) $value ) {
			header( $name . ': ' . $value );
		}
	}
}
add_action( 'send_headers', 'lavtheme_perf_security_headers' );

/* -------------------------------------------------------------------------
 * Critical-path trimming — the theme's own JS (assets/js/main.js) is vanilla
 * and has ZERO jQuery dependency, so jQuery only ships for Easy Digital
 * Downloads. Pushing jQuery + EDD's progressive scripts to the footer removes
 * them from the render-blocking head, improving FCP/LCP. WordPress preserves
 * dependency order, so EDD (which depends on jquery) still loads correctly.
 * ---------------------------------------------------------------------- */

/**
 * Move jQuery (and EDD's frontend scripts) out of the render-blocking head.
 */
function lavtheme_perf_scripts_to_footer() {
	if ( is_admin() ) {
		return;
	}
	$scripts = wp_scripts();
	$to_foot = (array) apply_filters(
		'lavtheme_perf_footer_handles',
		array( 'jquery', 'jquery-core', 'jquery-migrate', 'edd-ajax', 'edd-checkout-global' )
	);
	foreach ( $to_foot as $handle ) {
		if ( isset( $scripts->registered[ $handle ] ) ) {
			$scripts->add_data( $handle, 'group', 1 ); // group 1 = footer.
		}
	}
}
add_action( 'wp_enqueue_scripts', 'lavtheme_perf_scripts_to_footer', 100 );

/**
 * Defer EDD's footer scripts so they never block interactivity. Composes with
 * the existing lavtheme_perf_defer_scripts() filter list.
 *
 * @param array $handles Handles to defer.
 * @return array
 */
function lavtheme_perf_defer_edd( $handles ) {
	return array_merge( (array) $handles, array( 'edd-ajax', 'edd-checkout-global' ) );
}
add_filter( 'lavtheme_perf_defer_handles', 'lavtheme_perf_defer_edd' );

/**
 * Drop Easy Digital Downloads' render-blocking CSS on the front page. The
 * homepage product cards are styled by the theme (lavc and lavp classes via
 * products.css), not by EDD's stylesheet, so its CSS is dead render-blocking
 * weight here. EDD pages (cart, checkout, single download) keep their styles.
 */
function lavtheme_perf_trim_edd_css_frontpage() {
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
add_action( 'wp_enqueue_scripts', 'lavtheme_perf_trim_edd_css_frontpage', 100 );

/**
 * Drop jQuery + jQuery Migrate + EDD's scripts from the FRONT PAGE only. The
 * homepage's own JS (assets/js/main.js) is vanilla and there is no EDD cart UI
 * on it, so these ~34 KiB are pure critical-path weight here. Cart, checkout and
 * single-download pages are untouched, so EDD keeps working everywhere it counts.
 * Scoped to is_front_page() and only when EDD did not actually output a cart.
 */
function lavtheme_perf_trim_frontpage_js() {
	if ( ! is_front_page() || is_admin() ) {
		return;
	}
	/** Escape hatch in case a future homepage feature needs jQuery. */
	if ( ! apply_filters( 'lavtheme_perf_strip_frontpage_jquery', true ) ) {
		return;
	}
	$handles = array( 'jquery', 'jquery-core', 'jquery-migrate', 'edd-ajax', 'edd-checkout-global', 'edd-ajax-cart' );
	foreach ( $handles as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'lavtheme_perf_trim_frontpage_js', 100 );

/* -------------------------------------------------------------------------
 * Automatic, LCP-safe lazy-loading for EVERY front-end image.
 *
 * A permanent catch-all: any <img> rendered anywhere — theme templates, the
 * editor, plugins, future code — gets loading="lazy" + decoding="async" without
 * having to remember to add it. It is deliberately LCP-safe (no negative perf
 * effect): the FIRST real (non data-URI) image on the page is left eager because
 * that is the likely Largest Contentful Paint element, and lazy-loading it would
 * delay LCP. Anything already opting in/out — an existing loading= attribute,
 * fetchpriority="high", or a data-no-lazy marker — is respected, never overridden.
 * Runs on front-end HTML only and only at page-generation time (the result is
 * stored by the page cache), so there is no per-request cost on cache hits.
 * ---------------------------------------------------------------------- */

/**
 * Open an output buffer over the rendered page so every <img> can be processed.
 */
function lavtheme_lazy_buffer() {
	if ( is_admin() || is_feed() || is_embed() || is_robots()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return;
	}
	ob_start( 'lavtheme_lazy_filter' );
}
add_action( 'template_redirect', 'lavtheme_lazy_buffer', 1 );

/**
 * Add loading="lazy" + decoding="async" to images that should have them.
 *
 * @param string $html Buffered page HTML.
 * @return string
 */
function lavtheme_lazy_filter( $html ) {
	if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<img' ) ) {
		return $html;
	}

	/** Allow disabling the catch-all entirely. */
	if ( ! apply_filters( 'lavtheme_auto_lazyload', true ) ) {
		return $html;
	}

	$lcp_done = false;

	return (string) preg_replace_callback(
		'#<img\b[^>]*>#i',
		function ( $m ) use ( &$lcp_done ) {
			$tag = $m[0];

			// Always ensure asynchronous decoding (no downside, frees the main thread).
			if ( ! preg_match( '/\sdecoding\s*=/i', $tag ) ) {
				$tag = preg_replace( '/<img\b/i', '<img decoding="async"', $tag, 1 );
			}

			// Respect explicit intent — never override an author/plugin decision.
			if ( preg_match( '/\sloading\s*=/i', $tag )
				|| preg_match( '/fetchpriority\s*=\s*["\']?\s*high/i', $tag )
				|| false !== stripos( $tag, 'data-no-lazy' ) ) {
				return $tag;
			}

			// LCP protection: keep the first real (non data-URI) image eager.
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
