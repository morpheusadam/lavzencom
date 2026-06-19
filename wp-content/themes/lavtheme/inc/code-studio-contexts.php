<?php
/**
 * Theme Code Studio — page contexts (EDD / WooCommerce, etc.).
 *
 * The Front Page is edited through the section system. Plugin pages (EDD shop,
 * single download, checkout, purchase confirmation; Woo shop/cart/checkout/
 * product) are dynamically rendered by their plugins and have no static markup
 * to extract. So per context we offer a SAFE layer only:
 *   - Custom CSS  (injected in <head> on that page)
 *   - Custom JS   (injected in <footer> on that page)
 *   - HTML Before / HTML After the page content
 * Plugin templates are never overridden.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Easy Digital Downloads available?
 *
 * @return bool
 */
function lavtheme_cs_edd() {
	return function_exists( 'lavtheme_edd_active' ) ? lavtheme_edd_active() : ( function_exists( 'EDD' ) || post_type_exists( 'download' ) );
}

/**
 * Is WooCommerce available?
 *
 * @return bool
 */
function lavtheme_cs_woo() {
	return class_exists( 'WooCommerce' );
}

/**
 * Available editing contexts (besides the Front Page), keyed by context id.
 *
 * `scope` selects how HTML before/after is injected: 'singular' uses the
 * the_content filter; 'archive' uses loop_start / loop_end.
 *
 * @return array
 */
function lavtheme_cs_contexts() {
	$ctx = array();

	if ( lavtheme_cs_edd() ) {
		$ctx['edd_archive']  = array( 'label' => 'EDD · Downloads Archive (Shop)', 'scope' => 'archive' );
		$ctx['edd_single']   = array( 'label' => 'EDD · Single Download', 'scope' => 'singular' );
		$ctx['edd_checkout'] = array( 'label' => 'EDD · Checkout', 'scope' => 'singular' );
		$ctx['edd_success']  = array( 'label' => 'EDD · Purchase Confirmation', 'scope' => 'singular' );
	}

	if ( lavtheme_cs_woo() ) {
		$ctx['wc_shop']     = array( 'label' => 'WooCommerce · Shop', 'scope' => 'archive' );
		$ctx['wc_cart']     = array( 'label' => 'WooCommerce · Cart', 'scope' => 'singular' );
		$ctx['wc_checkout'] = array( 'label' => 'WooCommerce · Checkout', 'scope' => 'singular' );
		$ctx['wc_product']  = array( 'label' => 'WooCommerce · Single Product', 'scope' => 'singular' );
	}

	return apply_filters( 'lavtheme_cs_contexts', $ctx );
}

/**
 * Does the given context match the current request? (Guarded conditionals.)
 *
 * @param string $key Context id.
 * @return bool
 */
function lavtheme_cs_context_matches( $key ) {
	switch ( $key ) {
		case 'edd_archive':
			return is_post_type_archive( 'download' );
		case 'edd_single':
			return is_singular( 'download' );
		case 'edd_checkout':
			return function_exists( 'edd_is_checkout' ) && edd_is_checkout();
		case 'edd_success':
			return function_exists( 'edd_is_success_page' ) && edd_is_success_page();
		case 'wc_shop':
			return function_exists( 'is_shop' ) && is_shop();
		case 'wc_cart':
			return function_exists( 'is_cart' ) && is_cart();
		case 'wc_checkout':
			return function_exists( 'is_checkout' ) && is_checkout();
		case 'wc_product':
			return function_exists( 'is_product' ) && is_product();
	}
	return false;
}

/**
 * The context id for the current front-end request, or '' if none.
 *
 * @return string
 */
