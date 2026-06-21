<?php
/**
 * Single EDD download — thin loader.
 *
 * The product design lives in template-parts/single-download-body.php (the
 * editor default + safe fallback). When the Code Studio "Single Download
 * (template)" context has an editable Template (PHP/HTML) override AND PHP
 * sections are unlocked (LAVTHEME_ALLOW_PHP_SECTIONS), that override is run
 * instead — syntax-checked on save, wrapped in try/catch, and it falls back to
 * the file if it produces nothing. CSS/JS are injected by the dl-template
 * context (lavtheme_cs_dl_head / _footer), so they're editable too.
 *
 * NOT an EDD /edd/ template override — update-safe.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

// Don't let EDD auto-append a second purchase button inside the content.
remove_filter( 'the_content', 'edd_after_download_content' );

// Editable template body (Code Studio override) with a guaranteed file fallback.
$lav_body = function_exists( 'lavtheme_cs_dl_template_body' ) ? lavtheme_cs_dl_template_body() : '';
if ( '' !== $lav_body ) {
	echo $lav_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin code.
} else {
	$lav_path = get_theme_file_path( 'template-parts/single-download-body.php' );
	if ( is_readable( $lav_path ) ) {
		include $lav_path;
	}
}

get_footer();
