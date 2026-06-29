<?php
/**
 * Code Studio — per-page editable Template loader.
 *
 * Reached via template_include ONLY when a page has a non-empty editable
 * Template (design/html) override AND PHP sections are unlocked (and it is not
 * an Elementor canvas page). Renders the composed body (extra PHP + the
 * override) with a guaranteed fall-through to the resolved template file. When
 * no override exists the filter never routes here, so default page rendering is
 * completely unchanged.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

$lav_id   = (int) get_queried_object_id();
$lav_body = function_exists( 'lavtheme_cs_page_compose_body' ) ? lavtheme_cs_page_compose_body( $lav_id ) : '';

if ( '' !== $lav_body ) {
	echo $lav_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered, syntax-checked admin template.
} else {
	$lav_path = get_theme_file_path( Lav_CS_Source_Reader::resolve_page_template( $lav_id ) );
	if ( is_readable( $lav_path ) ) {
		include $lav_path;
	}
}
