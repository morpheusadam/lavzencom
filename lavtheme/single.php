<?php
/**
 * Single post — thin loader.
 *
 * The article design lives in template-parts/single-article.php (the editor
 * default + safe fallback). When the Code Studio "Single Post" context has an
 * editable Template (HTML/PHP) override AND PHP sections are unlocked, that
 * override runs instead — syntax-checked on save, with a guaranteed file
 * fallback. CSS/JS are injected by the single context (single source), so they
 * are editable too. Comments render via comments.php from inside the body.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	if ( function_exists( 'lavtheme_cs_single_render' ) ) {
		lavtheme_cs_single_render();
	} else {
		$lav_part = get_theme_file_path( 'template-parts/single-article.php' );
		if ( is_readable( $lav_part ) ) {
			include $lav_part;
		}
	}
endwhile;

get_footer();
