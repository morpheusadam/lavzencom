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
 * Whether the current request is an EDD purchase-flow page (checkout, success/
 * confirmation, failed transaction, purchase history, or the configured receipt
 * page). Used to load the theme's EDD styling only where it's needed.
 *
 * @return bool
 */
function lavtheme_is_edd_flow() {
	if ( ! function_exists( 'edd_get_option' ) ) {
		return false;
	}
	if ( function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {
		return true;
	}
	if ( function_exists( 'edd_is_success_page' ) && edd_is_success_page() ) {
		return true;
	}
	if ( function_exists( 'edd_is_failed_transaction_page' ) && edd_is_failed_transaction_page() ) {
		return true;
	}
	$ids = array_filter( array_map( 'absint', array(
		edd_get_option( 'purchase_page', 0 ),
		edd_get_option( 'success_page', 0 ),
		edd_get_option( 'failure_page', 0 ),
		edd_get_option( 'purchase_history_page', 0 ),
		edd_get_option( 'confirmation_page', 0 ),
	) ) );

	return ! empty( $ids ) && is_page( $ids );
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

	// Front-page EDD products grid styling. It is tiny (~1.3 KB), so inline it
	// onto the main handle instead of shipping a separate render-blocking request
	// — removes one hop from the homepage critical request chain. Falls back to a
	// normal enqueue if the file can't be read.
	if ( is_front_page() ) {
		$lavtheme_products_file = LAVTHEME_DIR . 'assets/css/products.css';
		$lavtheme_products_css  = is_readable( $lavtheme_products_file ) ? file_get_contents( $lavtheme_products_file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( '' !== $lavtheme_products_css ) {
			wp_add_inline_style( 'lavtheme-main', $lavtheme_products_css );
		} else {
			wp_enqueue_style(
				'lavtheme-products',
				LAVTHEME_URI . 'assets/css/products.css',
				array( 'lavtheme-main' ),
				lavtheme_asset_ver( 'assets/css/products.css' )
			);
		}
	}

	// EDD purchase-flow styling (checkout, cart, receipt, purchase history,
	// confirmation, failed transaction). EDD ships unstyled markup; this maps it
	// onto the theme tokens. Only loads on the EDD flow pages.
	if ( lavtheme_is_edd_flow() ) {
		wp_enqueue_style(
			'lavtheme-checkout',
			LAVTHEME_URI . 'assets/css/checkout.css',
			array( 'lavtheme-main' ),
			lavtheme_asset_ver( 'assets/css/checkout.css' )
		);
	}

	// Single blog post CSS/JS are NOT enqueued here: the Code Studio "Single Post"
	// context injects them (override-or-file) via lavtheme_cs_single_head() /
	// lavtheme_cs_single_footer() (single source, edits apply live). The files in
	// assets/css|js/single.* remain on disk as the editor defaults + fallback.

	// Shop archive CSS/JS are NOT enqueued here anymore: the Code Studio "Shop
	// (archive)" context injects them (override-or-file) via
	// lavtheme_cs_shop_head() / lavtheme_cs_shop_footer(), so the editors are the
	// single source and edits apply live. assets/css|js/shop.* stay on disk as the
	// editor defaults + fallback.

	// Blog archive CSS/JS are NOT enqueued here: the Code Studio "Blog (archive)"
	// context injects them (override-or-file) via lavtheme_cs_blog_head/_footer
	// (single source). assets/css|js/blog.* stay on disk as editor defaults.

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
