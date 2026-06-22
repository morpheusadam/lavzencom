<?php
/**
 * Error page body (404 + generic). Rendered standalone — no site header/footer.
 * This is the editable "Template (PHP/HTML)" body for the Code Studio "404 /
 * Error" context; assets/css/404.css + assets/js/404.js are its CSS/JS editors.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;

/* A few helpful destinations: top categories, blog, shop. */
$lav_links = array();
$lav_links[] = array( __( 'Home', 'lavtheme' ), home_url( '/' ) );
if ( function_exists( 'lavtheme_blog_url' ) ) {
	$lav_links[] = array( __( 'Blog', 'lavtheme' ), lavtheme_blog_url() );
}
if ( function_exists( 'lavtheme_shop_url' ) ) {
	$lav_links[] = array( __( 'Shop', 'lavtheme' ), lavtheme_shop_url() );
}
$lav_cats = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 4, 'hide_empty' => true ) );
if ( $lav_cats && ! is_wp_error( $lav_cats ) ) {
	foreach ( $lav_cats as $lav_c ) {
		$lav_links[] = array( $lav_c->name, get_category_link( $lav_c->term_id ) );
	}
}
?>
<div class="lav-404" id="content">
	<div class="e-card glass">

		<div class="e-code" data-parallax aria-hidden="true">
			<span class="digit">4</span><span class="digit mid">0</span><span class="digit">4</span>
		</div>

		<span class="e-kicker">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
			<?php esc_html_e( 'Error 404', 'lavtheme' ); ?>
		</span>

		<h1 class="e-title"><?php esc_html_e( 'This page took a wrong turn', 'lavtheme' ); ?></h1>

		<p class="e-text"><?php esc_html_e( 'The page you are looking for moved, was renamed, or never existed. Try a search or jump back to familiar ground.', 'lavtheme' ); ?></p>

		<form class="e-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg class="e-sicon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
			<label class="screen-reader-text" for="lav404-s"><?php esc_html_e( 'Search', 'lavtheme' ); ?></label>
			<input type="search" id="lav404-s" name="s" placeholder="<?php esc_attr_e( 'Search the site…', 'lavtheme' ); ?>" autocomplete="off">
			<button type="submit"><?php esc_html_e( 'Search', 'lavtheme' ); ?></button>
		</form>

		<div class="e-actions">
			<a class="e-btn primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M9 22V12h6v10"/></svg>
				<?php esc_html_e( 'Back to home', 'lavtheme' ); ?>
			</a>
			<?php if ( function_exists( 'lavtheme_blog_url' ) ) : ?>
				<a class="e-btn ghost" href="<?php echo esc_url( lavtheme_blog_url() ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
					<?php esc_html_e( 'Read the blog', 'lavtheme' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( count( $lav_links ) > 1 ) : ?>
			<nav class="e-links" aria-label="<?php esc_attr_e( 'Helpful links', 'lavtheme' ); ?>">
				<span class="e-llabel"><?php esc_html_e( 'Popular destinations', 'lavtheme' ); ?></span>
				<div class="e-lrow">
					<?php foreach ( $lav_links as $lav_l ) : ?>
						<a href="<?php echo esc_url( $lav_l[1] ); ?>"><?php echo esc_html( $lav_l[0] ); ?></a>
					<?php endforeach; ?>
				</div>
			</nav>
		<?php endif; ?>

	</div>
</div><!-- #content -->
