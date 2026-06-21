<?php
/**
 * Single post template.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="block lavtheme-content glass" id="content" style="padding:48px;">
		<div class="block-head">
			<div>
				<div class="kicker"><?php echo esc_html( get_the_date() ); ?></div>
				<h1 class="block-title"><?php the_title(); ?></h1>
				<p class="block-intro"><?php printf( /* translators: %s: author name. */ esc_html__( 'By %s', 'lavtheme' ), esc_html( get_the_author() ) ); ?></p>
			</div>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-thumb" style="aspect-ratio:16/7;border-radius:var(--r-lg);overflow:hidden;">
				<?php the_post_thumbnail( 'large', array( 'style' => 'width:100%;height:100%;object-fit:cover;' ) ); ?>
			</div>
		<?php endif; ?>
		<div class="entry-content" style="color:var(--text-2);line-height:1.7;">
			<?php
			the_content();
			wp_link_pages();
			?>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
