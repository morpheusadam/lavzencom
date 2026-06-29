<?php
/**
 * Easy Digital Downloads integration for the front-page products section.
 *
 * Everything is guarded so the theme never fatals when EDD is inactive;
 * the products section then falls back to its original static markup.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Easy Digital Downloads active and ready?
 *
 * @return bool
 */
function lavtheme_edd_active() {
	return function_exists( 'EDD' ) || class_exists( 'Easy_Digital_Downloads' ) || post_type_exists( 'download' );
}

/**
 * Admin notice when EDD is not active.
 */
function lavtheme_edd_admin_notice() {
	if ( lavtheme_edd_active() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'lavtheme: Easy Digital Downloads is not active — the products section is showing demo categories until you activate it.', 'lavtheme' ) . '</p></div>';
}
add_action( 'admin_notices', 'lavtheme_edd_admin_notice' );
