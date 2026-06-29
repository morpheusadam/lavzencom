<?php
/**
 * Single post.
 *
 * The article body lives in template-parts/single-article.php. The Code Studio
 * "Single Post" context (Phase 3) can override it, but the file is always the
 * guaranteed fallback.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/single-article' );
endwhile;

get_footer();
