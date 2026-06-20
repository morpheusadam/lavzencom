<?php
/**
 * Theme Code Studio — Shop (download archive) context.
 *
 * Makes the shop archive editable in Code Studio exactly like the other
 * contexts. It reuses the downloads context plumbing (the same lavtheme_cs_dl_*
 * AJAX handlers + 'shop' branches in code-studio-downloads.php), so the panel
 * dispatches the 'shop' context to those handlers. This file owns only the
 * front-end render: the editable Template body (override-or-file) and the
 * Global CSS/JS injection on shop pages (single source — enqueue removed).
 *
 * Editors (never empty): Global CSS ← assets/css/shop.css, Global JS ←
 * assets/js/shop.js, Template (PHP/HTML) ← template-parts/shop.php.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run the editable shop Template (PHP/HTML) override, if any.
 *
 * @return string Buffered output, or '' to fall back to the file.
 */
function lavtheme_cs_shop_template_body() {
	$is = ( function_exists( 'lavtheme_is_shop' ) && lavtheme_is_shop() ) || lavtheme_is_shop_page_request();
	if ( ! $is || ! lavtheme_cs_php_allowed() ) {
		return '';
	}
	$override = (string) get_option( lavtheme_cs_dl_key( 'shop', 'design', 'php' ), '' );
	if ( '' === trim( $override ) ) {
		return '';
	}
	$out = lavtheme_cs_run_php( $override );
	return '' !== trim( $out ) ? $out : '';
}

/**
 * Render the shop layout: the editable Template override, else the real file
 * (template-parts/shop.php). Called by archive-download.php / taxonomy-download_*.
 */
function lavtheme_cs_shop_render() {
	$body = lavtheme_cs_shop_template_body();
	if ( '' !== $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
		return;
	}
	if ( function_exists( 'lavtheme_part' ) ) {
		lavtheme_part( 'shop' );
	} else {
		get_template_part( 'template-parts/shop' );
	}
}

/**
 * Inject the shop context CSS (override-or-file) in the head — single source,
 * replacing the old enqueue. Loops the shop registry so Global + any custom
 * section CSS apply; the design (template) section has no CSS.
 */
function lavtheme_cs_shop_head() {
	if ( ! function_exists( 'lavtheme_is_shop' ) || ! lavtheme_is_shop() ) {
		return;
	}
	$css = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'shop' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'design' === $r['slug'] ) {
				continue;
			}
			$c = (string) lavtheme_cs_dl_get( 'shop', $r['slug'], 'css' );
			if ( 'global' === $r['slug'] ) {
				$bg = (string) lavtheme_cs_dl_get( 'shop', 'global', 'bg' );
				if ( '' !== trim( $bg ) ) {
					$c .= "\n" . $bg;
				}
			}
			if ( '' !== trim( $c ) ) {
				$css .= "\n" . $c;
			}
		}
	} else {
		// Registry not built yet (no admin visit) — just the file default CSS.
		$css = (string) lavtheme_cs_dl_get( 'shop', 'global', 'css' );
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-shop-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS sanitised on save; file is trusted.
	}
}
add_action( 'wp_head', 'lavtheme_cs_shop_head', 7 );

/**
 * Inject the shop context JS (override-or-file) in the footer.
 */
function lavtheme_cs_shop_footer() {
	if ( ! function_exists( 'lavtheme_is_shop' ) || ! lavtheme_is_shop() ) {
		return;
	}
	$js  = '';
	$reg = get_option( lavtheme_cs_dl_regopt( 'shop' ), null );
	if ( is_array( $reg ) ) {
		foreach ( $reg as $r ) {
			if ( ! isset( $r['slug'] ) || 'design' === $r['slug'] ) {
				continue;
			}
			$j = (string) lavtheme_cs_dl_get( 'shop', $r['slug'], 'js' );
			if ( '' !== trim( $j ) ) {
				$js .= ';(function(){' . $j . '})();';
			}
		}
	} else {
		$js = (string) lavtheme_cs_dl_get( 'shop', 'global', 'js' );
	}
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-shop-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing-tag neutralised on save.
	}
}
add_action( 'wp_footer', 'lavtheme_cs_shop_footer', 101 );

/* ===================== Shop on the configured EDD Shop Page ================ */

/** True while the configured Shop Page is rendering (pagination context). */
function lavtheme_is_shop_page_request() {
	return ! empty( $GLOBALS['lavtheme_shop_page_active'] );
}

/**
 * Render the shop on the configured EDD Shop Page (a normal page). Swaps in a
 * downloads query so the shared layout (template-parts/shop.php, via the Code
 * Studio Template override or the file) renders the grid + pagination, then
 * restores the page query. The page's own content/[downloads] block is bypassed
 * (no double render).
 */
function lavtheme_cs_shop_render_page() {
	global $wp_query, $post;
	$orig_query = $wp_query;
	$orig_post  = $post;

	$wp_query = lavtheme_shop_page_query(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['lavtheme_shop_page_active'] = true;

	lavtheme_cs_shop_render();

	$GLOBALS['lavtheme_shop_page_active'] = false;
	wp_reset_postdata();
	$wp_query = $orig_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$post     = $orig_post;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

/**
 * Route ONLY the configured Shop Page through a thin template that renders the
 * shop design. The auto `download` archive is unaffected; other pages untouched.
 *
 * @param string $template Resolved template path.
 * @return string
 */
function lavtheme_cs_shop_page_template( $template ) {
	if ( is_admin() || ! function_exists( 'lavtheme_shop_page_id' ) ) {
		return $template;
	}
	$pid = lavtheme_shop_page_id();
	if ( $pid && is_page( $pid ) ) {
		$custom = get_theme_file_path( 'template-parts/shop-page-template.php' );
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'lavtheme_cs_shop_page_template', 99 );
