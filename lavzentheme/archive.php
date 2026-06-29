<?php
/**
 * Archive — one entry point for every archive via the template hierarchy.
 *
 * The EDD download archive and its taxonomies (download_category / download_tag)
 * render the shop; post archives (category / tag / author / date / search-as-archive)
 * render the post grid. This single file replaces the redundant archive-download.php
 * + taxonomy-download_category.php + taxonomy-download_tag.php clones — WordPress
 * falls back here for all of them.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'lavzen_is_shop' ) && lavzen_is_shop() ) {
	get_template_part( 'template-parts/shop' );
} else {
	?>
	<section class="lavzen-archive">
		<header class="lavzen-archive__head">
			<h1 class="lavzen-archive__title"><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<div class="lavzen-archive__intro">', '</div>' ); ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="lavzen-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', get_post_type() );
				endwhile;
				?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</section>
	<?php
}

get_footer();
