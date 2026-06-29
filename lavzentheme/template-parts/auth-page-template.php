<?php
/**
 * Wrapper for the Login/Register page (routed here via template_include).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();
get_template_part( 'template-parts/auth' );
get_footer();
