<?php
/**
 * lavtheme bootstrap.
 *
 * Loads the modular include files. No business logic lives here.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'LAVTHEME_VERSION' ) ) {
	define( 'LAVTHEME_VERSION', '1.1.0' );
}
if ( ! defined( 'LAVTHEME_DIR' ) ) {
	define( 'LAVTHEME_DIR', trailingslashit( get_template_directory() ) );
}
if ( ! defined( 'LAVTHEME_URI' ) ) {
	define( 'LAVTHEME_URI', trailingslashit( get_template_directory_uri() ) );
}

/**
 * Require an include file if it exists.
 *
 * @param string $relative Path relative to the inc/ folder.
 */
function lavtheme_require( $relative ) {
	$path = LAVTHEME_DIR . 'inc/' . $relative;
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

// Core: helpers, theme setup, asset loading.
lavtheme_require( 'helpers.php' );
lavtheme_require( 'setup.php' );
lavtheme_require( 'menus.php' );
lavtheme_require( 'enqueue.php' );

// Front-end data: Easy Digital Downloads products.
lavtheme_require( 'edd.php' );
lavtheme_require( 'edd-shop.php' );
lavtheme_require( 'edd-shop-ui.php' );
lavtheme_require( 'edd-single-product-hooks.php' );
lavtheme_require( 'edd-product-meta.php' );

// Theme Code Studio: per-section code editing panel (DB + file modes).
lavtheme_require( 'code-studio-registry.php' );
lavtheme_require( 'code-studio.php' );
lavtheme_require( 'code-studio-save.php' );
lavtheme_require( 'code-studio-inject.php' );
lavtheme_require( 'code-studio-contexts.php' );
lavtheme_require( 'code-studio-pages.php' );
lavtheme_require( 'code-studio-downloads.php' );

// Read-only: download a section's saved code (Global/Schema/section, all tabs).
lavtheme_require( 'code-studio-export.php' );
