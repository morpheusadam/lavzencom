<?php
/**
 * "No results" content part.
 *
 * @package Lavzen
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="lavzen-noresults">
	<h2 class="lavzen-noresults__title"><?php esc_html_e( 'Nothing found', 'lavzentheme' ); ?></h2>
	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'No results matched your search. Try different keywords.', 'lavzentheme' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing has been published here yet.', 'lavzentheme' ); ?></p>
	<?php endif; ?>
	<?php get_search_form(); ?>
</div>
