<?php
/**
 * Single page template.
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
			<h1 class="block-title"><?php the_title(); ?></h1>
		</div>
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
