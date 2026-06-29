<?php
/**
 * Blog layout (blog.html design). Shared by home.php, archive.php, search.php
 * and the dedicated Blog page. Reads the (already-filtered) global query.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="lav-blog">
	<div class="shell">

		<?php echo lavtheme_blog_head_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
		<?php echo lavtheme_blog_featured_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
		<?php echo lavtheme_blog_filterbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>

		<div class="blog-layout">
			<div class="posts" id="posts">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						echo lavtheme_blog_card_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
					endwhile;
				else :
					?>
					<div class="empty"><?php esc_html_e( 'No articles match your filters. Try clearing some.', 'lavtheme' ); ?></div>
					<?php
				endif;
				?>
			</div>

			<?php echo lavtheme_blog_sidebar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
		</div>

		<?php echo lavtheme_blog_pagination_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder. ?>
	</div>
</section>
