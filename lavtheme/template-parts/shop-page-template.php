<?php
/**
 * Template for the configured EDD "Shop Page" (a normal page designated in
 * EDD → Settings → Pages). Renders the full shop design + filters via a
 * secondary downloads query (lavtheme_cs_shop_render_page), so the page's own
 * content / [downloads] block is replaced — no double render. Routed here by
 * lavtheme_cs_shop_page_template() (template_include).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

if ( function_exists( 'lavtheme_cs_shop_render_page' ) ) {
	lavtheme_cs_shop_render_page();
} elseif ( function_exists( 'lavtheme_part' ) ) {
	lavtheme_part( 'shop' );
}

get_footer();
