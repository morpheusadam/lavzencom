<?php
/**
 * Archive (category, tag, author, date, and other archives).
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();
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
get_footer();
