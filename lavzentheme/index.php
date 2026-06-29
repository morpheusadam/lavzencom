<?php
/**
 * Main template fallback.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="lavzen-loop">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		}
		the_posts_pagination( array( 'mid_size' => 1 ) );
	} else {
		get_template_part( 'template-parts/content', 'none' );
	}
	?>
</div>
<?php
get_footer();
