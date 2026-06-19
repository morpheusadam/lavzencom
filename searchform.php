<?php
/**
 * Search form — styled to match the hero searchbar.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;
?>
<form role="search" method="get" class="hero-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="hero-searchbar">
		<svg class="si" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
		<label class="screen-reader-text" for="lavtheme-s"><?php esc_html_e( 'Search for:', 'lavtheme' ); ?></label>
		<input type="search" id="lavtheme-s" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e( 'Search…', 'lavtheme' ); ?>">
		<button type="submit" class="go"><?php esc_html_e( 'Search', 'lavtheme' ); ?></button>
	</div>
</form>
