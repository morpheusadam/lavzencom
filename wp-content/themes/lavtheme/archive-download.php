<?php
/**
 * Shop — Easy Digital Downloads post-type archive.
 *
 * Standard WordPress template (no EDD internal template is overridden). The
 * real `download` main query is filtered in inc/edd-shop.php; the layout lives
 * in template-parts/shop.php so the taxonomy archives can reuse it.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

lavtheme_part( 'shop' );

get_footer();
