<?php
/**
 * Single EDD download — thin loader (the product design is in the body part).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

// Don't let EDD auto-append a second purchase button inside the content.
remove_filter( 'the_content', 'edd_after_download_content' );

get_template_part( 'template-parts/single-download-body' );

get_footer();
