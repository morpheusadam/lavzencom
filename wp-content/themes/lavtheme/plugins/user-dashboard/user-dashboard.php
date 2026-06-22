<?php
/**
 * Plugin: User Dashboard
 * Description: Front-end member/user dashboard for the theme (stub — placeholder).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

lavtheme_plugins_register_menu(
	array(
		'slug'     => 'lavtheme-user-dashboard',
		'title'    => __( 'User Dashboard', 'lavtheme' ),
		'callback' => 'lavtheme_user_dashboard_render',
		'position' => 23,
	)
);

/**
 * Render the User Dashboard admin screen.
 */
function lavtheme_user_dashboard_render() {
	lavtheme_plugins_placeholder(
		__( 'User Dashboard', 'lavtheme' ),
		__( 'A front-end account area (orders, downloads, profile, saved items) will be configured here.', 'lavtheme' )
	);
}
