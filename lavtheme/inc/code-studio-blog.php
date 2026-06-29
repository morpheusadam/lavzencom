<?php
/**
 * Theme Code Studio — Blog (archive) context.
 *
 * Mirrors the Shop context. Reuses the downloads (dl) AJAX plumbing (the panel
 * dispatches the 'blog' context to lavtheme_cs_dl_* via ctxIsDl(); 'blog'
 * branches live in code-studio-downloads.php). This file owns the front-end
 * render: the editable Template body (override-or-file) and the Global CSS/JS
 * injection on blog pages (single source — enqueue removed).
 *
 * Editors (never empty): Global CSS ← assets/css/blog.css, Global JS ←
 * assets/js/blog.js, Template (PHP/HTML) ← template-parts/blog.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the editable blog Template (PHP/HTML) override, if any.
 *
 * @return string Buffered output, or '' to fall back to the file.
 */
function lavtheme_cs_blog_template_body() {
	$is = ( function_exists( 'lavtheme_is_blog' ) && lavtheme_is_blog() )
		|| ( function_exists( 'lavtheme_is_blog_page_request' ) && lavtheme_is_blog_page_request() );
	if ( ! $is ) {
		return '';
	}
	return lavtheme_cs_dl_compose_body( 'blog', 'template-parts/blog.php' );
}

/**
 * Render the blog layout: the editable Template override, else the real file
 * (template-parts/blog.php). Called by lavtheme_blog_render().
 */
function lavtheme_cs_blog_render() {
	$body = lavtheme_cs_blog_template_body();
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	if ( function_exists( 'lavtheme_part' ) ) {
		lavtheme_part( 'blog' );
	} else {
		get_template_part( 'template-parts/blog' );
	}
}

/** Inject the blog context CSS (override-or-file) in the head — single source. */
function lavtheme_cs_blog_head() {
	if ( ! function_exists( 'lavtheme_is_blog' ) || ! lavtheme_is_blog() ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'blog' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( 'blog', $r['slug'], 'css' );
			if ( 'design' === $r['slug'] ) {
				$m = (string) lavtheme_cs_dl_get( 'blog', 'design', 'mcss' );
				if ( '' !== trim( $m ) ) {
					$c .= "\n" . $m;
				}
			}
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( 'blog', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		$css = (string) lavtheme_cs_dl_get( 'blog', 'design', 'css' ) . "\n" . (string) lavtheme_cs_dl_get( 'blog', 'design', 'mcss' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-blog-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitised on save; file is trusted.
	}
}
add_action( 'wp_head', 'lavtheme_cs_blog_head', 7 );

/** Inject the blog context JS (override-or-file) in the footer. */
function lavtheme_cs_blog_footer() {
	if ( ! function_exists( 'lavtheme_is_blog' ) || ! lavtheme_is_blog() ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'blog' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'schema' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( 'blog', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( 'blog', 'design', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-blog-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing-tag neutralised on save.
	}
}
add_action( 'wp_footer', 'lavtheme_cs_blog_footer', 101 );
