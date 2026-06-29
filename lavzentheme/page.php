<?php
/**
 * Single page.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="page-<?php the_ID(); ?>" <?php post_class( 'lavzen-page glass' ); ?>>
		<header class="lavzen-page__head">
			<h1 class="lavzen-page__title"><?php the_title(); ?></h1>
		</header>
		<div class="lavzen-page__content entry-content">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'lavzentheme' ),
					'after'  => '</div>',
				)
			);
			?>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;

get_footer();