function lavtheme_cs_current_context() {
	if ( is_admin() ) {
		return '';
	}
	// Single download must win before the generic singular checks, etc. — the
	// context list order already encodes the right priority.
	foreach ( lavtheme_cs_contexts() as $key => $c ) {
		if ( lavtheme_cs_context_matches( $key ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Option name for a context.
 *
 * @param string $key Context id.
 * @return string
 */
function lavtheme_cs_ctx_option( $key ) {
	return 'lavtheme_cs_ctx_' . sanitize_key( $key );
}

/**
 * Stored fields for a context (css/js/html_before/html_after).
 *
 * @param string $key Context id.
 * @return array
 */
function lavtheme_cs_ctx_get( $key ) {
	$v = get_option( lavtheme_cs_ctx_option( $key ), array() );
	if ( ! is_array( $v ) ) {
		$v = array();
	}
	return wp_parse_args(
		$v,
		array(
			'css'         => '',
			'js'          => '',
			'html_before' => '',
			'html_after'  => '',
		)
	);
}

/**
 * Persist one field of a context.
 *
 * @param string $key   Context id.
 * @param string $field css|js|html_before|html_after.
 * @param string $value Already-sanitised value.
 */
function lavtheme_cs_ctx_set( $key, $field, $value ) {
	$data            = lavtheme_cs_ctx_get( $key );
	$data[ $field ]  = $value;
	update_option( lavtheme_cs_ctx_option( $key ), $data );
}

/* --------------------------------------------------------------------------
 * Front-end application (only on the matching page).
 * ------------------------------------------------------------------------ */

/**
 * Inject the context CSS into the head.
 */
function lavtheme_cs_ctx_head() {
	$key = lavtheme_cs_current_context();
	if ( '' === $key ) {
		return;
	}
	$css = lavtheme_cs_ctx_get( $key )['css'];
	if ( '' !== trim( $css ) ) {
		// Sanitised on save (lavtheme_sanitize_css).
		echo '<style id="lavtheme-ctx-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'lavtheme_cs_ctx_head', 101 );

/**
 * Inject the context JS into the footer.
 */
function lavtheme_cs_ctx_footer() {
	$key = lavtheme_cs_current_context();
	if ( '' === $key ) {
		return;
	}
	$js = lavtheme_cs_ctx_get( $key )['js'];
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-ctx-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'lavtheme_cs_ctx_footer', 101 );

/**
 * Inject HTML before/after the content on singular context pages.
 *
 * @param string $content Post content.
 * @return string
 */
function lavtheme_cs_ctx_content( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$key = lavtheme_cs_current_context();
	if ( '' === $key ) {
		return $content;
	}
	$contexts = lavtheme_cs_contexts();
	if ( ! isset( $contexts[ $key ] ) || 'singular' !== $contexts[ $key ]['scope'] ) {
		return $content;
	}
	$d      = lavtheme_cs_ctx_get( $key );
	$before = '' !== trim( $d['html_before'] ) ? lavtheme_cs_render_html( $d['html_before'] ) : '';
	$after  = '' !== trim( $d['html_after'] ) ? lavtheme_cs_render_html( $d['html_after'] ) : '';
	return $before . $content . $after;
}
add_filter( 'the_content', 'lavtheme_cs_ctx_content', 20 );

/**
 * Inject HTML before the loop on archive context pages.
 *
 * @param WP_Query $q Query.
 */
function lavtheme_cs_ctx_loop_start( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$key      = lavtheme_cs_current_context();
	$contexts = lavtheme_cs_contexts();
	if ( '' === $key || ! isset( $contexts[ $key ] ) || 'archive' !== $contexts[ $key ]['scope'] ) {
		return;
	}
	$html = lavtheme_cs_ctx_get( $key )['html_before'];
	if ( '' !== trim( $html ) ) {
		echo lavtheme_cs_render_html( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'loop_start', 'lavtheme_cs_ctx_loop_start' );

/**
 * Inject HTML after the loop on archive context pages.
 *
 * @param WP_Query $q Query.
 */
function lavtheme_cs_ctx_loop_end( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$key      = lavtheme_cs_current_context();
	$contexts = lavtheme_cs_contexts();
	if ( '' === $key || ! isset( $contexts[ $key ] ) || 'archive' !== $contexts[ $key ]['scope'] ) {
		return;
	}
	$html = lavtheme_cs_ctx_get( $key )['html_after'];
	if ( '' !== trim( $html ) ) {
		echo lavtheme_cs_render_html( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'loop_end', 'lavtheme_cs_ctx_loop_end' );
