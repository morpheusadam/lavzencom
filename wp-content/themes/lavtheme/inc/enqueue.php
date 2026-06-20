<?php
/**
 * Front-end asset enqueue.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versioned asset URL using filemtime for cache-busting.
 *
 * @param string $relative Path relative to the theme root (e.g. assets/css/main.css).
 * @return string
 */
function lavtheme_asset_ver( $relative ) {
	$file = LAVTHEME_DIR . ltrim( $relative, '/' );
	return is_readable( $file ) ? (string) filemtime( $file ) : LAVTHEME_VERSION;
}

/**
 * Enqueue front-end styles and scripts.
 */
function lavtheme_enqueue_assets() {
	// Google Fonts are loaded verbatim in header.php (preload pattern); main stylesheet here.
	if ( lavtheme_cs_inline_css() ) {
		// Built-in CSS is composed from the editable per-section split files and
		// attached inline under a src-less 'lavtheme-main' handle — same cascade
		// position main.css held, so dependents (products.css) and the override
		// layer keep working while each built-in tab stays editable/removable.
		wp_register_style( 'lavtheme-main', false, array(), LAVTHEME_VERSION );
		wp_enqueue_style( 'lavtheme-main' );
		wp_add_inline_style( 'lavtheme-main', lavtheme_cs_builtin_base_css() );
	} else {
		// Rolled back (LAVTHEME_DISABLE_INLINE_CSS): the full monolithic stylesheet.
		wp_enqueue_style(
			'lavtheme-main',
			LAVTHEME_URI . 'assets/css/main.css',
			array(),
			lavtheme_asset_ver( 'assets/css/main.css' )
		);
	}

	// Front-page EDD products grid styling (kept out of main.css).
	if ( is_front_page() ) {
		wp_enqueue_style(
			'lavtheme-products',
			LAVTHEME_URI . 'assets/css/products.css',
			array( 'lavtheme-main' ),
			lavtheme_asset_ver( 'assets/css/products.css' )
		);
	}

	// Shop archive CSS/JS are NOT enqueued here anymore: the Code Studio "Shop
	// (archive)" context injects them (override-or-file) via
	// lavtheme_cs_shop_head() / lavtheme_cs_shop_footer(), so the editors are the
	// single source and edits apply live. assets/css|js/shop.* stay on disk as the
	// editor defaults + fallback.

	// Single EDD product page CSS/JS are NOT enqueued here anymore: the Code
	// Studio "Single Download (template)" context injects them (override-or-file)
	// via lavtheme_cs_dl_head() / lavtheme_cs_dl_footer(), so the editors are the
	// single source and edits apply live. The files in assets/css|js/single-product.*
	// remain on disk as the editor defaults + fallback.

	// NOTE: main.js is intentionally NOT enqueued here. The Theme Code Studio
	// delivers it as the "Global JS" default via wp_footer (lavtheme_cs_footer_js),
	// so it can be edited and so an edited copy never runs twice.

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lavtheme_enqueue_assets' );
