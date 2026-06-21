<?php
/**
 * Shop — Easy Digital Downloads post-type archive.
 *
 * Standard WordPress template (no EDD internal template is overridden). The
 * real `download` main query is filtered in inc/edd-shop.php; the layout lives
 * in template-parts/shop.php and is rendered through the Code Studio "Shop
 * (archive)" context (editable Template override, else the file).
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
