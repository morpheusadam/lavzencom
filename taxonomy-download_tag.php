<?php
/**
 * Shop — EDD download_tag term archive. Reuses the shop layout (via the Code
 * Studio "Shop (archive)" context).
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
