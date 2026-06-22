<?php
/**
 * Plugin: Caching
 * Description: Page/asset caching controls for the theme (stub — UI placeholder).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

lavtheme_plugins_register_menu(
	array(
		'slug'     => 'lavtheme-caching',
		'title'    => __( 'Caching', 'lavtheme' ),
		'callback' => 'lavtheme_caching_render',
		'position' => 20,
	)
);

/**
 * Render the Caching admin screen.
 */
function lavtheme_caching_render() {
	lavtheme_plugins_placeholder(
		__( 'Caching', 'lavtheme' ),
		__( 'Page caching, asset minification and cache-busting controls will live here.', 'lavtheme' )
	);
}
