<?php
/**
 * Default content part for the loop.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'lavzen-entry' ); ?>>
	<header class="lavzen-entry__head">
		<?php
		if ( is_singular() ) {
			the_title( '<h1 class="lavzen-entry__title">', '</h1>' );
		} else {
			the_title( sprintf( '<h2 class="lavzen-entry__title"><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' );
		}
		?>
	</header>
	<div class="lavzen-entry__content">
		<?php
		if ( is_singular() ) {
			the_content();
			wp_link_pages();
		} else {
			the_excerpt();
		}
		?>
	</div>
</article>
