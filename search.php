<?php
/**
 * Search results — rendered with the blog design.
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
