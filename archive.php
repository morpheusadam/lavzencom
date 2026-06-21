<?php
/**
 * Archive template (category, tag, author, date, and any other archive).
 *
 * Post-related archives (category/tag/author/date) render the blog design via
 * lavtheme_is_blog(); anything else falls back to a simple content loop. The
 * EDD download archive and download taxonomies have their own templates.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( function_exists( 'lavtheme_is_blog' ) && lavtheme_is_blog() ) {
	lavtheme_blog_render();
} else {
	?>
	<section class="block lavtheme-content" id="content">
		<div class="block-head">
			<div>
				<div class="kicker"><?php echo esc_html( get_the_archive_title() ); ?></div>
				<?php the_archive_description( '<p class="block-intro">', '</p>' ); ?>
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
			<p class="block-intro"><?php esc_html_e( 'No results found.', 'lavtheme' ); ?></p>
		<?php endif; ?>
	</section>
	<?php
}

get_footer();
