<?php
/**
 * Template for the dedicated "Blog" page (used when there's no standard posts
 * page). Renders the blog design via a secondary posts query — the page's own
 * content is bypassed (no double render). Routed here by
 * lavtheme_blog_page_template() (template_include).
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! headers_sent() ) {
	header( 'X-Content-Type-Options: nosniff' );
}

get_header();

if ( function_exists( 'lavtheme_blog_render_page' ) ) {
	lavtheme_blog_render_page();
} elseif ( function_exists( 'lavtheme_part' ) ) {
	lavtheme_part( 'blog' );
}

get_footer();
