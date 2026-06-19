<?php
/**
 * Fallback template — blog index / posts page.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="block lavtheme-content" id="content">
	<div class="block-head">
		<div>
			<?php if ( is_home() && ! is_front_page() ) : ?>
				<h1 class="block-title"><?php single_post_title(); ?></h1>
			<?php else : ?>
				<h1 class="block-title"><?php esc_html_e( 'Latest', 'lavtheme' ); ?></h1>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="blog-track" style="flex-wrap:wrap;overflow:visible;">
			<?php
			while ( have_posts() ) :
				the_post();
				lavtheme_part( 'content', array( 'post_id' => get_the_ID() ) );
			endwhile;
			?>
		</div>
		<div class="blog-nav" style="justify-content:center;margin-top:24px;">
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		</div>
	<?php else : ?>
		<p class="block-intro"><?php esc_html_e( 'Nothing published yet.', 'lavtheme' ); ?></p>
	<?php endif; ?>
</section>
<?php
get_footer();
