<?php
/**
 * Search results.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="lavzen-search">
	<header class="lavzen-search__head">
		<h1 class="lavzen-search__title">
			<?php
			/* translators: %s: search query. */
			printf( esc_html__( 'Results for “%s”', 'lavzentheme' ), '<span>' . esc_html( get_search_query() ) . '</span>' );
			?>
		</h1>
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
get_footer();
