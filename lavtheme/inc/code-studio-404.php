<?php
/**
 * Theme Code Studio — 404 / Error page context.
 *
 * Mirrors the Shop/Blog contexts. Reuses the downloads (dl) AJAX plumbing (the
 * panel dispatches the '404' context to lavtheme_cs_dl_* via ctxIsDl(); '404'
 * branches live in code-studio-downloads.php + code-studio-source-reader.php).
 * This file owns the front-end render: the editable Template body
 * (override-or-file) and the CSS/JS injection on the standalone 404 document.
 *
 * Editors (never empty): Global CSS ← assets/css/404.css, Global JS ←
 * assets/js/404.js, Template (PHP/HTML) ← template-parts/404.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the editable 404 Template (PHP/HTML) override, if any.
 *
 * @return string Buffered output, or '' to fall back to the file.
 */
function lavtheme_cs_404_template_body() {
	if ( ! is_404() ) {
		return '';
	}
	return lavtheme_cs_dl_compose_body( '404', 'template-parts/404.php' );
}

/**
 * Render the error body: the editable Template override, else the real file
 * (template-parts/404.php). Called by 404.php.
 */
function lavtheme_cs_404_render() {
	$body = function_exists( 'lavtheme_cs_dl_compose_body' ) ? lavtheme_cs_404_template_body() : '';
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	if ( function_exists( 'lavtheme_part' ) ) {
		lavtheme_part( '404' );
	} else {
		get_template_part( 'template-parts/404' );
	}
}

/** Inject the 404 context CSS (override-or-file) in the head — single source. */
function lavtheme_cs_404_head() {
	if ( ! is_404() || ! function_exists( 'lavtheme_cs_dl_get' ) ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( '404' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( '404', $r['slug'], 'css' );
			if ( 'design' === $r['slug'] ) {
				$m = (string) lavtheme_cs_dl_get( '404', 'design', 'mcss' );
				if ( '' !== trim( $m ) ) {
					$c .= "\n" . $m;
				}
			}
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( '404', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		// Registry not built yet (no admin visit) — the real stylesheet + mobile layer.
		$css = (string) lavtheme_cs_dl_get( '404', 'design', 'css' ) . "\n" . (string) lavtheme_cs_dl_get( '404', 'design', 'mcss' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-404-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitised on save; file is trusted.
	}
}
add_action( 'wp_head', 'lavtheme_cs_404_head', 7 );

/** Inject the 404 context JS (override-or-file) in the footer. */
function lavtheme_cs_404_footer() {
	if ( ! is_404() || ! function_exists( 'lavtheme_cs_dl_get' ) ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( '404' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( '404', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( '404', 'design', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-404-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing-tag neutralised on save.
	}
}
add_action( 'wp_footer', 'lavtheme_cs_404_footer', 101 );
