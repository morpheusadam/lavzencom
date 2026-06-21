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
	wp_enqueue_style(
		'lavtheme-main',
		LAVTHEME_URI . 'assets/css/main.css',
		array(),
		lavtheme_asset_ver( 'assets/css/main.css' )
	);

	// Front-page EDD products grid styling (kept out of main.css).
	if ( is_front_page() ) {
		wp_enqueue_style(
			'lavtheme-products',
			LAVTHEME_URI . 'assets/css/products.css',
			array( 'lavtheme-main' ),
			lavtheme_asset_ver( 'assets/css/products.css' )
		);
	}

	// NOTE: main.js is intentionally NOT enqueued here. The Theme Code Studio
	// delivers it as the "Global JS" default via wp_footer (lavtheme_cs_footer_js),
	// so it can be edited and so an edited copy never runs twice.

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'lavtheme_enqueue_assets' );
