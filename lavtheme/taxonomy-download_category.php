<?php
/**
 * Shop — EDD download_category term archive. Reuses the shop layout (via the
 * Code Studio "Shop (archive)" context), with the term applied to the main
 * query plus any sidebar filters/sort.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'lavtheme_cs_shop_render' ) ) {
	lavtheme_cs_shop_render();
} else {
	lavtheme_part( 'shop' );
}

get_footer();
