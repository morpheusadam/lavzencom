<?php
/**
 * Blog posts index (the standard "Posts page" when one is set). Renders the
 * blog design from the main query.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'lavtheme_blog_render' ) ) {
	lavtheme_blog_render();
} else {
	lavtheme_part( 'blog' );
}

get_footer();
