<?php
/**
 * Plugin: WP Dash
 * Description: A modern, animated analytics dashboard for the WordPress admin —
 * real site counts plus optimized SVG/CSS charts (area, bars, radial rings) and
 * a pixel-art activity heatmap. No chart library; pure SVG + CSS + a tiny JS.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

// WP Dash menu → open the Code Studio code editor focused on the "wp-dash"
// context (HTML/CSS/JS/PHP for the dashboard), instead of rendering a page.
add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			lavtheme_plugins_parent_slug(),
			__( 'WP Dash', 'lavtheme' ),
			__( 'WP Dash', 'lavtheme' ),
			lavtheme_plugins_cap(),
			'admin.php?page=' . lavtheme_plugins_parent_slug() . '&cs_context=wp-dash'
		);
	},
	22
);

/**
 * Keep the "WP Dash" submenu highlighted while its editor is open — without it,
 * WordPress falls back to highlighting the parent's first item (Code Studio),
 * which looks like the menu "jumped". Highlight only; no behaviour change.
 *
 * @param string $submenu_file Current highlighted submenu slug.
 * @return string
 */
function lavtheme_wp_dash_highlight_menu( $submenu_file ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['page'], $_GET['cs_context'] )
		&& lavtheme_plugins_parent_slug() === $_GET['page']
		&& 'wp-dash' === $_GET['cs_context'] ) {
		return 'admin.php?page=' . lavtheme_plugins_parent_slug() . '&cs_context=wp-dash';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	return $submenu_file;
}
add_filter( 'submenu_file', 'lavtheme_wp_dash_highlight_menu' );

/* ===================================================================
 * Native WordPress Dashboard (Dashboard → Home): apply the editable
 * "wp-dash" Code Studio context — glassmorphism liquid skin by default.
 * CSS/JS/HTML are override-or-file (assets/dash-skin.css, dash-skin.js,
 * template.php). Scoped strictly to the dashboard screen.
 * ================================================================ */

/** True on the native Dashboard home screen only. */
function lavtheme_wp_dash_is_home() {
	if ( ! is_admin() ) {
		return false;
	}
	if ( function_exists( 'get_current_screen' ) ) {
		$s = get_current_screen();
		if ( $s && isset( $s->base ) ) {
			return 'dashboard' === $s->base;
		}
	}
	return isset( $GLOBALS['pagenow'] ) && 'index.php' === $GLOBALS['pagenow'];
}

/** Read a wp-dash context value (override-or-file) via the Code Studio plumbing. */
function lavtheme_wp_dash_ctx_get( $type ) {
	if ( function_exists( 'lavtheme_cs_dl_get' ) ) {
		return (string) lavtheme_cs_dl_get( 'wp-dash', 'design', $type );
	}
	// Fallback: read the shipped default file directly.
	$map  = array( 'css' => 'assets/dash-skin.css', 'js' => 'assets/dash-skin.js' );
	$file = isset( $map[ $type ] ) ? get_theme_file_path( 'plugins/wp-dash/' . $map[ $type ] ) : '';
	return ( $file && is_readable( $file ) ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/** Inject the dashboard skin CSS into the Dashboard <head>. */
function lavtheme_wp_dash_skin_css() {
	if ( ! is_admin() ) {
		return;
	}
	// Apply across the whole admin (scoped to body.wp-admin in the CSS) so the
	// look never "jumps" between menu items. The block editor keeps its own UI.
	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return;
		}
	}
	$css = lavtheme_wp_dash_ctx_get( 'css' );
	$m   = function_exists( 'lavtheme_cs_dl_get' ) ? (string) lavtheme_cs_dl_get( 'wp-dash', 'design', 'mcss' ) : '';
	if ( '' !== trim( $m ) ) {
		$css .= "\n@media (max-width:782px){\n" . $m . "\n}";
	}
	if ( '' !== trim( $css ) ) {
		echo '<style id="lavtheme-wpdash-skin">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored CSS, sanitised on save.
	}
}
add_action( 'admin_head', 'lavtheme_wp_dash_skin_css' );

/** Render the editable glass hero at the top of the Dashboard. */
function lavtheme_wp_dash_skin_hero() {
	if ( ! lavtheme_wp_dash_is_home() ) {
		return;
	}
	$body = function_exists( 'lavtheme_cs_dl_compose_body' ) ? lavtheme_cs_dl_compose_body( 'wp-dash', 'plugins/wp-dash/template.php' ) : '';
	if ( '' === $body ) {
		$file = get_theme_file_path( 'plugins/wp-dash/template.php' );
		if ( is_readable( $file ) ) {
			ob_start();
			include $file;
			$body = (string) ob_get_clean();
		}
	}
	if ( '' !== trim( $body ) ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered template / admin-authored override.
	}
}
// admin_notices always fires at the top of the Dashboard content (single, reliable).
add_action( 'admin_notices', 'lavtheme_wp_dash_skin_hero', 5 );

/** Inject the dashboard JS into the Dashboard footer. */
function lavtheme_wp_dash_skin_js() {
	if ( ! lavtheme_wp_dash_is_home() ) {
		return;
	}
	$js = lavtheme_wp_dash_ctx_get( 'js' );
	if ( '' !== trim( $js ) ) {
		echo '<script id="lavtheme-wpdash-js">(function(){' . $js . '})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored, closing tag neutralised on save.
	}
}
add_action( 'admin_footer', 'lavtheme_wp_dash_skin_js' );
