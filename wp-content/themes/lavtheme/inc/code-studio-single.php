<?php
/**
 * Theme Code Studio — Single Post context.
 *
 * Mirrors the Shop/Blog contexts: reuses the downloads (dl) plumbing (the panel
 * dispatches the 'single' context to lavtheme_cs_dl_* via ctxIsDl(); the 'single'
 * branches live in code-studio-downloads.php / Source_Reader). This file owns the
 * front-end: the editable Template body (override-or-file) and the CSS/JS
 * injection on single posts (single source — the direct enqueue is removed).
 *
 * Editors (never empty): HTML/PHP ← template-parts/single-article.php, CSS ←
 * assets/css/single.css, JS ← assets/js/single.js, Mobile CSS ← extracted layer.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the single article: the editable Template override, else the real file.
 * Called inside the loop by single.php.
 */
function lavtheme_cs_single_render() {
	$body = is_singular( 'post' ) ? lavtheme_cs_dl_compose_body( 'single', 'template-parts/single-article.php' ) : '';
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	$path = get_theme_file_path( 'template-parts/single-article.php' );
	if ( is_readable( $path ) ) {
		include $path;
	}
}

/** The like AJAX config, printed early so the (context-injected) JS can read it. */
function lavtheme_cs_single_config_head() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$data = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'lavtheme_like' ),
	);
	echo '<script id="lavtheme-single-cfg">window.LavSingle=' . wp_json_encode( $data ) . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'lavtheme_cs_single_config_head', 5 );

/** Inject the single-post CSS (override-or-file) in the head — single source. */
function lavtheme_cs_single_head() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'single' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( 'single', $r['slug'], 'css' );
			if ( 'design' === $r['slug'] ) {
				$m = (string) lavtheme_cs_dl_get( 'single', 'design', 'mcss' );
				if ( '' !== trim( $m ) ) {
					$c .= "\n" . $m;
				}
			}
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( 'single', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		$css = (string) lavtheme_cs_dl_get( 'single', 'design', 'css' ) . "\n" . (string) lavtheme_cs_dl_get( 'single', 'design', 'mcss' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-single-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitised on save; file is trusted.
	}
}
add_action( 'wp_head', 'lavtheme_cs_single_head', 7 );

/** Inject the single-post JS (override-or-file) in the footer. */
function lavtheme_cs_single_footer() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'single' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( 'single', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( 'single', 'design', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-single-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing-tag neutralised on save.
	}
}
add_action( 'wp_footer', 'lavtheme_cs_single_footer', 101 );
