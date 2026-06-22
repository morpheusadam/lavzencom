<?php
/**
 * Plugin: Security
 * Description: Hardening controls for the theme (stub — UI placeholder).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

lavtheme_plugins_register_menu(
	array(
		'slug'     => 'lavtheme-security',
		'title'    => __( 'Security', 'lavtheme' ),
		'callback' => 'lavtheme_security_render',
		'position' => 21,
	)
);

/**
 * Render the Security admin screen.
 */
function lavtheme_security_render() {
	lavtheme_plugins_placeholder(
		__( 'Security', 'lavtheme' ),
		__( 'Login hardening, headers, file-edit lockdown and request firewall controls will live here.', 'lavtheme' )
	);
}
