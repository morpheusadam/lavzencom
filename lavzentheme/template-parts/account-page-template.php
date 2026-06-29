<?php
/**
 * Wrapper for the "My Account" page (routed here via template_include).
 * Opens the shell, renders the account dashboard body, closes the shell.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();
get_template_part( 'template-parts/account' );
get_footer();
