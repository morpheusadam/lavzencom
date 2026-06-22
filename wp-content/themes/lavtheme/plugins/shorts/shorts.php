<?php
/**
 * Plugin: Shorts
 * Description: Short-form content / reels feature for the theme (stub — placeholder).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

lavtheme_plugins_register_menu(
	array(
		'slug'     => 'lavtheme-shorts',
		'title'    => __( 'Shorts', 'lavtheme' ),
		'callback' => 'lavtheme_shorts_render',
		'position' => 24,
	)
);

/**
 * Render the Shorts admin screen.
 */
function lavtheme_shorts_render() {
	lavtheme_plugins_placeholder(
		__( 'Shorts', 'lavtheme' ),
		__( 'A short-form vertical content feed (reels/stories style) will be built here.', 'lavtheme' )
	);
}
